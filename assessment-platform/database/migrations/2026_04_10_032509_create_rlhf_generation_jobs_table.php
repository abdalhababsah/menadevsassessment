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
        Schema::create('rlhf_generation_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rlhf_turn_id')->constrained('attempt_rlhf_turns')->cascadeOnDelete();
            $table->string('side');
            $table->string('status')->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['rlhf_turn_id', 'side']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rlhf_generation_jobs');
    }
};
