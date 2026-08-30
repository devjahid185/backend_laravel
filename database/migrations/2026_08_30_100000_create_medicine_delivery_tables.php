<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('source_id')->nullable()->unique();
            $table->string('slug')->nullable()->index();
            $table->string('brand_name', 180)->index();
            $table->string('dosage_form', 80)->nullable()->index();
            $table->string('strength', 120)->nullable();
            $table->string('generic_name', 180)->nullable()->index();
            $table->unsignedBigInteger('generic_id')->nullable()->index();
            $table->string('company', 180)->nullable()->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->decimal('unit_price', 10, 2)->nullable();
            $table->string('price_text')->nullable();
            $table->string('pack_sizes')->nullable();
            $table->string('image_url')->nullable();
            $table->longText('indications')->nullable();
            $table->longText('composition')->nullable();
            $table->longText('pharmacology')->nullable();
            $table->longText('dosage_and_administration')->nullable();
            $table->longText('interaction')->nullable();
            $table->longText('contraindications')->nullable();
            $table->longText('side_effects')->nullable();
            $table->longText('pregnancy_and_lactation')->nullable();
            $table->longText('precautions_and_warnings')->nullable();
            $table->longText('overdose_effects')->nullable();
            $table->string('therapeutic_class')->nullable()->index();
            $table->longText('storage_conditions')->nullable();
            $table->json('sections')->nullable();
            $table->boolean('is_available')->default(true)->index();
            $table->boolean('is_promoted')->default(false)->index();
            $table->boolean('prescription_required')->default(false)->index();
            $table->unsignedInteger('stock_quantity')->nullable();
            $table->timestamps();
        });

        Schema::create('medicine_carts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('medicine_cart_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medicine_cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('medicine_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_no')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rider_id')->nullable()->constrained('riders')->nullOnDelete();
            $table->string('receiver_name', 120);
            $table->string('receiver_phone', 40);
            $table->text('delivery_address');
            $table->string('delivery_area')->nullable();
            $table->decimal('delivery_lat', 10, 7)->nullable();
            $table->decimal('delivery_lng', 10, 7)->nullable();
            $table->string('delivery_map_url')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->string('payment_method', 40)->default('cash_on_delivery');
            $table->string('payment_status', 30)->default('unpaid')->index();
            $table->decimal('items_total', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->text('order_note')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('medicine_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medicine_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medicine_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('brand_name', 180);
            $table->string('generic_name', 180)->nullable();
            $table->string('strength', 120)->nullable();
            $table->string('company', 180)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_order_items');
        Schema::dropIfExists('medicine_orders');
        Schema::dropIfExists('medicine_cart_items');
        Schema::dropIfExists('medicine_carts');
        Schema::dropIfExists('medicine_items');
    }
};
