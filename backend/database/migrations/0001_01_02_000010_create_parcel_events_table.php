<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcel_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parcel_id');
            $table->string('event_type', 40);
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40)->nullable();
            $table->uuid('actor_user_id')->nullable();
            $table->string('actor_role', 40)->nullable();
            $table->uuid('hub_id')->nullable();
            $table->uuid('trip_id')->nullable();
            $table->string('scan_mode', 16)->default('qr');
            $table->string('device_id', 80)->nullable();
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('occurred_at')->useCurrent();

            $table->foreign('parcel_id')->references('id')->on('parcels')->cascadeOnDelete();
            $table->foreign('actor_user_id')->references('id')->on('users');
            $table->foreign('hub_id')->references('id')->on('hubs');
            $table->foreign('trip_id')->references('id')->on('trips');
        });

        // Geo capture: decimal lat/lng (PostGIS to be enabled later)
        DB::statement('ALTER TABLE parcel_events ADD COLUMN geo_lat DECIMAL(10,7)');
        DB::statement('ALTER TABLE parcel_events ADD COLUMN geo_lng DECIMAL(10,7)');
        DB::statement("CREATE INDEX parcel_events_parcel_idx ON parcel_events(parcel_id, occurred_at DESC)");
        DB::statement("CREATE INDEX parcel_events_type_idx   ON parcel_events(event_type)");
    }

    public function down(): void
    {
        Schema::dropIfExists('parcel_events');
    }
};
