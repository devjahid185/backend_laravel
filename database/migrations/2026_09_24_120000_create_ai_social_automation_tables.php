<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_social_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('approval_required')->default(true);
            $table->text('openai_api_key')->nullable();
            $table->string('openai_text_model')->default('gpt-4.1-mini');
            $table->string('openai_image_model')->default('gpt-image-1');
            $table->string('facebook_page_id')->nullable();
            $table->text('facebook_page_access_token')->nullable();
            $table->string('facebook_api_version')->default('v21.0');
            $table->string('default_language', 24)->default('bn');
            $table->string('default_tone', 80)->default('friendly-local');
            $table->text('brand_voice')->nullable();
            $table->string('schedule_timezone', 64)->default('Asia/Dhaka');
            $table->unsignedTinyInteger('daily_post_limit')->default(3);
            $table->timestamp('last_openai_checked_at')->nullable();
            $table->text('last_openai_check_result')->nullable();
            $table->timestamp('last_facebook_checked_at')->nullable();
            $table->text('last_facebook_check_result')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_social_posts', function (Blueprint $table): void {
            $table->id();
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->json('source_snapshot')->nullable();
            $table->string('topic')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->string('platform', 32)->default('facebook');
            $table->text('caption')->nullable();
            $table->json('hashtags')->nullable();
            $table->text('image_prompt')->nullable();
            $table->string('image_url')->nullable();
            $table->json('ai_response')->nullable();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('platform_post_id')->nullable();
            $table->text('failure_message')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_social_publish_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ai_social_post_id')->constrained('ai_social_posts')->cascadeOnDelete();
            $table->string('action', 40);
            $table->string('status', 32);
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('admin_modules')) {
            DB::table('admin_modules')->updateOrInsert(
                ['slug' => 'ai-social'],
                [
                    'name' => 'AI Social Automation',
                    'group_name' => 'Engagement',
                    'route' => '/admin/ai-social',
                    'sort_order' => 7,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_social_publish_logs');
        Schema::dropIfExists('ai_social_posts');
        Schema::dropIfExists('ai_social_settings');
    }
};
