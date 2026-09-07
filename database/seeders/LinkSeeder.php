<?php

namespace Database\Seeders;

use App\Models\Link;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class LinkSeeder extends Seeder
{
    private const COUNT_PER_USER = 20;

    public function run(): void
    {
        User::all()->each(function (User $user) {
            $tagIds = Tag::query()
                ->where('user_id', $user->id)
                ->pluck('id');

            Link::factory()
                ->count(self::COUNT_PER_USER)
                ->create([
                    'user_id' => $user->id,
                ])
                ->each(function (Link $link) use ($tagIds) {
                    if ($tagIds->isEmpty()) {
                        return;
                    }

                    $link->tags()->attach(
                        $tagIds->random(
                            random_int(1, min(3, $tagIds->count()))
                        )
                    );
                });
        });
    }
}
