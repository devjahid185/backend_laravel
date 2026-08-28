<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_order_support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('food_order_id')->constrained('food_orders')->cascadeOnDelete();
            $table->string('subject', 160);
            $table->text('message');
            $table->string('status', 30)->default('open');
            $table->text('admin_reply')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'food_order_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_order_support_tickets');
    }
};
