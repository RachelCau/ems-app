<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\StudentEnrollment;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all students
        $students = Student::all();
        $updatedCount = 0;
        $errorCount = 0;
        
        foreach ($students as $student) {
            try {
                // Find the most recent enrollment for this student
                $enrollment = StudentEnrollment::where('student_id', $student->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($enrollment) {
                    // Prepare update data
                    $updateData = [];
                    
                    // Update program related fields if they're missing in the student record
                    if (empty($student->program_id) && !empty($enrollment->program_id)) {
                        $updateData['program_id'] = $enrollment->program_id;
                    }
                    
                    if (empty($student->program_code) && !empty($enrollment->program_code)) {
                        $updateData['program_code'] = $enrollment->program_code;
                    }
                    
                    // Update academic year if missing
                    if (empty($student->academic_year_id) && !empty($enrollment->academic_year_id)) {
                        $updateData['academic_year_id'] = $enrollment->academic_year_id;
                    }
                    
                    // Update year level if missing
                    if (empty($student->year_level) && !empty($enrollment->year_level)) {
                        $updateData['year_level'] = $enrollment->year_level;
                    }
                    
                    // Update semester if missing
                    if (empty($student->semester) && !empty($enrollment->semester)) {
                        $updateData['semester'] = $enrollment->semester;
                    }
                    
                    // Only update if we have data to update
                    if (!empty($updateData)) {
                        $student->update($updateData);
                        $updatedCount++;
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Error updating student {$student->id}: " . $e->getMessage());
                $errorCount++;
            }
        }
        
        \Illuminate\Support\Facades\Log::info('Updated students with enrollment data', [
            'updated_count' => $updatedCount,
            'error_count' => $errorCount,
            'total_students' => $students->count()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a data update, no direct rollback needed
    }
};
