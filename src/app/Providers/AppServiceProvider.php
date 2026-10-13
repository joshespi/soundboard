<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Livewire's temp-upload endpoint defaults to 12 MB and 5 minutes — far too small for
        // episodes. The per-field rules (Sound::MEDIA_RULES) are the real limit.
        // Temp files go on the private `local` disk: on the default `public` disk nginx would
        // serve any not-yet-validated upload (e.g. an .html file) from the app's own origin.
        config([
            'livewire.temporary_file_upload.disk' => 'local',
            'livewire.temporary_file_upload.rules' => ['required', 'file', 'max:1048576'],
            'livewire.temporary_file_upload.max_upload_time' => 60,
        ]);
    }
}
