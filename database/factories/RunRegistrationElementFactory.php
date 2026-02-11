<?php

namespace Database\Factories;

use App\Models\Run;
use App\Models\RunRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RunRegistrationElement>
 */
class RunRegistrationElementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'run_registration_id' => RunRegistration::factory(),
            'first_name'          => $this->faker->firstName,
            'last_name'           => $this->faker->lastName,
            'birthdate'           => $this->faker->date(),
            'gender'              => $this->faker->randomElement(['M', 'F']),
            'nationality'         => $this->faker->countryCode,
            'email'               => $this->faker->safeEmail,
            'run_id'              => Run::factory(),
            'run_name'            => $this->faker->word,
            'bloc'                => $this->faker->word,
            'with_video'          => $this->faker->boolean,
        ];
    }
}
