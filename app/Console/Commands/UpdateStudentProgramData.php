<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;

class UpdateStudentProgramData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:update-program-data {--force : Force update even if data exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update student program, academic year, year level and semester data from enrollment records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting student data update process...');
        
        $force = $this->option('force');
        
        // Get all students
        $students = Student::all();
        $this->info('Found ' . $students->count() . ' students');
        
        $updatedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        
        $progressBar = $this->output->createProgressBar($students->count());
        $progressBar->start();
        
        foreach ($students as $student) {
            try {
                // Find the most recent enrollment for this student
                $enrollment = StudentEnrollment::where('student_id', $student->id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                if ($enrollment) {
                    // Prepare update data
                    $updateData = [];
                    
                    // Update program related fields if they're missing or force is enabled
                    if (($force || empty($student->program_id)) && !empty($enrollment->program_id)) {
                        $updateData['program_id'] = $enrollment->program_id;
                    }
                    
                    if (($force || empty($student->program_code)) && !empty($enrollment->program_code)) {
                        $updateData['program_code'] = $enrollment->program_code;
                    }
                    
                    // Update academic year if missing or force is enabled
                    if (($force || empty($student->academic_year_id)) && !empty($enrollment->academic_year_id)) {
                        $updateData['academic_year_id'] = $enrollment->academic_year_id;
                    }
                    
                    // Update year level if missing or force is enabled
                    if (($force || empty($student->year_level)) && !empty($enrollment->year_level)) {
                        $updateData['year_level'] = $enrollment->year_level;
                    }
                    
                    // Update semester if missing or force is enabled
                    if (($force || empty($student->semester)) && !empty($enrollment->semester)) {
                        $updateData['semester'] = $enrollment->semester;
                    }
                    
                    // Only update if we have data to update
                    if (!empty($updateData)) {
                        $student->update($updateData);
                        $updatedCount++;
                    } else {
                        $skippedCount++;
                    }
                } else {
                    $skippedCount++;
                }
            } catch (\Exception $e) {
                $this->error("Error updating student {$student->id}: " . $e->getMessage());
                $errorCount++;
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->info('Student data update completed:');
        $this->table(
            ['Updated', 'Skipped', 'Errors', 'Total'],
            [[$updatedCount, $skippedCount, $errorCount, $students->count()]]
        );
        
        return 0;
    }
}
