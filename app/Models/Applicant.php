<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use App\Events\ApplicantStatusChanged;

class Applicant extends Model
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
        'academic_year_id',
        'applicant_number',
        'student_number',
        'last_name',
        'first_name',
        'middle_name',
        'suffix',
        'dateofbirth',
        'sex',
        'address',
        'zip',
        'province_id',
        'city_id',
        'barangay_id',
        'mobile',
        'landline',
        'email',
        'father_name',
        'father_mobile',
        'mother_name',
        'mother_mobile',
        'guardian_name',
        'guardian_address',
        'guardian_mobile',
        'school_year',
        'school_type',
        'school_name',
        'school_address',
        'strand',
        'grade',
        'program_category',
        'program_id',
        'desired_program',
        'transferee',
        'status',
        'enrollment_status',
    ];

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * Get the full name attribute.
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
     * Get the applicant name with number attribute.
     */
    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->full_name . ' (' . $this->applicant_number . ')'
        );
    }

    /**
     * Get the user that owns the applicant.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the campus that the applicant belongs to.
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the academic year that the applicant belongs to.
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the program that the applicant is applying for.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the province relationship.
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Get the city relationship.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the barangay relationship.
     */
    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    /**
     * Get the admission documents for this applicant.
     */
    public function admissionDocuments()
    {
        return $this->hasMany(AdmissionDocument::class);
    }

    /**
     * Get the exam schedules for this applicant.
     */
    public function examSchedules()
    {
        return $this->hasMany(ApplicantExamSchedule::class);
    }

    /**
     * Get all applicant exam schedules directly.
     */
    public function applicantExamSchedules()
    {
        return $this->hasMany(ApplicantExamSchedule::class);
    }

    /**
     * Get all applicant interview schedules directly.
     */
    public function applicantInterviewSchedules()
    {
        return $this->hasMany(ApplicantInterviewSchedule::class);
    }

    /**
     * Get the student record for this applicant.
     * Connects via email or student_number.
     */
    public function student()
    {
        // First try to find by student_number if it exists
        if (!empty($this->student_number)) {
            $student = Student::where('student_number', $this->student_number)->first();
            if ($student) {
                return $this->belongsTo(Student::class, 'student_number', 'student_number');
            }
        }
        
        // If no student found by student_number, try by email
        return $this->belongsTo(Student::class, 'email', 'email');
    }

    /**
     * Get the student enrollment for this applicant.
     */
    public function studentEnrollment()
    {
        return $this->hasOne(StudentEnrollment::class);
    }

    /**
     * Generate a secure upload token for this applicant that can be used to access document upload page.
     * 
     * @param int $expiryHours Hours until the token expires, defaults to 48 hours
     * @return string The upload token
     */
    public function generateUploadToken(int $expiryHours = 48): string
    {
        // Generate a unique token
        $token = md5($this->id . $this->email . now()->timestamp . uniqid());
        
        // Store token in cache with expiry time
        \Illuminate\Support\Facades\Cache::put(
            "upload_token_{$token}", 
            [
                'applicant_id' => $this->id,
                'expires_at' => now()->addHours($expiryHours),
            ],
            now()->addHours($expiryHours)
        );
        
        return $token;
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::updating(function ($applicant) {
            // Check if status is changing
            if ($applicant->isDirty('status')) {
                $oldStatus = $applicant->getOriginal('status');
                $newStatus = $applicant->status;
                
                // Log status change
                \Illuminate\Support\Facades\Log::info('Applicant status changed', [
                    'applicant_id' => $applicant->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ]);
                
                // Dispatch event
                event(new ApplicantStatusChanged($applicant, $oldStatus, $newStatus));
            }
        });
    }
}