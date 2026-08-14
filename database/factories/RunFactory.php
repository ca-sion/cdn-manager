<?php

namespace Database\Factories;

use App\Models\Run;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Run>
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
            'name'                => 'Course '.$this->faker->city.' '.$this->faker->numberBetween(5, 20).'km',
            'distance'            => $this->faker->randomFloat(2, 5, 42),
            'cost'                => $this->faker->randomElement([20.00, 25.00, 30.00, 35.00, 45.00, 50.00]),
            'available_for_types' => ['company', 'school', 'group', 'elite'],
            'start_blocs'         => [
                ['label' => 'Bloc A - Élite', 'time' => '09:30'],
                ['label' => 'Bloc B - Populaire', 'time' => '10:00'],
                ['label' => 'Bloc C - Débutant', 'time' => '10:30'],
            ],
            'registrations_deadline' => now()->addDays(30),
            'registrations_limit'    => $this->faker->numberBetween(100, 1000),
            'registrations_number'   => $this->faker->numberBetween(10, 80),
            'datasport_code'         => $this->faker->bothify('DS-####'),
            'code'                   => $this->faker->unique()->bothify('RUN-####'),
            'accepts_voucher'        => true,
        ];
    }
}
