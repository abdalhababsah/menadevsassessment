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
        Schema::create('attempt_coding_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coding_submission_id')->constrained('attempt_coding_submissions')->cascadeOnDelete();
            $table->foreignId('test_case_id')->constrained('coding_test_cases')->cascadeOnDelete();
            $table->boolean('passed')->default(false);
            $table->longText('actual_output')->nullable();
            $table->unsignedInteger('runtime_ms')->nullable();
            $table->unsignedInteger('memory_kb')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempt_coding_test_results');
    }
};
