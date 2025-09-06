<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdmissionDocumentResource\Pages;
use App\Filament\Resources\AdmissionDocumentResource\RelationManagers;
use App\Models\AdmissionDocument;
use App\Models\Applicant;
use App\Events\ApplicationStatusChanged;
use App\Models\ExamSchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class AdmissionDocumentResource extends Resource
{
    protected static ?string $model = AdmissionDocument::class;
    
    protected static ?string $navigationGroup = 'Application Management';
    
    protected static ?int $navigationSort = 3;

     // ✅ Add this method to show count badge in sidebar
     public static function getNavigationBadge(): ?string
     {
         return static::$model::count();
     }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Admission Document Information')
                    ->schema([
                        Forms\Components\Select::make('applicant_id')
                            ->label('Applicant')
                            ->relationship('applicant', 'applicant_number')
                            ->getOptionLabelFromRecordUsing(fn (Applicant $record): string => $record->display_name)
                            ->preload()
                            ->searchable(['first_name', 'last_name', 'applicant_number'])
                            ->required()
                            ->disabled(fn ($context) => $context === 'edit'),
                        Forms\Components\TextInput::make('document_type')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn ($context) => $context === 'edit'),
                        Forms\Components\DateTimePicker::make('submitted_at')
                            ->required()
                            ->disabled(fn ($context) => $context === 'edit'),
                        Forms\Components\Textarea::make('remarks')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('applicant.display_name')
                    ->label('Applicant')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('applicant', function (Builder $query) use ($search): Builder {
                            return $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('applicant_number', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(['applicant.last_name', 'applicant.first_name']),
                Tables\Columns\TextColumn::make('document_type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Missing' => 'warning',
                        'Submitted' => 'info',
                        'Verified' => 'success',
                        'Invalid' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),         
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Submitted' => 'Submitted',
                        'Missing' => 'Missing',
                        'Invalid' => 'Invalid',
                        'Verified' => 'Verified',
                    ]),
                Tables\Filters\SelectFilter::make('applicant_id')
                    ->relationship('applicant', 'applicant_number')
                    ->preload()
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('verify')
                        ->label('Verify Document')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->visible(fn (AdmissionDocument $record) => $record->status !== 'Verified')
                        ->requiresConfirmation()
                        ->modalHeading('Verify Document')
                        ->modalDescription('Are you sure you want to verify this document? This will mark it as accepted.')
                        ->modalSubmitActionLabel('Yes, verify document')
                        ->action(function (AdmissionDocument $record): void {
                            // Get the applicant and their program category
                            $applicant = $record->applicant;
                            if (!$applicant) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('Applicant not found.')
                                    ->send();
                                return;
                            }
                            
                            // Get program category
                            $programCategory = $applicant->program_category;
                            
                            // Handle numeric program categories by fetching the name
                            if (is_numeric($programCategory)) {
                                try {
                                    $categoryModel = \App\Models\ProgramCategory::find($programCategory);
                                    if ($categoryModel) {
                                        $programCategory = $categoryModel->name;
                                    }
                                } catch (\Exception $e) {
                                    // If any error, keep the original value
                                }
                            }
                            
                            // Check if all other documents are already verified
                            $otherDocuments = AdmissionDocument::where('applicant_id', $applicant->id)
                                ->where('id', '!=', $record->id)
                                ->get();
                                
                            $allOthersVerified = $otherDocuments->every(function ($doc) {
                                return $doc->status === 'Verified';
                            });
                            
                            // If this is the last document and applicant is CHED, check for exam schedules
                            if ($allOthersVerified && $programCategory === 'CHED') {
                                $hasExamSchedules = ExamSchedule::where('exam_date', '>=', now()->format('Y-m-d'))->exists();
                                
                                if (!$hasExamSchedules) {
                                    // Cannot verify - no exam schedules exist
                                    $admissionOfficers = \App\Models\User::whereHas('roles', function ($query) {
                                        $query->where('name', 'Admission Officer');
                                    })->get();
                                    
                                    foreach ($admissionOfficers as $officer) {
                                        Notification::make()
                                            ->warning()
                                            ->title('Verification Prevented')
                                            ->body('Cannot verify the final document for ' . $applicant->display_name . ' - no exam schedule exists. Please create an exam schedule first before verifying all documents.')
                                            ->actions([
                                                \Filament\Notifications\Actions\Action::make('create_schedule')
                                                    ->label('Create Schedule')
                                                    ->url(route('filament.portal.resources.exam-schedules.create'))
                                                    ->button(),
                                            ])
                                            ->sendToDatabase($officer);
                                    }
                                    
                                    Notification::make()
                                        ->warning()
                                        ->title('Verification Prevented')
                                        ->body('Final document cannot be verified because no exam schedule exists. Please create an exam schedule first.')
                                        ->persistent()
                                        ->send();
                                    
                                    return; // Don't verify the document
                                }
                            }
                            
                            // If this is the last document and applicant is TESDA/DIPLOMA, check for interview schedules
                            if ($allOthersVerified && in_array($programCategory, ['TESDA', 'DIPLOMA'])) {
                                $hasInterviewSchedules = \App\Models\InterviewSchedule::where('interview_date', '>=', now()->format('Y-m-d'))->exists();
                                
                                if (!$hasInterviewSchedules) {
                                    // Notify but allow verification to proceed
                                    $programHeads = \App\Models\User::whereHas('roles', function ($query) {
                                        $query->where('name', 'Program Head');
                                    })->get();
                                    
                                    $programName = $applicant->desired_program ?? 'Not specified';
                                    
                                    foreach ($programHeads as $programHead) {
                                        Notification::make()
                                            ->warning()
                                            ->title('Interview Schedule Needed')
                                            ->body('Document verification for ' . $applicant->display_name . ' will require an interview schedule. Please create one.')
                                            ->actions([
                                                \Filament\Notifications\Actions\Action::make('create_schedule')
                                                    ->label('Create Schedule')
                                                    ->url(route('filament.portal.resources.interview-schedules.create'))
                                                    ->button(),
                                            ])
                                            ->sendToDatabase($programHead);
                                    }
                                }
                            }
                            
                            // Proceed with verification
                            $record->status = 'Verified';
                            $record->remarks = $record->remarks . "\n[Verified by " . Auth::user()->name . " on " . now() . "]";
                            $record->save();
                            
                            // Check if all of this applicant's documents are verified
                            self::updateApplicantStatusBasedOnDocuments($record->applicant);
                            
                            Notification::make()
                                ->success()
                                ->title('Document Verified')
                                ->body('The document has been verified successfully.')
                                ->send();
                        }),
                        
                    Tables\Actions\Action::make('invalidate')
                        ->label('Mark as Invalid')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (AdmissionDocument $record) => $record->status !== 'Invalid')
                        ->form([
                            Forms\Components\Select::make('rejection_reason')
                                ->label('Rejection Reason')
                                ->options(function () {
                                    $controller = new \App\Http\Controllers\SecureDocumentUploadController();
                                    $reasons = $controller->getDocumentRejectionReasons();
                                    $options = [];
                                    
                                    foreach ($reasons as $key => $data) {
                                        $options[$data['reason']] = $data['reason'];
                                    }
                                    
                                    return $options;
                                })
                                ->required(),
                            Forms\Components\Textarea::make('details')
                                ->label('Additional Details')
                                ->rows(3),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Mark Document as Invalid')
                        ->modalDescription('Please provide a reason why this document is invalid. The applicant will be notified.')
                        ->modalSubmitActionLabel('Mark as Invalid')
                        ->action(function (AdmissionDocument $record, array $data): void {
                            $record->status = 'Invalid';
                            $record->remarks = $data['details'] . "\n[Marked invalid by " . Auth::user()->name . " on " . now() . "]";
                            $record->save();
                            
                            // Prepare the reason data
                            $controller = new \App\Http\Controllers\SecureDocumentUploadController();
                            $reasons = $controller->getDocumentRejectionReasons();
                            $subtext = '';
                            
                            foreach ($reasons as $key => $reason) {
                                if ($reason['reason'] === $data['rejection_reason']) {
                                    $subtext = $reason['subtext'];
                                    break;
                                }
                            }
                            
                            $reasonData = [
                                'reason_type' => 'invalid_document',
                                'document_name' => $record->document_type,
                                'rejection_reason' => $data['rejection_reason'],
                                'rejection_subtext' => $subtext,
                                'details' => $data['details'],
                            ];
                            
                            // Update applicant status to declined
                            $applicant = $record->applicant;
                            $oldStatus = $applicant->status;
                            $applicant->status = 'declined';
                            $applicant->save();
                            
                            // Trigger event to send notification email
                            event(new ApplicationStatusChanged($applicant, $oldStatus, 'declined', $reasonData));
                            
                            Notification::make()
                                ->danger()
                                ->title('Document Marked as Invalid')
                                ->body('The document has been marked as invalid and the applicant has been notified.')
                                ->send();
                        }),
                        
                    Tables\Actions\ViewAction::make()
                        ->color('gray')
                        ->icon('heroicon-o-eye')
                        ->label('View')
                        ->modalHeading(fn (AdmissionDocument $record) => "{$record->document_type} - {$record->applicant->display_name}")
                        ->form([
                            Forms\Components\Section::make('Admission Document Information')
                                ->schema([
                                    Forms\Components\Select::make('applicant_id')
                                        ->label('Applicant')
                                        ->relationship('applicant', 'applicant_number')
                                        ->getOptionLabelFromRecordUsing(fn (Applicant $record): string => $record->display_name)
                                        ->disabled(),
                                    Forms\Components\TextInput::make('document_type')
                                        ->disabled(),
                                    Forms\Components\TextInput::make('status')
                                        ->formatStateUsing(function ($state) {
                                            return ucfirst($state);
                                        })
                                        ->disabled(),
                                    Forms\Components\DateTimePicker::make('submitted_at')
                                        ->disabled(),
                                    Forms\Components\Textarea::make('remarks')
                                        ->disabled()
                                        ->columnSpanFull(),
                                ])->columns(2),
                            Forms\Components\View::make('filament.documents.preview')
                                ->visible(fn ($record) => $record->file_path),
                        ])
                        ->modalWidth('4xl'),
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->label('Edit'),
                    Tables\Actions\DeleteAction::make()
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->label('Delete'),
                ])
                ->icon('heroicon-s-ellipsis-vertical')
                ->color('gray')
                ->size('md')
                ->tooltip('Actions'),
            ])
            ->actionsAlignment('right')
            ->defaultSort('submitted_at', 'desc')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('verify_documents')
                        ->label('Mark as Verified')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records): void {
                            // Group records by applicant to process each applicant once
                            $recordsByApplicant = $records->groupBy('applicant_id');
                            
                            // First check if there are any CHED applicants that would need exam schedules
                            $hasApplicantsNeedingExam = false;
                            $applicantTypes = [];
                            
                            foreach ($recordsByApplicant as $applicantId => $applicantRecords) {
                                $applicant = Applicant::find($applicantId);
                                if (!$applicant) continue;
                                
                                // Get program category
                                $programCategory = $applicant->program_category;
                                
                                // Handle numeric program categories by fetching the name
                                if (is_numeric($programCategory)) {
                                    try {
                                        $categoryModel = \App\Models\ProgramCategory::find($programCategory);
                                        if ($categoryModel) {
                                            $programCategory = $categoryModel->name;
                                        }
                                    } catch (\Exception $e) {
                                        // If any error, keep the original value
                                    }
                                }
                                
                                if ($programCategory === 'CHED') {
                                    $hasApplicantsNeedingExam = true;
                                    $applicantTypes['CHED'] = true;
                                } else if (in_array($programCategory, ['TESDA', 'DIPLOMA'])) {
                                    $applicantTypes['INTERVIEW'] = true;
                                }
                            }
                            
                            // Check if we have exam schedules if there are CHED applicants
                            if ($hasApplicantsNeedingExam) {
                                $hasExamSchedules = ExamSchedule::where('exam_date', '>=', now()->format('Y-m-d'))->exists();
                                
                                if (!$hasExamSchedules) {
                                    // Cannot verify - no exam schedules exist
                                    $admissionOfficers = \App\Models\User::whereHas('roles', function ($query) {
                                        $query->where('name', 'Admission Officer');
                                    })->get();
                                    
                                    foreach ($admissionOfficers as $officer) {
                                        Notification::make()
                                            ->warning()
                                            ->title('Bulk Verification Prevented')
                                            ->body('Cannot verify documents for selected applicants - no exam schedule exists. Please create an exam schedule first before verifying documents.')
                                            ->actions([
                                                \Filament\Notifications\Actions\Action::make('create_schedule')
                                                    ->label('Create Schedule')
                                                    ->url(route('filament.portal.resources.exam-schedules.create'))
                                                    ->button(),
                                            ])
                                            ->sendToDatabase($officer);
                                    }
                                    
                                    Notification::make()
                                        ->warning()
                                        ->title('Bulk Verification Prevented')
                                        ->body('Documents cannot be verified because no exam schedule exists. Please create an exam schedule first.')
                                        ->persistent()
                                        ->send();
                                    
                                    return; // Stop processing - don't verify any documents
                                }
                            }
                            
                            // Check for interview schedules if needed
                            if (isset($applicantTypes['INTERVIEW'])) {
                                $hasInterviewSchedules = \App\Models\InterviewSchedule::where('interview_date', '>=', now()->format('Y-m-d'))->exists();
                                
                                if (!$hasInterviewSchedules) {
                                    // Don't stop the entire process, but notify about interview schedules
                                    $programHeads = \App\Models\User::whereHas('roles', function ($query) {
                                        $query->where('name', 'Program Head');
                                    })->get();
                                    
                                    foreach ($programHeads as $programHead) {
                                        Notification::make()
                                            ->warning()
                                            ->title('Interview Schedules Needed')
                                            ->body('Some verified applicants will need interview schedules. Please create interview schedules for TESDA/DIPLOMA applicants.')
                                            ->actions([
                                                \Filament\Notifications\Actions\Action::make('create_schedule')
                                                    ->label('Create Schedule')
                                                    ->url(route('filament.portal.resources.interview-schedules.create'))
                                                    ->button(),
                                            ])
                                            ->sendToDatabase($programHead);
                                    }
                                }
                            }
                            
                            // Proceed with verification for applicable records
                            $recordsByApplicant->each(function ($applicantRecords, $applicantId) {
                                // Update all records first
                                $applicantRecords->each(function ($record) {
                                    $record->update([
                                        'status' => 'Verified',
                                        'remarks' => $record->remarks . "\n[Verified in bulk by " . Auth::user()->name . " on " . now() . "]"
                                    ]);
                                });
                                
                                // Get the applicant
                                $applicant = Applicant::find($applicantId);
                                if (!$applicant) return;
                                
                                // Determine if we need to check for interview schedules
                                $programCategory = $applicant->program_category;
                                
                                // Handle numeric program categories by fetching the name
                                if (is_numeric($programCategory)) {
                                    try {
                                        // Try to get the category name from the ProgramCategory model
                                        $categoryModel = \App\Models\ProgramCategory::find($programCategory);
                                        if ($categoryModel) {
                                            $programCategory = $categoryModel->name;
                                        }
                                    } catch (\Exception $e) {
                                        // If any error, keep the original value
                                    }
                                }
                                
                                // Process based on program category
                                if ($programCategory === 'CHED') {
                                    // For CHED, we've already checked that exam schedules exist
                                    self::updateApplicantStatusBasedOnDocuments($applicant);
                                } else if (in_array($programCategory, ['TESDA', 'DIPLOMA'])) {
                                    // For TESDA/DIPLOMA, check if interview schedules are available before processing
                                    $availableSchedules = \App\Models\InterviewSchedule::where('interview_date', '>=', now()->format('Y-m-d'))
                                        ->get()
                                        ->filter(function ($schedule) {
                                            $usedCapacity = \App\Models\ApplicantInterviewSchedule::where('interview_schedule_id', $schedule->id)->count();
                                            return $usedCapacity < $schedule->capacity;
                                        });
                                    
                                    if ($availableSchedules->isEmpty()) {
                                        // Skip status update but still send notification
                                        $programHeads = \App\Models\User::whereHas('roles', function ($query) {
                                            $query->where('name', 'Program Head');
                                        })->get();
                                        
                                        $programName = $applicant->desired_program ?? 'Not specified';
                                        
                                        foreach ($programHeads as $programHead) {
                                            Notification::make()
                                                ->warning()
                                                ->title('Interview Schedule Needed')
                                                ->body('Applicant ' . $applicant->display_name . ' for ' . $programName . ' has verified documents but no interview schedule is available. Please create an interview schedule.')
                                                ->actions([
                                                    \Filament\Notifications\Actions\Action::make('create_schedule')
                                                        ->label('Create Schedule')
                                                        ->url(route('filament.portal.resources.interview-schedules.create'))
                                                        ->button(),
                                                ])
                                                ->sendToDatabase($programHead);
                                        }
                                        
                                        // Revert document status to previous (not verified)
                                        foreach ($applicantRecords as $record) {
                                            if ($record->status === 'Verified') {
                                                $record->status = 'Submitted';
                                                $record->save();
                                            }
                                        }
                                        
                                        return; // Skip status update
                                    } else {
                                        // Update applicant status for regular cases
                                        self::updateApplicantStatusBasedOnDocuments($applicant);
                                    }
                                } else {
                                    // For other categories, proceed normally
                                    self::updateApplicantStatusBasedOnDocuments($applicant);
                                }
                            });
                            
                            Notification::make()
                                ->success()
                                ->title('Documents Verified')
                                ->body('Selected documents have been marked as verified.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    /**
     * Update applicant status based on their documents verification status
     */
    public static function updateApplicantStatusBasedOnDocuments(Applicant $applicant): void
    {
        // Get all documents for this applicant
        $documents = AdmissionDocument::where('applicant_id', $applicant->id)->get();
        
        // If no documents, return
        if ($documents->isEmpty()) {
            return;
        }
        
        // Check if all documents are verified
        $allVerified = $documents->every(function ($document) {
            return $document->status === 'Verified';
        });
        
        // Check if any document is marked as Invalid
        $anyInvalid = $documents->contains(function ($document) {
            return $document->status === 'Invalid';
        });
        
        // If any document is invalid, make sure the status is declined
        if ($anyInvalid) {
            if ($applicant->status !== 'declined') {
                $oldStatus = $applicant->status;
                $applicant->status = 'declined';
                $applicant->save();
                
                // Notification already handled in the invalidate action
            }
            return;
        }
        
        // If all documents are verified, process applicant according to program category
        if ($allVerified) {
            $oldStatus = $applicant->status;
            
            // Allow changing status even from declined, but not if already approved, for entrance exam, or for interview
            if (!in_array($oldStatus, ['approved', 'for entrance exam', 'for interview'])) {
                // Get the program category directly from the applicant
                $programCategory = $applicant->program_category;
                
                // Handle numeric program categories by fetching the name
                if (is_numeric($programCategory)) {
                    try {
                        // Try to get the category name from the ProgramCategory model
                        $categoryModel = \App\Models\ProgramCategory::find($programCategory);
                        if ($categoryModel) {
                            $programCategory = $categoryModel->name;
                        }
                    } catch (\Exception $e) {
                        // If any error, keep the original value
                    }
                }
                
                // Process based on program category
                if ($programCategory === 'CHED') {
                    // Check for available exam schedules first - don't change status if no schedules exist
                    $hasExamSchedules = ExamSchedule::where('exam_date', '>=', now()->format('Y-m-d'))->exists();
                    
                    if ($hasExamSchedules) {
                        // Update status to "for entrance exam"
                        $applicant->status = 'for entrance exam';
                        $applicant->save();
                        
                        // Try to assign to an exam schedule and send notification in one step
                        self::assignToExamScheduleAndNotify($applicant, $oldStatus);
                    } else {
                        // Don't change status - notify admission officers to create a schedule first
                        $admissionOfficers = \App\Models\User::whereHas('roles', function ($query) {
                            $query->where('name', 'Admission Officer');
                        })->get();
                        
                        foreach ($admissionOfficers as $officer) {
                            Notification::make()
                                ->warning()
                                ->title('Exam Schedule Required')
                                ->body('Cannot verify documents for ' . $applicant->display_name . ' - no exam schedule exists. Please create an exam schedule first before verifying documents.')
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('create_schedule')
                                        ->label('Create Schedule')
                                        ->url(route('filament.portal.resources.exam-schedules.create'))
                                        ->button(),
                                ])
                                ->sendToDatabase($officer);
                        }
                        
                        // Also show a notification in the UI
                        Notification::make()
                            ->warning()
                            ->title('Verification Prevented')
                            ->body('Documents cannot be verified for ' . $applicant->display_name . ' because no exam schedule exists. Please create an exam schedule first.')
                            ->persistent()
                            ->send();
                            
                        // Revert document status to previous (not verified)
                        foreach ($documents as $document) {
                            if ($document->status === 'Verified') {
                                $document->status = 'Submitted';
                                $document->save();
                            }
                        }
                    }
                } else if (in_array($programCategory, ['TESDA', 'DIPLOMA'])) {
                    // For TESDA or DIPLOMA programs - first check if there's an available interview schedule
                    $availableSchedules = \App\Models\InterviewSchedule::where('interview_date', '>=', now()->format('Y-m-d'))
                        ->get()
                        ->filter(function ($schedule) {
                            // Check if schedule has available capacity
                            $usedCapacity = \App\Models\ApplicantInterviewSchedule::where('interview_schedule_id', $schedule->id)->count();
                            return $usedCapacity < $schedule->capacity;
                        });
                    
                    // Only proceed with status change if there are available schedules
                    if ($availableSchedules->isNotEmpty()) {
                        self::assignToInterviewScheduleOrNotify($applicant, $oldStatus);
                    } else {
                        // Just leave as submitted and notify program heads
                        // Find users with Program Head role
                        $programHeads = \App\Models\User::whereHas('roles', function ($query) {
                            $query->where('name', 'Program Head');
                        })->get();
                        
                        // Get program details for notification
                        $programName = $applicant->desired_program ?? 'Not specified';
                        
                        // Notify each program head
                        foreach ($programHeads as $programHead) {
                            Notification::make()
                                ->warning()
                                ->title('Interview Schedule Needed')
                                ->body('Applicant ' . $applicant->display_name . ' for ' . $programName . ' has verified documents but no interview schedule is available. Please create an interview schedule.')
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('create_schedule')
                                        ->label('Create Schedule')
                                        ->url(route('filament.portal.resources.interview-schedules.create'))
                                        ->button(),
                                ])
                                ->sendToDatabase($programHead);
                        }
                        
                        // Also show a notification in the UI
                        Notification::make()
                            ->warning()
                            ->title('No Interview Schedule Available')
                            ->body('No interview schedule is available for ' . $applicant->display_name . '. Program Heads have been notified to create one.')
                            ->persistent()
                            ->send();
                            
                        // Revert document status to previous (not verified)
                        foreach ($documents as $document) {
                            if ($document->status === 'Verified') {
                                $document->status = 'Submitted';
                                $document->save();
                            }
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Try to assign applicant to an available exam schedule
     */
    public static function tryAssignToExamSchedule(Applicant $applicant): void
    {
        // Look for available exam schedules with future dates
        $availableSchedules = ExamSchedule::where('exam_date', '>=', now()->format('Y-m-d'))
            ->get()
            ->filter(function ($schedule) {
                // Check if schedule has available capacity
                $usedCapacity = \App\Models\ApplicantExamSchedule::where('exam_schedule_id', $schedule->id)->count();
                return $usedCapacity < $schedule->capacity;
            })
            ->sortBy('exam_date');
        
        if ($availableSchedules->isNotEmpty()) {
            // Get the first available schedule
            $schedule = $availableSchedules->first();
            
            // Assign applicant to the exam schedule
            \App\Models\ApplicantExamSchedule::create([
                'applicant_id' => $applicant->id,
                'applicant_number' => $applicant->applicant_number,
                'exam_schedule_id' => $schedule->id,
                'status' => 'Scheduled'
            ]);
            
            Notification::make()
                ->success()
                ->title('Applicant Assigned to Exam Schedule')
                ->body($applicant->display_name . ' has been assigned to an exam on ' . $schedule->exam_date->format('M d, Y') . ' at ' . $schedule->start_time->format('h:i A') . '.')
                ->send();
        } else {
            // Check if there are any future exam schedules (but they're full)
            $futureSchedules = ExamSchedule::where('exam_date', '>=', now()->format('Y-m-d'))->exists();
            
            // Find users with Admission Officer role
            $admissionOfficers = \App\Models\User::whereHas('roles', function ($query) {
                $query->where('name', 'Admission Officer');
            })->get();
            
            if ($futureSchedules) {
                // Notify admission officers that all schedules are full
                foreach ($admissionOfficers as $officer) {
                    Notification::make()
                        ->warning()
                        ->title('All Exam Schedules Full')
                        ->body('All documents for ' . $applicant->display_name . ' are verified, but all exam schedules are full. Please create a new exam schedule.')
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('create_schedule')
                                ->label('Create Schedule')
                                ->url(route('filament.portal.resources.exam-schedules.create'))
                                ->button(),
                        ])
                        ->sendToDatabase($officer);
                }
                
                // Also show a notification in the UI
                Notification::make()
                    ->warning()
                    ->title('All Exam Schedules Full')
                    ->body('All documents for ' . $applicant->display_name . ' are verified, but all exam schedules are full. Admission Officers have been notified to create a new exam schedule.')
                    ->persistent()
                    ->send();
            } else {
                // Notify admission officers that no exam schedule exists
                foreach ($admissionOfficers as $officer) {
                    Notification::make()
                        ->warning()
                        ->title('Exam Schedule Needed')
                        ->body('All documents for ' . $applicant->display_name . ' are verified, but there is no active exam schedule. Please create an exam schedule to process this applicant.')
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('create_schedule')
                                ->label('Create Schedule')
                                ->url(route('filament.portal.resources.exam-schedules.create'))
                                ->button(),
                        ])
                        ->sendToDatabase($officer);
                }
                
                // Also show a notification in the UI
                Notification::make()
                    ->warning()
                    ->title('Action Required')
                    ->body('All documents for ' . $applicant->display_name . ' are verified, but there is no active exam schedule. Admission Officers have been notified to create an exam schedule.')
                    ->persistent()
                    ->send();
            }
        }
    }

    /**
     * Assign applicant to an exam schedule and send a combined notification
     */
    public static function assignToExamScheduleAndNotify(Applicant $applicant, string $oldStatus): void
    {
        // Look for available exam schedules with future dates
        $availableSchedules = ExamSchedule::where('exam_date', '>=', now()->format('Y-m-d'))
            ->get()
            ->filter(function ($schedule) {
                // Check if schedule has available capacity
                $usedCapacity = \App\Models\ApplicantExamSchedule::where('exam_schedule_id', $schedule->id)->count();
                return $usedCapacity < $schedule->capacity;
            })
            ->sortBy('exam_date');
        
        if ($availableSchedules->isNotEmpty()) {
            // Get the first available schedule
            $schedule = $availableSchedules->first();
            
            // Assign applicant to the exam schedule
            $examSchedule = \App\Models\ApplicantExamSchedule::create([
                'applicant_id' => $applicant->id,
                'applicant_number' => $applicant->applicant_number,
                'exam_schedule_id' => $schedule->id,
                'status' => 'Scheduled'
            ]);
            
            // Create data for email with exam details
            $examData = [
                'exam_date' => $schedule->exam_date->format('Y-m-d'),
                'start_time' => $schedule->start_time->format('h:i A'),
                'end_time' => $schedule->end_time->format('h:i A'),
                'room' => is_object($schedule->room) ? $schedule->room->name : ($schedule->room ?? 'TBA'),
                'venue' => $schedule->venue ?? 'Main Campus',
                'exam_schedule_id' => $schedule->id,
                'applicant_exam_schedule_id' => $examSchedule->id,
                'reason_type' => 'entrance_exam',
            ];
            
            // Trigger event to send combined email with exam details
            event(new ApplicationStatusChanged($applicant, $oldStatus, 'for entrance exam', $examData));
            
            Notification::make()
                ->success()
                ->title('Applicant Assigned to Exam Schedule')
                ->body($applicant->display_name . ' has been assigned to an exam on ' . $schedule->exam_date->format('M d, Y') . ' at ' . $schedule->start_time->format('h:i A') . '.')
                ->send();
                
            Notification::make()
                ->success()
                ->title('Applicant Ready for Entrance Exam')
                ->body('All documents for ' . $applicant->display_name . ' have been verified and they are now ready for entrance exam.')
                ->send();
        } else {
            // Check if there are any future exam schedules (but they're full)
            $futureSchedules = ExamSchedule::where('exam_date', '>=', now()->format('Y-m-d'))->exists();
            
            // Trigger the status change event without exam details
            event(new ApplicationStatusChanged($applicant, $oldStatus, 'for entrance exam'));
            
            // Find users with Admission Officer role
            $admissionOfficers = \App\Models\User::whereHas('roles', function ($query) {
                $query->where('name', 'Admission Officer');
            })->get();
            
            if ($futureSchedules) {
                // Notify admission officers that all schedules are full
                foreach ($admissionOfficers as $officer) {
                    Notification::make()
                        ->warning()
                        ->title('All Exam Schedules Full')
                        ->body('All documents for ' . $applicant->display_name . ' are verified, but all exam schedules are full. Please create a new exam schedule.')
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('create_schedule')
                                ->label('Create Schedule')
                                ->url(route('filament.portal.resources.exam-schedules.create'))
                                ->button(),
                        ])
                        ->sendToDatabase($officer);
                }
                
                // Also show a notification in the UI
                Notification::make()
                    ->warning()
                    ->title('All Exam Schedules Full')
                    ->body('All documents for ' . $applicant->display_name . ' are verified, but all exam schedules are full. Admission Officers have been notified to create a new exam schedule.')
                    ->persistent()
                    ->send();
            } else {
                // Notify admission officers that no exam schedule exists
                foreach ($admissionOfficers as $officer) {
                    Notification::make()
                        ->warning()
                        ->title('Exam Schedule Needed')
                        ->body('All documents for ' . $applicant->display_name . ' are verified, but there is no active exam schedule. Please create an exam schedule to process this applicant.')
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('create_schedule')
                                ->label('Create Schedule')
                                ->url(route('filament.portal.resources.exam-schedules.create'))
                                ->button(),
                        ])
                        ->sendToDatabase($officer);
                }
                
                // Also show a notification in the UI
                Notification::make()
                    ->warning()
                    ->title('Action Required')
                    ->body('All documents for ' . $applicant->display_name . ' are verified, but there is no active exam schedule. Admission Officers have been notified to create an exam schedule.')
                    ->persistent()
                    ->send();
            }
        }
    }

    /**
     * Assign TESDA/DIPLOMA applicant to an interview schedule if available, or notify program heads
     */
    public static function assignToInterviewScheduleOrNotify(Applicant $applicant, string $oldStatus): void
    {
        // Look for available interview schedules with future dates
        $availableSchedules = \App\Models\InterviewSchedule::where('interview_date', '>=', now()->format('Y-m-d'))
            ->get()
            ->filter(function ($schedule) {
                // Check if schedule has available capacity
                $usedCapacity = \App\Models\ApplicantInterviewSchedule::where('interview_schedule_id', $schedule->id)->count();
                return $usedCapacity < $schedule->capacity;
            })
            ->sortBy('interview_date');
        
        if ($availableSchedules->isNotEmpty()) {
            // Get the first available schedule
            $schedule = $availableSchedules->first();
            
            // Assign applicant to the interview schedule
            $interviewSchedule = \App\Models\ApplicantInterviewSchedule::create([
                'applicant_id' => $applicant->id,
                'interview_schedule_id' => $schedule->id,
                'status' => 'Scheduled'
            ]);
            
            // Update applicant status
            $applicant->status = 'for interview';
            $applicant->save();
            
            // Create data for email with interview details
            $interviewData = [
                'interview_date' => $schedule->interview_date->format('Y-m-d'),
                'start_time' => $schedule->start_time->format('h:i A'),
                'end_time' => $schedule->end_time->format('h:i A'),
                'venue' => $schedule->venue ?? 'Main Campus',
                'reason_type' => 'interview_schedule',
                'interview_schedule_id' => $schedule->id,
                'applicant_interview_schedule_id' => $interviewSchedule->id,
            ];
            
            // Trigger event to send notification email
            event(new ApplicationStatusChanged($applicant, $oldStatus, 'for interview', $interviewData));
            
            Notification::make()
                ->success()
                ->title('Applicant Assigned to Interview Schedule')
                ->body($applicant->display_name . ' has been assigned to an interview on ' . $schedule->interview_date->format('M d, Y') . ' at ' . $schedule->start_time->format('h:i A') . '.')
                ->send();
                
            Notification::make()
                ->success()
                ->title('Applicant Ready for Interview')
                ->body('All documents for ' . $applicant->display_name . ' have been verified and they are now ready for interview.')
                ->send();
        } else {
            // No interview schedules available, notify program heads
            
            // Keep the applicant status as "submitted"
            // Don't change the status since there's no interview schedule available
            
            // Find users with Program Head role
            $programHeads = \App\Models\User::whereHas('roles', function ($query) {
                $query->where('name', 'Program Head');
            })->get();
            
            // Get program details for notification
            $programName = $applicant->desired_program ?? 'Not specified';
            
            // Notify each program head
            foreach ($programHeads as $programHead) {
                Notification::make()
                    ->warning()
                    ->title('Interview Schedule Needed')
                    ->body('Applicant ' . $applicant->display_name . ' for ' . $programName . ' has verified documents but no interview schedule is available. Please create an interview schedule.')
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('create_schedule')
                            ->label('Create Schedule')
                            ->url(route('filament.portal.resources.interview-schedules.create'))
                            ->button(),
                    ])
                    ->sendToDatabase($programHead);
            }
            
            // Also show a notification in the UI
            Notification::make()
                ->warning()
                ->title('No Interview Schedule Available')
                ->body('No interview schedule is available for ' . $applicant->display_name . '. Program Heads have been notified to create one.')
                ->persistent()
                ->send();
        }
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
            'index' => Pages\ListAdmissionDocuments::route('/'),
            'create' => Pages\CreateAdmissionDocument::route('/create'),
            'edit' => Pages\EditAdmissionDocument::route('/{record}/edit'),
        ];
    }
}
