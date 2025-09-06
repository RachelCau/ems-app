<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use ReflectionClass;

class UserProfileWidget extends Widget
{
    protected static string $view = 'filament.widgets.user-profile-widget';
    
    // Make this widget appear at the top of the dashboard
    protected static ?int $sort = -3;
    
    // Make it full width
    protected int | string | array $columnSpan = 'full';
    
    // Eager load relationships when widget is initialized
    public function mount(): void
    {
        // Get the authenticated user
        $user = Auth::user();
        
        // Ensure employee and role relationship is loaded
        if ($user && $user->user_type === 'employee') {
            // Get a fresh instance with loaded relationships
            $user = \App\Models\User::with('employee.role')->find($user->id);
            
            // Update the auth instance with the loaded relationships
            Auth::setUser($user);
        }
    }
    
    public function getUserRole(): string
    {
        $user = Auth::user();
        
        if (!$user) {
            return 'Guest';
        }
        
        // First check if user has a related employee with a role
        if ($user->user_type === 'employee' && $user->employee && $user->employee->role) {
            return ucfirst($user->employee->role->name);
        }
        
        // Fallback to user_type
        if (isset($user->user_type)) {
            return ucfirst($user->user_type);
        }
        
        return 'User';
    }
    
    public function getUserFullName(): string
    {
        $user = Auth::user();
        
        if (!$user) {
            return 'Guest';
        }
        
        // Check direct name property first (most common)
        if (isset($user->name) && !empty($user->name)) {
            return $user->name;
        }
        
        // Check for full name property
        if (isset($user->full_name) && !empty($user->full_name)) {
            return $user->full_name;
        }
        
        // Check first name + last name pattern
        if (isset($user->first_name) && isset($user->last_name)) {
            return $user->first_name . ' ' . $user->last_name;
        }
        
        // Check relationships
        if (isset($user->employee) && $user->employee) {
            return $user->employee->first_name . ' ' . $user->employee->last_name;
        }
        
        if (isset($user->applicant) && $user->applicant) {
            return $user->applicant->first_name . ' ' . $user->applicant->last_name;
        }
        
        if (isset($user->student) && $user->student) {
            return $user->student->first_name . ' ' . $user->student->last_name;
        }
        
        return $user->email ?? 'User';
    }
    
    public function getInitial(): string
    {
        $fullName = $this->getUserFullName();
        return strtoupper(substr($fullName, 0, 1) ?: 'U');
    }
    
    /**
     * Get the user's avatar URL.
     */
    public function getAvatarUrl(): ?string
    {
        $user = Auth::user();
        
        if (!$user) {
            return null;
        }
        
        // Check if user has an employee with avatar
        if ($user->employee && $user->employee->avatar) {
            return asset('avatars/employees/' . $user->employee->avatar);
        }
        
        // Generate a default avatar using UI Avatars service
        $name = urlencode($this->getUserFullName());
        return "https://ui-avatars.com/api/?name={$name}&color=FFFFFF&background=111827";
    }
    
    public function getCurrentDate(): string
    {
        return date('l, F j, Y');
    }
    
    public function getLastLoginTime(): string
    {
        $user = Auth::user();
        
        if (!$user || !$user->last_login_at) {
            return 'First time login';
        }
        
        // Convert to Philippine Standard Time (UTC+8)
        $lastLogin = $user->last_login_at->setTimezone('Asia/Manila');
        
        if ($lastLogin->isToday()) {
            return 'Today at ' . $lastLogin->format('g:i A');
        } elseif ($lastLogin->isYesterday()) {
            return 'Yesterday at ' . $lastLogin->format('g:i A');
        } elseif ($lastLogin->isCurrentWeek()) {
            return $lastLogin->format('l') . ' at ' . $lastLogin->format('g:i A');
        } else {
            return $lastLogin->format('M j, Y') . ' at ' . $lastLogin->format('g:i A');
        }
    }
} 