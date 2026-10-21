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
            ['slug' => 'support-tickets'],
            [
                'name' => 'Support Tickets',
                'group_name' => 'Moderation',
                'route' => '/admin/support-tickets',
                'is_active' => true,
                'sort_order' => 46,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('admin_modules')) {
            DB::table('admin_modules')->where('slug', 'support-tickets')->delete();
        }
    }
};
