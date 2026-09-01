<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
