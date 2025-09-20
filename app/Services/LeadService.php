<?php

namespace App\Services;

use App\Models\Lead;
use App\Enums\LeadStatus;
use App\Jobs\LeadScoringJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LeadService
{
    /**
     * Create a new lead
     */
    public function createLead(array $data): Lead
    {
        $lead = Lead::create($data);
        $lead->load('car');

        $correlationId = Str::uuid()->toString();

        // Log the lead.created event
        Log::info('lead.created', [
            'lead_id' => $lead->id,
            'correlation_id' => $correlationId,
            'car_id' => $lead->car_id,
            'email' => $lead->email,
            'source' => $lead->source,
            'utm_campaign' => $lead->utm_campaign,
            'score' => $lead->score,
            'created_at' => $lead->created_at
        ]);

        LeadScoringJob::dispatch($lead->id, $correlationId);

        return $lead->load('car');
    }
}
