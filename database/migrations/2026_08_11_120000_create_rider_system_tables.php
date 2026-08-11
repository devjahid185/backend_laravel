<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('phone', 40);
            $table->string('email', 120)->nullable();
            $table->string('district', 80)->nullable();
            $table->string('upazila', 80)->nullable();
            $table->text('address')->nullable();
            $table->string('profile_photo')->nullable();
            $table->enum('vehicle_type', ['cycle', 'bike', 'car'])->default('bike');
            $table->string('vehicle_number', 80)->nullable();
            $table->string('emergency_contact_name', 120)->nullable();
            $table->string('emergency_contact_phone', 40)->nullable();
            $table->enum('kyc_status', ['draft', 'pending', 'approved', 'rejected'])->default('draft');
            $table->text('kyc_note')->nullable();
            $table->timestamp('kyc_submitted_at')->nullable();
            $table->timestamp('kyc_reviewed_at')->nullable();
            $table->foreignId('kyc_reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->boolean('agreement_accepted')->default(false);
            $table->timestamp('agreement_accepted_at')->nullable();
            $table->enum('agreement_status', ['pending', 'active', 'suspended', 'ended'])->default('pending');
            $table->enum('commission_type', ['fixed', 'percentage', 'zone_based'])->default('fixed');
            $table->decimal('commission_value', 10, 2)->default(0);
            $table->enum('payment_cycle', ['daily', 'weekly', 'monthly'])->default('weekly');
            $table->text('responsibility_terms')->nullable();
            $table->text('penalty_policy')->nullable();
            $table->string('agreement_pdf')->nullable();
            $table->enum('availability_status', ['offline', 'online', 'busy'])->default('offline');
            $table->enum('account_status', ['pending', 'active', 'suspended', 'blocked'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->decimal('wallet_balance', 12, 2)->default(0);
            $table->decimal('pending_payout', 12, 2)->default(0);
            $table->decimal('cash_in_hand', 12, 2)->default(0);
            $table->decimal('last_lat', 10, 7)->nullable();
            $table->decimal('last_lng', 10, 7)->nullable();
            $table->timestamp('last_location_at')->nullable();
            $table->timestamps();
            $table->index(['kyc_status', 'account_status']);
            $table->index(['availability_status', 'last_location_at']);
        });

        Schema::create('rider_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rider_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['nid_front', 'nid_back', 'selfie', 'driving_license', 'vehicle_paper', 'bank_mfs']);
            $table->string('title', 120);
            $table->string('file_path');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['type', 'status']);
        });

        Schema::create('rider_wallet_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('food_order_id')->nullable()->constrained('food_orders')->nullOnDelete();
            $table->enum('type', ['earning', 'cash_collection', 'payout', 'adjustment', 'penalty']);
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->string('title', 160);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['type', 'created_at']);
        });

        Schema::create('rider_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('food_order_id')->nullable()->constrained('food_orders')->nullOnDelete();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->timestamps();
            $table->index(['rider_id', 'created_at']);
        });

        Schema::create('rider_ratings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('food_order_id')->nullable()->constrained('food_orders')->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('review')->nullable();
            $table->timestamps();
            $table->unique(['rider_id', 'food_order_id']);
        });

        Schema::create('rider_support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('food_order_id')->nullable()->constrained('food_orders')->nullOnDelete();
            $table->string('subject', 160);
            $table->text('message');
            $table->enum('status', ['open', 'reviewing', 'resolved', 'closed'])->default('open');
            $table->text('admin_reply')->nullable();
            $table->timestamps();
        });

        Schema::table('food_orders', function (Blueprint $table): void {
            $table->foreignId('rider_id')->nullable()->after('restaurant_id')->constrained('riders')->nullOnDelete();
            $table->decimal('rider_earning', 10, 2)->default(0)->after('delivery_fee');
            $table->decimal('cash_collected', 10, 2)->default(0)->after('rider_earning');
            $table->string('delivery_otp', 12)->nullable()->after('delivery_person_phone');
            $table->string('delivery_proof_photo')->nullable()->after('delivery_otp');
            $table->string('cancel_reason')->nullable()->after('order_note');
            $table->timestamp('rider_assigned_at')->nullable()->after('accepted_at');
            $table->timestamp('picked_up_at')->nullable()->after('rider_assigned_at');
        });
    }

    public function down(): void
    {
        Schema::table('food_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('rider_id');
            $table->dropColumn([
                'rider_earning',
                'cash_collected',
                'delivery_otp',
                'delivery_proof_photo',
                'cancel_reason',
                'rider_assigned_at',
                'picked_up_at',
            ]);
        });
        Schema::dropIfExists('rider_support_tickets');
        Schema::dropIfExists('rider_ratings');
        Schema::dropIfExists('rider_locations');
        Schema::dropIfExists('rider_wallet_entries');
        Schema::dropIfExists('rider_documents');
        Schema::dropIfExists('riders');
    }
};
