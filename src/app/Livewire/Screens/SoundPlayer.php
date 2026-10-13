<?php

namespace App\Livewire\Screens;

use App\Http\Controllers\PwaController;
use App\Models\Screen;
use App\Models\Sound;
use Livewire\Component;

class SoundPlayer extends Component
{
    public Screen $screen;

    public Sound $sound;

    // Guests on private screens are already sent to login by the route's auth.unless-public middleware.
    public function mount(Screen $screen, Sound $sound): void
    {
        $this->authorize('view', $screen);

        $this->screen = $screen;
        $this->sound = $sound;
    }

    public function render()
    {
        $head = ['title' => $this->sound->name];

        if ($this->screen->is_public) {
            $head['manifest'] = route('sounds.manifest', [$this->screen, $this->sound]);
            $head['touchIcon'] = route('sounds.icon', [$this->screen, $this->sound, 192, 'v' => PwaController::iconVersion($this->sound->image_path ?? $this->screen->image_path)]);
        }

        return view('livewire.screens.sound-player')->layout('layouts.public', $head);
    }
}
