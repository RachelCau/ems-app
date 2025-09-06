<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // If no records exist, add a placeholder curriculum
        $count = DB::table('course_curricula')->count();
        
        if ($count == 0) {
            $this->info('No curriculum records found. Adding placeholder record.');
            
            // Get an academic year
            $academicYearId = DB::table('academic_years')->value('id');
            
            if (!$academicYearId) {
                $this->error('No academic years found. Please create an academic year first.');
                return;
            }
            
            // Get a program
            $programId = DB::table('programs')->value('id');
            
            if (!$programId) {
                $this->error('No programs found. Please create a program first.');
                return;
            }
            
            // Insert a placeholder curriculum
            DB::table('course_curricula')->insert([
                'name' => 'Default Curriculum',
                'version' => '1.0',
                'program_id' => $programId,
                'academic_year_id' => $academicYearId,
                'is_active' => true,
                'year_level' => 1,
                'semester' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $this->info('Added placeholder curriculum record.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the placeholder curriculum if it exists
        DB::table('course_curricula')
            ->where('name', 'Default Curriculum')
            ->where('version', '1.0')
            ->delete();
    }
    
    // Helper method to output messages during migration
    protected function info($message)
    {
        if (app()->runningInConsole()) {
            echo "\033[32mINFO: " . $message . "\033[0m\n";
        }
    }
    
    protected function error($message)
    {
        if (app()->runningInConsole()) {
            echo "\033[31mERROR: " . $message . "\033[0m\n";
        }
    }
};
