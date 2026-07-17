<?php

namespace Database\Factories;

use App\Models\Registration;
use App\Models\RegistrationPlayer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationPlayer>
 */
class RegistrationPlayerFactory extends Factory
{
    protected $model = RegistrationPlayer::class;

    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->optional()->phoneNumber(),
            'is_captain' => false,
        ];
    }
}
