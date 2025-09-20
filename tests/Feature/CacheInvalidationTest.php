<?php

namespace Tests\Feature;

use App\Enums\CarStatus;
use App\Models\Car;
use App\Models\Dealer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_cars_cache_invalidated_after_car_update(): void
    {
        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2020,
            'status' => CarStatus::ACTIVE->value
        ]);

        $response1 = $this->getJson('/api/v1/cars?make=Toyota');
        $response1->assertStatus(200);

        $cacheKey = buildCacheKey('cars', ['make' => 'Toyota']);
        $this->assertTrue(Cache::has($cacheKey));

        $car->update([
            'make' => 'Honda',
            'model' => 'Civic'
        ]);

        // Make another request - should get updated data
        $response2 = $this->getJson('/api/v1/cars?make=Toyota');
        $response2->assertStatus(200);
        $updatedData = $response2->json();

        // Should return no results since we changed make from Toyota to Honda
        $this->assertEquals(0, $updatedData['meta']['pagination']['total']);

        // Test with new make
        $response3 = $this->getJson('/api/v1/cars?make=Honda');
        $response3->assertStatus(200);
        $hondaData = $response3->json();
        $this->assertEquals(1, $hondaData['meta']['pagination']['total']);
        $this->assertEquals('Honda', $hondaData['data'][0]['make']);
        $this->assertEquals('Civic', $hondaData['data'][0]['model']);
    }

    public function test_cars_cache_invalidated_after_car_status_change(): void
    {
        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value
        ]);

        // Make initial request
        $response1 = $this->getJson('/api/v1/cars');
        $response1->assertStatus(200);
        $this->assertEquals(1, $response1->json()['meta']['pagination']['total']);

        // Change car status to SOLD
        $car->update(['status' => CarStatus::SOLD->value]);

        // Make another request - car should not appear (only active cars shown)
        $response2 = $this->getJson('/api/v1/cars');
        $response2->assertStatus(200);
        $this->assertEquals(0, $response2->json()['meta']['pagination']['total']);
    }

    public function test_cars_cache_invalidated_after_car_deletion(): void
    {
        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'make' => 'Toyota',
            'status' => CarStatus::ACTIVE->value
        ]);

        // Make initial request
        $response1 = $this->getJson('/api/v1/cars?make=Toyota');
        $response1->assertStatus(200);
        $this->assertEquals(1, $response1->json()['meta']['pagination']['total']);

        // Delete the car
        $car->delete();

        // Make another request - car should not appear
        $response2 = $this->getJson('/api/v1/cars?make=Toyota');
        $response2->assertStatus(200);
        $this->assertEquals(0, $response2->json()['meta']['pagination']['total']);
    }

    public function test_cars_cache_invalidated_after_new_car_creation(): void
    {
        $dealer = Dealer::factory()->create();
        
        // Make initial request
        $response1 = $this->getJson('/api/v1/cars?make=Toyota');
        $response1->assertStatus(200);
        $this->assertEquals(0, $response1->json()['meta']['pagination']['total']);

        // Create a new Toyota car
        Car::factory()->create([
            'dealer_id' => $dealer->id,
            'make' => 'Toyota',
            'model' => 'Camry',
            'status' => CarStatus::ACTIVE->value
        ]);

        // Make another request - new car should appear
        $response2 = $this->getJson('/api/v1/cars?make=Toyota');
        $response2->assertStatus(200);
        $this->assertEquals(1, $response2->json()['meta']['pagination']['total']);
        $this->assertEquals('Toyota', $response2->json()['data'][0]['make']);
    }

    public function test_cars_cache_invalidated_after_year_update(): void
    {
        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'year' => 2020,
            'status' => CarStatus::ACTIVE->value
        ]);

        // Make initial request with year filter
        $response1 = $this->getJson('/api/v1/cars?year_min=2019&year_max=2021');
        $response1->assertStatus(200);
        $this->assertEquals(1, $response1->json()['meta']['pagination']['total']);

        // Update car year to be outside filter range
        $car->update(['year' => 2018]);

        // Make another request - car should not appear
        $response2 = $this->getJson('/api/v1/cars?year_min=2019&year_max=2021');
        $response2->assertStatus(200);
        $this->assertEquals(0, $response2->json()['meta']['pagination']['total']);
    }

    public function test_cars_cache_invalidated_after_mileage_update(): void
    {
        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'mileage_km' => 50000,
            'status' => CarStatus::ACTIVE->value
        ]);

        // Make initial request with mileage filter
        $response1 = $this->getJson('/api/v1/cars?mileage_max_km=60000');
        $response1->assertStatus(200);
        $this->assertEquals(1, $response1->json()['meta']['pagination']['total']);

        // Update car mileage to be above filter
        $car->update(['mileage_km' => 70000]);

        // Make another request - car should not appear
        $response2 = $this->getJson('/api/v1/cars?mileage_max_km=60000');
        $response2->assertStatus(200);
        $this->assertEquals(0, $response2->json()['meta']['pagination']['total']);
    }

    public function test_cars_cache_invalidated_after_country_update(): void
    {
        $dealer = Dealer::factory()->create();
        $car = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'country_code' => 'US',
            'status' => CarStatus::ACTIVE->value
        ]);

        // Make initial request with country filter
        $response1 = $this->getJson('/api/v1/cars?country_code=US');
        $response1->assertStatus(200);
        $this->assertEquals(1, $response1->json()['meta']['pagination']['total']);

        // Update car country
        $car->update(['country_code' => 'CA']);

        // Make another request - car should not appear
        $response2 = $this->getJson('/api/v1/cars?country_code=US');
        $response2->assertStatus(200);
        $this->assertEquals(0, $response2->json()['meta']['pagination']['total']);

        // Test with new country
        $response3 = $this->getJson('/api/v1/cars?country_code=CA');
        $response3->assertStatus(200);
        $this->assertEquals(1, $response3->json()['meta']['pagination']['total']);
    }

    public function test_cars_cache_invalidated_after_multiple_cars_update(): void
    {
        $dealer = Dealer::factory()->create();
        
        $car1 = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'make' => 'Toyota',
            'status' => CarStatus::ACTIVE->value
        ]);

        $car2 = Car::factory()->create([
            'dealer_id' => $dealer->id,
            'make' => 'Honda',
            'status' => CarStatus::ACTIVE->value
        ]);

        // Make initial request
        $response1 = $this->getJson('/api/v1/cars');
        $response1->assertStatus(200);
        $this->assertEquals(2, $response1->json()['meta']['pagination']['total']);

        // Update both cars
        $car1->update(['make' => 'Ford']);
        $car2->update(['make' => 'BMW']);

        // Make another request - should show updated makes
        $response2 = $this->getJson('/api/v1/cars');
        $response2->assertStatus(200);
        $this->assertEquals(2, $response2->json()['meta']['pagination']['total']);

        $makes = array_column($response2->json()['data'], 'make');
        $this->assertContains('Ford', $makes);
        $this->assertContains('BMW', $makes);
        $this->assertNotContains('Toyota', $makes);
        $this->assertNotContains('Honda', $makes);
    }
}
