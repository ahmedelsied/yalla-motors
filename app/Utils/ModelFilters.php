<?php

namespace App\Utils;

class ModelFilters
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(protected array $filters) {}

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->filters;
    }
}
