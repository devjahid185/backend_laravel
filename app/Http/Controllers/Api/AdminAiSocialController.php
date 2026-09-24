<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiSocialPost;
use App\Models\AiSocialSetting;
use App\Services\AiSocialContentService;
use App\Services\FacebookPagePublisherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AdminAiSocialController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'settings' => $this->serializeSettings(AiSocialSetting::current()),
            'posts' => AiSocialPost::query()
                ->latest('id')
                ->limit(50)
                ->get(),
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'is_enabled' => ['required', 'boolean'],
            'approval_required' => ['required', 'boolean'],
            'openai_api_key' => ['nullable', 'string', 'max:500'],
            'openai_text_model' => ['required', 'string', 'max:80'],
            'openai_image_model' => ['required', 'string', 'max:80'],
            'facebook_page_id' => ['nullable', 'string', 'max:100'],
            'facebook_page_access_token' => ['nullable', 'string', 'max:1000'],
            'facebook_api_version' => ['required', 'string', 'max:20'],
            'default_language' => ['required', 'string', 'max:24'],
            'default_tone' => ['required', 'string', 'max:80'],
            'brand_voice' => ['nullable', 'string', 'max:2000'],
            'schedule_timezone' => ['required', 'string', 'max:64'],
            'daily_post_limit' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        if (blank($data['openai_api_key'] ?? null)) {
            unset($data['openai_api_key']);
        }
        if (blank($data['facebook_page_access_token'] ?? null)) {
            unset($data['facebook_page_access_token']);
        }

        $settings = AiSocialSetting::current();
        $settings->fill($data)->save();

        return response()->json([
            'message' => 'AI social automation settings saved.',
            'settings' => $this->serializeSettings($settings->fresh()),
        ]);
    }

    public function testOpenAi(): JsonResponse
    {
        $settings = AiSocialSetting::current();
        if (! $settings->hasOpenaiKey()) {
            return response()->json(['message' => 'OpenAI API key is missing.'], 422);
        }

        try {
            $response = Http::withToken($settings->openai_api_key_plain)
                ->timeout(30)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => $settings->openai_text_model ?: 'gpt-4.1-mini',
                    'input' => 'Reply with exactly: Bholavashi AI ready',
                ]);
            $message = $response->successful()
                ? 'OpenAI connected successfully.'
                : ($response->json('error.message') ?: $response->body());
            $settings->forceFill([
                'last_openai_checked_at' => now(),
                'last_openai_check_result' => $message,
            ])->save();

            return response()->json([
                'message' => $message,
                'settings' => $this->serializeSettings($settings->fresh()),
            ], $response->successful() ? 200 : 422);
        } catch (\Throwable $e) {
            $settings->forceFill([
                'last_openai_checked_at' => now(),
                'last_openai_check_result' => $e->getMessage(),
            ])->save();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function testFacebook(FacebookPagePublisherService $facebook): JsonResponse
    {
        $settings = AiSocialSetting::current();
        try {
            $page = $facebook->test($settings);
            $settings->forceFill([
                'last_facebook_checked_at' => now(),
                'last_facebook_check_result' => 'Connected to '.($page['name'] ?? $page['id'] ?? 'Facebook Page'),
            ])->save();
            return response()->json([
                'message' => 'Facebook Page connected successfully.',
                'page' => $page,
                'settings' => $this->serializeSettings($settings->fresh()),
            ]);
        } catch (\Throwable $e) {
            $settings->forceFill([
                'last_facebook_checked_at' => now(),
                'last_facebook_check_result' => $e->getMessage(),
            ])->save();
            return response()->json(['message' => $e->getMessage(), 'settings' => $this->serializeSettings($settings->fresh())], 422);
        }
    }

    public function sources(Request $request, AiSocialContentService $content): JsonResponse
    {
        $type = $request->query('type', 'all');
        return response()->json(['sources' => $content->sources((string) $type)]);
    }

    public function generate(Request $request, AiSocialContentService $content): JsonResponse
    {
        $data = $request->validate([
            'source_type' => ['required', 'string', 'max:40'],
            'source_id' => ['nullable', 'integer'],
            'tone' => ['nullable', 'string', 'max:80'],
            'scheduled_at' => ['nullable', 'date'],
            'generate_image' => ['boolean'],
        ]);

        $settings = AiSocialSetting::current();
        $snapshot = $content->snapshot($data['source_type'], $data['source_id'] ?? null);
        $draft = $content->generateDraft($settings, $snapshot, $data['tone'] ?? $settings->default_tone);

        $post = DB::transaction(function () use ($request, $data, $snapshot, $draft): AiSocialPost {
            return AiSocialPost::query()->create([
                'source_type' => $data['source_type'],
                'source_id' => $data['source_id'] ?? null,
                'source_snapshot' => $snapshot,
                'topic' => $draft['topic'] ?? ($snapshot['title'] ?? null),
                'status' => 'generated',
                'caption' => $draft['caption'],
                'hashtags' => $draft['hashtags'] ?? [],
                'image_prompt' => $draft['image_prompt'],
                'ai_response' => $draft['raw'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'created_by' => $request->user()?->id,
            ]);
        });

        if (($data['generate_image'] ?? false) === true) {
            $imageUrl = $content->generateImage($settings, $post);
            if ($imageUrl) {
                $post->update(['image_url' => $imageUrl]);
            }
        }

        return response()->json([
            'message' => 'AI social post generated.',
            'post' => $post->fresh(),
        ], 201);
    }

    public function updatePost(Request $request, AiSocialPost $post): JsonResponse
    {
        $data = $request->validate([
            'caption' => ['nullable', 'string', 'max:5000'],
            'hashtags' => ['nullable', 'array'],
            'hashtags.*' => ['string', 'max:80'],
            'image_prompt' => ['nullable', 'string', 'max:2000'],
            'image_url' => ['nullable', 'string', 'max:1000'],
            'scheduled_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:draft,generated,approved,scheduled,rejected'],
        ]);

        $post->update($data);

        return response()->json(['message' => 'Post updated.', 'post' => $post->fresh()]);
    }

    public function generateImage(AiSocialPost $post, AiSocialContentService $content): JsonResponse
    {
        $imageUrl = $content->generateImage(AiSocialSetting::current(), $post);
        if (! $imageUrl) {
            return response()->json(['message' => 'Image generation failed or OpenAI key is missing.'], 422);
        }
        $post->update(['image_url' => $imageUrl]);
        return response()->json(['message' => 'Image generated.', 'post' => $post->fresh()]);
    }

    public function approve(Request $request, AiSocialPost $post): JsonResponse
    {
        $status = $post->scheduled_at && $post->scheduled_at->isFuture() ? 'scheduled' : 'approved';
        $post->update([
            'status' => $status,
            'approved_at' => now(),
            'approved_by' => $request->user()?->id,
        ]);

        return response()->json(['message' => 'Post approved.', 'post' => $post->fresh()]);
    }

    public function publish(AiSocialPost $post, FacebookPagePublisherService $facebook): JsonResponse
    {
        return response()->json([
            'message' => 'Facebook publish completed.',
            'post' => $this->publishPost($post, $facebook, true),
        ]);
    }

    public function publishDue(FacebookPagePublisherService $facebook): JsonResponse
    {
        $settings = AiSocialSetting::current();
        $count = 0;
        AiSocialPost::query()
            ->whereIn('status', ['approved', 'scheduled'])
            ->where(function ($query): void {
                $query->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now());
            })
            ->limit($settings->daily_post_limit ?: 3)
            ->get()
            ->each(function (AiSocialPost $post) use ($facebook, &$count): void {
                $this->publishPost($post, $facebook, false);
                $count++;
            });

        return response()->json(['message' => "Published {$count} due post(s)."]);
    }

    private function publishPost(AiSocialPost $post, FacebookPagePublisherService $facebook, bool $now): AiSocialPost
    {
        $post->update(['status' => 'publishing', 'failure_message' => null]);
        try {
            $result = $facebook->publish(AiSocialSetting::current(), $post, $now);
            $postId = $result['post_id'] ?? $result['id'] ?? null;
            $post->update([
                'status' => 'published',
                'published_at' => now(),
                'platform_post_id' => $postId,
            ]);
            $post->logs()->create(['action' => 'publish', 'status' => 'success', 'payload' => $result]);
        } catch (\Throwable $e) {
            $post->update(['status' => 'failed', 'failure_message' => $e->getMessage()]);
            $post->logs()->create(['action' => 'publish', 'status' => 'failed', 'message' => $e->getMessage()]);
        }

        return $post->fresh();
    }

    private function serializeSettings(AiSocialSetting $settings): array
    {
        return [
            'is_enabled' => (bool) $settings->is_enabled,
            'approval_required' => (bool) $settings->approval_required,
            'has_openai_api_key' => $settings->hasOpenaiKey(),
            'openai_api_key_masked' => $settings->maskedOpenaiKey(),
            'openai_text_model' => $settings->openai_text_model,
            'openai_image_model' => $settings->openai_image_model,
            'facebook_page_id' => $settings->facebook_page_id,
            'has_facebook_page_access_token' => filled($settings->facebook_page_access_token_plain),
            'facebook_page_access_token_masked' => $settings->maskedFacebookToken(),
            'facebook_api_version' => $settings->facebook_api_version,
            'default_language' => $settings->default_language,
            'default_tone' => $settings->default_tone,
            'brand_voice' => $settings->brand_voice,
            'schedule_timezone' => $settings->schedule_timezone,
            'daily_post_limit' => (int) $settings->daily_post_limit,
            'last_openai_checked_at' => $settings->last_openai_checked_at,
            'last_openai_check_result' => $settings->last_openai_check_result,
            'last_facebook_checked_at' => $settings->last_facebook_checked_at,
            'last_facebook_check_result' => $settings->last_facebook_check_result,
        ];
    }
}
