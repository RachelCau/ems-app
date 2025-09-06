<?php

namespace App\Mail;

use App\Models\Applicant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Campus;

class ApplicantOfficiallyEnrolled extends Mailable
{
    use Queueable, SerializesModels;

    public $applicant;
    public $studentNumber;
    public $programName;

    /**
     * Create a new message instance.
     */
    public function __construct(Applicant $applicant, string $studentNumber, string $programName)
    {
        $this->applicant = $applicant;
        $this->studentNumber = $studentNumber;
        $this->programName = $programName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Bulacan Polytechnic College - Official Enrollment Confirmation',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Add safety checks for null values
        $campus = $this->applicant->campus ? $this->applicant->campus->name : 'Main Campus';
        $academicYear = $this->applicant->academicYear ? $this->applicant->academicYear->name : date('Y');
        
        return new Content(
            view: 'emails.applicant.applicant-officially-enrolled',
            with: [
                'applicant' => $this->applicant,
                'studentNumber' => $this->studentNumber,
                'campus' => $campus,
                'programName' => $this->programName,
                'academicYear' => $academicYear,
                'status' => 'Officially Enrolled',
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
}
