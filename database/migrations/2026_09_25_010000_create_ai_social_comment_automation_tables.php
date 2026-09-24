<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_social_settings')) {
            Schema::table('ai_social_settings', function (Blueprint $table): void {
                if (! Schema::hasColumn('ai_social_settings', 'facebook_webhook_verify_token')) {
                    $table->string('facebook_webhook_verify_token', 120)
                        ->nullable()
                        ->after('facebook_api_version');
                }
                if (! Schema::hasColumn('ai_social_settings', 'comment_automation_enabled')) {
                    $table->boolean('comment_automation_enabled')->default(false)->after('facebook_webhook_verify_token');
                }
                if (! Schema::hasColumn('ai_social_settings', 'comment_require_review')) {
                    $table->boolean('comment_require_review')->default(true)->after('comment_automation_enabled');
                }
                if (! Schema::hasColumn('ai_social_settings', 'auto_public_reply_enabled')) {
                    $table->boolean('auto_public_reply_enabled')->default(false)->after('comment_require_review');
                }
                if (! Schema::hasColumn('ai_social_settings', 'auto_private_reply_enabled')) {
                    $table->boolean('auto_private_reply_enabled')->default(false)->after('auto_public_reply_enabled');
                }
                if (! Schema::hasColumn('ai_social_settings', 'ai_comment_reply_enabled')) {
                    $table->boolean('ai_comment_reply_enabled')->default(false)->after('auto_private_reply_enabled');
                }
            });

            $settings = \App\Models\AiSocialSetting::current();
            if (blank($settings->facebook_webhook_verify_token)) {
                $settings->forceFill([
                    'facebook_webhook_verify_token' => 'bv-webhook-'.Str::random(32),
                ])->save();
            }
        }

        Schema::create('ai_social_comment_rules', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_active')->default(true)->index();
            $table->string('keyword', 120)->index();
            $table->text('public_reply')->nullable();
            $table->text('private_reply')->nullable();
            $table->boolean('ai_enabled')->default(false);
            $table->boolean('auto_public_reply')->default(true);
            $table->boolean('auto_private_reply')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ai_social_facebook_comments', function (Blueprint $table): void {
            $table->id();
            $table->string('page_id', 100)->nullable()->index();
            $table->string('post_id', 120)->nullable()->index();
            $table->string('comment_id', 160)->unique();
            $table->string('parent_id', 160)->nullable()->index();
            $table->string('sender_id', 160)->nullable()->index();
            $table->string('sender_name')->nullable();
            $table->text('message')->nullable();
            $table->string('matched_keyword', 120)->nullable()->index();
            $table->foreignId('ai_social_comment_rule_id')->nullable()->constrained('ai_social_comment_rules')->nullOnDelete();
            $table->string('status', 40)->default('received')->index();
            $table->text('public_reply_text')->nullable();
            $table->text('private_reply_text')->nullable();
            $table->timestamp('public_replied_at')->nullable();
            $table->timestamp('private_replied_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_social_facebook_comments');
        Schema::dropIfExists('ai_social_comment_rules');

        if (Schema::hasTable('ai_social_settings')) {
            Schema::table('ai_social_settings', function (Blueprint $table): void {
                foreach ([
                    'facebook_webhook_verify_token',
                    'comment_automation_enabled',
                    'comment_require_review',
                    'auto_public_reply_enabled',
                    'auto_private_reply_enabled',
                    'ai_comment_reply_enabled',
                ] as $column) {
                    if (Schema::hasColumn('ai_social_settings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
