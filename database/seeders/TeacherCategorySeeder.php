<?php

namespace Database\Seeders;

use App\Models\TeacherCategory;
use Illuminate\Database\Seeder;

class TeacherCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'গণিত', 'description' => 'অঙ্ক, বীজগণিত, জ্যামিতি'],
            ['name' => 'ইংরেজি', 'description' => 'গ্রামার, স্পোকেন, রাইটিং'],
            ['name' => 'বাংলা', 'description' => 'ব্যাকরণ, সাহিত্য'],
            ['name' => 'বিজ্ঞান', 'description' => 'সাধারণ বিজ্ঞান'],
            ['name' => 'পদার্থবিজ্ঞান', 'description' => 'Physics'],
            ['name' => 'রসায়ন', 'description' => 'Chemistry'],
            ['name' => 'জীববিজ্ঞান', 'description' => 'Biology'],
            ['name' => 'আইসিটি', 'description' => 'কম্পিউটার ও প্রযুক্তি'],
            ['name' => 'হিসাববিজ্ঞান', 'description' => 'Accounting'],
            ['name' => 'ব্যবসায় শিক্ষা', 'description' => 'Commerce'],
            ['name' => 'সমাজবিজ্ঞান', 'description' => 'Social Science'],
            ['name' => 'ইসলাম শিক্ষা', 'description' => 'ইসলাম ও নৈতিক শিক্ষা'],
            ['name' => 'কোরআন/তাজবীদ', 'description' => 'কোরআন শিক্ষা'],
            ['name' => 'ভর্তি প্রস্তুতি', 'description' => 'বিশ্ববিদ্যালয়/মেডিকেল'],
            ['name' => 'কোচিং/প্রাইভেট টিউশন', 'description' => 'ব্যক্তিগত বা গ্রুপ টিউশন'],
            ['name' => 'অন্যান্য', 'description' => 'অন্যান্য বিষয়'],
        ];

        foreach ($categories as $category) {
            TeacherCategory::query()->firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
