<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    // Disable the default "Saved" notification
    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }

    protected function afterSave(): void
    {
        // Get form data
        $data = $this->form->getState();
        
        // If email field exists and the employee has a related user
        if (isset($data['email']) && $this->record->user) {
            try {
                $user = $this->record->user;
                $user->email = $data['email'];
                $user->save();
                
                Notification::make()
                    ->title('Success')
                    ->body('Employee and user information updated successfully.')
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                // Log the error
                \Log::error('Error updating user: ' . $e->getMessage());
                
                Notification::make()
                    ->title('Warning')
                    ->body('Employee updated but error updating user: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }
        }
    }
} 