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
            if (! Schema::hasColumn('food_delivery_settings', 'user_manual_video_enabled')) {
                $table->boolean('user_manual_video_enabled')->default(false)->after('messenger_url');
            }
            if (! Schema::hasColumn('food_delivery_settings', 'user_manual_video_path')) {
                $table->string('user_manual_video_path', 1000)->nullable()->after('user_manual_video_enabled');
            }
            if (! Schema::hasColumn('food_delivery_settings', 'user_manual_video_title')) {
                $table->string('user_manual_video_title', 200)->nullable()->after('user_manual_video_path');
            }
            if (! Schema::hasColumn('food_delivery_settings', 'user_manual_video_description')) {
                $table->text('user_manual_video_description')->nullable()->after('user_manual_video_title');
            }
        });

        DB::table('food_delivery_settings')->where('id', 1)->update([
            'user_manual_video_enabled' => false,
            'user_manual_video_title' => 'কীভাবে খাবার অর্ডার করবেন',
            'user_manual_video_description' => 'ভোলাবাসী অ্যাপে খুব সহজে খাবার অর্ডার করার পুরো নিয়ম এই ভিডিওতে দেখুন।',
        ]);
    }

    public function down(): void
    {
        Schema::table('food_delivery_settings', function (Blueprint $table): void {
            foreach (['user_manual_video_description', 'user_manual_video_title', 'user_manual_video_path', 'user_manual_video_enabled'] as $column) {
                if (Schema::hasColumn('food_delivery_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
