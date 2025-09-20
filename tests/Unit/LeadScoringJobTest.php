<?php

namespace Tests\Unit;

use App\Enums\CarStatus;
use App\Enums\LeadStatus;
use App\Jobs\LeadScoringJob;
use App\Models\Car;
use App\Models\Dealer;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LeadScoringJobTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_calculates_score_with_all_fields(): void
    {
        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value,
            'listed_at' => now()->subHours(12) // Listed 12 hours ago (within 1 day)
        ]);

        $lead = Lead::factory()->create([
            'car_id' => $car->id,
            'phone' => '+1234567890',
            'email' => 'test@example.com',
            'source' => 'website',
            'status' => LeadStatus::NEW->value
        ]);

        $correlationId = 'test-correlation-id';
        $job = new LeadScoringJob($lead->id, $correlationId);
        $job->handle();

        $lead->refresh();
        
        // Expected score: 30 (phone) + 20 (email) + 10 (source) + 40 (recent listing) = 100
        $this->assertEquals(100, $lead->score);
    }

    public function test_calculates_score_without_phone(): void
    {
        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value,
            'listed_at' => now()->subDays(2) // Listed 2 days ago
        ]);

        $lead = Lead::factory()->create([
            'car_id' => $car->id,
            'phone' => null,
            'email' => 'test@example.com',
            'source' => 'website',
            'status' => LeadStatus::NEW->value
        ]);

        $job = new LeadScoringJob($lead->id, 'test-correlation-id');
        $job->handle();

        $lead->refresh();
        
        // Expected score: 0 (no phone) + 20 (email) + 10 (source) + 30 (2 days old) = 60
        $this->assertEquals(60, $lead->score);
    }

    public function test_calculates_score_without_email(): void
    {
        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value,
            'listed_at' => now()->subDays(5) // Listed 5 days ago
        ]);

        $lead = Lead::factory()->create([
            'car_id' => $car->id,
            'phone' => '+1234567890',
            'email' => null,
            'source' => 'website',
            'status' => LeadStatus::NEW->value
        ]);

        $job = new LeadScoringJob($lead->id, 'test-correlation-id');
        $job->handle();

        $lead->refresh();
        
        // Expected score: 30 (phone) + 0 (no email) + 10 (source) + 30 (5 days old) = 70
        $this->assertEquals(70, $lead->score);
    }

    public function test_calculates_score_without_source(): void
    {
        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value,
            'listed_at' => now()->subDays(10) // Listed 10 days ago
        ]);

        $lead = Lead::factory()->create([
            'car_id' => $car->id,
            'phone' => '+1234567890',
            'email' => 'test@example.com',
            'source' => null,
            'status' => LeadStatus::NEW->value
        ]);

        $job = new LeadScoringJob($lead->id, 'test-correlation-id');
        $job->handle();

        $lead->refresh();
        
        // Expected score: 30 (phone) + 20 (email) + 0 (no source) + 20 (10 days old) = 70
        $this->assertEquals(70, $lead->score);
    }

    public function test_calculates_score_with_old_listing(): void
    {
        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value,
            'listed_at' => now()->subDays(100) // Listed 100 days ago
        ]);

        $lead = Lead::factory()->create([
            'car_id' => $car->id,
            'phone' => '+1234567890',
            'email' => 'test@example.com',
            'source' => 'website',
            'status' => LeadStatus::NEW->value
        ]);

        $job = new LeadScoringJob($lead->id, 'test-correlation-id');
        $job->handle();

        $lead->refresh();
        
        // Expected score: 30 (phone) + 20 (email) + 10 (source) + 0 (old listing) = 60
        $this->assertEquals(60, $lead->score);
    }
}
