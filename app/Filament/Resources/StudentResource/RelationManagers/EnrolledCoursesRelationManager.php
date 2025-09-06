<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Program;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use App\Models\StudentEnrollment;

class EnrolledCoursesRelationManager extends RelationManager
{
    protected static string $relationship = 'enrolledCourses';

    protected static ?string $recordTitleAttribute = 'course.name';

    protected static ?string $title = 'Enrolled Courses';

    public function form(Form $form): Form
    {
        $student = $this->getOwnerRecord();
        $programCode = $student->program_code;

        return $form
            ->schema([
                Forms\Components\Section::make('Course Information')
                    ->schema([
                        Forms\Components\Select::make('course_id')
                            ->label('Course')
                            ->options(function () {
                                $student = $this->getOwnerRecord();
                                $programCode = $student->program_code;
                                $yearLevel = $student->year_level;
                                $semester = $student->semester;
                                
                                // Ensure we have values to work with
                                if (empty($yearLevel) && empty($semester)) {
                                    // Fallback to default values if none are set
                                    $yearLevel = '1st year';
                                    $semester = '1';
                                }
                                
                                // Convert numeric year level to text format
                                if (is_numeric($yearLevel)) {
                                    $yearLevelMap = [
                                        1 => '1st year',
                                        2 => '2nd year',
                                        3 => '3rd year',
                                        4 => '4th year',
                                    ];
                                    $yearLevel = $yearLevelMap[$yearLevel] ?? '1st year';
                                }
                                
                                // Direct query to courses table - get ALL courses
                                $query = Course::query();
                                
                                // Show courses from all programs, not just the student's program
                                // This allows assigning any course to any student regardless of program
                                
                                // Get the courses and map them to options, grouped by program
                                $courses = $query->with('programs')->get();
                                
                                // Create a dictionary of program codes to program names for display
                                $programNames = [];
                                $programs = \App\Models\Program::all();
                                foreach ($programs as $program) {
                                    $programNames[$program->code] = $program->name;
                                }
                                
                                // Group courses by program and format for the dropdown
                                $courseOptions = [];
                                
                                // First, add the student's program courses at the top
                                if (!empty($programCode)) {
                                    $studentProgramCourses = $courses->filter(function($course) use($programCode) {
                                        return $course->programs->contains(function($program) use($programCode) {
                                            return $program->code === $programCode;
                                        });
                                    });
                                    
                                    if ($studentProgramCourses->isNotEmpty()) {
                                        $programName = $programNames[$programCode] ?? "Program: {$programCode}";
                                        $courseOptions["Student's Program: {$programName}"] = $studentProgramCourses->mapWithKeys(function ($course) {
                                            return [$course->id => "{$course->code} - {$course->name} ({$course->unit} units)"];
                                        })->toArray();
                                    }
                                }
                                
                                // Add all programs
                                foreach ($programs as $program) {
                                    // Skip if this is the student's program (already added above)
                                    if ($program->code === $programCode) {
                                        continue;
                                    }
                                    
                                    $programCourses = $courses->filter(function($course) use($program) {
                                        return $course->programs->contains(function($p) use($program) {
                                            return $p->id === $program->id;
                                        });
                                    });
                                    
                                    if ($programCourses->isNotEmpty()) {
                                        $courseOptions["Program: {$program->name} ({$program->code})"] = $programCourses->mapWithKeys(function ($course) {
                                            return [$course->id => "{$course->code} - {$course->name} ({$course->unit} units)"];
                                        })->toArray();
                                    }
                                }
                                
                                // Add unassigned courses (courses not assigned to any program)
                                $unassignedCourses = $courses->filter(function($course) {
                                    return $course->programs->isEmpty();
                                });
                                
                                if ($unassignedCourses->isNotEmpty()) {
                                    $courseOptions["Other Courses"] = $unassignedCourses->mapWithKeys(function ($course) {
                                        return [$course->id => "{$course->code} - {$course->name} ({$course->unit} units)"];
                                    })->toArray();
                                }
                                
                                return $courseOptions;
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('academic_year_id')
                            ->label('Academic Year')
                            ->options(AcademicYear::pluck('name', 'id'))
                            ->required()
                            ->default(function () {
                                return AcademicYear::where('is_active', true)->first()?->id ??
                                    AcademicYear::latest('id')->first()?->id;
                            }),
                        Forms\Components\Select::make('status')
                            ->options([
                                'enrolled' => 'Enrolled',
                                'completed' => 'Completed',
                                'dropped' => 'Dropped',
                                'failed' => 'Failed',
                                'incomplete' => 'Incomplete',
                            ])
                            ->required()
                            ->default('enrolled'),
                        Forms\Components\TextInput::make('grade')
                            ->maxLength(255),
                        Forms\Components\Hidden::make('student_number')
                            ->default(function ($livewire) {
                                return $livewire->getOwnerRecord()->student_number;
                            }),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->recordUrl(false)
            ->columns([
                Tables\Columns\TextColumn::make('course.code')
                    ->label('Course Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course.name')
                    ->label('Course Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course.unit')
                    ->label('Units')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('updateGrades')
                        ->label('Update Grades')
                        ->icon('heroicon-o-academic-cap')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options([
                                    'completed' => 'Completed',
                                    'incomplete' => 'Incomplete',
                                    'failed' => 'Failed',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('grade')
                                ->label('Grade')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100),
                        ])
                        ->action(function (array $data, $records): void {
                            foreach ($records as $record) {
                                $record->status = $data['status'];
                                if (isset($data['grade'])) {
                                    $record->grade = $data['grade'];
                                }
                                $record->save();
                            }
                        }),
                ]),
            ]);
    }
}
