<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Models\Car;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->email(),
            'phone' => fake()->phoneNumber(),
            'source' => fake()->word(),
            'utm_campaign' => fake()->word(),
            'status' => fake()->randomElement(LeadStatus::cases())->value,
        ];
    }
}
