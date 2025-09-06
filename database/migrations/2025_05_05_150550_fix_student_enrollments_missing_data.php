<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\StudentEnrollment;
use App\Models\Student;
use App\Models\Applicant;
use App\Models\Program;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Find all student enrollments with missing data
        $enrollments = StudentEnrollment::whereNull('student_id')
            ->orWhereNull('program_code')
            ->get();

        $fixedCount = 0;
        $errorCount = 0;

        foreach ($enrollments as $enrollment) {
            try {
                // Fix missing student_id
                if (empty($enrollment->student_id) && !empty($enrollment->applicant_id)) {
                    $applicant = Applicant::find($enrollment->applicant_id);
                    if ($applicant) {
                        // Look for a student with matching email
                        $student = Student::where('email', $applicant->email)->first();
                        if ($student) {
                            $enrollment->student_id = $student->id;
                            $fixedCount++;
                        }
                    }
                }

                // Fix missing program_code
                if (empty($enrollment->program_code) && !empty($enrollment->program_id)) {
                    $program = Program::find($enrollment->program_id);
                    if ($program) {
                        $enrollment->program_code = $program->code;
                        $fixedCount++;
                    }
                }

                // Save the enrollment if changes were made
                if ($enrollment->isDirty()) {
                    $enrollment->save();
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Error fixing enrollment ID {$enrollment->id}: " . $e->getMessage());
                $errorCount++;
            }
        }

        \Illuminate\Support\Facades\Log::info("Fixed student enrollments data migration completed", [
            'total_processed' => count($enrollments),
            'fixed_count' => $fixedCount,
            'error_count' => $errorCount
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback for this data fix
    }
};
