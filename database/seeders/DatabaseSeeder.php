<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Edition;
use App\Models\Product;
use App\Models\Dicastry;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Provision;
use App\Models\ClientCategory;
use Illuminate\Database\Seeder;
use App\Models\ProvisionElement;
use App\Models\ProvisionCategory;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! User::where('email', 'info@coursedenoel.ch')->first()) {
            User::factory()->create([
                'name'     => 'Michael',
                'email'    => 'info@coursedenoel.ch',
                'password' => '$2y$12$GF7QBo/M5uYYkmphwfNvNOxbFA.0Aw9xHOtNEwXb8iy.InmOtYKUW',
            ]);
        }

        Edition::updateOrCreate(
            ['name' => '55e édition', 'year' => 2024],
            ['year' => 2024]
        );

        Edition::updateOrCreate(
            ['name' => '56e édition', 'year' => 2025],
            ['year' => 2025]
        );

        Contact::factory(15)->create();
        Client::factory(15)->create();
        Product::factory(5)->create();
        Provision::factory(5)->create();
        Dicastry::factory(5)->create();
        ClientCategory::factory(5)->create();
        ProvisionCategory::factory(5)->create();

        // Elements
        Provision::factory()->create([
            'name'        => 'Avec produit',
            'has_product' => true,
            'product_id'  => 1,
        ]);
        Provision::factory()->create([
            'name'      => 'Avec media',
            'has_media' => true,
        ]);
        Provision::factory()->create([
            'name'    => 'Avec VIP',
            'has_vip' => true,
        ]);

        ProvisionElement::factory()->create([
            'provision_id' => 6,
            'has_product'  => true,
            'unit'         => fake()->optional()->randomElement(['m.', 'pièce', 'litre']),
            'cost'         => fake()->randomNumber(3),
            'tax_rate'     => fake()->randomElement([null, '8.1', '2.6']),
            'quantity'     => fake()->randomElement([1, 2, 5, 10]),
        ]);

        ProvisionElement::factory()->create([
            'provision_id'          => 7,
            'vip_category'          => 'company',
            'vip_invitation_number' => 2,
            'vip_response_status'   => null,
        ]);

    }
}
