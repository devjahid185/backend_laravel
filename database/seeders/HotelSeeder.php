<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\HotelCategory;
use Illuminate\Database\Seeder;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        $categories = HotelCategory::query()
            ->get()
            ->keyBy('name');

        $items = [
            [
                'name' => 'Hotel Asia Bhola',
                'category' => 'হোটেল',
                'district' => 'Bhola',
                'upazila' => 'Bhola Sadar',
                'address' => 'Bhola Sadar 8300, Bhola',
                'check_in' => '12:00 PM',
                'check_out' => '11:30 AM',
            ],
            [
                'name' => 'The Papillon Hotel Bhola',
                'category' => 'হোটেল',
                'district' => 'Bhola',
                'upazila' => 'Bhola Sadar',
                'address' => 'Holding No. 262-01, Zaman Center (4th floor), Sadar Road, Ukil Para, Bhola-8300',
                'check_in' => '12:30 PM',
                'check_out' => '12:30 PM',
            ],
            [
                'name' => 'Hotel Noorjahan',
                'category' => 'হোটেল',
                'phone' => '01952-918795',
                'district' => 'Bhola',
                'upazila' => 'Bhola Sadar',
                'address' => 'Main Rd, Bhola',
            ],
            [
                'name' => 'Hotel Asia International Bhola',
                'category' => 'হোটেল',
                'phone' => '01704-183901',
                'district' => 'Bhola',
                'upazila' => 'Bhola Sadar',
                'address' => 'Sadar Road, Bhola',
            ],
            [
                'name' => 'HEED Guest House & Training Center (Bhola)',
                'category' => 'গেস্ট হাউস',
                'phone' => '01713-276610',
                'district' => 'Bhola',
                'upazila' => 'Bhola Sadar',
                'address' => 'Bhola Sadar, Bhola',
                'amenities' => ['AC/Non-AC rooms', 'Training center', 'Dining', 'Security'],
            ],
        ];

        foreach ($items as $item) {
            $categoryName = $item['category'] ?? null;
            $categoryId = $categoryName && isset($categories[$categoryName])
                ? (int) $categories[$categoryName]->id
                : null;

            Hotel::query()->updateOrCreate(
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
                    'check_in' => $item['check_in'] ?? null,
                    'check_out' => $item['check_out'] ?? null,
                    'rooms_total' => $item['rooms_total'] ?? null,
                    'min_price' => $item['min_price'] ?? null,
                    'max_price' => $item['max_price'] ?? null,
                    'amenities' => $item['amenities'] ?? null,
                    'services' => $item['services'] ?? null,
                    'description' => $item['description'] ?? null,
                    'status' => 'active',
                ]
            );
        }
    }
}
