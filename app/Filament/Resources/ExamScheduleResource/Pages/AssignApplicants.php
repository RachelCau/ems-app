<?php

namespace App\Filament\Resources\ExamScheduleResource\Pages;

use App\Filament\Resources\ExamScheduleResource;
use App\Models\Applicant;
use App\Models\ApplicantExamSchedule;
use App\Models\ExamSchedule;
use Filament\Resources\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssignApplicants extends Page implements Forms\Contracts\HasForms, Tables\Contracts\HasTable
{
    use Forms\Concerns\InteractsWithForms;
    use Tables\Concerns\InteractsWithTable;
    
    protected static string $resource = ExamScheduleResource::class;

    protected static string $view = 'filament.resources.exam-schedule-resource.pages.assign-applicants';
    
    public ?ExamSchedule $record = null;
    
    public function mount(ExamSchedule $record): void
    {
        $this->record = $record;
    }
    
    public function table(Table $table): Table
    {
        // Try to find CHED category ID
        $chedCategoryId = DB::table('program_categories')
            ->where('name', 'CHED')
            ->orWhere('name', 'like', '%CHED%')
            ->value('id');
        
        return $table
            ->query(
                Applicant::query()
                    ->where('application_status', 'approved')
                    ->where(function (Builder $query) use ($chedCategoryId) {
                        // First try with program relationship
                        $query->whereHas('program', function (Builder $query) use ($chedCategoryId) {
                            if ($chedCategoryId) {
                                $query->where('program_category_id', $chedCategoryId);
                            } else {
                                // Fallback to direct check when category ID can't be found
                                $query->whereHas('category', function (Builder $query) {
                                    $query->where('name', 'CHED')
                                          ->orWhere('name', 'like', '%CHED%');
                                });
                            }
                        })
                        // Fallback to program_category field
                        ->orWhere('program_category', 'CHED')
                        ->orWhere('program_category', 'like', '%CHED%');
                    })
                    ->whereDoesntHave('applicantExamSchedules', function (Builder $query) {
                        $query->where('exam_schedule_id', $this->record->id);
                    })
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Applicant Name')
                    ->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('program.name')
                    ->label('Program')
                    ->description(fn (Applicant $record): ?string => 
                        $record->program?->name ? null : $record->desired_program)
                    ->placeholder('Not specified')
                    ->searchable(),
                Tables\Columns\TextColumn::make('application_number')
                    ->label('Application #')
                    ->searchable(),
                Tables\Columns\TextColumn::make('application_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'declined' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                // You can add filters here
            ])
            ->actions([
                Tables\Actions\Action::make('assign')
                    ->label('Assign')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->disabled(fn () => $this->record->approved_applicants_count >= $this->record->capacity)
                    ->action(function (Applicant $applicant) {
                        // Check if there's still capacity available
                        if ($this->record->approved_applicants_count >= $this->record->capacity) {
                            Notification::make()
                                ->title('Capacity Full')
                                ->body('This exam schedule has reached its maximum capacity.')
                                ->danger()
                                ->send();
                                
                            return;
                        }
                    
                        // Assign the applicant to this exam schedule
                        ApplicantExamSchedule::create([
                            'applicant_id' => $applicant->id,
                            'exam_schedule_id' => $this->record->id,
                            'status' => 'Assigned',
                        ]);
                        
                        // Refresh the record to update approved_applicants_count
                        $this->record->refresh();
                        
                        Notification::make()
                            ->title('Applicant Assigned')
                            ->body("The applicant has been assigned to this exam schedule.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('assign_bulk')
                    ->label('Assign Selected')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->action(function (Collection $records) {
                        $remainingCapacity = $this->record->capacity - $this->record->approved_applicants_count;
                        
                        if ($remainingCapacity <= 0) {
                            Notification::make()
                                ->title('Capacity Full')
                                ->body('This exam schedule has reached its maximum capacity.')
                                ->danger()
                                ->send();
                                
                            return;
                        }
                        
                        $assignedCount = 0;
                        
                        foreach ($records as $applicant) {
                            if ($assignedCount >= $remainingCapacity) {
                                break; // Stop assigning when capacity is reached
                            }
                            
                            // Check if already assigned
                            $exists = ApplicantExamSchedule::where('applicant_id', $applicant->id)
                                ->where('exam_schedule_id', $this->record->id)
                                ->exists();
                                
                            if (!$exists) {
                                ApplicantExamSchedule::create([
                                    'applicant_id' => $applicant->id,
                                    'exam_schedule_id' => $this->record->id,
                                    'status' => 'Assigned',
                                ]);
                                
                                $assignedCount++;
                            }
                        }
                        
                        // Refresh the record to update approved_applicants_count
                        $this->record->refresh();
                        
                        Notification::make()
                            ->title('Applicants Assigned')
                            ->body("{$assignedCount} applicants have been assigned to this exam schedule.")
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
    
    protected function getHeaderActions(): array
    {
        return [
            // Add actions here if needed
        ];
    }
} 