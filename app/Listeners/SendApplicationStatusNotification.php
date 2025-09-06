<?php

namespace App\Listeners;

use App\Events\ApplicationStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ApplicationStatusUpdated;
use App\Models\Applicant;

class SendApplicationStatusNotification implements ShouldQueue
{
    use InteractsWithQueue;

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
    public function handle(ApplicationStatusChanged $event): void
    {
        // Log the event for debugging
        Log::info("ApplicationStatusChanged event received", [
            'applicant_id' => $event->applicant->id,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'has_reason_data' => !empty($event->reasonData),
            'reason_data' => $event->reasonData
        ]);
        
        // Only send notification if status has actually changed
        if ($event->oldStatus === $event->newStatus) {
            Log::info("Skipping notification - status unchanged", [
                'status' => $event->newStatus
            ]);
            return;
        }

        try {
            // Send specific email notification based on the new status
            $applicant = $event->applicant;
            $newStatus = $event->newStatus;
            
            // Check if applicant has an email address
            if (empty($applicant->email)) {
                Log::warning("Cannot send status update email: Applicant #{$applicant->id} has no email address");
                return;
            }
            
            Log::info("Preparing to send {$newStatus} email to {$applicant->email}", [
                'reason_data' => $event->reasonData
            ]);

            // Handle Officially Enrolled status
            if ($newStatus === 'Officially Enrolled') {
                // Add status debugging
                Log::info("Processing 'Officially Enrolled' status", [
                    'applicant_id' => $applicant->id,
                    'status_exact' => $newStatus,
                    'status_length' => strlen($newStatus),
                    'expected_length' => strlen('Officially Enrolled')
                ]);
                
                // Generate the student number based on specified format
                $studentNumber = $this->generateStudentNumber($applicant);
                
                // Get the program name
                $programName = $applicant->program ? $applicant->program->name : $applicant->desired_program;
                
                // Queue the officially enrolled email
                Mail::to($applicant->email)
                    ->queue(new \App\Mail\ApplicantOfficiallyEnrolled($applicant, $studentNumber, $programName));
                
                // Log success
                Log::info("Official enrollment notification email queued for {$applicant->email}", [
                    'student_number' => $studentNumber,
                    'program' => $programName
                ]);
                
                return;
            }
            
            // Log specific details for status updates that include schedules
            if ($newStatus === 'for interview' && !empty($event->reasonData)) {
                Log::info("Interview schedule details:", [
                    'interview_date' => $event->reasonData['interview_date'] ?? 'Not provided',
                    'start_time' => $event->reasonData['start_time'] ?? 'Not provided',
                    'end_time' => $event->reasonData['end_time'] ?? 'Not provided',
                    'venue' => $event->reasonData['venue'] ?? 'Not provided',
                ]);
            }
            
            // Queue the email to be sent using the appropriate template
            Mail::to($applicant->email)
                ->queue(new ApplicationStatusUpdated($applicant, $newStatus, $event->reasonData));
                
            // Log success for tracking
            Log::info("Application status update email queued for {$applicant->email} for status: {$newStatus}");
            
        } catch (\Exception $e) {
            // Log any errors that occur during email sending
            Log::error("Failed to send status update email: " . $e->getMessage(), [
                'applicant_id' => $event->applicant->id,
                'new_status' => $event->newStatus,
                'exception' => $e
            ]);
        }
    }
    
    /**
     * Generate a student number according to the specified format:
     * MA - Campus code (Alphabetic from campus)
     * 00 - Academic year (year enrolled)
     * 01 - Campus code (Numeric from campus)
     * 0000 - Auto-increment (based on queue when marked as Officially Enrolled)
     *
     * @param Applicant $applicant
     * @return string The generated student number
     */
    private function generateStudentNumber(Applicant $applicant): string
    {
        // Get the campus code
        $campus = $applicant->campus;
        
        // 1. MA - Campus code (Alphabetic) - First 2 letters of campus name or code from campus table
        $campusCode = strtoupper(substr($campus->name, 0, 2));
        
        // 2. 00 - Academic year (year enrolled) - Last 2 digits of current year
        $academicYear = substr(date('Y'), -2);
        
        // 3. 01 - Campus code (Numeric from campus) - Campus ID padded to 2 digits
        $campusNumericCode = str_pad($campus->id, 2, '0', STR_PAD_LEFT);
        
        // 4. 0000 - Auto-increment sequence
        // Count how many applicants from this campus have already been marked as 'Officially Enrolled'
        $enrolledCount = \App\Models\Applicant::where('campus_id', $campus->id)
            ->where('status', 'Officially Enrolled')
            ->count() + 1;
        $sequenceNumber = str_pad($enrolledCount, 4, '0', STR_PAD_LEFT);
        
        // Combine all parts to form the student number
        return $campusCode . $academicYear . $campusNumericCode . $sequenceNumber;
    }
} 