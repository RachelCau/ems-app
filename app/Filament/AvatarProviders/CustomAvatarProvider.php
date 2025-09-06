<?php

namespace App\Filament\AvatarProviders;

use Filament\Facades\Filament;
use Filament\AvatarProviders\Contracts\AvatarProvider;

class CustomAvatarProvider implements AvatarProvider
{
    public function get(mixed $user): string
    {
        // Check if the user has an employee with an avatar
        if ($user->employee && $user->employee->avatar) {
            return asset('avatars/employees/' . $user->employee->avatar);
        }
        
        // Generate a default avatar using UI Avatars service with the user's name
        $name = urlencode($user->getFilamentName());
        return "https://ui-avatars.com/api/?name={$name}&color=FFFFFF&background=111827";
    }
} 