<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Services\CourseAssignmentService;
use Illuminate\Console\Command;

class AssignCoursesToStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'courses:assign 
                            {--academic-year= : The academic year ID (default: current academic year)}
                            {--semester=1 : The semester (1 or 2)}
                            {--program= : Specific program code to assign courses for (optional)}
                            {--all-programs : Assign courses for all programs}
                            {--year-level= : Specific year level to assign courses for (1-4, optional)}
                            {--force : Force assignment even if processed before}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign courses to officially enrolled students based on program, year level, and semester';

    /**
     * Execute the console command.
     */
    public function handle(CourseAssignmentService $courseAssignmentService)
    {
        $this->info('Starting course assignment process...');
        
        // Get the academic year
        $academicYearId = $this->option('academic-year');
        
        if ($academicYearId) {
            $academicYear = AcademicYear::find($academicYearId);
            if (!$academicYear) {
                $this->error("Academic year with ID {$academicYearId} not found.");
                return 1;
            }
        } else {
            // Get the current academic year
            $academicYear = AcademicYear::where('is_active', true)->first();
            if (!$academicYear) {
                $this->error('No active academic year found. Please set an active academic year or specify an academic year ID.');
                return 1;
            }
        }
        
        // Get the semester
        $semester = (int) $this->option('semester');
        if ($semester < 1 || $semester > 2) {
            $this->error('Semester must be 1 or 2.');
            return 1;
        }
        
        // Get program code if specified
        $programCode = $this->option('program');
        $allPrograms = $this->option('all-programs');
        $yearLevel = $this->option('year-level');
        
        // Validate year level if provided
        if ($yearLevel && !in_array($yearLevel, [1, 2, 3, 4])) {
            $this->error('Year level must be between 1 and 4.');
            return 1;
        }
        
        // Display assignment scope
        if ($allPrograms) {
            $this->info("Processing for ALL PROGRAMS, Academic Year: {$academicYear->name}, Semester: {$semester}");
            if ($yearLevel) {
                $this->info("Filtering to Year Level: {$yearLevel}");
            }
        } elseif ($programCode) {
            $this->info("Processing for Program: {$programCode}, Academic Year: {$academicYear->name}, Semester: {$semester}");
            if ($yearLevel) {
                $this->info("Filtering to Year Level: {$yearLevel}");
            }
        } else {
            $this->info("Processing for DEFAULT PROGRAM ASSIGNMENT, Academic Year: {$academicYear->name}, Semester: {$semester}");
        }
        
        // Ask for confirmation unless force option is used
        if (!$this->option('force') && !$this->confirm('This will assign courses to officially enrolled students based on your criteria. Continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }
        
        try {
            // Run the assignment process
            $results = null;
            
            if ($allPrograms || $programCode || $yearLevel) {
                // Use the new flexible filtering method
                $specificProgram = $allPrograms ? null : $programCode;
                $results = $courseAssignmentService->assignCoursesWithFilters(
                    $academicYear, 
                    $semester,
                    $specificProgram,
                    $yearLevel ? (int)$yearLevel : null
                );
                
                // If all programs was selected, display programs processed
                if ($allPrograms && !empty($results['programs_processed'])) {
                    $this->info("Programs processed (" . count($results['programs_processed']) . "):");
                    foreach ($results['programs_processed'] as $program) {
                        $this->line("- {$program}");
                    }
                }
            } else {
                // Use the original method for backward compatibility
                $results = $courseAssignmentService->assignCoursesToOfficiallyEnrolledStudents($academicYear, $semester);
            }
            
            // Display the results
            $this->info('Course assignment completed.');
            $this->info("Total students processed: {$results['total_students_processed']}");
            $this->info("Successful assignments: {$results['successful_assignments']}");
            $this->info("Failed assignments: {$results['failed_assignments']}");
            
            // Show students requiring manual review
            if (!empty($results['students_requiring_manual_review'])) {
                $this->warn('The following students require manual review:');
                foreach ($results['students_requiring_manual_review'] as $student) {
                    $this->line("- {$student['student_name']} (ID: {$student['enrollment_id']}): {$student['reason']}");
                }
            }
            
            // Show errors if any
            if (!empty($results['errors'])) {
                $this->error('The following errors occurred:');
                foreach ($results['errors'] as $error) {
                    $this->line("- {$error}");
                }
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('An error occurred during the course assignment process:');
            $this->error($e->getMessage());
            return 1;
        }
    }
} 