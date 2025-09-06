<?php

namespace App\Filament\Resources\EnrolledCourseResource\Pages;

use App\Filament\Resources\EnrolledCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEnrolledCourse extends EditRecord
{
    protected static string $resource = EnrolledCourseResource::class;

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