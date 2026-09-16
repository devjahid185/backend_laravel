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
            ['name' => 'Staff Management', 'slug' => 'staff-management', 'group_name' => 'Core', 'route' => '/admin/staff-management'],
            ['name' => 'Home Banners', 'slug' => 'home-banners', 'group_name' => 'Engagement', 'route' => '/admin/home-banners'],
            ['name' => 'Home Services', 'slug' => 'home-service-shortcuts', 'group_name' => 'Engagement', 'route' => '/admin/home-service-shortcuts'],
            ['name' => 'Workers', 'slug' => 'workers', 'group_name' => 'Services', 'route' => '/admin/workers'],
            ['name' => 'Businesses', 'slug' => 'businesses', 'group_name' => 'Services', 'route' => '/admin/businesses'],
            ['name' => 'Marketplace', 'slug' => 'marketplace', 'group_name' => 'Services', 'route' => '/admin/marketplace'],
            ['name' => 'Jobs', 'slug' => 'jobs', 'group_name' => 'Services', 'route' => '/admin/jobs'],
            ['name' => 'Doctors', 'slug' => 'doctors', 'group_name' => 'Services', 'route' => '/admin/doctors'],
            ['name' => 'Hospitals', 'slug' => 'hospitals', 'group_name' => 'Services', 'route' => '/admin/hospitals'],
            ['name' => 'Hotels', 'slug' => 'hotels', 'group_name' => 'Services', 'route' => '/admin/hotels'],
            ['name' => 'Restaurants', 'slug' => 'restaurants', 'group_name' => 'Services', 'route' => '/admin/restaurants'],
            ['name' => 'Food Items', 'slug' => 'food-items', 'group_name' => 'Food Delivery', 'route' => '/admin/food-items'],
            ['name' => 'Food Categories', 'slug' => 'food-categories', 'group_name' => 'Food Delivery', 'route' => '/admin/food-categories'],
            ['name' => 'Food Banners', 'slug' => 'food-banners', 'group_name' => 'Food Delivery', 'route' => '/admin/food-banners'],
            ['name' => 'Food Orders', 'slug' => 'food-orders', 'group_name' => 'Food Delivery', 'route' => '/admin/food-orders'],
            ['name' => 'Food Coupons', 'slug' => 'food-coupons', 'group_name' => 'Food Delivery', 'route' => '/admin/food-coupons'],
            ['name' => 'Food Reviews', 'slug' => 'food-reviews', 'group_name' => 'Food Delivery', 'route' => '/admin/food-reviews'],
            ['name' => 'Delivery Settings', 'slug' => 'food-delivery-settings', 'group_name' => 'Food Delivery', 'route' => '/admin/food-delivery-settings'],
            ['name' => 'Medicine Items', 'slug' => 'medicine-items', 'group_name' => 'Medicine Delivery', 'route' => '/admin/medicine-items'],
            ['name' => 'Medicine Orders', 'slug' => 'medicine-orders', 'group_name' => 'Medicine Delivery', 'route' => '/admin/medicine-orders'],
            ['name' => 'Medicine Payments', 'slug' => 'medicine-payment-settings', 'group_name' => 'Medicine Delivery', 'route' => '/admin/medicine-payment-settings'],
            ['name' => 'Rider Management', 'slug' => 'riders', 'group_name' => 'Rider System', 'route' => '/admin/riders'],
            ['name' => 'Rider Settings', 'slug' => 'rider-settings', 'group_name' => 'Rider System', 'route' => '/admin/rider-settings'],
            ['name' => 'Income Reconciliation', 'slug' => 'delivery-income', 'group_name' => 'Finance', 'route' => '/admin/delivery-income'],
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
            ['name' => 'Map Settings', 'slug' => 'map-settings', 'group_name' => 'System', 'route' => '/admin/map-settings'],
            ['name' => 'Support Settings', 'slug' => 'support-settings', 'group_name' => 'System', 'route' => '/admin/support-settings'],
            ['name' => 'App Versions', 'slug' => 'app-version-settings', 'group_name' => 'System', 'route' => '/admin/app-version-settings'],
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
