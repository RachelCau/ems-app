<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Course extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'unit',
        'prerequisites',
        'level',
        'semester',
        'type',
        'academic_year_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'prerequisites' => 'array',
        'unit' => 'integer',
    ];

    /**
     * Get the academic year that the course belongs to.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the programs that belong to the course.
     */
    public function programs()
    {
        return $this->belongsToMany(Program::class, 'program_course');
    }

    /**
     * Get prerequisite courses.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function prerequisiteCourses()
    {
        return $this->belongsToMany(Course::class, 'course_prerequisite', 'course_id', 'prerequisite_id');
    }

    /**
     * Get the enrolled instances of this course.
     */
    public function enrolledCourses()
    {
        return $this->hasMany(EnrolledCourse::class);
    }
} 