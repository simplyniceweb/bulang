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
        Schema::create('rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->integer('round_number');
            $table->enum('status', ['open', 'closed', 'cancelled'])->default('open');
            $table->enum('winner', ['meron', 'wala', 'draw'])->nullable();

            $table->decimal('total_meron', 12, 2)->default(0);
            $table->decimal('total_wala', 12, 2)->default(0);
            $table->decimal('total_draw', 12, 2)->default(0);
            $table->decimal('house_cut', 12, 2)->default(0);

            $table->boolean('meron_closed')->default(false);
            $table->boolean('wala_closed')->default(false);
            $table->boolean('draw_closed')->default(false);
            $table->boolean('betting_closed')->default(false);

            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            $table->timestamps();

            $table->unique(['event_id', 'round_number']);
            $table->index(['event_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rounds');
    }
};
