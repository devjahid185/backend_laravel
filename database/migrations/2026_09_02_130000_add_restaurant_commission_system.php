<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table): void {
            if (! Schema::hasColumn('restaurants', 'commission_enabled')) {
                $table->boolean('commission_enabled')->default(true)->after('accepts_food_orders');
            }
            if (! Schema::hasColumn('restaurants', 'commission_type')) {
                $table->string('commission_type', 40)->default('percentage')->after('commission_enabled');
            }
            if (! Schema::hasColumn('restaurants', 'commission_rate')) {
                $table->decimal('commission_rate', 8, 2)->default(10)->after('commission_type');
            }
            if (! Schema::hasColumn('restaurants', 'commission_fixed_fee')) {
                $table->decimal('commission_fixed_fee', 10, 2)->default(0)->after('commission_rate');
            }
            if (! Schema::hasColumn('restaurants', 'settlement_cycle')) {
                $table->string('settlement_cycle', 30)->default('weekly')->after('commission_fixed_fee');
            }
        });

        Schema::table('food_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('food_orders', 'restaurant_commission_type')) {
                $table->string('restaurant_commission_type', 40)->nullable()->after('items_total');
            }
            if (! Schema::hasColumn('food_orders', 'restaurant_commission_rate')) {
                $table->decimal('restaurant_commission_rate', 8, 2)->default(0)->after('restaurant_commission_type');
            }
            if (! Schema::hasColumn('food_orders', 'restaurant_commission_fixed_fee')) {
                $table->decimal('restaurant_commission_fixed_fee', 10, 2)->default(0)->after('restaurant_commission_rate');
            }
            if (! Schema::hasColumn('food_orders', 'restaurant_commission_amount')) {
                $table->decimal('restaurant_commission_amount', 10, 2)->default(0)->after('restaurant_commission_fixed_fee');
            }
            if (! Schema::hasColumn('food_orders', 'restaurant_owner_payable')) {
                $table->decimal('restaurant_owner_payable', 10, 2)->default(0)->after('restaurant_commission_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('food_orders', function (Blueprint $table): void {
            foreach ([
                'restaurant_owner_payable',
                'restaurant_commission_amount',
                'restaurant_commission_fixed_fee',
                'restaurant_commission_rate',
                'restaurant_commission_type',
            ] as $column) {
                if (Schema::hasColumn('food_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('restaurants', function (Blueprint $table): void {
            foreach ([
                'settlement_cycle',
                'commission_fixed_fee',
                'commission_rate',
                'commission_type',
                'commission_enabled',
            ] as $column) {
                if (Schema::hasColumn('restaurants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
