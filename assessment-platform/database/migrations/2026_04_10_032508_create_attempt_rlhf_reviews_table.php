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
        Schema::create('attempt_rlhf_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_answer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('score', 8, 2);
            $table->string('decision');
            $table->text('comments')->nullable();
            $table->boolean('finalized')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempt_rlhf_reviews');
    }
};
