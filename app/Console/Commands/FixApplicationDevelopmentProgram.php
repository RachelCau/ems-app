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

class FixApplicationDevelopmentProgram extends Command
{
    protected $signature = 'fix:app-dev-program';
    protected $description = 'Fix issues with the Application Development program and course assignments';

    public function handle()
    {
        $this->info('Starting Application Development program fix...');
        
        // 1. Find the program by its name
        $searchTerms = ['associate', 'computer technology', 'application development'];
        $programs = Program::where(function($query) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                $query->orWhere('name', 'like', "%{$term}%");
            }
        })->get();
        
        if ($programs->isEmpty()) {
            $this->error('No matching programs found! Please check program names in the database.');
            return 1;
        }
        
        $this->info('Found ' . $programs->count() . ' matching programs:');
        
        foreach ($programs as $index => $program) {
            $this->line(($index + 1) . ". [{$program->id}] {$program->name} (Code: {$program->code})");
        }
        
        // Select the program to fix
        $programIndex = 0;
        if ($programs->count() > 1) {
            $programIndex = $this->ask('Which program do you want to fix? (Enter the number)') - 1;
            if ($programIndex < 0 || $programIndex >= $programs->count()) {
                $this->error('Invalid selection!');
                return 1;
            }
        }
        
        $program = $programs[$programIndex];
        $this->info("Selected program: {$program->name}");
        
        // Get or create active academic year
        $academicYear = AcademicYear::where('is_active', true)->first();
        if (!$academicYear) {
            $this->error('No active academic year found! Please set an active academic year first.');
            return 1;
        }
        
        // 2. Find all enrollments that might be for this program
        $this->info('Searching for student enrollments that might be associated with this program...');
        
        $enrollmentsToFix = StudentEnrollment::where(function($query) use ($program) {
            $query->where('program_id', $program->id)
                  ->orWhere('program_code', $program->code)
                  ->orWhereHas('applicant', function($q) use ($program) {
                      $q->where('desired_program', 'like', "%{$program->name}%")
                        ->orWhere('desired_program', 'like', "%{$program->code}%");
                  })
                  ->orWhereHas('student', function($q) use ($program) {
                      $q->where('program_id', $program->id)
                        ->orWhere('program_code', $program->code);
                  });
        })->get();
        
        $this->info("Found {$enrollmentsToFix->count()} enrollments to fix.");
        
        // 3. Fix the enrollments
        DB::beginTransaction();
        
        try {
            $fixedEnrollments = 0;
            
            foreach ($enrollmentsToFix as $enrollment) {
                $needsUpdate = false;
                $updates = [];
                
                if ($enrollment->program_id != $program->id) {
                    $updates['program_id'] = $program->id;
                    $needsUpdate = true;
                }
                
                if ($enrollment->program_code != $program->code) {
                    $updates['program_code'] = $program->code;
                    $needsUpdate = true;
                }
                
                if ($needsUpdate) {
                    $enrollment->update($updates);
                    $fixedEnrollments++;
                }
                
                // Fix student record if it exists
                if ($enrollment->student) {
                    $studentNeedsUpdate = false;
                    $studentUpdates = [];
                    
                    if ($enrollment->student->program_id != $program->id) {
                        $studentUpdates['program_id'] = $program->id;
                        $studentNeedsUpdate = true;
                    }
                    
                    if ($enrollment->student->program_code != $program->code) {
                        $studentUpdates['program_code'] = $program->code;
                        $studentNeedsUpdate = true;
                    }
                    
                    if ($studentNeedsUpdate) {
                        $enrollment->student->update($studentUpdates);
                    }
                }
            }
            
            $this->info("Fixed {$fixedEnrollments} enrollments with correct program information.");
            
            // 4. Ensure curriculum exists for each year level and semester
            $this->info('Creating or updating curricula for the program...');
            
            $yearLevels = [1, 2, 3, 4];
            $semesters = [1, 2];
            $createdCurricula = 0;
            
            foreach ($yearLevels as $yearLevel) {
                foreach ($semesters as $semester) {
                    // Check if curriculum exists
                    $curriculum = CourseCurriculum::where([
                        'program_id' => $program->id,
                        'year_level' => $yearLevel,
                        'semester' => $semester,
                        'is_active' => true,
                    ])->first();
                    
                    if (!$curriculum) {
                        // Create curriculum
                        $curriculum = CourseCurriculum::create([
                            'name' => "{$program->code} Curriculum Y{$yearLevel}S{$semester}",
                            'version' => '1.0',
                            'program_id' => $program->id,
                            'academic_year_id' => $academicYear->id,
                            'year_level' => $yearLevel,
                            'semester' => $semester,
                            'is_active' => true
                        ]);
                        
                        $createdCurricula++;
                        $this->line("Created curriculum for Year {$yearLevel}, Semester {$semester}");
                        
                        // 5. Create sample courses for this curriculum if needed
                        $programCourses = Course::whereHas('programs', function ($query) use ($program) {
                            $query->where('programs.id', $program->id);
                        })->orWhere('code', 'like', "%{$program->code}%")->get();
                        
                        if ($programCourses->isEmpty()) {
                            // Create sample course for this year/semester
                            $coursePrefix = strtoupper(substr($program->code, 0, 4));
                            if (empty($coursePrefix)) {
                                $coursePrefix = 'ACTAD'; // Default for Associate in Computer Technology - Application Development
                            }
                            
                            $courseCode = sprintf('%s%d%d01', $coursePrefix, $yearLevel, $semester);
                            $courseName = "Y{$yearLevel}S{$semester} - Course for {$program->name}";
                            
                            $course = Course::create([
                                'name' => $courseName,
                                'code' => $courseCode, 
                                'unit' => 3,
                                'description' => "Course for {$program->name}, Year {$yearLevel}, Semester {$semester}",
                                'level' => "{$yearLevel}st year",
                                'semester' => $semester === 1 ? 'First Semester' : 'Second Semester',
                                'academic_year_id' => $academicYear->id,
                            ]);
                            
                            // Attach to program
                            $course->programs()->attach($program->id);
                            
                            // Add to curriculum
                            $curriculum->courses()->attach($course->id, [
                                'is_required' => true,
                                'sort_order' => 1,
                                'year_level' => $yearLevel,
                                'semester' => $semester,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            
                            $this->line("Created course {$courseCode} for curriculum Y{$yearLevel}S{$semester}");
                        } else {
                            // Attach existing courses to curriculum
                            $sortOrder = 1;
                            foreach ($programCourses->take(3) as $course) {
                                $curriculum->courses()->attach($course->id, [
                                    'is_required' => true,
                                    'sort_order' => $sortOrder++,
                                    'year_level' => $yearLevel,
                                    'semester' => $semester,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                            $this->line("Attached {$programCourses->count()} existing courses to curriculum Y{$yearLevel}S{$semester}");
                        }
                    }
                }
            }
            
            $this->info("Created {$createdCurricula} new curricula for the program.");
            
            // 6. Assign courses to students
            $this->info('Assigning courses to eligible students...');
            
            $coursesAssigned = 0;
            $studentsWithNewCourses = 0;
            
            foreach ($enrollmentsToFix as $enrollment) {
                $yearLevel = $enrollment->year_level ?? 1;
                $semester = $enrollment->semester ?? 1;
                
                // Find curriculum for this year level and semester
                $curriculum = CourseCurriculum::where([
                    'program_id' => $program->id,
                    'year_level' => $yearLevel,
                    'semester' => $semester,
                    'is_active' => true,
                ])->first();
                
                if (!$curriculum) {
                    continue; // Should not happen after the previous steps
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
                    }
                }
                
                if ($studentAssigned) {
                    $studentsWithNewCourses++;
                }
            }
            
            $this->info("Assigned {$coursesAssigned} courses to {$studentsWithNewCourses} students.");
            
            DB::commit();
            $this->info('Successfully fixed the Application Development program and associated data!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error fixing program: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
} 