<?php

namespace App\Policies;

use App\Models\Sound;
use App\Models\User;

class SoundPolicy
{
    public function view(User $user, Sound $sound): bool
    {
        return $this->owns($user, $sound);
    }

    public function update(User $user, Sound $sound): bool
    {
        return $this->owns($user, $sound);
    }

    public function delete(User $user, Sound $sound): bool
    {
        return $this->owns($user, $sound);
    }

    private function owns(User $user, Sound $sound): bool
    {
        return $user->id === $sound->screen->user_id;
    }
}
