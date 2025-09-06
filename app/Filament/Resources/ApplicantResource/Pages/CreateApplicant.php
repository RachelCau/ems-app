<?php

namespace App\Filament\Resources\ApplicantResource\Pages;

use App\Filament\Resources\ApplicantResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Events\ApplicationStatusChanged;
use Filament\Notifications\Notification;

class CreateApplicant extends CreateRecord
{
    protected static string $resource = ApplicantResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function afterCreate(): void
    {
        // Trigger application status changed event for the newly created applicant
        $applicant = $this->record;
        
        if ($applicant) {
            // Trigger the event with empty old status since it's a new record
            event(new ApplicationStatusChanged($applicant, '', $applicant->status));
            
            // Show notification that email will be sent
            Notification::make()
                ->success()
                ->title('Applicant Created')
                ->body('The applicant has been created and will be notified via email.')
                ->send();
        }
    }
} 