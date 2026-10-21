<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_coupons', function (Blueprint $table): void {
            if (! Schema::hasColumn('food_coupons', 'auto_apply')) {
                $table->boolean('auto_apply')->default(false)->after('is_active')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('food_coupons', function (Blueprint $table): void {
            if (Schema::hasColumn('food_coupons', 'auto_apply')) {
                $table->dropColumn('auto_apply');
            }
        });
    }
};
