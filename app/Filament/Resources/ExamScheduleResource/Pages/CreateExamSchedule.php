<?php

namespace App\Filament\Resources\ExamScheduleResource\Pages;

use App\Filament\Resources\ExamScheduleResource;
use App\Models\ExamSchedule;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class CreateExamSchedule extends CreateRecord
{
    protected static string $resource = ExamScheduleResource::class;

    protected function beforeCreate(): void
    {
        // Validate that the room is available at the selected time
        $data = $this->data;
        
        if (empty($data['exam_date']) || empty($data['start_time']) || empty($data['end_time']) || empty($data['room_id'])) {
            return;
        }
        
        // Parse the times for better comparison
        $startTime = Carbon::parse($data['start_time']);
        $endTime = Carbon::parse($data['end_time']);
        
        // Check for overlapping exam schedules in the same room
        $query = ExamSchedule::query()
            ->where('room_id', $data['room_id'])
            ->where('exam_date', $data['exam_date']);
        
        // Get all schedules for this room and date
        $schedules = $query->get();
        
        foreach ($schedules as $schedule) {
            $existingStartTime = Carbon::parse($schedule->start_time);
            $existingEndTime = Carbon::parse($schedule->end_time);
            
            // Check for overlap scenarios:
            // 1. New start time is within an existing slot
            // 2. New end time is within an existing slot
            // 3. New slot completely contains an existing slot
            // 4. New slot is completely contained by an existing slot
            if (
                // New start time falls within existing slot
                ($startTime >= $existingStartTime && $startTime < $existingEndTime) ||
                // New end time falls within existing slot
                ($endTime > $existingStartTime && $endTime <= $existingEndTime) ||
                // New slot completely contains existing slot
                ($startTime <= $existingStartTime && $endTime >= $existingEndTime) ||
                // New slot is completely contained within existing slot
                ($startTime >= $existingStartTime && $endTime <= $existingEndTime)
            ) {
                $this->halt();
                
                $conflictMessage = "This room is already scheduled from " . 
                    $existingStartTime->format('h:i A') . " to " . 
                    $existingEndTime->format('h:i A') . " on the selected date.";
                
                Notification::make()
                    ->title('Time Slot Conflict')
                    ->body($conflictMessage)
                    ->danger()
                    ->send();
                
                return; // Stop after finding the first conflict
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Exam Schedule Created')
            ->body('The exam schedule has been created successfully.');
    }
}
