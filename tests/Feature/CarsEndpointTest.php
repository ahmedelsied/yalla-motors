<?php

namespace Tests\Feature;

use App\Enums\CarStatus;
use App\Models\Car;
use App\Models\Dealer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CarsEndpointTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    private array $baseJsonStructure = [
        'data' => [
            '*' => [
                'id',
                'make',
                'model',
                'year',
                'price_cents',
                'mileage_km',
                'country_code',
                'city',
                'status',
                'listed_at',
            ]
        ],
        'meta' => [
            'pagination' => [
                'current_page',
                'per_page',
                'total',
                'last_page',
                'from',
                'to'
            ],
            'filters_applied'
        ]
    ];
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_cars_endpoint_returns_paginated_results(): void
    {
        $dealer = Dealer::factory()->create();
        Car::factory()->count(25)->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value
        ]);

        $response = $this->getJson('/api/v1/cars');
        $response->assertStatus(200)
            ->assertJsonStructure($this->baseJsonStructure);

        $responseData = $response->json();
        $this->assertEquals(1, $responseData['meta']['pagination']['current_page']);
        $this->assertEquals(20, $responseData['meta']['pagination']['per_page']);
        $this->assertEquals(25, $responseData['meta']['pagination']['total']);
        $this->assertEquals(2, $responseData['meta']['pagination']['last_page']);
    }

    public function test_cars_endpoint_with_custom_pagination(): void
    {
        $dealer = Dealer::factory()->create();
        Car::factory()->count(15)->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value
        ]);

        $response = $this->getJson('/api/v1/cars?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure($this->baseJsonStructure);
        
        $responseData = $response->json();
        $this->assertEquals(10, $responseData['meta']['pagination']['per_page']);
        $this->assertEquals(15, $responseData['meta']['pagination']['total']);
        $this->assertEquals(2, $responseData['meta']['pagination']['last_page']);
    }

    public function test_cars_endpoint_with_filters(): void
    {
        $dealer = Dealer::factory()->create();
        
        Car::factory()->count(3)->create([
            'dealer_id' => $dealer->id,
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2020,
            'price_cents' => 2500000, // $25,000
            'country_code' => 'US',
            'status' => CarStatus::ACTIVE->value
        ]);

        Car::factory()->count(2)->create([
            'dealer_id' => $dealer->id,
            'make' => 'Honda',
            'model' => 'Civic',
            'year' => 2019,
            'price_cents' => 2000000, // $20,000
            'country_code' => 'CA',
            'status' => CarStatus::ACTIVE->value
        ]);

        $response = $this->getJson('/api/v1/cars?make=Toyota');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertEquals(3, $responseData['meta']['pagination']['total']);
        $this->assertEquals('Toyota', $responseData['data'][0]['make']);

        $response = $this->getJson('/api/v1/cars?year_min=2020&year_max=2021');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertEquals(3, $responseData['meta']['pagination']['total']);

        $response = $this->getJson('/api/v1/cars?price_min_cents=2000000&price_max_cents=3000000');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertEquals(5, $responseData['meta']['pagination']['total']);

        $response = $this->getJson('/api/v1/cars?country_code=US');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertEquals(3, $responseData['meta']['pagination']['total']);

        $response = $this->getJson('/api/v1/cars?make=Toyota&year_min=2020&country_code=US');
        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertEquals(3, $responseData['meta']['pagination']['total']);
    }

    public function test_cars_endpoint_with_facets(): void
    {
        $dealer = Dealer::factory()->create();
        
        Car::factory()->count(5)->create([
            'dealer_id' => $dealer->id,
            'make' => 'Toyota',
            'year' => 2020,
            'status' => CarStatus::ACTIVE->value
        ]);

        Car::factory()->count(3)->create([
            'dealer_id' => $dealer->id,
            'make' => 'Honda',
            'year' => 2021,
            'status' => CarStatus::ACTIVE->value
        ]);

        Car::factory()->count(2)->create([
            'dealer_id' => $dealer->id,
            'make' => 'Ford',
            'year' => 2020,
            'status' => CarStatus::ACTIVE->value
        ]);

        $response = $this->getJson('/api/v1/cars?include_facets=1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta',
                'facets' => [
                    'make' => [
                        '*' => [
                            'value',
                            'count'
                        ]
                    ],
                    'year' => [
                        '*' => [
                            'value',
                            'count'
                        ]
                    ]
                ]
            ]);

        $responseData = $response->json();
        
        $this->assertArrayHasKey('facets', $responseData);
        $this->assertArrayHasKey('make', $responseData['facets']);
        $this->assertArrayHasKey('year', $responseData['facets']);
        
        $makeFacets = $responseData['facets']['make'];
        $this->assertGreaterThanOrEqual($makeFacets[1]['count'], $makeFacets[0]['count']);
    }

    public function test_cars_endpoint_cache_behavior(): void
    {
        $dealer = Dealer::factory()->create();
        $cars = Car::factory()->count(5)->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value
        ]);

        $response1 = $this->getJson('/api/v1/cars');
        $response1->assertStatus(200);

        $response2 = $this->getJson('/api/v1/cars');
        $response2->assertStatus(200);

        $this->assertEquals($response1->json(), $response2->json());

        $response3 = $this->getJson('/api/v1/cars?make=Toyota');
        $response3->assertStatus(200);

        $this->assertNotEquals($response1->json(), $response3->json());
    }

    public function test_cars_endpoint_sorting(): void
    {
        $dealer = Dealer::factory()->create();
        
        Car::factory()->create([
            'dealer_id' => $dealer->id,
            'price_cents' => 1000000,
            'year' => 2020,
            'listed_at' => '2020-01-01',
            'status' => CarStatus::ACTIVE->value
        ]);

        Car::factory()->create([
            'dealer_id' => $dealer->id,
            'price_cents' => 2000000,
            'year' => 2021,
            'listed_at' => '2021-01-01',
            'status' => CarStatus::ACTIVE->value
        ]);

        Car::factory()->create([
            'dealer_id' => $dealer->id,
            'price_cents' => 3000000,
            'year' => 2019,
            'listed_at' => '2019-01-01',
            'status' => CarStatus::ACTIVE->value
        ]);

        $response = $this->getJson('/api/v1/cars?sort=price_cents');
        $response->assertStatus(200);
        
        $responseData = $response->json();

        $prices = array_column($responseData['data'], 'price_cents');
        $this->assertEquals([1000000, 2000000, 3000000], $prices);

        $response = $this->getJson('/api/v1/cars?sort=-listed_at');
        $response->assertStatus(200);
        
        $responseData = $response->json();
        $years = array_column($responseData['data'], 'listed_at');
        $this->assertEquals([
            '2021-01-01 00:00:00',
            '2020-01-01 00:00:00',
            '2019-01-01 00:00:00'
        ], $years);
    }

    public function test_cars_endpoint_only_shows_active_cars(): void
    {
        $dealer = Dealer::factory()->create();
        
        $activeCars = Car::factory()->count(3)->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value
        ]);

        $soldCars = Car::factory()->count(2)->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::SOLD->value
        ]);

        $hiddenCars = Car::factory()->count(1)->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::HIDDEN->value
        ]);

        $response = $this->getJson('/api/v1/cars');

        $response->assertStatus(200);
        $responseData = $response->json();
        $this->assertEquals(3, $responseData['meta']['pagination']['total']);
        
        // Verify all returned cars are active
        foreach ($responseData['data'] as $car) {
            $this->assertEquals(CarStatus::ACTIVE->value, $car['status']);
        }
    }

    public function test_cars_endpoint_validation(): void
    {
        // Test invalid per_page
        $response = $this->getJson('/api/v1/cars?per_page=100');
        $response->assertStatus(422);

        // Test invalid year format
        $response = $this->getJson('/api/v1/cars?year_min=invalid');
        $response->assertStatus(422);

        // Test year_min > year_max
        $response = $this->getJson('/api/v1/cars?year_min=2021&year_max=2020');
        $response->assertStatus(422);

        // Test invalid sort field
        $response = $this->getJson('/api/v1/cars?sort=invalid_field');
        $response->assertStatus(422);
    }

    public function test_cars_endpoint_filters_applied_metadata(): void
    {
        $dealer = Dealer::factory()->create();
        Car::factory()->count(5)->create([
            'dealer_id' => $dealer->id,
            'status' => CarStatus::ACTIVE->value
        ]);

        $response = $this->getJson('/api/v1/cars?make=Toyota&year_min=2020&per_page=10');

        $response->assertStatus(200);
        $responseData = $response->json();
        
        $this->assertArrayHasKey('filters_applied', $responseData['meta']);
        $filtersApplied = $responseData['meta']['filters_applied'];
        
        $this->assertEquals('Toyota', $filtersApplied['make']);
        $this->assertEquals('2020', $filtersApplied['year_min']);
        $this->assertArrayNotHasKey('per_page', $filtersApplied); // Should be excluded
    }
}
