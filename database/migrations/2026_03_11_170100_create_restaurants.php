<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('restaurant_categories')->nullOnDelete();
            $table->string('name', 160);
            $table->string('type', 80)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('facebook', 255)->nullable();
            $table->string('district', 80)->nullable();
            $table->string('upazila', 80)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('opening_hours', 120)->nullable();
            $table->integer('min_price')->nullable();
            $table->integer('max_price')->nullable();
            $table->json('cuisines')->nullable();
            $table->json('features')->nullable();
            $table->boolean('delivery_available')->default(false);
            $table->boolean('takeaway_available')->default(true);
            $table->boolean('dine_in_available')->default(true);
            $table->text('description')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('views')->default(0);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
