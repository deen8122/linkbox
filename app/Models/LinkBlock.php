<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkBlock extends Model
{
    protected $fillable = [
        'user_id',
        'url',
        'title',
        'image',
        'position',
        'favicon_path'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    protected $appends = [
        'image_url',
        'favicon_url',
    ];
    public function getFaviconUrlAttribute(): ?string
    {
        if (!$this->favicon_path) {
            return null;
        }

        return asset(
            'storage/' . $this->favicon_path
        );
    }
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        return asset('storage/' . $this->image);
    }
}
