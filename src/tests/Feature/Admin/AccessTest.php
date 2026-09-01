<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Content;
use App\Livewire\Admin\Library;
use App\Livewire\Admin\Users;
use App\Models\LibrarySound;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    public static function adminRoutes(): array
    {
        return [
            ['admin.dashboard'],
            ['admin.content'],
            ['admin.users'],
            ['admin.library'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_non_admin_cannot_view_admin_pages(string $route): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route($route))
            ->assertForbidden();
    }

    #[DataProvider('adminRoutes')]
    public function test_admin_can_view_admin_pages(string $route): void
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        $this->actingAs($admin)
            ->get(route($route))
            ->assertOk();
    }

    /**
     * Livewire only runs mount()/route middleware on the initial page load —
     * a subsequent action call hydrates the component from its snapshot and
     * bypasses both. Each admin component's mutating actions must therefore
     * check authorization themselves.
     */
    public function test_non_admin_cannot_call_admin_actions_directly(): void
    {
        $nonAdmin = User::factory()->create();
        $victim = User::factory()->create();
        $screen = $victim->screens()->create(['name' => 'Screen', 'sort_order' => 0]);
        $sound = $screen->sounds()->create(['name' => 'Sound', 'file_path' => 'sounds/1/a.mp3', 'sort_order' => 0]);
        $librarySound = LibrarySound::create(['name' => 'Boop', 'file_path' => 'library-sounds/boop.mp3', 'sort_order' => 0]);

        Livewire::actingAs($nonAdmin)->test(Content::class)
            ->call('deleteSound', $sound)
            ->assertForbidden();

        Livewire::actingAs($nonAdmin)->test(Library::class)
            ->call('deleteSound', $librarySound)
            ->assertForbidden();

        Livewire::actingAs($nonAdmin)->test(Users::class)
            ->call('deleteUser', $victim)
            ->assertForbidden();

        $this->assertDatabaseHas('sounds', ['id' => $sound->id]);
        $this->assertDatabaseHas('library_sounds', ['id' => $librarySound->id]);
        $this->assertDatabaseHas('users', ['id' => $victim->id]);
    }
}
