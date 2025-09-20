<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class LeadRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $ipKey = 'lead_rate_limit:ip:' . $ip;
        $ipAttempts = RateLimiter::attempts($ipKey);
        if ($ipAttempts >= 5) {
            return response()->json([
                'message' => 'Too many lead submissions from this IP address. Please try again later.',
                'retry_after' => RateLimiter::availableIn($ipKey)
            ], 429);
        }

        RateLimiter::hit($ipKey, 3600);
        
        return $next($request);
    }
}
