<?php

namespace Database\Seeders;

use App\Models\BloodDonor;
use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\EmergencyContact;
use App\Models\JobApplication;
use App\Models\JobPost;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceImage;
use App\Models\MarketplaceItem;
use App\Models\Message;
use App\Models\News;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Report;
use App\Models\Review;
use App\Models\ServiceBooking;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkerCategory;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Factory::create('en_US');

        $users = collect();
        for ($i = 1; $i <= 20; $i++) {
            $phone = sprintf('0171000%04d', $i);
            $users->push(User::query()->firstOrCreate(
                ['phone' => $phone],
                [
                    'name' => 'Demo User '.$i,
                    'email' => 'demo'.$i.'@districtsuperapp.local',
                    'password' => Hash::make('User@12345'),
                    'district' => 'Dhaka',
                    'upazila' => 'Savar',
                    'union_name' => 'Demo Union',
                    'address' => 'Demo Road '.$i,
                    'role' => 'user',
                    'verified' => true,
                    'rating' => rand(3, 5),
                ]
            ));
        }

        $workerCategories = WorkerCategory::query()->pluck('id');
        $businessCategories = BusinessCategory::query()->pluck('id');
        $marketplaceCategories = MarketplaceCategory::query()->pluck('id');

        $workers = collect();
        for ($i = 0; $i < 10; $i++) {
            $user = $users[$i];
            $worker = Worker::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'category_id' => $workerCategories->random(),
                    'experience' => rand(1, 12),
                    'hourly_price' => rand(300, 1200),
                    'skills' => $faker->sentence(6),
                    'service_area' => $faker->city(),
                    'availability' => true,
                    'description' => $faker->paragraph(),
                    'status' => 'approved',
                ]
            );
            $user->update(['role' => 'worker']);
            $workers->push($worker);
        }

        for ($i = 0; $i < 10; $i++) {
            ServiceBooking::query()->updateOrCreate(
                [
                    'user_id' => $users[10 + ($i % 10)]->id,
                    'worker_id' => $workers[$i % $workers->count()]->id,
                    'service_date' => now()->addDays($i + 1)->toDateString(),
                    'service_time' => sprintf('%02d:00:00', 9 + ($i % 8)),
                ],
                [
                    'description' => 'Demo booking request '.$i,
                    'price' => rand(500, 2000),
                    'status' => ['pending', 'confirmed', 'completed'][$i % 3],
                ]
            );
        }

        for ($i = 0; $i < 10; $i++) {
            $user = $users[$i];
            $user->update(['role' => 'business']);

            Business::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => 'Demo Business '.($i + 1),
                ],
                [
                    'category_id' => $businessCategories->random(),
                    'description' => $faker->sentence(10),
                    'address' => $faker->address(),
                    'latitude' => $faker->latitude(23.5, 24.0),
                    'longitude' => $faker->longitude(90.0, 90.6),
                    'opening_hours' => '09:00 AM - 09:00 PM',
                    'phone' => sprintf('0182000%04d', $i + 1),
                    'website' => 'https://business'.($i + 1).'.example.com',
                    'rating' => $faker->randomFloat(2, 3, 5),
                ]
            );
        }

        $items = collect();
        for ($i = 0; $i < 10; $i++) {
            $item = MarketplaceItem::query()->updateOrCreate(
                [
                    'title' => 'Demo Item '.($i + 1),
                ],
                [
                    'user_id' => $users[$i]->id,
                    'category_id' => $marketplaceCategories->random(),
                    'description' => $faker->sentence(12),
                    'price' => rand(1000, 60000),
                    'location' => $faker->city(),
                    'status' => 'active',
                ]
            );
            $items->push($item);

            MarketplaceImage::query()->updateOrCreate(
                [
                    'item_id' => $item->id,
                    'image' => 'https://picsum.photos/seed/item'.($i + 1).'/640/480',
                ],
                []
            );
        }

        for ($i = 0; $i < 10; $i++) {
            BloodDonor::query()->updateOrCreate(
                ['user_id' => $users[10 + $i]->id],
                [
                    'blood_group' => ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'][$i % 8],
                    'last_donation' => now()->subDays(rand(40, 180))->toDateString(),
                    'available' => true,
                    'location' => $faker->city(),
                ]
            );
        }

        $jobs = collect();
        for ($i = 0; $i < 10; $i++) {
            $jobs->push(JobPost::query()->updateOrCreate(
                ['title' => 'Demo Job Post '.($i + 1)],
                [
                    'posted_by' => $users[$i]->id,
                    'company' => 'Demo Company '.($i + 1),
                    'description' => $faker->paragraph(),
                    'salary' => rand(12000, 45000).' BDT',
                    'location' => $faker->city(),
                    'type' => ['Full Time', 'Part Time', 'Contract'][$i % 3],
                    'contact' => sprintf('0193000%04d', $i + 1),
                ]
            ));
        }

        for ($i = 0; $i < 10; $i++) {
            JobApplication::query()->updateOrCreate(
                [
                    'job_post_id' => $jobs[$i]->id,
                    'user_id' => $users[10 + ($i % 10)]->id,
                ],
                [
                    'cv' => 'https://example.com/cv/demo-user-'.($i + 1).'.pdf',
                    'cover_letter' => 'I am interested in this opportunity. #'.($i + 1),
                ]
            );
        }

        for ($i = 0; $i < 10; $i++) {
            Property::query()->updateOrCreate(
                ['title' => 'Demo Property '.($i + 1)],
                [
                    'user_id' => $users[$i]->id,
                    'type' => ['Flat Rent', 'Land Sale', 'Shop Rent'][$i % 3],
                    'price' => rand(500000, 5000000),
                    'location' => $faker->city(),
                    'description' => $faker->sentence(15),
                    'contact' => sprintf('0164000%04d', $i + 1),
                ]
            );
        }

        for ($i = 0; $i < 10; $i++) {
            News::query()->updateOrCreate(
                ['title' => 'Demo District News '.($i + 1)],
                [
                    'image' => 'https://picsum.photos/seed/news'.($i + 1).'/800/500',
                    'content' => $faker->paragraphs(3, true),
                    'author' => 'News Desk '.($i + 1),
                ]
            );

            Notice::query()->updateOrCreate(
                ['title' => 'Demo Notice '.($i + 1)],
                [
                    'description' => $faker->sentence(14),
                    'category' => ['Government', 'Education', 'Community'][$i % 3],
                ]
            );
        }

        $extraEmergency = [
            ['name' => 'District Control Room', 'phone' => '01320000001', 'category' => 'Administration'],
            ['name' => 'Hospital Help Desk', 'phone' => '01320000002', 'category' => 'Hospital'],
            ['name' => 'Road Accident Helpline', 'phone' => '01320000003', 'category' => 'Accident'],
            ['name' => 'Disaster Hotline', 'phone' => '01320000004', 'category' => 'Disaster'],
            ['name' => 'City Emergency Cell', 'phone' => '01320000005', 'category' => 'City Service'],
        ];

        foreach ($extraEmergency as $contact) {
            EmergencyContact::query()->firstOrCreate($contact);
        }

        for ($i = 0; $i < 10; $i++) {
            Message::query()->updateOrCreate(
                [
                    'sender_id' => $users[$i]->id,
                    'receiver_id' => $users[10 + ($i % 10)]->id,
                    'message' => 'Demo chat message '.($i + 1),
                ],
                [
                    'image' => null,
                    'seen' => (bool) ($i % 2),
                ]
            );

            Review::query()->updateOrCreate(
                [
                    'user_id' => $users[$i]->id,
                    'target_id' => ($i % 10) + 1,
                    'type' => ['worker', 'business', 'item'][$i % 3],
                ],
                [
                    'rating' => rand(3, 5),
                    'comment' => 'Demo review comment '.($i + 1),
                ]
            );

            Report::query()->updateOrCreate(
                [
                    'reporter_id' => $users[$i]->id,
                    'target_type' => ['user', 'item', 'worker'][$i % 3],
                    'target_id' => ($i % 10) + 1,
                ],
                [
                    'reason' => 'Demo report reason '.($i + 1),
                    'status' => ['pending', 'reviewed', 'resolved'][$i % 3],
                ]
            );

            Payment::query()->updateOrCreate(
                [
                    'transaction_id' => 'DEMO-TXN-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                ],
                [
                    'user_id' => $users[$i]->id,
                    'amount' => rand(100, 1500),
                    'method' => ['cash', 'card', 'rocket'][$i % 3],
                    'status' => ['pending', 'success', 'failed'][$i % 3],
                ]
            );
        }
    }
}