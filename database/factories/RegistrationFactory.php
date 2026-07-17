<?php

namespace Database\Factories;

use App\Enums\RegistrationStatus;
use App\Models\Registration;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    protected $model = Registration::class;

    public function definition(): array
    {
        return [
            'tournament_id' => Tournament::factory(),
            'team_name' => 'Équipe '.fake()->unique()->numberBetween(1, 100000),
            'follow_token' => (string) Str::ulid(),
            'status' => RegistrationStatus::Pending,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => RegistrationStatus::Confirmed, 'confirmed_at' => now()]);
    }

    public function checkedIn(): static
    {
        return $this->state(fn () => [
            'status' => RegistrationStatus::CheckedIn,
            'confirmed_at' => now(),
            'checked_in_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => RegistrationStatus::Cancelled, 'cancelled_at' => now()]);
    }
}
