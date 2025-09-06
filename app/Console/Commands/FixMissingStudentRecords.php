<?php

namespace App\Console\Commands;

use App\Events\ApplicantEnrolled;
use App\Models\Applicant;
use App\Models\Student;
use App\Services\ApplicantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FixMissingStudentRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-missing-student-records {--process : Actually process the records instead of just showing them} {--debug : Show detailed debug information} {--applicant_id= : Fix a specific applicant by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix missing student records for officially enrolled applicants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for officially enrolled applicants without student records...');
        
        $query = Applicant::where('status', 'Officially Enrolled');
        
        // If specific applicant ID is provided, only process that one
        if ($this->option('applicant_id')) {
            $query->where('id', $this->option('applicant_id'));
        }
        
        $applicants = $query->get();
        
        $this->info("Found {$applicants->count()} officially enrolled applicants.");
        
        $missing = 0;
        $fixed = 0;
        
        $shouldProcess = $this->option('process');
        $debug = $this->option('debug');
        
        if (!$shouldProcess) {
            $this->warn('Running in dry-run mode. Use --process to actually create missing student records.');
        }
        
        if ($debug) {
            $this->warn('Debug mode enabled - will show detailed information.');
        }
        
        foreach ($applicants as $applicant) {
            // Check if a corresponding student record exists
            $hasStudent = false;
            
            // Check by student_number
            if (!empty($applicant->student_number)) {
                $studentByNumber = Student::where('student_number', $applicant->student_number)->first();
                if ($studentByNumber) {
                    $hasStudent = true;
                    if ($debug) {
                        $this->info("Found student by student_number: {$applicant->student_number}");
                    }
                }
            }
            
            // Check by email
            if (!$hasStudent) {
                $studentByEmail = Student::where('email', $applicant->email)->first();
                if ($studentByEmail) {
                    $hasStudent = true;
                    if ($debug) {
                        $this->info("Found student by email: {$applicant->email}");
                    }
                }
            }
            
            // If no student record found, fix it
            if (!$hasStudent) {
                $missing++;
                $this->warn("Applicant #{$applicant->id} ({$applicant->first_name} {$applicant->last_name}) is officially enrolled but has no student record.");
                
                if ($debug) {
                    // Display more information about the applicant
                    $this->line("Applicant details:");
                    $this->line("  Email: {$applicant->email}");
                    $this->line("  Student Number: " . ($applicant->student_number ?? 'Not set'));
                    $this->line("  Campus ID: " . ($applicant->campus_id ?? 'Not set'));
                    $this->line("  Program: " . ($applicant->desired_program ?? 'Not set') . 
                        ($applicant->program_id ? " (ID: {$applicant->program_id})" : ''));
                    
                    // Check if campus exists
                    if ($applicant->campus_id) {
                        $campus = \App\Models\Campus::find($applicant->campus_id);
                        $this->line("  Campus exists: " . ($campus ? 'Yes' : 'No'));
                    }
                }
                
                if ($shouldProcess) {
                    try {
                        // Begin transaction
                        DB::beginTransaction();
                        
                        // Try direct creation through applicant service
                        $applicantService = app(ApplicantService::class);
                        $student = $applicantService->ensureStudentExists($applicant);
                        
                        if (!$student) {
                            // If that didn't work, try manual student creation with detailed error handling
                            if ($debug) {
                                $this->line("Direct creation failed, trying direct event dispatch...");
                            }
                            
                            // Validate required fields
                            $this->validateApplicantData($applicant);
                            
                            // Trigger student creation
                            event(new ApplicantEnrolled($applicant));
                            
                            // Check if student was created
                            if (!empty($applicant->student_number)) {
                                $student = Student::where('student_number', $applicant->student_number)->first();
                            } else {
                                $student = Student::where('email', $applicant->email)->first();
                            }
                        }
                        
                        if ($student) {
                            DB::commit();
                            $fixed++;
                            $this->info("Created student record #{$student->id} with student number {$student->student_number}");
                        } else {
                            DB::rollBack();
                            $this->error("Failed to create student record for applicant #{$applicant->id}");
                        }
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $this->error("Error creating student for applicant #{$applicant->id}: " . $e->getMessage());
                        if ($debug) {
                            $this->error("Stack trace: " . $e->getTraceAsString());
                        }
                        Log::error("Error in FixMissingStudentRecords command", [
                            'applicant_id' => $applicant->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            }
        }
        
        if ($missing === 0) {
            $this->info('All officially enrolled applicants have corresponding student records.');
        } else {
            $this->warn("Found {$missing} officially enrolled applicants without student records.");
            
            if ($shouldProcess) {
                $this->info("Fixed {$fixed} of {$missing} missing student records.");
            } else {
                $this->info("Run with --process option to fix these issues.");
            }
        }
        
        return 0;
    }
    
    /**
     * Validate that the applicant has all required fields for student creation
     */
    private function validateApplicantData(Applicant $applicant): void
    {
        $errors = [];
        
        if (empty($applicant->email)) {
            $errors[] = "Missing email";
        }
        
        if (empty($applicant->campus_id)) {
            $errors[] = "Missing campus_id";
        } else {
            $campus = \App\Models\Campus::find($applicant->campus_id);
            if (!$campus) {
                $errors[] = "Invalid campus_id: {$applicant->campus_id} - campus not found";
            }
        }
        
        // Check for program info
        $hasProgram = false;
        if (!empty($applicant->program_id)) {
            $program = \App\Models\Program::find($applicant->program_id);
            if ($program) {
                $hasProgram = true;
            } else {
                $errors[] = "Invalid program_id: {$applicant->program_id} - program not found";
            }
        }
        
        if (!$hasProgram && empty($applicant->desired_program)) {
            $errors[] = "Missing program information - both program_id and desired_program are empty";
        }
        
        if (!empty($errors)) {
            throw new \Exception("Applicant data validation failed: " . implode(", ", $errors));
        }
    }
} 