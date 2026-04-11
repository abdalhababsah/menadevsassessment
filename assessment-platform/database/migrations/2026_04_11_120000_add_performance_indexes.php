<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->index(['quiz_id', 'status', 'final_score'], 'quiz_attempts_quiz_status_final_idx');
        });

        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->index(['quiz_attempt_id', 'status'], 'attempt_answers_attempt_status_idx');
        });

        Schema::table('attempt_rlhf_turns', function (Blueprint $table) {
            $table->index(['attempt_answer_id', 'turn_number'], 'attempt_rlhf_turns_answer_turn_idx');
        });

        Schema::table('attempt_suspicious_events', function (Blueprint $table) {
            $table->index(['quiz_attempt_id', 'event_type'], 'attempt_suspicious_events_attempt_type_idx');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'audit_logs_user_created_idx');
            // The existing migration already indexes (auditable_type, auditable_id);
            // we also want a standalone action index for filter-by-action queries.
            $table->index('action', 'audit_logs_action_idx');
            $table->index('created_at', 'audit_logs_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('quiz_attempts_quiz_status_final_idx');
        });

        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->dropIndex('attempt_answers_attempt_status_idx');
        });

        Schema::table('attempt_rlhf_turns', function (Blueprint $table) {
            $table->dropIndex('attempt_rlhf_turns_answer_turn_idx');
        });

        Schema::table('attempt_suspicious_events', function (Blueprint $table) {
            $table->dropIndex('attempt_suspicious_events_attempt_type_idx');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_user_created_idx');
            $table->dropIndex('audit_logs_action_idx');
            $table->dropIndex('audit_logs_created_idx');
        });
    }
};
