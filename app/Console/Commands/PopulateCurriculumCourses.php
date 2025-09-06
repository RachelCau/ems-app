<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CourseCurriculum;
use App\Models\Course;
use App\Models\Program;
use Illuminate\Support\Facades\DB;

class PopulateCurriculumCourses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-curriculum-courses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add courses to curriculum records to ensure each has at least some courses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Populating curriculum courses...');

        // Get all curricula
        $curricula = CourseCurriculum::all();
        $this->info("Found {$curricula->count()} curriculum records.");

        $updated = 0;

        foreach ($curricula as $curriculum) {
            // Get the program for this curriculum
            $program = Program::find($curriculum->program_id);
            if (!$program) {
                $this->warn("No program found for curriculum {$curriculum->id}, skipping...");
                continue;
            }

            // Get courses for this program, matching year level and semester
            $programCourses = Course::whereHas('programs', function ($query) use ($program) {
                $query->where('programs.id', $program->id);
            })
            ->where(function ($query) use ($curriculum) {
                // Try to match by various formats of year level
                $query->where(function ($q) use ($curriculum) {
                    $yearLevel = $curriculum->year_level;
                    
                    if ($yearLevel == 1) {
                        $q->where('level', 'First Year')
                          ->orWhere('level', '1st Year');
                    } else if ($yearLevel == 2) {
                        $q->where('level', 'Second Year')
                          ->orWhere('level', '2nd Year');
                    } else if ($yearLevel == 3) {
                        $q->where('level', 'Third Year')
                          ->orWhere('level', '3rd Year');
                    } else if ($yearLevel == 4) {
                        $q->where('level', 'Fourth Year')
                          ->orWhere('level', '4th Year');
                    }
                });
            })
            ->where(function ($query) use ($curriculum) {
                // Try to match by various formats of semester
                $query->where(function ($q) use ($curriculum) {
                    $semester = $curriculum->semester;
                    
                    if ($semester == 1) {
                        $q->where('semester', 'First Semester')
                          ->orWhere('semester', '1st Semester');
                    } else if ($semester == 2) {
                        $q->where('semester', 'Second Semester')
                          ->orWhere('semester', '2nd Semester');
                    } else if ($semester == 3 || strtolower($semester) == 'summer') {
                        $q->where('semester', 'SUMMER')
                          ->orWhere('semester', 'Summer');
                    }
                });
            })
            ->get();

            if ($programCourses->isEmpty()) {
                // If no program-specific courses, try to find general courses that match the level/semester
                $this->info("No program-specific courses found for {$program->code}, looking for general courses...");
                
                $programCourses = Course::where(function ($query) use ($curriculum) {
                    // Try to match by various formats of year level
                    $query->where(function ($q) use ($curriculum) {
                        $yearLevel = $curriculum->year_level;
                        
                        if ($yearLevel == 1) {
                            $q->where('level', 'First Year')
                              ->orWhere('level', '1st Year');
                        } else if ($yearLevel == 2) {
                            $q->where('level', 'Second Year')
                              ->orWhere('level', '2nd Year');
                        } else if ($yearLevel == 3) {
                            $q->where('level', 'Third Year')
                              ->orWhere('level', '3rd Year');
                        } else if ($yearLevel == 4) {
                            $q->where('level', 'Fourth Year')
                              ->orWhere('level', '4th Year');
                        }
                    });
                })
                ->where(function ($query) use ($curriculum) {
                    // Try to match by various formats of semester
                    $query->where(function ($q) use ($curriculum) {
                        $semester = $curriculum->semester;
                        
                        if ($semester == 1) {
                            $q->where('semester', 'First Semester')
                              ->orWhere('semester', '1st Semester');
                        } else if ($semester == 2) {
                            $q->where('semester', 'Second Semester')
                              ->orWhere('semester', '2nd Semester');
                        } else if ($semester == 3 || strtolower($semester) == 'summer') {
                            $q->where('semester', 'SUMMER')
                              ->orWhere('semester', 'Summer');
                        }
                    });
                })
                ->where('type', 'GEN_ED') // Prefer general education courses
                ->get();
            }

            if ($programCourses->isEmpty()) {
                $this->warn("No courses found for curriculum {$curriculum->id} ({$program->code}, Year {$curriculum->year_level}, Semester {$curriculum->semester})");
                continue;
            }

            // Check if curriculum already has courses
            $currentCourseCount = DB::table('curriculum_course')
                ->where('course_curriculum_id', $curriculum->id)
                ->count();

            if ($currentCourseCount > 0) {
                $this->info("Curriculum {$curriculum->id} already has {$currentCourseCount} courses, skipping...");
                continue;
            }

            // Add courses to the curriculum
            $added = 0;
            $sortOrder = 1;
            
            foreach ($programCourses as $course) {
                DB::table('curriculum_course')->insert([
                    'course_curriculum_id' => $curriculum->id,
                    'course_id' => $course->id,
                    'year_level' => $curriculum->year_level,
                    'semester' => $curriculum->semester,
                    'is_required' => true,
                    'sort_order' => $sortOrder++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $added++;
            }

            if ($added > 0) {
                $this->info("Added {$added} courses to curriculum {$curriculum->id} ({$program->code}, Year {$curriculum->year_level}, Semester {$curriculum->semester})");
                $updated++;
            }
        }

        $this->info("Updated {$updated} curriculum records with courses.");
        
        return Command::SUCCESS;
    }
}
