<?php

namespace App\Policies;

use App\Models\Label;
use App\Models\User;

/**
 * Authorization policy for label operations.
 * Users can only manage their own labels.
 */
class LabelPolicy
{
    /**
     * Determine if the user can view any labels.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the label.
     */
    public function view(User $user, Label $label): bool
    {
        return $user->id === $label->user_id;
    }

    /**
     * Determine if the user can create labels.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can update the label.
     */
    public function update(User $user, Label $label): bool
    {
        return $user->id === $label->user_id;
    }

    /**
     * Determine if the user can delete the label.
     */
    public function delete(User $user, Label $label): bool
    {
        return $user->id === $label->user_id;
    }
}
