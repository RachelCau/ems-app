<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Program;
use App\Models\AcademicYear;
use App\Models\CourseCurriculum;

class SetupCourseCurricula extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup-course-curricula';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up curriculum records for all programs, ensuring first year, first semester is covered';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up course curricula...');

        // Get all programs
        $programs = Program::all();
        $this->info("Found {$programs->count()} programs.");

        // Get the current academic year
        $academicYear = AcademicYear::where('is_active', true)->first();
        
        if (!$academicYear) {
            $academicYear = AcademicYear::latest()->first();
            if (!$academicYear) {
                $this->error('No academic years found. Please create an academic year first.');
                return Command::FAILURE;
            }
        }
        
        $this->info("Using academic year: {$academicYear->name}");

        $created = 0;
        $skipped = 0;

        // Create curriculum records for each program
        foreach ($programs as $program) {
            // Check if first year, first semester curriculum already exists for this program
            $existingCurriculum = CourseCurriculum::where([
                'program_id' => $program->id,
                'academic_year_id' => $academicYear->id,
                'year_level' => 1,
                'semester' => 1,
                'is_active' => true
            ])->first();

            if ($existingCurriculum) {
                $this->info("Curriculum already exists for program: {$program->name}, skipping...");
                $skipped++;
                continue;
            }

            // Create a new curriculum
            CourseCurriculum::create([
                'name' => "{$program->code} Curriculum",
                'version' => '1.0',
                'program_id' => $program->id,
                'academic_year_id' => $academicYear->id,
                'year_level' => 1,
                'semester' => 1,
                'is_active' => true
            ]);

            $this->info("Created curriculum for program: {$program->name}");
            $created++;
        }

        $this->info("Created {$created} curricula, skipped {$skipped} existing curricula.");
        
        return Command::SUCCESS;
    }
}
