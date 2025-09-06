<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Applicant;
use App\Models\Program;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, check if the program_id column has a foreign key constraint
        $hasIndex = DB::select("SHOW INDEXES FROM applicants WHERE Column_name = 'program_id'");
        
        if (empty($hasIndex)) {
            // Add index to program_id if it doesn't exist
            Schema::table('applicants', function (Blueprint $table) {
                $table->index('program_id');
            });
        }
        
        // Update applicants with missing program_id but having desired_program
        $applicants = Applicant::whereNull('program_id')
            ->whereNotNull('desired_program')
            ->get();
            
        foreach ($applicants as $applicant) {
            // Try to find a matching program based on desired_program value
            $program = Program::where('name', 'like', "%{$applicant->desired_program}%")
                ->orWhere('code', $applicant->desired_program)
                ->first();
                
            if ($program) {
                $applicant->program_id = $program->id;
                $applicant->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse the data updates
        // Could remove the index if needed, but it's generally good to keep it
    }
};
