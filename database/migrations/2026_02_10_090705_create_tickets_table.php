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
            $table->string('ticket_number', 40)->unique()->index();

            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('round_id')->constrained()->cascadeOnDelete();

            $table->foreignId('teller_id')->constrained('users');
            $table->foreignId('paid_by')->nullable()->constrained('users');

            $table->enum('side', ['meron', 'wala', 'draw']);
            $table->decimal('amount', 12, 2);
            $table->decimal('odds', 6, 3);
            $table->decimal('potential_payout', 12, 2);

            $table->dateTime('claimed_at')->nullable();

            $table->timestamps();

            $table->index('round_id');
            $table->index('claimed_at');
            $table->index('teller_id');
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
