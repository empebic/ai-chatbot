<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'disk',
        'path',
        'mime_type',
        'size_bytes',
        'type',
        'width',
        'height',
        'duration_seconds',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
