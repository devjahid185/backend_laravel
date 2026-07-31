<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_orders', function (Blueprint $table): void {
            $table->decimal('delivery_lat', 10, 7)->nullable()->after('landmark');
            $table->decimal('delivery_lng', 10, 7)->nullable()->after('delivery_lat');
            $table->string('delivery_map_url')->nullable()->after('delivery_lng');
            $table->decimal('delivery_distance_km', 8, 2)->nullable()->after('delivery_fee');
            $table->string('delivery_charge_mode')->nullable()->after('delivery_distance_km');
        });
    }

    public function down(): void
    {
        Schema::table('food_orders', function (Blueprint $table): void {
            $table->dropColumn([
                'delivery_lat',
                'delivery_lng',
                'delivery_map_url',
                'delivery_distance_km',
                'delivery_charge_mode',
            ]);
        });
    }
};
