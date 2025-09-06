<?php

namespace App\Filament\Widgets;

use App\Models\Applicant;
use App\Models\Program;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class TesdaProgramsChart extends ChartWidget
{
    protected static ?string $heading = 'TESDA Program Enrollment';

    protected static string $color = 'info';

    // Eager load for better UX
    protected static bool $isLazy = false;

    protected static ?int $sort = 3;

    protected static ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 'full';



    public static function canView(): bool
    {
        // Hide for Academic Officer
        $user = auth()->user();
        if ($user && $user->roles->contains('name', 'Academic Officer')) {
            return false;
        }
        
        // Explicitly show for Registrar
        if ($user && $user->roles->contains('name', 'Registrar')) {
            return true;
        }
        
        // MIS and Admin can view all charts
        if (Gate::allows('view admissions') && (Gate::allows('view applicants') || Gate::allows('view students'))) {
            return true;
        }

        // Program Head can view program charts
        if (Gate::allows('view programs')) {
            return true;
        }

        return false;
    }

    protected function getData(): array
    {
        return Cache::remember('tesda_chart_data', 60 * 60, function () {
            // Get the TESDA program category ID
            $tesdaCategoryId = \App\Models\ProgramCategory::where('name', 'TESDA')
                ->orWhere('name', 'like', '%TESDA%')
                ->first()?->id;
            
            if (!$tesdaCategoryId) {
                // Fallback to hard-coded values if category not found
                return $this->getFallbackData();
            }
            
            // Get all TESDA programs
            $programs = \App\Models\Program::where('program_category_id', $tesdaCategoryId)
                ->select('id', 'code', 'name')
                ->orderBy('code')
                ->get();
            
            if ($programs->isEmpty()) {
                // Fallback to hard-coded values if no programs found
                return $this->getFallbackData();
            }
            
            // Extract codes for labels
            $labels = $programs->pluck('code')->toArray();
            $data = [];
            
            // Get program IDs
            $programIds = $programs->pluck('id')->toArray();
            
            // Get applicant counts for each program with single query
            $counts = Applicant::select('program_id', DB::raw('count(*) as count'))
                ->whereIn('program_id', $programIds)
                ->groupBy('program_id')
                ->pluck('count', 'program_id')
                ->toArray();
            
            // Build the data array in the same order as labels
            foreach ($programs as $program) {
                $data[] = $counts[$program->id] ?? 0;
            }
            
            // Colors for chart (reuse existing color arrays)
            $backgroundColor = [
                'rgba(255, 99, 132, 0.2)',
                'rgba(255, 159, 64, 0.2)',
                'rgba(255, 205, 86, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(153, 102, 255, 0.2)',
                'rgba(0, 206, 109, 0.2)'
            ];
            
            $borderColor = [
                'rgb(255, 99, 132)',
                'rgb(255, 159, 64)',
                'rgb(255, 205, 86)',
                'rgb(75, 192, 192)',
                'rgb(54, 162, 235)',
                'rgb(153, 102, 255)',
                'rgb(0, 206, 109)'
            ];
            
            // If we have more programs than colors, repeat the colors
            while (count($backgroundColor) < count($labels)) {
                $backgroundColor = array_merge($backgroundColor, $backgroundColor);
                $borderColor = array_merge($borderColor, $borderColor);
            }
            
            // Trim to the needed count
            $backgroundColor = array_slice($backgroundColor, 0, count($labels));
            $borderColor = array_slice($borderColor, 0, count($labels));

            return [
                'datasets' => [
                    [
                        'label' => 'Total Students in TESDA',
                        'data' => $data,
                        'backgroundColor' => $backgroundColor,
                        'borderColor' => $borderColor,
                        'borderWidth' => 1,
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    /**
     * Fallback data when categories or programs are not found
     */
    private function getFallbackData(): array
    {
        $labels = ['BKP', 'CK', 'CCS', 'EIM', 'HB', 'HKP', 'SMAW'];
        $data = [];

        foreach ($labels as $label) {
            $data[] = DB::table('applicants')->where('desired_program', $label)->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Students in TESDA',
                    'data' => $data,
                    'backgroundColor' =>  [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(255, 205, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(0, 206, 109, 0.2)'
                    ],
                    'borderColor' =>  [
                        'rgb(255, 99, 132)',
                        'rgb(255, 159, 64)',
                        'rgb(255, 205, 86)',
                        'rgb(75, 192, 192)',
                        'rgb(54, 162, 235)',
                        'rgb(153, 102, 255)',
                        'rgb(0, 206, 109)'
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * Get program counts from the database with optimized query
     */
    private function getProgramCounts(array $programCodes, int $categoryId): array
    {
        // Optimize by getting all programs at once
        $programs = Program::where('program_category_id', $categoryId)
            ->whereIn('code', $programCodes)
            ->select('id', 'code')
            ->get()
            ->keyBy('code');

        // Get counts with a single query if possible
        $programIds = $programs->pluck('id')->toArray();

        $counts = Applicant::select('program_id', DB::raw('count(*) as count'))
            ->whereIn('program_id', $programIds)
            ->where('status', 'approved')
            ->groupBy('program_id')
            ->pluck('count', 'program_id')
            ->toArray();

        // Map results back to program codes
        $result = [];
        foreach ($programCodes as $code) {
            $programId = $programs->get($code)->id ?? null;
            $result[$code] = $programId ? ($counts[$programId] ?? 0) : 0;
        }

        return $result;
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
