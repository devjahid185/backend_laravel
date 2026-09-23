<?php

use App\Models\Admin;
use App\Models\AdminModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            if (! Schema::hasColumn('admins', 'permissions')) {
                $table->json('permissions')->nullable()->after('is_super');
            }
            if (! Schema::hasColumn('admins', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('permissions');
            }
        });

        $firstAdmin = Admin::query()->orderBy('id')->first();
        if ($firstAdmin && ! Admin::query()->where('is_super', true)->exists()) {
            $firstAdmin->forceFill(['is_super' => true, 'is_active' => true])->save();
        }

        Admin::query()->whereNull('is_active')->update(['is_active' => true]);

        AdminModule::query()->updateOrCreate(
            ['slug' => 'staff-management'],
            [
                'name' => 'Staff Management',
                'group_name' => 'Core',
                'route' => '/admin/staff-management',
                'sort_order' => 4,
                'is_active' => true,
            ]
        );
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table): void {
            if (Schema::hasColumn('admins', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('admins', 'permissions')) {
                $table->dropColumn('permissions');
            }
        });
    }
};
