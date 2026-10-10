<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_settings', function (Blueprint $table): void {
            $table->boolean('facebook_group_prompt_enabled')->default(false)->after('whatsapp');
            $table->string('facebook_group_url', 500)->nullable()->after('facebook_group_prompt_enabled');
        });

        DB::table('support_settings')
            ->whereNull('facebook_group_url')
            ->update(['facebook_group_url' => 'https://www.facebook.com/share/g/1Eyr3yxGqM/']);
    }

    public function down(): void
    {
        Schema::table('support_settings', function (Blueprint $table): void {
            $table->dropColumn(['facebook_group_prompt_enabled', 'facebook_group_url']);
        });
    }
};
