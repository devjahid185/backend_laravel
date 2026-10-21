<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('food_orders', 'owner_alert_seen_at')) {
                $table->timestamp('owner_alert_seen_at')->nullable()->after('estimated_delivery_at')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('food_orders', function (Blueprint $table): void {
            if (Schema::hasColumn('food_orders', 'owner_alert_seen_at')) {
                $table->dropColumn('owner_alert_seen_at');
            }
        });
    }
};
