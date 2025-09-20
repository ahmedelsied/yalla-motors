<?php

namespace Database\Factories;

use App\Enums\CarStatus;
use App\Models\Dealer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class CarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dealer_id' => Dealer::inRandomOrder()->first()->id,
            'make' => fake()->word(),
            'model' => fake()->word(),
            'year' => fake()->year(),
            'price_cents' => fake()->numberBetween(1000, 100000),
            'mileage_km' => fake()->numberBetween(1000, 100000),
            'country_code' => fake()->countryCode(),
            'city' => fake()->city(),
            'status' => fake()->randomElement(CarStatus::cases())->value,
            'listed_at' => fake()->dateTime()->format('Y-m-d H:i:s')
        ];
    }
}
