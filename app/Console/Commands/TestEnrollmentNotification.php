<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Applicant;
use App\Events\ApplicationStatusChanged;
use Illuminate\Support\Facades\Log;

class TestEnrollmentNotification extends Command
{
    protected $signature = 'app:test-enrollment-notification {applicant_id}';
    protected $description = 'Test the enrollment notification for a specific applicant';

    public function handle()
    {
        $applicantId = $this->argument('applicant_id');
        $applicant = Applicant::find($applicantId);
        
        if (!$applicant) {
            $this->error("Applicant with ID {$applicantId} not found");
            return 1;
        }
        
        $this->info("Testing notification for applicant: {$applicant->full_name}");
        
        try {
            // Log the test
            Log::info("Manually testing enrollment notification", [
                'applicant_id' => $applicant->id,
                'current_status' => $applicant->status
            ]);
            
            // Store old status
            $oldStatus = $applicant->status;
            
            // Trigger the event
            $this->info("Triggering ApplicationStatusChanged event...");
            event(new ApplicationStatusChanged($applicant, $oldStatus, 'Officially Enrolled'));
            
            $this->info("Event triggered successfully. Check logs for details.");
            return 0;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error("Error testing enrollment notification: " . $e->getMessage(), [
                'applicant_id' => $applicant->id,
                'exception' => $e
            ]);
            return 1;
        }
    }
} 