<?php

namespace Tests\Feature\Broadcast;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_the_voice_setup_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('voice.setup'))
            ->assertOk()
            ->assertSee('VB-CABLE')
            ->assertSee('CABLE Output');
    }

    public function test_guest_cannot_view_the_voice_setup_page(): void
    {
        $this->get(route('voice.setup'))->assertRedirect(route('login'));
    }

    public function test_unverified_user_cannot_view_the_voice_setup_page(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('voice.setup'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_voice_setup_link_appears_in_the_navigation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();

        // The persistent broadcast bar in layouts.app links here too, so asserting the URL
        // is merely present would pass with both nav links deleted. Count instead: desktop
        // nav, responsive nav, and the bar's Setup button.
        $this->assertSame(
            3,
            substr_count($response->getContent(), 'href="'.route('voice.setup').'"'),
        );
    }
}
