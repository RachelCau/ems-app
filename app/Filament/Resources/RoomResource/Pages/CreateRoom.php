<?php

namespace App\Filament\Resources\RoomResource\Pages;

use App\Filament\Resources\RoomResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;

class CreateRoom extends CreateRecord
{
    protected static string $resource = RoomResource::class;
    
    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Create')
                ->color('primary')
                ->extraAttributes(['class' => 'filament-button-create']),
            
            $this->getCreateAnotherFormAction()
                ->label('Create & create another')
                ->color('gray'),
            
            $this->getCancelFormAction()
                ->label('Cancel')
                ->color('gray'),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Room created';
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['is_active'] = true;
        return $data;
    }

    // Make form actions appear left-aligned
    public function getBreadcrumbs(): array
    {
        return [];
    }
}
