<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rider_locations', function (Blueprint $table): void {
            if (! Schema::hasColumn('rider_locations', 'medicine_order_id')) {
                $table->foreignId('medicine_order_id')->nullable()->after('food_order_id')->constrained('medicine_orders')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('rider_locations', function (Blueprint $table): void {
            if (Schema::hasColumn('rider_locations', 'medicine_order_id')) {
                $table->dropConstrainedForeignId('medicine_order_id');
            }
        });
    }
};
