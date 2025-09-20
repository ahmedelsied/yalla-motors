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
            $table->string('country_code');
            $table->string('model');
            $table->integer('year');
            $table->decimal('price', 10, 2);
            $table->string('status')->default(CarStatus::ACTIVE->value);
            $table->timestamp('listed_at');
            $table->softDeletes();
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
