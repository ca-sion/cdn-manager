<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake('fr_CH')->firstName(),
            'last_name' => fake('fr_CH')->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake('de_CH')->optional()->mobileNumber(),
            'company' => fake('fr_CH')->optional()->company(),
            'role' => fake('fr_CH')->optional()->jobTitle(),
            'department' => fake('fr_CH')->optional()->catchPhrase(),
            'address' => fake('fr_CH')->optional()->streetName(),
            'locality' => fake('fr_CH')->optional()->city(),
            'postal_code' => fake()->optional()->postcode(),
            'country_code' => fake()->optional()->randomElement(['CH', 'FR', 'DE']),
            'salutation' => fake()->optional()->title(),
            'language' => fake()->optional()->randomElement(['fr', 'de', 'en']),
        ];
    }
}
