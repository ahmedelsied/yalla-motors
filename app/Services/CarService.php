<?php

namespace App\Services;

use App\Filters\CarFilter;
use App\Models\Car;
use App\Queries\ListCarsQuery;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CarService
{
    /**
     * Get paginated cars with optional facets
     * @param CarFilter $filters
     * @param int $perPage
     * @param bool $includeFacets
     * @return array<string, mixed>
     */
    public function getCars(CarFilter $filters, int $perPage = 20, bool $includeFacets = false): array
    {
        $cacheKey = buildCacheKey('cars', $filters->getFilters()->all());
        return Cache::remember($cacheKey, 300, function () use ($filters, $perPage, $includeFacets): array {
            
            $result = (new ListCarsQuery($filters, $perPage, $includeFacets))->handle();

            if ($includeFacets) {
                $result['facets'] = $this->getFacets($filters);
            }

            return $result;
        });
    }

    public function showCar(string $id): Car
    {
        $cacheKey = buildCacheKey('car', ['id' => $id]);
        return Cache::remember($cacheKey, 300, function () use ($id): Car {
            return Car::with('dealer')->findOrFail($id);
        });
    }

    /**
     * Get facets for make and year fields
     * @param CarFilter $filters
     * @return array<string, mixed>
     */
    private function getFacets(CarFilter $filters): array
    {
        $fields = ['make', 'year'];
        $results = [];
        
        foreach ($fields as $field) {
            $results[$field] = Car::filter($filters)
                ->select($field, DB::raw('count(*) as count'))
                ->whereNotNull($field)
                ->where($field, '!=', '')
                ->groupBy($field)
                ->orderBy('count', 'desc')
                ->limit(30)
                ->get()
                ->map(function ($item) use ($field) {
                    return [
                        'value' => $item->$field,
                        'count' => $item->count
                    ];
                })
                ->toArray();
        }
        
        return $results;
    }
}
