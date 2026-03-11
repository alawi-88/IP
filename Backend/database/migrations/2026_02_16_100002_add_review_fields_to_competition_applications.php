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
                if (Schema::hasTable('competition_applications')) {
            Schema::table('competition_applications', function (Blueprint $table) {
            // Evaluation framework fields
            if (!Schema::hasColumn('competition_applications', 'final_evaluation_score')) {
                $table->decimal('final_evaluation_score', 8, 2)->nullable() 
                ->comment('Sum of all evaluator scores for registration');
            }
            if (!Schema::hasColumn('competition_applications', 'minimum_score_threshold')) {
                $table->unsignedInteger('minimum_score_threshold')->nullable() 
                ->comment('Minimum score required for this program');
            }

            // Review & Decision fields (IN-2030)
            if (!Schema::hasColumn('competition_applications', 'decision_reason')) {
                $table->text('decision_reason')->nullable() 
                ->comment('Reason for approval/rejection');
            }
            if (!Schema::hasColumn('competition_applications', 'editable_fields')) {
                $table->json('editable_fields')->nullable() 
                ->comment('JSON array of field slugs admin has approved for editing');
            }
            if (!Schema::hasColumn('competition_applications', 'edit_notes')) {
                $table->json('edit_notes')->nullable() 
                ->comment('JSON object: field_slug => admin notes for each field');
            }
            if (!Schema::hasColumn('competition_applications', 'edit_requested_at')) {
                $table->timestamp('edit_requested_at')->nullable() 
                ;
            }
            if (!Schema::hasColumn('competition_applications', 'resubmitted_at')) {
                $table->timestamp('resubmitted_at')->nullable() 
                ;
            }
            if (!Schema::hasColumn('competition_applications', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable() 
                ->constrained('users')
                ->nullOnDelete();
            }
            if (!Schema::hasColumn('competition_applications', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable() 
                ;
            }
        });
        }

        // Add minimum score threshold to registration form configs
        if (Schema::hasTable('registration_form_configs')) {
            Schema::table('registration_form_configs', function (Blueprint $table) {
            if (!Schema::hasColumn('registration_form_configs', 'minimum_score_threshold')) {
                $table->unsignedInteger('minimum_score_threshold')->nullable() 
                ->comment('Minimum score required for auto-qualification');
            }
        });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('competition_applications')) {
            Schema::table('competition_applications', function (Blueprint $table) {
try {                 if (Schema::hasColumn('registration_form_configs', 'final_evaluation_score')) { if (Schema::hasColumn('competition_applications', 'final_evaluation_score')) { $table->dropColumn('final_evaluation_score'); } }
                if (Schema::hasColumn('registration_form_configs', 'minimum_score_threshold')) { if (Schema::hasColumn('competition_applications', 'minimum_score_threshold')) { $table->dropColumn('minimum_score_threshold'); } }
                if (Schema::hasColumn('registration_form_configs', 'decision_reason')) { if (Schema::hasColumn('competition_applications', 'decision_reason')) { $table->dropColumn('decision_reason'); } }
                if (Schema::hasColumn('registration_form_configs', 'editable_fields')) { if (Schema::hasColumn('competition_applications', 'editable_fields')) { $table->dropColumn('editable_fields'); } }
                if (Schema::hasColumn('registration_form_configs', 'edit_notes')) { if (Schema::hasColumn('competition_applications', 'edit_notes')) { $table->dropColumn('edit_notes'); } }
                if (Schema::hasColumn('registration_form_configs', 'edit_requested_at')) { if (Schema::hasColumn('competition_applications', 'edit_requested_at')) { $table->dropColumn('edit_requested_at'); } }
                if (Schema::hasColumn('registration_form_configs', 'resubmitted_at')) { if (Schema::hasColumn('competition_applications', 'resubmitted_at')) { $table->dropColumn('resubmitted_at'); } }
                if (Schema::hasColumn('registration_form_configs', 'reviewed_by')) { if (Schema::hasColumn('competition_applications', 'reviewed_by')) { $table->dropColumn('reviewed_by'); } }
                if (Schema::hasColumn('registration_form_configs', 'reviewed_at')) { if (Schema::hasColumn('competition_applications', 'reviewed_at')) { $table->dropColumn('reviewed_at'); } } } catch (\Exception $e) {}
        });
        }

        if (Schema::hasTable('registration_form_configs')) {
            Schema::table('registration_form_configs', function (Blueprint $table) {
            if (Schema::hasColumn('registration_form_configs', 'minimum_score_threshold')) { if (Schema::hasColumn('competition_applications', 'minimum_score_threshold')) { $table->dropColumn('minimum_score_threshold'); } }
        });
        }
    }
};
