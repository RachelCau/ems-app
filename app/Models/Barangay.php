<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Barangay extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'name',
        'code',
    ];

    /**
     * Get the city that owns the barangay.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
} 