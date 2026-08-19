<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('provider')->default('google');
            $table->text('browser_api_key')->nullable();
            $table->boolean('maps_javascript_enabled')->default(true);
            $table->boolean('embed_enabled')->default(true);
            $table->boolean('places_enabled')->default(false);
            $table->boolean('directions_enabled')->default(false);
            $table->unsignedInteger('client_cache_minutes')->default(1440);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_settings');
    }
};
