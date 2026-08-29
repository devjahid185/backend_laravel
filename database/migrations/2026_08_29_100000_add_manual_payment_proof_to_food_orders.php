<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('food_orders', 'manual_transaction_id')) {
                $table->string('manual_transaction_id', 120)->nullable()->after('payment_status');
            }
            if (! Schema::hasColumn('food_orders', 'payment_proof_photo')) {
                $table->string('payment_proof_photo')->nullable()->after('manual_transaction_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('food_orders', function (Blueprint $table): void {
            foreach (['payment_proof_photo', 'manual_transaction_id'] as $column) {
                if (Schema::hasColumn('food_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
