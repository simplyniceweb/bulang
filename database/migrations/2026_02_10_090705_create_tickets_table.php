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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 10)->unique()->index();
            $table->enum('status', ['pending', 'won', 'lost', 'paid', 'refunded'])->default('pending');

            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('round_id')->constrained()->cascadeOnDelete();

            $table->foreignId('teller_id')->constrained('users');
            $table->foreignId('paid_by')->nullable()->constrained('users');
            $table->foreignId('refunded_by')->nullable()->constrained('users');

            $table->enum('side', ['meron', 'wala', 'draw']);
            $table->decimal('amount', 12, 2);
            $table->decimal('odds', 6, 3);
            $table->decimal('potential_payout', 12, 2);

            $table->dateTime('claimed_at')->nullable();
            $table->dateTime('refunded_at')->nullable();

            $table->timestamps();

            $table->index('round_id');
            $table->index('claimed_at');
            $table->index('teller_id');

            $table->index('status');
            $table->index('event_id');
            $table->index('side');

            // Composite indexes for specific "Live" queries
            // 1. Used to calculate the betting pool for the current round
            $table->index(['round_id', 'status', 'side'], 'idx_round_pool');

            // 2. Used for teller's end-of-shift report (History)
            $table->index(['teller_id', 'status', 'created_at'], 'idx_teller_history');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
