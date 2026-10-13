<?php

namespace Tests\Feature\Screens;

use App\Livewire\Screens\Manage;
use App\Models\Screen;
use App\Models\Sound;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    private function makeScreen(bool $public = true, string $name = 'SpongeBob'): array
    {
        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => $name, 'sort_order' => 0, 'is_public' => $public]);
        $sound = $screen->sounds()->create(['name' => 'Jellyfish Jam', 'file_path' => 'sounds/1/jam.mp3', 'color' => '#ef4444', 'sort_order' => 0]);

        return [$user, $screen, $sound];
    }

    public function test_public_screen_manifest_describes_its_own_app(): void
    {
        [, $screen] = $this->makeScreen();

        $this->get(route('screens.manifest', $screen))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('name', 'SpongeBob')
            ->assertJsonPath('start_url', "/screens/{$screen->id}/play")
            ->assertJsonPath('icons.0.sizes', '192x192')
            ->assertJsonPath('icons.1.sizes', '512x512');
    }

    public function test_sound_manifest_is_scoped_to_exactly_that_sound(): void
    {
        [, $screen, $sound] = $this->makeScreen();

        $this->get(route('sounds.manifest', [$screen, $sound]))
            ->assertOk()
            ->assertJsonPath('name', 'Jellyfish Jam')
            ->assertJsonPath('background_color', '#ef4444')
            ->assertJsonPath('start_url', "/screens/{$screen->id}/sounds/{$sound->id}/play")
            ->assertJsonPath('scope', "/screens/{$screen->id}/sounds/{$sound->id}/");
    }

    public function test_private_screens_expose_no_manifest_or_icon(): void
    {
        [, $screen, $sound] = $this->makeScreen(public: false);

        $this->get(route('screens.manifest', $screen))->assertNotFound();
        $this->get(route('sounds.manifest', [$screen, $sound]))->assertNotFound();
        $this->get(route('screens.icon', [$screen, 192]))->assertNotFound();
        $this->get(route('sounds.icon', [$screen, $sound, 512]))->assertNotFound();
    }

    public function test_sound_from_another_screen_is_not_found(): void
    {
        [$user, $screen] = $this->makeScreen();
        $other = $user->screens()->create(['name' => 'Other', 'sort_order' => 1, 'is_public' => true]);
        $stray = $other->sounds()->create(['name' => 'Stray', 'file_path' => 'sounds/1/stray.mp3', 'sort_order' => 0]);

        $this->get(route('sounds.play', [$screen, $stray]))->assertNotFound();
        $this->get(route('sounds.manifest', [$screen, $stray]))->assertNotFound();
    }

    public function test_icon_is_a_square_png_cropped_from_the_cover_image(): void
    {
        Storage::fake('public');
        [, $screen, $sound] = $this->makeScreen();
        Storage::disk('public')->putFileAs('sounds/1/images', UploadedFile::fake()->image('cover.jpg', 600, 300), 'cover.jpg');
        $sound->update(['image_path' => 'sounds/1/images/cover.jpg']);

        $response = $this->get(route('sounds.icon', [$screen, $sound, 192]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $image = imagecreatefromstring($response->getContent());
        $this->assertSame([192, 192], [imagesx($image), imagesy($image)]);
    }

    public function test_icon_falls_back_to_the_default_when_there_is_no_cover(): void
    {
        [, $screen, $sound] = $this->makeScreen();

        $this->get(route('sounds.icon', [$screen, $sound, 512]))->assertOk();
        $this->get(route('screens.icon', [$screen, 192]))->assertOk();
    }

    public function test_only_the_two_icon_sizes_exist(): void
    {
        [, $screen] = $this->makeScreen();

        $this->get("/screens/{$screen->id}/icon-100.png")->assertNotFound();
    }

    public function test_guest_can_open_a_public_sound_page_that_links_its_own_manifest(): void
    {
        [, $screen, $sound] = $this->makeScreen();

        $this->get(route('sounds.play', [$screen, $sound]))
            ->assertOk()
            ->assertSee('Jellyfish Jam')
            ->assertSee(route('sounds.manifest', [$screen, $sound]), false);
    }

    public function test_guest_is_sent_to_login_for_a_private_sound_page(): void
    {
        [, $screen, $sound] = $this->makeScreen(public: false);

        $this->get(route('sounds.play', [$screen, $sound]))->assertRedirect(route('login'));
    }

    public function test_public_screen_page_links_the_screen_manifest(): void
    {
        [, $screen] = $this->makeScreen();

        $this->get(route('screens.play', $screen))
            ->assertOk()
            ->assertSee(route('screens.manifest', $screen), false);
    }

    public function test_private_screen_page_keeps_the_default_manifest(): void
    {
        [$user, $screen] = $this->makeScreen(public: false);

        $this->actingAs($user)
            ->get(route('screens.play', $screen))
            ->assertOk()
            ->assertDontSee(route('screens.manifest', $screen), false);
    }

    public function test_owner_can_set_and_remove_the_screen_icon(): void
    {
        Storage::fake('public');
        [$user, $screen] = $this->makeScreen();

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen])
            ->set('screenImage', UploadedFile::fake()->image('icon.png'));

        $path = $screen->fresh()->image_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen->fresh()])
            ->call('removeScreenImage');

        $this->assertNull($screen->fresh()->image_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_deleting_a_screen_removes_its_icon_file(): void
    {
        Storage::fake('public');
        [, $screen] = $this->makeScreen();
        Storage::disk('public')->put('sounds/1/screens/icon.png', 'x');
        $screen->update(['image_path' => 'sounds/1/screens/icon.png']);

        $screen->deleteWithSounds();

        Storage::disk('public')->assertMissing('sounds/1/screens/icon.png');
        $this->assertNull(Screen::find($screen->id));
        $this->assertSame(0, Sound::count());
    }
}
