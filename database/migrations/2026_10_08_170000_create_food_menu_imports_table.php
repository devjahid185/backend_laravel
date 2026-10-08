<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_menu_imports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('uploaded');
            $table->json('source_images')->nullable();
            $table->json('extracted_items')->nullable();
            $table->json('created_food_item_ids')->nullable();
            $table->text('notes')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('extracted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_menu_imports');
    }
};
