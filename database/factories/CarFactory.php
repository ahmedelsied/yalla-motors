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
            'country_code' => fake()->countryCode(),
            'model' => fake()->word(),
            'year' => fake()->year(),
            'price' => fake()->randomFloat(2, 1000, 100000),
            'status' => fake()->randomElement(CarStatus::cases()),
            'listed_at' => fake()->dateTime()->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure()
    {
        return $this->afterCreating(function ($car) {
            $width = fake()->randomElement([800, 1024, 1200, 1600]);
            $height = fake()->randomElement([600, 768, 900, 1200]);
            $imageId = fake()->numberBetween(1, 1000);
            
            $imageUrl = "https://placehold.co/{$width}x{$height}/0066CC/FFFFFF?text=Car+Image+{$imageId}";

            $car->addMediaFromUrl($imageUrl)
                ->toMediaCollection('images');

            $thumbnailUrl = "https://placehold.co/400x300/0066CC/FFFFFF?text=Car+Thumbnail";

            $car->addMediaFromUrl($thumbnailUrl)
                ->toMediaCollection('thumbnails');
        });
    }
}
