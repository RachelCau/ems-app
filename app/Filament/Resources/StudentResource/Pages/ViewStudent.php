<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\Course;
use App\Models\AcademicYear;
use App\Models\CourseCurriculum;
use App\Models\EnrolledCourse;
use App\Models\Program;
use App\Models\StudentEnrollment;
use Filament\Actions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('assignCourses')
                ->label('Assign Courses')
                ->icon('heroicon-o-academic-cap')
                ->color('success')
                ->form([
                    Select::make('assignment_type')
                        ->label('Assignment Type')
                        ->options([
                            'single_course' => 'Single Course',
                            'curriculum' => 'Curriculum Courses',
                        ])
                        ->default('single_course')
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(fn (callable $set) => $set('course_id', null)),
                        
                    // Filters for single course assignment
                    Grid::make(3)
                        ->schema([
                            Checkbox::make('filter_by_program')
                                ->label('Filter by program')
                                ->default(true)
                                ->reactive(),
                                
                            Checkbox::make('filter_by_year_level')
                                ->label('Filter by year level')
                                ->default(true)
                                ->reactive(),
                                
                            Checkbox::make('filter_by_semester')
                                ->label('Filter by semester')
                                ->default(true)
                                ->reactive(),
                        ])
                        ->visible(fn (callable $get): bool => $get('assignment_type') === 'single_course'),
                        
                    Select::make('course_id')
                        ->label('Course')
                        ->options(function(callable $get) {
                            $student = $this->getRecord();
                            $programCode = $student->program_code;
                            $yearLevel = $student->year_level ?? 1;
                            $semester = $student->semester ?? 1;
                            
                            // Get filter states
                            $filterByProgram = $get('filter_by_program');
                            $filterByYearLevel = $get('filter_by_year_level');
                            $filterBySemester = $get('filter_by_semester');
                            
                            // Create a dictionary of program codes to program names for display
                            $programNames = [];
                            $programs = Program::all();
                            foreach ($programs as $program) {
                                $programNames[$program->code] = $program->name;
                            }
                            
                            // Get courses and apply filters
                            $coursesQuery = Course::with(['programs']);
                            
                            // Get the courses and apply filters
                            $courses = $coursesQuery->get();
                            
                            // Group courses by program and format for the dropdown
                            $courseOptions = [];
                            
                            // First, add the student's program courses at the top
                            if (!empty($programCode)) {
                                $studentProgramCourses = $courses->filter(function($course) use($programCode, $yearLevel, $semester, $filterByProgram, $filterByYearLevel, $filterBySemester) {
                                    // Filter by program if enabled
                                    if ($filterByProgram) {
                                        $programMatch = $course->programs->contains(function($program) use($programCode) {
                                            return $program->code === $programCode;
                                        });
                                        if (!$programMatch) return false;
                                    }
                                    
                                    // Filter by year level if enabled
                                    if ($filterByYearLevel && isset($course->year_level) && $course->year_level != $yearLevel) {
                                        return false;
                                    }
                                    
                                    // Filter by semester if enabled
                                    if ($filterBySemester && isset($course->semester) && $course->semester != $semester) {
                                        return false;
                                    }
                                    
                                    return true;
                                });
                                
                                if ($studentProgramCourses->isNotEmpty()) {
                                    $programName = $programNames[$programCode] ?? "Program: {$programCode}";
                                    $courseOptions["Student's Program: {$programName}"] = $studentProgramCourses->mapWithKeys(function ($course) {
                                        $levelInfo = isset($course->year_level) ? ", Y{$course->year_level}-S{$course->semester}" : "";
                                        return [$course->id => "{$course->code} - {$course->name}{$levelInfo} ({$course->unit} units)"];
                                    })->toArray();
                                }
                            }
                            
                            // If only showing student's program courses with filters, return just those
                            if ($filterByProgram) {
                                return $courseOptions;
                            }
                            
                            // Otherwise add all programs
                            foreach ($programs as $program) {
                                // Skip if this is the student's program (already added above)
                                if ($program->code === $programCode) {
                                    continue;
                                }
                                
                                $programCourses = $courses->filter(function($course) use($program, $yearLevel, $semester, $filterByYearLevel, $filterBySemester) {
                                    // Check program match
                                    $programMatch = $course->programs->contains(function($p) use($program) {
                                        return $p->id === $program->id;
                                    });
                                    if (!$programMatch) return false;
                                    
                                    // Filter by year level if enabled
                                    if ($filterByYearLevel && isset($course->year_level) && $course->year_level != $yearLevel) {
                                        return false;
                                    }
                                    
                                    // Filter by semester if enabled
                                    if ($filterBySemester && isset($course->semester) && $course->semester != $semester) {
                                        return false;
                                    }
                                    
                                    return true;
                                });
                                
                                if ($programCourses->isNotEmpty()) {
                                    $courseOptions["Program: {$program->name} ({$program->code})"] = $programCourses->mapWithKeys(function ($course) {
                                        $levelInfo = isset($course->year_level) ? ", Y{$course->year_level}-S{$course->semester}" : "";
                                        return [$course->id => "{$course->code} - {$course->name}{$levelInfo} ({$course->unit} units)"];
                                    })->toArray();
                                }
                            }
                            
                            return $courseOptions;
                        })
                        ->searchable()
                        ->preload()
                        ->required(fn (callable $get): bool => $get('assignment_type') === 'single_course')
                        ->visible(fn (callable $get): bool => $get('assignment_type') === 'single_course'),

                    // Filters for curriculum assignment
                    Grid::make(3)
                        ->schema([
                            Checkbox::make('curriculum_filter_by_program')
                                ->label('Filter by program')
                                ->default(true)
                                ->reactive(),
                                
                            Checkbox::make('curriculum_filter_by_year_level')
                                ->label('Filter by year level')
                                ->default(true)
                                ->reactive(),
                                
                            Checkbox::make('curriculum_filter_by_semester')
                                ->label('Filter by semester')
                                ->default(true)
                                ->reactive(),
                        ])
                        ->visible(fn (callable $get): bool => $get('assignment_type') === 'curriculum'),

                    Select::make('curriculum_id')
                        ->label('Curriculum')
                        ->options(function(callable $get) {
                            $student = $this->getRecord();
                            
                            // Get filter states
                            $filterByProgram = $get('curriculum_filter_by_program');
                            $filterByYearLevel = $get('curriculum_filter_by_year_level');
                            $filterBySemester = $get('curriculum_filter_by_semester');
                            
                            // Get the program ID from student or find by program code
                            $programId = $student->program_id;
                            if (!$programId && $student->program_code) {
                                $program = Program::where('code', $student->program_code)->first();
                                $programId = $program?->id;
                            }
                            
                            if (!$programId && $filterByProgram) {
                                return [];
                            }
                            
                            // Get available curricula with courses
                            $query = CourseCurriculum::where('is_active', true)
                                ->whereHas('courses');
                            
                            // Apply filters
                            if ($filterByProgram && $programId) {
                                $query->where('program_id', $programId);
                            }
                            
                            if ($filterByYearLevel && $student->year_level) {
                                $query->where('year_level', $student->year_level);
                            }
                            
                            if ($filterBySemester && $student->semester) {
                                $query->where('semester', $student->semester);
                            }
                            
                            return $query->get()
                                ->mapWithKeys(function ($curriculum) {
                                    $courseCount = $curriculum->courses()->count();
                                    $programName = $curriculum->program ? " - {$curriculum->program->name}" : "";
                                    return [
                                        $curriculum->id => "Year {$curriculum->year_level}, Semester {$curriculum->semester}{$programName} ({$courseCount} courses)"
                                    ];
                                });
                        })
                        ->searchable()
                        ->required(fn (callable $get): bool => $get('assignment_type') === 'curriculum')
                        ->visible(fn (callable $get): bool => $get('assignment_type') === 'curriculum'),

                    Select::make('status')
                        ->options([
                            'enrolled' => 'Enrolled',
                            'completed' => 'Completed',
                            'dropped' => 'Dropped',
                            'failed' => 'Failed',
                            'incomplete' => 'Incomplete',
                        ])
                        ->required()
                        ->default('enrolled'),
                ])
                ->action(function (array $data): void {
                    $student = $this->getRecord();
                    $assignmentType = $data['assignment_type'];
                    $status = $data['status'];
                    
                    // Find or create a student enrollment
                    $enrollment = StudentEnrollment::where('student_id', $student->id)
                        ->where('status', 'enrolled')
                        ->first();
                    
                    if (!$enrollment) {
                        // Create a new enrollment if none exists
                        $academicYear = AcademicYear::where('is_active', true)->first();
                        if (!$academicYear) {
                            Notification::make()
                                ->title('Error')
                                ->body('No active academic year found. Please set one first.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        $enrollment = StudentEnrollment::create([
                            'student_id' => $student->id,
                            'academic_year_id' => $academicYear->id,
                            'campus_id' => $student->campus_id,
                            'program_id' => $student->program_id,
                            'program_code' => $student->program_code,
                            'year_level' => $student->year_level ?? 1,
                            'semester' => $student->semester ?? 1,
                            'status' => 'enrolled',
                        ]);
                    }
                    
                    DB::beginTransaction();
                    
                    try {
                        if ($assignmentType === 'single_course') {
                            // Single course assignment
                            $courseId = $data['course_id'];
                            
                            // Check if already enrolled
                            $existingEnrollment = EnrolledCourse::where([
                                'student_enrollment_id' => $enrollment->id,
                                'course_id' => $courseId,
                            ])->first();
                            
                            if ($existingEnrollment) {
                                Notification::make()
                                    ->title('Already Enrolled')
                                    ->body('Student is already enrolled in this course.')
                                    ->warning()
                                    ->send();
                                return;
                            }
                            
                            // Create the enrolled course record
                            EnrolledCourse::create([
                                'student_enrollment_id' => $enrollment->id,
                                'course_id' => $courseId,
                                'student_number' => $student->student_number,
                                'status' => $status,
                            ]);
                            
                            Notification::make()
                                ->title('Course Assigned')
                                ->body('The course has been assigned to the student.')
                                ->success()
                                ->send();
                                
                        } else {
                            // Curriculum-based assignment
                            $curriculumId = $data['curriculum_id'];
                            $curriculum = CourseCurriculum::find($curriculumId);
                            
                            if (!$curriculum) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Curriculum not found.')
                                    ->danger()
                                    ->send();
                                DB::rollBack();
                                return;
                            }
                            
                            $courses = $curriculum->courses;
                            
                            if ($courses->isEmpty()) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('No courses found in the selected curriculum.')
                                    ->danger()
                                    ->send();
                                DB::rollBack();
                                return;
                            }
                            
                            // Get existing enrolled courses to avoid duplicates
                            $existingCourseIds = EnrolledCourse::where('student_enrollment_id', $enrollment->id)
                                ->pluck('course_id')
                                ->toArray();
                            
                            $coursesAssigned = 0;
                            
                            foreach ($courses as $course) {
                                if (!in_array($course->id, $existingCourseIds)) {
                                    EnrolledCourse::create([
                                        'student_enrollment_id' => $enrollment->id,
                                        'course_id' => $course->id,
                                        'student_number' => $student->student_number,
                                        'status' => $status,
                                    ]);
                                    $coursesAssigned++;
                                }
                            }
                            
                            if ($coursesAssigned > 0) {
                                Notification::make()
                                    ->title('Courses Assigned')
                                    ->body("{$coursesAssigned} courses have been assigned to the student.")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('No New Courses')
                                    ->body('Student is already enrolled in all curriculum courses.')
                                    ->warning()
                                    ->send();
                            }
                        }
                        
                        DB::commit();
                        
                        // Refresh the page to show the new courses
                        $this->redirect($this->getResource()::getUrl('view', ['record' => $student->id]));
                        
                    } catch (\Exception $e) {
                        DB::rollBack();
                        
                        Notification::make()
                            ->title('Error')
                            ->body('An error occurred while assigning courses: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
