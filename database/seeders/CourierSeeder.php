<?php

namespace Database\Seeders;

use App\Models\CourierCompany;
use App\Models\CourierOffice;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CourierSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->firstOrCreate(
            ['phone' => '01700000001'],
            [
                'name' => 'Courier Seed',
                'email' => 'courier@districtsuperapp.local',
                'password' => Hash::make('Seed@12345'),
                'district' => 'Bhola',
                'role' => 'admin',
                'verified' => true,
            ]
        );

        $companies = [
            [
                'name' => 'Sundarban Courier Service',
                'website' => 'https://sundarbancourierltd.com',
                'facebook' => 'https://www.facebook.com/OfficialSundarbanCourierLtd',
                'email' => 'mail@sundarbancourier.com.bd',
                'hotline' => '09612-003003',
            ],
            [
                'name' => 'SA Paribahan',
                'website' => 'https://www.saparibahan.com',
                'facebook' => 'https://www.facebook.com/sapdhk',
                'email' => 'info@saparibahan.com',
                'hotline' => '01766-688371',
            ],
            [
                'name' => 'Shodagor Express',
                'website' => 'https://www.shodagorexpress.net',
                'facebook' => 'https://www.facebook.com/shodagorexpresslimited',
                'email' => 'info@shodagorexpress.net',
                'hotline' => '0963-333888',
            ],
            [
                'name' => 'Pathao Courier',
                'website' => 'https://pathao.com',
                'facebook' => 'https://www.facebook.com/Pathao',
                'email' => 'support@pathao.com',
            ],
            [
                'name' => 'Steadfast Courier',
                'website' => 'https://steadfast.com.bd',
                'facebook' => 'https://www.facebook.com/steadfastbd',
                'email' => 'support@steadfast.com.bd',
            ],
        ];

        $companyMap = [];
        foreach ($companies as $c) {
            $company = CourierCompany::query()->firstOrCreate(
                ['name' => $c['name']],
                [
                    'user_id' => $owner->id,
                    'website' => $c['website'] ?? null,
                    'facebook' => $c['facebook'] ?? null,
                    'email' => $c['email'] ?? null,
                    'hotline' => $c['hotline'] ?? null,
                ]
            );
            $companyMap[$c['name']] = $company->id;
        }

        $offices = [
            [
                'company' => 'Sundarban Courier Service',
                'name' => 'Bhola Sadar Branch',
                'district' => 'Bhola',
                'upazila' => 'Bhola Sadar',
                'address' => 'Infront of Homio College, Bhola Sadar, Bhola',
                'phones' => ['01725-654391', '01952-255786', '01936-003379', '01936-003365'],
                'hotline' => '09612-003003',
                'email' => 'mail@sundarbancourier.com.bd',
            ],
            [
                'company' => 'Sundarban Courier Service',
                'name' => 'Borhanuddin Branch',
                'district' => 'Bhola',
                'upazila' => 'Borhanuddin',
                'address' => 'Holding No-248/2, North Bus Stand, Borhanuddin, Bhola',
                'phones' => ['01936-001688', '01936-001689'],
                'hotline' => '09612-003003',
                'email' => 'mail@sundarbancourier.com.bd',
            ],
            [
                'company' => 'Sundarban Courier Service',
                'name' => 'Aicha Thana Branch',
                'district' => 'Bhola',
                'upazila' => 'Charfession',
                'address' => 'M/S Howlader Enterprise, South Aicha Bazar, Chor Fashion, Bhola',
                'phones' => ['01716-264922', '01714-242332'],
                'hotline' => '09612-003003',
                'email' => 'tuhin6347@gmail.com',
            ],
            [
                'company' => 'Sundarban Courier Service',
                'name' => 'Banglabazar (Bhola) Branch',
                'district' => 'Bhola',
                'upazila' => 'Bhola Sadar',
                'address' => 'Nayem Book House, Banglabazar Girls School Road, Bhola Sadar',
                'phones' => ['01709-608799', '01735-018043'],
                'hotline' => '09612-003003',
                'email' => 'scsbanglabazar1983@gmail.com',
            ],
            [
                'company' => 'Sundarban Courier Service',
                'name' => 'Daulatkhan Agency',
                'district' => 'Bhola',
                'upazila' => 'Daulatkhan',
                'address' => 'Janani Enterprise, Sadar Road, Ward-03, Daulatkhan, Bhola',
                'phones' => ['01721-050520'],
                'hotline' => '09612-003003',
                'email' => 'mail@sundarbancourier.com.bd',
            ],
            [
                'company' => 'Sundarban Courier Service',
                'name' => 'Dularhat Agency',
                'district' => 'Bhola',
                'upazila' => 'Charfession',
                'address' => 'M/S PD Electronics, Dularhat Bazar, Chor Fashion, Bhola',
                'phones' => ['01711-244812'],
                'hotline' => '09612-003003',
                'email' => 'mail@sundarbancourier.com.bd',
            ],
            [
                'company' => 'Sundarban Courier Service',
                'name' => 'Lalmohan Thana Agency',
                'district' => 'Bhola',
                'upazila' => 'Lalmohan',
                'address' => 'Mukta Studio, South Side of Karim Road, Near Masjid, Lalmohan, Bhola',
                'phones' => ['01710-818024', '01925-178446', '01712-276704'],
                'hotline' => '09612-003003',
                'email' => 'shibluhawlader26@gmail.com',
            ],
            [
                'company' => 'Sundarban Courier Service',
                'name' => 'Monpura Agency',
                'district' => 'Bhola',
                'upazila' => 'Manpura',
                'address' => 'M/S Monpura Enterprize, 3 No. Ward Chor Zatin, Hazir Hat, Monpura, Bhola',
                'phones' => ['01710-528597'],
                'hotline' => '09612-003003',
                'email' => 'nazim3051@gmail.com',
            ],
            [
                'company' => 'Sundarban Courier Service',
                'name' => 'Shoshivuson Agency',
                'district' => 'Bhola',
                'upazila' => 'Shoshivuson',
                'address' => 'Contractor Computer & Photostat, Ward-6, Eoazpur, Shoshivuson, Bhola',
                'phones' => ['01718-925305', '01611-925305'],
                'hotline' => '09612-003003',
                'email' => 'mail@sundarbancourier.com.bd',
            ],
            [
                'company' => 'SA Paribahan',
                'name' => 'Bhola Branch',
                'district' => 'Bhola',
                'upazila' => 'Bhola Sadar',
                'address' => 'Taslim House, Kalinath Rayer Bazar, Beside Hatkhola Mosque, Bhola Sadar, Bhola',
                'phones' => ['01322-843250', '01322-843251', '01322-843252'],
                'hotline' => '01766-688371',
                'email' => 'info@saparibahan.com',
            ],
            [
                'company' => 'Shodagor Express',
                'name' => 'Bhola Branch',
                'district' => 'Bhola',
                'upazila' => 'Bhola Sadar',
                'address' => 'Holding-776/1, Bhola Powrosova',
                'phones' => ['01324-719190'],
                'hotline' => '0963-333888',
                'email' => 'info@shodagorexpress.net',
            ],
        ];

        foreach ($offices as $o) {
            $companyId = $companyMap[$o['company']] ?? null;
            if (! $companyId) {
                continue;
            }

            CourierOffice::query()->updateOrCreate(
                [
                    'company_id' => $companyId,
                    'name' => $o['name'],
                ],
                [
                    'user_id' => $owner->id,
                    'district' => $o['district'] ?? null,
                    'upazila' => $o['upazila'] ?? null,
                    'address' => $o['address'] ?? null,
                    'phones' => $o['phones'] ?? null,
                    'hotline' => $o['hotline'] ?? null,
                    'email' => $o['email'] ?? null,
                    'status' => 'active',
                ]
            );
        }
    }
}
