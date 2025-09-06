<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicantExamSchedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'applicant_id',
        'applicant_number',
        'exam_schedule_id',
        'status',
        'score',
        'remarks',
        'total_items',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'score' => 'decimal:2',
    ];

    /**
     * Get the applicant that owns the exam schedule.
     */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    /**
     * Get the exam schedule for this applicant.
     */
    public function examSchedule(): BelongsTo
    {
        return $this->belongsTo(ExamSchedule::class);
    }

    // Add the getApplicantNumberAttribute method to get the applicant number through the relationship
    public function getApplicantNumberAttribute()
    {
        return $this->applicant?->applicant_number;
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (ApplicantExamSchedule $examSchedule) {
            // If applicant_number is not set but applicant_id is, set it automatically
            if (empty($examSchedule->applicant_number) && $examSchedule->applicant_id) {
                $applicant = Applicant::find($examSchedule->applicant_id);
                if ($applicant) {
                    $examSchedule->applicant_number = $applicant->applicant_number;
                }
            }
        });
    }
} 