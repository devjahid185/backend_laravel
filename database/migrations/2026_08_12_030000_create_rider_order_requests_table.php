<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rider_order_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('food_order_id')->constrained('food_orders')->cascadeOnDelete();
            $table->foreignId('rider_id')->constrained()->cascadeOnDelete();
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('restaurant_lat', 10, 7)->nullable();
            $table->decimal('restaurant_lng', 10, 7)->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'expired', 'cancelled'])->default('pending');
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();
            $table->unique(['food_order_id', 'rider_id']);
            $table->index(['rider_id', 'status', 'created_at']);
            $table->index(['food_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_order_requests');
    }
};
