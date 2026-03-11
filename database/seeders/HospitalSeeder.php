<?php

namespace Database\Seeders;

use App\Models\Hospital;
use App\Models\HospitalCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HospitalSeeder extends Seeder
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

        $categoryMap = HospitalCategory::query()
            ->get()
            ->keyBy('name')
            ->map(fn (HospitalCategory $c) => $c->id)
            ->all();

        $govCategoryId = $categoryMap['সরকারি হাসপাতাল'] ?? null;
        $clinicCategoryId = $categoryMap['ক্লিনিক'] ?? null;

        $rows = [
            [
                'name' => 'Bhola 250 bed District Sadar Hospital',
                'type' => 'District/General Hospital',
                'email' => 'bhola@hospi.dghs.gov.bd',
                'district' => 'Bhola',
                'upazila' => 'Bhola Sadar',
                'address' => 'Bhola Paurasava, Urban Ward No-03',
                'category_id' => $govCategoryId,
                'emergency_available' => true,
            ],
            [
                'name' => 'Bhola Chest Disease Clinic',
                'type' => 'Chest Disease Clinic',
                'email' => 'bhola@tc.dghs.gov.bd',
                'district' => 'Bhola',
                'upazila' => 'Bhola Sadar',
                'address' => 'Urban Ward No-08',
                'category_id' => $clinicCategoryId,
            ],
            [
                'name' => 'Borhanuddin Upazila Health Complex',
                'type' => 'Upazila Health Complex',
                'email' => 'borhanuddin@uhfpo.dghs.gov.bd',
                'district' => 'Bhola',
                'upazila' => 'Borhanuddin',
                'address' => 'Burhanuddin Paurasava',
                'category_id' => $govCategoryId,
                'emergency_available' => true,
            ],
            [
                'name' => 'Charfession Upazila Health Complex',
                'type' => 'Upazila Health Complex',
                'email' => 'charfession@uhfpo.dghs.gov.bd',
                'district' => 'Bhola',
                'upazila' => 'Charfession',
                'address' => 'Char Fasson Paurasava',
                'category_id' => $govCategoryId,
                'emergency_available' => true,
            ],
            [
                'name' => 'South Char Aicha 20 bed Hospital',
                'type' => '20-bed Hospital',
                'email' => 'southcharaicha20bed@hospi.dghs.gov.bd',
                'district' => 'Bhola',
                'upazila' => 'Charfession',
                'address' => 'Char Manika',
                'category_id' => $govCategoryId,
                'emergency_available' => true,
            ],
            [
                'name' => 'Daulatkhan Upazila Health Complex',
                'type' => 'Upazila Health Complex',
                'email' => 'daulatkhan@uhfpo.dghs.gov.bd',
                'district' => 'Bhola',
                'upazila' => 'Daulatkhan',
                'address' => 'Daulatkhan Paurasava, Urban Ward No-03',
                'category_id' => $govCategoryId,
                'emergency_available' => true,
                'lat' => 22.605118,
                'lng' => 90.745834,
            ],
            [
                'name' => 'Khairhat 30 bed Hospital',
                'type' => '30-bed Hospital',
                'email' => 'khairhat30bed@hospi.dghs.gov.bd',
                'district' => 'Bhola',
                'upazila' => 'Daulatkhan',
                'category_id' => $govCategoryId,
                'emergency_available' => true,
            ],
            [
                'name' => 'Lalmohan Upazila Health Complex',
                'type' => 'Upazila Health Complex',
                'email' => 'lalmohan@uhfpo.dghs.gov.bd',
                'district' => 'Bhola',
                'upazila' => 'Lalmohan',
                'category_id' => $govCategoryId,
                'emergency_available' => true,
            ],
            [
                'name' => 'Manpura Upazila Health Complex',
                'type' => 'Upazila Health Complex',
                'email' => 'manpura@uhfpo.dghs.gov.bd',
                'district' => 'Bhola',
                'upazila' => 'Manpura',
                'category_id' => $govCategoryId,
                'emergency_available' => true,
            ],
            [
                'name' => 'Tajumuddin Upazila Health Complex',
                'type' => 'Upazila Health Complex',
                'email' => 'tajumuddin@uhfpo.dghs.gov.bd',
                'district' => 'Bhola',
                'upazila' => 'Tazumuddin',
                'category_id' => $govCategoryId,
                'emergency_available' => true,
                'lat' => 22.415789,
                'lng' => 90.837997,
            ],
            [
                'name' => 'Dularhat Union Health Sub Center',
                'type' => 'Union Health Sub Center',
                'email' => 'dularhat@usc.dghs.gov.bd',
                'district' => 'Bhola',
                'upazila' => 'Charfession',
                'address' => 'Dhal Char',
                'category_id' => $clinicCategoryId,
            ],
            [
                'name' => 'Sarajganj(syedpur) Union Health Sub Center',
                'type' => 'Union Health Sub Center',
                'email' => 'sarajganj_syedpur@usc.dghs.gov.bd',
                'district' => 'Bhola',
                'upazila' => 'Daulatkhan',
                'address' => 'Saidpur',
                'category_id' => $clinicCategoryId,
            ],
            [
                'name' => 'Charponkirhat(kalma) Union Health Sub Center',
                'type' => 'Union Health Sub Center',
                'email' => 'charponkirhat_kalma@usc.dghs.gov.bd',
                'district' => 'Bhola',
                'upazila' => 'Lalmohan',
                'address' => 'Kalma',
                'category_id' => $clinicCategoryId,
            ],
            [
                'name' => 'Karterhat(ramaganj) Union Health Sub Center',
                'type' => 'Union Health Sub Center',
                'email' => 'karterhat_ramaganj@usc.dghs.gov.bd',
                'district' => 'Bhola',
                'upazila' => 'Lalmohan',
                'address' => 'Ramaganj',
                'category_id' => $clinicCategoryId,
            ],
            [
                'name' => 'Lordhardingue Union Health Sub Center',
                'type' => 'Union Health Sub Center',
                'email' => 'lordhardingue@usc.dghs.gov.bd',
                'district' => 'Bhola',
                'upazila' => 'Lalmohan',
                'address' => 'Lord Hardinje',
                'category_id' => $clinicCategoryId,
            ],
            [
                'name' => 'Mongalsikdar Union Health Sub Center',
                'type' => 'Union Health Sub Center',
                'email' => 'mongalsikdar@usc.dghs.gov.bd',
                'district' => 'Bhola',
                'upazila' => 'Lalmohan',
                'address' => 'Dhali Gaurnagar',
                'category_id' => $clinicCategoryId,
            ],
            [
                'name' => 'Monpura Union Health Sub Center',
                'type' => 'Union Health Sub Center',
                'email' => 'monpura@usc.dghs.gov.bd',
                'district' => 'Bhola',
                'upazila' => 'Manpura',
                'address' => 'Manpura',
                'category_id' => $clinicCategoryId,
            ],
        ];

        foreach ($rows as $row) {
            Hospital::query()->updateOrCreate(
                [
                    'name' => $row['name'],
                    'district' => $row['district'] ?? 'Bhola',
                ],
                array_merge($row, [
                    'user_id' => $owner->id,
                    'status' => 'active',
                ])
            );
        }
    }
}
