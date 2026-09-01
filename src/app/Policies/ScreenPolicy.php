<?php

namespace App\Policies;

use App\Models\Screen;
use App\Models\User;

class ScreenPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Screen $screen): bool
    {
        return $this->owns($user, $screen);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Screen $screen): bool
    {
        return $this->owns($user, $screen);
    }

    public function delete(User $user, Screen $screen): bool
    {
        return $this->owns($user, $screen);
    }

    private function owns(User $user, Screen $screen): bool
    {
        return $user->id === $screen->user_id;
    }
}
