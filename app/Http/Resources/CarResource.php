<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'price_cents' => $this->price_cents,
            'mileage_km' => $this->mileage_km,
            'country_code' => $this->country_code,
            'city' => $this->city,
            'status' => $this->status,
            'listed_at' => $this->listed_at,
            'dealer' => new DealerResource($this->whenLoaded('dealer')),
        ];
    }
}
