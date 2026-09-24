<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiSocialFacebookComment;
use App\Models\AiSocialSetting;
use App\Services\FacebookCommentAutomationService;
use Illuminate\Http\Request;

class FacebookWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $settings = AiSocialSetting::current();
        if (
            $request->query('hub_mode') === 'subscribe'
            || $request->query('hub.mode') === 'subscribe'
        ) {
            $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
            if (filled($settings->facebook_webhook_verify_token) && hash_equals($settings->facebook_webhook_verify_token, (string) $token)) {
                return response($request->query('hub_challenge', $request->query('hub.challenge')), 200);
            }
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request, FacebookCommentAutomationService $automation)
    {
        foreach (($request->input('entry') ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                if (($change['field'] ?? '') !== 'feed' || ($value['item'] ?? '') !== 'comment') {
                    continue;
                }

                $commentId = $value['comment_id'] ?? $value['comment_id_str'] ?? null;
                if (blank($commentId) || ($value['verb'] ?? 'add') === 'remove') {
                    continue;
                }

                $comment = AiSocialFacebookComment::query()->updateOrCreate(
                    ['comment_id' => (string) $commentId],
                    [
                        'page_id' => $entry['id'] ?? AiSocialSetting::current()->facebook_page_id,
                        'post_id' => $value['post_id'] ?? null,
                        'parent_id' => $value['parent_id'] ?? null,
                        'sender_id' => $value['sender_id'] ?? $value['from']['id'] ?? null,
                        'sender_name' => $value['sender_name'] ?? $value['from']['name'] ?? null,
                        'message' => $value['message'] ?? null,
                        'payload' => $value,
                    ],
                );

                $automation->handle($comment);
            }
        }

        return response('EVENT_RECEIVED', 200);
    }
}
