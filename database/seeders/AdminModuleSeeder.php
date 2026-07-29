<?php

namespace Database\Seeders;

use App\Models\AdminModule;
use Illuminate\Database\Seeder;

class AdminModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            ['name' => 'Dashboard', 'slug' => 'dashboard', 'group_name' => 'Core', 'route' => '/admin'],
            ['name' => 'Profile', 'slug' => 'profile', 'group_name' => 'Core', 'route' => '/admin/profile'],
            ['name' => 'Users', 'slug' => 'users', 'group_name' => 'Core', 'route' => '/admin/users'],
            ['name' => 'Workers', 'slug' => 'workers', 'group_name' => 'Services', 'route' => '/admin/workers'],
            ['name' => 'Businesses', 'slug' => 'businesses', 'group_name' => 'Services', 'route' => '/admin/businesses'],
            ['name' => 'Marketplace', 'slug' => 'marketplace', 'group_name' => 'Services', 'route' => '/admin/marketplace'],
            ['name' => 'Jobs', 'slug' => 'jobs', 'group_name' => 'Services', 'route' => '/admin/jobs'],
            ['name' => 'Doctors', 'slug' => 'doctors', 'group_name' => 'Services', 'route' => '/admin/doctors'],
            ['name' => 'Hospitals', 'slug' => 'hospitals', 'group_name' => 'Services', 'route' => '/admin/hospitals'],
            ['name' => 'Hotels', 'slug' => 'hotels', 'group_name' => 'Services', 'route' => '/admin/hotels'],
            ['name' => 'Restaurants', 'slug' => 'restaurants', 'group_name' => 'Services', 'route' => '/admin/restaurants'],
            ['name' => 'Property', 'slug' => 'property', 'group_name' => 'Services', 'route' => '/admin/property'],
            ['name' => 'Education', 'slug' => 'education', 'group_name' => 'Services', 'route' => '/admin/education'],
            ['name' => 'Blood Donation', 'slug' => 'blood', 'group_name' => 'Services', 'route' => '/admin/blood'],
            ['name' => 'Courier', 'slug' => 'courier', 'group_name' => 'Services', 'route' => '/admin/courier'],
            ['name' => 'Car Rental', 'slug' => 'car-rental', 'group_name' => 'Services', 'route' => '/admin/car-rental'],
            ['name' => 'Launch Services', 'slug' => 'launches', 'group_name' => 'Services', 'route' => '/admin/launches'],
            ['name' => 'Electricity Office', 'slug' => 'electricity', 'group_name' => 'Services', 'route' => '/admin/electricity'],
            ['name' => 'Emergency', 'slug' => 'emergency', 'group_name' => 'Content', 'route' => '/admin/emergency'],
            ['name' => 'News', 'slug' => 'news', 'group_name' => 'Content', 'route' => '/admin/news'],
            ['name' => 'Notices', 'slug' => 'notices', 'group_name' => 'Content', 'route' => '/admin/notices'],
            ['name' => 'Updates', 'slug' => 'updates', 'group_name' => 'Content', 'route' => '/admin/updates'],
            ['name' => 'FAQs', 'slug' => 'faqs', 'group_name' => 'Content', 'route' => '/admin/faqs'],
            ['name' => 'Notifications', 'slug' => 'notifications', 'group_name' => 'Engagement', 'route' => '/admin/notifications'],
            ['name' => 'Reviews', 'slug' => 'reviews', 'group_name' => 'Moderation', 'route' => '/admin/reviews'],
            ['name' => 'Reports', 'slug' => 'reports', 'group_name' => 'Moderation', 'route' => '/admin/reports'],
            ['name' => 'Messages', 'slug' => 'messages', 'group_name' => 'Moderation', 'route' => '/admin/messages'],
            ['name' => 'Payments', 'slug' => 'payments', 'group_name' => 'Finance', 'route' => '/admin/payments'],
            ['name' => 'SMS Settings', 'slug' => 'sms-settings', 'group_name' => 'System', 'route' => '/admin/sms-settings'],
            ['name' => 'Email Settings', 'slug' => 'email-settings', 'group_name' => 'System', 'route' => '/admin/email-settings'],
            ['name' => 'Settings', 'slug' => 'settings', 'group_name' => 'System', 'route' => '/admin/settings'],
        ];

        foreach ($modules as $index => $module) {
            AdminModule::query()->updateOrCreate(
                ['slug' => $module['slug']],
                [
                    ...$module,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
