<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Edition;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Product;
use App\Models\Provision;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! User::where('email', 'info@coursedenoel.ch')->first()) {
            User::factory()->create([
                'name' => 'Michael',
                'email' => 'info@coursedenoel.ch',
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
    }
}
