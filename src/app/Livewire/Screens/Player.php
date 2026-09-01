<?php

namespace App\Livewire\Screens;

use App\Models\Screen;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Player extends Component
{
    public Screen $screen;

    public function mount(Screen $screen): void
    {
        $this->authorize('view', $screen);

        $this->screen = $screen;
    }

    public function render()
    {
        return view('livewire.screens.player', [
            'sounds' => $this->screen->sounds()->get(),
        ]);
    }
}
