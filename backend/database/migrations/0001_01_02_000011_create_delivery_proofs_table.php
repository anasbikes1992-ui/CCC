<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_proofs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parcel_id')->unique();
            $table->string('receiver_name_input', 150);
            $table->text('receiver_nic_encrypted');
            $table->string('receiver_nic_last4', 4);
            $table->text('signature_url');
            $table->integer('signature_size_bytes');
            $table->text('photo_url')->nullable();
            $table->integer('photo_size_bytes')->nullable();
            $table->timestampTz('delivered_at');
            $table->uuid('delivered_by_user_id');
            $table->string('device_id', 80)->nullable();
            $table->timestampsTz();

            $table->foreign('parcel_id')->references('id')->on('parcels')->cascadeOnDelete();
            $table->foreign('delivered_by_user_id')->references('id')->on('users');
        });

        // Delivery geo: decimal lat/lng (PostGIS to be enabled later)
        DB::statement('ALTER TABLE delivery_proofs ADD COLUMN delivery_lat DECIMAL(10,7)');
        DB::statement('ALTER TABLE delivery_proofs ADD COLUMN delivery_lng DECIMAL(10,7)');
        DB::statement("ALTER TABLE delivery_proofs ADD CONSTRAINT delivery_proofs_sig_min CHECK (signature_size_bytes >= 5120)");
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_proofs');
    }
};
