<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Course;
use App\Models\Program;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FixCourseImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-course-import {--file= : Path to CSV file} {--academic-year= : Academic year ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix and process course imports with detailed error reporting';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->option('file');
        $academicYearId = $this->option('academic-year');

        if (!$academicYearId) {
            $activeYear = AcademicYear::where('is_active', true)->first();
            if ($activeYear) {
                $academicYearId = $activeYear->id;
                $this->info("Using active academic year: {$activeYear->name}");
            } else {
                $academicYearId = AcademicYear::latest()->first()?->id;
                if ($academicYearId) {
                    $this->info("Using latest academic year: " . AcademicYear::find($academicYearId)->name);
                } else {
                    $this->error("No academic years found in the system");
                    return 1;
                }
            }
        }

        if (empty($filePath)) {
            // Look for CSV files directly in the public directory
            $publicPath = public_path('assets'.DIRECTORY_SEPARATOR.'documents'.DIRECTORY_SEPARATOR.'csv-imports');
            
            // Check if directory exists
            if (!is_dir($publicPath)) {
                $this->error("Directory not found: {$publicPath}");
                return 1;
            }
            
            // Get all CSV files in the directory
            $files = scandir($publicPath);
            $csvFiles = array_filter($files, function($file) {
                return pathinfo($file, PATHINFO_EXTENSION) === 'csv';
            });
            
            if (empty($csvFiles)) {
                $this->error("No CSV files found in {$publicPath}");
                return 1;
            }
            
            // Sort files by modified time descending
            usort($csvFiles, function($a, $b) use ($publicPath) {
                return filemtime($publicPath.DIRECTORY_SEPARATOR.$b) - filemtime($publicPath.DIRECTORY_SEPARATOR.$a);
            });
            
            $fileChoice = $this->choice(
                'Select a CSV file to process:',
                $csvFiles
            );
            
            $filePath = $publicPath.DIRECTORY_SEPARATOR.$fileChoice;
            $this->info("Using file: {$filePath}");
        }

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("Processing file: {$filePath}");
        $this->info("Academic Year ID: {$academicYearId}");

        // Read the file
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $this->error("Could not open file: {$filePath}");
            return 1;
        }

        // Get headers
        $headers = fgetcsv($handle);
        if (!$headers) {
            $this->error("Empty CSV file or invalid format");
            fclose($handle);
            return 1;
        }

        // Normalize headers
        $normalizedHeaders = array_map(function($header) {
            return strtoupper(trim($header));
        }, $headers);

        $this->info("Found headers: " . implode(', ', $normalizedHeaders));

        // Map to expected headers
        $expectedHeaders = [
            'PROGRAM', 'CODE', 'COURSE', 'UNIT', 'TYPE', 'PREREQUISITE', 'LEVEL', 'SEMESTER'
        ];

        $headerMap = [
            'PROGRAM' => ['PROGRAM', 'PROGRAMS', 'PROGRAM_CODE', 'PROGRAM CODE', 'PROGRAM CODES', 'PROGRAM CODES'],
            'CODE' => ['CODE', 'COURSE_CODE', 'COURSE CODE'],
            'COURSE' => ['COURSE', 'COURSE_NAME', 'COURSE NAME', 'NAME', 'TITLE'],
            'UNIT' => ['UNIT', 'UNITS', 'CREDIT', 'CREDITS'],
            'TYPE' => ['TYPE', 'COURSE_TYPE', 'COURSE TYPE'],
            'PREREQUISITE' => ['PREREQUISITE', 'PREREQUISITES', 'PREREQ', 'PRE-REQUISITE', 'PRE_REQUISITE'],
            'LEVEL' => ['LEVEL', 'YEAR', 'YEAR_LEVEL', 'YEAR LEVEL'],
            'SEMESTER' => ['SEMESTER', 'SEM', 'TERM'],
        ];

        $headerMapping = [];
        $missingHeaders = [];

        foreach ($expectedHeaders as $expected) {
            $found = false;
            
            foreach ($normalizedHeaders as $index => $header) {
                if (in_array($header, $headerMap[$expected])) {
                    $headerMapping[$expected] = $index;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $missingHeaders[] = $expected;
            }
        }

        if (!empty($missingHeaders)) {
            $this->error("Missing required headers: " . implode(', ', $missingHeaders));
            
            // Suggest fixes
            if (count($missingHeaders) <= 2) {
                // Try to identify close matches
                foreach ($missingHeaders as $missing) {
                    $this->info("Looking for possible matches for '{$missing}'...");
                    $potentialMatches = [];
                    
                    foreach ($normalizedHeaders as $header) {
                        similar_text($missing, $header, $percent);
                        if ($percent > 60) {
                            $potentialMatches[] = $header;
                        }
                    }
                    
                    if (!empty($potentialMatches)) {
                        $this->info("Potential matches for '{$missing}': " . implode(', ', $potentialMatches));
                    }
                }
            }
            
            fclose($handle);
            return 1;
        }

        $this->info("All required headers found!");

        // Get available programs for validation
        $programs = Program::pluck('id', 'code')->toArray();
        if (empty($programs)) {
            $this->error("No programs found in database. Please create programs first.");
            fclose($handle);
            return 1;
        }
        
        $this->info("Found " . count($programs) . " programs in the database");

        // Get existing courses
        $existingCourses = Course::pluck('id', 'code')->toArray();
        $this->info("Found " . count($existingCourses) . " existing courses");

        // Process rows
        $rowNumber = 1; // Header row
        $validRows = [];
        $errors = [];

        $this->newLine();
        $this->info("Validating CSV data...");
        $this->newLine();

        // Create progress bar
        rewind($handle); // Go back to beginning of file
        $fileSize = filesize($filePath);
        $lineCount = 0;
        while (!feof($handle)) {
            fgets($handle);
            $lineCount++;
        }
        rewind($handle);
        fgetcsv($handle); // Skip header
        
        $progress = $this->output->createProgressBar($lineCount - 1);
        $progress->start();

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $progress->advance();
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Convert row to data using header mapping
            $rowData = [];
            foreach ($expectedHeaders as $header) {
                $index = $headerMapping[$header] ?? null;
                $rowData[$header] = $index !== null && isset($row[$index]) ? trim($row[$index]) : '';
            }
            
            // Basic validation
            $rowErrors = [];
            
            // Course code
            if (empty($rowData['CODE'])) {
                $rowErrors[] = "Missing course code";
            }
            
            // Course name
            if (empty($rowData['COURSE'])) {
                $rowErrors[] = "Missing course name";
            }
            
            // Unit
            if (empty($rowData['UNIT']) || !is_numeric($rowData['UNIT']) || (int)$rowData['UNIT'] <= 0 || (int)$rowData['UNIT'] > 10) {
                $rowErrors[] = "Invalid unit value (must be between 1 and 10): '{$rowData['UNIT']}'";
            }
            
            // Type
            $validTypes = ['GEN_ED', 'TECH_SKILL', 'CAP_OJT'];
            if (empty($rowData['TYPE']) || !in_array($rowData['TYPE'], $validTypes)) {
                $rowErrors[] = "Invalid type (must be one of: " . implode(', ', $validTypes) . "): '{$rowData['TYPE']}'";
            }
            
            // Level
            $validLevels = ['1', '2', '3', '4', 'First Year', 'Second Year', 'Third Year', 'Fourth Year'];
            if (empty($rowData['LEVEL']) || !in_array($rowData['LEVEL'], $validLevels)) {
                $rowErrors[] = "Invalid level (must be one of: " . implode(', ', $validLevels) . "): '{$rowData['LEVEL']}'";
            }
            
            // Semester
            $validSemesters = ['1', '2', 'summer', 'First Semester', 'Second Semester', 'Summer'];
            if (empty($rowData['SEMESTER']) || !in_array($rowData['SEMESTER'], $validSemesters)) {
                $rowErrors[] = "Invalid semester (must be one of: " . implode(', ', $validSemesters) . "): '{$rowData['SEMESTER']}'";
            }
            
            // Program codes
            $programCodes = array_map('trim', explode(',', $rowData['PROGRAM']));
            if (empty($programCodes) || $programCodes[0] === '') {
                $rowErrors[] = "Missing program code(s)";
            } else {
                // Check if all program codes exist
                $missingPrograms = [];
                foreach ($programCodes as $programCode) {
                    if (!isset($programs[$programCode])) {
                        $missingPrograms[] = $programCode;
                    }
                }
                
                if (!empty($missingPrograms)) {
                    $rowErrors[] = "Unknown program code(s): " . implode(', ', $missingPrograms);
                }
            }
            
            if (!empty($rowErrors)) {
                $errors[$rowNumber] = $rowErrors;
            } else {
                $validRows[] = $rowData;
            }
        }
        
        $progress->finish();
        $this->newLine(2);
        
        // Show validation results
        if (!empty($errors)) {
            $this->error(count($errors) . " rows have validation errors:");
            foreach ($errors as $rowNum => $rowErrors) {
                $this->line("Row {$rowNum}: " . implode('; ', $rowErrors));
            }
            $this->newLine();
        }
        
        $this->info(count($validRows) . " valid rows found");
        
        if (empty($validRows)) {
            $this->error("No valid data to import");
            fclose($handle);
            return 1;
        }
        
        // Ask to proceed with import
        if (!$this->confirm('Do you want to proceed with importing ' . count($validRows) . ' courses?', true)) {
            $this->info('Import cancelled');
            fclose($handle);
            return 0;
        }
        
        // Start DB transaction
        DB::beginTransaction();
        
        try {
            $importedCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;
            
            $this->newLine();
            $this->info("Importing courses...");
            $this->newLine();
            
            $progress = $this->output->createProgressBar(count($validRows));
            $progress->start();
            
            foreach ($validRows as $rowData) {
                $progress->advance();
                
                $courseCode = $rowData['CODE'];
                $courseName = $rowData['COURSE'];
                $unit = (int)$rowData['UNIT'];
                $type = $rowData['TYPE'];
                $level = $this->normalizeLevel($rowData['LEVEL']);
                $semester = $this->normalizeSemester($rowData['SEMESTER']);
                $programCodes = array_map('trim', explode(',', $rowData['PROGRAM']));
                $prerequisites = array_filter(array_map('trim', explode(',', $rowData['PREREQUISITE'] ?? '')));
                
                // Create or update course
                $course = Course::firstOrNew(['code' => $courseCode]);
                $isNew = !$course->exists;
                
                if ($isNew) {
                    $course->fill([
                        'name' => $courseName,
                        'unit' => $unit,
                        'type' => $type,
                        'level' => $level,
                        'semester' => $semester,
                        'academic_year_id' => $academicYearId,
                    ]);
                    $course->save();
                    $importedCount++;
                } else {
                    // Check if any fields need updating
                    $needsUpdate = false;
                    if ($course->name !== $courseName || 
                        $course->unit != $unit || 
                        $course->type !== $type || 
                        $course->level !== $level || 
                        $course->semester !== $semester) {
                        $needsUpdate = true;
                    }
                    
                    if ($needsUpdate) {
                        $course->update([
                            'name' => $courseName,
                            'unit' => $unit,
                            'type' => $type,
                            'level' => $level,
                            'semester' => $semester,
                            'academic_year_id' => $academicYearId,
                        ]);
                        $updatedCount++;
                    } else {
                        $skippedCount++;
                    }
                }
                
                // Associate programs
                $programIds = [];
                foreach ($programCodes as $programCode) {
                    if (isset($programs[$programCode])) {
                        $programIds[] = $programs[$programCode];
                    }
                }
                
                if (!empty($programIds)) {
                    // Get current program IDs for this course
                    $currentProgramIds = $course->programs()->pluck('programs.id')->toArray();
                    
                    // Merge with new program IDs (avoid duplicates)
                    $allProgramIds = array_unique(array_merge($currentProgramIds, $programIds));
                    
                    // Sync programs
                    $course->programs()->sync($allProgramIds);
                }
                
                // Handle prerequisites
                if (!empty($prerequisites)) {
                    $prerequisiteIds = [];
                    foreach ($prerequisites as $prereqCode) {
                        $prereqCourse = Course::where('code', $prereqCode)->first();
                        if ($prereqCourse) {
                            $prerequisiteIds[] = $prereqCourse->id;
                        }
                    }
                    
                    if (!empty($prerequisiteIds)) {
                        $course->prerequisiteCourses()->sync($prerequisiteIds);
                    }
                }
            }
            
            $progress->finish();
            $this->newLine(2);
            
            // Commit the transaction
            DB::commit();
            
            $this->info("Import completed successfully!");
            $this->info("- {$importedCount} courses created");
            $this->info("- {$updatedCount} courses updated");
            $this->info("- {$skippedCount} courses skipped (no changes)");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Import failed: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            fclose($handle);
            return 1;
        }
        
        fclose($handle);
        return 0;
    }
    
    /**
     * Normalize level values
     */
    private function normalizeLevel($level)
    {
        return match ($level) {
            '1' => 'First Year',
            '2' => 'Second Year',
            '3' => 'Third Year',
            '4' => 'Fourth Year',
            '1st Year', 'First Year' => 'First Year',
            '2nd Year', 'Second Year' => 'Second Year',
            '3rd Year', 'Third Year' => 'Third Year',
            '4th Year', 'Fourth Year' => 'Fourth Year',
            default => $level,
        };
    }
    
    /**
     * Normalize semester values
     */
    private function normalizeSemester($semester)
    {
        return match ($semester) {
            '1' => 'First Semester',
            '2' => 'Second Semester',
            'summer' => 'Summer',
            '1st Semester', 'First Semester' => 'First Semester',
            '2nd Semester', 'Second Semester' => 'Second Semester',
            'Summer' => 'Summer',
            default => $semester,
        };
    }
}
