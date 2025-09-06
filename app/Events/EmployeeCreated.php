<?php

namespace App\Events;

use App\Models\Employee;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeeCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

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
     * Create a new event instance.
     *
     * @param  \App\Models\Employee  $employee
     * @param  string  $password
     * @return void
     */
    public function __construct(Employee $employee, string $password)
    {
        $this->employee = $employee;
        $this->password = $password;
    }
} 