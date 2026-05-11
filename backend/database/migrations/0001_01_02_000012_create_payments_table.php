<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parcel_id');
            $table->string('method', 20);
            $table->string('status', 20)->default('pending');
            $table->decimal('amount_lkr', 10, 2);
            $table->string('reference', 100)->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampsTz();

            $table->foreign('parcel_id')->references('id')->on('parcels')->cascadeOnDelete();
        });

        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_method_check CHECK (method IN ('cod','bank_transfer','card'))");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status IN ('pending','paid','failed','refunded'))");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_amount_nonneg CHECK (amount_lkr >= 0)");
        DB::statement("CREATE INDEX payments_parcel_idx ON payments(parcel_id)");
        DB::statement("CREATE INDEX payments_status_idx ON payments(status) WHERE status = 'pending'");
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
