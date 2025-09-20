<?php

namespace App\Models;

use App\Enums\CarStatus;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Car extends Model
{
    /** @use HasFactory<\Database\Factories\CarFactory> */
    use HasFactory, HasUlids, Filterable;

    protected $guarded = [];

    /**
     * @return BelongsTo<Dealer>
     */
    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    /**
     * @return HasMany<Lead>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', CarStatus::ACTIVE->value);
    }

    /**
     * Boot the model and add event listeners for cache invalidation
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache when car is updated
        static::updated(function ($car) {
            $car->clearRelatedCaches();
        });

        // Clear cache when car is created
        static::created(function ($car) {
            $car->clearRelatedCaches();
        });

        // Clear cache when car is deleted
        static::deleted(function ($car) {
            $car->clearRelatedCaches();
        });
    }

    /**
     * Clear all caches related to cars
     */
    public function clearRelatedCaches(): void
    {
        // Clear all cars cache patterns
        $this->clearCarsCaches();
        
        // Clear individual car cache
        $carCacheKey = buildCacheKey('car', ['id' => $this->id]);
        Cache::forget($carCacheKey);
    }

    /**
     * Clear all cars listing caches
     */
    private function clearCarsCaches(): void
    {
        // For testing and array cache, we'll clear all cache
        // In production with Redis, you could implement pattern-based clearing
        if (config('cache.default') === 'array' || app()->environment('testing')) {
            Cache::flush();
            return;
        }

        // For Redis in production, clear specific patterns
        try {
            $redis = Cache::getRedis();
            $pattern = config('cache.prefix') . '*cars:*';
            $keys = $redis->keys($pattern);
            
            foreach ($keys as $key) {
                $cleanKey = str_replace(config('cache.prefix') . ':', '', $key);
                Cache::forget($cleanKey);
            }
        } catch (\Exception $e) {
            // Fallback to flushing all cache if Redis pattern matching fails
            Cache::flush();
        }
    }
}
