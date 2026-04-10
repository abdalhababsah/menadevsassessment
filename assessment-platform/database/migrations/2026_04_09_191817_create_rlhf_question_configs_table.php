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
        Schema::create('rlhf_question_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number_of_turns')->default(1);
            $table->string('candidate_input_mode')->default('text');
            $table->string('model_a');
            $table->string('model_b');
            $table->json('generation_params')->nullable();
            $table->boolean('enable_pre_prompt_form')->default(false);
            $table->boolean('enable_post_prompt_form')->default(true);
            $table->boolean('enable_rewrite_step')->default(false);
            $table->boolean('enable_post_rewrite_form')->default(false);
            $table->longText('guidelines_markdown')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rlhf_question_configs');
    }
};
