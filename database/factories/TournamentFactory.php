<?php

namespace Database\Factories;

use App\Enums\TeamFormat;
use App\Enums\TournamentStatus;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tournament>
 */
class TournamentFactory extends Factory
{
    protected $model = Tournament::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'Concours de '.fake()->unique()->city(),
            'description' => fake()->optional()->sentence(),
            'location' => fake()->city(),
            'scheduled_at' => fake()->dateTimeBetween('now', '+2 months'),
            'team_format' => fake()->randomElement(TeamFormat::cases()),
            'qualifying_rounds' => fake()->numberBetween(2, 5),
            'tableaux_count' => fake()->numberBetween(1, 4),
            'points_target' => 13,
            'max_teams' => fake()->optional()->numberBetween(16, 128),
            'status' => TournamentStatus::Draft,
            'registration_token' => (string) Str::ulid(),
            'settings' => null,
        ];
    }

    public function registrationOpen(): static
    {
        return $this->state(fn () => ['status' => TournamentStatus::RegistrationOpen]);
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'status' => TournamentStatus::Archived,
            'archived_at' => now(),
        ]);
    }
}
