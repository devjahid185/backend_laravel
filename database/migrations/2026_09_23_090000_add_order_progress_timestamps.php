<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['food_orders', 'medicine_orders'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (! Schema::hasColumn($tableName, 'preparing_at')) {
                    $table->timestamp('preparing_at')->nullable()->after('accepted_at');
                }
                if (! Schema::hasColumn($tableName, 'on_the_way_at')) {
                    $table->timestamp('on_the_way_at')->nullable()->after('picked_up_at');
                }
                if (! Schema::hasColumn($tableName, 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable()->after('delivered_at');
                }
                if (! Schema::hasColumn($tableName, 'rejected_at')) {
                    $table->timestamp('rejected_at')->nullable()->after('cancelled_at');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['food_orders', 'medicine_orders'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                foreach (['rejected_at', 'cancelled_at', 'on_the_way_at', 'preparing_at'] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
