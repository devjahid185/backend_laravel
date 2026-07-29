<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('phone_otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->string('code');
            $table->string('purpose');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->index(['phone', 'purpose']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_otps');
    }
};
