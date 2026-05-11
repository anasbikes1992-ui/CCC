<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('trip_code', 30)->unique();
            $table->uuid('route_id');
            $table->uuid('lorry_id')->nullable();
            $table->uuid('driver_id')->nullable();
            $table->timestampTz('scheduled_departure');
            $table->timestampTz('scheduled_arrival');
            $table->timestampTz('actual_departure')->nullable();
            $table->timestampTz('actual_arrival')->nullable();
            $table->string('status', 20)->default('SCHEDULED');
            $table->integer('capacity_units_max')->default(300);
            $table->integer('capacity_units_used')->default(0);
            $table->timestampTz('bookings_close_at');
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('route_id')->references('id')->on('routes');
            $table->foreign('lorry_id')->references('id')->on('lorries');
            $table->foreign('driver_id')->references('id')->on('drivers');
        });

        DB::statement('ALTER TABLE trips ADD CONSTRAINT trips_capacity_nonneg CHECK (capacity_units_used >= 0)');
        DB::statement('ALTER TABLE trips ADD CONSTRAINT trips_capacity_max CHECK (capacity_units_used <= capacity_units_max)');
        DB::statement('CREATE INDEX trips_route_dep_idx ON trips(route_id, scheduled_departure) WHERE deleted_at IS NULL AND status IN (\'SCHEDULED\',\'LOADING\')');
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
