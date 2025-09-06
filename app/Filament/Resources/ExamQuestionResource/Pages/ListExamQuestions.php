<?php

namespace App\Filament\Resources\ExamQuestionResource\Pages;

use App\Filament\Resources\ExamQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListExamQuestions extends ListRecords
{
    protected static string $resource = ExamQuestionResource::class;

    protected function getHeaderActions(): array
    {
        // Check if feature is enabled
        $isFeatureEnabled = class_exists('\App\Facades\Feature') && \App\Facades\Feature::isEnabled('exam_questions');
        
        return [
            Actions\CreateAction::make()
                ->label('New exam question')
                ->disabled(!$isFeatureEnabled)
                ->tooltip($isFeatureEnabled ? null : 'This feature is coming in a future update'),
        ];
    }
    
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
        }
            
        parent::mount();
    }
}
