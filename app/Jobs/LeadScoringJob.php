<?php

namespace App\Jobs;

use App\Models\Lead;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LeadScoringJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $leadId,
        public string $correlationId
    ) {}

    public function handle(): void
    {
        $lead = Lead::find($this->leadId);
        
        if (!$lead) {
            Log::warning('LeadScoringJob: Lead not found', [
                'lead_id' => $this->leadId,
                'correlation_id' => $this->correlationId
            ]);
            return;
        }

        $score = $this->calculateScore($lead);
        
        $lead->update(['score' => $score]);
        
        Log::info('LeadScoringJob: Score calculated', [
            'lead_id' => $this->leadId,
            'correlation_id' => $this->correlationId,
            'score' => $score
        ]);
    }

    private function calculateScore(Lead $lead): int
    {
        $score = 0;

        if (!empty($lead->phone)) {
            $score += 30;
        }

        if (!empty($lead->email)) {
            $score += 20;
        }

        if (!empty($lead->source)) {
            $score += 10;
        }

        if ($lead->car && $lead->car->listed_at) {
            $daysSinceListed = Carbon::parse($lead->car->listed_at)->diffInDays(now());
            // More recent listings get higher scores
            match(true) {
                $daysSinceListed <= 1 => $score += 40,
                $daysSinceListed <= 7 => $score += 30,
                $daysSinceListed <= 30 => $score += 20,
                $daysSinceListed <= 90 => $score += 10,
                default => 0,
            };
            
        }
        
        return $score;
    }
}
