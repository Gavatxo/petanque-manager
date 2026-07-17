<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'name' => 'Équipe '.fake()->unique()->numberBetween(1, 100000),
            'seed' => fake()->unique()->numberBetween(1, 100000),
        ];
    }
}
