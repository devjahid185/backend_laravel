<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            JobCategorySeeder::class,
            PropertyCategorySeeder::class,
            DoctorCategorySeeder::class,
            TeacherCategorySeeder::class,
            HospitalCategorySeeder::class,
            HospitalSeeder::class,
            CarCategorySeeder::class,
            CourierSeeder::class,
            ElectricityOfficeSeeder::class,
            DomainSeeder::class,
            DemoContentSeeder::class,
        ]);
    }
}
