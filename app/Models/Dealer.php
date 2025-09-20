<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dealer extends Model
{
    /** @use HasFactory<\Database\Factories\DealerFactory> */
    use HasFactory, HasUlids;

    protected $fillable = ['name'];

    /**
     * @return HasMany<Car>
     */
    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }
}
