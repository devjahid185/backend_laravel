<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('home_service_shortcuts')) {
            return;
        }

        DB::table('home_service_shortcuts')
            ->where('endpoint', '/medicine')
            ->update([
                'endpoint' => '/medicine/home',
                'sort_order' => 20,
                'updated_at' => now(),
            ]);

        DB::table('home_service_shortcuts')
            ->where('endpoint', '/items')
            ->update([
                'sort_order' => 30,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('home_service_shortcuts')) {
            return;
        }

        DB::table('home_service_shortcuts')
            ->where('endpoint', '/medicine/home')
            ->update([
                'endpoint' => '/medicine',
                'updated_at' => now(),
            ]);
    }
};
