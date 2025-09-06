<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ExamSchedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'exam_date',
        'start_time',
        'end_time',
        'room_id',
        'capacity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'exam_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'capacity' => 'integer',
    ];

    /**
     * Get the applicant exam schedules for this exam schedule.
     */
    public function applicantExamSchedules(): HasMany
    {
        return $this->hasMany(ApplicantExamSchedule::class);
    }

    /**
     * Get the exam questions for this exam schedule.
     */
    public function examQuestion(): HasOne
    {
        return $this->hasOne(ExamQuestion::class);
    }

    /**
     * Get the room for this exam schedule.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the count of approved CHED applicants for this exam schedule.
     */
    public function getApprovedApplicantsCountAttribute(): int
    {
        // Try to get CHED program category without relying on the model import
        $chedCategoryId = DB::table('program_categories')
            ->where('name', 'CHED')
            ->orWhere('name', 'like', '%CHED%')
            ->value('id');

        return $this->applicantExamSchedules()
            ->whereHas('applicant', function ($query) use ($chedCategoryId) {
                $query->where('application_status', 'approved')
                    ->where(function ($query) use ($chedCategoryId) {
                        // Check program relation first
                        $query->whereHas('program', function ($query) use ($chedCategoryId) {
                            if ($chedCategoryId) {
                                $query->where('program_category_id', $chedCategoryId);
                            } else {
                                // Fallback to direct check when category ID can't be found
                                $query->whereHas('category', function ($query) {
                                    $query->where('name', 'CHED')
                                        ->orWhere('name', 'like', '%CHED%');
                                });
                            }
                        })
                            // Fallback to program_category field
                            ->orWhere('program_category', 'CHED')
                            ->orWhere('program_category', 'like', '%CHED%');
                    });
            })
            ->count();
    }
}
