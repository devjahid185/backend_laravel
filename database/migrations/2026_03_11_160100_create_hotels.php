<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('hotel_categories')->nullOnDelete();
            $table->string('name', 160);
            $table->string('type', 60)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('facebook', 255)->nullable();
            $table->string('district', 80)->nullable();
            $table->string('upazila', 80)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('check_in', 40)->nullable();
            $table->string('check_out', 40)->nullable();
            $table->integer('rooms_total')->nullable();
            $table->integer('min_price')->nullable();
            $table->integer('max_price')->nullable();
            $table->json('amenities')->nullable();
            $table->json('services')->nullable();
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
        Schema::dropIfExists('hotels');
    }
};
