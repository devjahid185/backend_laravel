<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rider_order_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('rider_order_requests', 'medicine_order_id')) {
                $table->foreignId('medicine_order_id')->nullable()->after('food_order_id')->constrained('medicine_orders')->cascadeOnDelete();
            }
        });

        try {
            DB::statement('ALTER TABLE rider_order_requests MODIFY food_order_id BIGINT UNSIGNED NULL');
        } catch (\Throwable $e) {
            // Some databases may already have the nullable definition.
        }

        Schema::table('rider_order_requests', function (Blueprint $table): void {
            if (! $this->indexExists('rider_order_requests', 'rider_order_requests_medicine_order_id_rider_id_unique')) {
                $table->unique(['medicine_order_id', 'rider_id']);
            }
            if (! $this->indexExists('rider_order_requests', 'rider_order_requests_medicine_order_id_status_index')) {
                $table->index(['medicine_order_id', 'status']);
            }
        });

        Schema::table('medicine_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('medicine_orders', 'delivery_person_name')) {
                $table->string('delivery_person_name')->nullable()->after('rider_id');
            }
            if (! Schema::hasColumn('medicine_orders', 'delivery_person_phone')) {
                $table->string('delivery_person_phone')->nullable()->after('delivery_person_name');
            }
            if (! Schema::hasColumn('medicine_orders', 'rider_earning')) {
                $table->decimal('rider_earning', 10, 2)->default(0)->after('delivery_fee');
            }
            if (! Schema::hasColumn('medicine_orders', 'cash_collected')) {
                $table->decimal('cash_collected', 10, 2)->default(0)->after('rider_earning');
            }
            if (! Schema::hasColumn('medicine_orders', 'rider_assigned_at')) {
                $table->timestamp('rider_assigned_at')->nullable()->after('accepted_at');
            }
            if (! Schema::hasColumn('medicine_orders', 'picked_up_at')) {
                $table->timestamp('picked_up_at')->nullable()->after('rider_assigned_at');
            }
            if (! Schema::hasColumn('medicine_orders', 'delivery_proof_photo')) {
                $table->string('delivery_proof_photo')->nullable()->after('payment_proof_photo');
            }
        });

        Schema::table('rider_wallet_entries', function (Blueprint $table): void {
            if (! Schema::hasColumn('rider_wallet_entries', 'medicine_order_id')) {
                $table->foreignId('medicine_order_id')->nullable()->after('food_order_id')->constrained('medicine_orders')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('medicine_orders', function (Blueprint $table): void {
            foreach (['delivery_person_name', 'delivery_person_phone', 'rider_earning', 'cash_collected', 'rider_assigned_at', 'picked_up_at', 'delivery_proof_photo'] as $column) {
                if (Schema::hasColumn('medicine_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('rider_order_requests', function (Blueprint $table): void {
            if ($this->indexExists('rider_order_requests', 'rider_order_requests_medicine_order_id_status_index')) {
                $table->dropIndex('rider_order_requests_medicine_order_id_status_index');
            }
            if ($this->indexExists('rider_order_requests', 'rider_order_requests_medicine_order_id_rider_id_unique')) {
                $table->dropUnique('rider_order_requests_medicine_order_id_rider_id_unique');
            }
            if (Schema::hasColumn('rider_order_requests', 'medicine_order_id')) {
                $table->dropConstrainedForeignId('medicine_order_id');
            }
        });

        Schema::table('rider_wallet_entries', function (Blueprint $table): void {
            if (Schema::hasColumn('rider_wallet_entries', 'medicine_order_id')) {
                $table->dropConstrainedForeignId('medicine_order_id');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();
        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
