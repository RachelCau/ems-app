<?php

namespace App\Policies;

use App\Models\AdmissionDocument;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdmissionDocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view admission documents');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AdmissionDocument $admissionDocument): bool
    {
        return $user->hasPermissionTo('view admission documents');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create admission documents');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AdmissionDocument $admissionDocument): bool
    {
        return $user->hasPermissionTo('edit admission documents');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AdmissionDocument $admissionDocument): bool
    {
        return $user->hasPermissionTo('delete admission documents');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AdmissionDocument $admissionDocument): bool
    {
        return $user->hasPermissionTo('edit admission documents');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AdmissionDocument $admissionDocument): bool
    {
        return $user->hasPermissionTo('delete admission documents');
    }
} 