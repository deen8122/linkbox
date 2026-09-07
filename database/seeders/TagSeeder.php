<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    private const NAMES = [
        'github.com',
        'youtube.com',
        'laravel.com',
        'stackoverflow.com',
        'habr.com',
        'medium.com',
        'работа',
        'обучение',
        'идеи',
        'важное',
    ];

    public function run(): void
    {
        User::all()->each(function (User $user) {
            foreach (self::NAMES as $name) {
                Tag::factory()->create([
                    'user_id' => $user->id,
                    'name' => $name,
                    'slug' => Str::slug($name),
                ]);
            }
        });
    }
}
