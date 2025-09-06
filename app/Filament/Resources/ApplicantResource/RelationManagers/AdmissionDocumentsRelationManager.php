<?php

namespace App\Filament\Resources\ApplicantResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use App\Filament\Resources\AdmissionDocumentResource;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use App\Events\ApplicationStatusChanged;
use App\Models\AdmissionDocument;
use App\Models\Applicant;

class AdmissionDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'admissionDocuments';

    protected static ?string $recordTitleAttribute = 'document_type';

    protected static ?string $title = 'Applicant Documents';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('document_type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('remarks')
                    ->maxLength(65535),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('document_type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
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
                            $record->status = 'Verified';
                            $record->remarks = $record->remarks . "\n[Verified by " . Auth::user()->name . " on " . now() . "]";
                            $record->save();
                            
                            // Get applicant and determine if we can process status change
                            $applicant = $record->applicant;
                            $canUpdateStatus = true;
                            
                            // For TESDA/DIPLOMA applicants, check if interview schedules are available
                            $programCategory = $applicant->program_category;
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
                            
                            if (in_array($programCategory, ['TESDA', 'DIPLOMA'])) {
                                // Check if interview schedules are available
                                $availableSchedules = \App\Models\InterviewSchedule::where('interview_date', '>=', now()->format('Y-m-d'))
                                    ->get()
                                    ->filter(function ($schedule) {
                                        $usedCapacity = \App\Models\ApplicantInterviewSchedule::where('interview_schedule_id', $schedule->id)->count();
                                        return $usedCapacity < $schedule->capacity;
                                    });
                                
                                if ($availableSchedules->isEmpty()) {
                                    $canUpdateStatus = false;
                                }
                            }
                            
                            // Only update applicant status if we have necessary resources
                            if ($canUpdateStatus) {
                                // Check if all of this applicant's documents are verified
                                AdmissionDocumentResource::updateApplicantStatusBasedOnDocuments($applicant);
                            } else {
                                // Just verify the document but don't change applicant status
                                // All documents will be verified in database but applicant remains "submitted"
                                $allVerified = $applicant->admissionDocuments->every(function ($doc) use ($record) {
                                    return $doc->status === 'Verified' || ($doc->id === $record->id);
                                });
                                
                                if ($allVerified) {
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
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('verify_documents')
                        ->label('Mark as Verified')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records): void {
                            $records->each(function ($record): void {
                                $record->status = 'Verified';
                                $record->remarks = $record->remarks . "\n[Verified in bulk by " . Auth::user()->name . " on " . now() . "]";
                                $record->save();
                                
                                // Get applicant and determine if we can process status change
                                $applicant = $record->applicant;
                                $canUpdateStatus = true;
                                
                                // For TESDA/DIPLOMA applicants, check if interview schedules are available
                                $programCategory = $applicant->program_category;
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
                                
                                if (in_array($programCategory, ['TESDA', 'DIPLOMA'])) {
                                    // Check if interview schedules are available
                                    $availableSchedules = \App\Models\InterviewSchedule::where('interview_date', '>=', now()->format('Y-m-d'))
                                        ->get()
                                        ->filter(function ($schedule) {
                                            $usedCapacity = \App\Models\ApplicantInterviewSchedule::where('interview_schedule_id', $schedule->id)->count();
                                            return $usedCapacity < $schedule->capacity;
                                        });
                                    
                                    if ($availableSchedules->isEmpty()) {
                                        $canUpdateStatus = false;
                                    }
                                }
                                
                                // Only update applicant status if we have necessary resources
                                if ($canUpdateStatus) {
                                    // Update applicant status for each document's applicant
                                    AdmissionDocumentResource::updateApplicantStatusBasedOnDocuments($applicant);
                                }
                            });
                            
                            // Show success notification
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

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('reprocessStatus')
                ->label('Reprocess Applicant Status')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function () {
                    $applicant = $this->getOwnerRecord();
                    
                    // Check if it's a TESDA/DIPLOMA applicant
                    $canUpdateStatus = true;
                    $programCategory = $applicant->program_category;
                    
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
                    
                    if (in_array($programCategory, ['TESDA', 'DIPLOMA'])) {
                        // Check if interview schedules are available
                        $availableSchedules = \App\Models\InterviewSchedule::where('interview_date', '>=', now()->format('Y-m-d'))
                            ->get()
                            ->filter(function ($schedule) {
                                $usedCapacity = \App\Models\ApplicantInterviewSchedule::where('interview_schedule_id', $schedule->id)->count();
                                return $usedCapacity < $schedule->capacity;
                            });
                        
                        if ($availableSchedules->isEmpty()) {
                            $canUpdateStatus = false;
                            
                            // Show notification that we can't update status
                            Notification::make()
                                ->warning()
                                ->title('Status Not Changed')
                                ->body('This is a ' . $programCategory . ' applicant and no interview schedules are available. Status will remain unchanged until a schedule is created.')
                                ->send();
                            
                            return;
                        }
                    }
                    
                    // Reprocess the status based on documents if we can update
                    if ($canUpdateStatus) {
                        AdmissionDocumentResource::updateApplicantStatusBasedOnDocuments($applicant);
                        
                        // Refresh applicant data
                        $applicant->refresh();
                        
                        Notification::make()
                            ->success()
                            ->title('Status Reprocessed')
                            ->body("Applicant status has been updated to: {$applicant->status}")
                            ->send();
                    }
                }),
        ];
    }
}
