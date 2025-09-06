<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentEnrollment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'applicant_id',
        'program_id',
        'program_code',
        'remarks',
        'status',
        'academic_year_id',
        'campus_id',
        'year_level',
        'semester',
        'is_new_student',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'remarks' => 'string',
    ];

    /**
     * Get the applicant associated with this enrollment.
     */
    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }

    /**
     * Get the enrolled courses for this enrollment.
     */
    public function enrolledCourses()
    {
        return $this->hasMany(EnrolledCourse::class);
    }

    /**
     * Get the program associated with this enrollment based on program_code.
     */
    public function program()
    {
        return $this->belongsTo(Program::class, 'program_code', 'code');
    }
    
    /**
     * Get the effective program code, checking multiple sources.
     * 
     * @return string|null
     */
    public function getEffectiveProgramCode()
    {
        // First check if enrollment has a program_code
        if (!empty($this->program_code)) {
            return $this->program_code;
        }
        
        // Try to get from program relationship if it exists
        if ($this->program_id && $this->program) {
            return $this->program->code;
        }
        
        // Then check if the applicant has a desired_program
        if ($this->applicant && !empty($this->applicant->desired_program)) {
            return $this->applicant->desired_program;
        }
        
        // If applicant has a direct program relationship
        if ($this->applicant && $this->applicant->program && $this->applicant->program->code) {
            return $this->applicant->program->code;
        }
        
        // Finally check if the applicant has a student with a program_code
        if ($this->applicant && $this->applicant->student) {
            if (!empty($this->applicant->student->program_code)) {
                return $this->applicant->student->program_code;
            }
            
            // Or if student has program relationship
            if ($this->applicant->student->program) {
                return $this->applicant->student->program->code;
            }
        }
        
        return null;
    }

    /**
     * Get the student associated with this enrollment.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
} 