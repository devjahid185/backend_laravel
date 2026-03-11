<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('electricity_offices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 160);
            $table->string('provider', 80)->nullable();
            $table->string('office_type', 120)->nullable();
            $table->string('district', 80)->nullable();
            $table->string('upazila', 80)->nullable();
            $table->string('address', 255)->nullable();
            $table->json('phones')->nullable();
            $table->string('hotline', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('website', 255)->nullable();
            $table->text('notes')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('status', 30)->default('active');
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();

            $table->index(['provider', 'district']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electricity_offices');
    }
};
