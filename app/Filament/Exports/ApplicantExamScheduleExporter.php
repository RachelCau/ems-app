<?php

namespace App\Filament\Exports;

use App\Models\ApplicantExamSchedule;
use App\Models\AcademicYear;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class ApplicantExamScheduleExporter extends Exporter
{
    protected static ?string $model = ApplicantExamSchedule::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('applicant.applicant_number')
                ->label('Applicant Number'),
                
            ExportColumn::make('score')
                ->label('Score'),
        ];
    }

    public function getFileName(Export $export): string
    {
        $academicYear = AcademicYear::where('is_active', true)->first()?->name ?? date('Y') . '-' . (date('Y') + 1);
        
        return $academicYear . '_applicantlist';
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your exam scores export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
