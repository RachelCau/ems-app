<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Program;
use App\Models\CourseCurriculum;
use App\Models\Course;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

class FixCurriculum extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-curriculum';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix curriculum issues for BS Information Systems';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Diagnosing curriculum issues...');

        // Get academic year
        $academicYear = AcademicYear::where('is_active', true)->first();
        if (!$academicYear) {
            $this->error('No active academic year found.');
            return 1;
        }
        $this->info("Found active academic year: {$academicYear->name}");

        // Get BS Information Systems program
        $bsisProgram = Program::where('id', 19)->first();
        
        if (!$bsisProgram) {
            $this->error('BS Information Systems program not found.');
            return 1;
        }
        $this->info("Found program: {$bsisProgram->name} (ID: {$bsisProgram->id})");

        // Dump all curricula for verification
        $this->info("--- All Course Curricula ---");
        $allCurricula = DB::table('course_curricula')->get();
        $this->table(['id', 'program_id', 'year_level', 'semester', 'is_active'], 
            $allCurricula->map(function($item) {
                return [
                    'id' => $item->id,
                    'program_id' => $item->program_id,
                    'year_level' => $item->year_level,
                    'semester' => $item->semester,
                    'is_active' => $item->is_active ? 'Yes' : 'No',
                ];
            })->toArray()
        );

        // Check for existing curriculum
        $curriculum = CourseCurriculum::where([
            'program_id' => $bsisProgram->id,
            'year_level' => 1,
            'semester' => 1,
        ])->first();

        if ($curriculum) {
            $this->info("Found existing curriculum (ID: {$curriculum->id})");
            
            // Check if it's active
            if (!$curriculum->is_active) {
                $curriculum->is_active = true;
                $curriculum->save();
                $this->info("Activated the curriculum.");
            } else {
                $this->info("Curriculum is already active.");
            }
            
            // Make sure academic year is set
            if ($curriculum->academic_year_id != $academicYear->id) {
                $curriculum->academic_year_id = $academicYear->id;
                $curriculum->save();
                $this->info("Updated academic year for curriculum.");
            }
            
            // Check for courses
            $courseCount = DB::table('curriculum_course')
                ->where('course_curriculum_id', $curriculum->id)
                ->count();
            
            $this->info("Curriculum has {$courseCount} courses.");
            
            if ($courseCount === 0) {
                $this->info("Adding courses to the curriculum...");
                $this->addCoursesToCurriculum($curriculum, $bsisProgram);
            }
        } else {
            $this->info("No curriculum found. Creating new curriculum...");
            $curriculum = CourseCurriculum::create([
                'name' => "{$bsisProgram->code} Curriculum",
                'version' => '1.0',
                'program_id' => $bsisProgram->id,
                'academic_year_id' => $academicYear->id,
                'year_level' => 1,
                'semester' => 1,
                'is_active' => true,
            ]);
            $this->info("Created new curriculum (ID: {$curriculum->id})");
            
            $this->addCoursesToCurriculum($curriculum, $bsisProgram);
        }

        // Check if curriculum has courses after all operations
        $this->info("--- Curriculum Courses ---");
        $curriculumCourses = DB::table('curriculum_course')
            ->join('courses', 'curriculum_course.course_id', '=', 'courses.id')
            ->where('curriculum_course.course_curriculum_id', $curriculum->id)
            ->select('curriculum_course.id', 'courses.name', 'courses.code')
            ->get();
            
        if ($curriculumCourses->isEmpty()) {
            $this->warn("No courses found in curriculum after fix!");
            // Force add courses
            $this->info("Forcibly adding courses to curriculum...");
            $this->addCoursesToCurriculum($curriculum, $bsisProgram);
            
            // Check again
            $curriculumCourses = DB::table('curriculum_course')
                ->join('courses', 'curriculum_course.course_id', '=', 'courses.id')
                ->where('curriculum_course.course_curriculum_id', $curriculum->id)
                ->select('curriculum_course.id', 'courses.name', 'courses.code')
                ->get();
        }
        
        $this->table(['id', 'course_name', 'course_code'], 
            $curriculumCourses->map(function($item) {
                return [
                    'id' => $item->id,
                    'course_name' => $item->name,
                    'course_code' => $item->code,
                ];
            })->toArray()
        );

        $this->info("Curriculum fix completed successfully!");
        return 0;
    }

    /**
     * Add courses to a curriculum
     */
    private function addCoursesToCurriculum($curriculum, $program)
    {
        // Get first year courses for the program
        $courses = Course::where('level', 'First Year')
            ->whereHas('programs', function ($query) use ($program) {
                $query->where('programs.id', $program->id);
            })
            ->take(5)
            ->get();
        
        // If no program-specific courses, fallback to any first year courses
        if ($courses->isEmpty()) {
            $this->warn("No program-specific courses found. Using generic first year courses.");
            $courses = Course::where('level', 'First Year')
                ->take(5)
                ->get();
        }
        
        // Still empty? Create some sample courses
        if ($courses->isEmpty()) {
            $this->warn("No courses found at all. Creating sample courses.");
            $academicYear = AcademicYear::where('is_active', true)->first();
            
            $sampleCourses = [
                [
                    'name' => 'Introduction to Information Systems',
                    'code' => 'BSIS101',
                    'unit' => 3,
                    'level' => 'First Year',
                    'semester' => 'First Semester',
                    'type' => 'GEN_ED',
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'name' => 'Computer Programming 1',
                    'code' => 'BSIS102',
                    'unit' => 3,
                    'level' => 'First Year',
                    'semester' => 'First Semester',
                    'type' => 'TECH_SKILL',
                    'academic_year_id' => $academicYear->id,
                ],
                [
                    'name' => 'Mathematics for Computing',
                    'code' => 'BSIS103',
                    'unit' => 3,
                    'level' => 'First Year',
                    'semester' => 'First Semester',
                    'type' => 'GEN_ED',
                    'academic_year_id' => $academicYear->id,
                ],
            ];
            
            $courses = collect();
            foreach ($sampleCourses as $sampleCourse) {
                $course = Course::create($sampleCourse);
                // Attach course to the program
                $course->programs()->attach($program->id);
                $courses->push($course);
            }
        }
        
        $this->info("Found " . $courses->count() . " courses to add to curriculum.");
        
        // Attach courses to the curriculum
        foreach ($courses as $index => $course) {
            DB::table('curriculum_course')->insert([
                'course_curriculum_id' => $curriculum->id,
                'course_id' => $course->id,
                'is_required' => true,
                'sort_order' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        $this->info("Added " . $courses->count() . " courses to the curriculum.");
    }
}
