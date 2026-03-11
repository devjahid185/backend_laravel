<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('courier_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 160)->unique();
            $table->string('website', 255)->nullable();
            $table->string('facebook', 255)->nullable();
            $table->string('hotline', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->decimal('rating', 4, 2)->default(0);
            $table->string('status', 30)->default('active');
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_companies');
    }
};
