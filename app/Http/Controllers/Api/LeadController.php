<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\CreateLeadRequest;
use App\Http\Resources\LeadResource;
use App\Services\LeadService;

class LeadController extends BaseApiController
{
    public function store(CreateLeadRequest $request, LeadService $leadService)
    {
        $validated = $request->validated();
        
        $lead = $leadService->createLead($validated);

        return $this->respondSuccess(
            new LeadResource($lead),
            201
        );
    }
}
