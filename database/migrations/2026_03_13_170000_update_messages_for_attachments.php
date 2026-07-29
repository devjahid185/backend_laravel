<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->text('message')->nullable()->change();
            $table->string('attachment_url')->nullable()->after('image');
            $table->string('attachment_name')->nullable()->after('attachment_url');
            $table->string('attachment_mime')->nullable()->after('attachment_name');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->text('message')->nullable(false)->change();
            $table->dropColumn(['attachment_url', 'attachment_name', 'attachment_mime']);
        });
    }
};
