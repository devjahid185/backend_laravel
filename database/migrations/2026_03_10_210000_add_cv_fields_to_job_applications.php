<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->string('cv_file')->nullable()->after('cv');
            $table->string('cv_original_name')->nullable()->after('cv_file');
            $table->string('cv_mime')->nullable()->after('cv_original_name');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn(['cv_file', 'cv_original_name', 'cv_mime']);
        });
    }
};
