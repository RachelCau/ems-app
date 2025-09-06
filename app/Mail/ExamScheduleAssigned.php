<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Applicant;
use App\Models\ExamSchedule;

class ExamScheduleAssigned extends Mailable
{
    use Queueable, SerializesModels;

    public $applicant;
    public $examSchedule;

    /**
     * Create a new message instance.
     */
    public function __construct(Applicant $applicant, ExamSchedule $examSchedule)
    {
        $this->applicant = $applicant;
        $this->examSchedule = $examSchedule;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Exam Schedule Assignment Notification',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.exam-schedule-assigned',
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