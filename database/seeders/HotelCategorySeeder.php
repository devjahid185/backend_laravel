<?php

namespace Database\Seeders;

use App\Models\HotelCategory;
use Illuminate\Database\Seeder;

class HotelCategorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'হোটেল', 'description' => 'বাণিজ্যিক হোটেল ও আবাসন'],
            ['name' => 'রিসোর্ট', 'description' => 'রিসোর্ট ও পর্যটন আবাসন'],
            ['name' => 'গেস্ট হাউস', 'description' => 'গেস্ট হাউস ও প্রশিক্ষণ কেন্দ্র'],
            ['name' => 'মোটেল', 'description' => 'সড়কপথের মোটেল'],
            ['name' => 'লজ', 'description' => 'লো বাজেট থাকার স্থান'],
        ];

        foreach ($items as $item) {
            HotelCategory::query()->firstOrCreate(['name' => $item['name']], $item);
        }
    }
}
