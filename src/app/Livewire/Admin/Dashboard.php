<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\EnsuresAdmin;
use App\Models\LibrarySound;
use App\Models\Screen;
use App\Models\Sound;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    use EnsuresAdmin;

    public function render()
    {
        $this->ensureAdmin();

        return view('livewire.admin.dashboard', $this->stats());
    }

    private function stats(): array
    {
        return Cache::remember('admin.stats', now()->addMinutes(5), function () {
            $files = Sound::query()->get(['file_path', 'image_path'])
                ->concat(LibrarySound::query()->get(['file_path', 'image_path']));

            $totalBytes = Sound::sumStorageBytes($files);

            return [
                'userCount' => User::count(),
                'screenCount' => Screen::count(),
                'soundCount' => Sound::count(),
                'librarySoundCount' => LibrarySound::count(),
                'totalBytes' => $totalBytes,
            ];
        });
    }
}
