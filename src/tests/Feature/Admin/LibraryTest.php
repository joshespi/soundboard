<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Library;
use App\Models\LibrarySound;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LibraryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        return $admin;
    }

    public function test_admin_can_upload_a_library_sound(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin())
            ->test(Library::class)
            ->set('newSoundName', 'Boop')
            ->set('newSoundEmoji', '🎵')
            ->set('newSound', UploadedFile::fake()->create('boop.mp3', 100, 'audio/mpeg'))
            ->call('uploadSound');

        $librarySound = LibrarySound::firstOrFail();

        $this->assertSame('Boop', $librarySound->name);
        Storage::disk('public')->assertExists($librarySound->file_path);
    }

    public function test_admin_can_delete_a_library_sound(): void
    {
        Storage::fake('public');

        $path = 'library-sounds/boop.mp3';
        Storage::disk('public')->put($path, 'fake-audio');
        $librarySound = LibrarySound::create([
            'name' => 'Boop',
            'file_path' => $path,
            'sort_order' => 0,
        ]);

        Livewire::actingAs($this->admin())
            ->test(Library::class)
            ->call('deleteSound', $librarySound);

        $this->assertDatabaseMissing('library_sounds', ['id' => $librarySound->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
