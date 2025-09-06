<?php

namespace App\Listeners;

use App\Events\ApplicantStatusChanged;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ProcessApplicantEnrollment
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ApplicantStatusChanged $event): void
    {
        // Process if the new status is "for enrollment" or "officially enrolled"
        if ($event->newStatus !== 'for enrollment' && $event->newStatus !== 'officially enrolled') {
            return;
        }

        $applicant = $event->applicant;

        Log::info('Processing applicant enrollment status change', [
            'applicant_id' => $applicant->id,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus
        ]);

        // For "for enrollment" status, we don't need to do anything special
        if ($event->newStatus === 'for enrollment') {
            return;
        }

        try {
            DB::beginTransaction();
            
            // Check if a student enrollment already exists for this applicant
            $existingEnrollment = StudentEnrollment::where('applicant_id', $applicant->applicant_number)->first();
            
            if ($existingEnrollment) {
                // If status is "officially enrolled" and we have an existing enrollment,
                // make sure the student's user account has the right username
                if ($event->newStatus === 'officially enrolled' && $existingEnrollment->student) {
                    $student = $existingEnrollment->student;
                    
                    if ($student->user && $student->student_number && $student->user->username !== $student->student_number) {
                        $student->user->username = $student->student_number;
                        $student->user->user_type = 'student';
                        $student->user->save();
                        
                        Log::info('Updated student username', [
                            'student_id' => $student->id,
                            'username' => $student->student_number
                        ]);
                    }
                }
                
                Log::info('Student enrollment already exists for applicant - skipping auto-enrollment', [
                    'applicant_id' => $applicant->id,
                    'applicant_number' => $applicant->applicant_number,
                    'enrollment_id' => $existingEnrollment->id
                ]);
                
                DB::commit();
                return;
            }
            
            // Since we want to prioritize manual enrollment, we will NOT automatically 
            // create student enrollments here anymore. The enrollment should happen
            // through the manual "Enroll as Student" action only.
            
            Log::info('Skipping auto-enrollment process - waiting for manual enrollment', [
                'applicant_id' => $applicant->id,
                'applicant_number' => $applicant->applicant_number
            ]);
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error during enrollment status processing: ' . $e->getMessage(), [
                'applicant_id' => $applicant->id,
                'exception' => $e
            ]);
        }
    }
} 