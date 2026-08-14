<?php

namespace Database\Factories;

use App\Models\RunRegistration;
use App\Enums\RunRegistrationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RunRegistration>
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
            'run_registration_type'  => $this->faker->randomElement(RunRegistrationType::cases()),
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
