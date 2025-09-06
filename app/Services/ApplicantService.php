<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\Student;
use App\Events\ApplicantEnrolled;
use Illuminate\Support\Facades\Log;
use App\Models\StudentEnrollment;
use App\Models\AcademicYear;

class ApplicantService
{
    /**
     * Update applicant status and trigger relevant events
     */
    public function updateStatus(Applicant $applicant, string $newStatus): bool
    {
        $oldStatus = $applicant->status;
        $applicant->status = $newStatus;
        
        // No longer generating and saving student_number in Applicant
        // Student numbers will only be generated in the CreateStudentFromApplicant listener
        
        $result = $applicant->save();
        
        Log::info('Applicant status updated', [
            'applicant_id' => $applicant->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);
        
        // If the applicant is officially enrolled, trigger the event
        if ($newStatus === 'Officially Enrolled') {
            // Check if this applicant already has a student enrollment
            $hasEnrollment = StudentEnrollment::where('applicant_id', $applicant->applicant_number)->exists();
            
            if ($hasEnrollment) {
                Log::info('Skipping ApplicantEnrolled event - enrollment already exists', [
                    'applicant_id' => $applicant->id,
                    'applicant_number' => $applicant->applicant_number,
                ]);
                return $result;
            }
            
            // Check if this applicant already has a student record
            $hasStudent = !empty($applicant->student_number) && 
                Student::where('student_number', $applicant->student_number)->exists();
            
            // If we have a student but no enrollment, we need to create the enrollment manually
            if ($hasStudent && !$hasEnrollment) {
                $student = Student::where('student_number', $applicant->student_number)->first();
                if ($student) {
                    Log::info('Creating enrollment for existing student', [
                        'applicant_id' => $applicant->id,
                        'student_id' => $student->id,
                    ]);
                    
                    // Create student enrollment manually
                    StudentEnrollment::create([
                        'student_id' => $student->id,
                        'applicant_id' => $applicant->applicant_number,
                        'program_id' => $applicant->program_id,
                        'program_code' => $applicant->program ? $applicant->program->code : null,
                        'campus_id' => $applicant->campus_id,
                        'academic_year_id' => AcademicYear::where('is_active', true)->first()?->id,
                        'year_level' => 1,
                        'semester' => 1,
                        'status' => 'enrolled',
                        'is_new_student' => true,
                        'remarks' => 'Manual enrollment',
                    ]);
                    
                    return $result;
                }
            }
            
            // Only dispatch the event if no student record or enrollment exists yet
            event(new ApplicantEnrolled($applicant));
            
            Log::info('ApplicantEnrolled event dispatched', [
                'applicant_id' => $applicant->id,
            ]);
        }
        
        return $result;
    }
    
    /**
     * Generate a student number based on campus and sequence
     * This is now only used by the CreateStudentFromApplicant listener
     */
    public function generateStudentNumber(Applicant $applicant): string
    {
        // Get campus from the applicant
        $campus = $applicant->campus;
        
        if (!$campus) {
            throw new \Exception('Cannot generate student number: Campus not found for applicant');
        }
        
        // Get the academic year (2-digit year)
        $academicYear = substr(date('Y'), -2);
        
        // Get the campus code (alphabetic - first 2 letters of campus name)
        $campusAlphaCode = strtoupper(substr($campus->name, 0, 2));
        
        // Get the campus code (numeric)
        $campusNumericCode = str_pad($campus->id, 2, '0', STR_PAD_LEFT);
        
        // Get the sequence number (4 digits)
        $enrolledCount = \App\Models\Student::where('campus_id', $campus->id)->count() + 1;
        $sequenceNumber = str_pad($enrolledCount, 4, '0', STR_PAD_LEFT);
        
        // Format: MAAABBCCCC where:
        // MA = Campus Alphabetic Code
        // AA = Academic Year
        // BB = Campus Numeric Code
        // CCCC = Sequence Number
        return $campusAlphaCode . $academicYear . $campusNumericCode . $sequenceNumber;
    }
    
    /**
     * Make sure a student record exists for an officially enrolled applicant
     * This can be called to ensure student record creation if the event listener failed
     */
    public function ensureStudentExists(Applicant $applicant): ?Student
    {
        if ($applicant->status !== 'Officially Enrolled') {
            Log::info('Cannot ensure student exists - applicant is not officially enrolled', [
                'applicant_id' => $applicant->id,
                'status' => $applicant->status,
            ]);
            return null;
        }
        
        // Try to find the student by number or email
        $student = null;
        if (!empty($applicant->student_number)) {
            $student = Student::where('student_number', $applicant->student_number)->first();
        }
        
        if (!$student) {
            $student = Student::where('email', $applicant->email)->first();
        }
        
        // If student exists, return it
        if ($student) {
            Log::info('Found existing student record for officially enrolled applicant', [
                'applicant_id' => $applicant->id,
                'student_id' => $student->id,
            ]);
            return $student;
        }
        
        // If not, trigger the event to create it
        event(new ApplicantEnrolled($applicant));
        
        Log::info('Triggered student creation for officially enrolled applicant', [
            'applicant_id' => $applicant->id,
        ]);
        
        // Try to get the student after event processing
        if (!empty($applicant->student_number)) {
            $student = Student::where('student_number', $applicant->student_number)->first();
        }
        
        if (!$student) {
            $student = Student::where('email', $applicant->email)->first();
        }
        
        return $student;
    }
} 