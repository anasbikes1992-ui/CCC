<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_matrix', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('route_id');
            $table->uuid('package_size_id');
            $table->decimal('base_price_lkr', 10, 2);
            $table->jsonb('surcharges')->default(DB::raw("'{}'::jsonb"));
            $table->date('effective_from')->useCurrent();
            $table->date('effective_until')->nullable();
            $table->timestampsTz();

            $table->foreign('route_id')->references('id')->on('routes')->cascadeOnDelete();
            $table->foreign('package_size_id')->references('id')->on('package_sizes')->cascadeOnDelete();

            $table->unique(['route_id', 'package_size_id', 'effective_from']);
        });

        DB::statement('CREATE INDEX pricing_matrix_lookup_idx ON pricing_matrix(route_id, package_size_id) WHERE effective_until IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_matrix');
    }
};
