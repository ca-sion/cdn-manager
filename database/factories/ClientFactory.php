<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'              => fake('fr_CH')->company(),
            'long_name'         => fake('fr_CH')->company(),
            'email'             => fake('fr_CH')->unique()->companyEmail(),
            'phone'             => fake('de_CH')->optional()->phoneNumber(),
            'website'           => fake('dr_CH')->optional()->domainName(),
            'address'           => fake('fr_CH')->streetName(),
            'address_extension' => fake('fr_CH')->optional()->streetName(),
            'locality'          => fake('fr_CH')->city(),
            'postal_code'       => fake('fr_CH')->postcode(),
            'country_code'      => fake()->optional()->randomElement(['CH', 'FR', 'DE']),
            'iban'              => fake('fr_CH')->optional()->bankAccountNumber(),
            'iban_qr'           => fake('fr_CH')->optional()->bankAccountNumber(),
            'ide'               => fake('fr_BE')->optional()->vat(),
        ];
    }
}
