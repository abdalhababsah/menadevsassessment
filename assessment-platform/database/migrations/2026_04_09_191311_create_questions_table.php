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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->text('stem');
            $table->text('instructions')->nullable();
            $table->string('difficulty');
            $table->decimal('points', 8, 2)->default(1);
            $table->unsignedInteger('time_limit_seconds')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('parent_question_id')->nullable()->constrained('questions')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
