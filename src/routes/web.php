<?php

use App\Livewire\Admin\Content as AdminContent;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Library as AdminLibrary;
use App\Livewire\Admin\Users as AdminUsers;
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

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboard::class)->name('dashboard');
    Route::get('content', AdminContent::class)->name('content');
    Route::get('users', AdminUsers::class)->name('users');
    Route::get('library', AdminLibrary::class)->name('library');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
