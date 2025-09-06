<?php

namespace App\Policies;

use App\Models\InterviewSchedule;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\HandlesAuthorization;

class InterviewSchedulePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view interview schedules');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, InterviewSchedule $interviewSchedule): bool
    {
        return $user->hasPermissionTo('view interview schedules');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create interview schedules');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InterviewSchedule $interviewSchedule): bool
    {
        return $user->hasPermissionTo('edit interview schedules');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InterviewSchedule $interviewSchedule): bool
    {
        return $user->hasPermissionTo('delete interview schedules');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, InterviewSchedule $interviewSchedule): bool
    {
        return $user->hasPermissionTo('edit interview schedules');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, InterviewSchedule $interviewSchedule): bool
    {
        return $user->hasPermissionTo('delete interview schedules');
    }
}
