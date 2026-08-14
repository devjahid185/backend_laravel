<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_service_shortcuts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('endpoint')->unique();
            $table->string('icon')->nullable();
            $table->string('accent_color', 24)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        $now = now();
        DB::table('home_service_shortcuts')->insert([
            ['title' => 'ফুড ডেলিভারি', 'subtitle' => 'রেস্টুরেন্ট থেকে খাবার', 'endpoint' => '/food/home', 'icon' => 'delivery', 'accent_color' => '#E91E63', 'sort_order' => 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'মার্কেটপ্লেস', 'subtitle' => 'বাই-সেল পণ্য', 'endpoint' => '/items', 'icon' => 'shop', 'accent_color' => '#F97316', 'sort_order' => 20, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'কর্মী খুঁজুন', 'subtitle' => 'লোকাল সার্ভিস', 'endpoint' => '/workers', 'icon' => 'worker', 'accent_color' => '#0EA5E9', 'sort_order' => 30, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'ব্যবসা ডিরেক্টরি', 'subtitle' => 'লোকাল দোকান', 'endpoint' => '/businesses', 'icon' => 'business', 'accent_color' => '#10B981', 'sort_order' => 40, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'চাকরি', 'subtitle' => 'লোকাল জব', 'endpoint' => '/jobs', 'icon' => 'work', 'accent_color' => '#6366F1', 'sort_order' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'প্রোপার্টি', 'subtitle' => 'ভাড়া ও বিক্রয়', 'endpoint' => '/properties', 'icon' => 'property', 'accent_color' => '#14B8A6', 'sort_order' => 60, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'রক্তদাতা', 'subtitle' => 'জরুরি ডোনার', 'endpoint' => '/blood-donors', 'icon' => 'blood', 'accent_color' => '#EF4444', 'sort_order' => 70, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'ডাক্তার', 'subtitle' => 'ডাক্তার খুঁজুন', 'endpoint' => '/doctors', 'icon' => 'doctor', 'accent_color' => '#06B6D4', 'sort_order' => 80, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'হাসপাতাল', 'subtitle' => 'ক্লিনিক ও হাসপাতাল', 'endpoint' => '/hospitals', 'icon' => 'hospital', 'accent_color' => '#22C55E', 'sort_order' => 90, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'হোটেল', 'subtitle' => 'হোটেল ও গেস্ট হাউস', 'endpoint' => '/hotels', 'icon' => 'hotel', 'accent_color' => '#A855F7', 'sort_order' => 100, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'রেস্টুরেন্ট', 'subtitle' => 'খাবার ও রেস্টুরেন্ট', 'endpoint' => '/restaurants', 'icon' => 'restaurant', 'accent_color' => '#F59E0B', 'sort_order' => 110, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'শিক্ষা প্রতিষ্ঠান', 'subtitle' => 'স্কুল কলেজ', 'endpoint' => '/education', 'icon' => 'education', 'accent_color' => '#3B82F6', 'sort_order' => 120, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'শিক্ষক/টিউটর', 'subtitle' => 'টিউশন ও কোচিং', 'endpoint' => '/teachers', 'icon' => 'teacher', 'accent_color' => '#8B5CF6', 'sort_order' => 130, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'বিদ্যুৎ অফিস', 'subtitle' => 'পল্লী বিদ্যুৎ', 'endpoint' => '/electricity/offices', 'icon' => 'electricity', 'accent_color' => '#84CC16', 'sort_order' => 140, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'গাড়ি ভাড়া', 'subtitle' => 'যাত্রী/পণ্য পরিবহন', 'endpoint' => '/car-rentals', 'icon' => 'car', 'accent_color' => '#64748B', 'sort_order' => 150, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'লঞ্চ সার্ভিস', 'subtitle' => 'সময় ও রুট', 'endpoint' => '/launches', 'icon' => 'launch', 'accent_color' => '#0284C7', 'sort_order' => 160, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'কুরিয়ার', 'subtitle' => 'কুরিয়ার সার্ভিস', 'endpoint' => '/couriers/companies', 'icon' => 'courier', 'accent_color' => '#DB2777', 'sort_order' => 170, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'জরুরি নম্বর', 'subtitle' => 'পুলিশ ফায়ার অ্যাম্বুলেন্স', 'endpoint' => '/emergency', 'icon' => 'emergency', 'accent_color' => '#DC2626', 'sort_order' => 180, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'সংবাদ', 'subtitle' => 'জেলার আপডেট', 'endpoint' => '/news', 'icon' => 'news', 'accent_color' => '#0F766E', 'sort_order' => 190, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'নোটিশ', 'subtitle' => 'গুরুত্বপূর্ণ ঘোষণা', 'endpoint' => '/notices', 'icon' => 'notice', 'accent_color' => '#EA580C', 'sort_order' => 200, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('home_service_shortcuts');
    }
};
