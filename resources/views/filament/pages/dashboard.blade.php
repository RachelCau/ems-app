<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Student Info Card -->
        <div class="col-span-1 bg-white rounded-lg shadow-md p-4">
            <div class="flex items-center mb-4">
                <div class="bg-primary-100 p-2 rounded-full mr-3">
                    <x-heroicon-o-user class="h-6 w-6 text-primary-500" />
                </div>
                <h2 class="text-lg font-bold">Student Information</h2>
            </div>
            <div class="space-y-2">
                @if(auth()->user()->student)
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-600">Student Number:</span>
                        <span class="font-medium">{{ auth()->user()->student->student_number }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-600">Name:</span>
                        <span class="font-medium">{{ auth()->user()->student->first_name }} {{ auth()->user()->student->last_name }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-600">Campus:</span>
                        <span class="font-medium">{{ auth()->user()->student->campus->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-medium text-emerald-600">{{ ucfirst(auth()->user()->student->status) }}</span>
                    </div>
                @else
                    <div class="text-center text-gray-500 py-4">
                        Student information not available
                    </div>
                @endif
            </div>
        </div>

        <!-- Current Enrollment Card -->
        <div class="col-span-2 bg-white rounded-lg shadow-md p-4">
            <div class="flex items-center mb-4">
                <div class="bg-primary-100 p-2 rounded-full mr-3">
                    <x-heroicon-o-academic-cap class="h-6 w-6 text-primary-500" />
                </div>
                <h2 class="text-lg font-bold">Current Enrollment</h2>
            </div>
            
            @if($currentEnrollment)
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-600">Academic Year:</span>
                        <span class="font-medium">{{ $currentEnrollment->academicYear->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-600">Semester:</span>
                        <span class="font-medium">{{ $currentEnrollment->semester }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-2">
                        <span class="text-gray-600">Program:</span>
                        <span class="font-medium">{{ $currentEnrollment->program->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-medium 
                            @if($currentEnrollment->status === 'enrolled') text-emerald-600 
                            @elseif($currentEnrollment->status === 'pending') text-amber-600 
                            @else text-red-600 @endif">
                            {{ ucfirst($currentEnrollment->status) }}
                        </span>
                    </div>
                </div>
                
                <h3 class="text-md font-bold mb-2">Enrolled Courses</h3>
                
                @if($currentEnrollment->enrolledCourses->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Code</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($currentEnrollment->enrolledCourses as $course)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $course->course->course_code ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $course->course->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                @if($course->status === 'enrolled') bg-green-100 text-green-800 
                                                @elseif($course->status === 'completed') bg-blue-100 text-blue-800 
                                                @else bg-red-100 text-red-800 @endif">
                                                {{ ucfirst($course->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $course->grade ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-gray-500 py-4 bg-gray-50 rounded">
                        No courses enrolled yet.
                    </div>
                @endif
            @else
                <div class="text-center text-gray-500 py-8 bg-gray-50 rounded">
                    No current enrollment found.
                </div>
            @endif
        </div>
    </div>

    <!-- Enrollment History -->
    <div class="mt-6 bg-white rounded-lg shadow-md p-4">
        <div class="flex items-center mb-4">
            <div class="bg-primary-100 p-2 rounded-full mr-3">
                <x-heroicon-o-clock class="h-6 w-6 text-primary-500" />
            </div>
            <h2 class="text-lg font-bold">Enrollment History</h2>
        </div>
        
        @if($enrollmentHistory->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Academic Year</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Semester</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Courses</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($enrollmentHistory as $enrollment)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $enrollment->academicYear->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $enrollment->semester }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $enrollment->program->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $enrollment->enrolledCourses->count() }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        @if($enrollment->status === 'enrolled') bg-green-100 text-green-800 
                                        @elseif($enrollment->status === 'pending') bg-yellow-100 text-yellow-800 
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ ucfirst($enrollment->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center text-gray-500 py-4 bg-gray-50 rounded">
                No enrollment history available.
            </div>
        @endif
    </div>
</x-filament-panels::page> 