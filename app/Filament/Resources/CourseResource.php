<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
use App\Models\Course;
use App\Models\Program;
use App\Models\Campus;
use App\Models\ProgramCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Response;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    // protected static ?string $navigationIcon = 'heroicon-o-book-open';
    
    protected static ?string $navigationGroup = 'Academic Management';
    
    protected static ?int $navigationSort = 3;

    /**
     * Generate a sample CSV file for download
     */
    public static function getDownloadSampleResponse()
    {
        $headers = [
            'PROGRAM', 'CODE', 'COURSE', 'UNIT', 'TYPE', 'PREREQUISITE', 'LEVEL', 'SEMESTER'
        ];
        
        $sampleData = [
            ['BSIT', 'IT101', 'Introduction to Information Technology', '3', 'GEN_ED', '', '1', '1'],
            ['BSIT', 'IT102', 'Computer Programming 1', '3', 'TECH_SKILL', '', '1', '1'],
            ['BSIT,BSCS', 'CS101', 'Data Structures and Algorithms', '3', 'TECH_SKILL', 'IT102', '1', '2'],
        ];
        
        return Response::streamDownload(function () use ($headers, $sampleData) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            
            foreach ($sampleData as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        }, 'sample_courses.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Get response for exporting courses
     */
    public static function getExportResponse()
    {
        // Get all courses with related data
        $courses = Course::with(['programs', 'prerequisiteCourses'])->get();
        
        // Prepare headers
        $headers = [
            'PROGRAM', 'CODE', 'COURSE', 'UNIT', 'TYPE', 'PREREQUISITE', 'LEVEL', 'SEMESTER'
        ];
        
        // Stream CSV response
        return Response::streamDownload(function () use ($courses, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            
            foreach ($courses as $course) {
                // Get program codes as comma-separated string
                $programCodes = $course->programs->pluck('code')->implode(',');
                
                // Get prerequisite codes as comma-separated string
                $prerequisites = $course->prerequisiteCourses->pluck('code')->implode(',');
                
                // Format level and semester for export
                $level = match ($course->level) {
                    'First Year' => '1',
                    'Second Year' => '2',
                    'Third Year' => '3',
                    'Fourth Year' => '4',
                    default => $course->level,
                };
                
                $semester = match ($course->semester) {
                    'First Semester' => '1',
                    'Second Semester' => '2',
                    'Summer' => 'summer',
                    default => $course->semester,
                };
                
                // Write course data
                fputcsv($file, [
                    $programCodes,
                    $course->code,
                    $course->name,
                    $course->unit,
                    $course->type,
                    $prerequisites,
                    $level,
                    $semester,
                ]);
            }
            
            fclose($file);
        }, 'courses_export_' . date('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Get import form schema
     */
    public static function getImportForm()
    {
        return [
            Forms\Components\Select::make('academic_year_id')
                ->relationship('academicYear', 'name')
                ->label('Academic Year')
                ->default(fn () => \App\Models\AcademicYear::where('is_active', true)->first()?->id)
                ->preload()
                ->searchable()
                ->required(),
            Forms\Components\FileUpload::make('csv_file')
                ->label('CSV File')
                ->disk('public')
                ->directory('csv-imports')
                ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel'])
                ->required()
                ->helperText('Upload a CSV file with the following columns: PROGRAM, CODE, COURSE, UNIT, TYPE, PREREQUISITE, LEVEL, SEMESTER. Note: Courses will only be merged if they have identical CODE, COURSE, LEVEL, SEMESTER, and UNIT values. Otherwise, they will be created as separate entries.'),
            Forms\Components\Actions::make([
                Forms\Components\Actions\Action::make('download_sample')
                    ->label('Download Sample CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->extraAttributes(['class' => 'filament-button-outline'])
                    ->action(function () {
                        return static::getDownloadSampleResponse();
                    }),
            ])->columnSpan(2),
        ];
    }

    /**
     * Process CSV import 
     */
    public static function processImport($data)
    {
        $csvFile = $data['csv_file'];
        $academicYearId = $data['academic_year_id'];
        
        // Get file content using Storage
        try {
            // Direct approach using Storage
            $fileContents = Storage::disk('public')->get($csvFile);
            
            // Create a temporary file to work with
            $tempFile = tempnam(sys_get_temp_dir(), 'csv_import_');
            file_put_contents($tempFile, $fileContents);
            
            Notification::make()
                ->title('Processing file')
                ->info()
                ->send();
                
            // Open the temporary file
            $handle = fopen($tempFile, 'r');
            
            if ($handle === false) {
                Notification::make()
                    ->title('Could not open temporary file')
                    ->danger()
                    ->send();
                return;
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error reading file')
                ->body($e->getMessage())
                ->danger()
                ->send();
            return;
        }
        
        // Define the expected headers and their positions
        $expectedHeaders = [
            'PROGRAM', 'CODE', 'COURSE', 'UNIT', 'TYPE', 'PREREQUISITE', 'LEVEL', 'SEMESTER'
        ];
        
        // Map common alternative header names
        $headerMap = [
            'PROGRAM' => ['PROGRAM', 'PROGRAMS', 'PROGRAM_CODE', 'PROGRAM CODE', 'PROGRAM CODES', 'PROGRAM CODES'],
            'CODE' => ['CODE', 'COURSE_CODE', 'COURSE CODE'],
            'COURSE' => ['COURSE', 'COURSE_NAME', 'COURSE NAME', 'NAME', 'TITLE'],
            'UNIT' => ['UNIT', 'UNITS', 'CREDIT', 'CREDITS'],
            'TYPE' => ['TYPE', 'COURSE_TYPE', 'COURSE TYPE'],
            'PREREQUISITE' => ['PREREQUISITE', 'PREREQUISITES', 'PREREQ', 'PRE-REQUISITE', 'PRE_REQUISITE'],
            'LEVEL' => ['LEVEL', 'YEAR', 'YEAR_LEVEL', 'YEAR LEVEL'],
            'SEMESTER' => ['SEMESTER', 'SEM', 'TERM'],
        ];
        
        // Check if headers match expected format
        $headers = fgetcsv($handle);
        
        if (!$headers) {
            Notification::make()
                ->title('CSV file is empty or not properly formatted')
                ->danger()
                ->send();
            fclose($handle);
            return;
        }
        
        // Normalize headers (trim, uppercase)
        $normalizedHeaders = array_map(function($header) {
            return strtoupper(trim($header));
        }, $headers);
        
        // Map provided headers to expected headers
        $headerMapping = [];
        $missingHeaders = [];
        
        foreach ($expectedHeaders as $expected) {
            $found = false;
            
            // Try to find a match in the provided headers
            foreach ($normalizedHeaders as $index => $header) {
                if (in_array($header, $headerMap[$expected])) {
                    $headerMapping[$expected] = $index;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $missingHeaders[] = $expected;
            }
        }
        
        // If there are missing headers, show error
        if (!empty($missingHeaders)) {
            Notification::make()
                ->title('CSV file has missing required headers')
                ->body('Missing: ' . implode(', ', $missingHeaders) . '. Expected headers are: ' . implode(', ', $expectedHeaders))
                ->danger()
                ->send();
            fclose($handle);
            return;
        }
        
        // Prepare to collect validation errors and successes
        $errors = [];
        $importedCount = 0;
        $updatedCount = 0;
        $mergedProgramsCount = 0;
        $rowNumber = 1; // Start after header
        
        // We'll process in a transaction
        DB::beginTransaction();
        
        try {
            // Map programs for quick lookup
            $programs = Program::pluck('id', 'code')->toArray();
            
            // Map existing courses by code for quick lookup and prerequisite handling
            $existingCourses = Course::pluck('id', 'code')->toArray();

            // Get all existing courses with their program relationships for merging
            $existingCourseDetails = Course::with('programs')->get()->keyBy('code');
            
            // First pass: Collect all courses to be imported
            $coursesData = [];
            $csvRows = [];
            
            // Process the CSV rows
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Basic validation for row length
                if (count($row) < count($headerMapping)) {
                    $errors[] = "Row {$rowNumber}: Incomplete data";
                    continue;
                }
                
                // Create data using header mapping
                $rowData = [];
                foreach ($expectedHeaders as $header) {
                    $rowData[$header] = isset($row[$headerMapping[$header]]) ? trim($row[$headerMapping[$header]]) : '';
                }
                
                $csvRows[] = $rowData;
                
                // Validate course data
                $courseCode = $rowData['CODE'];
                if (empty($courseCode)) {
                    $errors[] = "Row {$rowNumber}: Missing course code";
                    continue;
                }
                
                $courseName = $rowData['COURSE'];
                if (empty($courseName)) {
                    $errors[] = "Row {$rowNumber}: Missing course name";
                    continue;
                }
                
                $unit = (int)$rowData['UNIT'];
                if ($unit <= 0 || $unit > 10) {
                    $errors[] = "Row {$rowNumber}: Invalid unit value (must be between 1 and 10)";
                    continue;
                }
                
                $type = $rowData['TYPE'];
                $validTypes = array_keys(self::getTypeOptions());
                if (!in_array($type, $validTypes)) {
                    $errors[] = "Row {$rowNumber}: Invalid type '{$type}'. Must be one of: " . implode(', ', $validTypes);
                    continue;
                }
                
                $level = $rowData['LEVEL'];
                if (empty($level)) {
                    $errors[] = "Row {$rowNumber}: Missing level";
                    continue;
                }
                
                $semester = $rowData['SEMESTER'];
                if (empty($semester)) {
                    $errors[] = "Row {$rowNumber}: Missing semester";
                    continue;
                }
                
                // Program validation
                $programCodes = array_map('trim', explode(',', $rowData['PROGRAM']));
                if (empty($programCodes) || $programCodes[0] === '') {
                    $errors[] = "Row {$rowNumber}: Missing program code(s)";
                    continue;
                }
                
                // Check if all program codes exist
                $missingPrograms = [];
                foreach ($programCodes as $programCode) {
                    if (!isset($programs[$programCode])) {
                        $missingPrograms[] = $programCode;
                    }
                }
                
                if (!empty($missingPrograms)) {
                    $errors[] = "Row {$rowNumber}: Unknown program code(s): " . implode(', ', $missingPrograms);
                    continue;
                }
                
                // Check for duplicate course in the same import file
                if (isset($coursesData[$courseCode])) {
                    // Check if all required fields match exactly before merging
                    $existingLevel = $coursesData[$courseCode]['level'];
                    $existingUnit = $coursesData[$courseCode]['unit'];
                    $existingSemester = $coursesData[$courseCode]['semester'];
                    $existingName = $coursesData[$courseCode]['name'];
                    $normalizedLevel = self::normalizeFormData(['level' => $level])['level'];
                    $normalizedSemester = self::normalizeFormData(['semester' => $semester])['semester'];
                    
                    if ($existingLevel !== $normalizedLevel || 
                        $existingSemester !== $normalizedSemester || 
                        $existingUnit != $unit ||
                        $existingName !== $courseName) {
                        // Instead of error, we'll treat this as a separate entry
                        // Modify the code to make it unique by appending a suffix
                        $newCode = $courseCode . '_' . $rowNumber;
                        $errors[] = "Row {$rowNumber}: Course with code '{$courseCode}' has different attributes. Creating separate entry with code '{$newCode}'.";
                        $courseCode = $newCode;
                        
                        // Store new course with modified code
                        $coursesData[$courseCode] = [
                            'name' => $courseName,
                            'code' => $courseCode,
                            'unit' => $unit,
                            'type' => $type,
                            'level' => self::normalizeFormData(['level' => $level])['level'],
                            'semester' => self::normalizeFormData(['semester' => $semester])['semester'],
                            'academic_year_id' => $academicYearId,
                            'programs' => $programCodes,
                            'prerequisites' => array_filter(array_map('trim', explode(',', $rowData['PREREQUISITE'] ?? ''))),
                        ];
                        
                        continue;
                    }
                    
                    // Merge programs if the course has identical values for all required fields
                    $existingPrograms = $coursesData[$courseCode]['programs'];
                    $mergedPrograms = array_unique(array_merge($existingPrograms, $programCodes));
                    
                    if (count($mergedPrograms) > count($existingPrograms)) {
                        $coursesData[$courseCode]['programs'] = $mergedPrograms;
                        $mergedProgramsCount++;
                    }
                    
                    // Keep the current data since all fields match
                    $coursesData[$courseCode]['prerequisites'] = array_filter(array_map('trim', explode(',', $rowData['PREREQUISITE'] ?? '')));
                    
                    continue;
                }
                
                // Store normalized course data
                $coursesData[$courseCode] = [
                    'name' => $courseName,
                    'code' => $courseCode,
                    'unit' => $unit,
                    'type' => $type,
                    'level' => self::normalizeFormData(['level' => $level])['level'],
                    'semester' => self::normalizeFormData(['semester' => $semester])['semester'],
                    'academic_year_id' => $academicYearId,
                    'programs' => $programCodes,
                    'prerequisites' => array_filter(array_map('trim', explode(',', $rowData['PREREQUISITE'] ?? ''))),
                ];
            }
            
            // Second pass: Create courses and handle relationships
            foreach ($coursesData as $courseCode => $courseData) {
                // Check if course already exists
                $existingCourse = isset($existingCourseDetails[$courseCode]) ? $existingCourseDetails[$courseCode] : null;
                
                if ($existingCourse) {
                    // Check if all required fields match exactly before updating
                    $existingLevel = $existingCourse->level;
                    $existingUnit = $existingCourse->unit;
                    $existingSemester = $existingCourse->semester;
                    $existingName = $existingCourse->name;
                    
                    if ($existingLevel !== $courseData['level'] || 
                        $existingSemester !== $courseData['semester'] || 
                        $existingUnit != $courseData['unit'] ||
                        $existingName !== $courseData['name']) {
                        // Instead of updating, create a new course with modified code
                        $newCode = $courseCode . '_' . date('Ymd');
                        $errors[] = "Course {$courseCode}: Existing course has different attributes. Creating new entry with code '{$newCode}'.";
                        
                        // Create new course with modified code
                        $course = Course::create([
                            'name' => $courseData['name'],
                            'code' => $newCode,
                            'unit' => $courseData['unit'],
                            'type' => $courseData['type'],
                            'level' => $courseData['level'],
                            'semester' => $courseData['semester'],
                            'academic_year_id' => $courseData['academic_year_id'],
                        ]);
                        
                        // Update existing courses lookup
                        $existingCourses[$newCode] = $course->id;
                        $importedCount++;
                        
                        // Associate programs with the new course only
                        $programIds = [];
                        foreach ($courseData['programs'] as $programCode) {
                            if (isset($programs[$programCode])) {
                                $programIds[] = $programs[$programCode];
                            }
                        }
                        
                        // Sync programs for the new course
                        if (!empty($programIds)) {
                            $course->programs()->sync($programIds);
                        }
                        
                        continue;
                    }
                    
                    // Get existing program codes
                    $existingProgramCodes = $existingCourse->programs->pluck('code')->toArray();
                    
                    // Merge with new program codes
                    $allProgramCodes = array_unique(array_merge($existingProgramCodes, $courseData['programs']));
                    
                    // Check if we're adding new programs
                    $addingNewPrograms = count($allProgramCodes) > count($existingProgramCodes);
                    
                    // Update existing course type only as other fields must match
                    $existingCourse->update([
                        'type' => $courseData['type'],
                        'academic_year_id' => $courseData['academic_year_id'],
                    ]);
                    
                    $course = $existingCourse;
                    $updatedCount++;
                    
                    if ($addingNewPrograms) {
                        $mergedProgramsCount++;
                    }
                    
                    // Use all the program codes for syncing
                    $courseData['programs'] = $allProgramCodes;
                } else {
                    // Create new course
                    $course = Course::create([
                        'name' => $courseData['name'],
                        'code' => $courseCode,
                        'unit' => $courseData['unit'],
                        'type' => $courseData['type'],
                        'level' => $courseData['level'],
                        'semester' => $courseData['semester'],
                        'academic_year_id' => $courseData['academic_year_id'],
                    ]);
                    
                    // Update existing courses lookup
                    $existingCourses[$courseCode] = $course->id;
                    $importedCount++;
                }
                
                // Associate programs (many-to-many)
                $programIds = [];
                foreach ($courseData['programs'] as $programCode) {
                    if (isset($programs[$programCode])) {
                        $programIds[] = $programs[$programCode];
                    }
                }
                
                // Sync programs
                if (!empty($programIds)) {
                    $course->programs()->sync($programIds);
                }
            }
            
            // Third pass: Handle prerequisites after all courses are created
            foreach ($coursesData as $courseCode => $courseData) {
                $course = Course::where('code', $courseCode)->first();
                
                if (!$course) continue;
                
                $prerequisites = array_filter($courseData['prerequisites']);
                if (empty($prerequisites) || $prerequisites[0] === '') {
                    continue;
                }
                
                $prerequisiteIds = [];
                $missingPrereqs = [];
                
                foreach ($prerequisites as $prereqCode) {
                    if (isset($existingCourses[$prereqCode])) {
                        $prerequisiteIds[] = $existingCourses[$prereqCode];
                    } else {
                        $missingPrereqs[] = $prereqCode;
                    }
                }
                
                // Log missing prerequisites but don't fail the import
                if (!empty($missingPrereqs)) {
                    $errors[] = "Course {$courseCode}: Missing prerequisite course(s): " . implode(', ', $missingPrereqs);
                }
                
                // Sync prerequisites
                if (!empty($prerequisiteIds)) {
                    $course->prerequisiteCourses()->sync($prerequisiteIds);
                }
            }
            
            // Commit the transaction if everything went well
            DB::commit();
            
            // Build a summary message
            $summaryParts = [];
            if ($importedCount > 0) {
                $summaryParts[] = "{$importedCount} new courses added";
            }
            if ($updatedCount > 0) {
                $summaryParts[] = "{$updatedCount} courses updated";
            }
            if ($mergedProgramsCount > 0) {
                $summaryParts[] = "{$mergedProgramsCount} courses had programs merged";
            }
            
            $summary = implode(', ', $summaryParts);
            
            if (!empty($errors)) {
                // We had some warnings but import was partially successful
                Notification::make()
                    ->title("Import completed with warnings")
                    ->body("{$summary}. " . count($errors) . " warnings encountered.")
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title("Import successful")
                    ->body($summary)
                    ->success()
                    ->send();
            }
            
        } catch (\Exception $e) {
            // Roll back the transaction on error
            DB::rollBack();
            Notification::make()
                ->title('Import failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            // Close file handle and remove temp file if it exists
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    // ✅ Add this method to show count badge in sidebar
    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    // Level options used in multiple places
    private static function getLevelOptions(): array
    {
        return [
            '1' => '1st Year',
            '2' => '2nd Year',
            '3' => '3rd Year',
            '4' => '4th Year',
        ];
    }

    // Semester options used in multiple places
    private static function getSemesterOptions(): array
    {
        return [
            '1' => '1st Semester',
            '2' => '2nd Semester',
            'SUMMER' => 'Summer',
        ];
    }

    // Course type options
    private static function getTypeOptions(): array
    {
        return [
            'GEN_ED' => 'GEN_ED',
            'TECH_SKILL' => 'TECH_SKILL',
            'CAP_OJT' => 'CAP_OJT',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Course Information')
                    ->schema([
                        Forms\Components\Select::make('academic_year_id')
                            ->relationship('academicYear', 'name')
                            ->label('Academic Year')
                            ->default(fn () => \App\Models\AcademicYear::where('is_active', true)->first()?->id)
                            ->preload()
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('unit')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10),
                        Forms\Components\Select::make('type')
                            ->options(self::getTypeOptions())
                            ->default('GEN_ED')
                            ->required(),
                        Forms\Components\Select::make('prerequisites')
                            ->multiple()
                            ->relationship('prerequisiteCourses', 'name', fn (Builder $query, $record) => 
                                $record ? $query->where('courses.id', '!=', $record->id) : $query
                            )
                            ->preload()
                            ->label('Prerequisites'),
                        Forms\Components\Select::make('level')
                            ->options(self::getLevelOptions())
                            ->required(),
                        Forms\Components\Select::make('semester')
                            ->options(self::getSemesterOptions())
                            ->required(),
                        Forms\Components\Select::make('programs')
                            ->relationship('programs', 'code')
                            ->multiple()
                            ->preload()
                            ->required()
                            ->label('Programs')
                            ->helperText('All programs this course belongs to - course will appear in these programs\' curriculum')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('program_category_id')
                                    ->label('Program Category')
                                    ->options(ProgramCategory::all()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable(),
                                Forms\Components\Select::make('campus_id')
                                    ->label('Campus')
                                    ->options(Campus::all()->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->multiple(),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return Program::create($data)->id;
                            })
                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                return $action->modalWidth('xl');
                            }),
                    ])->columns(2),
            ]);
    }

    // Data normalization for create and edit
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        return static::normalizeFormData($data);
    }

    public static function mutateFormDataBeforeUpdate(array $data): array
    {
        return static::normalizeFormData($data);
    }

    // Helper function to normalize level and semester values
    protected static function normalizeFormData(array $data): array
    {
        // Normalize level values
        if (isset($data['level'])) {
            $data['level'] = match ($data['level']) {
                '1' => 'First Year',
                '2' => 'Second Year',
                '3' => 'Third Year',
                '4' => 'Fourth Year',
                '1st Year', 'First Year' => 'First Year',
                '2nd Year', 'Second Year' => 'Second Year',
                '3rd Year', 'Third Year' => 'Third Year',
                '4th Year', 'Fourth Year' => 'Fourth Year',
                default => $data['level'],
            };
        }
        
        // Normalize semester values
        if (isset($data['semester'])) {
            $data['semester'] = match ($data['semester']) {
                '1' => 'First Semester',
                '2' => 'Second Semester',
                'SUMMER' => 'SUMMER',
                '1st Semester', 'First Semester' => 'First Semester',
                '2nd Semester', 'Second Semester' => 'Second Semester',
                'Summer' => 'SUMMER',
                default => $data['semester'],
            };
        }
        
        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('programs.code')
                    ->label('Programs')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->searchable(),
                Tables\Columns\TextColumn::make('level')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'First Year' => '1st Year',
                        'Second Year' => '2nd Year',
                        'Third Year' => '3rd Year',
                        'Fourth Year' => '4th Year',
                        default => $state,
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('semester')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'First Semester' => '1st Semester',
                        'Second Semester' => '2nd Semester',
                        'SUMMER' => 'Summer',
                        default => $state,
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->limit(30)
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('prerequisiteCourses.code')
                    ->label('Pre-Requisite(s)')
                    ->listWithLineBreaks()
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('academic_year_id')
                    ->relationship('academicYear', 'name')
                    ->label('Academic Year')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('program')
                    ->relationship('programs', 'code')
                    ->label('Program')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('level')
                    ->options(self::getLevelOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        // Handle both formats
                        return $query->where(function ($query) use ($data) {
                            $query->where('level', $data['value'])
                                  ->orWhere('level', match ($data['value']) {
                                      '1' => 'First Year',
                                      '2' => 'Second Year',
                                      '3' => 'Third Year',
                                      '4' => 'Fourth Year',
                                      default => $data['value'],
                                  });
                        });
                    }),
                Tables\Filters\SelectFilter::make('semester')
                    ->options(self::getSemesterOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        // Handle both formats
                        return $query->where(function ($query) use ($data) {
                            $query->where('semester', $data['value'])
                                  ->orWhere('semester', match ($data['value']) {
                                      '1' => 'First Semester',
                                      '2' => 'Second Semester',
                                      'SUMMER' => 'SUMMER',
                                      default => $data['value'],
                                  });
                        });
                    }),
                Tables\Filters\SelectFilter::make('type')
                    ->options(self::getTypeOptions()),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading('Course Information')
                        ->modalIcon('heroicon-o-book-open')
                        ->modalWidth('xl')
                        ->color('gray')
                        ->infolist(fn (Infolist $infolist): Infolist => $infolist
                            ->schema([
                                Infolists\Components\Section::make('Course Details')
                                    ->description('Course information')
                                    ->icon('heroicon-o-book-open')
                                    ->schema([
                                        Infolists\Components\Grid::make(2)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('code')
                                                    ->label('Course Code')
                                                    ->icon('heroicon-o-identification')
                                                    ->copyable()
                                                    ->weight('bold')
                                                    ->color('gray')
                                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                                    
                                                Infolists\Components\TextEntry::make('name')
                                                    ->label('Course Name')
                                                    ->icon('heroicon-o-book-open')
                                                    ->color('gray')
                                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                            ]),
                                            
                                        Infolists\Components\Grid::make(3)
                                            ->schema([
                                                Infolists\Components\TextEntry::make('unit')
                                                    ->label('Units')
                                                    ->icon('heroicon-o-rectangle-stack')
                                                    ->color('gray')
                                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                                    
                                                Infolists\Components\TextEntry::make('level')
                                                    ->label('Year Level')
                                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                                        'First Year' => '1st Year',
                                                        'Second Year' => '2nd Year',
                                                        'Third Year' => '3rd Year',
                                                        'Fourth Year' => '4th Year',
                                                        default => $state,
                                                    })
                                                    ->icon('heroicon-o-arrow-trending-up')
                                                    ->color('gray')
                                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                                    
                                                Infolists\Components\TextEntry::make('semester')
                                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                                        'First Semester' => '1st Semester',
                                                        'Second Semester' => '2nd Semester',
                                                        'SUMMER' => 'Summer',
                                                        default => $state,
                                                    })
                                                    ->icon('heroicon-o-calendar')
                                                    ->color('gray')
                                                    ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                            ]),
                                            
                                        Infolists\Components\TextEntry::make('prerequisiteCourses.code')
                                            ->label('Prerequisites')
                                            ->listWithLineBreaks()
                                            ->bulleted()
                                            ->icon('heroicon-o-arrow-path')
                                            ->visible(fn ($record) => $record->prerequisiteCourses->count() > 0)
                                            ->color('gray')
                                            ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                            
                                        Infolists\Components\TextEntry::make('programs.code')
                                            ->label('Programs')
                                            ->listWithLineBreaks()
                                            ->bulleted()
                                            ->icon('heroicon-o-academic-cap')
                                            ->color('gray')
                                            ->extraAttributes(['class' => 'p-2 rounded-lg bg-gray-900 bg-opacity-50']),
                                    ])
                                    ->collapsible()
                                    ->extraAttributes(['class' => 'bg-gray-950 border border-gray-800 rounded-xl p-6 shadow-lg']),
                            ])
                            ->extraAttributes(['class' => 'p-0 bg-gray-950'])
                        ),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
} 