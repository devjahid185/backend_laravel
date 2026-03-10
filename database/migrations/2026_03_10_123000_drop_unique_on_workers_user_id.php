<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $fks = DB::select(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'workers'
               AND COLUMN_NAME = 'user_id'
               AND REFERENCED_TABLE_NAME IS NOT NULL"
        );
        foreach ($fks as $fk) {
            DB::statement("ALTER TABLE `workers` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        $uniqueExists = DB::select(
            "SELECT 1
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'workers'
               AND INDEX_NAME = 'workers_user_id_unique'
             LIMIT 1"
        );
        if (! empty($uniqueExists)) {
            DB::statement("ALTER TABLE `workers` DROP INDEX `workers_user_id_unique`");
        }

        $indexExists = DB::select(
            "SELECT 1
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'workers'
               AND INDEX_NAME = 'workers_user_id_index'
             LIMIT 1"
        );
        if (empty($indexExists)) {
            DB::statement("ALTER TABLE `workers` ADD INDEX `workers_user_id_index` (`user_id`)");
        }

        Schema::table('workers', function (Blueprint $table) {
            $table->foreign('user_id', 'workers_user_id_foreign')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        $fks = DB::select(
            "SELECT CONSTRAINT_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'workers'
               AND COLUMN_NAME = 'user_id'
               AND REFERENCED_TABLE_NAME IS NOT NULL"
        );
        foreach ($fks as $fk) {
            DB::statement("ALTER TABLE `workers` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }

        $indexExists = DB::select(
            "SELECT 1
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'workers'
               AND INDEX_NAME = 'workers_user_id_index'
             LIMIT 1"
        );
        if (! empty($indexExists)) {
            DB::statement("ALTER TABLE `workers` DROP INDEX `workers_user_id_index`");
        }

        $uniqueExists = DB::select(
            "SELECT 1
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'workers'
               AND INDEX_NAME = 'workers_user_id_unique'
             LIMIT 1"
        );
        if (empty($uniqueExists)) {
            DB::statement("ALTER TABLE `workers` ADD UNIQUE `workers_user_id_unique` (`user_id`)");
        }

        Schema::table('workers', function (Blueprint $table) {
            $table->foreign('user_id', 'workers_user_id_foreign')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};
