<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_notification_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('use_ai_copy')->default(true);
            $table->string('timezone', 64)->default('Asia/Dhaka');
            $table->time('quiet_hours_start')->default('21:00:00');
            $table->time('quiet_hours_end')->default('10:45:00');
            $table->unsignedTinyInteger('max_promotional_per_day')->default(1);
            $table->unsignedTinyInteger('max_promotional_per_week')->default(4);
            $table->unsignedSmallInteger('minimum_gap_hours')->default(20);
            $table->unsignedSmallInteger('batch_limit')->default(50);
            $table->json('campaigns')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->text('last_run_summary')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_notification_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('app_notification_id')->nullable()->constrained('app_notifications')->nullOnDelete();
            $table->string('campaign', 50)->index();
            $table->string('status', 30)->default('queued')->index();
            $table->decimal('score', 5, 2)->default(0);
            $table->string('title', 200)->nullable();
            $table->text('message')->nullable();
            $table->string('deep_link', 500)->nullable();
            $table->json('context')->nullable();
            $table->string('copy_source', 20)->nullable();
            $table->string('dedupe_key', 180)->unique();
            $table->timestamp('scheduled_for')->nullable()->index();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamp('opened_at')->nullable()->index();
            $table->timestamp('converted_at')->nullable()->index();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'sent_at']);
            $table->index(['campaign', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_notification_decisions');
        Schema::dropIfExists('ai_notification_settings');
    }
};
