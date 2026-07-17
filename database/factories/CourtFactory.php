<?php

namespace Database\Factories;

use App\Enums\CourtStatus;
use App\Models\Court;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Court>
 */
class CourtFactory extends Factory
{
    protected $model = Court::class;

    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'label' => (string) fake()->unique()->numberBetween(1, 999),
            'status' => CourtStatus::Available,
        ];
    }
}
