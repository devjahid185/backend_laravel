<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('cv');
            $table->decimal('expected_salary', 12, 2)->nullable()->after('phone');
            $table->enum('status', ['pending', 'shortlisted', 'rejected', 'hired'])->default('pending')->after('cover_letter');
            $table->text('note')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn(['phone', 'expected_salary', 'status', 'note']);
        });
    }
};
