<?php

namespace Database\Seeders;

use App\Models\Screen;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    // [name, emoji, tile color, tone Hz, duration s]
    private const DEMO_SOUNDS = [
        ['Ding', '🔔', '#6366f1', 800, 0.3],
        ['Boop', '🔵', '#0ea5e9', 400, 0.2],
        ['Buzz', '⚡', '#f59e0b', 150, 0.4],
        ['Chime', '✨', '#10b981', 1200, 0.25],
        ['Honk', '📯', '#f43f5e', 300, 0.5],
        ['Alert', '🚨', '#8b5cf6', 600, 0.35],
    ];

    public function run(): void
    {
        // updateOrCreate, not factory create: safe to rerun every deploy.
        $user = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $screen = Screen::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Demo Board'],
            ['sort_order' => 0],
        );

        if ($screen->sounds()->count() > 0) {
            return;
        }

        foreach (self::DEMO_SOUNDS as $index => [$name, $emoji, $color, $frequency, $duration]) {
            $path = 'sounds/'.$user->id.'/demo-'.str($name)->slug().'.wav';

            Storage::disk('public')->put($path, $this->generateToneWav($frequency, $duration));

            $screen->sounds()->create([
                'name' => $name,
                'emoji' => $emoji,
                'color' => $color,
                'file_path' => $path,
                'sort_order' => $index,
            ]);
        }
    }

    // Synthesizes a WAV tone so the demo screen has real audio with no binary sample files in the repo.
    private function generateToneWav(float $frequency, float $seconds, int $sampleRate = 8000): string
    {
        $numSamples = (int) ($sampleRate * $seconds);
        $data = '';

        for ($i = 0; $i < $numSamples; $i++) {
            // Linear fade-out so the tone doesn't end in an audible click.
            $envelope = 1 - ($i / $numSamples) * 0.8;
            $sample = sin(2 * M_PI * $frequency * $i / $sampleRate) * $envelope;
            $data .= chr((int) max(0, min(255, round(($sample + 1) * 127.5))));
        }

        $dataSize = strlen($data);

        $header = 'RIFF'
            .pack('V', 36 + $dataSize)
            .'WAVE'
            .'fmt '
            .pack('V', 16)
            .pack('v', 1) // PCM
            .pack('v', 1) // mono
            .pack('V', $sampleRate)
            .pack('V', $sampleRate) // byte rate = sampleRate * channels * bytes/sample
            .pack('v', 1) // block align
            .pack('v', 8) // bits per sample
            .'data'
            .pack('V', $dataSize);

        return $header.$data;
    }
}
