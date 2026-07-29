<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('launches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 160);
            $table->string('operator_name', 160)->nullable();
            $table->string('route_from', 120)->nullable();
            $table->string('route_to', 120)->nullable();
            $table->string('departure_terminal', 180)->nullable();
            $table->string('arrival_terminal', 180)->nullable();
            $table->time('departure_time')->nullable();
            $table->time('arrival_time')->nullable();
            $table->string('running_days', 180)->nullable();
            $table->decimal('deck_fare', 10, 2)->nullable();
            $table->decimal('chair_fare', 10, 2)->nullable();
            $table->decimal('single_cabin_fare', 10, 2)->nullable();
            $table->decimal('double_cabin_fare', 10, 2)->nullable();
            $table->boolean('has_cabin')->default(false);
            $table->boolean('has_ac')->default(false);
            $table->boolean('has_food')->default(false);
            $table->boolean('online_booking')->default(false);
            $table->json('phones')->nullable();
            $table->string('hotline', 60)->nullable();
            $table->string('website', 180)->nullable();
            $table->string('district', 80)->nullable();
            $table->string('upazila', 80)->nullable();
            $table->string('address', 255)->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 40)->default('active');
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();
            $table->index(['status', 'route_from', 'route_to']);
            $table->index(['district', 'upazila']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('launches');
    }
};