<?php

namespace App\Http\Resources;

use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CarCollection extends ResourceCollection
{

    public function __construct($resource, private array $filtersApplied = [], private $facets = [])
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $response = [
            'data' => CarResource::collection($this->collection),
            'meta' => [
                'pagination' => [
                    'current_page' => $this->currentPage(),
                    'per_page' => $this->perPage(),
                    'total' => $this->total(),
                    'last_page' => $this->lastPage(),
                    'from' => $this->firstItem(),
                    'to' => $this->lastItem()
                ],
                'filters_applied' => $this->filtersApplied,
            ],
        ];

        if($this->facets) {
            $response['facets'] = $this->facets;
        }
        return $response;
    }
}
