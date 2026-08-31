<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_payment_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('cod_enabled')->default(true);
            $table->boolean('manual_bkash_enabled')->default(false);
            $table->boolean('manual_nagad_enabled')->default(false);
            $table->boolean('online_enabled')->default(false);
            $table->boolean('require_manual_payment_proof')->default(false);
            $table->string('cod_title', 80)->default('Cash on Delivery');
            $table->string('manual_bkash_title', 80)->default('Manual bKash');
            $table->string('manual_nagad_title', 80)->default('Manual Nagad');
            $table->string('online_title', 80)->default('Online Payment');
            $table->string('bkash_number', 40)->nullable();
            $table->string('nagad_number', 40)->nullable();
            $table->text('cod_instructions')->nullable();
            $table->text('manual_bkash_instructions')->nullable();
            $table->text('manual_nagad_instructions')->nullable();
            $table->text('online_instructions')->nullable();
            $table->text('payment_notice')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_payment_settings');
    }
};
