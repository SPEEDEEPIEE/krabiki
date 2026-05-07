<?php

namespace App\Policies;

use App\Models\Registration;
use App\Models\User;

class RegistrationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Registration $registration): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->id === $registration->participant_user_id) return true;
        if ($user->isOrganizer() && $user->organizer && $registration->workshop->organizer_id === $user->organizer->id) return true;
        return false;
    }

    public function create(User $user): bool
    {
        return $user->isParticipant();
    }

    public function updateStatus(User $user, Registration $registration): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->isOrganizer() && $user->organizer && $registration->workshop->organizer_id === $user->organizer->id) return true;
        return false;
    }

    public function delete(User $user, Registration $registration): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->id === $registration->participant_user_id) return true;
        return false;
    }
}