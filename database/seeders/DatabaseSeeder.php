<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\Dealer;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Dealer::factory(10)->create();
        Car::factory(200)->create();
    }
}
