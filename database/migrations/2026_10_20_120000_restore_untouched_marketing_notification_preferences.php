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
            $table->boolean('marketing_enabled')->default(false)->change();
        });

        // Restore only values changed by the previous default migration.
        // Explicit customer opt-ins and opt-outs have a newer updated_at.
        DB::table('notification_preferences')
            ->where('marketing_enabled', true)
            ->whereColumn('created_at', 'updated_at')
            ->update([
                'marketing_enabled' => false,
                'updated_at' => DB::raw('updated_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('notification_preferences', function (Blueprint $table): void {
            $table->boolean('marketing_enabled')->default(true)->change();
        });
    }
};
