<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campus extends Model
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
    ];
    
    /**
     * Get primary employees assigned to this campus.
     */
    public function primaryEmployees()
    {
        return $this->hasMany(Employee::class);
    }
    
    /**
     * Get secondary employees that belong to the campus.
     */
    public function secondaryEmployees()
    {
        return $this->belongsToMany(Employee::class, 'campus_employee')
            ->withPivot('is_secondary')
            ->withTimestamps();
    }

    /**
     * Get the programs offered at this campus.
     */
    public function programs()
    {
        return $this->belongsToMany(Program::class, 'campus_program')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get the student enrollments for this campus.
     */
    public function studentEnrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }
    
    /**
     * Get the rooms for this campus.
     */
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
} 