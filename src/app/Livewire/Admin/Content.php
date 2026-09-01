<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\EnsuresAdmin;
use App\Models\Sound;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Content extends Component
{
    use EnsuresAdmin, WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function deleteSound(Sound $sound): void
    {
        $this->ensureAdmin();

        $sound->delete();
    }

    public function render()
    {
        $sounds = Sound::query()
            ->with('screen.user')
            ->when($this->search !== '', fn ($query) => $query
                ->where('name', 'like', '%'.$this->search.'%')
                ->orWhereHas('screen.user', fn ($q) => $q->where('email', 'like', '%'.$this->search.'%')))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.content', ['sounds' => $sounds]);
    }
}
