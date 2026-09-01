<?php

namespace App\Models;

use App\Models\Concerns\HasStoredFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sound extends Model
{
    use HasStoredFile;

    const IMAGE_RULES = 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:4096';

    const AUDIO_RULES = 'nullable|file|mimes:mp3,wav,ogg,m4a,aac|max:20480';

    const EMOJI_RULES = 'nullable|string|max:8';

    protected $fillable = [
        'name',
        'emoji',
        'image_path',
        'color',
        'file_path',
        'sort_order',
    ];

    public function screen(): BelongsTo
    {
        return $this->belongsTo(Screen::class);
    }
}
