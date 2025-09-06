<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Bootstrap the model and its traits.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($academicYear) {
            // If this academic year is being set to active
            if ($academicYear->is_active) {
                // Set all other academic years to inactive
                static::where('id', '!=', $academicYear->id)
                    ->update(['is_active' => false]);
            }
        });
    }

    /**
     * Get the enrollments for the academic year.
     */
    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }
} 