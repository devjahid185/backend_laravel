<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_reviews', function (Blueprint $table): void {
            if (! Schema::hasColumn('food_reviews', 'owner_reply')) {
                $table->text('owner_reply')->nullable()->after('comment');
            }
            if (! Schema::hasColumn('food_reviews', 'owner_reply_user_id')) {
                $table->foreignId('owner_reply_user_id')->nullable()->after('owner_reply')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('food_reviews', 'owner_replied_at')) {
                $table->timestamp('owner_replied_at')->nullable()->after('owner_reply_user_id');
            }
            if (! Schema::hasColumn('food_reviews', 'status')) {
                $table->string('status', 30)->default('active')->after('is_verified_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('food_reviews', function (Blueprint $table): void {
            if (Schema::hasColumn('food_reviews', 'owner_reply_user_id')) {
                $table->dropConstrainedForeignId('owner_reply_user_id');
            }
            foreach (['owner_reply', 'owner_replied_at', 'status'] as $column) {
                if (Schema::hasColumn('food_reviews', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
