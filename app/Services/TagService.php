<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Support\Str;

class TagService
{
    public function getOrCreate(string $name, int $userId): Tag
    {
        return Tag::firstOrCreate(
            [
                'user_id' => $userId,
                'slug' => Str::slug($name),
            ],
            [
                'name' => $name,
            ],
        );
    }
}
