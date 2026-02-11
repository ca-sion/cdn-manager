<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Run>
 */
class RunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'                   => $this->faker->words(3, true),
            'distance'               => $this->faker->randomFloat(2, 5, 42),
            'cost'                   => $this->faker->randomFloat(2, 10, 100),
            'available_for_types'    => ['company', 'group'],
            'start_blocs'            => ['10:00', '10:15'],
            'registrations_deadline' => $this->faker->dateTimeBetween('now', '+1 month'),
            'registrations_limit'    => $this->faker->numberBetween(100, 1000),
            'registrations_number'   => $this->faker->numberBetween(1, 100),
            'datasport_code'         => $this->faker->bothify('##??'),
            'code'                   => $this->faker->unique()->slug,
            'accepts_voucher'        => $this->faker->boolean,
        ];
    }
}
