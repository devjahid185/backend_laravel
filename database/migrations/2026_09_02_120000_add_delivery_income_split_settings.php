<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_delivery_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('food_delivery_settings', 'rider_fixed_earning')) {
                $table->decimal('rider_fixed_earning', 10, 2)->default(50)->after('municipality_fixed_charge');
            }
            if (! Schema::hasColumn('food_delivery_settings', 'rider_per_km_earning')) {
                $table->decimal('rider_per_km_earning', 10, 2)->default(15)->after('municipality_extra_per_km_charge');
            }
        });

        Schema::table('food_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('food_orders', 'admin_delivery_income')) {
                $table->decimal('admin_delivery_income', 10, 2)->default(0)->after('rider_earning');
            }
        });

        Schema::table('medicine_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('medicine_orders', 'admin_delivery_income')) {
                $table->decimal('admin_delivery_income', 10, 2)->default(0)->after('rider_earning');
            }
        });
    }

    public function down(): void
    {
        Schema::table('medicine_orders', function (Blueprint $table): void {
            if (Schema::hasColumn('medicine_orders', 'admin_delivery_income')) {
                $table->dropColumn('admin_delivery_income');
            }
        });

        Schema::table('food_orders', function (Blueprint $table): void {
            if (Schema::hasColumn('food_orders', 'admin_delivery_income')) {
                $table->dropColumn('admin_delivery_income');
            }
        });

        Schema::table('food_delivery_settings', function (Blueprint $table): void {
            foreach (['rider_per_km_earning', 'rider_fixed_earning'] as $column) {
                if (Schema::hasColumn('food_delivery_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
