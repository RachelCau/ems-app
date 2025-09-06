<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get required IDs for relationships
        $programId = DB::table('programs')->where('code', 'BSIS')->value('id');
        $academicYearId = DB::table('academic_years')->where('is_active', 1)->value('id') 
            ?? DB::table('academic_years')->latest('id')->value('id');
        $campusId = DB::table('campuses')->first()->id ?? null;
        $userId = DB::table('users')->first()->id ?? 1;
        
        if (!$programId || !$academicYearId) {
            $this->info('Missing required data: Program or Academic Year.');
            return;
        }
        
        $this->info("Creating demo data for BSIS program (ID: {$programId})");
        
        // Create 5 demo applicants
        for ($i = 1; $i <= 5; $i++) {
            $firstName = "DemoStudent{$i}";
            $lastName = "BSIS_" . Str::random(5);
            $email = strtolower("{$firstName}.{$lastName}@example.com");
            $applicantNumber = "BSIS-" . date('Y') . "-" . sprintf('%04d', $i);
            
            // Insert applicant
            $applicantId = DB::table('applicants')->insertGetId([
                'user_id' => $userId,
                'campus_id' => $campusId,
                'academic_year_id' => $academicYearId,
                'applicant_number' => $applicantNumber,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'desired_program' => 'BSIS',
                'email' => $email,
                'status' => 'Officially Enrolled',
                'enrollment_status' => 'Officially Enrolled',
                'mobile' => '09' . rand(100000000, 999999999),
                'sex' => rand(0, 1) ? 'Male' : 'Female', 
                'dateofbirth' => date('Y-m-d', strtotime('-' . rand(18, 25) . ' years')),
                'program_id' => $programId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Insert enrollment with or without campus_id based on availability
            $enrollmentData = [
                'applicant_id' => $applicantId,
                'program_id' => $programId,
                'program_code' => 'BSIS',
                'academic_year_id' => $academicYearId,
                'status' => 'Active',
                'is_new_student' => true,
                'year_level' => 1,
                'semester' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            // Only add campus_id if it exists
            if ($campusId) {
                $enrollmentData['campus_id'] = $campusId;
            }
            
            DB::table('student_enrollments')->insert($enrollmentData);
            
            $this->info("Created demo student: {$firstName} {$lastName}");
        }
        
        $this->info("Successfully created 5 demo students for BSIS program");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete demo students
        $demoApplicants = DB::table('applicants')
            ->where('first_name', 'like', 'DemoStudent%')
            ->where('last_name', 'like', 'BSIS_%')
            ->get();
            
        foreach ($demoApplicants as $applicant) {
            // Delete enrollments
            DB::table('student_enrollments')
                ->where('applicant_id', $applicant->id)
                ->delete();
                
            // Delete applicant
            DB::table('applicants')
                ->where('id', $applicant->id)
                ->delete();
        }
        
        $this->info("Removed demo students");
    }
    
    /**
     * Helper method to output messages during migration
     */
    protected function info($message)
    {
        if (app()->runningInConsole()) {
            echo "\033[32mINFO: " . $message . "\033[0m\n";
        }
    }
};
