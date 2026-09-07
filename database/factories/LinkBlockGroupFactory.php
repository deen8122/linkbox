<?php

namespace Database\Factories;

use App\Models\LinkBlockGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LinkBlockGroup>
 */
class LinkBlockGroupFactory extends Factory
{
    protected $model = LinkBlockGroup::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'position' => 0,
        ];
    }
}
