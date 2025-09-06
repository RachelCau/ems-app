<?php

namespace App\Filament\Resources\ExamQuestionResource\Pages;

use App\Filament\Resources\ExamQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditExamQuestion extends EditRecord
{
    protected static string $resource = ExamQuestionResource::class;

    protected function getHeaderActions(): array
    {
        // Check if feature is enabled
        $isFeatureEnabled = class_exists('\App\Facades\Feature') && \App\Facades\Feature::isEnabled('exam_questions');
        
        return [
            Actions\DeleteAction::make()
                ->disabled(!$isFeatureEnabled)
                ->tooltip($isFeatureEnabled ? null : 'This feature is coming in a future update'),
        ];
    }
    
    public function mount($record): void
    {
        parent::mount($record);
        
        // Check if the feature is enabled
        if (class_exists('\App\Facades\Feature') && !\App\Facades\Feature::isEnabled('exam_questions')) {
            // Show a notification that this feature is coming soon
            Notification::make()
                ->title('Coming Soon')
                ->body('The Exam Questions feature is scheduled for a future update.')
                ->info()
                ->persistent()
                ->send();
                
            // Redirect back to list page
            $this->redirect(static::getResource()::getUrl('index'));
        }
    }
    
    // Handle form submission
    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        // Check if the feature is enabled
        if (class_exists('\App\Facades\Feature') && !\App\Facades\Feature::isEnabled('exam_questions')) {
            Notification::make()
                ->title('Feature Unavailable')
                ->body('The Exam Questions feature is not available yet.')
                ->warning()
                ->send();
                
            return; // Don't actually call the parent save method
        }
        
        // Feature is enabled, proceed with normal save
        parent::save($shouldRedirect, $shouldSendSavedNotification);
    }
}
