<?php

namespace Tests\Feature;

use App\Enums\CarStatus;
use App\Enums\LeadStatus;
use App\Jobs\LeadScoringJob;
use App\Models\Car;
use App\Models\Dealer;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LeadCreationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_can_create_lead_with_active_car(): void
    {
        Queue::fake();

        // Create a dealer and active car
        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value,
            'listed_at' => now()->subDays(1)
        ]);

        $leadData = [
            'car_id' => $car->id,
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'phone' => $this->faker->phoneNumber,
            'source' => 'website'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'car_id',
                    'name',
                    'email',
                    'phone',
                    'source',
                    'status',
                    'score'
                ]
            ]);

        // Verify lead was created
        $this->assertDatabaseHas('leads', [
            'car_id' => $car->id,
            'email' => $leadData['email'],
            'status' => LeadStatus::NEW->value
        ]);

        // Verify scoring job was dispatched
        Queue::assertPushed(LeadScoringJob::class);
    }

    public function test_cannot_create_lead_with_inactive_car(): void
    {
        // Create a dealer and inactive car
        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::SOLD->value
        ]);

        $leadData = [
            'car_id' => $car->id,
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'phone' => $this->faker->phoneNumber,
            'source' => 'website'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['car_id']);
    }

    public function test_rate_limiting_works(): void
    {
        // Create a dealer and active car
        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value
        ]);

        $leadData = [
            'car_id' => $car->id,
            'name' => $this->faker->name,
            'email' => 'test@example.com',
            'phone' => $this->faker->phoneNumber,
            'source' => 'website'
        ];

        // Submit 5 leads (should work)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/leads', $leadData);
            $response->assertStatus(201);
        }

        // 6th submission should be rate limited
        $response = $this->postJson('/api/v1/leads', $leadData);
        $response->assertStatus(429);
    }
}
