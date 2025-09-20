<?php

namespace App\Filters;

use App\Enums\CarStatus;

class CarFilter extends QueryFilter
{
    /**
     * Filter by car make
     */
    public function make(string $value): void
    {
        $this->builder->where('make', 'like', "%{$value}%");
    }

    /**
     * Filter by car model
     */
    public function model(string $value): void
    {
        $this->builder->where('model', 'like', "%{$value}%");
    }

    /**
     * Filter by country code
     */
    public function country_code(string $value): void
    {
        $this->builder->where('country_code', $value);
    }

    /**
     * Filter by car status
     */
    public function status(string $value): void
    {
        $this->builder->where('status', $value);
    }

    /**
     * Filter by minimum year
     */
    public function year_min(int $value): void
    {
        $this->builder->where('year', '>=', $value);
    }

    /**
     * Filter by maximum year
     */
    public function year_max(int $value): void
    {
        $this->builder->where('year', '<=', $value);
    }

    /**
     * Filter by minimum price in cents
     */
    public function price_min_cents(int $value): void
    {
        $this->builder->where('price_cents', '>=', $value);
    }

    /**
     * Filter by maximum price in cents
     */
    public function price_max_cents(int $value): void
    {
        $this->builder->where('price_cents', '<=', $value);
    }

    /**
     * Filter by maximum mileage in km
     */
    public function mileage_max_km(int $value): void
    {
        $this->builder->where('mileage_km', '<=', $value);
    }

    /**
     * Sort the results
     */
    public function sort(string $value): void
    {
        $direction = 'asc';
        
        if (str_starts_with($value, '-')) {
            $direction = 'desc';
            $value = substr($value, 1);
        }

        $allowedSorts = ['listed_at', 'price_cents', 'mileage_km', 'year'];
        
        if (in_array($value, $allowedSorts)) {
            $this->builder->orderBy($value, $direction);
        }
    }
}
