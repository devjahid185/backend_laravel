<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\UpdatesController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/services', function () {
    return view('services');
});

Route::get('/market', function () {
    return view('market');
});

Route::get('/jobs', function () {
    return view('jobs');
});

Route::get('/doctors', function () {
    return view('doctors');
});

Route::get('/hospitals', function () {
    return view('hospitals');
});

Route::get('/hotels', function () {
    return view('hotels');
});

Route::get('/restaurants', function () {
    return view('restaurants');
});

Route::get('/property', function () {
    return view('property');
});

Route::get('/education', function () {
    return view('education');
});

Route::get('/updates', [UpdatesController::class, 'index']);
Route::get('/updates/{slug}', [UpdatesController::class, 'show']);

Route::get('/privacy', function () {
    return view('privacy');
});

Route::get('/terms', function () {
    return view('terms');
});

Route::get('/support', function () {
    return view('support');
});

Route::get('/about', function () {
    return view('about');
});

Route::get('/sitemap.xml', function () {
    $postSlugs = \App\Models\UpdatePost::query()
        ->where('is_published', true)
        ->orderByDesc('published_at')
        ->pluck('slug')
        ->all();

    $pages = [
        '/',
        '/services',
        '/market',
        '/jobs',
        '/doctors',
        '/hospitals',
        '/hotels',
        '/restaurants',
        '/property',
        '/education',
        '/updates',
        ...array_map(fn ($slug) => '/updates/' . $slug, $postSlugs),
        '/privacy',
        '/terms',
        '/support',
        '/about',
    ];

    $base = url('/');
    $date = now()->toDateString();

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

    foreach ($pages as $path) {
        $xml .= '  <url>' . PHP_EOL;
        $xml .= '    <loc>' . $base . $path . '</loc>' . PHP_EOL;
        $xml .= '    <lastmod>' . $date . '</lastmod>' . PHP_EOL;
        $xml .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
        $xml .= '    <priority>0.8</priority>' . PHP_EOL;
        $xml .= '  </url>' . PHP_EOL;
    }

    $xml .= '</urlset>' . PHP_EOL;

    return response($xml, 200)->header('Content-Type', 'application/xml');
});
