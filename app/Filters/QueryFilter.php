<?php

namespace App\Filters;

use App\Utils\ModelFilters;
use Illuminate\Database\Eloquent\Builder;

abstract class QueryFilter
{
    /**
     * The Eloquent builder instance.
     *
     * @var Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected $builder;

    /**
     * QueryFilter constructor.
     */
    public function __construct(protected ModelFilters $filters) {}

    /**
     * Apply the filters to the builder.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $builder
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->filters->all() as $key => $value) {
            if (method_exists($this, $key)) {
                $this->callFilterMethod($key, $value);
            }
        }

        return $this->builder;
    }

    /**
     * @param  mixed  $value
     */
    protected function callFilterMethod(string $method, $value): void
    {
        $this->{$method}($value);
    }

    public function getFilters(): ModelFilters
    {
        return $this->filters;
    }
}
