<?php

namespace Database\Factories;

use App\Models\ProvisionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProvisionCategory>
 */
class ProvisionCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'  => fake()->bothify('Catégorie ??'),
            'color' => fake()->safeHexColor(),
        ];
    }
}
