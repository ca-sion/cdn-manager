<?php

namespace Database\Factories;

use App\Models\Provision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Provision>
 */
class ProvisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'edition_id'  => 1,
            'product_id'  => fake('fr_CH')->optional()->randomElement([1, 2, 3, 4, 5]),
            'name'        => fake()->bothify('Prestation ??'),
            'description' => fake('fr_CH')->optional()->sentence(8),
            'dicastry_id' => null,
            'type'        => fake('fr_CH')->word(1),
        ];
    }
}
