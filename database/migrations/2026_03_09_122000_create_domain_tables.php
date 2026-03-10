<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('worker_categories')->restrictOnDelete();
            $table->integer('experience')->default(0);
            $table->decimal('hourly_price', 10, 2)->default(0);
            $table->text('skills')->nullable();
            $table->string('service_area')->nullable();
            $table->boolean('availability')->default(true);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
            $table->unique('user_id');
        });

        Schema::create('service_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained('workers')->cascadeOnDelete();
            $table->date('service_date');
            $table->time('service_time');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });

        Schema::create('business_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('category_id')->constrained('business_categories')->restrictOnDelete();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('opening_hours')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('website')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('marketplace_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('marketplace_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('marketplace_categories')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('location')->nullable();
            $table->enum('status', ['active', 'sold', 'blocked'])->default('active');
            $table->timestamps();
        });

        Schema::create('marketplace_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('marketplace_items')->cascadeOnDelete();
            $table->string('image');
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->string('image')->nullable();
            $table->boolean('seen')->default(false);
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('target_id');
            $table->enum('type', ['worker', 'business', 'item']);
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->index(['type', 'target_id']);
        });

        Schema::create('blood_donors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('blood_group', 5);
            $table->date('last_donation')->nullable();
            $table->boolean('available')->default(true);
            $table->string('location')->nullable();
            $table->timestamps();
            $table->unique('user_id');
        });

        Schema::create('job_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('company');
            $table->text('description');
            $table->string('salary')->nullable();
            $table->string('location')->nullable();
            $table->string('type')->nullable();
            $table->string('contact')->nullable();
            $table->timestamps();
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('cv')->nullable();
            $table->text('cover_letter')->nullable();
            $table->timestamps();
            $table->unique(['job_post_id', 'user_id']);
        });

        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('type');
            $table->decimal('price', 12, 2)->default(0);
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('contact')->nullable();
            $table->timestamps();
        });

        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image')->nullable();
            $table->longText('content');
            $table->string('author')->nullable();
            $table->timestamps();
        });

        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('category')->nullable();
            $table->timestamps();
        });

        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('category');
            $table->timestamps();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('target_type');
            $table->unsignedBigInteger('target_id');
            $table->text('reason');
            $table->enum('status', ['pending', 'reviewed', 'resolved', 'rejected'])->default('pending');
            $table->timestamps();
            $table->index(['target_type', 'target_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('method', ['bkash', 'nagad', 'rocket', 'card', 'cash']);
            $table->string('transaction_id')->unique();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('emergency_contacts');
        Schema::dropIfExists('notices');
        Schema::dropIfExists('news');
        Schema::dropIfExists('properties');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_posts');
        Schema::dropIfExists('blood_donors');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('marketplace_images');
        Schema::dropIfExists('marketplace_items');
        Schema::dropIfExists('marketplace_categories');
        Schema::dropIfExists('businesses');
        Schema::dropIfExists('business_categories');
        Schema::dropIfExists('service_bookings');
        Schema::dropIfExists('workers');
        Schema::dropIfExists('worker_categories');
    }
};
