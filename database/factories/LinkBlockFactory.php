<?php

namespace Database\Factories;

use App\Models\LinkBlock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LinkBlock>
 */
class LinkBlockFactory extends Factory
{
    protected $model = LinkBlock::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'url' => fake()->unique()->url(),
            'title' => fake()->sentence(3),
            'image' => null,
            'favicon_path' => null,
            'position' => 0,
        ];
    }
}
