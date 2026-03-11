<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Epic IN-2029/IN-2030/IN-2025: Registration Review & Evaluation
     * Adds evaluation score, decision reason, and edit request fields
     */
    public function up(): void
    {
        Schema::table('competition_applications', function (Blueprint $table) {
            // Evaluation framework fields
            $table->decimal('final_evaluation_score', 8, 2)->nullable()
                
                ->comment('Sum of all evaluator scores for registration');
            $table->unsignedInteger('minimum_score_threshold')->nullable()
                
                ->comment('Minimum score required for this program');

            // Review & Decision fields (IN-2030)
            $table->text('decision_reason')->nullable()
                
                ->comment('Reason for approval/rejection');
            $table->json('editable_fields')->nullable()
                
                ->comment('JSON array of field slugs admin has approved for editing');
            $table->json('edit_notes')->nullable()
                
                ->comment('JSON object: field_slug => admin notes for each field');
            $table->timestamp('edit_requested_at')->nullable()
                ;
            $table->timestamp('resubmitted_at')->nullable()
                ;
            $table->foreignId('reviewed_by')->nullable()
                
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()
                ;
        });

        // Add minimum score threshold to registration form configs
        Schema::table('registration_form_configs', function (Blueprint $table) {
            $table->unsignedInteger('minimum_score_threshold')->nullable()
                
                ->comment('Minimum score required for auto-qualification');
        });
    }

    public function down(): void
    {
        Schema::table('competition_applications', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn([
                'final_evaluation_score',
                'minimum_score_threshold',
                'decision_reason',
                'editable_fields',
                'edit_notes',
                'edit_requested_at',
                'resubmitted_at',
                'reviewed_by',
                'reviewed_at',
            ]);
        });

        Schema::table('registration_form_configs', function (Blueprint $table) {
            $table->dropColumn('minimum_score_threshold');
        });
    }
};
