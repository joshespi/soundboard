<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\EnsuresAdmin;
use App\Models\Sound;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
            $user->screens->each->deleteWithSounds();

            $user->delete();
        });
    }

    public function render()
    {
        $storageByUser = Cache::remember('admin.users.storage', now()->addMinutes(5), function () {
            return Sound::query()
                ->join('screens', 'screens.id', '=', 'sounds.screen_id')
                ->select('screens.user_id', 'sounds.file_path', 'sounds.image_path')
                ->get()
                ->groupBy('user_id')
                ->map(fn ($sounds) => Sound::sumStorageBytes($sounds));
        });

        $users = User::query()
            ->withCount(['screens', 'sounds'])
            ->orderBy('name')
            ->get()
            ->each(function (User $user) use ($storageByUser) {
                $user->storage_bytes = $storageByUser->get($user->id, 0);
            });

        return view('livewire.admin.users', ['users' => $users]);
    }
}
