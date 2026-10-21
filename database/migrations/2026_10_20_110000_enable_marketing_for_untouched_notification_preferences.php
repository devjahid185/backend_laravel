<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->boolean('marketing_enabled')->default(true)->change();
        });

        // Old rows received `false` automatically. Enable only preferences the
        // customer has never changed, preserving every explicit opt-out.
        DB::table('notification_preferences')
            ->where('marketing_enabled', false)
            ->whereColumn('created_at', 'updated_at')
            ->update([
                'marketing_enabled' => true,
                'updated_at' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->boolean('marketing_enabled')->default(false)->change();
        });
    }
};
