<?php

namespace App\Listeners;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseCurriculum;
use App\Models\Program;
use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnsureCourseAssignmentEligibility
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        // Get the model from the event, handling different event types
        $model = null;
        
        if (isset($event->model) && $event->model instanceof Model) {
            $model = $event->model;
        } elseif (isset($event->enrollment) && $event->enrollment instanceof Model) {
            $model = $event->enrollment;
        } elseif (method_exists($event, 'getModel') && $event->getModel() instanceof Model) {
            $model = $event->getModel();
        }
        
        // If not a student enrollment, exit
        if (!$model || !($model instanceof StudentEnrollment)) {
            return;
        }
        
        // Get the enrollment
        $enrollment = $model;
        
        $needsUpdate = false;
        $updates = [];
        
        // If enrollment has a student but no program_id or program_code, try to infer them
        if (empty($enrollment->program_id) || empty($enrollment->program_code)) {
            // 1. Try to get program info from student
            if ($enrollment->student) {
                if (empty($enrollment->program_id) && $enrollment->student->program_id) {
                    $updates['program_id'] = $enrollment->student->program_id;
                    $needsUpdate = true;
                }
                
                if (empty($enrollment->program_code) && !empty($enrollment->student->program_code)) {
                    $updates['program_code'] = $enrollment->student->program_code;
                    $needsUpdate = true;
                }
            }
            
            // 2. Try to get program info from applicant
            if ((empty($enrollment->program_id) || empty($enrollment->program_code)) && $enrollment->applicant) {
                if (empty($enrollment->program_id) && $enrollment->applicant->program_id) {
                    $updates['program_id'] = $enrollment->applicant->program_id;
                    $needsUpdate = true;
                }
                
                // 3. Try to infer from desired program
                if ((empty($enrollment->program_id) || empty($enrollment->program_code)) && 
                    !empty($enrollment->applicant->desired_program)) {
                    
                    $desiredProgram = trim($enrollment->applicant->desired_program);
                    $programs = Program::all();
                    
                    foreach ($programs as $program) {
                        // Improved program matching logic with partial name/code matching
                        $programFound = false;
                        
                        if (stripos($program->code, $desiredProgram) !== false || 
                            stripos($desiredProgram, $program->code) !== false ||
                            stripos($program->name, $desiredProgram) !== false ||
                            stripos($desiredProgram, $program->name) !== false ||
                            // Improved matching for abbreviated terms
                            (str_contains(strtolower($desiredProgram), 'associate') && str_contains(strtolower($program->name), 'associate')) ||
                            (str_contains(strtolower($desiredProgram), 'computer technology') && str_contains(strtolower($program->name), 'computer technology')) ||
                            (str_contains(strtolower($desiredProgram), 'application development') && str_contains(strtolower($program->name), 'application development'))) {
                            
                            $programFound = true;
                        }
                        
                        if ($programFound) {
                            if (empty($enrollment->program_id)) {
                                $updates['program_id'] = $program->id;
                            }
                            
                            if (empty($enrollment->program_code)) {
                                $updates['program_code'] = $program->code;
                            }
                            
                            $needsUpdate = true;
                            break;
                        }
                    }
                }
            }
        }
        
        // Apply updates if needed
        if ($needsUpdate && !empty($updates)) {
            $enrollment->update($updates);
        }
        
        // Ensure a curriculum exists for this program and year level
        $this->ensureCurriculumExists($enrollment);
    }
    
    /**
     * Ensure that a curriculum exists for the enrollment's program and year level
     */
    private function ensureCurriculumExists(StudentEnrollment $enrollment): void
    {
        // Skip if no program_id
        if (empty($enrollment->program_id)) {
            return;
        }
        
        $yearLevel = $enrollment->year_level ?? 1;
        $semester = $enrollment->semester ?? 1;
        
        // Check if curriculum exists
        $curriculum = CourseCurriculum::where([
            'program_id' => $enrollment->program_id,
            'year_level' => $yearLevel,
            'semester' => $semester,
            'is_active' => true,
        ])->first();
        
        if (!$curriculum) {
            // Get current academic year
            $academicYear = AcademicYear::where('is_active', true)->first();
            if (!$academicYear) {
                return;
            }
            
            // Get program
            $program = Program::find($enrollment->program_id);
            if (!$program) {
                return;
            }
            
            DB::beginTransaction();
            
            try {
                // Create a new curriculum
                $curriculum = CourseCurriculum::create([
                    'name' => "{$program->code} Curriculum Y{$yearLevel}S{$semester}",
                    'version' => '1.0',
                    'program_id' => $program->id,
                    'academic_year_id' => $academicYear->id,
                    'year_level' => $yearLevel,
                    'semester' => $semester,
                    'is_active' => true
                ]);
                
                // Find courses for this program or create at least one
                $programCourses = Course::whereHas('programs', function ($query) use ($program) {
                    $query->where('programs.id', $program->id);
                })->orWhere('code', 'like', "%{$program->code}%")->get();
                
                if ($programCourses->isEmpty()) {
                    // Create at least one course for this program
                    $course = Course::create([
                        'name' => "Introduction to {$program->name}",
                        'code' => "{$program->code}101",
                        'unit' => 3,
                        'description' => "Basic course for {$program->name}",
                        'level' => '1st year',
                        'semester' => 'First Semester',
                        'academic_year_id' => $academicYear->id,
                    ]);
                    
                    // Attach to program
                    $course->programs()->attach($program->id);
                    
                    // Add to curriculum
                    $curriculum->courses()->attach($course->id, [
                        'is_required' => true,
                        'sort_order' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    // Attach existing courses to curriculum
                    $sortOrder = 1;
                    foreach ($programCourses as $course) {
                        $curriculum->courses()->attach($course->id, [
                            'is_required' => true,
                            'sort_order' => $sortOrder++,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error creating curriculum: " . $e->getMessage());
            }
        }
    }
} 