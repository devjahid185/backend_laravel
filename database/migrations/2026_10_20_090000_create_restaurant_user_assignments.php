<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('access_role', 30)->default('manager');
            $table->timestamps();

            $table->unique(['restaurant_id', 'user_id']);
            $table->index(['user_id', 'restaurant_id']);
        });

        DB::table('restaurants')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->select(['id', 'user_id'])
            ->chunkById(500, function ($restaurants): void {
                $now = now();
                DB::table('restaurant_user')->insertOrIgnore(
                    $restaurants->map(fn ($restaurant) => [
                        'restaurant_id' => $restaurant->id,
                        'user_id' => $restaurant->user_id,
                        'access_role' => 'owner',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });

        Schema::create('food_order_owner_alert_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('food_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('seen_at');
            $table->timestamps();

            $table->unique(['food_order_id', 'user_id']);
            $table->index(['user_id', 'seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_order_owner_alert_reads');
        Schema::dropIfExists('restaurant_user');
    }
};
