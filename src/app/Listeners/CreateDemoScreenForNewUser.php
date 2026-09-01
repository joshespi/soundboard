<?php

namespace App\Listeners;

use App\Support\CreatesDemoScreen;
use Illuminate\Auth\Events\Registered;

class CreateDemoScreenForNewUser
{
    public function handle(Registered $event): void
    {
        app(CreatesDemoScreen::class)->for($event->user);
    }
}
