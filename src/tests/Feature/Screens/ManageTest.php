<?php

namespace Tests\Feature\Screens;

use App\Livewire\Screens\Manage;
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
}
