<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_modules')) {
            return;
        }

        DB::table('admin_modules')->updateOrInsert(
            ['slug' => 'analytics'],
            [
                'name' => 'Analytics',
                'group_name' => 'Core',
                'route' => '/admin/analytics',
                'is_active' => true,
                'sort_order' => 2,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('admin_modules')) {
            DB::table('admin_modules')->where('slug', 'analytics')->delete();
        }
    }
};
