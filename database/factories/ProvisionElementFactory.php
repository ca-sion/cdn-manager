<?php

namespace Database\Factories;

use App\Models\ProvisionElement;
use App\Enums\ProvisionElementStatusEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProvisionElement>
 */
class ProvisionElementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'edition_id'     => 1,
            'recipient_id'   => 1,
            'recipient_type' => 'App\Models\Client',
            'provision_id'   => 1,
            'status'         => fake('fr_CH')->randomElement(ProvisionElementStatusEnum::class),
        ];
    }
}
