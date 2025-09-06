<?php

namespace App\Mail;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeCredentials extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The employee instance.
     *
     * @var \App\Models\Employee
     */
    public $employee;

    /**
     * The generated password.
     *
     * @var string
     */
    public $password;

    /**
     * Create a new message instance.
     */
    public function __construct(Employee $employee, string $password)
    {
        // Ensure the employee has the user relation loaded
        $this->employee = $employee->load('user');
        $this->password = $password;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Employee Account Credentials',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.employee.credentials',
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