<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_delivery_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('food_delivery_settings', 'messenger_enabled')) {
                $table->boolean('messenger_enabled')->default(false)->after('checkout_image_notice_text');
            }
            if (! Schema::hasColumn('food_delivery_settings', 'messenger_url')) {
                $table->string('messenger_url', 500)->nullable()->after('messenger_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('food_delivery_settings', function (Blueprint $table): void {
            foreach (['messenger_url', 'messenger_enabled'] as $column) {
                if (Schema::hasColumn('food_delivery_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
