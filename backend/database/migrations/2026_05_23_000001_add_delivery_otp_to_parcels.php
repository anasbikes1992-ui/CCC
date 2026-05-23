<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->string('delivery_otp', 6)->nullable()->after('status_changed_at');
            $table->timestampTz('delivery_otp_generated_at')->nullable()->after('delivery_otp');
            $table->timestampTz('delivery_otp_verified_at')->nullable()->after('delivery_otp_generated_at');
            $table->integer('delivery_otp_attempts')->default(0)->after('delivery_otp_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('parcels', function (Blueprint $table) {
            $table->dropColumn(['delivery_otp', 'delivery_otp_generated_at', 'delivery_otp_verified_at', 'delivery_otp_attempts']);
        });
    }
};
