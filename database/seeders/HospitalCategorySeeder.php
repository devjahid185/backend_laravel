<?php

namespace Database\Seeders;

use App\Models\HospitalCategory;
use Illuminate\Database\Seeder;

class HospitalCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'সরকারি হাসপাতাল',
            'বেসরকারি হাসপাতাল',
            'ডায়াগনস্টিক সেন্টার',
            'ক্লিনিক',
            'মাতৃসদন',
            'শিশু হাসপাতাল',
            'কার্ডিয়াক হাসপাতাল',
            'ক্যান্সার হাসপাতাল',
            'ট্রমা সেন্টার',
            'চক্ষু হাসপাতাল',
            'ডেন্টাল হাসপাতাল',
            'মানসিক স্বাস্থ্য',
            'পুনর্বাসন কেন্দ্র',
            'নার্সিং হোম',
        ];

        foreach ($categories as $name) {
            HospitalCategory::query()->firstOrCreate(['name' => $name]);
        }
    }
}
