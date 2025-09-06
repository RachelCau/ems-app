<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\StudentEnrollment;
use App\Models\Applicant;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First backup the current data
        $enrollments = DB::table('student_enrollments')->get();
        $convertedData = [];
        
        // Create a mapping of applicant IDs to applicant numbers
        $applicants = Applicant::all()->keyBy('id');
        
        foreach ($enrollments as $enrollment) {
            if (isset($enrollment->applicant_id) && is_numeric($enrollment->applicant_id)) {
                $applicantId = (int)$enrollment->applicant_id;
                $applicantNumber = $applicants[$applicantId]->applicant_number ?? null;
                
                if ($applicantNumber) {
                    $convertedData[$enrollment->id] = $applicantNumber;
                }
            }
        }
        
        // First drop the foreign key constraint if it exists
        Schema::table('student_enrollments', function (Blueprint $table) {
            // Get all foreign keys
            $foreignKeys = $this->listTableForeignKeys('student_enrollments');
            
            // Find and drop the constraint on applicant_id if it exists
            foreach ($foreignKeys as $foreignKey) {
                if (str_contains($foreignKey, 'applicant_id')) {
                    $table->dropForeign($foreignKey);
                }
            }
        });
        
        // Change the column type to string
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->string('applicant_id')->nullable()->change();
        });
        
        // Update records with the new applicant_number values
        foreach ($convertedData as $enrollmentId => $applicantNumber) {
            DB::table('student_enrollments')
                ->where('id', $enrollmentId)
                ->update(['applicant_id' => $applicantNumber]);
        }
        
        DB::statement('ALTER TABLE student_enrollments MODIFY COLUMN applicant_id VARCHAR(255) COMMENT "Stores the applicant_number instead of ID"');
        
        \Illuminate\Support\Facades\Log::info('Updated student_enrollments.applicant_id to use applicant_number', [
            'records_updated' => count($convertedData)
        ]);
    }
    
    /**
     * Get the table's foreign key names
     *
     * @param string $table
     * @return array
     */
    private function listTableForeignKeys($table)
    {
        $conn = Schema::getConnection()->getDoctrineSchemaManager();
        
        return array_map(
            function($key) {
                return $key->getName();
            },
            $conn->listTableForeignKeys($table)
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This is a data transformation, no direct rollback needed
        // Could convert back to integer IDs if necessary but would lose data
    }
};
