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
        Schema::create('attempt_rlhf_form_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rlhf_turn_id')->constrained('attempt_rlhf_turns')->cascadeOnDelete();
            $table->string('stage');
            $table->string('field_key');
            $table->longText('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempt_rlhf_form_responses');
    }
};
