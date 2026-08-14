<?php

namespace Database\Factories;

use App\Models\Dicastry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dicastry>
 */
class DicastryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->bothify('Dicastère ??'),
        ];
    }
}
