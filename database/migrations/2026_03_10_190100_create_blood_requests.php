<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blood_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('patient_name')->nullable();
            $table->string('blood_group', 5);
            $table->unsignedTinyInteger('units')->default(1);
            $table->date('needed_at')->nullable();
            $table->string('hospital')->nullable();
            $table->string('location')->nullable();
            $table->string('district')->nullable();
            $table->string('upazila')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();
            $table->index(['blood_group', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blood_requests');
    }
};
