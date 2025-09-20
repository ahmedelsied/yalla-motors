<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Car extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\CarFactory> */
    use HasFactory, HasUlids, InteractsWithMedia;

    protected $fillable = ['dealer_id', 'country_code', 'model', 'year', 'price', 'status', 'listed_at'];

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }
}
