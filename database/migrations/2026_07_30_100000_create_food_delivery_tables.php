<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('icon', 80)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('food_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('food_category_id')->nullable()->constrained('food_categories')->nullOnDelete();
            $table->string('name', 160);
            $table->string('slug', 180)->nullable()->index();
            $table->string('image_url')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->json('size_options')->nullable();
            $table->json('spice_options')->nullable();
            $table->json('add_ons')->nullable();
            $table->unsignedInteger('preparation_minutes')->default(20);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });

        Schema::create('food_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 60)->default('Home');
            $table->string('receiver_name', 120);
            $table->string('receiver_phone', 40);
            $table->string('district', 80)->default('Bhola');
            $table->string('upazila', 80)->nullable();
            $table->string('area', 120)->nullable();
            $table->string('landmark', 160)->nullable();
            $table->text('address');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('food_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_id')->nullable()->constrained('restaurants')->nullOnDelete();
            $table->timestamps();
            $table->unique('user_id');
        });

        Schema::create('food_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('food_cart_id')->constrained('food_carts')->cascadeOnDelete();
            $table->foreignId('food_item_id')->constrained('food_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('size')->nullable();
            $table->string('spice_level')->nullable();
            $table->json('add_ons')->nullable();
            $table->text('note')->nullable();
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('food_coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('title', 120);
            $table->enum('discount_type', ['fixed', 'percent', 'free_delivery'])->default('fixed');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('minimum_order', 10, 2)->default(0);
            $table->foreignId('restaurant_id')->nullable()->constrained('restaurants')->nullOnDelete();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('food_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 40)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('food_address_id')->nullable()->constrained('food_addresses')->nullOnDelete();
            $table->string('receiver_name', 120);
            $table->string('receiver_phone', 40);
            $table->text('delivery_address');
            $table->string('delivery_area', 120)->nullable();
            $table->string('landmark', 160)->nullable();
            $table->enum('order_type', ['delivery', 'pickup'])->default('delivery');
            $table->enum('payment_method', ['cash_on_delivery', 'online'])->default('cash_on_delivery');
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded'])->default('unpaid');
            $table->enum('status', ['pending', 'accepted', 'preparing', 'picked_up', 'on_the_way', 'delivered', 'cancelled', 'rejected'])->default('pending');
            $table->decimal('items_total', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->string('coupon_code', 40)->nullable();
            $table->text('order_note')->nullable();
            $table->string('delivery_person_name', 120)->nullable();
            $table->string('delivery_person_phone', 40)->nullable();
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('food_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('food_order_id')->constrained('food_orders')->cascadeOnDelete();
            $table->foreignId('food_item_id')->nullable()->constrained('food_items')->nullOnDelete();
            $table->string('name', 160);
            $table->unsignedInteger('quantity');
            $table->string('size')->nullable();
            $table->string('spice_level')->nullable();
            $table->json('add_ons')->nullable();
            $table->text('note')->nullable();
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('food_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_id')->nullable()->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('food_item_id')->nullable()->constrained('food_items')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('food_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_id')->nullable()->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('food_item_id')->nullable()->constrained('food_items')->cascadeOnDelete();
            $table->foreignId('food_order_id')->nullable()->constrained('food_orders')->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_verified_order')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_reviews');
        Schema::dropIfExists('food_favorites');
        Schema::dropIfExists('food_order_items');
        Schema::dropIfExists('food_orders');
        Schema::dropIfExists('food_coupons');
        Schema::dropIfExists('food_cart_items');
        Schema::dropIfExists('food_carts');
        Schema::dropIfExists('food_addresses');
        Schema::dropIfExists('food_items');
        Schema::dropIfExists('food_categories');
    }
};
