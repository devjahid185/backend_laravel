<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('blood_donors', function (Blueprint $table) {
            $table->string('name')->nullable()->after('user_id');
            $table->string('phone')->nullable()->after('blood_group');
            $table->string('district')->nullable()->after('phone');
            $table->string('upazila')->nullable()->after('district');
            $table->text('address')->nullable()->after('upazila');
            $table->string('gender', 16)->nullable()->after('address');
            $table->unsignedTinyInteger('age')->nullable()->after('gender');
            $table->unsignedSmallInteger('weight')->nullable()->after('age');
            $table->unsignedSmallInteger('donation_count')->default(0)->after('weight');
            $table->text('note')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('blood_donors', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'phone',
                'district',
                'upazila',
                'address',
                'gender',
                'age',
                'weight',
                'donation_count',
                'note',
            ]);
        });
    }
};
