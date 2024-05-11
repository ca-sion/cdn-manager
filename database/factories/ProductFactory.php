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
            'name' => fake()->bothify('Produit ??'),
            'description' => fake('fr_CH')->optional()->sentence(6),
            'code' => fake('fr_CH')->optional()->bothify('p-??##'),
            'unit' => fake()->optional()->randomElement(['m.', 'pièce', 'litre']),
            'cost' => fake()->randomNumber(3),
            'tax_rate' => fake()->randomElement([null, '8.1', '2.6']),
        ];
    }
}
