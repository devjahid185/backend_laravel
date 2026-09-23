<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_coupons', function (Blueprint $table): void {
            if (! Schema::hasColumn('food_coupons', 'source')) {
                $table->string('source', 24)->default('admin')->after('restaurant_id');
            }
            if (! Schema::hasColumn('food_coupons', 'funding_source')) {
                $table->string('funding_source', 24)->default('admin')->after('source');
            }
            if (! Schema::hasColumn('food_coupons', 'applies_to')) {
                $table->string('applies_to', 24)->default('order_items')->after('funding_source');
            }
            if (! Schema::hasColumn('food_coupons', 'max_discount')) {
                $table->decimal('max_discount', 10, 2)->nullable()->after('discount_value');
            }
            if (! Schema::hasColumn('food_coupons', 'usage_limit')) {
                $table->unsignedInteger('usage_limit')->nullable()->after('minimum_order');
            }
            if (! Schema::hasColumn('food_coupons', 'per_user_limit')) {
                $table->unsignedInteger('per_user_limit')->nullable()->after('usage_limit');
            }
            if (! Schema::hasColumn('food_coupons', 'used_count')) {
                $table->unsignedInteger('used_count')->default(0)->after('per_user_limit');
            }
            if (! Schema::hasColumn('food_coupons', 'owner_user_id')) {
                $table->foreignId('owner_user_id')->nullable()->after('restaurant_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('food_coupons', 'notes')) {
                $table->text('notes')->nullable()->after('is_active');
            }
        });

        Schema::table('food_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('food_orders', 'coupon_id')) {
                $table->foreignId('coupon_id')->nullable()->after('coupon_code')->constrained('food_coupons')->nullOnDelete();
            }
            if (! Schema::hasColumn('food_orders', 'coupon_title')) {
                $table->string('coupon_title', 160)->nullable()->after('coupon_id');
            }
            if (! Schema::hasColumn('food_orders', 'admin_discount_amount')) {
                $table->decimal('admin_discount_amount', 10, 2)->default(0)->after('discount_amount');
            }
            if (! Schema::hasColumn('food_orders', 'restaurant_discount_amount')) {
                $table->decimal('restaurant_discount_amount', 10, 2)->default(0)->after('admin_discount_amount');
            }
            if (! Schema::hasColumn('food_orders', 'delivery_discount_amount')) {
                $table->decimal('delivery_discount_amount', 10, 2)->default(0)->after('restaurant_discount_amount');
            }
            if (! Schema::hasColumn('food_orders', 'discount_breakdown')) {
                $table->json('discount_breakdown')->nullable()->after('delivery_discount_amount');
            }
        });

        if (! Schema::hasTable('food_coupon_redemptions')) {
            Schema::create('food_coupon_redemptions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('food_coupon_id')->constrained('food_coupons')->cascadeOnDelete();
                $table->foreignId('food_order_id')->constrained('food_orders')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('restaurant_id')->nullable()->constrained('restaurants')->nullOnDelete();
                $table->decimal('discount_amount', 10, 2)->default(0);
                $table->decimal('admin_discount_amount', 10, 2)->default(0);
                $table->decimal('restaurant_discount_amount', 10, 2)->default(0);
                $table->decimal('delivery_discount_amount', 10, 2)->default(0);
                $table->json('breakdown')->nullable();
                $table->timestamps();
                $table->unique('food_order_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('food_coupon_redemptions');

        Schema::table('food_orders', function (Blueprint $table): void {
            foreach (['discount_breakdown', 'delivery_discount_amount', 'restaurant_discount_amount', 'admin_discount_amount', 'coupon_title'] as $column) {
                if (Schema::hasColumn('food_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
            if (Schema::hasColumn('food_orders', 'coupon_id')) {
                $table->dropForeign(['coupon_id']);
                $table->dropColumn('coupon_id');
            }
        });

        Schema::table('food_coupons', function (Blueprint $table): void {
            foreach (['notes', 'used_count', 'per_user_limit', 'usage_limit', 'max_discount', 'applies_to', 'funding_source', 'source'] as $column) {
                if (Schema::hasColumn('food_coupons', $column)) {
                    $table->dropColumn($column);
                }
            }
            if (Schema::hasColumn('food_coupons', 'owner_user_id')) {
                $table->dropForeign(['owner_user_id']);
                $table->dropColumn('owner_user_id');
            }
        });
    }
};
