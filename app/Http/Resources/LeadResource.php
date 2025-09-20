<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'source' => $this->resource->source,
            'utm_campaign' => $this->resource->utm_campaign,
            'score' => (int) $this->resource->score,
            'car' => new CarResource($this->whenLoaded('car')),
            'created_at' => $this->resource->created_at,
        ];
    }
}
