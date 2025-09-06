<?php

namespace App\Filament\Resources\ProgramCategoryResource\Pages;

use App\Filament\Resources\ProgramCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProgramCategory extends ViewRecord
{
    protected static string $resource = ProgramCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
} 