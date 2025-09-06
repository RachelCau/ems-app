<div class="p-4">
    <div class="mb-4">
        <h3 class="text-xl font-bold mb-2">Enrollment Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">Program</p>
                <p class="font-medium">{{ $record->program ? $record->program->name : $record->program_code }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Academic Year</p>
                <p class="font-medium">{{ $record->academicYear->name ?? 'Not specified' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Year Level</p>
                <p class="font-medium">
                    @switch($record->year_level)
                        @case(1)
                            1st Year
                            @break
                        @case(2)
                            2nd Year
                            @break
                        @case(3)
                            3rd Year
                            @break
                        @case(4)
                            4th Year
                            @break
                        @default
                            {{ $record->year_level }}th Year
                    @endswitch
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Semester</p>
                <p class="font-medium">{{ $record->semester }}{{ $record->semester == 1 ? 'st' : 'nd' }} Semester</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Status</p>
                <p class="font-medium">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                        @switch($record->status)
                            @case('active')
                                bg-green-100 text-green-800
                                @break
                            @case('pending')
                                bg-yellow-100 text-yellow-800
                                @break
                            @case('completed')
                                bg-blue-100 text-blue-800
                                @break
                            @case('dropped')
                                bg-red-100 text-red-800
                                @break
                            @case('withdrawn')
                                bg-red-100 text-red-800
                                @break
                            @default
                                bg-gray-100 text-gray-800
                        @endswitch
                    ">
                        {{ ucfirst($record->status) }}
                    </span>
                </p>
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-xl font-bold mb-2">Enrolled Courses</h3>
        @if($record->enrolledCourses->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Code</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Units</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($record->enrolledCourses as $course)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $course->course->code ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $course->course->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $course->course->unit ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        @switch($course->status)
                                            @case('enrolled')
                                                bg-blue-100 text-blue-800
                                                @break
                                            @case('completed')
                                                bg-green-100 text-green-800
                                                @break
                                            @case('dropped')
                                                bg-red-100 text-red-800
                                                @break
                                            @case('failed')
                                                bg-red-100 text-red-800
                                                @break
                                            @case('incomplete')
                                                bg-yellow-100 text-yellow-800
                                                @break
                                            @default
                                                bg-gray-100 text-gray-800
                                        @endswitch
                                    ">
                                        {{ ucfirst($course->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $course->grade ?? 'N/A' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="bg-gray-50 p-4 rounded">
                <p class="text-center text-gray-500">No courses have been enrolled yet.</p>
            </div>
        @endif
    </div>
</div> 