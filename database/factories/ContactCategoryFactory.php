<?php

namespace Database\Factories;

use App\Models\ContactCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactCategory>
 */
class ContactCategoryFactory extends Factory
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
