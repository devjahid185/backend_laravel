<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table): void {
            if (! Schema::hasColumn('restaurants', 'accepts_food_orders')) {
                $table->boolean('accepts_food_orders')->default(true)->after('delivery_available');
            }
            if (! Schema::hasColumn('restaurants', 'average_prep_minutes')) {
                $table->unsignedInteger('average_prep_minutes')->default(30)->after('opening_hours');
            }
            if (! Schema::hasColumn('restaurants', 'service_radius_km')) {
                $table->decimal('service_radius_km', 8, 2)->nullable()->after('lng');
            }
            if (! Schema::hasColumn('restaurants', 'approval_note')) {
                $table->text('approval_note')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table): void {
            foreach (['accepts_food_orders', 'average_prep_minutes', 'service_radius_km', 'approval_note'] as $column) {
                if (Schema::hasColumn('restaurants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
