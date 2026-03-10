<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('section', 80);
            $table->string('target_type', 80);
            $table->unsignedBigInteger('target_id');
            $table->string('file_path');
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('alt_text')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index(['section', 'target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};

