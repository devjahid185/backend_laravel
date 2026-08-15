<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('phone', 40)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('whatsapp', 40)->nullable();
            $table->string('availability', 120)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_settings');
    }
};
