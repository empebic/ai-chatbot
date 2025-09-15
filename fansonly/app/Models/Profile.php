<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'username',
        'display_name',
        'bio',
        'location',
        'website',
        'avatar_path',
        'cover_path',
        'is_creator',
        'monthly_price',
        'currency',
        'is_verified',
        'is_nsfw',
        'stripe_account_id',
        'settings',
    ];

    protected $casts = [
        'is_creator' => 'boolean',
        'is_verified' => 'boolean',
        'is_nsfw' => 'boolean',
        'settings' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
