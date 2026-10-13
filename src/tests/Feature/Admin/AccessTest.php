<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Content;
use App\Livewire\Admin\Library;
use App\Livewire\Admin\Users;
use App\Models\LibrarySound;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
        $admin = User::factory()->admin()->create();

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

        Livewire::actingAs($nonAdmin)->test(Library::class)
            ->call('deleteSound', $librarySound)
            ->assertForbidden();

        // Users guards render() too (see below), so it's rejected before deleteUser is reachable.
        Livewire::actingAs($nonAdmin)->test(Users::class)->assertForbidden();

        $this->assertDatabaseHas('sounds', ['id' => $sound->id]);
        $this->assertDatabaseHas('library_sounds', ['id' => $librarySound->id]);
        $this->assertDatabaseHas('users', ['id' => $victim->id]);
    }

    /**
     * Content also guards render() itself (see Content::render()), since
     * WithPagination's gotoPage()/nextPage()/previousPage() have no
     * authorization check of their own — so a non-admin is rejected before
     * a mutating action is even reachable.
     */
    public function test_non_admin_cannot_render_the_content_component(): void
    {
        $nonAdmin = User::factory()->create();

        Livewire::actingAs($nonAdmin)->test(Content::class)->assertForbidden();
    }

    public function test_deleting_a_user_removes_their_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('sounds/9/a.mp3', 'x');
        Storage::disk('public')->put('sounds/9/screens/icon.png', 'x');

        $admin = User::factory()->admin()->create();
        $victim = User::factory()->create();
        $screen = $victim->screens()->create(['name' => 'Screen', 'sort_order' => 0, 'image_path' => 'sounds/9/screens/icon.png']);
        $screen->sounds()->create(['name' => 'Sound', 'file_path' => 'sounds/9/a.mp3', 'sort_order' => 0]);

        Livewire::actingAs($admin)->test(Users::class)->call('deleteUser', $victim);

        $this->assertDatabaseMissing('users', ['id' => $victim->id]);
        $this->assertDatabaseMissing('sounds', ['screen_id' => $screen->id]);
        Storage::disk('public')->assertMissing(['sounds/9/a.mp3', 'sounds/9/screens/icon.png']);
    }
}
