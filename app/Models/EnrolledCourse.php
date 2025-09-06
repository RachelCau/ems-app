<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnrolledCourse extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_enrollment_id',
        'student_number',
        'course_id',
        'status',
        'grade',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Get the student enrollment this course belongs to.
     */
    public function studentEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class);
    }

    /**
     * Get the student associated with this enrolled course.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }

    /**
     * Get the course details.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
} 