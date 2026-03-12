<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\RestaurantCategory;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        $categories = RestaurantCategory::query()->get()->keyBy('name');

        $items = [
            [
                'name' => 'TFC Bhola',
                'category' => 'রেস্টুরেন্ট',
                'district' => 'Bhola',
                'upazila' => 'Bhola Sadar',
                'address' => 'Walton Showroom, Ukil Para, Sadar Road 3rd floor, Bhola 8300',
                'phone' => '01753-407784',
            ],
            [
                'name' => 'Kabana Multi-Cuisine Restaurant',
                'category' => 'রেস্টুরেন্ট',
                'district' => 'Bhola',
                'upazila' => 'Bhola Sadar',
                'address' => '1st Floor Abashar Center, Sadar Road, Bhola 8300',
                'phone' => '01846-628800',
            ],
            [
                'name' => 'The Royal Kitchen',
                'category' => 'রেস্টুরেন্ট',
                'district' => 'Bhola',
                'upazila' => 'Bhola Sadar',
                'address' => 'Bhola 8320',
                'phone' => '01747-676318',
                'opening_hours' => '09:15 AM - 10:45 PM',
            ],
        ];

        foreach ($items as $item) {
            $categoryName = $item['category'] ?? null;
            $categoryId = $categoryName && isset($categories[$categoryName])
                ? (int) $categories[$categoryName]->id
                : null;

            Restaurant::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'category_id' => $categoryId,
                    'name' => $item['name'],
                    'type' => $item['type'] ?? null,
                    'phone' => $item['phone'] ?? null,
                    'email' => $item['email'] ?? null,
                    'website' => $item['website'] ?? null,
                    'facebook' => $item['facebook'] ?? null,
                    'district' => $item['district'] ?? null,
                    'upazila' => $item['upazila'] ?? null,
                    'address' => $item['address'] ?? null,
                    'opening_hours' => $item['opening_hours'] ?? null,
                    'min_price' => $item['min_price'] ?? null,
                    'max_price' => $item['max_price'] ?? null,
                    'cuisines' => $item['cuisines'] ?? null,
                    'features' => $item['features'] ?? null,
                    'description' => $item['description'] ?? null,
                    'status' => 'active',
                ]
            );
        }
    }
}
