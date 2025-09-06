<?php

namespace App\Filament\Resources\EnrolledCourseResource\Pages;

use App\Filament\Resources\EnrolledCourseResource;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseCurriculum;
use App\Models\EnrolledCourse;
use App\Models\Program;
use App\Models\StudentEnrollment;
use App\Services\CourseAssignmentService;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Checkbox;

class ListEnrolledCourses extends ListRecords
{
    protected static string $resource = EnrolledCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('assignCourses')
                ->label('Batch Assign Courses')
                ->icon('heroicon-o-academic-cap')
                ->color('primary')
                ->form([
                    Select::make('assignment_type')
                        ->label('Assignment Type')
                        ->options([
                            'all_programs' => 'All Programs',
                        ])
                        ->default('all_programs')
                        ->required()
                        ->reactive(),
                        
                    Select::make('program_id')
                        ->label('Program')
                        ->options(function () {
                            // Eagerly load all programs to avoid N+1 issues
                            $programs = Program::all();
                            
                            if ($programs->isEmpty()) {
                                // If no programs exist, create a default one for demonstration
                                if (app()->environment('local', 'development')) {
                                    Notification::make()
                                        ->title('No Programs Found')
                                        ->body('Creating a sample program for demonstration purposes.')
                                        ->warning()
                                        ->send();
                                        
                                    $program = Program::create([
                                        'name' => 'Associate in Computer Technology with specialization in Application Development',
                                        'code' => 'ACT',
                                        'description' => 'Sample program created by system',
                                    ]);
                                    
                                    $programs = Program::all();
                                }
                            }
                            
                            return $programs->mapWithKeys(function ($program) {
                                return [$program->id => $program->code . ' - ' . $program->name];
                            });
                        })
                        ->required()
                        ->reactive()
                        ->searchable()
                        ->preload(),

                    Select::make('year_level')
                        ->label('Year Level')
                        ->options([
                            1 => '1st Year',
                            2 => '2nd Year',
                            3 => '3rd Year',
                            4 => '4th Year',
                            'all' => 'All Year Levels',
                        ])
                        ->default('all')
                        ->required(),

                    Select::make('semester')
                        ->label('Semester')
                        ->options([
                            1 => '1st Semester',
                            2 => '2nd Semester',
                        ])
                        ->required(),

                    Select::make('academic_year_id')
                        ->label('Academic Year')
                        ->options(AcademicYear::pluck('name', 'id'))
                        ->required()
                        ->default(function () {
                            // Use the active academic year
                            if (Schema::hasColumn('academic_years', 'is_current')) {
                                return AcademicYear::where('is_current', true)->first()?->id;
                            } else {
                                return AcademicYear::where('is_active', true)->first()?->id ?? AcademicYear::latest('id')->first()?->id;
                            }
                        }),

                    Select::make('student_type')
                        ->label('Student Type')
                        ->options([
                            'all' => 'All Students',
                            'new' => 'New Students Only',
                            'old' => 'Old Students Only',
                        ])
                        ->required()
                        ->default('all')
                        ->helperText('Old students may require manual review of course assignments'),
                ])
                ->action(function (array $data): void {
                    $academicYear = AcademicYear::find($data['academic_year_id']);
                    $yearLevel = $data['year_level'] ?? 'all';
                    $semester = (int) ($data['semester'] ?? 1);
                    $studentType = $data['student_type'];
                    $programId = $data['program_id'] ?? null;

                    if (!$academicYear) {
                        Notification::make()
                            ->title('Error')
                            ->body('Academic year not found.')
                            ->danger()
                            ->send();
                        return;
                    }
                    
                    // Process all programs assignment with filter values always true
                    $this->processAllProgramsAssignment(
                        $academicYear, 
                        $yearLevel, 
                        $semester, 
                        $studentType,
                        true,  // Always filter by program
                        true,  // Always filter by year level 
                        true,  // Always filter by semester
                        $programId // Pass the selected program ID
                    );
                }),
        ];
    }

    /**
     * Determine the year level of a student based on their enrolled courses.
     *
     * @param StudentEnrollment $enrollment
     * @return int Year level (1-4 typically)
     */
    private function determineYearLevel(StudentEnrollment $enrollment): int
    {
        // Get the count of courses by year level
        $courseCounts = DB::table('enrolled_courses')
            ->join('courses', 'enrolled_courses.course_id', '=', 'courses.id')
            ->where('enrolled_courses.student_enrollment_id', $enrollment->id)
            ->whereNotNull('courses.level')
            ->selectRaw('courses.level, COUNT(*) as count')
            ->groupBy('courses.level')
            ->orderByDesc('count')
            ->get();

        if ($courseCounts->isEmpty()) {
            // If no courses found, assume first year
            return 1;
        }

        // Get the year level with the most courses
        $mostFrequentLevel = $courseCounts->first()->level;

        // Map the level string to a numeric year (e.g., "1st year" to 1)
        $yearMapping = [
            '1st year' => 1,
            '2nd year' => 2,
            '3rd year' => 3,
            '4th year' => 4,
        ];

        // Get numeric year or default to 1
        return $yearMapping[$mostFrequentLevel] ?? 1;
    }

    private function processAllProgramsAssignment(AcademicYear $academicYear, $yearLevel, int $semester, string $studentType, bool $filterByProgram, bool $filterByYearLevel, bool $filterBySemester, ?int $programId = null): void
    {
        // Use a transaction to ensure data integrity
        DB::beginTransaction();
        
        try {
            $stats = [
                'total_programs' => 0,
                'processed_programs' => 0,
                'skipped_programs' => 0,
                'total_students' => 0,
                'total_courses_assigned' => 0,
                'students_with_new_courses' => 0,
                'students_without_courses' => 0,
                'errors' => 0,
                'created_curricula' => 0,
            ];
            
            // Load the programs to process - either all or just the selected one
            $programsQuery = Program::query();
            if ($filterByProgram && $programId) {
                $programsQuery->where('id', $programId);
            }
            $programs = $programsQuery->get();
            
            $stats['total_programs'] = $programs->count();
            
            // Debug information about programs found
            \Illuminate\Support\Facades\Log::info('Processing programs batch assignment', [
                'total_programs' => $programs->count(),
                'specific_program_id' => $programId,
                'academic_year' => $academicYear->name,
                'year_level' => $yearLevel,
                'semester' => $semester,
                'filter_by_program' => $filterByProgram,
                'filter_by_year_level' => $filterByYearLevel,
                'filter_by_semester' => $filterBySemester
            ]);
            
            // 1. FIRST PASS: Build efficient data structures and ensure curricula exist
            $eligibleCoursesByProgram = [];
            $curriculaByProgram = [];
            
            foreach ($programs as $program) {
                \Illuminate\Support\Facades\Log::debug('Processing program', [
                    'program_id' => $program->id,
                    'program_name' => $program->name,
                    'program_code' => $program->code
                ]);
                
                // Build query for active curricula
                $curriculumQuery = CourseCurriculum::where([
                    'is_active' => true,
                ]);
                
                // Apply filters based on settings
                if ($filterByProgram) {
                    $curriculumQuery->where('program_id', $program->id);
                }
                
                // Apply year level filter if enabled and not 'all'
                if ($filterByYearLevel && $yearLevel !== 'all') {
                    $curriculumQuery->where('year_level', (int)$yearLevel);
                }
                
                // Apply semester filter if enabled
                if ($filterBySemester) {
                    $curriculumQuery->where('semester', $semester);
                }
                
                // Get curricula and associated courses in a single query
                $curricula = $curriculumQuery->with('courses')->get();
                
                \Illuminate\Support\Facades\Log::debug('Existing curricula', [
                    'program_id' => $program->id,
                    'curricula_count' => $curricula->count()
                ]);
                
                // Create curriculum if it doesn't exist
                if ($curricula->isEmpty()) {
                    $yLevels = ($filterByYearLevel && $yearLevel !== 'all') ? [(int)$yearLevel] : [1, 2, 3, 4];
                    
                    foreach ($yLevels as $yLevel) {
                        // Build the check query
                        $checkQuery = CourseCurriculum::where([
                            'is_active' => true,
                        ]);
                        
                        if ($filterByProgram) {
                            $checkQuery->where('program_id', $program->id);
                        }
                        
                        if ($filterByYearLevel) {
                            $checkQuery->where('year_level', $yLevel);
                        }
                        
                        if ($filterBySemester) {
                            $checkQuery->where('semester', $semester);
                        }
                        
                        // Check if we need to create a curriculum
                        $curriculumExists = $checkQuery->exists();
                        
                        if (!$curriculumExists) {
                            \Illuminate\Support\Facades\Log::info('Creating curriculum', [
                                'program_id' => $program->id,
                                'program_name' => $program->name,
                                'year_level' => $yLevel,
                                'semester' => $semester
                            ]);
                            
                            // Create a new curriculum
                            $newCurriculum = CourseCurriculum::create([
                                'name' => "{$program->code} Curriculum Y{$yLevel}S{$semester}",
                                'version' => '1.0',
                                'program_id' => $program->id,
                                'academic_year_id' => $academicYear->id,
                                'year_level' => $yLevel,
                                'semester' => $semester,
                                'is_active' => true
                            ]);
                            
                            // Find sample courses for this program
                            $sampleCourses = Course::where(function($query) use ($program) {
                                $query->whereHas('programs', function($q) use ($program) {
                                    $q->where('programs.id', $program->id);
                                })
                                ->orWhere('code', 'like', "%{$program->code}%");
                            })->get();
                            
                            \Illuminate\Support\Facades\Log::debug('Found sample courses', [
                                'program_id' => $program->id,
                                'course_count' => $sampleCourses->count()
                            ]);
                            
                            // If we found sample courses, attach them
                            if ($sampleCourses->isNotEmpty()) {
                                foreach ($sampleCourses as $index => $course) {
                                    $newCurriculum->courses()->attach($course->id, [
                                        'is_required' => true,
                                        'sort_order' => $index + 1,
                                        'year_level' => $yLevel,
                                        'semester' => $semester,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                }
                            } else {
                                // Create at least one sample course if none exists
                                \Illuminate\Support\Facades\Log::info('Creating sample course', [
                                    'program_id' => $program->id,
                                    'program_name' => $program->name
                                ]);
                                
                                $course = Course::create([
                                    'name' => "Introduction to {$program->name}",
                                    'code' => "{$program->code}101",
                                    'unit' => 3,
                                    'description' => "Basic course for {$program->name}",
                                    'level' => $yLevel === 1 ? '1st year' : ($yLevel === 2 ? '2nd year' : ($yLevel === 3 ? '3rd year' : '4th year')),
                                    'semester' => $semester === 1 ? 'First Semester' : 'Second Semester',
                                    'academic_year_id' => $academicYear->id,
                                ]);
                                
                                // Attach to program and curriculum
                                $course->programs()->attach($program->id);
                                $newCurriculum->courses()->attach($course->id, [
                                    'is_required' => true,
                                    'sort_order' => 1,
                                    'year_level' => $yLevel,
                                    'semester' => $semester,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }
                    
                    // Re-fetch curricula after possible creation
                    $curricula = $curriculumQuery->with('courses')->get();
                    
                    // If still empty after creation attempt, skip this program
                    if ($curricula->isEmpty()) {
                        $stats['skipped_programs']++;
                        \Illuminate\Support\Facades\Log::warning('Skipping program - no curricula', [
                            'program_id' => $program->id,
                            'program_name' => $program->name
                        ]);
                        continue;
                    }
                }
                
                // Track program as processed
                $stats['processed_programs']++;
                $curriculaByProgram[$program->id] = $curricula;
                
                // Extract all eligible courses by year level
                $eligibleCoursesByProgram[$program->id] = [];
                
                foreach ($curricula as $curriculum) {
                    $courseYearLevel = $curriculum->year_level;
                    
                    if (!isset($eligibleCoursesByProgram[$program->id][$courseYearLevel])) {
                        $eligibleCoursesByProgram[$program->id][$courseYearLevel] = [];
                    }
                    
                    foreach ($curriculum->courses as $course) {
                        $eligibleCoursesByProgram[$program->id][$courseYearLevel][] = $course;
                    }
                }
                
                \Illuminate\Support\Facades\Log::debug('Processed program curricula', [
                    'program_id' => $program->id,
                    'program_name' => $program->name,
                    'year_levels_with_courses' => array_keys($eligibleCoursesByProgram[$program->id])
                ]);
            }
            
            // 2. SECOND PASS: Get all eligible students in one efficient query
            $studentQuery = StudentEnrollment::query()
                ->where('status', 'enrolled')
                ->where(function($query) {
                    $query->whereHas('applicant', function ($q) {
                        $q->where('status', 'Officially Enrolled')
                          ->orWhere('enrollment_status', 'Officially Enrolled');
                    })
                    ->orWhereHas('student', function($q) {
                        $q->where('student_status', 'active');
                    });
                });
            
            // Log the base query for debugging
            \Illuminate\Support\Facades\Log::debug('Batch assignment student query', [
                'sql' => $studentQuery->toSql(),
                'bindings' => $studentQuery->getBindings()
            ]);
            
            // If we're filtering by year level, include that
            if ($filterByYearLevel && $yearLevel !== 'all') {
                $studentQuery->where('year_level', (int)$yearLevel);
            }
            
            // If filtering by semester, apply that filter
            if ($filterBySemester) {
                $studentQuery->where('semester', $semester);
            }
            
            // Apply student type filter
            if ($studentType === 'new') {
                $studentQuery->where(function ($q) {
                    $q->doesntHave('enrolledCourses')
                        ->orWhereHas('enrolledCourses', function ($q) {
                            $q->whereHas('course', function ($c) {
                                $c->where('level', '1st year');
                            });
                        });
                });
            } elseif ($studentType === 'old') {
                $studentQuery->whereHas('enrolledCourses', function ($q) {
                    $q->whereHas('course', function ($c) {
                        $c->whereIn('level', ['2nd year', '3rd year', '4th year']);
                    });
                });
            }
            
            // Preload relationships for efficiency
            $studentQuery->with(['applicant', 'program', 'student', 'enrolledCourses']);
            
            // Get all students in one go
            $students = $studentQuery->get();
            $stats['total_students'] = $students->count();
            
            \Illuminate\Support\Facades\Log::info('Found students for batch assignment', [
                'student_count' => $students->count()
            ]);
            
            // 3. THIRD PASS: Build an efficient lookup of existing course enrollments
            $existingEnrollments = [];
            $studentPrograms = [];
            
            // Create a map of program codes to program IDs for easier lookups
            $programCodeMap = [];
            $programsByID = [];
            
            foreach ($programs as $program) {
                // Store program by ID for easier lookup
                $programsByID[$program->id] = $program;
                
                // Direct ID mapping
                $programCodeMap[$program->id] = $program->id;
                
                // Process program code variations
                $codeVariants = [
                    // Original code
                    $program->code,
                    // Lowercase
                    strtolower($program->code),
                    // No spaces
                    str_replace(' ', '', $program->code),
                    // Lowercase no spaces
                    strtolower(str_replace(' ', '', $program->code)),
                    // Program name no spaces
                    strtolower(str_replace(' ', '', $program->name))
                ];
                
                // Add 2-character prefix for codes like BSOM -> BS
                if (strlen($program->code) > 2) {
                    $codeVariants[] = strtolower(substr($program->code, 0, 2));
                }
                
                // Special handling for Computer Tech Application Development program
                if (stripos($program->name, 'Computer Technology') !== false && 
                    stripos($program->name, 'Application Development') !== false) {
                    $codeVariants[] = 'act';
                    $codeVariants[] = 'actad';
                    $codeVariants[] = 'appdev';
                    $codeVariants[] = 'application';
                    $codeVariants[] = 'development';
                }
                
                // Special handling for BSIS and similar programs
                if (stripos($program->name, 'Information Systems') !== false || 
                    stripos($program->code, 'BSIS') !== false) {
                    $codeVariants[] = 'bsis';
                    $codeVariants[] = 'is';
                    $codeVariants[] = 'info';
                    $codeVariants[] = 'information';
                    $codeVariants[] = 'systems';
                }
                
                // Generic handling for any program - extract first letters of each word
                $firstLetters = '';
                $words = explode(' ', $program->name);
                foreach ($words as $word) {
                    if (strlen($word) > 0) {
                        $firstLetters .= strtoupper(substr($word, 0, 1));
                    }
                }
                if (strlen($firstLetters) > 1) {
                    $codeVariants[] = strtolower($firstLetters);
                }
                
                // For any Bachelor programs (BS/BA)
                if (stripos($program->name, 'Bachelor') !== false || 
                    stripos($program->code, 'BS') === 0 || 
                    stripos($program->code, 'BA') === 0) {
                    $codeVariants[] = 'bs';
                    $codeVariants[] = 'ba';
                    $codeVariants[] = 'bachelor';
                }
                
                // Special handling for NCII programs
                if (stripos($program->code, 'NCII') !== false || stripos($program->name, 'NCII') !== false) {
                    $codeVariants[] = 'ncii';
                    $codeVariants[] = 'nc2';
                }
                
                // Add all variants to map
                foreach ($codeVariants as $variant) {
                    $programCodeMap[$variant] = $program->id;
                }
            }
            
            \Illuminate\Support\Facades\Log::debug('Program code mapping', [
                'count' => count($programCodeMap),
                'program_count' => count($programs),
                'sample_keys' => array_slice(array_keys($programCodeMap), 0, 10) // Log a sample of keys
            ]);
            
            // Enhanced program match tracking
            $matchTypeCounts = [];
            
            foreach ($students as $student) {
                $studentId = $student->id;
                $existingEnrollments[$studentId] = [];
                
                foreach ($student->enrolledCourses as $enrollment) {
                    $existingEnrollments[$studentId][] = $enrollment->course_id;
                }
                
                // Use enhanced program matching
                $match = $this->getEnhancedProgramMatch($student, $programCodeMap, false);
                if ($match) {
                    [$programId, $matchType] = $match;
                    $studentPrograms[$studentId] = $programId;
                    
                    // Track match type for statistics
                    if (!isset($matchTypeCounts[$matchType])) {
                        $matchTypeCounts[$matchType] = 0;
                    }
                    $matchTypeCounts[$matchType]++;
                    
                    \Illuminate\Support\Facades\Log::debug('Matched student to program', [
                        'student_id' => $studentId,
                        'program_id' => $programId,
                        'match_type' => $matchType
                    ]);
                } else {
                    // Fall back to original logic for NCII programs and other special cases
                    $programId = null;
                    
                    // Fall back to searching for a match with relaxed criteria
                    if ($student->applicant && !empty($student->applicant->desired_program)) {
                        $desiredProgram = trim($student->applicant->desired_program);
                        
                        // Special handling for Associate in Computer Technology
                        if (stripos($desiredProgram, 'associate') !== false && 
                            (stripos($desiredProgram, 'computer') !== false || stripos($desiredProgram, 'tech') !== false) && 
                            (stripos($desiredProgram, 'app') !== false || stripos($desiredProgram, 'dev') !== false)) {
                            
                            // Find an Associate in Computer Technology program
                            foreach ($programs as $program) {
                                if (stripos($program->name, 'associate') !== false && 
                                    stripos($program->name, 'computer technology') !== false && 
                                    stripos($program->name, 'application development') !== false) {
                                    
                                    $programId = $program->id;
                                    if (!isset($matchTypeCounts['special_act_match'])) {
                                        $matchTypeCounts['special_act_match'] = 0;
                                    }
                                    $matchTypeCounts['special_act_match']++;
                                    
                                    \Illuminate\Support\Facades\Log::info('Matched student to ACT program', [
                                        'student_id' => $studentId,
                                        'desired_program' => $desiredProgram,
                                        'program_id' => $programId,
                                        'program_name' => $program->name
                                    ]);
                                    
                                    break;
                                }
                            }
                        }
                        
                        // Special handling for BSIS
                        if (!$programId && (
                            stripos($desiredProgram, 'bachelor') !== false || 
                            stripos($desiredProgram, 'bs') !== false ||
                            stripos($desiredProgram, 'information') !== false || 
                            stripos($desiredProgram, 'system') !== false)) {
                            
                            // Find a BSIS program
                            foreach ($programs as $program) {
                                if (stripos($program->name, 'information systems') !== false || 
                                    stripos($program->code, 'BSIS') !== false) {
                                    
                                    $programId = $program->id;
                                    if (!isset($matchTypeCounts['special_bsis_match'])) {
                                        $matchTypeCounts['special_bsis_match'] = 0;
                                    }
                                    $matchTypeCounts['special_bsis_match']++;
                                    
                                    \Illuminate\Support\Facades\Log::info('Matched student to BSIS program', [
                                        'student_id' => $studentId,
                                        'desired_program' => $desiredProgram,
                                        'program_id' => $programId,
                                        'program_name' => $program->name
                                    ]);
                                    
                                    break;
                                }
                            }
                        }
                        
                        // Generic acronym matching for any program
                        if (!$programId) {
                            // Extract possible acronym from desired program
                            preg_match_all('/\b([a-zA-Z])[a-zA-Z]*\b/', $desiredProgram, $matches);
                            if (!empty($matches[1])) {
                                $acronym = strtolower(implode('', $matches[1]));
                                
                                if (strlen($acronym) >= 2) {
                                    foreach ($programs as $program) {
                                        // Check if acronym matches program code
                                        if (stripos($program->code, $acronym) !== false) {
                                            $programId = $program->id;
                                            if (!isset($matchTypeCounts['acronym_match'])) {
                                                $matchTypeCounts['acronym_match'] = 0;
                                            }
                                            $matchTypeCounts['acronym_match']++;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    if ($programId) {
                        $studentPrograms[$studentId] = $programId;
                        \Illuminate\Support\Facades\Log::debug('Matched student to program (fallback)', [
                            'student_id' => $studentId,
                            'program_id' => $programId
                        ]);
                    } else {
                        if (!isset($matchTypeCounts['no_match'])) {
                            $matchTypeCounts['no_match'] = 0;
                        }
                        $matchTypeCounts['no_match']++;
                        \Illuminate\Support\Facades\Log::warning('Could not match student to any program', [
                            'student_id' => $studentId, 
                            'student_name' => $student->applicant->full_name ?? 'Unknown'
                        ]);
                    }
                }
            }
            
            // Log how many students were matched to programs
            \Illuminate\Support\Facades\Log::info('Student program matching results', [
                'total_students' => $students->count(),
                'matched_students' => count($studentPrograms),
                'match_types' => $matchTypeCounts
            ]);
            
            // 4. FINAL PASS: Apply course assignments in a single batch
            $coursesToInsert = [];
            
            foreach ($students as $student) {
                $studentId = $student->id;
                
                // Skip if we couldn't determine the program when program filtering is enabled
                if ($filterByProgram && !isset($studentPrograms[$studentId])) {
                    $stats['students_without_courses']++;
                    continue;
                }
                
                // If not filtering by program, just use a valid curriculum
                $programId = null;
                if ($filterByProgram) {
                    $programId = $studentPrograms[$studentId];
                    
                    // Skip if no curriculum found for this program
                    if (!isset($eligibleCoursesByProgram[$programId])) {
                        \Illuminate\Support\Facades\Log::warning('No curriculum found for program', [
                            'student_id' => $studentId,
                            'program_id' => $programId
                        ]);
                        continue;
                    }
                } else {
                    // If not filtering by program, use any program that has a curriculum
                    foreach ($eligibleCoursesByProgram as $pid => $yearLevelData) {
                        $programId = $pid;
                        break;
                    }
                    
                    if (!$programId) {
                        \Illuminate\Support\Facades\Log::warning('No curriculum found for any program', [
                            'student_id' => $studentId
                        ]);
                        continue;
                    }
                }
                
                $studentNumber = null;
                
                // Get student number - try multiple sources
                if ($student->student && $student->student->student_number) {
                    $studentNumber = $student->student->student_number;
                } elseif ($student->applicant && $student->applicant->student_number) {
                    $studentNumber = $student->applicant->student_number;
                } elseif ($student->applicant && $student->applicant->student && $student->applicant->student->student_number) {
                    $studentNumber = $student->applicant->student->student_number;
                }
                
                // Skip if no student number
                if (!$studentNumber) {
                    $stats['errors']++;
                    \Illuminate\Support\Facades\Log::warning('No student number found', [
                        'student_id' => $studentId
                    ]);
                    continue;
                }
                
                // Determine student's year level
                $studentYearLevel = $student->year_level ?? 1;
                if ($filterByYearLevel) {
                    if ($yearLevel !== 'all') {
                        $studentYearLevel = (int)$yearLevel;
                    }
                }
                
                // Skip if no courses for this year level
                if (!isset($eligibleCoursesByProgram[$programId][$studentYearLevel])) {
                    if ($filterByYearLevel) {
                        \Illuminate\Support\Facades\Log::warning('No courses for year level', [
                            'student_id' => $studentId,
                            'program_id' => $programId,
                            'year_level' => $studentYearLevel,
                            'available_year_levels' => array_keys($eligibleCoursesByProgram[$programId])
                        ]);
                        continue;
                    } else {
                        // If not filtering by year level, use any year level that has courses
                        $foundYearLevel = false;
                        foreach ($eligibleCoursesByProgram[$programId] as $yl => $courses) {
                            $studentYearLevel = $yl;
                            $foundYearLevel = true;
                            break;
                        }
                        
                        if (!$foundYearLevel) {
                            \Illuminate\Support\Facades\Log::warning('No courses for any year level', [
                                'student_id' => $studentId,
                                'program_id' => $programId
                            ]);
                            continue;
                        }
                    }
                }
                
                $coursesForStudent = $eligibleCoursesByProgram[$programId][$studentYearLevel];
                $assignedAny = false;
                
                \Illuminate\Support\Facades\Log::debug('Processing courses for student', [
                    'student_id' => $studentId,
                    'program_id' => $programId,
                    'year_level' => $studentYearLevel,
                    'course_count' => count($coursesForStudent)
                ]);
                
                // Add courses not already enrolled
                foreach ($coursesForStudent as $course) {
                    // Skip if already enrolled
                    if (isset($existingEnrollments[$studentId]) && 
                        in_array($course->id, $existingEnrollments[$studentId])) {
                        continue;
                    }
                    
                    $coursesToInsert[] = [
                        'student_enrollment_id' => $studentId,
                        'student_number' => $studentNumber,
                        'course_id' => $course->id,
                        'status' => 'enrolled',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    
                    \Illuminate\Support\Facades\Log::debug('Adding course to student', [
                        'student_id' => $studentId,
                        'course_id' => $course->id,
                        'course_name' => $course->name
                    ]);
                    
                    $stats['total_courses_assigned']++;
                    $assignedAny = true;
                }
                
                if ($assignedAny) {
                    $stats['students_with_new_courses']++;
                }
            }
            
            \Illuminate\Support\Facades\Log::info('Course assignment summary', [
                'courses_to_insert' => count($coursesToInsert),
                'students_with_new_courses' => $stats['students_with_new_courses']
            ]);
            
            // Batch insert in chunks for efficiency
            foreach (array_chunk($coursesToInsert, 500) as $chunk) {
                DB::table('enrolled_courses')->insert($chunk);
            }
            
            // Commit the transaction
            DB::commit();
            
            // 5. REPORT results
            $message = "Assignment complete for all programs:\n" .
                       "• {$stats['processed_programs']} of {$stats['total_programs']} programs processed\n" .
                       "• {$stats['total_courses_assigned']} new course enrollments created\n" .
                       "• {$stats['students_with_new_courses']} of {$stats['total_students']} students received new courses";
            
            if ($stats['created_curricula'] > 0) {
                $message .= "\n• {$stats['created_curricula']} new curricula were created";
            }
            
            if ($stats['errors'] > 0) {
                $message .= "\n• {$stats['errors']} errors encountered";
            }
            
            if ($stats['students_without_courses'] > 0) {
                $message .= "\n• {$stats['students_without_courses']} students couldn't be assigned to a program";
            }
            
            // Add program matching diagnostics
            if (isset($matchTypeCounts) && count($matchTypeCounts) > 0) {
                $message .= "\n\nProgram Matching Details:";
                foreach ($matchTypeCounts as $type => $count) {
                    $message .= "\n• {$type}: {$count}";
                }
            }
            
            $notification = Notification::make()
                ->title('Batch Course Assignment Complete')
                ->body($message);
                
            if ($stats['errors'] > 0) {
                $notification->warning();
            } else {
                $notification->success();
            }
            
            $notification->send();
            
            // Refresh the list
            $this->dispatch('reloadTable');
            
        } catch (\Exception $e) {
            // Roll back on any error
            DB::rollBack();
            
            // Log the full error details
            \Illuminate\Support\Facades\Log::error('Course batch assignment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Notify the user
            Notification::make()
                ->title('Assignment Failed')
                ->body('An error occurred during course assignment: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Helper function to determine if two strings have significant common substrings
     * 
     * @param string $str1
     * @param string $str2
     * @return bool
     */
    private function hasCommonSubstringMatch(string $str1, string $str2): bool 
    {
        $str1Words = array_filter(preg_split('/\s+/', strtolower($str1)));
        $str2Words = array_filter(preg_split('/\s+/', strtolower($str2)));
        
        // Find significant words (more than 3 characters) that appear in both strings
        $significantWords = array_filter($str1Words, function($word) {
            return strlen($word) > 3; // Only consider words with 4+ characters significant
        });
        
        foreach ($significantWords as $word) {
            if (in_array($word, $str2Words)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Enhanced version of getEffectiveProgramCode that provides more matching options
     * 
     * @param StudentEnrollment $enrollment
     * @param array $programCodeMap Map of lowercase program codes to program IDs
     * @param bool $strictMode When true, only use direct matches (for single program assignment)
     * @return array|null Returns [program_id, match_type] or null if no match
     */
    private function getEnhancedProgramMatch($enrollment, array $programCodeMap, bool $strictMode = false): ?array
    {
        // First check for direct program_id - highest priority
        if ($enrollment->program_id) {
            return [$enrollment->program_id, 'direct_program_id_match'];
        }
        
        // Next check if enrollment has a program_code - high priority
        if (!empty($enrollment->program_code)) {
            $lookupCode = strtolower($enrollment->program_code);
            
            // Direct match
            if (isset($programCodeMap[$lookupCode])) {
                return [$programCodeMap[$lookupCode], 'program_code_direct'];
            }
            
            // Try with spaces removed
            $noSpaceCode = str_replace(' ', '', $lookupCode);
            if (isset($programCodeMap[$noSpaceCode])) {
                return [$programCodeMap[$noSpaceCode], 'program_code_nospace'];
            }
            
            // Scan all programs for exact matches first
            if ($enrollment->program) {
                return [$enrollment->program->id, 'direct_program_relation'];
            }
            
            // Only do partial matching if not in strict mode
            if (!$strictMode) {
                // Try to find a partial match
                foreach ($programCodeMap as $code => $id) {
                    if (stripos($lookupCode, $code) !== false || stripos($code, $lookupCode) !== false) {
                        return [$id, 'program_code_partial'];
                    }
                }
            }
        }
        
        // If applicant has a direct program relationship - medium priority
        if ($enrollment->applicant && $enrollment->applicant->program_id) {
            return [$enrollment->applicant->program_id, 'applicant_program_id'];
        }
        
        // Check if the applicant's student has a program relationship - medium priority
        if ($enrollment->applicant && $enrollment->applicant->student && $enrollment->applicant->student->program_id) {
            return [$enrollment->applicant->student->program_id, 'student_program_id'];
        }
        
        // If in strict mode, stop here
        if ($strictMode) {
            return null;
        }
        
        // Then check if the applicant has a desired_program - lower priority
        if ($enrollment->applicant && !empty($enrollment->applicant->desired_program)) {
            $lookupCode = strtolower(trim($enrollment->applicant->desired_program));
            
            // Direct match
            if (isset($programCodeMap[$lookupCode])) {
                return [$programCodeMap[$lookupCode], 'desired_program_direct'];
            }
            
            // Try with spaces removed
            $noSpaceCode = str_replace(' ', '', $lookupCode);
            if (isset($programCodeMap[$noSpaceCode])) {
                return [$programCodeMap[$noSpaceCode], 'desired_program_nospace'];
            }
            
            // Look for key terms in the desired program
            $keyTerms = [
                'associate' => 'associate',
                'computer' => 'computer',
                'technology' => 'technology',
                'app' => 'application',
                'application' => 'application',
                'development' => 'development',
                'ladderized' => 'ladderized',
                'bsis' => 'bsis'
            ];
            
            // Check for specialized programs with distinctive keywords
            foreach ($programCodeMap as $code => $id) {
                $program = Program::find($id);
                if (!$program) continue;
                
                $programName = strtolower($program->name);
                
                // Check for term matches
                $matchScore = 0;
                $requiredScore = 2; // Need at least 2 matching terms
                
                foreach ($keyTerms as $term => $fullTerm) {
                    if (stripos($lookupCode, $term) !== false && stripos($programName, $fullTerm) !== false) {
                        $matchScore++;
                    }
                }
                
                // Special case for "Application Development" which is very specific
                if ((stripos($lookupCode, 'app') !== false || stripos($lookupCode, 'application') !== false) &&
                    stripos($lookupCode, 'dev') !== false && 
                    stripos($programName, 'application') !== false && 
                    stripos($programName, 'development') !== false) {
                    $matchScore += 2; // Bonus for this specific match
                }
                
                if ($matchScore >= $requiredScore) {
                    return [$id, 'desired_program_keywords'];
                }
            }
            
            // Try to find a partial match
            foreach ($programCodeMap as $code => $id) {
                if (stripos($lookupCode, $code) !== false || stripos($code, $lookupCode) !== false) {
                    return [$id, 'desired_program_partial'];
                }
            }
            
            // Check for common substring matches (looking for significant word overlap)
            foreach ($programCodeMap as $code => $id) {
                $program = Program::find($id);
                if (!$program) continue;
                
                if ($this->hasCommonSubstringMatch($lookupCode, strtolower($program->name))) {
                    return [$id, 'desired_program_substring'];
                }
            }
        }
        
        // Finally check if the applicant's student has a program_code - lowest priority
        if ($enrollment->applicant && $enrollment->applicant->student && !empty($enrollment->applicant->student->program_code)) {
            $lookupCode = strtolower($enrollment->applicant->student->program_code);
            
            // Direct match
            if (isset($programCodeMap[$lookupCode])) {
                return [$programCodeMap[$lookupCode], 'student_program_code_direct'];
            }
            
            // Try with spaces removed
            $noSpaceCode = str_replace(' ', '', $lookupCode);
            if (isset($programCodeMap[$noSpaceCode])) {
                return [$programCodeMap[$noSpaceCode], 'student_program_code_nospace'];
            }
        }
        
        return null;
    }
}
