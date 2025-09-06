<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\EmployeeCreated;
use App\Listeners\SendEmployeeCredentialsNotification;
use App\Events\ApplicationStatusChanged;
use App\Listeners\SendApplicationStatusNotification;
use App\Events\ApplicantEnrolled;
use App\Listeners\CreateStudentFromApplicant;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        EmployeeCreated::class => [
            SendEmployeeCredentialsNotification::class,
        ],
        ApplicationStatusChanged::class => [
            SendApplicationStatusNotification::class,
        ],
        ApplicantEnrolled::class => [
            CreateStudentFromApplicant::class,
        ],
        'App\Events\ApplicantStatusChanged' => [
            'App\Listeners\ProcessApplicantEnrollment',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
