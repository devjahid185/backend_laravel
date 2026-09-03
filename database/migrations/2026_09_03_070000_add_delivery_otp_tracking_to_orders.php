<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('food_orders', 'delivery_otp_sent_at')) {
                $table->timestamp('delivery_otp_sent_at')->nullable()->after('delivery_otp');
            }
            if (! Schema::hasColumn('food_orders', 'delivery_otp_expires_at')) {
                $table->timestamp('delivery_otp_expires_at')->nullable()->after('delivery_otp_sent_at');
            }
            if (! Schema::hasColumn('food_orders', 'delivery_otp_send_failed_at')) {
                $table->timestamp('delivery_otp_send_failed_at')->nullable()->after('delivery_otp_expires_at');
            }
            if (! Schema::hasColumn('food_orders', 'delivery_otp_send_error')) {
                $table->string('delivery_otp_send_error')->nullable()->after('delivery_otp_send_failed_at');
            }
        });

        Schema::table('medicine_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('medicine_orders', 'delivery_otp')) {
                $table->string('delivery_otp', 12)->nullable()->after('delivery_person_phone');
            }
            if (! Schema::hasColumn('medicine_orders', 'delivery_otp_sent_at')) {
                $table->timestamp('delivery_otp_sent_at')->nullable()->after('delivery_otp');
            }
            if (! Schema::hasColumn('medicine_orders', 'delivery_otp_expires_at')) {
                $table->timestamp('delivery_otp_expires_at')->nullable()->after('delivery_otp_sent_at');
            }
            if (! Schema::hasColumn('medicine_orders', 'delivery_otp_send_failed_at')) {
                $table->timestamp('delivery_otp_send_failed_at')->nullable()->after('delivery_otp_expires_at');
            }
            if (! Schema::hasColumn('medicine_orders', 'delivery_otp_send_error')) {
                $table->string('delivery_otp_send_error')->nullable()->after('delivery_otp_send_failed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('food_orders', function (Blueprint $table): void {
            foreach (['delivery_otp_send_error', 'delivery_otp_send_failed_at', 'delivery_otp_expires_at', 'delivery_otp_sent_at'] as $column) {
                if (Schema::hasColumn('food_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('medicine_orders', function (Blueprint $table): void {
            foreach (['delivery_otp_send_error', 'delivery_otp_send_failed_at', 'delivery_otp_expires_at', 'delivery_otp_sent_at', 'delivery_otp'] as $column) {
                if (Schema::hasColumn('medicine_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
