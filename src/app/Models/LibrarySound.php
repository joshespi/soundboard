<?php

namespace App\Models;

use App\Models\Concerns\HasStoredFile;
use Illuminate\Database\Eloquent\Model;

class LibrarySound extends Model
{
    use HasStoredFile;

    protected $fillable = [
        'name',
        'emoji',
        'image_path',
        'color',
        'file_path',
        'sort_order',
    ];
}
