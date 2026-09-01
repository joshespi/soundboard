<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Sound extends Model
{
    protected $fillable = [
        'name',
        'emoji',
        'image_path',
        'color',
        'file_path',
        'sort_order',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Sound $sound) {
            Storage::disk('public')->delete($sound->file_path);

            if ($sound->image_path) {
                Storage::disk('public')->delete($sound->image_path);
            }
        });
    }

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
