<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_delivery_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->string('charge_mode')->default('fixed');
            $table->decimal('fixed_charge', 10, 2)->default(40);
            $table->decimal('base_charge', 10, 2)->default(0);
            $table->decimal('per_km_charge', 10, 2)->default(15);
            $table->decimal('minimum_charge', 10, 2)->default(30);
            $table->decimal('free_delivery_min_order', 10, 2)->nullable();
            $table->decimal('max_delivery_distance_km', 8, 2)->nullable();
            $table->decimal('store_lat', 10, 7)->nullable();
            $table->decimal('store_lng', 10, 7)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_delivery_settings');
    }
};
