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
            if (! Schema::hasColumn('food_delivery_settings', 'municipality_rule_enabled')) {
                $table->boolean('municipality_rule_enabled')->default(false)->after('charge_mode');
            }
            if (! Schema::hasColumn('food_delivery_settings', 'municipality_fixed_charge')) {
                $table->decimal('municipality_fixed_charge', 10, 2)->default(50)->after('municipality_rule_enabled');
            }
            if (! Schema::hasColumn('food_delivery_settings', 'municipality_extra_per_km_charge')) {
                $table->decimal('municipality_extra_per_km_charge', 10, 2)->default(15)->after('municipality_fixed_charge');
            }
            if (! Schema::hasColumn('food_delivery_settings', 'municipality_center_lat')) {
                $table->decimal('municipality_center_lat', 10, 7)->nullable()->after('municipality_extra_per_km_charge');
            }
            if (! Schema::hasColumn('food_delivery_settings', 'municipality_center_lng')) {
                $table->decimal('municipality_center_lng', 10, 7)->nullable()->after('municipality_center_lat');
            }
            if (! Schema::hasColumn('food_delivery_settings', 'municipality_radius_km')) {
                $table->decimal('municipality_radius_km', 8, 2)->nullable()->after('municipality_center_lng');
            }
            if (! Schema::hasColumn('food_delivery_settings', 'municipality_polygon')) {
                $table->json('municipality_polygon')->nullable()->after('municipality_radius_km');
            }
        });

        DB::table('food_delivery_settings')->where('id', 1)->update([
            'municipality_rule_enabled' => true,
            'municipality_fixed_charge' => 50,
            'municipality_extra_per_km_charge' => 15,
            'municipality_center_lat' => 22.686,
            'municipality_center_lng' => 90.644,
            'municipality_radius_km' => 1.66,
            'municipality_polygon' => json_encode([
                ['lat' => 22.7044, 'lng' => 90.6179],
                ['lat' => 22.7049, 'lng' => 90.6227],
                ['lat' => 22.6996, 'lng' => 90.6274],
                ['lat' => 22.7016, 'lng' => 90.6373],
                ['lat' => 22.6993, 'lng' => 90.6448],
                ['lat' => 22.6990, 'lng' => 90.6511],
                ['lat' => 22.7031, 'lng' => 90.6525],
                ['lat' => 22.7050, 'lng' => 90.6558],
                ['lat' => 22.6987, 'lng' => 90.6579],
                ['lat' => 22.6961, 'lng' => 90.6644],
                ['lat' => 22.6901, 'lng' => 90.6617],
                ['lat' => 22.6835, 'lng' => 90.6591],
                ['lat' => 22.6755, 'lng' => 90.6642],
                ['lat' => 22.6603, 'lng' => 90.6665],
                ['lat' => 22.6487, 'lng' => 90.6677],
                ['lat' => 22.6449, 'lng' => 90.6639],
                ['lat' => 22.6465, 'lng' => 90.6571],
                ['lat' => 22.6552, 'lng' => 90.6534],
                ['lat' => 22.6645, 'lng' => 90.6500],
                ['lat' => 22.6739, 'lng' => 90.6460],
                ['lat' => 22.6746, 'lng' => 90.6389],
                ['lat' => 22.6791, 'lng' => 90.6365],
                ['lat' => 22.6812, 'lng' => 90.6291],
                ['lat' => 22.6852, 'lng' => 90.6250],
                ['lat' => 22.6880, 'lng' => 90.6172],
            ]),
            'note' => 'Bhola Sadar Pourashava rule enabled. Default boundary polygon is traced from the CityPopulation map screenshot for Bhola Municipality. Replace with official GIS polygon when available.',
        ]);
    }

    public function down(): void
    {
        Schema::table('food_delivery_settings', function (Blueprint $table): void {
            $columns = [
                'municipality_polygon',
                'municipality_radius_km',
                'municipality_center_lng',
                'municipality_center_lat',
                'municipality_extra_per_km_charge',
                'municipality_fixed_charge',
                'municipality_rule_enabled',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('food_delivery_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
