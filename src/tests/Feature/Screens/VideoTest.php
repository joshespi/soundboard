<?php

namespace Tests\Feature\Screens;

use App\Livewire\Screens\Manage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Js;
use Livewire\Livewire;
use Tests\TestCase;

class VideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_extension_decides_whether_a_sound_is_video(): void
    {
        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Shows', 'sort_order' => 0]);

        $episode = $screen->sounds()->create(['name' => 'Ep', 'file_path' => 'sounds/1/ep.MP4', 'sort_order' => 0]);
        $song = $screen->sounds()->create(['name' => 'Song', 'file_path' => 'sounds/1/song.m4a', 'sort_order' => 1]);

        $this->assertTrue($episode->is_video);
        $this->assertFalse($song->is_video);
    }

    public function test_owner_can_upload_a_video_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Shows', 'sort_order' => 0]);

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen])
            ->set('newSoundName', 'Episode 1')
            ->set('newSound', UploadedFile::fake()->create('episode.mp4', 100, 'video/mp4'))
            ->call('uploadSound')
            ->assertHasNoErrors();

        $sound = $screen->sounds()->firstOrFail();
        $this->assertTrue($sound->is_video);
        Storage::disk('public')->assertExists($sound->file_path);
    }

    public function test_non_media_uploads_are_still_rejected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Shows', 'sort_order' => 0]);

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen])
            ->set('newSound', UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'))
            ->assertHasErrors('newSound');
    }

    public function test_player_keeps_video_out_of_the_audio_engine_and_adds_the_overlay(): void
    {
        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Shows', 'sort_order' => 0]);
        $song = $screen->sounds()->create(['name' => 'Song', 'file_path' => 'sounds/1/song.mp3', 'sort_order' => 0]);
        $screen->sounds()->create(['name' => 'Episode', 'file_path' => 'sounds/1/ep.mp4', 'sort_order' => 1]);

        $this->actingAs($user)
            ->get(route('screens.play', $screen))
            ->assertOk()
            ->assertSee('x-data="soundboard('.Js::from([$song->url]).')"', false)
            ->assertSee("\$dispatch('play-video'", false)
            ->assertSee('<video', false);
    }

    public function test_audio_only_screen_has_no_video_overlay(): void
    {
        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Songs', 'sort_order' => 0]);
        $screen->sounds()->create(['name' => 'Song', 'file_path' => 'sounds/1/song.mp3', 'sort_order' => 0]);

        $this->actingAs($user)
            ->get(route('screens.play', $screen))
            ->assertOk()
            ->assertDontSee('<video', false);
    }

    public function test_single_sound_page_plays_an_episode_in_a_video_element(): void
    {
        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Shows', 'sort_order' => 0, 'is_public' => true]);
        $episode = $screen->sounds()->create(['name' => 'Episode', 'file_path' => 'sounds/1/ep.mp4', 'sort_order' => 0]);

        $this->get(route('sounds.play', [$screen, $episode]))
            ->assertOk()
            ->assertSee('<video', false)
            ->assertSee('autoplay', false);
    }
}
