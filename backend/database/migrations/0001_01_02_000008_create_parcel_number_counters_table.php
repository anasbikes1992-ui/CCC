<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcel_number_counters', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->integer('last_seq')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcel_number_counters');
    }
};
