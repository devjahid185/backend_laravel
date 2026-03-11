<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('teacher_categories')->nullOnDelete();
            $table->string('name', 120);
            $table->string('title', 120)->nullable();
            $table->json('subjects')->nullable();
            $table->json('class_levels')->nullable();
            $table->string('medium', 60)->nullable();
            $table->string('gender', 20)->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->string('education', 180)->nullable();
            $table->string('institute', 160)->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->decimal('monthly_rate', 10, 2)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('district', 80)->nullable();
            $table->string('upazila', 80)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('preferred_area', 255)->nullable();
            $table->string('mode', 40)->nullable();
            $table->text('availability')->nullable();
            $table->text('about')->nullable();
            $table->boolean('is_available')->default(true);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('status', 30)->default('active');
            $table->unsignedInteger('views')->default(0);
            $table->decimal('rating', 4, 2)->default(0);
            $table->timestamps();

            $table->index(['category_id', 'district']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
