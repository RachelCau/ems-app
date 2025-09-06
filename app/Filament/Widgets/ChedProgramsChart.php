<?php

namespace App\Filament\Widgets;

use App\Models\Applicant;
use App\Models\Program;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ChedProgramsChart extends ChartWidget
{
    protected static ?string $heading = 'CHED PROGRAMS OVERVIEW';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    protected static ?int $sort = 3;

    // Modern color theme
    protected static string $color = 'success';

    protected static ?string $pollingInterval = '120s';

    // Eager load for better UX
    protected static bool $isLazy = false;

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
        return Cache::remember('ched_chart_data', 120, function () {
            // Get the CHED program category ID
            $chedCategoryId = \App\Models\ProgramCategory::where('name', 'CHED')
                ->orWhere('name', 'like', '%CHED%')
                ->first()?->id;
            
            if (!$chedCategoryId) {
                // Fallback to hard-coded values if category not found
                return $this->getFallbackData();
            }
            
            // Get all CHED programs
            $programs = \App\Models\Program::where('program_category_id', $chedCategoryId)
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
                'rgba(201, 203, 207, 0.2)',
                'rgba(100, 200, 150, 0.2)',
                'rgba(220, 100, 150, 0.2)',
                'rgba(50, 120, 200, 0.2)'
            ];
            
            $borderColor = [
                'rgb(255, 99, 132)',
                'rgb(255, 159, 64)',
                'rgb(255, 205, 86)',
                'rgb(75, 192, 192)',
                'rgb(54, 162, 235)',
                'rgb(153, 102, 255)',
                'rgb(201, 203, 207)',
                'rgb(100, 200, 150)',
                'rgb(220, 100, 150)',
                'rgb(50, 120, 200)'
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
                        'label' => 'Total Students in CHED',
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
        $labels = ['ACT', 'BSAIS', 'BSCA', 'BSIS', 'BSOM', 'BTVTED-FSM', 'BTVTED-IAET', 'BTVTED-IAWT'];
        $data = [];

        foreach ($labels as $label) {
            $data[] = DB::table('applicants')->where('desired_program', $label)->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Students in CHED',
                    'data' => $data,
                    'backgroundColor' =>  [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(255, 205, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(201, 203, 207, 0.2)',
                        'rgba(100, 200, 150, 0.2)',
                        'rgba(220, 100, 150, 0.2)',
                        'rgba(50, 120, 200, 0.2)'
                    ],
                    'borderColor' =>  [
                        'rgb(255, 99, 132)',
                        'rgb(255, 159, 64)',
                        'rgb(255, 205, 86)',
                        'rgb(75, 192, 192)',
                        'rgb(54, 162, 235)',
                        'rgb(153, 102, 255)',
                        'rgb(201, 203, 207)',
                        'rgb(100, 200, 150)',
                        'rgb(220, 100, 150)',
                        'rgb(50, 120, 200)'
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
