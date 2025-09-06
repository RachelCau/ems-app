<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\StudentEnrollment;
use App\Models\EnrolledCourse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentPdfController extends Controller
{
    /**
     * Generate PDF Certificate of Registration for a student
     */
    public function generateCorPdf($studentId)
    {
        // Get the student record
        $student = Student::with('campus')->findOrFail($studentId);
        
        // Get current academic year
        $academicYear = AcademicYear::where('is_active', true)->first();
        
        // Get student enrolled courses
        $studentEnrollment = StudentEnrollment::where('student_id', $student->id)
            ->where('academic_year_id', $academicYear?->id)
            ->where('semester', $student->semester)
            ->first();

        $enrolledCourses = [];
        if ($studentEnrollment) {
            $enrolledCourses = EnrolledCourse::with('course')
                ->where('student_enrollment_id', $studentEnrollment->id)
                ->get();
        }
        
        // Generate PDF
        $pdf = PDF::loadView('pdf.cor', [
            'student' => $student,
            'academicYear' => $academicYear,
            'enrolledCourses' => $enrolledCourses,
        ]);
        
        // Generate filename with student number
        $filename = 'COR_' . $student->student_number . '.pdf';
        
        // Download the PDF
        return $pdf->download($filename);
    }
} 