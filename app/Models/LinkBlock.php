<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'link_block_group_id',
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

    public function group(): BelongsTo
    {
        return $this->belongsTo(LinkBlockGroup::class, 'link_block_group_id');
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
