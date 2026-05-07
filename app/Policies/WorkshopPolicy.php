<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workshop;

class WorkshopPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Workshop $workshop): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isOrganizer() || $user->isAdmin();
    }

    public function update(User $user, Workshop $workshop): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->isOrganizer() && $user->organizer && $workshop->organizer_id === $user->organizer->id) return true;
        return false;
    }

    public function delete(User $user, Workshop $workshop): bool
    {
        if ($user->isAdmin()) return true;
        if ($user->isOrganizer() && $user->organizer && $workshop->organizer_id === $user->organizer->id) return true;
        return false;
    }
}