<?php

namespace Database\Seeders;

use App\Models\ElectricityOffice;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ElectricityOfficeSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->firstOrCreate(
            ['phone' => '01700000000'],
            [
                'name' => 'System Seed',
                'email' => 'seed@districtsuperapp.local',
                'password' => Hash::make('Seed@12345'),
                'district' => 'Bhola',
                'role' => 'admin',
                'verified' => true,
            ]
        );

        $rows = [
            [
                'name' => 'ভোলা পল্লী বিদ্যুৎ সমিতি',
                'provider' => 'বাংলাদেশ পল্লী বিদ্যুতায়ন বোর্ড (REB)',
                'office_type' => 'সমিতি কার্যালয়',
                'district' => 'Bhola',
                'upazila' => 'Bhola Sadar',
                'address' => 'চরফ্যাসন রোড, ভোলা',
                'phones' => ['0491-63036', '0491-63021'],
                'email' => 'bhopbs@gmail.com',
                'notes' => 'নির্বাহী প্রকৌশলী কার্যালয়',
                'website' => 'https://reb.gov.bd',
            ],
            [
                'name' => 'ভোলা বিদ্যুৎ সরবরাহ অফিস (ওজোপাডিকো)',
                'provider' => 'West Zone Power Distribution Company Ltd (WZPDCL)',
                'office_type' => 'বিদ্যুৎ সরবরাহ অফিস',
                'district' => 'Bhola',
                'upazila' => 'Bhola Sadar',
                'address' => 'নির্বাহী প্রকৌশলী, বিদ্যুৎ সরবরাহ, ওয়েষ্ট জোন পাওয়ার ডিস্ট্রিবিউশন কোম্পানী লিমিটেড, ভোলা',
                'phones' => ['0491-61335', '01711668463'],
                'email' => 'WZ.bhola@gmail.com',
                'website' => 'https://pdb.bhola.gov.bd',
            ],
            [
                'name' => 'বোরহানউদ্দিন বিদ্যুৎ সরবরাহ (ওজোপাডিকো)',
                'provider' => 'West Zone Power Distribution Company Ltd (WZPDCL)',
                'office_type' => 'শাখা অফিস',
                'district' => 'Bhola',
                'upazila' => 'Borhanuddin',
                'address' => 'ওজোপাডিকো, বোরহানউদ্দিন',
                'phones' => ['01917722203'],
                'email' => 'wz.borhanuddin@gmail.com',
                'website' => 'https://pdb.bhola.gov.bd',
            ],
            [
                'name' => 'চরফ্যাশন বিদ্যুৎ সরবরাহ (ওজোপাডিকো)',
                'provider' => 'West Zone Power Distribution Company Ltd (WZPDCL)',
                'office_type' => 'শাখা অফিস',
                'district' => 'Bhola',
                'upazila' => 'Charfession',
                'address' => 'ওজোপাডিকো, চরফ্যাশন',
                'phones' => ['01917722204'],
                'email' => 'wz.charfession@gmail.com',
                'website' => 'https://pdb.bhola.gov.bd',
            ],
            [
                'name' => 'মনপুরা বিদ্যুৎ সরবরাহ (ওজোপাডিকো)',
                'provider' => 'West Zone Power Distribution Company Ltd (WZPDCL)',
                'office_type' => 'শাখা অফিস',
                'district' => 'Bhola',
                'upazila' => 'Monpura',
                'address' => 'ওজোপাডিকো, মনপুরা',
                'phones' => ['01917722205'],
                'email' => 'wz.monpura@gmail.com',
                'website' => 'https://pdb.bhola.gov.bd',
            ],
        ];

        foreach ($rows as $row) {
            ElectricityOffice::query()->updateOrCreate(
                [
                    'name' => $row['name'],
                    'district' => $row['district'],
                ],
                $row + [
                    'user_id' => $owner->id,
                    'status' => 'active',
                ]
            );
        }
    }
}
