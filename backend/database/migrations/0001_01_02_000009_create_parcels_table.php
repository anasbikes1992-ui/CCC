<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('parcel_number', 24)->unique();
            $table->text('qr_token');

            $table->uuid('customer_id');
            $table->uuid('trip_id')->nullable();

            $table->uuid('route_id');
            $table->uuid('package_size_id');

            $table->decimal('weight_kg', 8, 2);
            $table->integer('length_cm')->nullable();
            $table->integer('width_cm')->nullable();
            $table->integer('height_cm')->nullable();

            $table->string('pickup_type', 16);
            $table->text('pickup_address')->nullable();
            $table->uuid('pickup_hub_id')->nullable();

            $table->string('drop_type', 16);
            $table->text('drop_address')->nullable();
            $table->uuid('drop_hub_id')->nullable();

            $table->string('receiver_name', 150);
            $table->string('receiver_phone', 20);

            $table->decimal('declared_value_lkr', 12, 2)->nullable();
            $table->decimal('cod_amount_lkr', 12, 2)->nullable();

            $table->boolean('is_express')->default(false);
            $table->boolean('has_insurance')->default(false);

            $table->decimal('base_price_lkr', 10, 2);
            $table->decimal('surcharges_lkr', 10, 2)->default(0);
            $table->decimal('discount_lkr', 10, 2)->default(0);
            $table->decimal('total_price_lkr', 10, 2);
            $table->integer('capacity_units');

            $table->string('status', 40)->default('BOOKED');
            $table->timestampTz('status_changed_at')->useCurrent();

            $table->text('notes')->nullable();

            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('customer_id')->references('id')->on('users');
            $table->foreign('trip_id')->references('id')->on('trips');
            $table->foreign('route_id')->references('id')->on('routes');
            $table->foreign('package_size_id')->references('id')->on('package_sizes');
            $table->foreign('pickup_hub_id')->references('id')->on('hubs');
            $table->foreign('drop_hub_id')->references('id')->on('hubs');
        });

        // Geo columns: decimal lat/lng (PostGIS geography to be added when extension is available)
        DB::statement('ALTER TABLE parcels ADD COLUMN pickup_lat DECIMAL(10,7)');
        DB::statement('ALTER TABLE parcels ADD COLUMN pickup_lng DECIMAL(10,7)');
        DB::statement('ALTER TABLE parcels ADD COLUMN drop_lat DECIMAL(10,7)');
        DB::statement('ALTER TABLE parcels ADD COLUMN drop_lng DECIMAL(10,7)');

        DB::statement("ALTER TABLE parcels ADD CONSTRAINT parcels_pickup_type_check CHECK (pickup_type IN ('hub','doorstep'))");
        DB::statement("ALTER TABLE parcels ADD CONSTRAINT parcels_drop_type_check CHECK (drop_type IN ('hub','doorstep'))");
        DB::statement("ALTER TABLE parcels ADD CONSTRAINT parcels_total_nonneg CHECK (total_price_lkr >= 0)");
        DB::statement("ALTER TABLE parcels ADD CONSTRAINT parcels_capacity_pos CHECK (capacity_units > 0)");

        DB::statement("CREATE INDEX parcels_customer_idx ON parcels(customer_id) WHERE deleted_at IS NULL");
        DB::statement("CREATE INDEX parcels_trip_idx     ON parcels(trip_id)     WHERE deleted_at IS NULL");
        DB::statement("CREATE INDEX parcels_status_idx   ON parcels(status)      WHERE deleted_at IS NULL");
        DB::statement("CREATE INDEX parcels_number_lower_idx ON parcels(LOWER(parcel_number))");
    }

    public function down(): void
    {
        Schema::dropIfExists('parcels');
    }
};
