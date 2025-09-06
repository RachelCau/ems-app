<?php

namespace App\Filament\Resources\InterviewScheduleResource\Pages;

use App\Filament\Resources\InterviewScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditInterviewSchedule extends EditRecord
{
    protected static string $resource = InterviewScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Interview Schedule Updated')
            ->body('The interview schedule has been updated successfully.');
    }
}
