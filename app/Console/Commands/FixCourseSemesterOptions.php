<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Course;
use Illuminate\Support\Facades\DB;

class FixCourseSemesterOptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-course-semester-options';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix course semester options to ensure SUMMER is properly supported';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing course semester options to support SUMMER semester...');

        // Find courses where the semester value is "SUMMER"
        $courses = Course::whereRaw('LOWER(semester) = ?', ['summer'])->get();
        $this->info("Found {$courses->count()} courses with summer semester value.");

        // Update each course with proper case for Summer
        $count = 0;
        foreach ($courses as $course) {
            $course->semester = 'SUMMER';
            $course->save();
            $count++;
        }

        // Update any field using old format (summer, Summer) to the new format (SUMMER)
        DB::statement("UPDATE courses SET semester = 'SUMMER' WHERE LOWER(semester) = 'summer' AND semester != 'SUMMER'");

        $this->info("Successfully updated {$count} courses with consistent SUMMER semester value.");
        
        return Command::SUCCESS;
    }
}
