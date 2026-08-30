<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('medicine_orders', 'manual_transaction_id')) {
                $table->string('manual_transaction_id', 120)->nullable()->after('payment_status');
            }
            if (! Schema::hasColumn('medicine_orders', 'payment_proof_photo')) {
                $table->string('payment_proof_photo')->nullable()->after('manual_transaction_id');
            }
            if (! Schema::hasColumn('medicine_orders', 'delivery_distance_km')) {
                $table->decimal('delivery_distance_km', 8, 2)->nullable()->after('delivery_fee');
            }
            if (! Schema::hasColumn('medicine_orders', 'delivery_charge_mode')) {
                $table->string('delivery_charge_mode')->nullable()->after('delivery_distance_km');
            }
        });
    }

    public function down(): void
    {
        Schema::table('medicine_orders', function (Blueprint $table): void {
            foreach ([
                'delivery_charge_mode',
                'delivery_distance_km',
                'payment_proof_photo',
                'manual_transaction_id',
            ] as $column) {
                if (Schema::hasColumn('medicine_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
