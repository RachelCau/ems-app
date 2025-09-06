<?php

namespace App\Filament\Exports;

use App\Models\Student;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Exports\Models\Export;

class StudentExport extends Exporter
{
    protected static ?string $model = Student::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('student_number')
                ->label('Student Number'),
            ExportColumn::make('first_name')
                ->label('First Name'),
            ExportColumn::make('middle_name')
                ->label('Middle Name'),
            ExportColumn::make('last_name')
                ->label('Last Name'),
            ExportColumn::make('suffix')
                ->label('Suffix'),
            ExportColumn::make('sex')
                ->label('Sex'),
            ExportColumn::make('mobile_number')
                ->label('Mobile Number'),
            ExportColumn::make('email')
                ->label('Email'),
            ExportColumn::make('campus.name')
                ->label('Campus'),
            ExportColumn::make('student_status')
                ->label('Status'),
            ExportColumn::make('created_at')
                ->label('Registration Date'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return 'Your student export has completed and is ready to download.';
    }
    
    public function getFileName(Export $export): string
    {
        $currentYear = date('Y');
        $academicYear = (date('n') >= 6) 
            ? $currentYear . '-' . ($currentYear + 1)
            : ($currentYear - 1) . '-' . $currentYear;
            
        return $academicYear . '-studentlist';
    }
} 