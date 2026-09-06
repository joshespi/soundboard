<?php

namespace App\Livewire\Broadcast;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Setup extends Component
{
    /**
     * Everything on this page is browser state — audio devices, permissions, the live
     * graph — so there's nothing for the server to hold. The component exists to give
     * the walkthrough a route, a layout and the app's auth middleware.
     */
    public function render()
    {
        return view('livewire.broadcast.setup');
    }
}
