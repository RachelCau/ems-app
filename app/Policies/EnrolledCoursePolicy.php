<?php

namespace App\Policies;

use App\Models\EnrolledCourse;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EnrolledCoursePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view enrolled courses');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EnrolledCourse $enrolledCourse): bool
    {
        return $user->hasPermissionTo('view enrolled courses');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create enrolled courses');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EnrolledCourse $enrolledCourse): bool
    {
        return $user->hasPermissionTo('edit enrolled courses');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EnrolledCourse $enrolledCourse): bool
    {
        return $user->hasPermissionTo('delete enrolled courses');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, EnrolledCourse $enrolledCourse): bool
    {
        return $user->hasPermissionTo('edit enrolled courses');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, EnrolledCourse $enrolledCourse): bool
    {
        return $user->hasPermissionTo('delete enrolled courses');
    }
} 