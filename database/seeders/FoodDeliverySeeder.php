<?php

namespace Database\Seeders;

use App\Models\FoodCategory;
use App\Models\FoodCoupon;
use App\Models\FoodItem;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FoodDeliverySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'ভাত ও বিরিয়ানি', 'slug' => 'rice-biryani', 'icon' => 'rice_bowl', 'sort_order' => 1],
            ['name' => 'ফাস্ট ফুড', 'slug' => 'fast-food', 'icon' => 'fastfood', 'sort_order' => 2],
            ['name' => 'চাইনিজ', 'slug' => 'chinese', 'icon' => 'ramen_dining', 'sort_order' => 3],
            ['name' => 'মিষ্টি ও ডেজার্ট', 'slug' => 'dessert', 'icon' => 'cake', 'sort_order' => 4],
            ['name' => 'চা ও কফি', 'slug' => 'tea-coffee', 'icon' => 'local_cafe', 'sort_order' => 5],
            ['name' => 'মাছ ও স্থানীয় খাবার', 'slug' => 'local-fish', 'icon' => 'set_meal', 'sort_order' => 6],
            ['name' => 'নাশতা', 'slug' => 'breakfast', 'icon' => 'bakery_dining', 'sort_order' => 7],
            ['name' => 'পানীয়', 'slug' => 'drinks', 'icon' => 'local_drink', 'sort_order' => 8],
        ];

        foreach ($categories as $category) {
            FoodCategory::query()->updateOrCreate(['slug' => $category['slug']], $category + ['is_active' => true]);
        }

        $defaultItems = [
            ['ভোলা স্পেশাল বিরিয়ানি', 'rice-biryani', 180, 160, 'গরম বিরিয়ানি, সালাদ ও সস সহ।'],
            ['চিকেন ফ্রাইড রাইস', 'chinese', 220, null, 'ডিম, সবজি ও চিকেন দিয়ে তৈরি ফ্রাইড রাইস।'],
            ['বিফ খিচুড়ি', 'rice-biryani', 190, null, 'ভুনা গরু ও ডিমসহ ঘরোয়া খিচুড়ি।'],
            ['চিকেন বার্গার', 'fast-food', 140, 120, 'সফট বান, চিকেন প্যাটি ও ফ্রেশ সস।'],
            ['ফুচকা প্লেট', 'fast-food', 100, null, 'টক, ঝাল ও মিষ্টি সসসহ ফুচকা।'],
            ['ইলিশ ভাজা সেট', 'local-fish', 280, null, 'ভাত, ডাল, ভর্তা ও ইলিশ ভাজা।'],
            ['দুধ চা', 'tea-coffee', 25, null, 'গরম দুধ চা।'],
            ['ফালুদা', 'dessert', 130, null, 'ঠান্ডা ফালুদা আইসক্রিমসহ।'],
        ];

        $restaurants = Restaurant::query()->limit(20)->get();
        foreach ($restaurants as $restaurant) {
            $restaurant->forceFill([
                'delivery_available' => true,
                'takeaway_available' => true,
                'dine_in_available' => $restaurant->dine_in_available ?? true,
                'min_price' => $restaurant->min_price ?: 100,
                'max_price' => $restaurant->max_price ?: 500,
                'status' => 'active',
            ])->save();

            foreach ($defaultItems as $index => [$name, $categorySlug, $price, $discount, $description]) {
                $category = FoodCategory::query()->where('slug', $categorySlug)->first();
                FoodItem::query()->updateOrCreate(
                    ['restaurant_id' => $restaurant->id, 'slug' => Str::slug($restaurant->id . '-' . $name)],
                    [
                        'food_category_id' => $category?->id,
                        'name' => $name,
                        'description' => $description,
                        'price' => $price + ($index * 5),
                        'discount_price' => $discount,
                        'size_options' => ['Regular', 'Large'],
                        'spice_options' => ['Normal', 'Medium', 'Hot'],
                        'add_ons' => [
                            ['name' => 'Extra Sauce', 'price' => 20],
                            ['name' => 'Extra Salad', 'price' => 15],
                        ],
                        'preparation_minutes' => 20 + $index,
                        'is_available' => true,
                        'is_popular' => $index < 3,
                        'status' => 'active',
                    ]
                );
            }
        }

        foreach ([
            ['code' => 'BHOLA50', 'title' => 'ভোলাবাসী ৫০ টাকা ছাড়', 'discount_type' => 'fixed', 'discount_value' => 50, 'minimum_order' => 300],
            ['code' => 'FREEDEL', 'title' => 'ফ্রি ডেলিভারি', 'discount_type' => 'free_delivery', 'discount_value' => 0, 'minimum_order' => 250],
            ['code' => 'FIRST10', 'title' => 'প্রথম অর্ডারে ১০% ছাড়', 'discount_type' => 'percent', 'discount_value' => 10, 'minimum_order' => 200],
        ] as $coupon) {
            FoodCoupon::query()->updateOrCreate(['code' => $coupon['code']], $coupon + ['is_active' => true]);
        }
    }
}
