<?php

use App\Http\Controllers\PwaController;
use App\Livewire\Admin\Content as AdminContent;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Library as AdminLibrary;
use App\Livewire\Admin\Users as AdminUsers;
use App\Livewire\Broadcast\Setup as VoiceSetup;
use App\Livewire\Screens\Manage;
use App\Livewire\Screens\Player;
use App\Livewire\Screens\SoundPlayer;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('screens/{screen}/manage', Manage::class)->name('screens.manage');
    Route::get('voice', VoiceSetup::class)->name('voice.setup');
});

// Public screens are open to anyone with the link; private ones send guests to login.
Route::get('screens/{screen}/play', Player::class)->middleware('auth.unless-public')->name('screens.play');

// Installable-app plumbing for public screens/songs (see PwaController).
Route::get('screens/{screen}/manifest.webmanifest', [PwaController::class, 'screenManifest'])->name('screens.manifest');
Route::get('screens/{screen}/icon-{size}.png', [PwaController::class, 'screenIcon'])->whereIn('size', PwaController::ICON_SIZES)->name('screens.icon');

Route::scopeBindings()->prefix('screens/{screen}/sounds/{sound}')->name('sounds.')->group(function () {
    Route::get('play', SoundPlayer::class)->middleware('auth.unless-public')->name('play');
    Route::get('manifest.webmanifest', [PwaController::class, 'soundManifest'])->name('manifest');
    Route::get('icon-{size}.png', [PwaController::class, 'soundIcon'])->whereIn('size', PwaController::ICON_SIZES)->name('icon');
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
