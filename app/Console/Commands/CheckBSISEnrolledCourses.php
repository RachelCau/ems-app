<?php

namespace App\Console\Commands;

use App\Models\EnrolledCourse;
use App\Models\Program;
use App\Models\StudentEnrollment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckBSISEnrolledCourses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-bsis-enrolled-courses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check enrolled courses for BSIS students';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get BSIS program
        $program = Program::where('code', 'BSIS')->first();
        if (!$program) {
            $this->error('BSIS program not found');
            return 1;
        }
        
        $this->info("Checking for enrolled courses for BSIS program (ID: {$program->id})");
        
        // Get all student enrollments for BSIS
        $enrollments = StudentEnrollment::where('program_code', 'BSIS')->get();
        $this->info("Found {$enrollments->count()} student enrollments for BSIS");
        
        // Show enrollment details
        foreach ($enrollments as $enrollment) {
            $this->info("Enrollment #{$enrollment->id}");
            $this->info("  - Applicant ID: {$enrollment->applicant_id}");
            $this->info("  - Program Code: {$enrollment->program_code}");
            $this->info("  - Status: {$enrollment->status}");
            
            // Get enrolled courses
            $enrolledCourses = EnrolledCourse::where('student_enrollment_id', $enrollment->id)->get();
            $this->info("  - Enrolled Courses: {$enrolledCourses->count()}");
            
            foreach ($enrolledCourses as $course) {
                $courseName = DB::table('courses')->where('id', $course->course_id)->value('name');
                $this->info("    - {$courseName} (ID: {$course->course_id})");
            }
        }
        
        // Check for all enrolled courses
        $allEnrolledCourses = EnrolledCourse::count();
        $this->info("Total enrolled courses in system: {$allEnrolledCourses}");
        
        // Check available student enrollments
        $allEnrollments = StudentEnrollment::count();
        $this->info("Total student enrollments in system: {$allEnrollments}");
        
        // Check student enrollments matching BSIS students through applicant table
        $bsisEnrollments = StudentEnrollment::whereHas('applicant', function ($query) use ($program) {
            $query->where('program_id', $program->id)
                  ->orWhere('desired_program', 'BSIS');
        })->get();
        
        $this->info("Found {$bsisEnrollments->count()} enrollments through applicant relationship");
        
        // Show these enrollments and add courses if needed
        foreach ($bsisEnrollments as $enrollment) {
            $this->info("Related Enrollment #{$enrollment->id}");
            $this->info("  - Applicant ID: {$enrollment->applicant_id}");
            $applicantName = DB::table('applicants')->where('id', $enrollment->applicant_id)->value(DB::raw("CONCAT(first_name, ' ', last_name)"));
            $this->info("  - Applicant Name: {$applicantName}");
            $this->info("  - Program Code: {$enrollment->program_code}");
            
            // Check if already has enrolled courses
            $enrolledCourses = EnrolledCourse::where('student_enrollment_id', $enrollment->id)->count();
            $this->info("  - Has {$enrolledCourses} enrolled courses");
            
            // If no enrolled courses, add them from curriculum
            if ($enrolledCourses === 0) {
                $this->info("  - No enrolled courses found, adding from curriculum");
                
                // Get the active curriculum for BSIS
                $curriculum = DB::table('course_curricula')
                    ->where('program_id', $program->id)
                    ->where('is_active', true)
                    ->where('year_level', 1)
                    ->where('semester', 1)
                    ->first();
                
                if (!$curriculum) {
                    $this->error("  - No active curriculum found for BSIS");
                    continue;
                }
                
                $this->info("  - Using curriculum ID: {$curriculum->id}");
                
                // Get courses from curriculum
                $curriculumCourses = DB::table('curriculum_course')
                    ->where('course_curriculum_id', $curriculum->id)
                    ->join('courses', 'curriculum_course.course_id', '=', 'courses.id')
                    ->select('courses.id', 'courses.name', 'courses.code')
                    ->get();
                
                $this->info("  - Found {$curriculumCourses->count()} courses in curriculum");
                
                // Add each course to enrollment
                $addedCount = 0;
                foreach ($curriculumCourses as $course) {
                    // Get applicant's student number if available
                    $studentNumber = null;
                    $applicant = DB::table('applicants')->find($enrollment->applicant_id);
                    if ($applicant && !empty($applicant->student_number)) {
                        $studentNumber = $applicant->student_number;
                    }
                    
                    try {
                        DB::table('enrolled_courses')->insert([
                            'student_enrollment_id' => $enrollment->id,
                            'course_id' => $course->id,
                            'student_number' => $studentNumber,
                            'status' => 'enrolled',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $addedCount++;
                        $this->info("    - Added course: {$course->name} ({$course->code})");
                    } catch (\Exception $e) {
                        $this->error("    - Failed to add course {$course->id}: {$e->getMessage()}");
                    }
                }
                
                $this->info("  - Added {$addedCount} courses to enrollment #{$enrollment->id}");
            }
        }
        
        return 0;
    }
} 