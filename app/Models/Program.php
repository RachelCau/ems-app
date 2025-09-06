<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Program extends Model
{
    use HasFactory;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        // Set default ordering to avoid empty column name issues
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('name');
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'campus_id',
        'program_category_id',
        'description',
    ];

    protected $casts = [
        'campus_id' => 'array',
    ];

    /**
     * Get the campuses that this program belongs to.
     */
    public function campuses()
    {
        return $this->belongsToMany(Campus::class, 'campus_program')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
    
    /**
     * Get the primary campus for this program.
     */
    public function campus()
    {
        return $this->belongsToMany(Campus::class, 'campus_program')
            ->withPivot('is_primary')
            ->wherePivot('is_primary', true)
            ->first();
    }

    /**
     * Get the category that the program belongs to.
     */
    public function category()
    {
        return $this->belongsTo(ProgramCategory::class, 'program_category_id');
    }

    /**
     * Get the student enrollments for the program.
     */
    public function studentEnrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }
    
    /**
     * Get the courses that belong to the program.
     */
    public function courses()
    {
        return $this->belongsToMany(Course::class, 'program_course');
    }

    /**
     * Scope a query to include programs for a specific campus.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $campusId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCampus($query, $campusId)
    {
        return $query->whereJsonContains('campus_id', $campusId);
    }

    /**
     * Get courses for a specific campus.
     * 
     * @param int $campusId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function coursesForCampus($campusId)
    {
        if (!in_array($campusId, $this->campus_id ?? [])) {
            return collect();
        }
        
        return $this->courses;
    }
    
    /**
     * Get the departments that this program belongs to.
     */
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_program')
            ->withTimestamps()
            ->orderByRaw('departments.name');
    }
} 