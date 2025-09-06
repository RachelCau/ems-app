<?php

namespace App\Policies;

use App\Models\ExamQuestion;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExamQuestionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view exam schedules');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ExamQuestion $examQuestion): bool
    {
        return $user->hasPermissionTo('view exam schedules');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create exam schedules');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ExamQuestion $examQuestion): bool
    {
        return $user->hasPermissionTo('edit exam schedules');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ExamQuestion $examQuestion): bool
    {
        return $user->hasPermissionTo('delete exam schedules');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ExamQuestion $examQuestion): bool
    {
        return $user->hasPermissionTo('edit exam schedules');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ExamQuestion $examQuestion): bool
    {
        return $user->hasPermissionTo('delete exam schedules');
    }
} 