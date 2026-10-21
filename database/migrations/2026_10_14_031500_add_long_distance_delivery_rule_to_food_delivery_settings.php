<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_delivery_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('food_delivery_settings', 'municipality_long_distance_enabled')) {
                $table->boolean('municipality_long_distance_enabled')->default(false)->after('municipality_extra_per_km_charge');
            }
            if (! Schema::hasColumn('food_delivery_settings', 'municipality_long_distance_threshold_km')) {
                $table->decimal('municipality_long_distance_threshold_km', 8, 2)->default(8)->after('municipality_long_distance_enabled');
            }
            if (! Schema::hasColumn('food_delivery_settings', 'municipality_long_distance_per_km_charge')) {
                $table->decimal('municipality_long_distance_per_km_charge', 10, 2)->default(15)->after('municipality_long_distance_threshold_km');
            }
        });

        DB::table('food_delivery_settings')->where('id', 1)->update([
            'municipality_long_distance_enabled' => false,
            'municipality_long_distance_threshold_km' => 8,
            'municipality_long_distance_per_km_charge' => 15,
        ]);
    }

    public function down(): void
    {
        Schema::table('food_delivery_settings', function (Blueprint $table): void {
            foreach ([
                'municipality_long_distance_per_km_charge',
                'municipality_long_distance_threshold_km',
                'municipality_long_distance_enabled',
            ] as $column) {
                if (Schema::hasColumn('food_delivery_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
