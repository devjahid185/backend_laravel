<?php

namespace App\Services;

use App\Models\AiNotificationSetting;
use App\Models\AiSocialSetting;
use Illuminate\Support\Facades\Http;

class AiNotificationContentService
{
    public function generate(AiNotificationSetting $settings, array $decision): array
    {
        $fallback = $this->fallback($decision);
        if (($decision['campaign'] ?? null) === 'free_delivery') {
            return [...$fallback, 'copy_source' => 'template'];
        }

        $aiSettings = AiSocialSetting::current();
        if (! $settings->use_ai_copy || ! $aiSettings->hasOpenaiKey()) {
            return [...$fallback, 'copy_source' => 'template'];
        }

        try {
            $response = Http::withToken($aiSettings->openai_api_key_plain)
                ->timeout(25)
                ->post('https://api.openai.com/v1/responses', [
                    'model' => $aiSettings->openai_text_model ?: 'gpt-4.1-mini',
                    'input' => $this->prompt($decision),
                    'text' => ['format' => ['type' => 'json_object']],
                ]);
            if (! $response->successful()) {
                return [...$fallback, 'copy_source' => 'template'];
            }

            $text = $response->json('output_text');
            if (! $text) {
                $output = collect($response->json('output', []))
                    ->flatMap(fn ($item) => $item['content'] ?? [])
                    ->firstWhere('type', 'output_text');
                $text = is_array($output) ? ($output['text'] ?? null) : null;
            }
            $content = json_decode((string) $text, true);
            $title = trim((string) ($content['title'] ?? ''));
            $message = trim((string) ($content['message'] ?? ''));
            if ($title === '' || $message === '' || mb_strlen($title) > 70 || mb_strlen($message) > 180) {
                return [...$fallback, 'copy_source' => 'template'];
            }

            return ['title' => $title, 'message' => $message, 'copy_source' => 'ai'];
        } catch (\Throwable) {
            return [...$fallback, 'copy_source' => 'template'];
        }
    }

    private function prompt(array $decision): string
    {
        return "Write one short push notification for Bholavashi food delivery in natural conversational Bangla.\n"
            ."Return strict JSON with only title and message. Title maximum 45 characters; message maximum 120 characters.\n"
            ."Use only the verified data below. Never invent an offer, discount, free delivery, delivery time, stock, rating, price or guarantee. Do not say 'Valobashi'. Brand is Bholavashi/ভোলাবাসী. No emoji. Keep the call to action gentle and specific.\n"
            .json_encode(['campaign' => $decision['campaign'], 'data' => $decision['context']], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function fallback(array $decision): array
    {
        if (($decision['campaign'] ?? null) === 'free_delivery') {
            return [
                'title' => 'ভোলা পৌরসভায় ফ্রি ডেলিভারি',
                'message' => 'ভোলা পৌরসভার ভিতরে ডেলিভারি চার্জ ফ্রি। পৌরসভার বাইরে প্রতি অর্ডারের ডেলিভারি চার্জ থেকে ৪০ টাকা ছাড়।',
            ];
        }

        $item = $decision['context']['food_item_name'] ?? null;
        $restaurant = $decision['context']['restaurant_name'] ?? null;
        $title = match ($decision['campaign']) {
            'cart_abandoned', 'checkout_abandoned' => 'অর্ডারটি এখনো বাকি',
            'rejected_recovery' => 'অন্য খাবার দেখে নিন',
            'new_user' => 'সহজেই খাবার অর্ডার করুন',
            'reorder' => 'আবার অর্ডার করবেন?',
            'inactive_user' => 'আজ কী খাবেন?',
            'free_delivery' => 'ডেলিভারি চার্জে বিশেষ সুবিধা',
            'discovery' => 'আজকের খাবার দেখে নিন',
            default => $item ?: 'আপনার জন্য খাবারের পরামর্শ',
        };
        $message = (string) ($decision['context']['fallback_message'] ?? 'ভোলাবাসী অ্যাপে খাবারগুলো দেখে নিন।');
        if ($item && ! str_contains($message, $item)) {
            $message = trim($item.($restaurant ? ' - '.$restaurant : '').'. '.$message);
        }

        return ['title' => $title, 'message' => mb_substr($message, 0, 180)];
    }
}
