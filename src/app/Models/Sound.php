<?php

namespace App\Models;

use App\Models\Concerns\HasStoredFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sound extends Model
{
    use HasStoredFile;

    const NAME_RULES = 'required|string|max:50';

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

    // Directory a user's screen sounds are stored under — shared by the demo seeder and Screens\Manage.
    public static function storagePathFor(int $userId, string $sub = ''): string
    {
        return 'sounds/'.$userId.($sub !== '' ? '/'.$sub : '');
    }
}
