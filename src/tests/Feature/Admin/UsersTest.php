<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Users;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_a_user_and_their_files_are_cleaned_up(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Their Screen', 'sort_order' => 0]);
        $audioPath = 'sounds/'.$user->id.'/sound.mp3';
        $imagePath = 'sounds/'.$user->id.'/images/icon.png';
        Storage::disk('public')->put($audioPath, 'fake-audio');
        Storage::disk('public')->put($imagePath, 'fake-image');
        $screen->sounds()->create([
            'name' => 'Sound',
            'file_path' => $audioPath,
            'image_path' => $imagePath,
            'sort_order' => 0,
        ]);

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(Users::class)
            ->call('deleteUser', $user);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('screens', ['id' => $screen->id]);
        Storage::disk('public')->assertMissing($audioPath);
        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_admin_cannot_delete_their_own_account_from_here(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('deleteUser', $admin)
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
