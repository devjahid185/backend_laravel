<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('food_items', 'is_promoted')) {
                $table->boolean('is_promoted')->default(false)->after('is_popular')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('food_items', function (Blueprint $table): void {
            if (Schema::hasColumn('food_items', 'is_promoted')) {
                $table->dropColumn('is_promoted');
            }
        });
    }
};
