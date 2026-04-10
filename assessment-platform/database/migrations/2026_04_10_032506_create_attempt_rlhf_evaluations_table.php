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
        Schema::create('attempt_rlhf_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rlhf_turn_id')->constrained('attempt_rlhf_turns')->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('rlhf_criteria')->cascadeOnDelete();
            $table->string('response_side');
            $table->string('rating_value', 50);
            $table->text('justification')->nullable();
            $table->timestamps();

            $table->unique(['rlhf_turn_id', 'criterion_id', 'response_side'], 'eval_turn_criterion_side_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempt_rlhf_evaluations');
    }
};
