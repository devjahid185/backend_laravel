<?php

namespace Database\Seeders;

use App\Models\PropertyCategory;
use Illuminate\Database\Seeder;

class PropertyCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'বাসা/ফ্ল্যাট',
            'সাবলেট',
            'রুম',
            'অফিস স্পেস',
            'শপ/কমার্শিয়াল',
            'গুদাম/ওয়্যারহাউস',
            'প্লট/জমি',
            'ডুপ্লেক্স/ভিলা',
            'হোস্টেল/মেস',
            'গ্যারেজ/পার্কিং',
            'বিল্ডিং',
            'অন্যান্য',
        ];

        foreach ($categories as $name) {
            PropertyCategory::query()->firstOrCreate(['name' => $name]);
        }
    }
}
