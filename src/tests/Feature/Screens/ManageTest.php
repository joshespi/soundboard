<?php

namespace Tests\Feature\Screens;

use App\Livewire\Screens\Manage;
use App\Models\LibrarySound;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ManageTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_manage_page(): void
    {
        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Test Screen', 'sort_order' => 0]);

        $this->actingAs($user)
            ->get(route('screens.manage', $screen))
            ->assertOk()
            ->assertSee('Test Screen');
    }

    public function test_other_users_cannot_view_manage_page(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $screen = $owner->screens()->create(['name' => 'Private', 'sort_order' => 0]);

        $this->actingAs($intruder)
            ->get(route('screens.manage', $screen))
            ->assertForbidden();
    }

    public function test_owner_can_upload_a_sound_with_an_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Test Screen', 'sort_order' => 0]);

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen])
            ->set('newSoundName', 'Boop')
            ->set('newSoundEmoji', '🎵')
            ->set('newSound', UploadedFile::fake()->create('boop.mp3', 100, 'audio/mpeg'))
            ->set('newSoundImage', UploadedFile::fake()->image('icon.png'))
            ->call('uploadSound');

        $sound = $screen->sounds()->firstOrFail();

        $this->assertSame('Boop', $sound->name);
        $this->assertNotNull($sound->image_path);
        Storage::disk('public')->assertExists($sound->file_path);
        Storage::disk('public')->assertExists($sound->image_path);
    }

    public function test_owner_can_edit_a_sounds_name_emoji_and_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Test Screen', 'sort_order' => 0]);
        $sound = $screen->sounds()->create([
            'name' => 'Original',
            'emoji' => '🔊',
            'file_path' => 'sounds/1/original.mp3',
            'sort_order' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen])
            ->call('startEditSound', $sound)
            ->set('editName', 'Renamed')
            ->set('editEmoji', '🎶')
            ->set('editImage', UploadedFile::fake()->image('icon.png'))
            ->call('saveEditSound');

        $sound->refresh();

        $this->assertSame('Renamed', $sound->name);
        $this->assertSame('🎶', $sound->emoji);
        $this->assertNotNull($sound->image_path);
        Storage::disk('public')->assertExists($sound->image_path);
    }

    public function test_owner_can_remove_a_sounds_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Test Screen', 'sort_order' => 0]);
        $image = UploadedFile::fake()->image('icon.png')->store('sounds/1/images', 'public');
        $sound = $screen->sounds()->create([
            'name' => 'Original',
            'emoji' => '🔊',
            'image_path' => $image,
            'file_path' => 'sounds/1/original.mp3',
            'sort_order' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen])
            ->call('startEditSound', $sound)
            ->set('editRemoveImage', true)
            ->call('saveEditSound');

        $sound->refresh();

        $this->assertNull($sound->image_path);
        Storage::disk('public')->assertMissing($image);
    }

    public function test_owner_can_add_a_library_sound_to_their_screen_as_an_independent_copy(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Test Screen', 'sort_order' => 0]);
        Storage::disk('public')->put('library-sounds/boop.mp3', 'fake-audio');
        Storage::disk('public')->put('library-sounds/images/boop.png', 'fake-image');
        $librarySound = LibrarySound::create([
            'name' => 'Boop',
            'emoji' => '🎵',
            'color' => '#123456',
            'file_path' => 'library-sounds/boop.mp3',
            'image_path' => 'library-sounds/images/boop.png',
            'sort_order' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen])
            ->call('addFromLibrary', $librarySound);

        $sound = $screen->sounds()->firstOrFail();

        $this->assertSame('Boop', $sound->name);
        $this->assertSame('🎵', $sound->emoji);
        $this->assertSame('#123456', $sound->color);
        $this->assertNotSame($librarySound->file_path, $sound->file_path);
        $this->assertNotSame($librarySound->image_path, $sound->image_path);
        Storage::disk('public')->assertExists($sound->file_path);
        Storage::disk('public')->assertExists($sound->image_path);

        // Deleting the library original must not affect the user's independent copy.
        $librarySound->delete();
        Storage::disk('public')->assertExists($sound->file_path);
        Storage::disk('public')->assertExists($sound->image_path);
    }
}
