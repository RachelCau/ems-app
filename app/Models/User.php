<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\HasName;

class User extends Authenticatable implements HasName
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

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
        'id',
        'username',
        'email',
        'password',
        'temp_password',
        'user_type',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'temp_password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'last_login_at' => 'datetime',
    ];

    /**
     * Get the student associated with the user.
     */
    public function student()
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Get the applicant associated with the user.
     */
    public function applicant()
    {
        return $this->hasOne(Applicant::class);
    }

    /**
     * Get the employee associated with the user.
     */
    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Get the name from related models (employee, applicant, or student)
     */
    public function getFilamentName(): string
    {
        if ($this->employee) {
            return $this->employee->first_name . ' ' . $this->employee->last_name;
        }
        
        if ($this->applicant) {
            return $this->applicant->first_name . ' ' . $this->applicant->last_name;
        }
        
        if ($this->student) {
            return $this->student->first_name . ' ' . $this->student->last_name;
        }
        
        return $this->email ?? 'User';
    }
    
    /**
     * Get the login identifier for the user.
     * This allows multiple login methods (username or email)
     */
    public function findForPassport($username)
    {
        return $this->where('username', $username)
            ->orWhere('email', $username)
            ->first();
    }

    /**
     * Get the URL of the user's avatar.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        // Check if the user has an employee with an avatar
        if ($this->employee && $this->employee->avatar) {
            return asset('avatars/employees/' . $this->employee->avatar);
        }
        
        // Generate a default avatar using UI Avatars service
        $name = urlencode($this->getFilamentName());
        return "https://ui-avatars.com/api/?name={$name}&color=FFFFFF&background=111827";
    }
}
