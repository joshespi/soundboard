<?php

use App\Livewire\Screens\Manage;
use App\Livewire\Screens\Player;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('screens/{screen}/manage', Manage::class)->name('screens.manage');
    Route::get('screens/{screen}/play', Player::class)->name('screens.play');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
