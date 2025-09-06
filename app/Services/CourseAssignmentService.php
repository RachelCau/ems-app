<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseCurriculum;
use App\Models\EnrolledCourse;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseAssignmentService
{
    /**
     * Assign courses to officially enrolled students
     * 
     * @param AcademicYear $academicYear
     * @param int $semester
     * @return array
     */
    public function assignCoursesToOfficiallyEnrolledStudents(AcademicYear $academicYear, int $semester): array
    {
        $results = [
            'total_students_processed' => 0,
            'successful_assignments' => 0,
            'failed_assignments' => 0,
            'students_requiring_manual_review' => [],
            'errors' => [],
        ];

        try {
            // Get all officially enrolled students
            $enrollments = StudentEnrollment::whereHas('applicant', function ($query) {
                $query->where('status', 'Officially Enrolled')
                    ->orWhere('enrollment_status', 'Officially Enrolled');
            })
            ->where('status', 'active')
            ->get();

            $results['total_students_processed'] = $enrollments->count();

            foreach ($enrollments as $enrollment) {
                try {
                    // Get the program code for this enrollment
                    $programCode = $enrollment->getEffectiveProgramCode();
                    
                    if (!$programCode) {
                        throw new \Exception("No program code found for enrollment #{$enrollment->id}");
                    }
                    
                    // Find the active curriculum for this program, year level, and semester
                    $yearLevel = $enrollment->year_level ?? 1;
                    
                    $curriculum = CourseCurriculum::where([
                        'program_id' => $enrollment->program_id,
                        'year_level' => $yearLevel,
                        'semester' => $semester,
                        'is_active' => true,
                    ])->first();

                    if (!$curriculum) {
                        $results['students_requiring_manual_review'][] = [
                            'enrollment_id' => $enrollment->id,
                            'student_name' => $enrollment->applicant->full_name ?? 'Unknown',
                            'reason' => "No active curriculum found for program {$programCode}, year {$yearLevel}, semester {$semester}",
                        ];
                        $results['failed_assignments']++;
                        continue;
                    }

                    // Get courses for this curriculum
                    $curriculumCourses = $curriculum->courses()->get();
                    
                    if ($curriculumCourses->isEmpty()) {
                        $results['students_requiring_manual_review'][] = [
                            'enrollment_id' => $enrollment->id,
                            'student_name' => $enrollment->applicant->full_name ?? 'Unknown',
                            'reason' => "Curriculum has no courses for program {$programCode}, year {$yearLevel}, semester {$semester}",
                        ];
                        $results['failed_assignments']++;
                        continue;
                    }

                    // Enroll the student in each course
                    $coursesEnrolled = 0;
                    
                    foreach ($curriculumCourses as $course) {
                        // Check if the student is already enrolled in this course
                        $existingEnrollment = EnrolledCourse::where([
                            'student_enrollment_id' => $enrollment->id,
                            'course_id' => $course->id,
                        ])->first();
                        
                        if ($existingEnrollment) {
                            // Skip if already enrolled
                            continue;
                        }
                        
                        // Create the enrolled course record
                        EnrolledCourse::create([
                            'student_enrollment_id' => $enrollment->id,
                            'course_id' => $course->id,
                            'student_number' => $enrollment->applicant->student_number ?? null,
                            'status' => 'enrolled',
                        ]);
                        
                        $coursesEnrolled++;
                    }
                    
                    if ($coursesEnrolled > 0) {
                        $results['successful_assignments']++;
                    } else {
                        // If no new courses were enrolled, check if they already have courses for this semester
                        $existingCourses = EnrolledCourse::where('student_enrollment_id', $enrollment->id)
                            ->whereHas('course', function ($query) use ($semester) {
                                $query->where('semester', $semester);
                            })
                            ->count();
                        
                        if ($existingCourses > 0) {
                            $results['successful_assignments']++;
                        } else {
                            $results['students_requiring_manual_review'][] = [
                                'enrollment_id' => $enrollment->id,
                                'student_name' => $enrollment->applicant->full_name ?? 'Unknown',
                                'reason' => "No courses were enrolled and no existing courses found for semester {$semester}",
                            ];
                            $results['failed_assignments']++;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error assigning courses to enrollment #{$enrollment->id}: " . $e->getMessage());
                    $results['errors'][] = "Error for enrollment #{$enrollment->id}: " . $e->getMessage();
                    $results['failed_assignments']++;
                }
            }
        } catch (\Exception $e) {
            Log::error("Error in course assignment process: " . $e->getMessage());
            $results['errors'][] = "General error: " . $e->getMessage();
        }

        return $results;
    }

    /**
     * Assign courses to officially enrolled students with flexible filtering options
     * 
     * @param AcademicYear $academicYear
     * @param int $semester
     * @param string|null $programCode Filter by specific program code
     * @param int|null $yearLevel Filter by specific year level
     * @return array
     */
    public function assignCoursesWithFilters(AcademicYear $academicYear, int $semester, ?string $programCode = null, ?int $yearLevel = null): array
    {
        $results = [
            'total_students_processed' => 0,
            'successful_assignments' => 0,
            'failed_assignments' => 0,
            'students_requiring_manual_review' => [],
            'errors' => [],
            'programs_processed' => [],
        ];

        try {
            // Base query for officially enrolled students
            $query = StudentEnrollment::whereHas('applicant', function ($q) {
                $q->where('status', 'Officially Enrolled')
                    ->orWhere('enrollment_status', 'Officially Enrolled');
            })
            ->where('status', 'active');
            
            // Apply program filter if specified
            if ($programCode) {
                $query->where(function($q) use ($programCode) {
                    $q->where('program_code', $programCode)
                      ->orWhereHas('program', function($pq) use ($programCode) {
                          $pq->where('code', $programCode);
                      });
                });
            }
            
            // Apply year level filter if specified
            if ($yearLevel) {
                $query->where('year_level', $yearLevel);
            }
            
            // Get filtered enrollments
            $enrollments = $query->get();
            
            $results['total_students_processed'] = $enrollments->count();
            
            // Track unique programs processed
            $processedPrograms = [];

            foreach ($enrollments as $enrollment) {
                try {
                    // Get the program code for this enrollment
                    $effectiveProgramCode = $enrollment->getEffectiveProgramCode();
                    
                    if (!$effectiveProgramCode) {
                        throw new \Exception("No program code found for enrollment #{$enrollment->id}");
                    }
                    
                    // Track the program
                    if (!in_array($effectiveProgramCode, $processedPrograms)) {
                        $processedPrograms[] = $effectiveProgramCode;
                    }
                    
                    // Find the active curriculum for this program, year level, and semester
                    $enrollmentYearLevel = $enrollment->year_level ?? 1;
                    
                    $program = \App\Models\Program::where('code', $effectiveProgramCode)->first();
                    if (!$program) {
                        $results['students_requiring_manual_review'][] = [
                            'enrollment_id' => $enrollment->id,
                            'student_name' => $enrollment->applicant->full_name ?? 'Unknown',
                            'reason' => "Program not found with code {$effectiveProgramCode}",
                        ];
                        $results['failed_assignments']++;
                        continue;
                    }
                    
                    $curriculum = CourseCurriculum::where([
                        'program_id' => $program->id,
                        'year_level' => $enrollmentYearLevel,
                        'semester' => $semester,
                        'is_active' => true,
                    ])->first();

                    if (!$curriculum) {
                        $results['students_requiring_manual_review'][] = [
                            'enrollment_id' => $enrollment->id,
                            'student_name' => $enrollment->applicant->full_name ?? 'Unknown',
                            'reason' => "No active curriculum found for program {$effectiveProgramCode}, year {$enrollmentYearLevel}, semester {$semester}",
                        ];
                        $results['failed_assignments']++;
                        continue;
                    }

                    // Get courses for this curriculum
                    $curriculumCourses = $curriculum->courses()->get();
                    
                    if ($curriculumCourses->isEmpty()) {
                        $results['students_requiring_manual_review'][] = [
                            'enrollment_id' => $enrollment->id,
                            'student_name' => $enrollment->applicant->full_name ?? 'Unknown',
                            'reason' => "Curriculum has no courses for program {$effectiveProgramCode}, year {$enrollmentYearLevel}, semester {$semester}",
                        ];
                        $results['failed_assignments']++;
                        continue;
                    }

                    // Enroll the student in each course
                    $coursesEnrolled = 0;
                    
                    foreach ($curriculumCourses as $course) {
                        // Check if the student is already enrolled in this course
                        $existingEnrollment = EnrolledCourse::where([
                            'student_enrollment_id' => $enrollment->id,
                            'course_id' => $course->id,
                        ])->first();
                        
                        if ($existingEnrollment) {
                            // Skip if already enrolled
                            continue;
                        }
                        
                        // Create the enrolled course record
                        EnrolledCourse::create([
                            'student_enrollment_id' => $enrollment->id,
                            'course_id' => $course->id,
                            'student_number' => $enrollment->applicant->student_number ?? null,
                            'status' => 'enrolled',
                        ]);
                        
                        $coursesEnrolled++;
                    }
                    
                    if ($coursesEnrolled > 0) {
                        $results['successful_assignments']++;
                    } else {
                        // If no new courses were enrolled, check if they already have courses for this semester
                        $existingCourses = EnrolledCourse::where('student_enrollment_id', $enrollment->id)
                            ->whereHas('course', function ($query) use ($semester) {
                                $query->where('semester', $semester);
                            })
                            ->count();
                        
                        if ($existingCourses > 0) {
                            $results['successful_assignments']++;
                        } else {
                            $results['students_requiring_manual_review'][] = [
                                'enrollment_id' => $enrollment->id,
                                'student_name' => $enrollment->applicant->full_name ?? 'Unknown',
                                'reason' => "No courses were enrolled and no existing courses found for semester {$semester}",
                            ];
                            $results['failed_assignments']++;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error assigning courses to enrollment #{$enrollment->id}: " . $e->getMessage());
                    $results['errors'][] = "Error for enrollment #{$enrollment->id}: " . $e->getMessage();
                    $results['failed_assignments']++;
                }
            }
            
            // Add processed programs to results
            $results['programs_processed'] = $processedPrograms;
            
        } catch (\Exception $e) {
            Log::error("Error in course assignment process: " . $e->getMessage());
            $results['errors'][] = "General error: " . $e->getMessage();
        }

        return $results;
    }
} 