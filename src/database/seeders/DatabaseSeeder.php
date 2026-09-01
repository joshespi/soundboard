<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\CreatesDemoScreen;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // updateOrCreate, not factory create: safe to rerun every deploy.
        $user = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        app(CreatesDemoScreen::class)->for($user);
    }
}
