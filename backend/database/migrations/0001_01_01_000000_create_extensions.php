<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // pgcrypto: used for NIC encryption in delivery_proofs
        DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto');
        // citext: case-insensitive text (used for email/phone lookups)
        DB::statement('CREATE EXTENSION IF NOT EXISTS citext');
        // PostGIS: deferred — use decimal lat/lng for Phase 1.
        // Enable when postgis extension is installed on the server:
        // DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
    }

    public function down(): void
    {
        // Don't drop extensions on rollback — they may be shared.
    }
};
