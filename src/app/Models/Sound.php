<?php

namespace App\Models;

use App\Models\Concerns\HasStoredFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sound extends Model
{
    use HasStoredFile;

    const NAME_RULES = 'required|string|max:50';

    const NAME_RULES_OPTIONAL = 'nullable|string|max:50';

    const IMAGE_RULES = 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:4096';

    const AUDIO_RULES = 'nullable|file|mimes:mp3,wav,ogg,m4a,aac|max:20480';

    const EMOJI_RULES = 'nullable|string|max:8';

    // Tile colour presets offered in the forms; an upload with no pick cycles through them.
    const COLORS = ['#6366f1', '#ef4444', '#f97316', '#eab308', '#22c55e', '#14b8a6', '#0ea5e9', '#a855f7', '#ec4899', '#64748b'];

    const COLOR_RULES = ['nullable', 'regex:/^#[0-9a-f]{6}$/i'];

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

    public static function colorForPosition(int $position): string
    {
        return self::COLORS[$position % count(self::COLORS)];
    }

    // Directory a user's screen sounds are stored under — shared by the demo seeder and Screens\Manage.
    public static function storagePathFor(int $userId, string $sub = ''): string
    {
        return 'sounds/'.$userId.($sub !== '' ? '/'.$sub : '');
    }
}
