<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->string('mailer')->default('smtp');
            $table->string('host')->nullable();
            $table->unsignedInteger('port')->default(587);
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('encryption')->nullable();
            $table->string('from_address')->nullable();
            $table->string('from_name')->default('Bholabashi');
            $table->unsignedInteger('timeout')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->text('last_test_result')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_settings');
    }
};
