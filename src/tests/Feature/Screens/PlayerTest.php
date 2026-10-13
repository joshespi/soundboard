<?php

namespace Tests\Feature\Screens;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Js;
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

    public function test_play_page_exposes_sound_urls_to_the_audio_engine(): void
    {
        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Test Screen', 'sort_order' => 0]);
        $screen->sounds()->create([
            'name' => 'Boop',
            'file_path' => 'sounds/1/boop.mp3',
            'sort_order' => 0,
        ]);

        // The audio engine is handed the whole list up front so it can pre-decode clips.
        // Matching on the filename alone would not do: every tile's x-on:click carries its
        // own URL, so that assertion passes even with the x-data list deleted.
        $urls = Js::from($screen->sounds()->get()->pluck('url'));

        $this->actingAs($user)
            ->get(route('screens.play', $screen))
            ->assertOk()
            ->assertSee('x-data="soundboard('.$urls.')"', false);
    }

    public function test_guest_can_play_a_public_screen_without_owner_controls(): void
    {
        $owner = User::factory()->create();
        $screen = $owner->screens()->create(['name' => 'Bluey Songs', 'sort_order' => 0, 'is_public' => true]);
        $screen->sounds()->create(['name' => 'Boop', 'file_path' => 'sounds/1/boop.mp3', 'sort_order' => 0]);

        $this->get(route('screens.play', $screen))
            ->assertOk()
            ->assertSee('Boop')
            ->assertDontSee('Edit sounds');
    }

    public function test_guest_is_sent_to_login_for_a_private_screen(): void
    {
        $owner = User::factory()->create();
        $screen = $owner->screens()->create(['name' => 'Private', 'sort_order' => 0]);

        $this->get(route('screens.play', $screen))
            ->assertRedirect(route('login'));
    }

    public function test_logged_in_stranger_can_play_public_screen_but_not_edit_it(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $screen = $owner->screens()->create(['name' => 'Shared', 'sort_order' => 0, 'is_public' => true]);

        $this->actingAs($stranger)->get(route('screens.play', $screen))->assertOk()->assertDontSee('Edit sounds');
        $this->actingAs($stranger)->get(route('screens.manage', $screen))->assertForbidden();
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
