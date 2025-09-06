<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\DB;

class Employee extends Model
{
    use HasFactory, HasRoles;

    /**
     * The guard name for the model
     *
     * @var string
     */
    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'employee_number',
        'first_name',
        'middle_name',
        'last_name',
        'sex',
        'mobile_number',
        'address',
        'employee_type',
        'department_id',
        'office_id',
        'role_id',
        'campus_id',
        'employee_status',
        'avatar',
    ];

    /**
     * Get the user that owns the employee.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the department that the employee belongs to.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the office that the employee belongs to.
     */
    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * Get the role that the employee belongs to.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the campus that the employee belongs to.
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the department where the employee serves as program head.
     */
    public function departmentHeaded()
    {
        return $this->hasOne(Department::class, 'program_head_id');
    }

    /**
     * Get the employee's full name.
     */
    public function getFullNameAttribute()
    {
        $middleInitial = $this->middle_name ? ' ' . substr($this->middle_name, 0, 1) . '.' : '';
        return $this->first_name . $middleInitial . ' ' . $this->last_name;
    }

    /**
     * Get all campuses that the employee is assigned to.
     */
    public function campuses()
    {
        return $this->belongsToMany(Campus::class);
    }

    /**
     * Get admissions evaluated by this employee.
     */
    // public function admissions()
    // {
    //     return $this->hasMany(Admission::class, 'evaluated_by');
    // }
    
    /**
     * Find an employee by their employee number
     *
     * @param string $employeeNumber
     * @return \App\Models\Employee|null
     */
    public static function findByEmployeeNumber(string $employeeNumber)
    {
        return static::where('employee_number', $employeeNumber)->first();
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // When an employee is saved, sync their role to the user
        static::saving(function ($employee) {
            // If role_id is an array, take the first value
            if (is_array($employee->role_id) && !empty($employee->role_id)) {
                $employee->role_id = $employee->role_id[0];
            }
        });
        
        static::saved(function ($employee) {
            if ($employee->user && $employee->role_id) {
                $role = Role::find($employee->role_id);
                if ($role) {
                    // Directly assign the role to the user using DB query for reliability
                    DB::table('model_has_roles')
                        ->where('model_id', $employee->user->id)
                        ->where('model_type', 'App\\Models\\User')
                        ->delete();
                    
                    DB::table('model_has_roles')->insert([
                        'role_id' => $role->id,
                        'model_id' => $employee->user->id,
                        'model_type' => 'App\\Models\\User',
                    ]);
                }
            }
        });
    }
} 