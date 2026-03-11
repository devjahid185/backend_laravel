<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('car_rental_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_rental_id')->constrained('car_rentals')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('pickup_location', 255)->nullable();
            $table->string('dropoff_location', 255)->nullable();
            $table->boolean('need_driver')->default(false);
            $table->string('contact_phone', 30)->nullable();
            $table->text('note')->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamps();

            $table->index(['car_rental_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_rental_bookings');
    }
};
