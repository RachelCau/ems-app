<?php

namespace App\Filament\Resources\StudentEnrollmentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Course;
use App\Models\Program;

class EnrolledCoursesRelationManager extends RelationManager
{
    protected static string $relationship = 'enrolledCourses';

    public function form(Form $form): Form
    {
        $enrollment = $this->getOwnerRecord();
        $programCode = $enrollment->getEffectiveProgramCode();
        
        return $form
            ->schema([
                Forms\Components\Select::make('course_id')
                    ->label('Course')
                    ->options(function () use ($programCode) {
                        // If we have a program code, filter courses by it
                        if (!empty($programCode)) {
                            $program = Program::where('code', $programCode)->first();
                            
                            if ($program) {
                                // Get courses linked to this program
                                $courseIds = $program->courses()->pluck('courses.id')->toArray();
                                return Course::whereIn('id', $courseIds)
                                    ->get()
                                    ->mapWithKeys(function ($course) {
                                        return [$course->id => "{$course->code} - {$course->name}"];
                                    });
                            }
                        }
                        
                        // Fallback to all courses if no program found
                        return Course::all()->mapWithKeys(function ($course) {
                            return [$course->id => "{$course->code} - {$course->name}"];
                        });
                    })
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('status')
                    ->options([
                        'enrolled' => 'Enrolled',
                        'completed' => 'Completed',
                        'dropped' => 'Dropped',
                        'failed' => 'Failed',
                        'incomplete' => 'Incomplete',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('grade')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('course.name')
                    ->label('Course')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course.code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('course.unit')
                    ->label('Units')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'enrolled' => 'info',
                        'completed' => 'success',
                        'dropped' => 'danger',
                        'failed' => 'danger',
                        'incomplete' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('grade')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'enrolled' => 'Enrolled',
                        'completed' => 'Completed',
                        'dropped' => 'Dropped',
                        'failed' => 'Failed',
                        'incomplete' => 'Incomplete',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 