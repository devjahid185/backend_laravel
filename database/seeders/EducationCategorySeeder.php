<?php

namespace Database\Seeders;

use App\Models\EducationCategory;
use Illuminate\Database\Seeder;

class EducationCategorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'স্কুল', 'description' => 'প্রাথমিক ও মাধ্যমিক শিক্ষা প্রতিষ্ঠান'],
            ['name' => 'কলেজ', 'description' => 'উচ্চ মাধ্যমিক ও ডিগ্রি কলেজ'],
            ['name' => 'মাদ্রাসা', 'description' => 'দাখিল/আলিম/কামিল মাদ্রাসা'],
            ['name' => 'কোচিং সেন্টার', 'description' => 'কোচিং ও টিউশন সেন্টার'],
            ['name' => 'কারিগরি/টেকনিক্যাল', 'description' => 'টেকনিক্যাল স্কুল, পলিটেকনিক, TTC'],
            ['name' => 'বিশ্ববিদ্যালয়', 'description' => 'বিশ্ববিদ্যালয় ও উচ্চশিক্ষা প্রতিষ্ঠান'],
            ['name' => 'ট্রেনিং সেন্টার', 'description' => 'স্কিল ও প্রফেশনাল ট্রেনিং'],
        ];

        foreach ($items as $item) {
            EducationCategory::query()->firstOrCreate(['name' => $item['name']], $item);
        }
    }
}