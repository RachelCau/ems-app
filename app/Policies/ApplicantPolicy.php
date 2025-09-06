<?php

namespace App\Policies;

use App\Models\Applicant;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApplicantPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view applicants');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Applicant $applicant): bool
    {
        return $user->hasPermissionTo('view applicants');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create applicants');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Applicant $applicant): bool
    {
        return $user->hasPermissionTo('edit applicants');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Applicant $applicant): bool
    {
        return $user->hasPermissionTo('delete applicants');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Applicant $applicant): bool
    {
        return $user->hasPermissionTo('edit applicants');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Applicant $applicant): bool
    {
        return $user->hasPermissionTo('delete applicants');
    }
} 