<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    protected $commands = [
        // Register your commands here
        Commands\FixDocumentPaths::class,
        Commands\ImportDummyDocuments::class,
        Commands\FixRoleGuards::class,
        Commands\SyncEmployeeRolesToUsers::class,
        Commands\FixMissingStudentRecords::class,
        Commands\FixApplicant43::class,
        Commands\AssignRegistrarPermissions::class,
        Commands\AssignMisOfficerPermissions::class,
        Commands\FixApplicationDevelopmentProgram::class,
    ];
}
