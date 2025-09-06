<?php

namespace App\Filament\Resources\AdmissionDocumentResource\Pages;

use App\Filament\Resources\AdmissionDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdmissionDocuments extends ListRecords
{
    protected static string $resource = AdmissionDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
           
        ];
    }
}
