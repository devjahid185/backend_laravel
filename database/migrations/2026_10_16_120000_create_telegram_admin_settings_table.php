<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_admin_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->text('bot_token')->nullable();
            $table->json('chat_ids')->nullable();
            $table->unsignedBigInteger('message_thread_id')->nullable();
            $table->json('event_types')->nullable();
            $table->boolean('include_details')->default(true);
            $table->string('portal_url')->default('https://portal.bholavashi.site');
            $table->timestamp('last_tested_at')->nullable();
            $table->text('last_test_result')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_admin_settings');
    }
};
