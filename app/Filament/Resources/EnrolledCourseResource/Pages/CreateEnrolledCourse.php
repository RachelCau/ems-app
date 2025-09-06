<?php

namespace App\Filament\Resources\EnrolledCourseResource\Pages;

use App\Filament\Resources\EnrolledCourseResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEnrolledCourse extends CreateRecord
{
    protected static string $resource = EnrolledCourseResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 