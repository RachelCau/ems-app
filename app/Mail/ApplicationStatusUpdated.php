<?php

namespace App\Mail;

use App\Models\Applicant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public $applicant;
    public $newStatus;
    public $reasonData;

    /**
     * Create a new message instance.
     */
    public function __construct(Applicant $applicant, string $newStatus, ?array $reasonData = null)
    {
        $this->applicant = $applicant;
        $this->newStatus = $newStatus;
        $this->reasonData = $reasonData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->newStatus) {
            'approved' => 'Your Application Has Been Approved',
            'for entrance exam' => 'Entrance Examination Information',
            'for interview' => 'Interview Schedule Information',
            'for enrollment' => 'Enrollment Instructions',
            'Officially Enrolled' => 'Congratulations! You Are Officially Enrolled',
            'declined' => 'Application Status Update - Action Required',
            'pending' => 'Application Received',
            default => 'Your Application Status Has Been Updated',
        };
        
        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Determine which view to use based on the status
        $view = match ($this->newStatus) {
            'approved' => 'emails.applicant.application-approved',
            'for entrance exam' => 'emails.applicant.application-for-entrance-exam',
            'for interview' => match ($this->reasonData['reason_type'] ?? '') {
                'exam_passed_with_interview', 'exam_passed_no_interview' => 'emails.applicant.application-exam-passed',
                default => 'emails.applicant.application-for-interview',
            },
            'declined' => match ($this->reasonData['reason_type'] ?? '') {
                'exam_failed' => 'emails.applicant.application-exam-failed',
                default => 'emails.applicant.application-declined',
            },
            'for enrollment' => 'emails.applicant.application-for-enrollment',
            'pending' => 'emails.applicant.application-pending',
            default => 'emails.applicant.application-status-updated', // Fallback to the original template
        };
        
        // For entrance exam, check if we have exam data and prepare it
        $examScheduleData = null;
        if ($this->newStatus === 'for entrance exam' && $this->reasonData) {
            // Create an object to mimic the ExamSchedule object properties the template expects
            $examScheduleData = (object) [
                'exam_date' => isset($this->reasonData['exam_date']) ? 
                    new \Carbon\Carbon($this->reasonData['exam_date']) : now()->addDays(7),
                'start_time' => isset($this->reasonData['start_time']) ? 
                    $this->reasonData['start_time'] : '08:00 AM',
                'end_time' => isset($this->reasonData['end_time']) ? 
                    $this->reasonData['end_time'] : '10:00 AM',
                'room' => (object) [
                    'name' => is_string($this->reasonData['room']) ? $this->reasonData['room'] : 'To be determined',
                    'building' => $this->reasonData['venue'] ?? 'Main Campus'
                ]
            ];
        }
        
        // For interview, check if we have interview data and prepare it
        $interviewScheduleData = null;
        if ($this->newStatus === 'for interview' && $this->reasonData && !in_array($this->reasonData['reason_type'] ?? '', ['exam_passed_with_interview', 'exam_passed_no_interview'])) {
            // Create an object to mimic the InterviewSchedule object properties the template expects
            $interviewScheduleData = (object) [
                'interview_date' => isset($this->reasonData['interview_date']) ? 
                    new \Carbon\Carbon($this->reasonData['interview_date']) : now()->addDays(7),
                'start_time' => isset($this->reasonData['start_time']) ? 
                    $this->reasonData['start_time'] : '09:00 AM',
                'end_time' => isset($this->reasonData['end_time']) ? 
                    $this->reasonData['end_time'] : '10:00 AM',
                'venue' => $this->reasonData['venue'] ?? 'Main Campus',
                'room' => (object) [
                    'building' => $this->reasonData['venue'] ?? 'Main Campus'
                ]
            ];
        }
        
        return new Content(
            view: $view,
            with: [
                'applicant' => $this->applicant,
                'newStatus' => $this->newStatus,
                'statusLabel' => $this->getStatusLabel(),
                'campus' => $this->applicant->campus->name,
                'program' => $this->applicant->desired_program,
                'reasonData' => $this->reasonData,
                'reasonType' => $this->reasonData ? $this->getReasonLabel() : null,
                'documentName' => $this->reasonData['document_name'] ?? null,
                'declineDetails' => $this->reasonData['details'] ?? null,
                'examSchedule' => $examScheduleData,
                'interviewSchedule' => $interviewScheduleData,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
    
    /**
     * Get the human-readable status label.
     */
    private function getStatusLabel(): string
    {
        return match ($this->newStatus) {
            'approved' => 'Approved',
            'for entrance exam' => 'For Entrance Exam',
            'for interview' => 'For Interview',
            'for enrollment' => 'For Enrollment',
            'declined' => 'Declined',
            'pending' => 'Pending',
            default => 'Updated',
        };
    }
    
    /**
     * Get the human-readable reason label.
     */
    private function getReasonLabel(): string
    {
        if (!$this->reasonData || !isset($this->reasonData['reason_type'])) {
            return 'Reason Not Specified';
        }
        
        return match ($this->reasonData['reason_type']) {
            'invalid_document' => 'Invalid Document',
            'incomplete_requirements' => 'Incomplete Requirements',
            'failed_entrance_exam' => 'Failed Entrance Exam',
            'failed_interview' => 'Failed Interview',
            'other' => 'Other',
            default => 'Reason Not Specified',
        };
    }
} 