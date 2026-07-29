<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('provider')->default('mram');
            $table->string('api_key')->nullable();
            $table->string('sender_id')->nullable();
            $table->string('label')->default('transactional');
            $table->string('message_type')->default('unicode');
            $table->string('api_url')->default('https://sms.mram.com.bd/smsapi');
            $table->timestamp('last_tested_at')->nullable();
            $table->text('last_test_result')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_settings');
    }
};
