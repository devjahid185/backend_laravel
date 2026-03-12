<?php

namespace Database\Seeders;

use App\Models\EducationCategory;
use App\Models\EducationInstitute;
use Illuminate\Database\Seeder;

class EducationSeeder extends Seeder
{
    public function run(): void
    {
        $categories = EducationCategory::query()
            ->get()
            ->keyBy('name');

        $items = [
            // Colleges
            [
                'name' => 'Bhola Government College',
                'category' => 'কলেজ',
                'eiin' => '101205',
                'board' => 'Barisal Education Board',
                'district' => 'Bhola',
                'upazila' => 'Bhola Sadar',
                'address' => 'Yugirghol, Bhola-Char Fasson Road, Bhola 8300',
                'website' => 'https://bholagovtcollege.edu.bd',
                'type' => 'Government',
            ],
            [
                'name' => 'Char Fasson Govt. College',
                'category' => 'কলেজ',
                'eiin' => '101439',
                'board' => 'Barisal Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'address' => 'Principal Nazrul Islam Road, Char Fasson, Bhola 8340',
                'website' => 'https://charfassongovtcollege.edu.bd',
                'type' => 'Government',
            ],

            // Madrasas (BANBEIS Ebtedayee list - Char Fasson)
            [
                'name' => 'Abu Bakar Pur Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Abubakarpur Ahammadia Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Aligaon Ibrahim Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Aligaon Sekantor Ali Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Aminabad A Hakim Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Aminabad Kalimullah Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Aminabad Nurul Haque Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Aminabad Taheria Masjid Attach Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Aslampur Ahmmadia Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Aslampur Amiria Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Aslampur Anowaria Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Aslampur Mohammadia Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Aslampur Rahmania Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Aslampur Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'At-Kapat Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Char Shashi Bhushan Arab Ali Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Char Shashibhushon Abdul Malek Kazi Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Char Tofazzal Islamia Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Char Tofazzal Khorshedia Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Char Yeachinia Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Charmotahar Nur Hossan Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Char Newton Fatema Halim Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Char Monohar Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Dakhin Aminpur Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Dakhin Char Nolua Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Dakhin Fasson Ahammadia Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Dakkhin Aminabad Joynalia Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
            [
                'name' => 'Dakkhin Char Gopalpur Swatantra Ebtedayee Madrasah',
                'category' => 'মাদ্রাসা',
                'board' => 'Bangladesh Madrasah Education Board',
                'district' => 'Bhola',
                'upazila' => 'Char Fasson',
                'type' => 'Ebtedayee',
            ],
        ];

        foreach ($items as $item) {
            $categoryName = $item['category'] ?? null;
            $categoryId = $categoryName && isset($categories[$categoryName])
                ? (int) $categories[$categoryName]->id
                : null;

            EducationInstitute::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'category_id' => $categoryId,
                    'name' => $item['name'],
                    'type' => $item['type'] ?? null,
                    'eiin' => $item['eiin'] ?? null,
                    'board' => $item['board'] ?? null,
                    'phone' => $item['phone'] ?? null,
                    'email' => $item['email'] ?? null,
                    'website' => $item['website'] ?? null,
                    'facebook' => $item['facebook'] ?? null,
                    'district' => $item['district'] ?? null,
                    'upazila' => $item['upazila'] ?? null,
                    'address' => $item['address'] ?? null,
                    'opening_hours' => $item['opening_hours'] ?? null,
                    'levels' => $item['levels'] ?? null,
                    'mediums' => $item['mediums'] ?? null,
                    'facilities' => $item['facilities'] ?? null,
                    'description' => $item['description'] ?? null,
                    'status' => 'active',
                ]
            );
        }
    }
}