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
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LeadsEndpointTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private array $baseJsonStructure = [
        'id',
        'name',
        'email',
        'phone',
        'source',
        'utm_campaign',
        'score',
        'car',
        'created_at'
    ];
    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('*');
    }

    public function test_can_create_lead_with_active_car(): void
    {
        Queue::fake();

        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value,
            'listed_at' => now()->subDays(1)
        ]);

        $leadData = [
            'car_id' => $car->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'source' => 'website',
            'utm_campaign' => 'summer_sale'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201)
            ->assertJsonStructure($this->baseJsonStructure);

        $this->assertDatabaseHas('leads', [
            'car_id' => $car->id,
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'status' => LeadStatus::NEW->value
        ]);

        Queue::assertPushed(LeadScoringJob::class, function ($job) use ($car) {
            return $job->leadId !== null && $job->correlationId !== null;
        });
    }

    public function test_cannot_create_lead_with_inactive_car(): void
    {
        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::SOLD->value
        ]);

        $leadData = [
            'car_id' => $car->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+1234567890',
            'source' => 'website'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['car_id']);
    }

    public function test_cannot_create_lead_with_nonexistent_car(): void
    {
        $leadData = [
            'car_id' => 'nonexistent-id',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+1234567890',
            'source' => 'website'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['car_id']);
    }

    public function test_lead_creation_validation(): void
    {
        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value
        ]);

        $response = $this->postJson('/api/v1/leads', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['car_id', 'name', 'email', 'phone']);

        $response = $this->postJson('/api/v1/leads', [
            'car_id' => $car->id,
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'phone' => '+1234567890'
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $response = $this->postJson('/api/v1/leads', [
            'car_id' => $car->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => str_repeat('1', 25)
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_rate_limiting_by_ip(): void
    {
        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value
        ]);

        $leadData = [
            'car_id' => $car->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'source' => 'website'
        ];

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/leads', $leadData);
            $response->assertStatus(201);
        }

        $response = $this->postJson('/api/v1/leads', $leadData);
        $response->assertStatus(429)
            ->assertJsonStructure([
                'message',
                'retry_after'
            ]);
    }

    public function test_rate_limiting_resets_after_time_window(): void
    {
        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value
        ]);

        $leadData = [
            'car_id' => $car->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'source' => 'website'
        ];

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/leads', $leadData);
            $response->assertStatus(201);
        }

        $response = $this->postJson('/api/v1/leads', $leadData);
        $response->assertStatus(429);

        RateLimiter::clear('lead_rate_limit:ip:127.0.0.1');

        $response = $this->postJson('/api/v1/leads', $leadData);
        $response->assertStatus(201);
    }

    public function test_lead_creation_with_optional_fields(): void
    {
        Queue::fake();

        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value
        ]);

        $leadData = [
            'car_id' => $car->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'source' => null,
            'utm_campaign' => 'winter_sale'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('leads', [
            'car_id' => $car->id,
            'email' => 'john@example.com',
            'source' => null,
            'utm_campaign' => 'winter_sale'
        ]);
    }

    public function test_lead_creation_logs_event(): void
    {
        Queue::fake();

        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value
        ]);

        $leadData = [
            'car_id' => $car->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'source' => 'website'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);
        $response->assertStatus(201);
    }

    public function test_lead_creation_returns_correct_response_format(): void
    {
        Queue::fake();

        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value
        ]);

        $leadData = [
            'car_id' => $car->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'source' => 'website',
            'utm_campaign' => 'summer_sale'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201)
            ->assertJsonStructure($this->baseJsonStructure);
    }

    public function test_lead_creation_with_different_sources(): void
    {
        Queue::fake();

        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value
        ]);

        $sources = ['website', 'facebook', 'google', 'referral', 'walk_in'];

        foreach ($sources as $source) {
            $leadData = [
                'car_id' => $car->id,
                'name' => 'John Doe',
                'email' => "john+{$source}@example.com",
                'phone' => '+1234567890',
                'source' => $source
            ];

            $response = $this->postJson('/api/v1/leads', $leadData);
            $response->assertStatus(201);

            $this->assertDatabaseHas('leads', [
                'email' => "john+{$source}@example.com",
                'source' => $source
            ]);
        }
    }

    public function test_lead_creation_with_special_characters(): void
    {
        Queue::fake();

        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value
        ]);

        $leadData = [
            'car_id' => $car->id,
            'name' => 'José María García-López',
            'email' => 'josé.maría@example.com',
            'phone' => '+1-555-123-4567',
            'source' => 'website'
        ];

        $response = $this->postJson('/api/v1/leads', $leadData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('leads', [
            'name' => 'José María García-López',
            'email' => 'josé.maría@example.com',
            'phone' => '+1-555-123-4567'
        ]);
    }
}
