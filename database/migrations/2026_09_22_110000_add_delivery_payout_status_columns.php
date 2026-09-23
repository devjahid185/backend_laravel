<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rider_wallet_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('rider_wallet_entries', 'payout_status')) {
                $table->string('payout_status', 24)->default('pending')->after('balance_after');
            }
            if (! Schema::hasColumn('rider_wallet_entries', 'payout_reference')) {
                $table->string('payout_reference', 120)->nullable()->after('payout_status');
            }
            if (! Schema::hasColumn('rider_wallet_entries', 'paid_out_at')) {
                $table->timestamp('paid_out_at')->nullable()->after('payout_reference');
            }
        });

        Schema::table('food_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('food_orders', 'restaurant_payout_status')) {
                $table->string('restaurant_payout_status', 24)->default('pending')->after('restaurant_owner_payable');
            }
            if (! Schema::hasColumn('food_orders', 'restaurant_payout_reference')) {
                $table->string('restaurant_payout_reference', 120)->nullable()->after('restaurant_payout_status');
            }
            if (! Schema::hasColumn('food_orders', 'restaurant_paid_out_at')) {
                $table->timestamp('restaurant_paid_out_at')->nullable()->after('restaurant_payout_reference');
            }
        });
    }

    public function down(): void
    {
        Schema::table('food_orders', function (Blueprint $table): void {
            foreach (['restaurant_paid_out_at', 'restaurant_payout_reference', 'restaurant_payout_status'] as $column) {
                if (Schema::hasColumn('food_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('rider_wallet_entries', function (Blueprint $table): void {
            foreach (['paid_out_at', 'payout_reference', 'payout_status'] as $column) {
                if (Schema::hasColumn('rider_wallet_entries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
