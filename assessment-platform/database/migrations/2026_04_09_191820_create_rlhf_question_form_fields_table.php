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
        Schema::create('rlhf_question_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('stage');
            $table->string('field_key');
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('field_type');
            $table->json('options')->nullable();
            $table->boolean('required')->default(true);
            $table->unsignedInteger('min_length')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rlhf_question_form_fields');
    }
};
