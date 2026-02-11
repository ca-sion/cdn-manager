<?php

namespace Database\Factories;

use App\Enums\RunRegistrationTypesEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RunRegistration>
 */
class RunRegistrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type'                   => $this->faker->randomElement(RunRegistrationTypesEnum::cases()),
            'invoicing_company_name' => $this->faker->company,
            'invoicing_address'      => $this->faker->streetAddress,
            'invoicing_postal_code'  => $this->faker->postcode,
            'invoicing_locality'     => $this->faker->city,
            'invoicing_email'        => $this->faker->email,
            'contact_first_name'     => $this->faker->firstName,
            'contact_last_name'      => $this->faker->lastName,
            'contact_email'          => $this->faker->email,
            'contact_phone'          => $this->faker->phoneNumber,
        ];
    }
}
