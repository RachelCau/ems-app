<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseCurriculum;
use App\Models\EnrolledCourse;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixCourseAssignment extends Command
{
    protected $signature = 'fix:course-assignment {--debug : Show detailed debug information}';
    protected $description = 'Diagnose and fix course assignment issues';

    public function handle()
    {
        $this->info('Starting course assignment diagnostic...');
        
        // Check students
        $students = Student::where('student_status', 'active')->get();
        $this->info("Found {$students->count()} active students");
        
        // Check student enrollments
        $enrollments = StudentEnrollment::where('status', 'active')->get();
        $this->info("Found {$enrollments->count()} active student enrollments");
        
        // Check programs
        $programs = Program::all();
        $this->info("Found {$programs->count()} programs");
        
        // Check curricula
        $curricula = CourseCurriculum::where('is_active', true)->get();
        $this->info("Found {$curricula->count()} active curricula");
        
        $curriculaWithCourses = $curricula->filter(function($curriculum) {
            return $curriculum->courses()->count() > 0;
        });
        $this->info("Found {$curriculaWithCourses->count()} curricula with courses");
        
        // Diagnose enrollment issues
        $withProgramId = $enrollments->filter(function($enrollment) {
            return !empty($enrollment->program_id);
        })->count();
        
        $withProgramCode = $enrollments->filter(function($enrollment) {
            return !empty($enrollment->program_code);
        })->count();
        
        $this->info("Enrollments with program_id: {$withProgramId}");
        $this->info("Enrollments with program_code: {$withProgramCode}");

        // 1. Fix enrollments without program links
        $this->info('Fixing student enrollments without program links...');
        $fixed = 0;
        
        foreach ($enrollments as $enrollment) {
            $needsUpdate = false;
            $updates = [];
            
            // If enrollment has no program_id but has a student with program linked
            if (empty($enrollment->program_id) && $enrollment->student && $enrollment->student->program_id) {
                $updates['program_id'] = $enrollment->student->program_id;
                $needsUpdate = true;
            }
            
            // If enrollment has no program_code but has a student with program_code
            if (empty($enrollment->program_code) && $enrollment->student && !empty($enrollment->student->program_code)) {
                $updates['program_code'] = $enrollment->student->program_code;
                $needsUpdate = true;
            }
            
            // If enrollment has no program link but has an applicant with program_id
            if (empty($enrollment->program_id) && $enrollment->applicant && $enrollment->applicant->program_id) {
                $updates['program_id'] = $enrollment->applicant->program_id;
                $needsUpdate = true;
            }
            
            // Try to infer program from applicant's desired program
            if (empty($enrollment->program_id) && empty($enrollment->program_code) && 
                $enrollment->applicant && !empty($enrollment->applicant->desired_program)) {
                
                $desiredProgram = trim($enrollment->applicant->desired_program);
                foreach ($programs as $program) {
                    if (stripos($program->code, $desiredProgram) !== false || 
                        stripos($desiredProgram, $program->code) !== false ||
                        stripos($program->name, $desiredProgram) !== false ||
                        stripos($desiredProgram, $program->name) !== false) {
                        
                        $updates['program_id'] = $program->id;
                        $updates['program_code'] = $program->code;
                        $needsUpdate = true;
                        break;
                    }
                }
            }
            
            if ($needsUpdate) {
                $enrollment->update($updates);
                $fixed++;
                
                if ($this->option('debug')) {
                    $this->line("Fixed enrollment ID: {$enrollment->id} - Updates: " . json_encode($updates));
                }
            }
        }
        
        $this->info("Fixed {$fixed} student enrollments");
        
        // 2. Create missing course curricula
        $this->info('Checking for missing course curricula...');
        $createdCurricula = 0;
        
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) {
            $this->error('No active academic year found!');
            return 1;
        }
        
        foreach ($programs as $program) {
            // Check if program has curricula
            $programCurricula = CourseCurriculum::where('program_id', $program->id)
                ->where('is_active', true)
                ->get();
            
            if ($programCurricula->isEmpty()) {
                $this->warn("Program {$program->code} has no active curricula - creating default");
                
                // Create a default curriculum for first year, first semester
                $curriculum = CourseCurriculum::create([
                    'name' => "{$program->code} Curriculum Y1S1",
                    'version' => '1.0',
                    'program_id' => $program->id,
                    'academic_year_id' => $activeYear->id,
                    'year_level' => 1,
                    'semester' => 1,
                    'is_active' => true
                ]);
                
                // Find courses for this program or create at least one
                $programCourses = Course::whereHas('programs', function ($query) use ($program) {
                    $query->where('programs.id', $program->id);
                })->orWhere('code', 'like', "%{$program->code}%")->get();
                
                if ($programCourses->isEmpty()) {
                    // Create at least one course for this program
                    $course = Course::create([
                        'name' => "Introduction to {$program->name}",
                        'code' => "{$program->code}101",
                        'unit' => 3,
                        'description' => "Basic course for {$program->name}",
                        'level' => '1st year',
                        'semester' => 'First Semester',
                        'academic_year_id' => $activeYear->id,
                    ]);
                    
                    // Attach to program
                    $course->programs()->attach($program->id);
                    
                    // Add to curriculum
                    $curriculum->courses()->attach($course->id);
                    
                    $this->line("Created new course {$course->code} for program {$program->code}");
                } else {
                    // Attach existing courses to curriculum
                    foreach ($programCourses as $course) {
                        $curriculum->courses()->attach($course->id);
                    }
                    $this->line("Attached {$programCourses->count()} courses to new curriculum for {$program->code}");
                }
                
                $createdCurricula++;
            }
        }
        
        $this->info("Created {$createdCurricula} new curricula");
        
        // 3. Now assign courses to eligible students
        $this->info('Assigning courses to eligible students...');
        
        $coursesAssigned = 0;
        $studentsWithNewCourses = 0;
        
        DB::beginTransaction();
        
        try {
            // Get updated enrollments
            $enrollments = StudentEnrollment::where('status', 'active')
                ->with(['student', 'program', 'enrolledCourses'])
                ->get();
                
            foreach ($enrollments as $enrollment) {
                // Skip if no program relationship
                if (!$enrollment->program_id) {
                    continue;
                }
                
                // Find curriculum for this program, student's year level
                $yearLevel = $enrollment->year_level ?? 1;
                $semester = $enrollment->semester ?? 1;
                
                $curriculum = CourseCurriculum::where('program_id', $enrollment->program_id)
                    ->where('year_level', $yearLevel)
                    ->where('semester', $semester)
                    ->where('is_active', true)
                    ->first();
                
                if (!$curriculum) {
                    continue;
                }
                
                // Get curriculum courses
                $curriculumCourses = $curriculum->courses;
                if ($curriculumCourses->isEmpty()) {
                    continue;
                }
                
                // Get existing course enrollments
                $existingCourseIds = $enrollment->enrolledCourses->pluck('course_id')->toArray();
                
                // Get student number
                $studentNumber = null;
                if ($enrollment->student) {
                    $studentNumber = $enrollment->student->student_number;
                } elseif ($enrollment->applicant && $enrollment->applicant->student_number) {
                    $studentNumber = $enrollment->applicant->student_number;
                }
                
                if (!$studentNumber) {
                    continue;
                }
                
                $studentAssigned = false;
                
                // Assign courses not already enrolled
                foreach ($curriculumCourses as $course) {
                    if (!in_array($course->id, $existingCourseIds)) {
                        EnrolledCourse::create([
                            'student_enrollment_id' => $enrollment->id,
                            'student_number' => $studentNumber,
                            'course_id' => $course->id,
                            'status' => 'enrolled'
                        ]);
                        
                        $coursesAssigned++;
                        $studentAssigned = true;
                        
                        if ($this->option('debug')) {
                            $this->line("Assigned course {$course->code} to student {$studentNumber}");
                        }
                    }
                }
                
                if ($studentAssigned) {
                    $studentsWithNewCourses++;
                }
            }
            
            DB::commit();
            $this->info("Assigned {$coursesAssigned} courses to {$studentsWithNewCourses} students");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error assigning courses: " . $e->getMessage());
            Log::error("Course assignment error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        $this->info('Course assignment fix complete!');
        return 0;
    }
} 