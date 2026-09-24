<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_social_settings') || ! Schema::hasColumn('ai_social_settings', 'openai_api_key')) {
            return;
        }

        DB::statement('ALTER TABLE ai_social_settings MODIFY openai_api_key TEXT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_social_settings') || ! Schema::hasColumn('ai_social_settings', 'openai_api_key')) {
            return;
        }

        DB::statement('ALTER TABLE ai_social_settings MODIFY openai_api_key VARCHAR(255) NULL');
    }
};
