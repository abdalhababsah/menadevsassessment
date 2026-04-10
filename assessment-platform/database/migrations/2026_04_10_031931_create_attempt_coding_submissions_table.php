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
        Schema::create('attempt_coding_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_answer_id')->constrained()->cascadeOnDelete();
            $table->string('language');
            $table->longText('code');
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempt_coding_submissions');
    }
};
