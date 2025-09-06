<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Department extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];
    
    /**
     * The programs that belong to this department.
     */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'department_program')
            ->withTimestamps()
            ->orderByRaw('programs.name');
    }
    
    /**
     * Get the employees associated with this department.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
} 