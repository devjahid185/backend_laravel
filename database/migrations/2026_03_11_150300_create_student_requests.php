<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('teacher_categories')->nullOnDelete();
            $table->string('title', 160);
            $table->string('class_level', 80)->nullable();
            $table->string('medium', 60)->nullable();
            $table->string('mode', 40)->nullable();
            $table->string('days_per_week', 40)->nullable();
            $table->decimal('fee', 10, 2)->nullable();
            $table->string('district', 80)->nullable();
            $table->string('upazila', 80)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('open');
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();

            $table->index(['category_id', 'district']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_requests');
    }
};
