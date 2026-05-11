<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hubs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 8)->unique();
            $table->string('name', 100);
            $table->text('address');
            $table->string('city', 80);
            $table->string('district', 80);
            $table->string('phone', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        // Hub location: decimal lat/lng (PostGIS GIST index to be added when extension is available)
        DB::statement('ALTER TABLE hubs ADD COLUMN hub_lat DECIMAL(10,7) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE hubs ADD COLUMN hub_lng DECIMAL(10,7) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE hubs ALTER COLUMN hub_lat DROP DEFAULT');
        DB::statement('ALTER TABLE hubs ALTER COLUMN hub_lng DROP DEFAULT');
        DB::statement('CREATE INDEX hubs_location_idx ON hubs(hub_lat, hub_lng)');
    }

    public function down(): void
    {
        Schema::dropIfExists('hubs');
    }
};
