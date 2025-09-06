<?php

namespace App\Filament\Resources\ExamQuestionResource\Pages;

use App\Filament\Resources\ExamQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateExamQuestion extends CreateRecord
{
    protected static string $resource = ExamQuestionResource::class;
    
    public function mount(): void
    {
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
        } else {
            // Feature is enabled, proceed normally
            parent::mount();
        }
    }
    
    // Disable form from being submitted
    public function create(bool $another = false): void
    {
        // Check if the feature is enabled
        if (class_exists('\App\Facades\Feature') && !\App\Facades\Feature::isEnabled('exam_questions')) {
            Notification::make()
                ->title('Feature Unavailable')
                ->body('The Exam Questions feature is not available yet.')
                ->warning()
                ->send();
                
            return;
        }
        
        // Feature is enabled, proceed with normal creation
        parent::create($another);
    }
}
