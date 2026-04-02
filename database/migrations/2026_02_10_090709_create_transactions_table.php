<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teller_id')->constrained('users');
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('round_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('direction', ['in', 'out']);

            $table->enum('type', ['bet', 'claim', 'refund', 'cash_in', 'cash_out']);
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2)->default(0);
            $table->decimal('balance_after', 12, 2)->default(0);

            $table->foreignId('authorized_by')->nullable()->constrained('users');

            $table->timestamp('created_at')->useCurrent();

            $table->index(['teller_id', 'event_id']);
            $table->index('event_id');
            $table->index('ticket_id');
            $table->unique(['ticket_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
