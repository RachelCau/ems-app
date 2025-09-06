<?php

namespace App\Filament\Imports;

use App\Models\ApplicantExamSchedule;
use App\Models\Applicant;
use App\Models\ProgramCategory;
use App\Events\ApplicationStatusChanged;
use Filament\Notifications\Notification;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;

class ApplicantExamScheduleScoreImporter extends Importer
{
    protected static ?string $model = ApplicantExamSchedule::class;

    // We don't save directly to the model fields
    // Instead we use the data to find the record and then update it
    protected static bool $skipRecordCreation = true;

    public static function getColumns(): array
    {
        return [
            // This is only used for lookup, not for saving
            ImportColumn::make('applicant_number')
                ->requiredMapping()
                ->rules(['required', 'string', 'exists:applicants,applicant_number'])
                ->label('Applicant Number'),
                
            ImportColumn::make('score')
                ->numeric(2)
                ->rules(['required', 'numeric', 'min:0'])
                ->requiredMapping()
                ->label('Score'),
        ];
    }

    public function resolveRecord(): ?ApplicantExamSchedule
    {
        // Find the applicant by applicant_number
        $applicant = Applicant::where('applicant_number', $this->data['applicant_number'])->first();
        
        if (!$applicant) {
            return null;
        }
        
        // Find the most recent exam schedule for this applicant
        // or the one specified in options if provided
        $query = ApplicantExamSchedule::where('applicant_id', $applicant->id);
        
        if (isset($this->options['exam_schedule_id']) && !empty($this->options['exam_schedule_id'])) {
            $query->where('exam_schedule_id', $this->options['exam_schedule_id']);
        }
        
        return $query->orderBy('created_at', 'desc')->first();
    }

    public function getValidationAttributes(): array
    {
        return [
            'data.applicant_number' => 'Applicant Number',
            'data.score' => 'Score',
        ];
    }

    protected function mutateBeforeCreate(array $data): array
    {
        // Remove applicant_number as it's not a field in the database table
        unset($data['applicant_number']);
        
        // Keep score in the data
        return $data;
    }

    protected function beforeSave(): void
    {
        // Make sure we have a record
        if (!$this->record) {
            return;
        }

        // Get the score from the imported data
        $score = (float) $this->data['score'];
        
        // Explicitly update the score in the database
        $this->record->score = $score;
        
        // Set status to 'Attended' when a score is added
        $this->record->status = 'Attended';
        
        // Always use the value from the import dialog
        $this->record->total_items = $this->options['total_items'] ?? 75;
        
        // Calculate passing score based on number of items (75% of total)
        $passingScore = round($this->record->total_items * 0.75, 2);
        
        // Set the remarks based on the score with explicit string values
        if ($score >= $passingScore) {
            $this->record->remarks = 'passed';
            
            // Save first to ensure the record is updated before processing
            $this->record->save();
            
            // Use the RelationManager's method to handle passed exams if it exists
            if (class_exists('\\App\\Filament\\Resources\\ExamScheduleResource\\RelationManagers\\ApplicantExamSchedulesRelationManager')) {
                $manager = new \App\Filament\Resources\ExamScheduleResource\RelationManagers\ApplicantExamSchedulesRelationManager('applicantExamSchedules');
                $manager->processPassedExam($this->record);
            } else {
                // Fall back to the old method just in case
                $this->updateApplicantStatus($this->record, 'passed');
            }
        } else {
            $this->record->remarks = 'failed';
            $this->record->save();
            
            // Use the RelationManager's method to handle failed exams if it exists
            if (class_exists('\\App\\Filament\\Resources\\ExamScheduleResource\\RelationManagers\\ApplicantExamSchedulesRelationManager')) {
                $manager = new \App\Filament\Resources\ExamScheduleResource\RelationManagers\ApplicantExamSchedulesRelationManager('applicantExamSchedules');
                $manager->processFailedExam($this->record);
            }
        }
    }

    /**
     * Update the applicant status based on exam results
     */
    protected function updateApplicantStatus(ApplicantExamSchedule $record, string $examResult): void
    {
        // Get the applicant
        $applicant = $record->applicant;
        if (!$applicant) {
            return;
        }
        
        // Only process CHED applicants who passed
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
        
        if ($programCategory === 'CHED' && $examResult === 'passed') {
            $oldStatus = $applicant->status;
            
            // Check if there's an available interview schedule before updating
            // Reuse the method from AdmissionDocumentResource
            if (class_exists('\App\Filament\Resources\AdmissionDocumentResource')) {
                \App\Filament\Resources\AdmissionDocumentResource::assignToInterviewScheduleOrNotify($applicant, $oldStatus);
            } else {
                // Fallback if the class doesn't exist
                $applicant->status = 'for interview';
                $applicant->save();
                
                // Trigger event to send notification email
                event(new \App\Events\ApplicationStatusChanged($applicant, $oldStatus, 'for interview'));
                
                // Show notification
                Notification::make()
                    ->success()
                    ->title('Applicant Ready for Interview')
                    ->body('Applicant ' . $applicant->display_name . ' has passed the entrance exam and is now ready for interview.')
                    ->send();
            }
        }
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            TextInput::make('total_items')
                ->label('Total Number of Items')
                ->numeric()
                ->integer()
                ->default(75)
                ->minValue(1)
                ->required()
                ->helperText('The passing score will be calculated as 75% of this value'),
        ];
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your scores import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
