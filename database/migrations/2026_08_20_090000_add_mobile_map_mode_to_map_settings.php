<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('map_settings', function (Blueprint $table): void {
            $table->string('mobile_map_mode')->default('webview')->after('provider');
        });
    }

    public function down(): void
    {
        Schema::table('map_settings', function (Blueprint $table): void {
            $table->dropColumn('mobile_map_mode');
        });
    }
};
