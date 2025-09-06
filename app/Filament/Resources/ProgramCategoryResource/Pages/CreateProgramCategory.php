<?php

namespace App\Filament\Resources\ProgramCategoryResource\Pages;

use App\Filament\Resources\ProgramCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProgramCategory extends CreateRecord
{
    protected static string $resource = ProgramCategoryResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 