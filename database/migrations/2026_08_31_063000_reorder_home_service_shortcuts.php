<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('home_service_shortcuts')) {
            return;
        }

        $now = now();
        DB::table('home_service_shortcuts')->updateOrInsert(
            ['endpoint' => '/medicine'],
            [
                'title' => 'মেডিসিন ডেলিভারি',
                'subtitle' => 'ঔষধ অর্ডার করুন',
                'icon' => 'medicine',
                'accent_color' => '#087464',
                'sort_order' => 20,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        foreach ($this->orderedEndpoints() as $endpoint => $sortOrder) {
            DB::table('home_service_shortcuts')
                ->where('endpoint', $endpoint)
                ->update([
                    'sort_order' => $sortOrder,
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('home_service_shortcuts')) {
            return;
        }

        DB::table('home_service_shortcuts')
            ->where('endpoint', '/items')
            ->update(['sort_order' => 20, 'updated_at' => now()]);
    }

    private function orderedEndpoints(): array
    {
        return [
            '/food/home' => 10,
            '/medicine' => 20,
            '/items' => 30,
            '/workers' => 40,
            '/businesses' => 50,
            '/jobs' => 60,
            '/properties' => 70,
            '/blood-donors' => 80,
            '/doctors' => 90,
            '/hospitals' => 100,
            '/hotels' => 110,
            '/restaurants' => 120,
            '/education' => 130,
            '/teachers' => 140,
            '/electricity/offices' => 150,
            '/car-rentals' => 160,
            '/launches' => 170,
            '/couriers/companies' => 180,
            '/emergency' => 190,
            '/news' => 200,
            '/notices' => 210,
        ];
    }
};
