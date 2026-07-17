<?php

namespace App\Policies;

use App\Models\Registration;
use App\Models\User;

class RegistrationPolicy
{
    public function view(User $user, Registration $registration): bool
    {
        return $this->owns($user, $registration);
    }

    public function update(User $user, Registration $registration): bool
    {
        return $this->owns($user, $registration);
    }

    public function delete(User $user, Registration $registration): bool
    {
        return $this->owns($user, $registration);
    }

    private function owns(User $user, Registration $registration): bool
    {
        return $user->id === $registration->tournament->user_id;
    }
}
