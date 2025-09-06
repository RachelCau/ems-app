<?php

namespace App\Http\Controllers;

use App\Models\EnrolledCourse;
use App\Models\StudentEnrollment;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentEnrollmentController extends Controller
{
    /**
     * Display a listing of student enrollments.
     */
    public function index()
    {
        $enrollments = StudentEnrollment::with('student', 'program', 'academicYear', 'campus')
            ->paginate(10);
        
        return view('enrollments.index', compact('enrollments'));
    }

    /**
     * Show the form for creating a new enrollment.
     */
    public function create()
    {
        return view('enrollments.create');
    }

    /**
     * Store a newly created enrollment in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'campus_id' => 'required|exists:campuses,id',
            'program_id' => 'required|exists:programs,id',
            'semester' => ['required', Rule::in(['First Semester', 'Second Semester', 'Summer'])],
            'status' => ['required', Rule::in(['enrolled', 'dropped', 'pending', 'withdrawn'])],
        ]);

        $enrollment = StudentEnrollment::create($validated);

        return redirect()->route('enrollments.show', $enrollment)
            ->with('success', 'Enrollment created successfully.');
    }

    /**
     * Display the specified enrollment.
     */
    public function show(StudentEnrollment $enrollment)
    {
        $enrollment->load('student', 'program', 'academicYear', 'campus', 'enrolledCourses.course');
        
        return view('enrollments.show', compact('enrollment'));
    }

    /**
     * Show the form for editing the specified enrollment.
     */
    public function edit(StudentEnrollment $enrollment)
    {
        return view('enrollments.edit', compact('enrollment'));
    }

    /**
     * Update the specified enrollment in storage.
     */
    public function update(Request $request, StudentEnrollment $enrollment)
    {
        $validated = $request->validate([
            'academic_year_id' => 'sometimes|exists:academic_years,id',
            'campus_id' => 'sometimes|exists:campuses,id',
            'program_id' => 'sometimes|exists:programs,id',
            'semester' => ['sometimes', Rule::in(['First Semester', 'Second Semester', 'Summer'])],
            'status' => ['sometimes', Rule::in(['enrolled', 'dropped', 'pending', 'withdrawn'])],
        ]);

        $enrollment->update($validated);

        return redirect()->route('enrollments.show', $enrollment)
            ->with('success', 'Enrollment updated successfully.');
    }

    /**
     * Show form for adding courses to the enrollment.
     */
    public function addCoursesForm(StudentEnrollment $enrollment)
    {
        $availableCourses = Course::whereHas('programs', function ($query) use ($enrollment) {
            $query->where('programs.id', $enrollment->program_id);
        })->get();
        
        $enrolledCourseIds = $enrollment->enrolledCourses->pluck('course_id')->toArray();
        
        return view('enrollments.add_courses', compact('enrollment', 'availableCourses', 'enrolledCourseIds'));
    }

    /**
     * Add courses to student enrollment.
     */
    public function addCourses(Request $request, StudentEnrollment $enrollment)
    {
        $validated = $request->validate([
            'course_ids' => 'required|array',
            'course_ids.*' => 'exists:courses,id'
        ]);

        DB::beginTransaction();
        
        try {
            foreach ($validated['course_ids'] as $courseId) {
                // Check if already enrolled
                $exists = $enrollment->enrolledCourses()
                    ->where('course_id', $courseId)
                    ->exists();
                
                if (!$exists) {
                    $enrollment->enrolledCourses()->create([
                        'course_id' => $courseId,
                        'status' => 'enrolled'
                    ]);
                }
            }
            
            DB::commit();
            return redirect()->route('enrollments.show', $enrollment)
                ->with('success', 'Courses added to enrollment successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Failed to add courses: ' . $e->getMessage());
        }
    }

    /**
     * Update the status or grade of an enrolled course.
     */
    public function updateEnrolledCourse(Request $request, EnrolledCourse $enrolledCourse)
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['enrolled', 'dropped', 'completed'])],
            'grade' => 'sometimes|nullable|string',
        ]);

        $enrolledCourse->update($validated);

        return redirect()->route('enrollments.show', $enrolledCourse->studentEnrollment)
            ->with('success', 'Course updated successfully.');
    }

    /**
     * Remove an enrolled course from the enrollment.
     */
    public function removeEnrolledCourse(EnrolledCourse $enrolledCourse)
    {
        $enrollment = $enrolledCourse->studentEnrollment;
        $enrolledCourse->delete();

        return redirect()->route('enrollments.show', $enrollment)
            ->with('success', 'Course removed from enrollment.');
    }
} 