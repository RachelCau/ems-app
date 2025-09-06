<?php

namespace App\Console\Commands;

use App\Models\Applicant;
use App\Models\Campus;
use App\Models\Program;
use App\Models\Student;
use App\Events\ApplicantEnrolled;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class FixApplicant43 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-applicant-43';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Mary Cucharo (Applicant #43) enrollment issue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Looking for Applicant #43...');
        
        $applicant = Applicant::find(43);
        
        if (!$applicant) {
            $this->error('Applicant #43 not found!');
            return 1;
        }
        
        $this->info("Found Applicant: {$applicant->first_name} {$applicant->last_name}");
        
        // Debug the current state before any changes
        $this->info("\n=== INITIAL STATE ===");
        $this->logApplicantDetails($applicant);
        $this->checkCampusRelationship($applicant);
        $this->checkProgramRelationship($applicant);
        
        // Check and fix missing data
        $this->fixApplicantData($applicant);
        
        // Debug after fixes
        $this->info("\n=== AFTER FIXES ===");
        $this->logApplicantDetails($applicant);
        
        // Check if a student already exists
        $student = Student::where('email', $applicant->email)
            ->orWhere(function($query) use ($applicant) {
                if (!empty($applicant->student_number)) {
                    $query->where('student_number', $applicant->student_number);
                }
            })
            ->first();
        
        if ($student) {
            $this->info("Student record already exists: #{$student->id}, {$student->first_name} {$student->last_name}");
            return 0;
        }
        
        // Create student record
        $this->info('Creating student record...');
        
        try {
            DB::beginTransaction();
            
            // Test the student number generation first, to see if that's the issue
            $this->info("\n=== TESTING STUDENT NUMBER GENERATION ===");
            $applicantService = app(\App\Services\ApplicantService::class);
            
            try {
                $testStudentNumber = $applicantService->generateStudentNumber($applicant);
                $this->info("Student number generation successful: {$testStudentNumber}");
            } catch (\Exception $e) {
                $this->error("Student number generation failed: " . $e->getMessage());
                $this->error("File: " . $e->getFile() . " line " . $e->getLine());
                $this->line("Fixing any issues...");
                
                // Check campus relationship again and fix if needed
                $this->manuallyFixCampusRelationship($applicant);
                
                // Try again
                try {
                    $testStudentNumber = $applicantService->generateStudentNumber($applicant);
                    $this->info("Student number generation now successful: {$testStudentNumber}");
                } catch (\Exception $e) {
                    $this->error("Student number generation still failing after fixes: " . $e->getMessage());
                    $this->error("Cannot continue. Please fix campus relationship manually.");
                    DB::rollBack();
                    return 1;
                }
            }
            
            // Make sure the applicant is officially enrolled
            $applicant->status = 'Officially Enrolled';
            $applicant->save();
            
            // Manually go through the CreateStudentFromApplicant steps for better visibility
            $this->info("\n=== MANUAL STUDENT CREATION ===");
            
            // 1. Generate student number
            $studentNumber = $applicantService->generateStudentNumber($applicant);
            $this->info("Generated student number: {$studentNumber}");
            
            // 2. Create user account
            $password = \Illuminate\Support\Str::random(8);
            $user = User::where('username', $studentNumber)->first();
            if (!$user) {
                $user = new \App\Models\User();
                $user->username = $studentNumber;
                $user->email = $applicant->email;
                $user->user_type = 'student';
                $user->password = bcrypt($password);
                $user->temp_password = $password;
                $user->save();
                $this->info("Created new user with username {$user->username}");
            } else {
                $user->password = bcrypt($password);
                $user->temp_password = $password;
                $user->save();
                $this->info("Updated existing user with username {$user->username}");
            }
            
            // 3. Get current academic year
            $academicYear = \App\Models\AcademicYear::where('is_active', true)->first();
            if (!$academicYear) {
                $academicYear = \App\Models\AcademicYear::latest('id')->first();
            }
            $this->info("Using academic year: " . ($academicYear ? $academicYear->name : 'None found'));
            
            // 4. Create student record
            $student = Student::where('student_number', $studentNumber)->first();
            if (!$student) {
                $student = new Student();
            }
            
            $student->user_id = $user->id;
            $student->student_number = $studentNumber;
            $student->first_name = $applicant->first_name;
            $student->middle_name = $applicant->middle_name;
            $student->last_name = $applicant->last_name;
            $student->suffix = $applicant->suffix ?? null;
            $student->sex = $applicant->sex ?? 'Male';
            
            // Handle mobile_number - CRITICAL: This is required in the database schema
            if (!empty($applicant->mobile)) {
                $student->mobile_number = $applicant->mobile;
            } else {
                // Must provide a default because mobile_number cannot be null in the database
                $student->mobile_number = '00000000000'; // Placeholder value
                $this->info("Setting placeholder mobile_number because it can't be null");
            }
            
            $student->email = $applicant->email;
            $student->campus_id = $applicant->campus_id;
            
            // Set program code
            if ($applicant->program) {
                $student->program_code = $applicant->program->code;
                $this->info("Setting program_code from relationship: {$applicant->program->code}");
            } elseif (!empty($applicant->desired_program)) {
                $student->program_code = $applicant->desired_program;
                $this->info("Setting program_code from desired_program: {$applicant->desired_program}");
            } elseif (!empty($applicant->program_id)) {
                $program = \App\Models\Program::find($applicant->program_id);
                if ($program) {
                    $student->program_code = $program->code;
                    $this->info("Setting program_code from program_id lookup: {$program->code}");
                }
            }
            
            // Set academic year and defaults
            $student->academic_year_id = $academicYear ? $academicYear->id : null;
            $student->year_level = 1;
            $student->semester = 1;
            $student->student_status = 'active';
            
            // Save the student
            $saved = $student->save();
            
            if ($saved) {
                // Update applicant with student number
                $applicant->student_number = $studentNumber;
                $applicant->save();
                
                $this->info("Student record successfully created!");
                $this->info("Student ID: {$student->id}");
                $this->info("Student Number: {$student->student_number}");
                
                DB::commit();
                return 0;
            } else {
                $this->error("Failed to save student record.");
                DB::rollBack();
                return 1;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error creating student: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . " line " . $e->getLine());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
    
    /**
     * Check and fix missing data in the applicant record
     */
    private function fixApplicantData(Applicant $applicant): void
    {
        $fixed = false;
        
        $this->info("Current applicant data:");
        $this->line("  Campus ID: " . ($applicant->campus_id ?? 'Not set'));
        $this->line("  Email: " . ($applicant->email ?? 'Not set'));
        $this->line("  Program ID: " . ($applicant->program_id ?? 'Not set'));
        $this->line("  Desired Program: " . ($applicant->desired_program ?? 'Not set'));
        
        // Fix campus_id if missing
        if (empty($applicant->campus_id)) {
            $campus = Campus::first();
            if ($campus) {
                $applicant->campus_id = $campus->id;
                $this->info("Setting campus_id to {$campus->id} ({$campus->name})");
                $fixed = true;
            } else {
                $this->warn("No campus found in the database!");
            }
        }
        
        // Fix program information if missing
        if (empty($applicant->program_id) && empty($applicant->desired_program)) {
            $program = Program::first();
            if ($program) {
                $applicant->program_id = $program->id;
                $applicant->desired_program = $program->code;
                $this->info("Setting program_id to {$program->id} ({$program->name}) and desired_program to {$program->code}");
                $fixed = true;
            } else {
                $this->warn("No program found in the database!");
            }
        }
        
        // Fix email if missing (very unlikely)
        if (empty($applicant->email)) {
            $applicant->email = "student{$applicant->id}@example.com";
            $this->info("Setting placeholder email to {$applicant->email}");
            $fixed = true;
        }
        
        // Save if any changes were made
        if ($fixed) {
            $applicant->save();
            $this->info("Applicant data fixed and saved.");
        } else {
            $this->info("No fixes needed for the applicant data.");
        }
    }
    
    /**
     * Log detailed information about the applicant
     */
    private function logApplicantDetails(Applicant $applicant): void
    {
        $this->info("Applicant #{$applicant->id} Details:");
        $this->line("  Name: {$applicant->first_name} {$applicant->last_name}");
        $this->line("  Email: {$applicant->email}");
        $this->line("  Status: {$applicant->status}");
        $this->line("  Student Number: " . ($applicant->student_number ?? 'Not set'));
        $this->line("  Campus ID: " . ($applicant->campus_id ?? 'Not set'));
        $this->line("  Program ID: " . ($applicant->program_id ?? 'Not set'));
        $this->line("  Desired Program: " . ($applicant->desired_program ?? 'Not set'));
    }
    
    /**
     * Check the campus relationship integrity
     */
    private function checkCampusRelationship(Applicant $applicant): void
    {
        $this->info("Checking campus relationship:");
        
        if (empty($applicant->campus_id)) {
            $this->warn("  No campus_id set on applicant");
            return;
        }
        
        $campus = Campus::find($applicant->campus_id);
        if ($campus) {
            $this->info("  Campus found: #{$campus->id} - {$campus->name}");
        } else {
            $this->error("  Campus not found for ID: {$applicant->campus_id}");
        }
        
        // Check the actual relationship method
        $relatedCampus = $applicant->campus;
        if ($relatedCampus) {
            $this->info("  Related campus: #{$relatedCampus->id} - {$relatedCampus->name}");
        } else {
            $this->error("  Related campus method returns null");
        }
    }
    
    /**
     * Check the program relationship integrity
     */
    private function checkProgramRelationship(Applicant $applicant): void
    {
        $this->info("Checking program relationship:");
        
        if (empty($applicant->program_id)) {
            $this->warn("  No program_id set on applicant");
        } else {
            $program = Program::find($applicant->program_id);
            if ($program) {
                $this->info("  Program found: #{$program->id} - {$program->name} ({$program->code})");
            } else {
                $this->error("  Program not found for ID: {$applicant->program_id}");
            }
        }
        
        // Check the actual relationship method
        $relatedProgram = $applicant->program;
        if ($relatedProgram) {
            $this->info("  Related program: #{$relatedProgram->id} - {$relatedProgram->name} ({$relatedProgram->code})");
        } else {
            $this->warn("  Related program method returns null");
        }
        
        // Check desired program
        if (!empty($applicant->desired_program)) {
            $this->info("  Desired program: {$applicant->desired_program}");
        } else {
            $this->warn("  No desired_program set");
        }
    }
    
    /**
     * Manually fix campus relationship issues
     */
    private function manuallyFixCampusRelationship(Applicant $applicant): void
    {
        $this->info("Attempting to fix campus relationship issues:");
        
        // Find a valid campus
        $campus = Campus::first();
        if (!$campus) {
            $this->error("No campuses found in database!");
            return;
        }
        
        // Update the campus_id field
        $applicant->campus_id = $campus->id;
        $saved = $applicant->save();
        
        if ($saved) {
            $this->info("  Fixed campus_id to {$campus->id} ({$campus->name})");
        } else {
            $this->error("  Failed to save applicant with new campus_id");
        }
        
        // Check again
        $this->checkCampusRelationship($applicant);
    }
} 