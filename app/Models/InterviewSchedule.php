<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'interview_date',
        'start_time',
        'end_time',
        'capacity',
        'venue',
        'campus_id',
    ];

    protected $casts = [
        'interview_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'capacity' => 'integer',
    ];
    
    /**
     * Get the campus this interview is scheduled at
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }
    
    /**
     * Get the applicants assigned to this interview schedule
     */
    public function applicantInterviewSchedules()
    {
        return $this->hasMany(ApplicantInterviewSchedule::class);
    }
}
