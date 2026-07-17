<?php

namespace App\Policies;

use App\Models\Tournament;
use App\Models\User;

class TournamentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tournament $tournament): bool
    {
        return $this->owns($user, $tournament);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Tournament $tournament): bool
    {
        return $this->owns($user, $tournament);
    }

    public function delete(User $user, Tournament $tournament): bool
    {
        return $this->owns($user, $tournament);
    }

    private function owns(User $user, Tournament $tournament): bool
    {
        return $user->id === $tournament->user_id;
    }
}
