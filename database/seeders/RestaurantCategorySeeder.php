<?php

namespace Database\Seeders;

use App\Models\RestaurantCategory;
use Illuminate\Database\Seeder;

class RestaurantCategorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'বাংলা খাবার', 'description' => 'ভাত, মাছ, মাংস, দেশি রান্না'],
            ['name' => 'বিরিয়ানি', 'description' => 'বিরিয়ানি, কাচ্চি, তেহারি'],
            ['name' => 'ফাস্ট ফুড', 'description' => 'বার্গার, ফ্রাই, স্যান্ডউইচ'],
            ['name' => 'চাইনিজ', 'description' => 'চাইনিজ ও এশিয়ান খাবার'],
            ['name' => 'ক্যাফে', 'description' => 'কফি, স্ন্যাকস, হালকা খাবার'],
            ['name' => 'ডেজার্ট', 'description' => 'মিষ্টি, ডেজার্ট ও বেকারি'],
            ['name' => 'সীফুড', 'description' => 'মাছ ও সীফুড স্পেশাল'],
            ['name' => 'স্ট্রিট ফুড', 'description' => 'ফুচকা, চটপটি, স্ন্যাকস'],
        ];

        foreach ($items as $item) {
            RestaurantCategory::query()->firstOrCreate(['name' => $item['name']], $item);
        }
    }
}
