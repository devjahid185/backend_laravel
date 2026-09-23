<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table): void {
            if (! Schema::hasColumn('restaurants', 'is_promoted')) {
                $table->boolean('is_promoted')->default(false)->after('views')->index();
            }
            if (! Schema::hasColumn('restaurants', 'promotion_priority')) {
                $table->unsignedInteger('promotion_priority')->default(0)->after('is_promoted')->index();
            }
            if (! Schema::hasColumn('restaurants', 'promotion_badge')) {
                $table->string('promotion_badge', 80)->nullable()->after('promotion_priority');
            }
            if (! Schema::hasColumn('restaurants', 'promotion_starts_at')) {
                $table->timestamp('promotion_starts_at')->nullable()->after('promotion_badge')->index();
            }
            if (! Schema::hasColumn('restaurants', 'promotion_ends_at')) {
                $table->timestamp('promotion_ends_at')->nullable()->after('promotion_starts_at')->index();
            }
            if (! Schema::hasColumn('restaurants', 'promotion_note')) {
                $table->string('promotion_note', 255)->nullable()->after('promotion_ends_at');
            }
        });

        Schema::table('food_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('food_items', 'promotion_priority')) {
                $table->unsignedInteger('promotion_priority')->default(0)->after('is_promoted')->index();
            }
            if (! Schema::hasColumn('food_items', 'promotion_badge')) {
                $table->string('promotion_badge', 80)->nullable()->after('promotion_priority');
            }
            if (! Schema::hasColumn('food_items', 'promotion_starts_at')) {
                $table->timestamp('promotion_starts_at')->nullable()->after('promotion_badge')->index();
            }
            if (! Schema::hasColumn('food_items', 'promotion_ends_at')) {
                $table->timestamp('promotion_ends_at')->nullable()->after('promotion_starts_at')->index();
            }
            if (! Schema::hasColumn('food_items', 'promotion_note')) {
                $table->string('promotion_note', 255)->nullable()->after('promotion_ends_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('food_items', function (Blueprint $table): void {
            foreach (['promotion_note', 'promotion_ends_at', 'promotion_starts_at', 'promotion_badge', 'promotion_priority'] as $column) {
                if (Schema::hasColumn('food_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('restaurants', function (Blueprint $table): void {
            foreach (['promotion_note', 'promotion_ends_at', 'promotion_starts_at', 'promotion_badge', 'promotion_priority', 'is_promoted'] as $column) {
                if (Schema::hasColumn('restaurants', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
