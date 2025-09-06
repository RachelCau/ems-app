<x-filament::page>
    <div class="mb-6 p-2 bg-primary-50 dark:bg-gray-800 rounded-lg border border-primary-200 dark:border-gray-700">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold">Assign Applicants to Exam Schedule</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Date: {{ $record->exam_date->format('F j, Y') }} | 
                    Time: {{ \Carbon\Carbon::parse($record->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($record->end_time)->format('h:i A') }} | 
                    Room: {{ $record->room->name ?? 'N/A' }}
                </p>
            </div>
            <div class="text-right">
                <h3 class="font-medium">Capacity</h3>
                <p class="text-2xl font-bold 
                    {{ $record->approved_applicants_count >= $record->capacity ? 'text-danger-600' : 
                       ($record->approved_applicants_count >= ($record->capacity * 0.8) ? 'text-warning-600' : 'text-success-600') }}">
                    {{ $record->approved_applicants_count }} / {{ $record->capacity }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    @if($record->capacity - $record->approved_applicants_count <= 0)
                        No slots available
                    @else
                        {{ $record->capacity - $record->approved_applicants_count }} slots available
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{ $this->table }}
</x-filament::page> 