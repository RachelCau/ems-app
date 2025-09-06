<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CourseCurriculum;
use Illuminate\Support\Facades\DB;

class PopulateCurriculumYearLevel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-curriculum-year-level';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate the year_level and semester columns in the course_curricula table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Populating year_level and semester in course_curricula table...');

        // Get all course curricula
        $curricula = CourseCurriculum::all();
        $this->info("Found {$curricula->count()} curriculum records.");

        $updated = 0;

        foreach ($curricula as $curriculum) {
            // Get the most common year_level and semester from curriculum_course table
            $yearLevelData = DB::table('curriculum_course')
                ->where('curriculum_id', $curriculum->id)
                ->select('year_level', DB::raw('count(*) as count'))
                ->groupBy('year_level')
                ->orderBy('count', 'desc')
                ->first();

            $semesterData = DB::table('curriculum_course')
                ->where('curriculum_id', $curriculum->id)
                ->select('semester', DB::raw('count(*) as count'))
                ->groupBy('semester')
                ->orderBy('count', 'desc')
                ->first();

            if ($yearLevelData) {
                $curriculum->year_level = $yearLevelData->year_level;
                
                if ($semesterData) {
                    $curriculum->semester = $semesterData->semester;
                }
                
                $curriculum->save();
                $updated++;
                
                $this->info("Updated curriculum {$curriculum->id} with year_level: {$curriculum->year_level}, semester: {$curriculum->semester}");
            } else {
                $this->warn("No curriculum_course data found for curriculum {$curriculum->id}");
            }
        }

        $this->info("Updated {$updated} curriculum records out of {$curricula->count()}.");
        
        return Command::SUCCESS;
    }
}
