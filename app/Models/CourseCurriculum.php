<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCurriculum extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'version',
        'program_id',
        'year_level',
        'semester',
        'academic_year_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'year_level' => 'integer',
        'semester' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the program that owns the curriculum.
     */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the academic year that this curriculum belongs to.
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the courses that are part of this curriculum.
     */
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'curriculum_course')
            ->withPivot('is_required', 'sort_order')
            ->withTimestamps();
    }
} 