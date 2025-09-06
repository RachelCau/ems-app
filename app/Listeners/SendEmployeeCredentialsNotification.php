<?php

namespace App\Listeners;

use App\Events\EmployeeCreated;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendEmployeeCredentialsNotification
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
    public function handle(EmployeeCreated $event): void
    {
        // Make sure to load the employee with its user relationship
        $employee = $event->employee->fresh(['user']);
        $user = $employee->user;
        $password = $event->password;

        if (!$user) {
            Log::error('Cannot send credentials email - user relationship not found', [
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number
            ]);
            return;
        }

        Log::info('Attempting to send credentials email', [
            'employee_number' => $employee->employee_number,
            'email' => $user->email,
        ]);

        try {
            Mail::to($user->email)->send(new \App\Mail\EmployeeCredentials($employee, $password));
            Log::info('Credentials email sent successfully to employee', [
                'employee_number' => $employee->employee_number,
                'email' => $user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send credentials email', [
                'employee_number' => $employee->employee_number,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
} 