<?php

namespace App\Livewire\Screens;

use App\Http\Controllers\PwaController;
use App\Models\Screen;
use Livewire\Component;

class Player extends Component
{
    public Screen $screen;

    // Guests on private screens are already sent to login by the route's auth.unless-public middleware.
    public function mount(Screen $screen): void
    {
        $this->authorize('view', $screen);

        $this->screen = $screen;
    }

    public function render()
    {
        $head = ['title' => $this->screen->name];

        if ($this->screen->is_public) {
            $head['manifest'] = route('screens.manifest', $this->screen);
            $head['touchIcon'] = route('screens.icon', [$this->screen, 192, 'v' => PwaController::iconVersion($this->screen->image_path)]);
        }

        $sounds = $this->screen->sounds()->get();

        return view('livewire.screens.player', [
            'sounds' => $sounds,
            // Video never goes through the audio engine; this is also the 1-9 shortcut order.
            'audioUrls' => $sounds->reject->is_video->pluck('url')->values(),
        ])->layout(auth()->check() ? 'layouts.app' : 'layouts.public', $head);
    }
}
