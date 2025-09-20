<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\ListCarRequest;
use App\Http\Resources\CarCollection;
use App\Filters\CarFilter;
use App\Http\Resources\CarResource;
use App\Services\CarService;
use Illuminate\Support\Arr;

class CarController extends BaseApiController
{
    public function __construct(
        private CarService $carService
    ) {}

    public function index(ListCarRequest $request, CarFilter $filters)
    {
        $validated = $request->validated();
        
        $result = $this->carService->getCars(
            $filters,
            $validated['per_page'] ?? 20,
            $validated['include_facets'] ?? false
        );
        
        return $this->respondSuccess(new CarCollection(
            Arr::get($result, 'cars'),
            Arr::get($result, 'filters_applied'),
            Arr::get($result, 'facets', [])
        ));
    }

    public function show(string $id)
    {
        return $this->respondSuccess(new CarResource($this->carService->showCar($id)));
    }
}
