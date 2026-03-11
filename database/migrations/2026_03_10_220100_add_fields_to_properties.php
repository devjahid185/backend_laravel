<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained('property_categories')->nullOnDelete()->after('user_id');
            $table->enum('purpose', ['rent', 'sell'])->default('rent')->after('category_id');
            $table->string('property_type')->nullable()->after('type');
            $table->unsignedTinyInteger('bedrooms')->nullable()->after('property_type');
            $table->unsignedTinyInteger('bathrooms')->nullable()->after('bedrooms');
            $table->decimal('area', 12, 2)->nullable()->after('bathrooms');
            $table->string('area_unit', 20)->nullable()->after('area');
            $table->unsignedSmallInteger('floor')->nullable()->after('area_unit');
            $table->unsignedSmallInteger('total_floors')->nullable()->after('floor');
            $table->boolean('furnished')->default(false)->after('total_floors');
            $table->boolean('parking')->default(false)->after('furnished');
            $table->string('facing')->nullable()->after('parking');
            $table->unsignedSmallInteger('year_built')->nullable()->after('facing');
            $table->decimal('price_per_sqft', 12, 2)->nullable()->after('price');
            $table->boolean('negotiable')->default(false)->after('price_per_sqft');
            $table->string('district')->nullable()->after('location');
            $table->string('upazila')->nullable()->after('district');
            $table->string('address')->nullable()->after('upazila');
            $table->string('location_type')->nullable()->after('address');
            $table->decimal('lat', 10, 7)->nullable()->after('location_type');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
            $table->string('contact_name')->nullable()->after('contact');
            $table->string('contact_phone')->nullable()->after('contact_name');
            $table->string('contact_email')->nullable()->after('contact_phone');
            $table->string('contact_website')->nullable()->after('contact_email');
            $table->json('amenities')->nullable()->after('contact_website');
            $table->enum('status', ['open', 'closed'])->default('open')->after('amenities');
            $table->unsignedBigInteger('views')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn([
                'purpose',
                'property_type',
                'bedrooms',
                'bathrooms',
                'area',
                'area_unit',
                'floor',
                'total_floors',
                'furnished',
                'parking',
                'facing',
                'year_built',
                'price_per_sqft',
                'negotiable',
                'district',
                'upazila',
                'address',
                'location_type',
                'lat',
                'lng',
                'contact_name',
                'contact_phone',
                'contact_email',
                'contact_website',
                'amenities',
                'status',
                'views',
            ]);
        });
    }
};
