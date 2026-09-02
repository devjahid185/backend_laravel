<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rider_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_enabled')->default(true);
            $table->string('commission_title', 160)->nullable();
            $table->text('commission_description')->nullable();
            $table->string('agreement_title', 160)->nullable();
            $table->longText('agreement_terms')->nullable();
            $table->text('cash_policy')->nullable();
            $table->text('penalty_policy')->nullable();
            $table->timestamps();
        });

        Schema::table('riders', function (Blueprint $table): void {
            if (! Schema::hasColumn('riders', 'bkash_number')) {
                $table->string('bkash_number', 40)->nullable()->after('emergency_contact_phone');
            }
            if (! Schema::hasColumn('riders', 'nagad_number')) {
                $table->string('nagad_number', 40)->nullable()->after('bkash_number');
            }
            if (! Schema::hasColumn('riders', 'bank_account_name')) {
                $table->string('bank_account_name', 120)->nullable()->after('nagad_number');
            }
            if (! Schema::hasColumn('riders', 'bank_account_number')) {
                $table->string('bank_account_number', 80)->nullable()->after('bank_account_name');
            }
            if (! Schema::hasColumn('riders', 'bank_name')) {
                $table->string('bank_name', 120)->nullable()->after('bank_account_number');
            }
            if (! Schema::hasColumn('riders', 'bank_branch')) {
                $table->string('bank_branch', 120)->nullable()->after('bank_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('riders', function (Blueprint $table): void {
            foreach (['bank_branch', 'bank_name', 'bank_account_number', 'bank_account_name', 'nagad_number', 'bkash_number'] as $column) {
                if (Schema::hasColumn('riders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('rider_settings');
    }
};
