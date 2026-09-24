<?php

namespace App\Services;

use App\Models\AiSocialCommentRule;
use App\Models\AiSocialFacebookComment;
use App\Models\AiSocialSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FacebookCommentAutomationService
{
    private const RISKY_TERMS = [
        'refund',
        'complaint',
        'scam',
        'fraud',
        'lawsuit',
        'police',
        'ভুয়া',
        'প্রতার',
        'অভিযোগ',
        'রিফান্ড',
        'পুলিশ',
        'মামলা',
    ];

    public function __construct(
        private readonly FacebookPagePublisherService $facebook,
    ) {}

    public function handle(AiSocialFacebookComment $comment): AiSocialFacebookComment
    {
        $settings = AiSocialSetting::current();
        if (! $settings->comment_automation_enabled) {
            $comment->update(['status' => 'received']);
            return $comment->fresh();
        }

        if ($this->isRisky($comment->message)) {
            $comment->update([
                'status' => 'needs_review',
                'last_error' => 'Comment matched safety review terms.',
            ]);
            return $comment->fresh();
        }

        $rule = $this->matchingRule((string) $comment->message);
        if (! $rule) {
            $comment->update(['status' => 'no_rule']);
            return $comment->fresh();
        }

        $replies = $this->replies($settings, $rule, $comment);
        $comment->update([
            'status' => $settings->comment_require_review ? 'needs_review' : 'matched',
            'matched_keyword' => $rule->keyword,
            'ai_social_comment_rule_id' => $rule->id,
            'public_reply_text' => $replies['public_reply'] ?? null,
            'private_reply_text' => $replies['private_reply'] ?? null,
            'last_error' => null,
        ]);

        if ($settings->comment_require_review) {
            return $comment->fresh();
        }

        return $this->sendReplies($comment->fresh(), $settings);
    }

    public function sendReplies(AiSocialFacebookComment $comment, ?AiSocialSetting $settings = null): AiSocialFacebookComment
    {
        $settings ??= AiSocialSetting::current();
        $errors = [];

        if ($settings->auto_public_reply_enabled && blank($comment->public_replied_at) && filled($comment->public_reply_text)) {
            try {
                $this->facebook->replyToComment($settings, $comment->comment_id, $comment->public_reply_text);
                $comment->forceFill(['public_replied_at' => now()])->save();
            } catch (\Throwable $e) {
                $errors[] = 'Public reply: '.$e->getMessage();
            }
        }

        if ($settings->auto_private_reply_enabled && blank($comment->private_replied_at) && filled($comment->private_reply_text)) {
            try {
                $this->facebook->privateReplyToComment($settings, $comment->comment_id, $comment->private_reply_text);
                $comment->forceFill(['private_replied_at' => now()])->save();
            } catch (\Throwable $e) {
                $errors[] = 'Private reply: '.$e->getMessage();
            }
        }

        $comment->update([
            'status' => $errors ? 'reply_failed' : 'replied',
            'last_error' => $errors ? implode("\n", $errors) : null,
        ]);

        return $comment->fresh();
    }

    private function matchingRule(string $message): ?AiSocialCommentRule
    {
        $normalized = Str::lower($message);

        return AiSocialCommentRule::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->first(function (AiSocialCommentRule $rule) use ($normalized): bool {
                return filled($rule->keyword) && str_contains($normalized, Str::lower($rule->keyword));
            });
    }

    private function replies(AiSocialSetting $settings, AiSocialCommentRule $rule, AiSocialFacebookComment $comment): array
    {
        if ($settings->ai_comment_reply_enabled && $rule->ai_enabled && $settings->hasOpenaiKey()) {
            $ai = $this->aiReplies($settings, $rule, $comment);
            if ($ai) {
                return $ai;
            }
        }

        return [
            'public_reply' => $rule->auto_public_reply ? $this->renderTemplate($rule->public_reply, $comment) : null,
            'private_reply' => $rule->auto_private_reply ? $this->renderTemplate($rule->private_reply, $comment) : null,
        ];
    }

    private function aiReplies(AiSocialSetting $settings, AiSocialCommentRule $rule, AiSocialFacebookComment $comment): ?array
    {
        $response = Http::withToken($settings->openai_api_key_plain)
            ->timeout(45)
            ->post('https://api.openai.com/v1/responses', [
                'model' => $settings->openai_text_model ?: 'gpt-4.1-mini',
                'input' => "Create safe Bangla Facebook Page comment automation replies for Bholavashi.\n"
                    ."Do not make fake claims, medical guarantees, discounts, stock promises, or payment promises.\n"
                    ."Return strict JSON: {\"public_reply\":\"...\", \"private_reply\":\"...\"}.\n"
                    ."Public reply should be short and friendly. Private reply can include more detail.\n"
                    ."Keyword: {$rule->keyword}\n"
                    ."User comment: {$comment->message}\n"
                    ."Fallback public template: {$rule->public_reply}\n"
                    ."Fallback private template: {$rule->private_reply}",
                'text' => ['format' => ['type' => 'json_object']],
            ]);

        if (! $response->successful()) {
            return null;
        }

        $decoded = json_decode((string) ($response->json('output_text') ?? ''), true);
        if (! is_array($decoded)) {
            return null;
        }

        return [
            'public_reply' => $rule->auto_public_reply
                ? (trim((string) ($decoded['public_reply'] ?? '')) ?: $this->renderTemplate($rule->public_reply, $comment))
                : null,
            'private_reply' => $rule->auto_private_reply
                ? (trim((string) ($decoded['private_reply'] ?? '')) ?: $this->renderTemplate($rule->private_reply, $comment))
                : null,
        ];
    }

    private function renderTemplate(?string $template, AiSocialFacebookComment $comment): ?string
    {
        if (blank($template)) {
            return null;
        }

        return strtr($template, [
            '{name}' => $comment->sender_name ?: 'ভাই/আপু',
            '{comment}' => $comment->message ?: '',
            '{post_id}' => $comment->post_id ?: '',
        ]);
    }

    private function isRisky(?string $message): bool
    {
        $text = Str::lower((string) $message);
        foreach (self::RISKY_TERMS as $term) {
            if (str_contains($text, Str::lower($term))) {
                return true;
            }
        }

        return false;
    }
}
