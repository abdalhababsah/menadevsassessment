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
        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('question_version')->default(1);
            $table->timestamp('answered_at')->nullable();
            $table->unsignedInteger('time_spent_seconds')->default(0);
            $table->decimal('auto_score', 8, 2)->nullable();
            $table->decimal('reviewer_score', 8, 2)->nullable();
            $table->string('status')->default('unanswered');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
    }
};
