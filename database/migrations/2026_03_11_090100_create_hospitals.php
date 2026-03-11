<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('hospital_categories')->nullOnDelete();
            $table->string('name', 160);
            $table->string('type', 60)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('emergency_phone', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('district', 80)->nullable();
            $table->string('upazila', 80)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('opening_hours', 120)->nullable();
            $table->unsignedInteger('bed_capacity')->nullable();
            $table->boolean('icu_available')->default(false);
            $table->boolean('emergency_available')->default(true);
            $table->boolean('ambulance_available')->default(false);
            $table->json('services')->nullable();
            $table->json('facilities')->nullable();
            $table->text('description')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('status', 30)->default('active');
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();

            $table->index(['category_id', 'district']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitals');
    }
};
