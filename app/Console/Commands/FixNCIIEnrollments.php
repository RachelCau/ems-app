<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Models\Campus;
use App\Models\EnrolledCourse;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Models\CourseCurriculum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FixNCIIEnrollments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-ncii-enrollments 
                           {--program= : Specific NCII program code to fix (e.g., Cookery-NCII)}
                           {--year-level=1 : Year level for course assignment}
                           {--semester=1 : Semester for course assignment}
                           {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix enrollment issues for NCII programs and assign courses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to fix NCII program enrollment issues...');
        
        // Check if we're in dry-run mode
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN MODE: No changes will be saved to the database');
        }
        
        // Get specific program if provided, or work with all NCII programs
        $programCode = $this->option('program');
        $yearLevel = (int)$this->option('year-level');
        $semester = (int)$this->option('semester');
        
        if ($programCode) {
            $program = Program::where('code', $programCode)->first();
            if (!$program) {
                $this->error("Program with code '{$programCode}' not found");
                return 1;
            }
            $programs = [$program];
            $this->info("Working with specific program: {$program->name} ({$program->code})");
        } else {
            // Find all NCII programs
            $programs = Program::where('code', 'like', '%NCII%')
                ->orWhere('name', 'like', '%NCII%')
                ->orWhere('code', 'like', '%NC II%')
                ->orWhere('name', 'like', '%NC II%')
                ->get();
            
            if ($programs->isEmpty()) {
                $this->error("No NCII programs found");
                return 1;
            }
            
            $this->info("Working with all NCII programs (" . $programs->count() . " found)");
            foreach ($programs as $program) {
                $this->info("- {$program->name} ({$program->code})");
            }
        }
        
        // Get active academic year
        $academicYear = null;
        
        // First try with is_active field which should exist
        try {
            $academicYear = AcademicYear::where('is_active', true)->first();
        } catch (\Exception $e) {
            $this->warn("Error looking up active academic year: " . $e->getMessage());
        }
        
        // If not found and is_current field might exist, try that
        if (!$academicYear) {
            try {
                if (Schema::hasColumn('academic_years', 'is_current')) {
                    $academicYear = AcademicYear::where('is_current', true)->first();
                }
            } catch (\Exception $e) {
                $this->warn("Error looking up current academic year: " . $e->getMessage());
            }
        }
        
        // Still no academic year, try to get the latest
        if (!$academicYear) {
            try {
                $academicYear = AcademicYear::latest('id')->first();
            } catch (\Exception $e) {
                $this->error("Failed to get any academic year: " . $e->getMessage());
                return 1;
            }
            
            if (!$academicYear) {
                $this->error("No academic year found in the system");
                return 1;
            }
        }
        
        $this->info("Using academic year: {$academicYear->name}");
        
        // Process each program
        $totalFixed = 0;
        $totalEnrollments = 0;
        $totalCoursesAssigned = 0;
        
        foreach ($programs as $program) {
            $this->info("\nProcessing program: {$program->name} ({$program->code})");
            
            // Find all applicants that match this NCII program
            $applicants = $this->findNCIIApplicants($program);
            
            $this->info("Found {$applicants->count()} applicants potentially for program {$program->code}");
            
            if ($applicants->isEmpty() && !$dryRun) {
                // Create a sample applicant for testing
                $this->info("No applicants found. Creating a sample applicant for testing");
                $this->createSampleNCIIApplicant($program, $academicYear);
                
                // Re-fetch applicants
                $applicants = $this->findNCIIApplicants($program);
                $this->info("Created and found {$applicants->count()} applicants");
            }
            
            // Process each applicant
            $fixedCount = 0;
            $enrollmentCount = 0;
            $coursesAssigned = 0;
            
            foreach ($applicants as $applicant) {
                $this->info("Processing applicant #{$applicant->id}: {$applicant->full_name}");
                
                // Check & fix student record
                $student = $this->findOrCreateStudentRecord($applicant, $program, $dryRun);
                if (!$student) {
                    $this->warn("  - Could not find or create student record");
                    continue;
                }
                
                // Check & fix student enrollment record
                $enrollment = $this->findOrCreateEnrollmentRecord($applicant, $student, $program, $academicYear, $dryRun);
                if (!$enrollment) {
                    $this->warn("  - Could not find or create enrollment record");
                    continue;
                }
                
                // Ensure program code is set correctly on all related models
                $fixed = $this->ensureProgramCodeConsistency($applicant, $student, $enrollment, $program, $dryRun);
                
                if ($fixed) {
                    $fixedCount++;
                    $this->info("  ✅ Fixed program code consistency");
                }
                
                // Check if student has courses assigned
                $existingCourses = EnrolledCourse::where('student_enrollment_id', $enrollment->id)->count();
                $this->info("  - Current enrolled courses: {$existingCourses}");
                
                // Assign courses if none exist
                if ($existingCourses == 0 && !$dryRun) {
                    $coursesAdded = $this->assignCoursesFromCurriculum(
                        $enrollment, 
                        $student, 
                        $program, 
                        $academicYear, 
                        $yearLevel, 
                        $semester
                    );
                    
                    if ($coursesAdded > 0) {
                        $this->info("  ✅ Added {$coursesAdded} courses to student");
                        $coursesAssigned += $coursesAdded;
                        $enrollmentCount++;
                    }
                }
            }
            
            $totalFixed += $fixedCount;
            $totalEnrollments += $enrollmentCount;
            $totalCoursesAssigned += $coursesAssigned;
            $this->info("Fixed {$fixedCount} applicants for program {$program->code}");
            $this->info("Added courses to {$enrollmentCount} students");
            $this->info("Assigned {$coursesAssigned} total courses");
        }
        
        $this->info("\nTotal fixes applied: {$totalFixed}");
        $this->info("Total enrollments with courses: {$totalEnrollments}");
        $this->info("Total courses assigned: {$totalCoursesAssigned}");
        
        if ($dryRun) {
            $this->warn('DRY RUN COMPLETE: Run again without --dry-run to apply changes');
        } else {
            $this->info('All changes have been applied to the database');
        }
        
        return 0;
    }
    
    /**
     * Find applicants that might be related to an NCII program
     */
    private function findNCIIApplicants(Program $program)
    {
        return Applicant::where(function($query) use ($program) {
            // Match by program_id
            $query->where('program_id', $program->id);
            
            // Match by desired_program exact match
            $query->orWhere('desired_program', $program->code);
            
            // Match by partial name/code for NCII programs
            $query->orWhere('desired_program', 'like', "%{$program->code}%");
            $query->orWhere('desired_program', 'like', "%{$program->name}%");
            
            // General NCII matches
            $query->orWhere('desired_program', 'like', '%NCII%');
            $query->orWhere('desired_program', 'like', '%NC II%');
            
            // If the program is something specific like "Cookery NCII"
            $programNameWithoutNCII = str_replace(['NCII', 'NC II', 'NC2'], '', $program->name);
            $programNameWithoutNCII = trim($programNameWithoutNCII);
            if (!empty($programNameWithoutNCII)) {
                $query->orWhere('desired_program', 'like', "%{$programNameWithoutNCII}%");
            }
        })
        ->where(function($query) {
            // Should be officially enrolled or similar status
            $query->where('status', 'Officially Enrolled')
                ->orWhere('enrollment_status', 'Officially Enrolled')
                ->orWhere('status', 'for enrollment')
                ->orWhere('status', 'enrolled');
        })
        ->get();
    }
    
    /**
     * Find or create a student record for an applicant
     */
    private function findOrCreateStudentRecord(Applicant $applicant, Program $program, bool $dryRun): ?Student
    {
        // First check if student is already associated
        if ($applicant->student) {
            $this->info("  - Student record already exists: #{$applicant->student->id}");
            return $applicant->student;
        }
        
        // Try to find by student number if available
        if (!empty($applicant->student_number)) {
            $student = Student::where('student_number', $applicant->student_number)->first();
            if ($student) {
                $this->info("  - Found student by student_number: #{$student->id}");
                return $student;
            }
        }
        
        // Try to find by email
        if (!empty($applicant->email)) {
            $student = Student::where('email', $applicant->email)->first();
            if ($student) {
                $this->info("  - Found student by email: #{$student->id}");
                return $student;
            }
        }
        
        // If we're in dry-run mode, don't create a new record
        if ($dryRun) {
            $this->info("  - Would create new student record (skipped in dry-run)");
            return null;
        }
        
        // Create new student record
        try {
            $student = new Student();
            $student->student_number = $applicant->student_number ?? 'TEMP-' . uniqid();
            $student->email = $applicant->email;
            $student->first_name = $applicant->first_name;
            $student->middle_name = $applicant->middle_name;
            $student->last_name = $applicant->last_name;
            $student->suffix = $applicant->suffix;
            $student->program_code = $program->code;
            $student->program_id = $program->id;
            $student->campus_id = $applicant->campus_id;
            $student->status = 'active';
            $student->save();
            
            $this->info("  - Created new student record: #{$student->id}");
            return $student;
        } catch (\Exception $e) {
            $this->error("  - Failed to create student record: " . $e->getMessage());
            Log::error("Failed to create student record for applicant #{$applicant->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    /**
     * Find or create a student enrollment record
     */
    private function findOrCreateEnrollmentRecord(Applicant $applicant, Student $student, Program $program, AcademicYear $academicYear, bool $dryRun): ?StudentEnrollment
    {
        // Look for existing enrollment
        $enrollment = StudentEnrollment::where('applicant_id', $applicant->id)->first();
        
        if ($enrollment) {
            $this->info("  - Enrollment record already exists: #{$enrollment->id}");
            return $enrollment;
        }
        
        // If we're in dry-run mode, don't create a new record
        if ($dryRun) {
            $this->info("  - Would create new enrollment record (skipped in dry-run)");
            return null;
        }
        
        // Create new enrollment record
        try {
            $enrollment = new StudentEnrollment();
            $enrollment->applicant_id = $applicant->id;
            $enrollment->student_id = $student->id;
            $enrollment->program_code = $program->code;
            $enrollment->program_id = $program->id;
            $enrollment->status = 'enrolled';
            $enrollment->academic_year_id = $academicYear->id;
            $enrollment->year_level = $this->option('year-level');
            $enrollment->semester = $this->option('semester');
            
            // If campus ID is in the fillable array, set it
            if (in_array('campus_id', $enrollment->getFillable())) {
                $enrollment->campus_id = $applicant->campus_id;
            }
            
            $enrollment->save();
            
            $this->info("  - Created new enrollment record: #{$enrollment->id}");
            return $enrollment;
        } catch (\Exception $e) {
            $this->error("  - Failed to create enrollment record: " . $e->getMessage());
            Log::error("Failed to create enrollment record for applicant #{$applicant->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    /**
     * Ensure program code consistency across all related models
     */
    private function ensureProgramCodeConsistency(Applicant $applicant, Student $student, StudentEnrollment $enrollment, Program $program, bool $dryRun): bool
    {
        $fixed = false;
        
        // Check applicant
        if ($applicant->program_id != $program->id) {
            $this->info("  - Applicant program_id inconsistent: {$applicant->program_id} vs {$program->id}");
            if (!$dryRun) {
                $applicant->program_id = $program->id;
                $fixed = true;
            }
        }
        
        if (empty($applicant->desired_program) || $applicant->desired_program != $program->code) {
            $this->info("  - Applicant desired_program inconsistent: {$applicant->desired_program} vs {$program->code}");
            if (!$dryRun) {
                $applicant->desired_program = $program->code;
                $fixed = true;
            }
        }
        
        // Check student - check if fields exist before setting them
        $studentFields = Schema::getColumnListing('students');
        
        if (in_array('program_id', $studentFields) && $student->program_id != $program->id) {
            $this->info("  - Student program_id inconsistent: {$student->program_id} vs {$program->id}");
            if (!$dryRun) {
                $student->program_id = $program->id;
                $fixed = true;
            }
        }
        
        if (in_array('program_code', $studentFields) && 
            (empty($student->program_code) || $student->program_code != $program->code)) {
            $this->info("  - Student program_code inconsistent: {$student->program_code} vs {$program->code}");
            if (!$dryRun) {
                $student->program_code = $program->code;
                $fixed = true;
            }
        }
        
        // Check enrollment
        $enrollmentFields = Schema::getColumnListing('student_enrollments');
        
        if (in_array('program_code', $enrollmentFields) && 
            (empty($enrollment->program_code) || $enrollment->program_code != $program->code)) {
            $this->info("  - Enrollment program_code inconsistent: {$enrollment->program_code} vs {$program->code}");
            if (!$dryRun) {
                $enrollment->program_code = $program->code;
                $fixed = true;
            }
        }
        
        if (in_array('program_id', $enrollmentFields) && 
            (empty($enrollment->program_id) || $enrollment->program_id != $program->id)) {
            $this->info("  - Enrollment program_id inconsistent: {$enrollment->program_id} vs {$program->id}");
            if (!$dryRun) {
                $enrollment->program_id = $program->id;
                $fixed = true;
            }
        }
        
        // Save all changes if not in dry-run mode
        if ($fixed && !$dryRun) {
            DB::beginTransaction();
            try {
                $applicant->save();
                $student->save();
                $enrollment->save();
                DB::commit();
                $this->info("  - Saved all changes successfully");
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("  - Failed to save changes: " . $e->getMessage());
                Log::error("Failed to save program code consistency changes", [
                    'applicant_id' => $applicant->id,
                    'student_id' => $student->id,
                    'enrollment_id' => $enrollment->id,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        }
        
        return $fixed;
    }
    
    /**
     * Assign courses from curriculum to a student enrollment
     */
    private function assignCoursesFromCurriculum(
        StudentEnrollment $enrollment, 
        Student $student, 
        Program $program, 
        AcademicYear $academicYear,
        int $yearLevel,
        int $semester
    ): int {
        $this->info("  - Assigning courses from curriculum for Year {$yearLevel}, Semester {$semester}");
        
        // Find the curriculum
        $curriculum = CourseCurriculum::where([
            'program_id' => $program->id,
            'year_level' => $yearLevel,
            'semester' => $semester,
            'is_active' => true,
        ])->first();
        
        if (!$curriculum) {
            // Try to find any curriculum for this program
            $curriculum = CourseCurriculum::where([
                'program_id' => $program->id,
                'is_active' => true,
            ])->first();
            
            if (!$curriculum) {
                $this->warn("  - No curriculum found for this program");
                return 0;
            }
        }
        
        $this->info("  - Using curriculum: {$curriculum->name}");
        
        // Get the courses from the curriculum
        $curriculumCourses = $curriculum->courses;
        
        if ($curriculumCourses->isEmpty()) {
            $this->warn("  - No courses found in curriculum");
            return 0;
        }
        
        $this->info("  - Found {$curriculumCourses->count()} courses in curriculum");
        
        // Assign each course
        $coursesAdded = 0;
        
        foreach ($curriculumCourses as $course) {
            try {
                EnrolledCourse::create([
                    'student_enrollment_id' => $enrollment->id,
                    'student_number' => $student->student_number,
                    'course_id' => $course->id,
                    'status' => 'enrolled',
                    'grade' => null,
                ]);
                
                $coursesAdded++;
                $this->info("  - Added course: {$course->name} ({$course->code})");
                
            } catch (\Exception $e) {
                $this->error("  - Failed to add course {$course->name}: {$e->getMessage()}");
            }
        }
        
        return $coursesAdded;
    }
    
    /**
     * Create a sample NCII applicant for testing
     */
    private function createSampleNCIIApplicant(Program $program, AcademicYear $academicYear): void
    {
        // Get or create a campus
        $campus = Campus::first();
        if (!$campus) {
            $campus = new Campus();
            $campus->name = 'Main Campus';
            $campus->address = 'Main Street';
            $campus->save();
        }
        
        // Get admin user ID for applicant records
        $adminUser = User::first();
        if (!$adminUser) {
            // Create a default user if none exists
            $adminUser = new User();
            $adminUser->name = 'Admin User';
            $adminUser->email = 'admin@example.com';
            $adminUser->password = bcrypt('password');
            $adminUser->email_verified_at = now();
            $adminUser->save();
        }
        
        // Create a sample applicant
        $email = strtolower(str_replace(' ', '_', $program->code)) . "_test_" . time() . "@example.com";
        
        // Create a new applicant
        $applicant = new Applicant();
        $applicant->first_name = explode(' ', $program->name)[0] ?? "NCII";
        $applicant->last_name = "Test";
        $applicant->email = $email;
        $applicant->mobile = "09".rand(100000000, 999999999);
        $applicant->sex = 'Male';
        $applicant->program_id = $program->id;
        $applicant->desired_program = $program->code;
        $applicant->applicant_number = 'SAMPLE-' . uniqid();
        $applicant->student_number = 'NCII-' . rand(10000, 99999);
        $applicant->status = 'Officially Enrolled';
        $applicant->campus_id = $campus->id;
        $applicant->academic_year_id = $academicYear->id;
        $applicant->user_id = $adminUser->id;
        $applicant->save();
        
        $this->info("Created sample applicant: {$applicant->full_name}");
    }
}
