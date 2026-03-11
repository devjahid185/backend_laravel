<?php

namespace Database\Seeders;

use App\Models\CarCategory;
use Illuminate\Database\Seeder;

class CarCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'সেডান',
            'হ্যাচব্যাক',
            'এসইউভি',
            'মাইক্রোবাস',
            'মিনিবাস',
            'বাস',
            'প্রাইভেট কার',
            'নোয়া/এক্স-নোয়া',
            'হাইস',
            'ভ্যান',
            'অটো',
            'অটোরিকশা',
            'সি এন জি',
            'ইজি বাইক',
            'টেম্পু',
            'পিকআপ',
            'ট্রাক',
            'কাভার্ড ভ্যান',
            'অ্যাম্বুলেন্স',
            'বাইক',
            'ইলেকট্রিক কার',
            'লাক্সারি',
        ];

        foreach ($categories as $name) {
            CarCategory::query()->firstOrCreate(['name' => $name]);
        }
    }
}
