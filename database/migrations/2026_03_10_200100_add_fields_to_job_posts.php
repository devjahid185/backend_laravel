<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('job_posts', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained('job_categories')->nullOnDelete()->after('posted_by');
            $table->enum('post_type', ['hiring', 'seeking'])->default('hiring')->after('category_id');
            $table->string('employment_type')->nullable()->after('type');
            $table->string('experience_level')->nullable()->after('employment_type');
            $table->string('education')->nullable()->after('experience_level');
            $table->unsignedTinyInteger('vacancies')->nullable()->after('education');
            $table->date('deadline')->nullable()->after('vacancies');
            $table->decimal('salary_min', 12, 2)->nullable()->after('salary');
            $table->decimal('salary_max', 12, 2)->nullable()->after('salary_min');
            $table->boolean('negotiable')->default(false)->after('salary_max');
            $table->string('location_type')->nullable()->after('location');
            $table->string('contact_email')->nullable()->after('contact');
            $table->string('contact_phone')->nullable()->after('contact_email');
            $table->string('company_website')->nullable()->after('contact_phone');
            $table->string('company_size')->nullable()->after('company_website');
            $table->text('responsibilities')->nullable()->after('description');
            $table->text('requirements')->nullable()->after('responsibilities');
            $table->text('benefits')->nullable()->after('requirements');
            $table->string('gender')->nullable()->after('benefits');
            $table->unsignedTinyInteger('age_min')->nullable()->after('gender');
            $table->unsignedTinyInteger('age_max')->nullable()->after('age_min');
            $table->enum('status', ['open', 'closed'])->default('open')->after('age_max');
            $table->unsignedBigInteger('views')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('job_posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn([
                'post_type',
                'employment_type',
                'experience_level',
                'education',
                'vacancies',
                'deadline',
                'salary_min',
                'salary_max',
                'negotiable',
                'location_type',
                'contact_email',
                'contact_phone',
                'company_website',
                'company_size',
                'responsibilities',
                'requirements',
                'benefits',
                'gender',
                'age_min',
                'age_max',
                'status',
                'views',
            ]);
        });
    }
};
