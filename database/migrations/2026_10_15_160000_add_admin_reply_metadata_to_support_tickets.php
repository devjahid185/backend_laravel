<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_order_support_tickets', function (Blueprint $table): void {
            $table->foreignId('admin_replied_by_id')->nullable()->after('admin_reply')->constrained('admins')->nullOnDelete();
            $table->timestamp('admin_replied_at')->nullable()->after('admin_replied_by_id');
        });

        Schema::table('rider_support_tickets', function (Blueprint $table): void {
            $table->foreignId('admin_replied_by_id')->nullable()->after('admin_reply')->constrained('admins')->nullOnDelete();
            $table->timestamp('admin_replied_at')->nullable()->after('admin_replied_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('food_order_support_tickets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('admin_replied_by_id');
            $table->dropColumn('admin_replied_at');
        });

        Schema::table('rider_support_tickets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('admin_replied_by_id');
            $table->dropColumn('admin_replied_at');
        });
    }
};
