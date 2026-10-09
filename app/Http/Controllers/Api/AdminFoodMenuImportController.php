<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiSocialSetting;
use App\Models\FoodCategory;
use App\Models\FoodItem;
use App\Models\FoodMenuImport;
use App\Models\MediaAsset;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminFoodMenuImportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $imports = FoodMenuImport::query()
            ->with('restaurant:id,name,phone')
            ->latest()
            ->limit((int) min(max($request->integer('limit', 12), 1), 50))
            ->get()
            ->map(fn (FoodMenuImport $import): array => $this->serializeImport($import));

        return response()->json(['data' => $imports]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'restaurant_id' => ['required', 'integer', 'exists:restaurants,id'],
            'images' => ['required', 'array', 'min:1', 'max:8'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        Restaurant::query()->findOrFail((int) $data['restaurant_id']);
        $images = [];
        foreach ($data['images'] as $file) {
            $path = $file->store('food-menu-imports/'.date('Y/m'), 'public');
            $images[] = [
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
            ];
        }

        $import = FoodMenuImport::query()->create([
            'restaurant_id' => (int) $data['restaurant_id'],
            'user_id' => $request->user()?->id,
            'status' => 'uploaded',
            'source_images' => $images,
            'notes' => $data['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Menu card uploaded. Review the AI draft before creating items.',
            'import' => $this->serializeImport($import->fresh('restaurant:id,name,phone')),
        ], 201);
    }

    public function storeDraft(Request $request): JsonResponse
    {
        $data = $request->validate([
            'restaurant_id' => ['nullable', 'integer', 'exists:restaurants,id'],
            'title' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.selected' => ['nullable', 'boolean'],
            'items.*.name' => ['required', 'string', 'max:160'],
            'items.*.category_name' => ['nullable', 'string', 'max:120'],
            'items.*.food_category_id' => ['nullable', 'integer', 'exists:food_categories,id'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'items.*.discount_price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'items.*.image_url' => ['nullable', 'string', 'max:2048'],
            'items.*.image_path' => ['nullable', 'string', 'max:2048'],
            'items.*.size_options' => ['nullable', 'array'],
            'items.*.size_options.*.name' => ['required_with:items.*.size_options', 'string', 'max:80'],
            'items.*.size_options.*.price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'items.*.preparation_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
        ]);

        $restaurantId = isset($data['restaurant_id']) ? (int) $data['restaurant_id'] : null;
        $items = $this->normalizeAiItems($data['items'], $restaurantId);
        $import = FoodMenuImport::query()->create([
            'restaurant_id' => $restaurantId,
            'user_id' => $request->user()?->id,
            'status' => 'draft_ready',
            'source_images' => [],
            'extracted_items' => $items,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'extracted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Chat draft saved. Select restaurant and approve from admin panel.',
            'import' => $this->serializeImport($import->fresh('restaurant:id,name,phone')),
        ], 201);
    }

    public function show(FoodMenuImport $import): JsonResponse
    {
        return response()->json($this->serializeImport($import->load('restaurant:id,name,phone')));
    }

    public function uploadItemImage(Request $request, FoodMenuImport $import): JsonResponse
    {
        $data = $request->validate([
            'index' => ['required', 'integer', 'min:0'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $items = $import->extracted_items ?: [];
        $index = (int) $data['index'];
        if (! array_key_exists($index, $items)) {
            return response()->json(['message' => 'Draft item not found for image upload.'], 422);
        }

        $file = $request->file('image');
        $path = $file->store('food-menu-import-items/'.date('Y/m'), 'public');
        $image = [
            'image_path' => $path,
            'image_url' => Storage::disk('public')->url($path),
            'image_name' => $file->getClientOriginalName(),
            'image_mime' => $file->getMimeType(),
        ];

        $items[$index] = array_merge((array) $items[$index], $image);
        $import->forceFill(['extracted_items' => $items])->save();

        return response()->json([
            'message' => 'Draft item image attached.',
            'image' => $image,
            'import' => $this->serializeImport($import->fresh('restaurant:id,name,phone')),
        ]);
    }

    public function extract(FoodMenuImport $import): JsonResponse
    {
        $settings = AiSocialSetting::current();
        if (! $settings->hasOpenaiKey()) {
            return response()->json(['message' => 'Existing OpenAI API key is missing in AI Social settings.'], 422);
        }

        $images = $import->source_images ?: [];
        if (! $images) {
            return response()->json(['message' => 'No menu card image found for this import.'], 422);
        }

        $import->forceFill(['status' => 'extracting', 'error_message' => null])->save();

        try {
            $payload = $this->openAiPayload($settings, $import, $images);
            $response = Http::withToken($settings->openai_api_key_plain)
                ->timeout(120)
                ->post('https://api.openai.com/v1/responses', $payload);

            if (! $response->successful()) {
                throw new \RuntimeException($response->body());
            }

            $text = $response->json('output_text') ?: $this->extractResponseText($response->json());
            $decoded = json_decode((string) $text, true);
            if (! is_array($decoded)) {
                throw new \RuntimeException('OpenAI response was not valid JSON.');
            }

            $items = $this->normalizeAiItems((array) ($decoded['items'] ?? []), $import->restaurant_id);
            $import->forceFill([
                'status' => $items ? 'draft_ready' : 'needs_review',
                'extracted_items' => $items,
                'error_message' => $items ? null : 'No menu item could be extracted from the uploaded image.',
                'extracted_at' => now(),
            ])->save();

            return response()->json([
                'message' => $items ? 'AI draft ready. Please review before creating food items.' : 'No item detected. Try a clearer menu card image.',
                'import' => $this->serializeImport($import->fresh('restaurant:id,name,phone')),
            ]);
        } catch (\Throwable $e) {
            $import->forceFill([
                'status' => 'failed',
                'error_message' => Str::limit($e->getMessage(), 2000),
            ])->save();

            return response()->json([
                'message' => 'Menu extraction failed. '.$import->error_message,
                'import' => $this->serializeImport($import->fresh('restaurant:id,name,phone')),
            ], 422);
        }
    }

    public function createItems(Request $request, FoodMenuImport $import): JsonResponse
    {
        $data = $request->validate([
            'restaurant_id' => ['nullable', 'integer', 'exists:restaurants,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.selected' => ['nullable', 'boolean'],
            'items.*.name' => ['required', 'string', 'max:160'],
            'items.*.category_name' => ['nullable', 'string', 'max:120'],
            'items.*.food_category_id' => ['nullable', 'integer', 'exists:food_categories,id'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'items.*.discount_price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'items.*.image_url' => ['nullable', 'string', 'max:2048'],
            'items.*.image_path' => ['nullable', 'string', 'max:2048'],
            'items.*.size_options' => ['nullable', 'array'],
            'items.*.size_options.*.name' => ['required_with:items.*.size_options', 'string', 'max:80'],
            'items.*.size_options.*.price' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'items.*.preparation_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
        ]);

        $restaurantId = (int) ($import->restaurant_id ?: ($data['restaurant_id'] ?? 0));
        if ($restaurantId <= 0) {
            return response()->json(['message' => 'Please select a restaurant before approving this draft.'], 422);
        }

        $createdIds = DB::transaction(function () use ($data, $import, $restaurantId): array {
            $ids = [];
            foreach ($data['items'] as $row) {
                if (array_key_exists('selected', $row) && ! $row['selected']) {
                    continue;
                }

                $name = trim((string) $row['name']);
                if ($name === '') {
                    continue;
                }

                $exists = FoodItem::query()
                    ->where('restaurant_id', $restaurantId)
                    ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
                    ->exists();
                if ($exists) {
                    continue;
                }

                $categoryId = $row['food_category_id'] ?? null;
                if (! $categoryId && filled($row['category_name'] ?? null)) {
                    $categoryId = $this->categoryIdFor((string) $row['category_name']);
                }

                $item = FoodItem::query()->create([
                    'restaurant_id' => $restaurantId,
                    'food_category_id' => $categoryId,
                    'name' => $name,
                    'slug' => $this->uniqueFoodItemSlug($restaurantId, $name),
                    'description' => $row['description'] ?? null,
                    'image_url' => $row['image_url'] ?? null,
                    'price' => round((float) $row['price'], 2),
                    'discount_price' => filled($row['discount_price'] ?? null) ? round((float) $row['discount_price'], 2) : null,
                    'size_options' => $this->normalizeSizeOptions($row['size_options'] ?? []),
                    'preparation_minutes' => (int) ($row['preparation_minutes'] ?? 25),
                    'is_available' => true,
                    'status' => 'active',
                ]);
                $this->attachDraftImageToFoodItem($item, $row);
                $ids[] = $item->id;
            }

            $import->forceFill([
                'restaurant_id' => $restaurantId,
                'status' => 'completed',
                'extracted_items' => $data['items'],
                'created_food_item_ids' => $ids,
                'completed_at' => now(),
            ])->save();

            return $ids;
        });

        return response()->json([
            'message' => count($createdIds).' food item created from menu card.',
            'created_ids' => $createdIds,
            'import' => $this->serializeImport($import->fresh('restaurant:id,name,phone')),
        ]);
    }

    private function openAiPayload(AiSocialSetting $settings, FoodMenuImport $import, array $images): array
    {
        $restaurant = $import->restaurant ?: Restaurant::query()->find($import->restaurant_id);
        $content = [
            [
                'type' => 'input_text',
                'text' => "Extract restaurant menu items from the attached menu card images.\n"
                    ."Restaurant: ".($restaurant?->name ?: 'Unknown')."\n"
                    ."Language may be Bangla, English, or mixed.\n"
                    ."Return strict JSON only with this shape: {\"items\":[{\"name\":\"\",\"category_name\":\"\",\"description\":\"\",\"price\":0,\"discount_price\":null,\"size_options\":[{\"name\":\"1 person\",\"price\":0}],\"confidence\":0.0,\"notes\":\"\"}]}.\n"
                    ."Important: if a menu row has multiple package/people columns, keep it as one item and put every column price inside size_options. Set price to the lowest/single-person price. Do not collapse package prices into one wrong price.\n"
                    ."Rules: keep item names exactly readable, convert Bangladeshi taka prices to numeric values, ignore phone numbers, addresses, VAT notes, social links, headings without price, package decoration, and non-food text. If price is unclear use 0 and low confidence. Do not invent items.",
            ],
        ];

        foreach ($images as $image) {
            $path = $image['path'] ?? null;
            if (! $path || ! Storage::disk('public')->exists($path)) {
                continue;
            }
            $mime = $image['mime'] ?? Storage::disk('public')->mimeType($path) ?: 'image/jpeg';
            $content[] = [
                'type' => 'input_image',
                'image_url' => 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($path)),
            ];
        }

        return [
            'model' => $settings->openai_text_model ?: 'gpt-4.1-mini',
            'input' => [[
                'role' => 'user',
                'content' => $content,
            ]],
            'text' => ['format' => ['type' => 'json_object']],
        ];
    }

    private function normalizeAiItems(array $items, ?int $restaurantId): array
    {
        return collect($items)
            ->map(function ($raw) use ($restaurantId): ?array {
                if (! is_array($raw)) {
                    return null;
                }
                $name = trim((string) ($raw['name'] ?? ''));
                if ($name === '') {
                    return null;
                }
                $price = $this->money($raw['price'] ?? 0);
                $sizeOptions = $this->normalizeSizeOptions($raw['size_options'] ?? []);
                if ($price <= 0 && $sizeOptions) {
                    $price = (float) collect($sizeOptions)->pluck('price')->filter(fn ($value) => $value !== null && $value > 0)->min();
                }
                $categoryName = trim((string) ($raw['category_name'] ?? ''));
                $matchedCategoryId = $categoryName === '' ? null : $this->categoryIdFor($categoryName);
                $duplicate = $restaurantId
                    ? FoodItem::query()
                        ->where('restaurant_id', $restaurantId)
                        ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
                        ->first()
                    : null;

                return [
                    'selected' => $price > 0 && ! $duplicate,
                    'name' => $name,
                    'category_name' => $categoryName,
                    'food_category_id' => $matchedCategoryId,
                    'description' => trim((string) ($raw['description'] ?? '')) ?: null,
                    'price' => $price,
                    'discount_price' => filled($raw['discount_price'] ?? null) ? $this->money($raw['discount_price']) : null,
                    'image_url' => trim((string) ($raw['image_url'] ?? '')) ?: null,
                    'image_path' => trim((string) ($raw['image_path'] ?? '')) ?: null,
                    'size_options' => $sizeOptions,
                    'preparation_minutes' => 25,
                    'confidence' => max(0, min(1, (float) ($raw['confidence'] ?? 0.7))),
                    'notes' => trim((string) ($raw['notes'] ?? '')) ?: null,
                    'duplicate_food_item_id' => $duplicate?->id,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeSizeOptions(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(function ($option): ?array {
                if (is_string($option)) {
                    $parts = explode(':', $option, 2);
                    $name = trim($parts[0] ?? '');
                    $price = $parts[1] ?? null;
                } elseif (is_array($option)) {
                    $name = trim((string) ($option['name'] ?? $option['label'] ?? ''));
                    $price = $option['price'] ?? null;
                } else {
                    return null;
                }

                if ($name === '') {
                    return null;
                }

                return [
                    'name' => $name,
                    'price' => $price === null || $price === '' ? null : $this->money($price),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function categoryIdFor(string $name): int
    {
        $clean = trim($name);
        $existing = FoodCategory::query()->whereRaw('LOWER(name) = ?', [Str::lower($clean)])->first();
        if ($existing) {
            return $existing->id;
        }

        $slug = Str::slug($clean) ?: 'food-category-'.Str::lower(Str::random(6));
        $base = $slug;
        $i = 2;
        while (FoodCategory::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return FoodCategory::query()->create([
            'name' => $clean,
            'slug' => $slug,
            'is_active' => true,
            'sort_order' => 0,
        ])->id;
    }

    private function uniqueFoodItemSlug(int $restaurantId, string $name): string
    {
        $base = Str::slug($restaurantId.'-'.$name) ?: 'food-item-'.$restaurantId.'-'.Str::lower(Str::random(6));
        $slug = $base;
        $i = 2;
        while (FoodItem::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }
        return $slug;
    }

    private function money(mixed $value): float
    {
        if (is_numeric($value)) {
            return round((float) $value, 2);
        }
        $clean = preg_replace('/[^\d.]/', '', (string) $value);
        return round((float) ($clean ?: 0), 2);
    }

    private function attachDraftImageToFoodItem(FoodItem $item, array $row): void
    {
        $path = trim((string) ($row['image_path'] ?? ''));
        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return;
        }

        MediaAsset::query()
            ->where('target_type', 'food_item')
            ->where('target_id', $item->id)
            ->update(['is_primary' => false]);

        MediaAsset::query()->create([
            'user_id' => request()->user()?->id,
            'section' => 'food_item',
            'target_type' => 'food_item',
            'target_id' => $item->id,
            'file_path' => $path,
            'is_primary' => true,
            'sort_order' => 0,
            'alt_text' => $item->name,
        ]);
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

    private function serializeImport(FoodMenuImport $import): array
    {
        return [
            'id' => $import->id,
            'restaurant_id' => $import->restaurant_id,
            'restaurant' => $import->restaurant,
            'status' => $import->status,
            'source_images' => $import->source_images ?: [],
            'extracted_items' => $import->extracted_items ?: [],
            'created_food_item_ids' => $import->created_food_item_ids ?: [],
            'notes' => $import->notes,
            'error_message' => $import->error_message,
            'extracted_at' => $import->extracted_at,
            'completed_at' => $import->completed_at,
            'created_at' => $import->created_at,
        ];
    }
}
