<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignUuid('parcel_id')
                ->nullable()
                ->constrained('parcels')
                ->nullOnDelete();

            $table->string('subject', 255);

            $table->enum('status', [
                'open',
                'pending',       // waiting on customer reply
                'in_progress',
                'resolved',
                'closed',
            ])->default('open');

            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');

            $table->foreignUuid('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->timestamp('resolved_at')->nullable();

            $table->index(['status', 'priority', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index(['assigned_to', 'status']);
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('ticket_id')
                ->constrained('support_tickets')
                ->cascadeOnDelete();

            $table->foreignUuid('sender_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('body');

            // JSON array of {url, filename, mime_type} objects
            $table->jsonb('attachments')->default('[]');

            $table->timestamp('sent_at')->useCurrent();

            $table->index(['ticket_id', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
