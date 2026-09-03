<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_payment_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('medicine_payment_settings', 'bkash_tokenized_enabled')) {
                $table->boolean('bkash_tokenized_enabled')->default(false)->after('online_enabled');
            }
            if (! Schema::hasColumn('medicine_payment_settings', 'bkash_tokenized_sandbox')) {
                $table->boolean('bkash_tokenized_sandbox')->default(true)->after('bkash_tokenized_enabled');
            }
            if (! Schema::hasColumn('medicine_payment_settings', 'bkash_tokenized_title')) {
                $table->string('bkash_tokenized_title', 80)->default('bKash Checkout')->after('online_title');
            }
            if (! Schema::hasColumn('medicine_payment_settings', 'bkash_tokenized_base_url')) {
                $table->string('bkash_tokenized_base_url')->nullable()->after('nagad_number');
            }
            if (! Schema::hasColumn('medicine_payment_settings', 'bkash_tokenized_callback_url')) {
                $table->string('bkash_tokenized_callback_url')->nullable()->after('bkash_tokenized_base_url');
            }
            if (! Schema::hasColumn('medicine_payment_settings', 'bkash_tokenized_app_key')) {
                $table->text('bkash_tokenized_app_key')->nullable()->after('bkash_tokenized_callback_url');
            }
            if (! Schema::hasColumn('medicine_payment_settings', 'bkash_tokenized_app_secret')) {
                $table->text('bkash_tokenized_app_secret')->nullable()->after('bkash_tokenized_app_key');
            }
            if (! Schema::hasColumn('medicine_payment_settings', 'bkash_tokenized_username')) {
                $table->text('bkash_tokenized_username')->nullable()->after('bkash_tokenized_app_secret');
            }
            if (! Schema::hasColumn('medicine_payment_settings', 'bkash_tokenized_password')) {
                $table->text('bkash_tokenized_password')->nullable()->after('bkash_tokenized_username');
            }
            if (! Schema::hasColumn('medicine_payment_settings', 'bkash_tokenized_instructions')) {
                $table->text('bkash_tokenized_instructions')->nullable()->after('online_instructions');
            }
        });

        Schema::table('medicine_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('medicine_orders', 'bkash_payment_id')) {
                $table->string('bkash_payment_id')->nullable()->after('manual_transaction_id')->index();
            }
            if (! Schema::hasColumn('medicine_orders', 'bkash_trx_id')) {
                $table->string('bkash_trx_id')->nullable()->after('bkash_payment_id')->index();
            }
            if (! Schema::hasColumn('medicine_orders', 'bkash_url')) {
                $table->text('bkash_url')->nullable()->after('bkash_trx_id');
            }
            if (! Schema::hasColumn('medicine_orders', 'bkash_status')) {
                $table->string('bkash_status', 40)->nullable()->after('bkash_url')->index();
            }
            if (! Schema::hasColumn('medicine_orders', 'bkash_raw')) {
                $table->json('bkash_raw')->nullable()->after('bkash_status');
            }
            if (! Schema::hasColumn('medicine_orders', 'bkash_paid_at')) {
                $table->timestamp('bkash_paid_at')->nullable()->after('bkash_raw');
            }
        });
    }

    public function down(): void
    {
        Schema::table('medicine_orders', function (Blueprint $table): void {
            foreach (['bkash_paid_at', 'bkash_raw', 'bkash_status', 'bkash_url', 'bkash_trx_id', 'bkash_payment_id'] as $column) {
                if (Schema::hasColumn('medicine_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('medicine_payment_settings', function (Blueprint $table): void {
            foreach ([
                'bkash_tokenized_instructions',
                'bkash_tokenized_password',
                'bkash_tokenized_username',
                'bkash_tokenized_app_secret',
                'bkash_tokenized_app_key',
                'bkash_tokenized_callback_url',
                'bkash_tokenized_base_url',
                'bkash_tokenized_title',
                'bkash_tokenized_sandbox',
                'bkash_tokenized_enabled',
            ] as $column) {
                if (Schema::hasColumn('medicine_payment_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
