<?php

namespace Database\Seeders;

use App\Models\LinkBlock;
use App\Models\User;
use Illuminate\Database\Seeder;

class LinkBlockSeeder extends Seeder
{
    private const COUNT_PER_USER = 6;

    public function run(): void
    {
        User::all()->each(function (User $user) {
            for ($position = 0; $position < self::COUNT_PER_USER; $position++) {
                LinkBlock::factory()->create([
                    'user_id' => $user->id,
                    'position' => $position,
                ]);
            }
        });
    }
}
