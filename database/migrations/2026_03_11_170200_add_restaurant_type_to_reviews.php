<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE reviews MODIFY COLUMN type ENUM('worker','business','item','hospital','car_rental','courier','teacher','hotel','restaurant') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE reviews MODIFY COLUMN type ENUM('worker','business','item','hospital','car_rental','courier','teacher','hotel') NOT NULL");
    }
};
