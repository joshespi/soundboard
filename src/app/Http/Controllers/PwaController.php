<?php

namespace App\Http\Controllers;

use App\Models\Screen;
use App\Models\Sound;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Per-screen and per-sound web app manifests + square icons, so each public screen or
 * song can be installed as its own home-screen app. Installing only works for public
 * screens: the WebAPK build server fetches these URLs without his login.
 */
class PwaController extends Controller
{
    public const ICON_SIZES = [192, 512];

    public function screenManifest(Screen $screen): JsonResponse
    {
        abort_unless($screen->is_public, 404);

        $startUrl = route('screens.play', $screen, false);

        return $this->manifest(
            name: $screen->name,
            startUrl: $startUrl,
            scope: $startUrl,
            backgroundColor: '#f9fafb',
            iconRoute: 'screens.icon',
            iconParameters: ['screen' => $screen],
            version: self::iconVersion($screen->image_path),
        );
    }

    public function soundManifest(Screen $screen, Sound $sound): JsonResponse
    {
        abort_unless($screen->is_public, 404);

        $startUrl = route('sounds.play', [$screen, $sound], false);

        return $this->manifest(
            name: $sound->name,
            startUrl: $startUrl,
            // Trailing slash so /sounds/5/ never claims /sounds/50/.
            scope: Str::beforeLast($startUrl, '/play').'/',
            backgroundColor: $sound->color ?: '#f9fafb',
            iconRoute: 'sounds.icon',
            iconParameters: ['screen' => $screen, 'sound' => $sound],
            version: self::iconVersion($sound->image_path ?? $screen->image_path),
        );
    }

    public function screenIcon(Screen $screen, int $size): Response|BinaryFileResponse
    {
        abort_unless($screen->is_public, 404);

        return $this->icon($screen->image_path, $size);
    }

    public function soundIcon(Screen $screen, Sound $sound, int $size): Response|BinaryFileResponse
    {
        abort_unless($screen->is_public, 404);

        return $this->icon($sound->image_path ?? $screen->image_path, $size);
    }

    // Cache-buster for icon URLs: changes exactly when the image they're cut from does
    // (stored filenames are random), so the icons can be cached as immutable.
    public static function iconVersion(?string $imagePath): string
    {
        return $imagePath ? substr(md5($imagePath), 0, 8) : 'default';
    }

    private function manifest(string $name, string $startUrl, string $scope, string $backgroundColor, string $iconRoute, array $iconParameters, string $version): JsonResponse
    {
        $icons = array_map(fn (int $size) => [
            'src' => route($iconRoute, $iconParameters + ['size' => $size, 'v' => $version], false),
            'sizes' => "{$size}x{$size}",
            'type' => 'image/png',
            'purpose' => 'any',
        ], self::ICON_SIZES);

        return response()->json([
            'id' => $startUrl,
            'name' => $name,
            'short_name' => Str::limit($name, 12, ''),
            'start_url' => $startUrl,
            'scope' => $scope,
            'display' => 'standalone',
            'background_color' => $backgroundColor,
            'theme_color' => '#4f46e5',
            'icons' => $icons,
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }

    private function icon(?string $imagePath, int $size): Response|BinaryFileResponse
    {
        $headers = ['Cache-Control' => 'public, max-age=31536000, immutable'];
        $png = $imagePath ? $this->squarePng($imagePath, $size) : null;

        if ($png === null) {
            return response()->file(public_path("icons/icon-{$size}.png"), $headers);
        }

        return response($png, 200, $headers + ['Content-Type' => 'image/png']);
    }

    // Center-crops to a square and scales; null (→ default icon) when the file is missing or GD can't decode it.
    private function squarePng(string $imagePath, int $size): ?string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($imagePath)) {
            return null;
        }

        $source = @imagecreatefromstring($disk->get($imagePath));

        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);

        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagecopyresampled($canvas, $source, 0, 0, intdiv($width - $side, 2), intdiv($height - $side, 2), $size, $size, $side, $side);

        ob_start();
        imagepng($canvas);

        return ob_get_clean();
    }
}
