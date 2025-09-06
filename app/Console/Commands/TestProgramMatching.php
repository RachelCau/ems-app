<?php

namespace App\Console\Commands;

use App\Models\Program;
use App\Models\StudentEnrollment;
use Illuminate\Console\Command;

class TestProgramMatching extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:program-matching {program_code? : Optional program code to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test program matching logic with student enrollments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get specified program code or test all
        $programCode = $this->argument('program_code');
        
        if ($programCode) {
            $program = Program::where('code', $programCode)->first();
            if (!$program) {
                $this->error("Program with code {$programCode} not found");
                return 1;
            }
            $this->testProgramMatching($program);
        } else {
            // Test with a few example programs
            $programs = Program::take(5)->get();
            if ($programs->isEmpty()) {
                $this->error("No programs found to test");
                return 1;
            }
            
            foreach ($programs as $program) {
                $this->testProgramMatching($program);
                $this->newLine();
            }
        }
        
        return 0;
    }
    
    protected function testProgramMatching(Program $program)
    {
        $this->info("Testing program matching for: {$program->code} - {$program->name}");
        
        // Get all student enrollments
        $enrollments = StudentEnrollment::with('applicant.student')->take(20)->get();
        $this->info("Testing with " . $enrollments->count() . " student enrollments");
        
        // Create a program code map like in our application
        $programCodeMap = [
            strtolower($program->code) => $program->id,
            strtolower(str_replace(' ', '', $program->code)) => $program->id,
            $program->id => $program->id
        ];
        
        // Test strict matching
        $strictMatches = [];
        $looseMatches = [];
        
        foreach ($enrollments as $enrollment) {
            // Try strict matching
            $strictMatch = $this->getEnhancedProgramMatch($enrollment, $programCodeMap, true);
            if ($strictMatch) {
                [$matchedProgramId, $matchType] = $strictMatch;
                if ($matchedProgramId === $program->id) {
                    $strictMatches[] = [
                        'enrollment_id' => $enrollment->id, 
                        'match_type' => $matchType
                    ];
                }
            }
            
            // Try loose matching
            $looseMatch = $this->getEnhancedProgramMatch($enrollment, $programCodeMap, false);
            if ($looseMatch) {
                [$matchedProgramId, $matchType] = $looseMatch;
                if ($matchedProgramId === $program->id && !$strictMatch) {
                    $looseMatches[] = [
                        'enrollment_id' => $enrollment->id, 
                        'match_type' => $matchType
                    ];
                }
            }
        }
        
        // Report results
        $this->info("Strict matches: " . count($strictMatches));
        if (count($strictMatches) > 0) {
            $this->table(['Enrollment ID', 'Match Type'], array_map(function($match) {
                return [$match['enrollment_id'], $match['match_type']];
            }, $strictMatches));
        }
        
        $this->info("Additional loose matches: " . count($looseMatches));
        if (count($looseMatches) > 0) {
            $this->table(['Enrollment ID', 'Match Type'], array_map(function($match) {
                return [$match['enrollment_id'], $match['match_type']];
            }, $looseMatches));
        }
    }
    
    /**
     * Enhanced version of getEffectiveProgramCode that provides more matching options
     * Copy of the method from ListEnrolledCourses to test independently
     * 
     * @param StudentEnrollment $enrollment
     * @param array $programCodeMap Map of lowercase program codes to program IDs
     * @param bool $strictMode When true, only use direct matches (for single program assignment)
     * @return array|null Returns [program_id, match_type] or null if no match
     */
    private function getEnhancedProgramMatch($enrollment, array $programCodeMap, bool $strictMode = false): ?array
    {
        // Direct program_id match - highest priority
        if ($enrollment->program_id && isset($programCodeMap[$enrollment->program_id])) {
            return [$enrollment->program_id, 'direct_program_id_match'];
        }
        
        // Check if enrollment has a program_code - high priority
        if (!empty($enrollment->program_code)) {
            $lookupCode = strtolower($enrollment->program_code);
            
            // Direct match
            if (isset($programCodeMap[$lookupCode])) {
                return [$programCodeMap[$lookupCode], 'program_code_direct'];
            }
            
            // Try with spaces removed
            $noSpaceCode = str_replace(' ', '', $lookupCode);
            if (isset($programCodeMap[$noSpaceCode])) {
                return [$programCodeMap[$noSpaceCode], 'program_code_nospace'];
            }
            
            // Only try partial matching if not in strict mode
            if (!$strictMode) {
                // Try to find a partial match
                foreach ($programCodeMap as $code => $id) {
                    if (stripos($lookupCode, $code) !== false || stripos($code, $lookupCode) !== false) {
                        return [$id, 'program_code_partial'];
                    }
                }
            }
        }
        
        // If applicant has a student with program_id - medium priority
        if ($enrollment->applicant && $enrollment->applicant->student && $enrollment->applicant->student->program_id) {
            if (isset($programCodeMap[$enrollment->applicant->student->program_id])) {
                return [$enrollment->applicant->student->program_id, 'student_program_id'];
            }
        }
        
        // If applicant has a direct program_id relationship - medium priority
        if ($enrollment->applicant && $enrollment->applicant->program_id) {
            if (isset($programCodeMap[$enrollment->applicant->program_id])) {
                return [$enrollment->applicant->program_id, 'applicant_program_id'];
            }
        }
        
        // If we're in strict mode, stop here - don't try fuzzy matching
        if ($strictMode) {
            return null;
        }
        
        // Check if the applicant has a student with a program_code - lower priority
        if ($enrollment->applicant && $enrollment->applicant->student && !empty($enrollment->applicant->student->program_code)) {
            $lookupCode = strtolower($enrollment->applicant->student->program_code);
            
            // Direct match
            if (isset($programCodeMap[$lookupCode])) {
                return [$programCodeMap[$lookupCode], 'student_program_code_direct'];
            }
            
            // Try with spaces removed
            $noSpaceCode = str_replace(' ', '', $lookupCode);
            if (isset($programCodeMap[$noSpaceCode])) {
                return [$programCodeMap[$noSpaceCode], 'student_program_code_nospace'];
            }
        }
        
        // Check if the applicant has a desired_program - lowest priority
        if ($enrollment->applicant && !empty($enrollment->applicant->desired_program)) {
            $lookupCode = strtolower(trim($enrollment->applicant->desired_program));
            
            // Direct match
            if (isset($programCodeMap[$lookupCode])) {
                return [$programCodeMap[$lookupCode], 'desired_program_direct'];
            }
            
            // Try with spaces removed
            $noSpaceCode = str_replace(' ', '', $lookupCode);
            if (isset($programCodeMap[$noSpaceCode])) {
                return [$programCodeMap[$noSpaceCode], 'desired_program_nospace'];
            }
            
            // Try to find a partial match
            foreach ($programCodeMap as $code => $id) {
                if (stripos($lookupCode, $code) !== false || stripos($code, $lookupCode) !== false) {
                    return [$id, 'desired_program_partial'];
                }
            }
        }
        
        return null;
    }
}
