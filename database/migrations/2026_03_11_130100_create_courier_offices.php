<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('courier_offices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('courier_companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 160);
            $table->string('district', 80)->nullable();
            $table->string('upazila', 80)->nullable();
            $table->string('address', 255)->nullable();
            $table->json('phones')->nullable();
            $table->string('hotline', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('facebook', 255)->nullable();
            $table->json('services')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->decimal('rating', 4, 2)->default(0);
            $table->string('status', 30)->default('active');
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'district']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_offices');
    }
};
