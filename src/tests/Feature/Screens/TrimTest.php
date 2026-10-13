<?php

namespace Tests\Feature\Screens;

use App\Livewire\Screens\Manage;
use App\Models\User;
use App\Services\MediaConverter;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class TrimTest extends TestCase
{
    use RefreshDatabase;

    private function videoSound(): array
    {
        Storage::fake('public');
        Storage::disk('public')->put('sounds/1/ep.mp4', 'video-bytes');

        $user = User::factory()->create();
        $screen = $user->screens()->create(['name' => 'Shows', 'sort_order' => 0]);
        $sound = $screen->sounds()->create(['name' => 'Episode', 'file_path' => 'sounds/1/ep.mp4', 'color' => '#ef4444', 'sort_order' => 0]);

        return [$user, $screen, $sound];
    }

    // Stands in for ffmpeg: reports a 60s duration and "writes" the output file (or fails).
    private function fakeConverter(?\Throwable $failure = null): void
    {
        $this->mock(MediaConverter::class, function (MockInterface $mock) use ($failure) {
            $mock->shouldReceive('duration')->andReturn(60.0);
            $failure
                ? $mock->shouldReceive('convert')->andThrow($failure)
                : $mock->shouldReceive('convert')->andReturnUsing(fn ($in, $out) => file_put_contents($out, 'converted'));
        });
    }

    public function test_clip_as_new_keeps_the_original(): void
    {
        [$user, $screen, $sound] = $this->videoSound();
        $this->fakeConverter();

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen])
            ->call('startTrim', $sound->id)
            ->call('trimSound', 5.0, 15.0, false, true)
            ->assertHasNoErrors()
            ->assertSet('trimmingSoundId', null);

        $this->assertSame(2, $screen->sounds()->count());
        $clip = $screen->sounds()->where('id', '!=', $sound->id)->firstOrFail();
        $this->assertSame('Episode (clip)', $clip->name);
        $this->assertTrue($clip->is_video);
        Storage::disk('public')->assertExists($clip->file_path);
        Storage::disk('public')->assertExists($sound->file_path);
    }

    public function test_audio_only_replace_swaps_the_file_and_drops_video(): void
    {
        [$user, $screen, $sound] = $this->videoSound();
        $this->fakeConverter();

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen])
            ->call('startTrim', $sound->id)
            ->call('trimSound', 0.0, 60.0, true, false)
            ->assertHasNoErrors();

        $sound->refresh();
        $this->assertSame(1, $screen->sounds()->count());
        $this->assertFalse($sound->is_video);
        $this->assertStringEndsWith('.m4a', $sound->file_path);
        Storage::disk('public')->assertMissing('sounds/1/ep.mp4');
        Storage::disk('public')->assertExists($sound->file_path);
    }

    public function test_whole_file_with_video_kept_is_rejected(): void
    {
        [$user, $screen, $sound] = $this->videoSound();
        $this->fakeConverter();

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen])
            ->call('startTrim', $sound->id)
            ->call('trimSound', 0.0, 60.0, false, true)
            ->assertHasErrors('trim');

        $this->assertSame(1, $screen->sounds()->count());
    }

    public function test_failed_conversion_reports_an_error_and_changes_nothing(): void
    {
        [$user, $screen, $sound] = $this->videoSound();
        $this->fakeConverter(new \RuntimeException('boom'));

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen])
            ->call('startTrim', $sound->id)
            ->call('trimSound', 5.0, 15.0, false, false)
            ->assertHasErrors('trim');

        $this->assertSame('sounds/1/ep.mp4', $sound->fresh()->file_path);
    }

    public function test_cannot_trim_a_sound_from_another_screen(): void
    {
        [$user, $screen] = $this->videoSound();
        $other = $user->screens()->create(['name' => 'Other', 'sort_order' => 1]);
        $elsewhere = $other->sounds()->create(['name' => 'Elsewhere', 'file_path' => 'sounds/1/x.mp3', 'sort_order' => 0]);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($user)
            ->test(Manage::class, ['screen' => $screen])
            ->call('startTrim', $elsewhere->id);
    }

    public function test_converter_builds_an_audio_only_ffmpeg_command(): void
    {
        Process::fake();

        (new MediaConverter)->convert('/in/ep.mp4', '/out/a.m4a', 5.0, 15.0, false);

        Process::assertRan(fn ($process) => $process->command === [
            'ffmpeg', '-y', '-v', 'error', '-ss', '5.000', '-i', '/in/ep.mp4', '-t', '10.000',
            '-vn', '-c:a', 'aac', '-b:a', '192k', '-movflags', '+faststart', '/out/a.m4a',
        ]);
    }

    public function test_converter_surfaces_ffmpeg_errors(): void
    {
        Process::fake(['*' => Process::result(errorOutput: 'no such stream', exitCode: 1)]);

        $this->expectExceptionMessage('no such stream');

        (new MediaConverter)->convert('/in/ep.mp4', '/out/a.m4a', 0.0, null, false);
    }
}
