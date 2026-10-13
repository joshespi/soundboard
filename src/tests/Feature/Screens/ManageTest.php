<?php

namespace Tests\Feature\Screens;

use App\Livewire\Screens\Manage;
use App\Models\LibrarySound;
use App\Models\Sound;
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

    public function test_owner_can_toggle_the_public_link(): void
    {
        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Test Screen', 'sort_order' => 0]);

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen])
            ->set('isPublic', true);

        $this->assertTrue($screen->fresh()->is_public);
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

    public function test_uploads_use_the_chosen_colour_or_cycle_through_the_presets(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Test Screen', 'sort_order' => 0]);

        $component = Livewire::actingAs($user)->test(Manage::class, ['screen' => $screen]);

        $component
            ->set('newSound', UploadedFile::fake()->create('a.mp3', 100, 'audio/mpeg'))
            ->set('newSoundColor', '#ef4444')
            ->call('uploadSound')
            ->set('newSound', UploadedFile::fake()->create('b.mp3', 100, 'audio/mpeg'))
            ->call('uploadSound');

        $colors = $screen->sounds()->pluck('color')->all();

        $this->assertSame('#ef4444', $colors[0]);
        $this->assertSame(Sound::colorForPosition(1), $colors[1]);
    }

    public function test_rejects_a_colour_that_is_not_a_hex_value(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Test Screen', 'sort_order' => 0]);

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen])
            ->set('newSound', UploadedFile::fake()->create('a.mp3', 100, 'audio/mpeg'))
            ->set('newSoundColor', 'red; background:url(x)')
            ->call('uploadSound')
            ->assertHasErrors('newSoundColor');

        $this->assertSame(0, $screen->sounds()->count());
    }

    public function test_a_long_filename_is_trimmed_to_a_name_that_can_be_edited_later(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Test Screen', 'sort_order' => 0]);

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen])
            ->set('newSound', UploadedFile::fake()->create(str_repeat('a', 80).'.mp3', 100, 'audio/mpeg'))
            ->call('uploadSound')
            ->assertHasNoErrors();

        $this->assertSame(50, mb_strlen($screen->sounds()->firstOrFail()->name));
    }

    public function test_owner_can_edit_a_sounds_colour(): void
    {
        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Test Screen', 'sort_order' => 0]);
        $sound = $screen->sounds()->create(['name' => 'Boop', 'file_path' => 'x.mp3', 'color' => '#6366f1', 'sort_order' => 0]);

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen])
            ->call('startEditSound', $sound)
            ->assertSet('editColor', '#6366f1')
            ->set('editColor', '#22c55e')
            ->call('saveEditSound');

        $this->assertSame('#22c55e', $sound->fresh()->color);
    }

    public function test_reorder_moves_a_sound_to_the_given_position(): void
    {
        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Test Screen', 'sort_order' => 0]);
        [, , $c] = collect(['A', 'B', 'C'])->map(
            fn ($name, $i) => $screen->sounds()->create(['name' => $name, 'file_path' => "$name.mp3", 'sort_order' => $i])
        )->all();

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen])
            ->call('reorder', $c->id, 0);

        $this->assertSame(['C', 'A', 'B'], $screen->sounds()->pluck('name')->all());
    }

    public function test_cannot_reorder_a_sound_from_another_screen(): void
    {
        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Mine', 'sort_order' => 0]);
        $other = User::factory()->create()->screens()->create(['name' => 'Theirs', 'sort_order' => 0]);
        $foreign = $other->sounds()->create(['name' => 'X', 'file_path' => 'x.mp3', 'sort_order' => 0]);

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen])
            ->call('reorder', $foreign->id, 0)
            ->assertNotFound();
    }

    public function test_library_search_treats_wildcards_literally(): void
    {
        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Test Screen', 'sort_order' => 0]);
        LibrarySound::create(['name' => 'Boop', 'file_path' => 'a.mp3', 'sort_order' => 0]);

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen])
            ->set('librarySearch', '%')
            ->assertSee('Nothing found.');
    }
}
