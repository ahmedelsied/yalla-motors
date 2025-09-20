<?php

use App\Enums\CarStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('dealer_id')->constrained('dealers');
            $table->string('make')->index();
            $table->string('model')->index();
            $table->integer('year')->index();
            $table->integer('price_cents')->index();
            $table->integer('mileage_km')->index();
            $table->string('country_code')->index();
            $table->string('city')->index();
            $table->string('status')->index()->default(CarStatus::ACTIVE->value);
            $table->dateTime('listed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
