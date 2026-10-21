<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_delivery_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('food_delivery_settings', 'checkout_image_notice_enabled')) {
                $table->boolean('checkout_image_notice_enabled')->default(true)->after('store_lng');
            }
            if (! Schema::hasColumn('food_delivery_settings', 'checkout_image_notice_text')) {
                $table->text('checkout_image_notice_text')->nullable()->after('checkout_image_notice_enabled');
            }
        });

        DB::table('food_delivery_settings')->where('id', 1)->update([
            'checkout_image_notice_enabled' => true,
            'checkout_image_notice_text' => 'খাবারের ছবিগুলো অনেক ক্ষেত্রে AI/ইলাস্ট্রেশন হিসেবে ব্যবহার করা হয়েছে। রেস্টুরেন্টের প্রস্তুত খাবার ছবির সাথে সামান্য ভিন্ন হতে পারে। অর্ডার নিশ্চিত করলে আপনি বিষয়টি বুঝে সম্মত হচ্ছেন।',
        ]);
    }

    public function down(): void
    {
        Schema::table('food_delivery_settings', function (Blueprint $table): void {
            foreach (['checkout_image_notice_text', 'checkout_image_notice_enabled'] as $column) {
                if (Schema::hasColumn('food_delivery_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
