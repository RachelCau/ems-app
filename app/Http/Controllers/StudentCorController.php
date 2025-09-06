<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\AcademicYear;
use App\Models\StudentEnrollment;
use App\Models\EnrolledCourse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentCorController extends Controller
{
    /**
     * Generate PDF Certificate of Registration for a student
     */
    public function downloadPdf($studentId)
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
        
        // Set paper size to A4
        $pdf->setPaper('a4', 'portrait');
        
        // Generate filename with the required format: SURNAME-FIRSTNAME-SUFFIX-MIDDLENAME-PROGRAM CODE-YEAR LEVEL
        $lastName = strtoupper($student->last_name);
        $firstName = strtoupper($student->first_name);
        $suffix = strtoupper($student->suffix ?? '');
        $middleName = strtoupper($student->middle_name ?? '');
        $programCode = strtoupper($student->program_code);
        $yearLevel = $student->year_level;
        
        $filename = "{$lastName}-{$firstName}";
        
        // Only add suffix if it exists
        if (!empty($suffix)) {
            $filename .= "-{$suffix}";
        }
        
        // Only add middle name if it exists
        if (!empty($middleName)) {
            $filename .= "-{$middleName}";
        }
        
        $filename .= "-{$programCode}-{$yearLevel}.pdf";
        
        // Download the PDF
        return $pdf->download($filename);
    }
} 