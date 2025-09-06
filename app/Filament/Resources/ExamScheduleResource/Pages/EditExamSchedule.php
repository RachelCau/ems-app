<?php

namespace App\Filament\Resources\ExamScheduleResource\Pages;

use App\Filament\Resources\ExamScheduleResource;
use App\Models\ExamSchedule;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Room;

class EditExamSchedule extends EditRecord
{
    protected static string $resource = ExamScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
          
        ];
    }

    protected function beforeSave(): void
    {
        // Get the old values before saving
        if ($this->record->exists) {
            $oldValues = $this->record->getOriginal();
            
            // Check if room is changed
            if (isset($this->data['room_id']) && $oldValues['room_id'] != $this->data['room_id']) {
                // Set capacity based on new room's capacity if not explicitly set
                $room = Room::find($this->data['room_id']);
                if ($room && (!isset($this->data['capacity']) || $this->data['capacity'] == $oldValues['capacity'])) {
                    $this->data['capacity'] = $room->capacity;
                }
            }
        }

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
            ->where('exam_date', $data['exam_date'])
            ->where('id', '!=', $this->record->id); // Exclude current record

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

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Exam Schedule Updated')
            ->body('The exam schedule has been updated successfully.');
    }
}
