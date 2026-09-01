<?php

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Livewire only runs route middleware (and `mount()`) on a component's
 * initial page load — a subsequent action call (e.g. a button click)
 * hydrates the component from its snapshot and invokes the method
 * directly, bypassing both. Mutating actions on admin-only components
 * must therefore check authorization themselves, not just in `mount()`.
 */
trait EnsuresAdmin
{
    protected function ensureAdmin(): void
    {
        abort_unless(Auth::user()?->is_admin, 403);
    }
}
