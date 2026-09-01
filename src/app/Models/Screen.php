<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Screen extends Model
{
    const NAME_RULES = 'required|string|max:50';

    protected $fillable = [
        'name',
        'sort_order',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sounds(): HasMany
    {
        return $this->hasMany(Sound::class)->orderBy('sort_order');
    }

    // Bulk-deletes the screen's sound files/rows instead of loading and
    // deleting each Sound model individually (each of which would otherwise
    // fire its own pair of Storage::delete() calls via HasStoredFile).
    // Storage::delete() runs after the DB transaction commits, not before —
    // if it ran first and the transaction then failed, sound rows would
    // survive pointing at files that no longer exist.
    public function deleteWithSounds(): void
    {
        $paths = $this->sounds()
            ->get(['file_path', 'image_path'])
            ->flatMap(fn (Sound $sound) => array_filter([$sound->file_path, $sound->image_path]))
            ->all();

        DB::transaction(function () {
            $this->sounds()->delete();
            $this->delete();
        });

        Storage::disk('public')->delete($paths);
    }
}
