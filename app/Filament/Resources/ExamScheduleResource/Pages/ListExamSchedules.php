<?php

namespace App\Filament\Resources\ExamScheduleResource\Pages;

use App\Filament\Resources\ExamScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExamSchedules extends ListRecords
{
    protected static string $resource = ExamScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
