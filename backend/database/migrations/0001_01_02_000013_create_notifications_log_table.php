<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parcel_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('channel', 20);
            $table->string('template', 80)->nullable();
            $table->string('recipient', 120);
            $table->string('status', 20);
            $table->string('provider_msg_id', 120)->nullable();
            $table->string('error_code', 40)->nullable();
            $table->text('error_message')->nullable();
            $table->jsonb('payload')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('parcel_id')->references('id')->on('parcels')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users');
        });

        DB::statement("CREATE INDEX notifications_log_parcel_idx ON notifications_log(parcel_id)");
        DB::statement("CREATE INDEX notifications_log_status_idx ON notifications_log(status, created_at)");
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_log');
    }
};
