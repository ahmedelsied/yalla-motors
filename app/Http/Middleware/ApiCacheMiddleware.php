<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiCacheMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        $cacheKey = buildCacheKey('api_cache', ['path' => $request->path(), 'query' => $request->getQueryString()]);

        $ifNoneMatch = $request->header('If-None-Match');
        if ($ifNoneMatch) {
            $cachedResponse = Cache::get($cacheKey);
            if ($cachedResponse && $cachedResponse['etag'] === $ifNoneMatch) {
                $this->addObservabilityHeaders($response = response('', 304), $cacheKey, 'HIT', $startTime);
                return $response;
            }
        }

        $cachedData = Cache::get($cacheKey);
        
        if ($cachedData) {
            $response = $this->buildResponseFromCache($cachedData);
            $this->addObservabilityHeaders($response, $cacheKey, 'HIT', $startTime);
            $this->handleStaleWhileRevalidate($request, $next, $cacheKey, $cachedData);
            
            return $response;
        }

        $response = $next($request);

        if ($response->getStatusCode() === 200) {
            $this->cacheResponse($response, $cacheKey);
        }
        
        $this->addObservabilityHeaders($response, $cacheKey, 'MISS', $startTime);
        
        return $response;
    }
    
    private function cacheResponse(Response $response, string $cacheKey): void
    {
        $content = $response->getContent();
        $etag = $this->generateETag($content);
        
        $cacheData = [
            'content' => $content,
            'headers' => $response->headers->all(),
            'etag' => $etag,
            'cached_at' => now()->timestamp,
            'max_age' => 60,
            'stale_while_revalidate' => 120,
        ];
        
        Cache::put($cacheKey, $cacheData, 180);
    }

    private function buildResponseFromCache(array $cachedData): Response
    {
        $response = response($cachedData['content']);
        
        foreach ($cachedData['headers'] as $name => $values) {
            $response->headers->set($name, $values);
        }
        
        return $response;
    }
    
    private function generateETag(string $content): string
    {
        return '"' . hash('sha256', $content) . '"';
    }
    
    private function addObservabilityHeaders(Response $response, string $cacheKey, string $cacheStatus, float $startTime): void
    {
        $queryTime = round((microtime(true) - $startTime) * 1000, 2);
        
        $response->headers->set('Cache-Control', 'public, max-age=60, stale-while-revalidate=120');
        $response->headers->set('ETag', $this->getETagFromResponse($response));
        
        $response->headers->set('X-Cache', $cacheStatus);
        $response->headers->set('X-Cache-Key', $cacheKey);
        $response->headers->set('X-Query-Time-ms', $queryTime);
        
        if ($cacheStatus === 'HIT') {
            $cachedData = Cache::get($cacheKey);
            if ($cachedData) {
                $age = now()->timestamp - $cachedData['cached_at'];
                $response->headers->set('Age', $age);
            }
        }
    }
    
    private function getETagFromResponse(Response $response): string
    {
        $etag = $response->headers->get('ETag');
        if (!$etag) {
            $etag = $this->generateETag($response->getContent());
        }
        return $etag;
    }
    
    private function handleStaleWhileRevalidate(Request $request, Closure $next, string $cacheKey, array $cachedData): void
    {
        $age = now()->timestamp - $cachedData['cached_at'];
        $maxAge = $cachedData['max_age'];
        $staleWhileRevalidate = $cachedData['stale_while_revalidate'];
        
        if ($age > $maxAge && $age <= ($maxAge + $staleWhileRevalidate)) {
            $mutexKey = $cacheKey . ':refresh';
            
            if (!Cache::has($mutexKey)) {
                Cache::put($mutexKey, true, 5);
                
                $this->refreshCacheInBackground($request, $next, $cacheKey);
            }
        }
    }
    
    private function refreshCacheInBackground(Request $request, Closure $next, string $cacheKey): void
    {
        Log::info('Background cache refresh initiated', ['cache_key' => $cacheKey]);
        
        try {
            $response = $next($request);
            
            if ($response->getStatusCode() === 200) {
                $this->cacheResponse($response, $cacheKey);
                Log::info('Background cache refresh completed', ['cache_key' => $cacheKey]);
            }
        } catch (\Exception $e) {
            Log::error('Background cache refresh failed', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
        } finally {
            Cache::forget($cacheKey . ':refresh');
        }
    }
}
