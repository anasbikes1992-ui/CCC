<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disputes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('parcel_id')
                ->constrained('parcels')
                ->cascadeOnDelete();

            $table->foreignUuid('raised_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->enum('type', [
                'not_delivered',
                'damaged',
                'lost',
                'wrong_item',
                'late_delivery',
                'other',
            ]);

            $table->text('description');

            $table->enum('status', [
                'open',
                'under_review',
                'resolved',
                'rejected',
                'closed',
            ])->default('open');

            $table->foreignUuid('resolved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('resolution')->nullable();

            $table->timestamps();
            $table->timestamp('resolved_at')->nullable();

            $table->index(['parcel_id', 'status']);
            $table->index(['raised_by_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
