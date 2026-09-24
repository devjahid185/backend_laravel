<?php

namespace App\Services;

use App\Models\AiSocialPost;
use App\Models\AiSocialSetting;
use Illuminate\Support\Facades\Http;

class FacebookPagePublisherService
{
    public function test(AiSocialSetting $settings): array
    {
        if (! $settings->hasFacebookCredentials()) {
            throw new \RuntimeException('Facebook Page ID or Page Access Token is missing.');
        }

        $accessToken = $this->pageAccessToken($settings);
        $response = Http::timeout(30)->get($this->graphUrl($settings, $settings->facebook_page_id), [
            'fields' => 'id,name,fan_count',
            'access_token' => $accessToken,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException($response->json('error.message') ?: $response->body());
        }

        return $response->json();
    }

    public function publish(AiSocialSetting $settings, AiSocialPost $post, bool $now = false): array
    {
        if (! $settings->hasFacebookCredentials()) {
            throw new \RuntimeException('Facebook credentials are missing.');
        }

        $payload = [
            'message' => trim($post->caption."\n\n".implode(' ', $post->hashtags ?: [])),
            'access_token' => $this->pageAccessToken($settings),
        ];

        if (! $now && $post->scheduled_at && $post->scheduled_at->isFuture()) {
            $payload['published'] = false;
            $payload['scheduled_publish_time'] = $post->scheduled_at->timestamp;
        }

        $endpoint = filled($post->image_url)
            ? $this->graphUrl($settings, $settings->facebook_page_id.'/photos')
            : $this->graphUrl($settings, $settings->facebook_page_id.'/feed');

        if (filled($post->image_url)) {
            $payload['url'] = $post->image_url;
            if (isset($payload['message'])) {
                $payload['caption'] = $payload['message'];
                unset($payload['message']);
            }
        }

        $response = Http::asForm()->timeout(45)->post($endpoint, $payload);
        if (! $response->successful()) {
            throw new \RuntimeException($this->friendlyError($response->json('error.message') ?: $response->body()));
        }

        return $response->json();
    }

    private function friendlyError(string $message): string
    {
        if (str_contains($message, 'publish_actions')) {
            return 'Facebook publish failed because this token/app is using deprecated publish_actions. Generate a Page/System User token with pages_manage_posts and pages_read_engagement instead.';
        }

        return $message;
    }

    private function pageAccessToken(AiSocialSetting $settings): string
    {
        $token = $settings->facebook_page_access_token_plain;
        $pageId = (string) $settings->facebook_page_id;

        $me = Http::timeout(20)->get($this->graphUrl($settings, 'me'), [
            'fields' => 'id,name',
            'access_token' => $token,
        ]);

        if ($me->successful() && (string) $me->json('id') === $pageId) {
            return $token;
        }

        $accounts = Http::timeout(20)->get($this->graphUrl($settings, 'me/accounts'), [
            'fields' => 'id,name,access_token,tasks',
            'access_token' => $token,
        ]);

        if ($accounts->successful()) {
            foreach (($accounts->json('data') ?? []) as $page) {
                if ((string) ($page['id'] ?? '') === $pageId && filled($page['access_token'] ?? null)) {
                    return (string) $page['access_token'];
                }
            }
        }

        return $token;
    }

    private function graphUrl(AiSocialSetting $settings, string $path): string
    {
        $version = $settings->facebook_api_version ?: 'v21.0';
        return 'https://graph.facebook.com/'.$version.'/'.ltrim($path, '/');
    }
}
