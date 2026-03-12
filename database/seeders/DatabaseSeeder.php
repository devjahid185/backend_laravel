<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            JobCategorySeeder::class,
            PropertyCategorySeeder::class,
            DoctorCategorySeeder::class,
            TeacherCategorySeeder::class,
            HospitalCategorySeeder::class,
            HospitalSeeder::class,
            HotelCategorySeeder::class,
            HotelSeeder::class,
            RestaurantCategorySeeder::class,
            RestaurantSeeder::class,
            EducationCategorySeeder::class,
            EducationSeeder::class,
            UpdatePostSeeder::class,
        ]);
    }
}
