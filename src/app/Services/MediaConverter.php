<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Thin wrapper around the ffmpeg/ffprobe binaries baked into the `app` image.
 * Works on absolute paths; callers own the storage layout.
 */
class MediaConverter
{
    // Long videos can take a while to re-encode; callers raise PHP's time limit to match.
    public const TIMEOUT_SECONDS = 1800;

    /** Duration in seconds, or null if the file can't be probed. */
    public function duration(string $path): ?float
    {
        $result = Process::timeout(60)->run([
            'ffprobe', '-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', $path,
        ]);

        $seconds = trim($result->output());

        return $result->successful() && is_numeric($seconds) ? (float) $seconds : null;
    }

    /**
     * Writes $output from $input, starting at $start seconds and ending at $end
     * (null = to the end). With $keepVideo the output is mp4/H.264, otherwise
     * the video stream is dropped and it's m4a/AAC — $output's extension must match.
     */
    public function convert(string $input, string $output, float $start, ?float $end, bool $keepVideo): void
    {
        $command = ['ffmpeg', '-y', '-v', 'error'];

        if ($start > 0) {
            $command = [...$command, '-ss', $this->seconds($start)];
        }

        $command = [...$command, '-i', $input];

        if ($end !== null) {
            $command = [...$command, '-t', $this->seconds($end - $start)];
        }

        $command = [...$command, ...($keepVideo
            ? ['-c:v', 'libx264', '-preset', 'veryfast', '-crf', '23', '-pix_fmt', 'yuv420p', '-c:a', 'aac', '-b:a', '160k']
            : ['-vn', '-c:a', 'aac', '-b:a', '192k']),
            '-movflags', '+faststart', $output,
        ];

        $result = Process::timeout(self::TIMEOUT_SECONDS)->run($command);

        if (! $result->successful()) {
            throw new RuntimeException(trim($result->errorOutput()) ?: 'ffmpeg failed.');
        }
    }

    private function seconds(float $value): string
    {
        return number_format(max(0, $value), 3, '.', '');
    }
}
