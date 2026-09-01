<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Shared behavior for models with a required `file_path` and optional
 * `image_path` on the `public` disk (Sound, LibrarySound).
 */
trait HasStoredFile
{
    protected static function bootHasStoredFile(): void
    {
        static::deleting(function (self $model) {
            Storage::disk('public')->delete($model->file_path);

            if ($model->image_path) {
                Storage::disk('public')->delete($model->image_path);
            }
        });
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public static function sumStorageBytes(iterable $models): int
    {
        $bytes = 0;

        foreach ($models as $model) {
            $bytes += static::storageFileSize($model->file_path);

            if ($model->image_path) {
                $bytes += static::storageFileSize($model->image_path);
            }
        }

        return $bytes;
    }

    private static function storageFileSize(string $path): int
    {
        try {
            return Storage::disk('public')->size($path);
        } catch (\Throwable) {
            return 0;
        }
    }
}
