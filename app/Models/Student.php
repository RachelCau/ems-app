<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Student extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'campus_id',
        'program_id',
        'program',
        'program_code',
        'student_number',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'sex',
        'mobile_number',
        'email',
        'student_status',
        'avatar',
        'address',
        'academic_year_id',
        'year_level',
        'semester',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'year_level' => 'integer',
        'semester' => 'integer',
    ];

    /**
     * Get the full name attribute (first name + last name + suffix)
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->first_name . ' ' . 
                ($this->middle_name ? substr($this->middle_name, 0, 1) . '. ' : '') . 
                $this->last_name . 
                ($this->suffix ? ' ' . $this->suffix : '')
        );
    }

    /**
     * Get the user that owns the student.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the campus that the student belongs to.
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the academic year that the student belongs to.
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the program that the student belongs to.
     */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get all enrolled courses for this student directly.
     * 
     * This provides direct access to enrolled courses using the student_number.
     */
    public function enrolledCourses()
    {
        return $this->hasMany(
            EnrolledCourse::class,
            'student_number',  // Foreign key on enrolled_courses
            'student_number'   // Local key on students
        )->with('course');  // Eager load the course details
    }
}
