<?php

namespace App\Filament\Resources\AdmissionDocumentResource\Pages;

use App\Filament\Resources\AdmissionDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdmissionDocument extends EditRecord
{
    protected static string $resource = AdmissionDocumentResource::class;

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
}
