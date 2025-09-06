<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantInterviewSchedule extends Model
{
    use HasFactory;
    
    // Explicitly set the table name
    protected $table = 'applicant_interview_schedule';
    
    protected $fillable = [
        'applicant_id',
        'interview_schedule_id',
        'status',
    ];
    
    /**
     * Get the applicant for this schedule assignment
     */
    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
    
    /**
     * Get the interview schedule this is assigned to
     */
    public function interviewSchedule()
    {
        return $this->belongsTo(InterviewSchedule::class);
    }
}
