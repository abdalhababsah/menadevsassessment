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
        Schema::create('attempt_rlhf_turns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_answer_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('turn_number');
            $table->longText('candidate_input')->nullable();
            $table->string('candidate_input_audio_url', 500)->nullable();
            $table->longText('response_a')->nullable();
            $table->longText('response_b')->nullable();
            $table->string('model_a');
            $table->string('model_b');
            $table->string('generation_status')->default('pending');
            $table->text('generation_error')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->tinyInteger('sxs_rating')->nullable();
            $table->text('sxs_justification')->nullable();
            $table->string('selected_side')->nullable();
            $table->longText('selected_response_rewrite')->nullable();
            $table->timestamp('rewrite_completed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['attempt_answer_id', 'turn_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempt_rlhf_turns');
    }
};
