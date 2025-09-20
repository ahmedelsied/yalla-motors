<?php

namespace App\Http\Requests\Api;

use App\Enums\CarStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListCarRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'make' => ['nullable', 'filled', 'string'],
            'model' => ['nullable', 'filled', 'string'],
            'country_code' => ['nullable', 'filled', 'string'],
            'status' => ['nullable', 'filled', 'string', Rule::in(CarStatus::values())],

            'year_min' => ['nullable', 'filled', 'date_format:Y'],
            'year_max' => ['nullable', 'filled', 'gte:year_min', 'date_format:Y'],

            'price_min_cents' => ['nullable', 'filled', 'integer', 'min:0'],
            'price_max_cents' => ['nullable', 'filled', 'gte:price_min_cents', 'integer', 'min:0'],

            'mileage_max_km' => ['nullable', 'filled', 'integer', 'min:0'],

            'sort' => ['nullable', 'filled', 'string', Rule::in(['listed_at', 'price_cents', 'mileage_km', 'year'])],
            'per_page' => ['nullable', 'filled', 'integer', 'min:1', 'max:50'],
            'include_facets' => ['nullable', 'boolean'],
        ];
    }
}
