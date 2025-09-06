<?php

namespace App\Filament\Resources\ExamScheduleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Applicant;
use App\Models\InterviewSchedule;
use App\Models\ApplicantInterviewSchedule;
use App\Models\User;
use App\Filament\Imports\ApplicantExamScheduleScoreImporter;
use App\Filament\Exports\ApplicantExamScheduleExporter;
use Filament\Tables\Actions\ImportAction;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use App\Events\ApplicationStatusChanged;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

class ApplicantExamSchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'applicantExamSchedules';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('applicant_id')
                    ->label('Applicant')
                    ->options(Applicant::all()->pluck('full_name', 'id'))
                    ->required()
                    ->searchable(),

                Forms\Components\TextInput::make('total_items')
                    ->label('Total Number of Items')
                    ->numeric()
                    ->integer()
                    ->default(75)
                    ->minValue(1)
                    ->required()
                    ->reactive(),

                Forms\Components\TextInput::make('score')
                    ->label('Score')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(fn (callable $get) => (int)$get('total_items'))
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if ($state === null) {
                            $set('remarks', null);
                            return;
                        }
                        
                        $totalItems = (int)$get('total_items');
                        $passingScore = round($totalItems * 0.75, 2);
                        $score = (float) $state;
                        $set('remarks', $score >= $passingScore ? 'passed' : 'failed');
                        
                        $set('status', 'Attended');
                    })
                    ->reactive(),

                Forms\Components\TextInput::make('remarks')
                    ->label('Remarks')
                    ->disabled()
                    ->dehydrated(),
 
                Forms\Components\Select::make('status')
                    ->options([
                        'Scheduled' => 'Scheduled',
                    ])
                    ->default('Scheduled')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('applicant.applicant_number')
                    ->label('Applicant Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('applicant.full_name')
                    ->label('Applicant name')
                    ->searchable(['applicant.first_name', 'applicant.last_name'])
                    ->sortable(['applicant.first_name', 'applicant.last_name'])
                    ->wrap(),
                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->numeric(2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_items')
                    ->label('Total Items')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('remarks')
                    ->label('Remarks')
                    ->badge()
                    ->formatStateUsing(fn($state) => ucfirst(strtolower($state)))
                    ->color(fn($state) => match (strtolower($state)) {
                        'passed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => match(strtolower($state)) {
                        'attended' => 'Done',
                        default => ucfirst(strtolower($state))
                    })
                    ->color('info'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->exporter(ApplicantExamScheduleExporter::class),
                ImportAction::make()
                    ->label('Import')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->importer(ApplicantExamScheduleScoreImporter::class)
                    ->options(fn(RelationManager $livewire) => [
                        'exam_schedule_id' => $livewire->getOwnerRecord()->id,
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->form([
                            Forms\Components\TextInput::make('applicant.applicant_number')
                                ->label('Applicant number')
                                ->formatStateUsing(fn($record) => $record->applicant->applicant_number)
                                ->disabled(),
                            Forms\Components\TextInput::make('score')
                                ->label('Score')
                                ->disabled(),
                            Forms\Components\TextInput::make('total_items')
                                ->label('Total Items')
                                ->disabled(),
                            Forms\Components\TextInput::make('status')
                                ->label('Status')
                                ->formatStateUsing(fn($state, $record) => ucfirst(strtolower($record->status)))
                                ->disabled(),
                            Forms\Components\TextInput::make('remarks')
                                ->label('Remarks')
                                ->formatStateUsing(fn($state, $record) => ucfirst(strtolower($record->remarks)))
                                ->disabled(),
                        ]),
                    Tables\Actions\EditAction::make()
                        ->form([
                            Forms\Components\TextInput::make('applicant.applicant_number')
                                ->label('Applicant number')
                                ->formatStateUsing(fn($record) => $record->applicant->applicant_number)
                                ->disabled(),
                            Forms\Components\TextInput::make('total_items')
                                ->label('Total Number of Items')
                                ->numeric()
                                ->integer()
                                ->default(75)
                                ->minValue(1)
                                ->required()
                                ->reactive(),
                            Forms\Components\TextInput::make('score')
                                ->label('Score')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(fn (callable $get) => (int)$get('total_items'))
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    if ($state === null) {
                                        $set('remarks', null);
                                        return;
                                    }
                                    
                                    $totalItems = (int)$get('total_items');
                                    $passingScore = round($totalItems * 0.75, 2);
                                    $score = (float) $state;
                                    $set('remarks', $score >= $passingScore ? 'passed' : 'failed');
                                    
                                    $set('status', 'Attended');
                                })
                                ->reactive(),
                            Forms\Components\TextInput::make('remarks')
                                ->label('Remarks')
                                ->disabled()
                                ->dehydrated(),
                            Forms\Components\Select::make('status')
                                ->options([
                                    'Scheduled' => 'Scheduled',
                                ])
                                ->default('Scheduled')
                                ->required(),
                        ])
                        ->after(function ($record) {
                            // Process passed or failed exams
                            if (strtolower($record->status) === 'attended') {
                                if (strtolower($record->remarks) === 'passed') {
                                    $this->processPassedExam($record);
                                } else if (strtolower($record->remarks) === 'failed') {
                                    $this->processFailedExam($record);
                                }
                            }
                        }),
                ])
                ->tooltip('Actions')
                ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                // No bulk actions needed
            ]);
    }

    /**
     * Process a passed exam - assign to interview or notify program head
     */
    public function processPassedExam($record): void
    {
        // Get the applicant
        $applicant = $record->applicant;
        if (!$applicant) {
            return;
        }

        // Save the old status for event notification
        $oldStatus = $applicant->status;

        // Try to find an available interview schedule
        $availableSchedule = $this->findAvailableInterviewSchedule();
        
        if ($availableSchedule) {
            // Assign to available interview schedule
            $this->assignToInterviewSchedule($applicant, $availableSchedule, $oldStatus, $record);
        } else {
            // No schedule available - notify program heads and set status
            $this->notifyProgramHeadsForInterviewSchedule($applicant, $oldStatus, $record);
        }
    }

    /**
     * Process a failed exam - send notification and update applicant status
     */
    public function processFailedExam($record): void
    {
        // Get the applicant
        $applicant = $record->applicant;
        if (!$applicant) {
            return;
        }

        // Save the old status for event notification
        $oldStatus = $applicant->status;

        // Prepare exam data for the email
        $examData = [
            'reason_type' => 'exam_failed',
            'applicant_number' => $applicant->applicant_number,
            'exam_date' => $record->examSchedule->exam_date->format('Y-m-d'),
            'score' => $record->score,
            'total_items' => $record->total_items,
            'passing_score' => round($record->total_items * 0.75, 2),
            'remarks' => ucfirst(strtolower($record->remarks)),
            'academic_year' => $applicant->academicYear->name ?? 'Current',
        ];
        
        // Update applicant status to declined
        $applicant->status = 'declined';
        $applicant->is_blacklisted = true; // Mark as blacklisted for current academic year
        $applicant->save();
        
        // Trigger event to send notification email
        event(new ApplicationStatusChanged($applicant, $oldStatus, 'declined', $examData));
        
        Notification::make()
            ->warning()
            ->title('Applicant Failed Exam')
            ->body($applicant->first_name . ' ' . $applicant->last_name . ' failed the entrance exam with a score of ' . $record->score . '/' . $record->total_items . '.')
            ->send();
    }

    /**
     * Find an available interview schedule with capacity
     */
    public function findAvailableInterviewSchedule()
    {
        // Look for available interview schedules with future dates
        return InterviewSchedule::where('interview_date', '>=', now()->format('Y-m-d'))
            ->get()
            ->filter(function ($schedule) {
                // Check if schedule has available capacity
                $usedCapacity = ApplicantInterviewSchedule::where('interview_schedule_id', $schedule->id)->count();
                return $usedCapacity < $schedule->capacity;
            })
            ->sortBy('interview_date')
            ->first();
    }

    /**
     * Assign applicant to interview schedule
     */
    public function assignToInterviewSchedule(Applicant $applicant, InterviewSchedule $schedule, string $oldStatus, $examRecord): void
    {
        // Check if the applicant is already assigned to any interview schedule
        $existingSchedule = ApplicantInterviewSchedule::where('applicant_id', $applicant->id)->first();
        
        if ($existingSchedule) {
            // Update the existing record instead of creating a new one
            $existingSchedule->interview_schedule_id = $schedule->id;
            $existingSchedule->status = 'Scheduled';
            $existingSchedule->save();
            $interviewSchedule = $existingSchedule;
        } else {
            // Create a new interview schedule assignment
            $interviewSchedule = ApplicantInterviewSchedule::create([
                'applicant_id' => $applicant->id,
                'interview_schedule_id' => $schedule->id,
                'status' => 'Scheduled'
            ]);
        }
        
        // Update applicant status
        $applicant->status = 'for interview';
        $applicant->save();
        
        // Create data for email with exam credentials and interview details
        $combinedData = [
            'reason_type' => 'exam_passed_with_interview',
            'applicant_number' => $applicant->applicant_number,
            'exam_date' => $examRecord->examSchedule->exam_date->format('Y-m-d'),
            'score' => $examRecord->score,
            'total_items' => $examRecord->total_items,
            'passing_score' => round($examRecord->total_items * 0.75, 2),
            'remarks' => ucfirst(strtolower($examRecord->remarks)),
            'interview_date' => $schedule->interview_date->format('Y-m-d'),
            'start_time' => $schedule->start_time->format('h:i A'),
            'end_time' => $schedule->end_time->format('h:i A'),
            'venue' => $schedule->venue ?? 'Main Campus',
            'interview_schedule_id' => $schedule->id,
            'applicant_interview_schedule_id' => $interviewSchedule->id,
        ];
        
        // Trigger event to send notification email
        event(new ApplicationStatusChanged($applicant, $oldStatus, 'for interview', $combinedData));
        
        Notification::make()
            ->success()
            ->title('Applicant Assigned to Interview')
            ->body($applicant->first_name . ' ' . $applicant->last_name . ' has passed the entrance exam with a score of ' . $examRecord->score . '/' . $examRecord->total_items . ' and has been assigned to an interview on ' . $schedule->interview_date->format('M d, Y') . '.')
            ->send();
    }

    /**
     * Notify program heads when no interview schedule is available
     */
    public function notifyProgramHeadsForInterviewSchedule(Applicant $applicant, string $oldStatus, $examRecord): void
    {
        // Find users with Program Head role
        $programHeads = User::whereHas('roles', function ($query) {
            $query->where('name', 'Program Head');
        })->get();
        
        // Get program details for notification
        $programName = $applicant->program->name ?? 'Not specified';
        
        // Change status to 'for interview' even without a schedule
        // This ensures they'll be flagged properly for interviews later
        $applicant->status = 'for interview';
        $applicant->save();
        
        // Create redirect URL to create interview schedule
        $createInterviewUrl = URL::to('/portal/interview-schedules');
        
        // Create data for email with exam credentials only
        $examData = [
            'reason_type' => 'exam_passed_no_interview',
            'applicant_number' => $applicant->applicant_number,
            'exam_date' => $examRecord->examSchedule->exam_date->format('Y-m-d'),
            'score' => $examRecord->score,
            'total_items' => $examRecord->total_items,
            'passing_score' => round($examRecord->total_items * 0.75, 2),
            'remarks' => ucfirst(strtolower($examRecord->remarks)),
        ];
        
        // Trigger event to send notification email
        event(new ApplicationStatusChanged($applicant, $oldStatus, 'for interview', $examData));
        
        // Notify each program head
        foreach ($programHeads as $programHead) {
            Notification::make()
                ->warning()
                ->title('Interview Schedule Needed')
                ->body("Applicant {$applicant->first_name} {$applicant->last_name} has passed the entrance exam with a score of {$examRecord->score}/{$examRecord->total_items} and has been marked for interview, but no interview schedule is available yet. Program: {$programName}")
                ->actions([
                    Action::make('create_schedule')
                        ->label('Create Schedule')
                        ->url($createInterviewUrl, shouldOpenInNewTab: true),
                ])
                ->sendToDatabase($programHead);
        }
        
        Notification::make()
            ->warning()
            ->title('No Interview Schedule Available')
            ->body('No interview schedule is available for ' . $applicant->first_name . ' ' . $applicant->last_name . ' who passed with a score of ' . $examRecord->score . '/' . $examRecord->total_items . '. They have been marked for interview, and Program Heads have been notified to create a schedule.')
            ->persistent()
            ->send();
    }
}
