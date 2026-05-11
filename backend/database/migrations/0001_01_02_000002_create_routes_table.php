<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 16)->unique();
            $table->uuid('origin_hub_id');
            $table->uuid('destination_hub_id');
            $table->string('display_name', 100);
            $table->integer('estimated_duration_minutes');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->foreign('origin_hub_id')->references('id')->on('hubs')->restrictOnDelete();
            $table->foreign('destination_hub_id')->references('id')->on('hubs')->restrictOnDelete();

            $table->index('origin_hub_id', 'routes_origin_idx');
        });

        DB::statement('ALTER TABLE routes ADD CONSTRAINT routes_origin_dest_diff_check CHECK (origin_hub_id <> destination_hub_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
