<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_visit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 40)->default('flutter');
            $table->string('path', 120)->nullable();
            $table->string('session_key', 120)->nullable();
            $table->string('ip_hash', 80)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('visited_at')->useCurrent();
            $table->timestamps();

            $table->index(['visited_at', 'source']);
            $table->index(['user_id', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_visit_logs');
    }
};
