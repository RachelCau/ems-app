<?php

namespace App\Filament\Exports;

use App\Models\Course;
use App\Models\AcademicYear;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CourseExporter extends Exporter
{
    protected static ?string $model = Course::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('code')
                ->label('Code'),
                
            ExportColumn::make('name')
                ->label('Name'),
                
            ExportColumn::make('unit')
                ->label('Unit'),
                
            ExportColumn::make('type')
                ->label('Type'),
                
            ExportColumn::make('level')
                ->label('Level'),
                
            ExportColumn::make('semester')
                ->label('Semester'),
                
            ExportColumn::make('academic_year.name')
                ->label('Academic Year'),
                
            ExportColumn::make('program.code')
                ->label('Main Program'),
                
            ExportColumn::make('prerequisiteCourses.code')
                ->label('Pre-Requisites')
                ->formatStateUsing(function ($state) {
                    if (is_array($state)) {
                        return implode(', ', $state);
                    }
                    return $state;
                }),
                
            ExportColumn::make('programs.code')
                ->label('Programs')
                ->formatStateUsing(function ($state) {
                    if (is_array($state)) {
                        return implode(', ', $state);
                    }
                    return $state;
                }),
        ];
    }

    public function getFileName(Export $export): string
    {
        $academicYear = AcademicYear::where('is_active', true)->first()?->name ?? date('Y') . '-' . (date('Y') + 1);
        
        return $academicYear . '_courselist';
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your course export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
