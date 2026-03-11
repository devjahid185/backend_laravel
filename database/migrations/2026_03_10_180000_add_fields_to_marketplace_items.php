<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_items', function (Blueprint $table) {
            $table->enum('condition', ['new', 'used'])->nullable()->after('price');
            $table->string('brand')->nullable()->after('condition');
            $table->string('model')->nullable()->after('brand');
            $table->boolean('negotiable')->default(false)->after('model');
            $table->string('delivery')->nullable()->after('negotiable');
            $table->decimal('location_lat', 10, 7)->nullable()->after('location');
            $table->decimal('location_lng', 10, 7)->nullable()->after('location_lat');
            $table->unsignedInteger('views')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_items', function (Blueprint $table) {
            $table->dropColumn([
                'condition',
                'brand',
                'model',
                'negotiable',
                'delivery',
                'location_lat',
                'location_lng',
                'views',
            ]);
        });
    }
};
