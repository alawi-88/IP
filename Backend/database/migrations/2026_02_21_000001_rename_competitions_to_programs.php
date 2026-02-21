<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename all competition-related tables and columns to use "program" terminology.
     */
    public function up(): void
    {
        // Disable foreign key checks for the duration of the migration
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // 1. Rename main tables
        Schema::rename('competitions', 'programs');
        Schema::rename('competition_applications', 'program_applications');
        Schema::rename('competition_judge', 'program_judge');
        Schema::rename('competition_labels', 'program_labels');
        Schema::rename('competition_tabs', 'program_tabs');
        Schema::rename('branding_competitions', 'branding_programs');
        Schema::rename('user_competitions', 'user_programs');
        Schema::rename('mentor_competitions', 'mentor_programs');

        // 2. Rename competition_id → program_id in all affected tables

        // program_applications (formerly competition_applications)
        Schema::table('program_applications', function (Blueprint $table) {
            $table->renameColumn('competition_id', 'program_id');
        });

        // program_judge (formerly competition_judge)
        Schema::table('program_judge', function (Blueprint $table) {
            $table->renameColumn('competition_id', 'program_id');
        });

        // program_labels (formerly competition_labels)
        Schema::table('program_labels', function (Blueprint $table) {
            $table->renameColumn('competition_id', 'program_id');
        });

        // program_tabs (formerly competition_tabs)
        Schema::table('program_tabs', function (Blueprint $table) {
            $table->renameColumn('competition_id', 'program_id');
        });

        // branding_programs (formerly branding_competitions)
        Schema::table('branding_programs', function (Blueprint $table) {
            $table->renameColumn('competition_id', 'program_id');
        });

        // user_programs (formerly user_competitions)
        Schema::table('user_programs', function (Blueprint $table) {
            $table->renameColumn('competition_id', 'program_id');
        });

        // mentor_programs (formerly mentor_competitions)
        Schema::table('mentor_programs', function (Blueprint $table) {
            $table->renameColumn('competition_id', 'program_id');
        });

        // committees
        if (Schema::hasColumn('committees', 'competition_id')) {
            Schema::table('committees', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // dashboards
        if (Schema::hasColumn('dashboards', 'competition_id')) {
            Schema::table('dashboards', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // evaluation_stage_configs
        if (Schema::hasColumn('evaluation_stage_configs', 'competition_id')) {
            Schema::table('evaluation_stage_configs', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // events
        if (Schema::hasColumn('events', 'competition_id')) {
            Schema::table('events', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // forms
        if (Schema::hasColumn('forms', 'competition_id')) {
            Schema::table('forms', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // guidelines
        if (Schema::hasColumn('guidelines', 'competition_id')) {
            Schema::table('guidelines', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // mentor_participant
        if (Schema::hasColumn('mentor_participant', 'competition_id')) {
            Schema::table('mentor_participant', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // mentor_sessions
        if (Schema::hasColumn('mentor_sessions', 'competition_id')) {
            Schema::table('mentor_sessions', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // mentors
        if (Schema::hasColumn('mentors', 'competition_id')) {
            Schema::table('mentors', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // notification_management
        if (Schema::hasColumn('notification_management', 'competition_id')) {
            Schema::table('notification_management', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // projects
        if (Schema::hasColumn('projects', 'competition_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // registration_evaluation_forms
        if (Schema::hasColumn('registration_evaluation_forms', 'competition_id')) {
            Schema::table('registration_evaluation_forms', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // registration_evaluations: competition_application_id → program_application_id
        if (Schema::hasColumn('registration_evaluations', 'competition_application_id')) {
            Schema::table('registration_evaluations', function (Blueprint $table) {
                $table->renameColumn('competition_application_id', 'program_application_id');
            });
        }

        // registration_evaluators
        if (Schema::hasColumn('registration_evaluators', 'competition_id')) {
            Schema::table('registration_evaluators', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // registration_form_configs
        if (Schema::hasColumn('registration_form_configs', 'competition_id')) {
            Schema::table('registration_form_configs', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // satisfactions
        if (Schema::hasColumn('satisfactions', 'competition_id')) {
            Schema::table('satisfactions', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // stages
        if (Schema::hasColumn('stages', 'competition_id')) {
            Schema::table('stages', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // task_assignments
        if (Schema::hasColumn('task_assignments', 'competition_id')) {
            Schema::table('task_assignments', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // task_templates
        if (Schema::hasColumn('task_templates', 'competition_id')) {
            Schema::table('task_templates', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // team_form_configs
        if (Schema::hasColumn('team_form_configs', 'competition_id')) {
            Schema::table('team_form_configs', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // tracks
        if (Schema::hasColumn('tracks', 'competition_id')) {
            Schema::table('tracks', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // winners
        if (Schema::hasColumn('winners', 'competition_id')) {
            Schema::table('winners', function (Blueprint $table) {
                $table->renameColumn('competition_id', 'program_id');
            });
        }

        // application_comments references competition_applications
        // The foreign key itself points to program_applications now (table was renamed)
        // No column rename needed here

        // program_approval_requests already has program_id column - no change needed

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Rename columns back
        $columnRenames = [
            'program_applications' => [['program_id', 'competition_id']],
            'program_judge' => [['program_id', 'competition_id']],
            'program_labels' => [['program_id', 'competition_id']],
            'program_tabs' => [['program_id', 'competition_id']],
            'branding_programs' => [['program_id', 'competition_id']],
            'user_programs' => [['program_id', 'competition_id']],
            'mentor_programs' => [['program_id', 'competition_id']],
            'committees' => [['program_id', 'competition_id']],
            'dashboards' => [['program_id', 'competition_id']],
            'evaluation_stage_configs' => [['program_id', 'competition_id']],
            'events' => [['program_id', 'competition_id']],
            'forms' => [['program_id', 'competition_id']],
            'guidelines' => [['program_id', 'competition_id']],
            'mentor_participant' => [['program_id', 'competition_id']],
            'mentor_sessions' => [['program_id', 'competition_id']],
            'mentors' => [['program_id', 'competition_id']],
            'notification_management' => [['program_id', 'competition_id']],
            'projects' => [['program_id', 'competition_id']],
            'registration_evaluation_forms' => [['program_id', 'competition_id']],
            'registration_evaluations' => [['program_application_id', 'competition_application_id']],
            'registration_evaluators' => [['program_id', 'competition_id']],
            'registration_form_configs' => [['program_id', 'competition_id']],
            'satisfactions' => [['program_id', 'competition_id']],
            'stages' => [['program_id', 'competition_id']],
            'task_assignments' => [['program_id', 'competition_id']],
            'task_templates' => [['program_id', 'competition_id']],
            'team_form_configs' => [['program_id', 'competition_id']],
            'tracks' => [['program_id', 'competition_id']],
            'winners' => [['program_id', 'competition_id']],
        ];

        foreach ($columnRenames as $table => $renames) {
            foreach ($renames as [$from, $to]) {
                if (Schema::hasColumn($table, $from)) {
                    Schema::table($table, function (Blueprint $blueprint) use ($from, $to) {
                        $blueprint->renameColumn($from, $to);
                    });
                }
            }
        }

        // Rename tables back
        Schema::rename('programs', 'competitions');
        Schema::rename('program_applications', 'competition_applications');
        Schema::rename('program_judge', 'competition_judge');
        Schema::rename('program_labels', 'competition_labels');
        Schema::rename('program_tabs', 'competition_tabs');
        Schema::rename('branding_programs', 'branding_competitions');
        Schema::rename('user_programs', 'user_competitions');
        Schema::rename('mentor_programs', 'mentor_competitions');

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
