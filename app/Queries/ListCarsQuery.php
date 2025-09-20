<?php

namespace App\Queries;

use App\Enums\CarStatus;
use App\Filters\CarFilter;
use App\Models\Car;
use Illuminate\Pagination\LengthAwarePaginator;

class ListCarsQuery
{
    private LengthAwarePaginator $cars;
    private array $appliedFilters;

    public function __construct(
        private CarFilter $filters,
        private int $perPage,
        private bool $includeFacets
    ) {}

    public function handle()
    {
        return $this->setCars()
                    ->buildAppliedFilters()
                    ->buildResult();
    }

    private function setCars(): self
    {
        $this->cars = Car::where('status', CarStatus::ACTIVE->value)->filter($this->filters)->paginate($this->perPage);
        return $this;
    }

    private function buildAppliedFilters(): self
    {
        $this->appliedFilters = array_filter($this->filters->getFilters()->all(), function ($value) {
            return $value !== null && $value !== '';
        });
        unset(
            $this->appliedFilters['per_page'],
            $this->appliedFilters['sort'],
            $this->appliedFilters['include_facets']
        );

        return $this;
    }

    private function buildResult(): array
    {
        return [
            'cars' => $this->cars,
            'filters_applied' => $this->appliedFilters
        ];
    }
}