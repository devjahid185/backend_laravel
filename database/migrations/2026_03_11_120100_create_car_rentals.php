<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('car_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('car_categories')->nullOnDelete();
            $table->string('title', 160);
            $table->string('brand', 80)->nullable();
            $table->string('model', 80)->nullable();
            $table->string('variant', 80)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('fuel_type', 40)->nullable();
            $table->string('transmission', 40)->nullable();
            $table->unsignedTinyInteger('seats')->nullable();
            $table->unsignedTinyInteger('doors')->nullable();
            $table->string('color', 40)->nullable();
            $table->string('reg_no', 60)->nullable();
            $table->decimal('price_per_day', 10, 2)->nullable();
            $table->decimal('price_per_hour', 10, 2)->nullable();
            $table->decimal('price_per_km', 10, 2)->nullable();
            $table->boolean('driver_available')->default(false);
            $table->boolean('ac_available')->default(true);
            $table->boolean('gps_available')->default(false);
            $table->boolean('delivery_available')->default(false);
            $table->string('district', 80)->nullable();
            $table->string('upazila', 80)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('pickup_location', 255)->nullable();
            $table->string('dropoff_location', 255)->nullable();
            $table->json('features')->nullable();
            $table->text('description')->nullable();
            $table->text('terms')->nullable();
            $table->date('available_from')->nullable();
            $table->date('available_to')->nullable();
            $table->string('status', 30)->default('active');
            $table->unsignedInteger('views')->default(0);
            $table->decimal('rating', 4, 2)->default(0);
            $table->timestamps();

            $table->index(['category_id', 'district']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_rentals');
    }
};
