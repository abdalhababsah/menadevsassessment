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
        Schema::create('attempt_answer_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_answer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_option_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['attempt_answer_id', 'question_option_id'], 'answer_selection_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempt_answer_selections');
    }
};
