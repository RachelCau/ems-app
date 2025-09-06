<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnrolledCourseResource\Pages;
use App\Filament\Resources\EnrolledCourseResource\RelationManagers;
use App\Models\EnrolledCourse;
use App\Models\StudentEnrollment;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\AcademicYear;
use App\Models\Program;
use App\Services\CourseAssignmentService;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EnrolledCourseResource extends Resource
{
    protected static ?string $model = EnrolledCourse::class;

    // protected static ?string $navigationIcon = 'heroicon-o-book-open';
    
    protected static ?string $navigationGroup = 'Academic Management';
    
    protected static ?int $navigationSort = 7;

    // ✅ Add this method to show count badge in sidebar
    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_number')
                    ->relationship('student', 'student_number')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Student Number')
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->student_number} - {$record->full_name}"),
                Forms\Components\Select::make('course_id')
                    ->relationship('course', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->code} - {$record->name}"),
                Forms\Components\Select::make('status')
                    ->options([
                        'enrolled' => 'Enrolled',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'dropped' => 'Dropped',
                    ])
                    ->required()
                    ->default('enrolled'),
                Forms\Components\TextInput::make('grade')
                    ->numeric()
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student_number')
                    ->label('Student Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course.code')
                    ->label('Course Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course.name')
                    ->label('Course Name')
                    ->limit(50)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course.unit')
                    ->label('Units')
                    ->numeric()
                    ->sortable(),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with(['course', 'student']);
            })
            ->filters([
                Tables\Filters\SelectFilter::make('program')
                    ->relationship('student', 'program_code')
                    ->searchable()
                    ->preload()
                    ->label('Program'),
                Tables\Filters\SelectFilter::make('year_level')
                    ->options([
                        '1st year' => '1st Year',
                        '2nd year' => '2nd Year',
                        '3rd year' => '3rd Year',
                        '4th year' => '4th Year',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn (Builder $query, $yearLevel): Builder =>
                                $query->whereHas('course', fn ($q) => $q->where('level', $yearLevel))
                            );
                    }),
                Tables\Filters\SelectFilter::make('semester')
                    ->options([
                        '1' => '1st Semester',
                        '2' => '2nd Semester',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn (Builder $query, $semester): Builder =>
                                $query->whereHas('course', fn ($q) => $q->where('semester', $semester))
                            );
                    }),
                Tables\Filters\Filter::make('student_number')
                    ->form([
                        Forms\Components\TextInput::make('student_number')
                            ->label('Student Number')
                            ->placeholder('Enter student number'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['student_number'],
                                fn (Builder $query, $studentNumber): Builder => $query->where('student_number', 'like', "%{$studentNumber}%")
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->form(function (EnrolledCourse $record) {
                            // Get the current record data
                            $course = $record->course;
                            $courseId = $record->course_id;
                            $studentNumber = $record->student_number;
                            $status = $record->status;
                            $grade = $record->grade;
                            
                            // Get the student name from the student number
                            $studentName = '';
                            if ($record->student) {
                                $studentName = $record->student->full_name;
                            }
                            
                            return [
                                Forms\Components\Select::make('student_number')
                                    ->label('Student Number')
                                    ->options([$studentNumber => "$studentNumber - " . ($studentName ?: 'Unknown')])
                                    ->disabled()
                                    ->dehydrated(false),
                                    
                                Forms\Components\Select::make('course_id')
                                    ->label('Course')
                                    ->options([$courseId => "{$course->code} - {$course->name}"])
                                    ->disabled()
                                    ->dehydrated(false),
                                    
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'enrolled' => 'Enrolled',
                                        'completed' => 'Completed',
                                        'failed' => 'Failed',
                                        'dropped' => 'Dropped',
                                    ])
                                    ->default($status)
                                    ->disabled()
                                    ->dehydrated(false),
                                    
                                Forms\Components\TextInput::make('grade')
                                    ->label('Grade')
                                    ->default($grade)
                                    ->disabled()
                                    ->dehydrated(false),
                            ];
                        }),
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
            'index' => Pages\ListEnrolledCourses::route('/'),
            'edit' => Pages\EditEnrolledCourse::route('/{record}/edit'),
        ];
    }
} 