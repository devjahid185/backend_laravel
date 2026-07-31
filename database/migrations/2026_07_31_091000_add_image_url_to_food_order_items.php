<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_order_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('food_order_items', 'image_url')) {
                $table->string('image_url', 500)->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('food_order_items', function (Blueprint $table): void {
            if (Schema::hasColumn('food_order_items', 'image_url')) {
                $table->dropColumn('image_url');
            }
        });
    }
};
