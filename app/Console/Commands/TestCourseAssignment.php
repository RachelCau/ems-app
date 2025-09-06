<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Applicant;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Program;
use App\Services\CourseAssignmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestCourseAssignment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-course-assignment 
                            {--enrollment_id= : Optional specific enrollment ID to test}
                            {--program= : Specific program to test}
                            {--force : Force assignment without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the course assignment process based on desired program';

    /**
     * Execute the console command.
     */
    public function handle(CourseAssignmentService $courseAssignmentService)
    {
        $this->info('Starting course assignment test...');

        // Get current academic year
        $academicYear = AcademicYear::where('is_active', true)->first();
        if (!$academicYear) {
            $this->error('No active academic year found!');
            return 1;
        }
        
        // Get semester (default to 1)
        $semester = 1;
        
        // Check if testing a specific enrollment
        $enrollmentId = $this->option('enrollment_id');
        if ($enrollmentId) {
            $enrollment = StudentEnrollment::find($enrollmentId);
            if (!$enrollment) {
                $this->error("Enrollment with ID {$enrollmentId} not found!");
                return 1;
            }
            
            $this->info("Testing course assignment for enrollment #{$enrollmentId} - {$enrollment->name}");
            $this->info("Desired Program: " . ($enrollment->desired_program ?? 'Not set'));
            
            // Find the student record
            $student = null;
            if ($enrollment->applicant) {
                $student = Student::where('email', $enrollment->applicant->email)
                    ->orWhere('student_number', $enrollment->applicant->student_number)
                    ->first();
            }
            
            if ($student) {
                $this->info("Found linked student record: #{$student->id} - {$student->first_name} {$student->last_name}");
                $this->info("Student's Desired Program: " . ($student->desired_program ?? 'Not set'));
            } else {
                $this->warn("No linked student record found");
            }
            
            // Find the program
            $programName = $enrollment->desired_program ?? ($student->desired_program ?? null);
            if ($programName) {
                $program = Program::where('name', 'like', "%{$programName}%")
                    ->orWhere('code', 'like', "%{$programName}%")
                    ->first();
                
                if ($program) {
                    $this->info("Found matching program: {$program->name} (Code: {$program->code})");
                } else {
                    $this->error("No matching program found for '{$programName}'");
                }
            } else {
                $this->error("No desired program set on enrollment or student record");
            }
            
            // Confirm execution
            if (!$this->option('force') && !$this->confirm('Do you want to proceed with course assignment?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
            
            // Process the enrollment
            DB::beginTransaction();
            try {
                $results = $courseAssignmentService->assignCoursesToOfficiallyEnrolledStudents($academicYear, $semester);
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Total Processed', $results['total_students_processed']],
                        ['Successful', $results['successful_assignments']],
                        ['Failed', $results['failed_assignments']],
                        ['Manual Review', count($results['students_requiring_manual_review'])],
                    ]
                );
                
                if (!empty($results['errors'])) {
                    $this->error('Errors:');
                    foreach ($results['errors'] as $error) {
                        $this->line(" - {$error}");
                    }
                }
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error: " . $e->getMessage());
                return 1;
            }
            
            // Show enrolled courses
            $enrolledCourses = $enrollment->enrolledCourses()
                ->with('course')
                ->get();
            
            if ($enrolledCourses->isEmpty()) {
                $this->warn("No courses were enrolled for this student");
            } else {
                $this->info("Enrolled courses:");
                $this->table(
                    ['ID', 'Course Code', 'Course Name', 'Status'],
                    $enrolledCourses->map(function ($ec) {
                        return [
                            'id' => $ec->id,
                            'code' => $ec->course->code ?? 'N/A',
                            'name' => $ec->course->name ?? 'N/A',
                            'status' => $ec->status,
                        ];
                    })
                );
            }
        } else {
            // If testing a specific program
            $programOption = $this->option('program');
            if ($programOption) {
                $this->info("Testing course assignment for program: {$programOption}");
                
                // Find matching program
                $program = Program::where('name', 'like', "%{$programOption}%")
                    ->orWhere('code', 'like', "%{$programOption}%")
                    ->first();
                
                if (!$program) {
                    $this->error("No matching program found for '{$programOption}'");
                    return 1;
                }
                
                $this->info("Found program: {$program->name} (Code: {$program->code})");
                
                // Find students with this program
                $students = Student::where('desired_program', 'like', "%{$program->name}%")
                    ->orWhere('desired_program', 'like', "%{$program->code}%")
                    ->get();
                
                $this->info("Found {$students->count()} students with this program as desired program");
                
                // Find enrollments with this program
                $enrollments = StudentEnrollment::where('desired_program', 'like', "%{$program->name}%")
                    ->orWhere('desired_program', 'like', "%{$program->code}%")
                    ->whereHas('applicant', function ($query) {
                        $query->where('status', 'for enrollment');
                    })
                    ->get();
                
                $this->info("Found {$enrollments->count()} active enrollments with this program");
                
                // Run test for all enrollments
                if ($enrollments->count() > 0) {
                    // Confirm execution
                    if (!$this->option('force') && !$this->confirm("Do you want to proceed with course assignment for {$enrollments->count()} enrollments?")) {
                        $this->info('Operation cancelled.');
                        return 0;
                    }
                    
                    // Process all enrollments
                    DB::beginTransaction();
                    try {
                        $results = $courseAssignmentService->assignCoursesToOfficiallyEnrolledStudents($academicYear, $semester);
                        $this->table(
                            ['Metric', 'Value'],
                            [
                                ['Total Processed', $results['total_students_processed']],
                                ['Successful', $results['successful_assignments']],
                                ['Failed', $results['failed_assignments']],
                                ['Manual Review', count($results['students_requiring_manual_review'])],
                            ]
                        );
                        
                        if (!empty($results['errors'])) {
                            $this->error('Errors:');
                            foreach ($results['errors'] as $error) {
                                $this->line(" - {$error}");
                            }
                        }
                        
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $this->error("Error: " . $e->getMessage());
                        return 1;
                    }
                }
            } else {
                $this->warn("No specific enrollment or program specified. Use --enrollment_id or --program options.");
                return 1;
            }
        }
        
        $this->info('Course assignment test completed!');
        return 0;
    }
}
