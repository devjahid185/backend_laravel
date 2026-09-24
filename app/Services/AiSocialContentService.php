<?php

namespace App\Services;

use App\Models\AiSocialPost;
use App\Models\AiSocialSetting;
use App\Models\FoodCoupon;
use App\Models\FoodItem;
use App\Models\Restaurant;
use App\Support\MediaLookup;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiSocialContentService
{
    public function sources(string $type = 'all', int $limit = 24): array
    {
        $sources = [];
        if ($type === 'all' || $type === 'food_item') {
            $items = FoodItem::query()
                ->with('restaurant:id,name,address,district,upazila,status')
                ->where('is_available', true)
                ->where('status', 'active')
                ->orderByDesc('is_promoted')
                ->orderByDesc('is_popular')
                ->latest('id')
                ->limit($limit)
                ->get();
            $images = MediaLookup::primaryUrlMap('food_item', $items->pluck('id')->all());
            foreach ($items as $item) {
                $sources[] = [
                    'source_type' => 'food_item',
                    'source_id' => $item->id,
                    'title' => $item->name,
                    'subtitle' => $item->restaurant?->name,
                    'price' => $item->discount_price ?: $item->price,
                    'image_url' => $images[$item->id] ?? null,
                    'snapshot' => $this->foodItemSnapshot($item, $images[$item->id] ?? null),
                ];
            }
        }

        if ($type === 'all' || $type === 'restaurant') {
            $restaurants = Restaurant::query()
                ->where('status', 'active')
                ->where('accepts_food_orders', true)
                ->orderByDesc('is_promoted')
                ->latest('id')
                ->limit($limit)
                ->get();
            $images = MediaLookup::primaryUrlMap('restaurant', $restaurants->pluck('id')->all());
            foreach ($restaurants as $restaurant) {
                $sources[] = [
                    'source_type' => 'restaurant',
                    'source_id' => $restaurant->id,
                    'title' => $restaurant->name,
                    'subtitle' => $restaurant->address ?: $this->restaurantAreaLabel($restaurant),
                    'image_url' => $images[$restaurant->id] ?? null,
                    'snapshot' => $this->restaurantSnapshot($restaurant, $images[$restaurant->id] ?? null),
                ];
            }
        }

        if ($type === 'all' || $type === 'coupon') {
            $coupons = FoodCoupon::query()
                ->with('restaurant:id,name,address')
                ->where('is_active', true)
                ->where(function ($query): void {
                    $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->where(function ($query): void {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                })
                ->latest('id')
                ->limit($limit)
                ->get();
            foreach ($coupons as $coupon) {
                $sources[] = [
                    'source_type' => 'coupon',
                    'source_id' => $coupon->id,
                    'title' => $coupon->code,
                    'subtitle' => $coupon->restaurant?->name ?: 'Bholavashi offer',
                    'price' => $coupon->discount_value,
                    'image_url' => null,
                    'snapshot' => $this->couponSnapshot($coupon),
                ];
            }
        }

        if ($type === 'all' || $type === 'app_feature') {
            foreach ($this->featureSources() as $source) {
                $sources[] = $source;
            }
        }

        return array_slice($sources, 0, $limit);
    }

    public function snapshot(string $type, ?int $id): array
    {
        return match ($type) {
            'food_item' => $this->foodItemSnapshot(FoodItem::query()->with('restaurant')->findOrFail($id)),
            'restaurant' => $this->restaurantSnapshot(Restaurant::query()->findOrFail($id)),
            'coupon' => $this->couponSnapshot(FoodCoupon::query()->with('restaurant')->findOrFail($id)),
            'app_feature' => collect($this->featureSources())->firstWhere('source_id', $id)['snapshot'] ?? $this->featureSources()[0]['snapshot'],
            default => ['type' => 'custom', 'title' => 'Bholavashi'],
        };
    }

    public function generateDraft(AiSocialSetting $settings, array $source, string $tone = 'friendly-local'): array
    {
        if (! $settings->hasOpenaiKey()) {
            return $this->fallbackDraft($source, $tone);
        }

        $prompt = $this->prompt($settings, $source, $tone);
        $response = Http::withToken($settings->openai_api_key_plain)
            ->timeout(60)
            ->post('https://api.openai.com/v1/responses', [
                'model' => $settings->openai_text_model ?: 'gpt-4.1-mini',
                'input' => $prompt,
                'text' => ['format' => ['type' => 'json_object']],
            ]);

        if (! $response->successful()) {
            return $this->fallbackDraft($source, $tone, $response->body());
        }

        $text = $response->json('output_text') ?: $this->extractResponseText($response->json());
        $decoded = json_decode((string) $text, true);
        if (! is_array($decoded)) {
            return $this->fallbackDraft($source, $tone, 'AI response was not valid JSON.');
        }

        return [
            'caption' => trim((string) ($decoded['caption'] ?? '')),
            'hashtags' => array_values(array_filter((array) ($decoded['hashtags'] ?? []))),
            'image_prompt' => trim((string) ($decoded['image_prompt'] ?? '')),
            'topic' => trim((string) ($decoded['topic'] ?? ($source['title'] ?? 'Bholavashi'))),
            'raw' => $response->json(),
        ];
    }

    public function generateImage(AiSocialSetting $settings, AiSocialPost $post): ?string
    {
        if (! $settings->hasOpenaiKey() || blank($post->image_prompt)) {
            return null;
        }

        $response = Http::withToken($settings->openai_api_key_plain)
            ->timeout(120)
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => $settings->openai_image_model ?: 'gpt-image-1',
                'prompt' => $post->image_prompt,
                'size' => '1024x1024',
                'quality' => 'medium',
                'output_format' => 'png',
            ]);

        if (! $response->successful()) {
            $post->logs()->create([
                'action' => 'image_generate',
                'status' => 'failed',
                'message' => $response->body(),
            ]);
            return null;
        }

        $b64 = $response->json('data.0.b64_json');
        if (! $b64) {
            return null;
        }

        $path = 'ai-social/'.date('Y/m').'/post-'.$post->id.'-'.Str::random(8).'.png';
        Storage::disk('public')->put($path, base64_decode($b64));

        return Storage::disk('public')->url($path);
    }

    private function prompt(AiSocialSetting $settings, array $source, string $tone): string
    {
        return "You are the marketing automation assistant for Bholavashi, a local service app for Bhola, Bangladesh.\n"
            ."Create a Facebook post from the provided database-safe marketing data only.\n"
            ."Language: Bangla with simple local tone. Tone: {$tone}.\n"
            ."Brand voice: {$settings->brand_voice}\n"
            ."Rules: do not invent fake offers, stock, delivery time, ratings, medical cures, personal data, phone numbers, or guarantees. Avoid political/religious controversy. Keep it accurate and friendly.\n"
            ."Return strict JSON with keys: topic, caption, hashtags (array), image_prompt.\n"
            ."Data:\n".json_encode($source, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function fallbackDraft(array $source, string $tone, ?string $note = null): array
    {
        $title = $source['title'] ?? $source['name'] ?? 'Bholavashi';
        $type = $source['type'] ?? $source['source_type'] ?? 'feature';
        $caption = match ($type) {
            'food_item' => "{$title} এখন ভোলাবাসী অ্যাপে। পছন্দের খাবার সহজে অর্ডার করুন, ডেলিভারি আপডেট দেখুন।",
            'restaurant' => "{$title} থেকে খাবার অর্ডার করুন ভোলাবাসী অ্যাপে। লোকাল রেস্টুরেন্ট, সহজ অর্ডার, পরিষ্কার ট্র্যাকিং।",
            'coupon' => "ভোলাবাসী অফার: {$title} কুপন ব্যবহার করে অর্ডারে ছাড় নিন। শর্ত প্রযোজ্য।",
            default => "ভোলাবাসী অ্যাপে ভোলার দরকারি সেবা এখন আরও সহজ। আজই অ্যাপটি ব্যবহার করুন।",
        };

        return [
            'caption' => $caption."\n\n#ভোলাবাসী #Bhola #LocalService",
            'hashtags' => ['#ভোলাবাসী', '#Bhola', '#LocalService'],
            'image_prompt' => "Create a clean square social media graphic for Bholavashi about {$title}. Bangladeshi local service app style, warm red and teal accents, no fake logos, no readable tiny text.",
            'topic' => $title,
            'raw' => ['fallback' => true, 'note' => $note],
        ];
    }

    private function foodItemSnapshot(FoodItem $item, ?string $imageUrl = null): array
    {
        return [
            'type' => 'food_item',
            'id' => $item->id,
            'title' => $item->name,
            'restaurant' => $item->restaurant?->name,
            'price' => $item->price,
            'discount_price' => $item->discount_price,
            'is_popular' => (bool) $item->is_popular,
            'is_promoted' => (bool) $item->is_promoted,
            'image_url' => $imageUrl,
        ];
    }

    private function restaurantSnapshot(Restaurant $restaurant, ?string $imageUrl = null): array
    {
        return [
            'type' => 'restaurant',
            'id' => $restaurant->id,
            'title' => $restaurant->name,
            'area' => $this->restaurantAreaLabel($restaurant),
            'address' => $restaurant->address,
            'promotion_badge' => $restaurant->promotion_badge,
            'image_url' => $imageUrl,
        ];
    }

    private function couponSnapshot(FoodCoupon $coupon): array
    {
        return [
            'type' => 'coupon',
            'id' => $coupon->id,
            'title' => $coupon->code,
            'restaurant' => $coupon->restaurant?->name,
            'discount_type' => $coupon->discount_type,
            'discount_value' => $coupon->discount_value,
            'minimum_order' => $coupon->minimum_order,
            'max_discount' => $coupon->max_discount,
            'ends_at' => optional($coupon->ends_at)->toDateTimeString(),
        ];
    }

    private function featureSources(): array
    {
        $features = [
            ['id' => 1, 'title' => 'Food delivery', 'subtitle' => 'খাবার অর্ডার ও লাইভ ট্র্যাকিং'],
            ['id' => 2, 'title' => 'Medicine delivery', 'subtitle' => 'মেডিসিন অর্ডার ও পেমেন্ট'],
            ['id' => 3, 'title' => 'Rider tracking', 'subtitle' => 'রাইডার লোকেশন ও অর্ডার প্রগ্রেস'],
            ['id' => 4, 'title' => 'Local services', 'subtitle' => 'ভোলার দরকারি সেবা এক অ্যাপে'],
        ];

        return array_map(fn ($feature) => [
            'source_type' => 'app_feature',
            'source_id' => $feature['id'],
            'title' => $feature['title'],
            'subtitle' => $feature['subtitle'],
            'image_url' => null,
            'snapshot' => ['type' => 'app_feature', ...$feature],
        ], $features);
    }

    private function restaurantAreaLabel(Restaurant $restaurant): ?string
    {
        return collect([$restaurant->upazila ?? null, $restaurant->district ?? null])
            ->filter(fn ($value) => filled($value))
            ->implode(', ') ?: null;
    }

    private function extractResponseText(array $payload): ?string
    {
        foreach (($payload['output'] ?? []) as $item) {
            foreach (($item['content'] ?? []) as $content) {
                if (($content['type'] ?? '') === 'output_text') {
                    return $content['text'] ?? null;
                }
            }
        }
        return null;
    }
}
