<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->constrained('doctor_categories')->restrictOnDelete();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('specialization')->nullable();
            $table->string('hospital')->nullable();
            $table->string('clinic')->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->string('degrees')->nullable();
            $table->string('bmdc_number')->nullable();
            $table->decimal('fees', 12, 2)->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('district')->nullable();
            $table->string('upazila')->nullable();
            $table->string('address')->nullable();
            $table->string('chamber_time')->nullable();
            $table->text('about')->nullable();
            $table->boolean('is_available')->default(true);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
