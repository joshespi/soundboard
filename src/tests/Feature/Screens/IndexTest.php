<?php

namespace Tests\Feature\Screens;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Screens\Index;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_screen(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Index::class)
            ->set('newScreenName', 'Minecraft')
            ->call('createScreen');

        $this->assertDatabaseHas('screens', [
            'user_id' => $user->id,
            'name' => 'Minecraft',
        ]);
    }

    public function test_user_can_delete_their_own_screen(): void
    {
        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Old Screen', 'sort_order' => 0]);

        Livewire::actingAs($user)
            ->test(Index::class)
            ->call('deleteScreen', $screen);

        $this->assertDatabaseMissing('screens', ['id' => $screen->id]);
    }

    public function test_user_only_sees_their_own_screens(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $user->screens()->create(['name' => 'Mine', 'sort_order' => 0]);
        $other->screens()->create(['name' => 'Theirs', 'sort_order' => 0]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee('Mine')
            ->assertDontSee('Theirs');
    }
}
