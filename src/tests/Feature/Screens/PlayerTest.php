<?php

namespace Tests\Feature\Screens;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_play_page_with_sounds(): void
    {
        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Test Screen', 'sort_order' => 0]);
        $screen->sounds()->create([
            'name' => 'Boop',
            'emoji' => '🎵',
            'file_path' => 'sounds/1/boop.mp3',
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('screens.play', $screen))
            ->assertOk()
            ->assertSee('Boop');
    }

    public function test_other_users_cannot_view_play_page(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $screen = $owner->screens()->create(['name' => 'Private', 'sort_order' => 0]);

        $this->actingAs($intruder)
            ->get(route('screens.play', $screen))
            ->assertForbidden();
    }
}
