<?php

namespace App\Livewire\Screens;

use App\Models\Screen;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Rule;
use Livewire\Component;

class Index extends Component
{
    #[Rule(Screen::NAME_RULES)]
    public string $newScreenName = '';

    public bool $showCreateForm = false;

    public function createScreen(): void
    {
        $this->validate();

        $user = Auth::user();

        $screen = $user->screens()->create([
            'name' => $this->newScreenName,
            'sort_order' => ($user->screens()->max('sort_order') ?? -1) + 1,
        ]);

        $this->reset('newScreenName', 'showCreateForm');

        $this->redirectRoute('screens.manage', $screen, navigate: true);
    }

    public function deleteScreen(Screen $screen): void
    {
        $this->authorize('delete', $screen);

        $screen->deleteWithSounds();
    }

    public function render()
    {
        return view('livewire.screens.index', [
            'screens' => Auth::user()->screens()->withCount('sounds')->get(),
        ]);
    }
}
