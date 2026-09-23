<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('phone', 40)->nullable();
            $table->string('purpose', 80)->nullable();
            $table->string('provider', 80)->nullable();
            $table->string('sender_id', 120)->nullable();
            $table->string('api_url')->nullable();
            $table->string('status', 30)->default('pending');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('message')->nullable();
            $table->text('gateway_response')->nullable();
            $table->text('error_message')->nullable();
            $table->json('request_payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['purpose', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
