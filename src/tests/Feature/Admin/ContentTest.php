<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_another_users_sound(): void
    {
        $owner = User::factory()->create();
        $screen = $owner->screens()->create(['name' => 'Their Screen', 'sort_order' => 0]);
        $screen->sounds()->create([
            'name' => 'Naughty Sound',
            'file_path' => 'sounds/1/naughty.mp3',
            'sort_order' => 0,
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.content'))
            ->assertOk()
            ->assertSee('Naughty Sound')
            ->assertSee($owner->email);
    }

    public function test_admin_can_delete_another_users_sound(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $screen = $owner->screens()->create(['name' => 'Their Screen', 'sort_order' => 0]);
        $path = 'sounds/1/naughty.mp3';
        Storage::disk('public')->put($path, 'fake-audio');
        $sound = $screen->sounds()->create([
            'name' => 'Naughty Sound',
            'file_path' => $path,
            'sort_order' => 0,
        ]);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Content::class)
            ->call('deleteSound', $sound);

        $this->assertDatabaseMissing('sounds', ['id' => $sound->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
