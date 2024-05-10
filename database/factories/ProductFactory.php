<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'edition_id' => 1,
            'name' => fake('fr_CH')->sentence(3),
            'description' => fake('fr_CH')->optional()->sentence(6),
            'code' => fake('fr_CH')->optional()->bothify('p-??##'),
            'unit' => fake()->optional()->randomElement(['m.', 'pièce', 'litre']),
            'price' => fake()->randomNumber(3),
            'tax_rate' => fake()->randomElement([null, '8.1', '2.5']),
        ];
    }
}
