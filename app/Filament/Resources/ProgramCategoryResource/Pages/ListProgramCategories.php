<?php

namespace App\Filament\Resources\ProgramCategoryResource\Pages;

use App\Filament\Resources\ProgramCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProgramCategories extends ListRecords
{
    protected static string $resource = ProgramCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
} 