<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\EnsuresAdmin;
use App\Models\Sound;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Users extends Component
{
    use EnsuresAdmin;

    public function deleteUser(User $user): void
    {
        $this->ensureAdmin();

        abort_if($user->id === Auth::id(), 403, "You can't delete your own account here.");

        DB::transaction(function () use ($user) {
            foreach ($user->screens as $screen) {
                $screen->sounds->each->delete();
                $screen->delete();
            }

            $user->delete();
        });
    }

    public function render()
    {
        $filesByUser = Sound::query()
            ->join('screens', 'screens.id', '=', 'sounds.screen_id')
            ->select('screens.user_id', 'sounds.file_path', 'sounds.image_path')
            ->get()
            ->groupBy('user_id');

        $users = User::query()
            ->withCount(['screens', 'sounds'])
            ->orderBy('name')
            ->get()
            ->each(function (User $user) use ($filesByUser) {
                $user->storage_bytes = Sound::sumStorageBytes($filesByUser->get($user->id, collect()));
            });

        return view('livewire.admin.users', ['users' => $users]);
    }
}
