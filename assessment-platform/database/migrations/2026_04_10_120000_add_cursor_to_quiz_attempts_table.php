<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->foreignId('current_section_id')->nullable()->after('invitation_id')
                ->constrained('quiz_sections')->nullOnDelete();
            $table->foreignId('current_question_id')->nullable()->after('current_section_id')
                ->constrained('questions')->nullOnDelete();
            $table->timestamp('section_started_at')->nullable()->after('current_question_id');
            $table->timestamp('question_started_at')->nullable()->after('section_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropForeign(['current_section_id']);
            $table->dropForeign(['current_question_id']);
            $table->dropColumn([
                'current_section_id',
                'current_question_id',
                'section_started_at',
                'question_started_at',
            ]);
        });
    }
};
