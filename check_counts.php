<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$counts = [
    'workers' => App\Models\Worker::count(),
    'service_bookings' => App\Models\ServiceBooking::count(),
    'businesses' => App\Models\Business::count(),
    'marketplace_items' => App\Models\MarketplaceItem::count(),
    'marketplace_images' => App\Models\MarketplaceImage::count(),
    'blood_donors' => App\Models\BloodDonor::count(),
    'job_posts' => App\Models\JobPost::count(),
    'job_applications' => App\Models\JobApplication::count(),
    'properties' => App\Models\Property::count(),
    'news' => App\Models\News::count(),
    'notices' => App\Models\Notice::count(),
    'messages' => App\Models\Message::count(),
    'reviews' => App\Models\Review::count(),
    'reports' => App\Models\Report::count(),
    'payments' => App\Models\Payment::count(),
];

echo json_encode($counts, JSON_PRETTY_PRINT);
