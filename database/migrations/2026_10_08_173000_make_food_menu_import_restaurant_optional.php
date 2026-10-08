<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('food_menu_imports')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE food_menu_imports DROP FOREIGN KEY food_menu_imports_restaurant_id_foreign');
        } catch (\Throwable) {
            // The constraint may already be absent on older installs.
        }

        DB::statement('ALTER TABLE food_menu_imports MODIFY restaurant_id BIGINT UNSIGNED NULL');

        try {
            DB::statement('ALTER TABLE food_menu_imports ADD CONSTRAINT food_menu_imports_restaurant_id_foreign FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE SET NULL');
        } catch (\Throwable) {
            // Keep the column nullable even if the FK already exists with a different name.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('food_menu_imports')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE food_menu_imports DROP FOREIGN KEY food_menu_imports_restaurant_id_foreign');
        } catch (\Throwable) {
        }

        DB::statement('ALTER TABLE food_menu_imports MODIFY restaurant_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE food_menu_imports ADD CONSTRAINT food_menu_imports_restaurant_id_foreign FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE');
    }
};
