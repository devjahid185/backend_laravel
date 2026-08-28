<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_version_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('platform')->default('android')->unique();
            $table->boolean('is_enabled')->default(true);
            $table->string('latest_version')->default('1.0.0');
            $table->unsignedInteger('latest_build')->default(1);
            $table->string('minimum_supported_version')->default('1.0.0');
            $table->unsignedInteger('minimum_supported_build')->default(1);
            $table->enum('update_type', ['none', 'recommended', 'force'])->default('none');
            $table->string('update_title')->default('নতুন আপডেট এসেছে');
            $table->text('update_message')->nullable();
            $table->string('store_url')->nullable();
            $table->string('direct_apk_url')->nullable();
            $table->boolean('maintenance_mode')->default(false);
            $table->string('maintenance_title')->default('সার্ভিস আপডেট চলছে');
            $table->text('maintenance_message')->nullable();
            $table->timestamp('maintenance_until')->nullable();
            $table->json('blocked_versions')->nullable();
            $table->text('changelog')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_version_settings');
    }
};
