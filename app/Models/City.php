<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'province_id',
        'name',
        'is_city',
        'code',
    ];

    protected $casts = [
        'is_city' => 'boolean',
    ];

    /**
     * Get the province that owns the city.
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * Get the barangays for the city.
     */
    public function barangays(): HasMany
    {
        return $this->hasMany(Barangay::class);
    }
} 