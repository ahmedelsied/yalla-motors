<?php

namespace App\Http\Requests\Api;

use App\Enums\LeadStatus;
use App\Enums\CarStatus;
use App\Models\Car;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLeadRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'car_id' => [
                'required', 
                'string', 
                'exists:cars,id',
                function ($attribute, $value, $fail) {
                    $car = Car::active()->find($value);
                    if (!$car) {
                        $fail('The selected car is not available or not active.');
                    }
                }
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'source' => ['nullable', 'string', 'max:100'],
            'utm_campaign' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'car_id.required' => 'Car ID is required.',
            'car_id.exists' => 'The selected car does not exist.',
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'phone.required' => 'Phone number is required.',
            'source.required' => 'Lead source is required.',
        ];
    }
}
