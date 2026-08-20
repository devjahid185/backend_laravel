<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table): void {
            if (! Schema::hasColumn('restaurants', 'cod_enabled')) {
                $table->boolean('cod_enabled')->default(true)->after('accepts_food_orders');
            }
            if (! Schema::hasColumn('restaurants', 'manual_bkash_number')) {
                $table->string('manual_bkash_number', 40)->nullable()->after('cod_enabled');
            }
            if (! Schema::hasColumn('restaurants', 'manual_nagad_number')) {
                $table->string('manual_nagad_number', 40)->nullable()->after('manual_bkash_number');
            }
            if (! Schema::hasColumn('restaurants', 'manual_payment_instructions')) {
                $table->text('manual_payment_instructions')->nullable()->after('manual_nagad_number');
            }
        });

        DB::statement("ALTER TABLE food_orders MODIFY payment_method VARCHAR(40) NOT NULL DEFAULT 'cash_on_delivery'");
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table): void {
            foreach (['manual_payment_instructions', 'manual_nagad_number', 'manual_bkash_number', 'cod_enabled'] as $column) {
                if (Schema::hasColumn('restaurants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        DB::statement("ALTER TABLE food_orders MODIFY payment_method ENUM('cash_on_delivery', 'online') NOT NULL DEFAULT 'cash_on_delivery'");
    }
};
