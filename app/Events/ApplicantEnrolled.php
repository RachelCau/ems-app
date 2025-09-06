<?php

namespace App\Events;

use App\Models\Applicant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicantEnrolled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The applicant instance.
     *
     * @var \App\Models\Applicant
     */
    public $applicant;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\Applicant  $applicant
     * @return void
     */
    public function __construct(Applicant $applicant)
    {
        $this->applicant = $applicant;
    }
} 