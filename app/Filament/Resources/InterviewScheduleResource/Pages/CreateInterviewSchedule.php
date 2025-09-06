<?php

namespace App\Filament\Resources\InterviewScheduleResource\Pages;

use App\Filament\Resources\InterviewScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateInterviewSchedule extends CreateRecord
{
    protected static string $resource = InterviewScheduleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Interview Schedule Created')
            ->body('The interview schedule has been created successfully.');
    }

    protected function afterCreate(): void
    {
        // Auto-assign queued applicants to this new schedule
        InterviewScheduleResource::assignQueuedApplicants($this->record);
    }
}
