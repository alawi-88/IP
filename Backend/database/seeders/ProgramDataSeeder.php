<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\ProgramLabel;
use App\Models\EvaluationStageConfig;
use App\Models\Form;
use App\Models\FormAiScoringConfig;
use App\Models\ProjectFormConfig;
use App\Models\RegistrationFormConfig;
use App\Models\TeamFormConfig;
use Illuminate\Database\Seeder;

class ProgramDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding program data...');

        // ─── Program 1: Saudi Innovation Hackathon 2025 ───
        // Already has: reg config (both), team config, eval config (1 stage), project config, AI scoring, eval form
        // Just needs labels
        $this->seedLabelsFor(1);
        $this->command->info('  ✓ Program 1: Labels seeded');

        // ─── Program 2: TGA Regulatory Sandbox 2026 ───
        // Has: reg config (individual), project configs (forms 11-12)
        // Needs: eval form, eval config (2 stages), team config, AI scoring, labels
        $this->seedProgram2();

        // ─── Program 3: Transportation Sandbox 2025 ───
        // Has: reg config (individual), project configs (many monthly forms), AI scoring, reg eval forms
        // Needs: eval form, eval config (3 stages for multi-stage), labels
        $this->seedProgram3();

        // ─── Program 5: Naql Thon 5 ───
        // Has: reg config (both), team config, project config (form 24), AI scoring, reg eval forms
        // Needs: eval form, eval config (2 stages), labels
        $this->seedProgram5();

        $this->command->info('Done seeding all program data.');
    }

    private function seedProgram2(): void
    {
        $comp = Program::find(2);
        if (!$comp) return;

        // Create evaluation form
        $evalForm = Form::firstOrCreate(
            ['program_id' => 2, 'type' => 'evaluation', 'name->en' => 'Regulatory Compliance Evaluation'],
            [
                'program_id' => 2,
                'type' => 'evaluation',
                'name' => ['en' => 'Regulatory Compliance Evaluation', 'ar' => 'تقييم الامتثال التنظيمي'],
                'description' => ['en' => 'Evaluate sandbox proposals for regulatory compliance and innovation potential.', 'ar' => 'تقييم مقترحات الساندبوكس للامتثال التنظيمي وإمكانات الابتكار.'],
                'is_published' => true,
                'is_archived' => false,
            ]
        );

        // Create 2-stage evaluation config
        EvaluationStageConfig::firstOrCreate(
            ['program_id' => 2],
            [
                'program_id' => 2,
                'number_of_stages' => 2,
                'stages' => [
                    [
                        'stage_number' => 1,
                        'evaluation_form_id' => $evalForm->id,
                        'apply_to_all_tracks' => true,
                        'track_ids' => [],
                        'submission_requirement' => 'new',
                    ],
                    [
                        'stage_number' => 2,
                        'evaluation_form_id' => $evalForm->id,
                        'apply_to_all_tracks' => true,
                        'track_ids' => [],
                        'submission_requirement' => 'previous',
                    ],
                ],
                'is_active' => true,
            ]
        );

        // Add team config (individual only for sandbox, but add optional team)
        TeamFormConfig::firstOrCreate(
            ['program_id' => 2],
            [
                'program_id' => 2,
                'min_team_members' => 1,
                'max_team_members' => 3,
                'allow_track_selection' => false,
                'require_same_track' => false,
                'auto_publish_teams' => true,
                'is_active' => true,
            ]
        );

        $this->seedLabelsFor(2);
        $this->command->info('  ✓ Program 2: Eval form, 2-stage config, team config, labels seeded');
    }

    private function seedProgram3(): void
    {
        $comp = Program::find(3);
        if (!$comp) return;

        // Create evaluation forms (2 different ones for multi-stage)
        $evalForm1 = Form::firstOrCreate(
            ['program_id' => 3, 'type' => 'evaluation', 'name->en' => 'Monthly Performance Review'],
            [
                'program_id' => 3,
                'type' => 'evaluation',
                'name' => ['en' => 'Monthly Performance Review', 'ar' => 'مراجعة الأداء الشهري'],
                'description' => ['en' => 'Monthly evaluation of sandbox participant progress and compliance.', 'ar' => 'تقييم شهري لتقدم المشاركين في الساندبوكس والامتثال.'],
                'is_published' => true,
                'is_archived' => false,
            ]
        );

        $evalForm2 = Form::firstOrCreate(
            ['program_id' => 3, 'type' => 'evaluation', 'name->en' => 'Final Sandbox Assessment'],
            [
                'program_id' => 3,
                'type' => 'evaluation',
                'name' => ['en' => 'Final Sandbox Assessment', 'ar' => 'التقييم النهائي للساندبوكس'],
                'description' => ['en' => 'Comprehensive final evaluation including demo and business viability.', 'ar' => 'تقييم نهائي شامل يتضمن العرض التوضيحي وجدوى الأعمال.'],
                'is_published' => true,
                'is_archived' => false,
            ]
        );

        // Create 3-stage evaluation config (monthly → midterm → final)
        EvaluationStageConfig::firstOrCreate(
            ['program_id' => 3],
            [
                'program_id' => 3,
                'number_of_stages' => 3,
                'stages' => [
                    [
                        'stage_number' => 1,
                        'evaluation_form_id' => $evalForm1->id,
                        'apply_to_all_tracks' => true,
                        'track_ids' => [],
                        'submission_requirement' => 'new',
                    ],
                    [
                        'stage_number' => 2,
                        'evaluation_form_id' => $evalForm1->id,
                        'apply_to_all_tracks' => true,
                        'track_ids' => [],
                        'submission_requirement' => 'new',
                    ],
                    [
                        'stage_number' => 3,
                        'evaluation_form_id' => $evalForm2->id,
                        'apply_to_all_tracks' => true,
                        'track_ids' => [],
                        'submission_requirement' => 'new',
                    ],
                ],
                'is_active' => true,
            ]
        );

        $this->seedLabelsFor(3);
        $this->command->info('  ✓ Program 3: 2 eval forms, 3-stage config, labels seeded');
    }

    private function seedProgram5(): void
    {
        $comp = Program::find(5);
        if (!$comp) return;

        // Create evaluation forms (2 different stages: screening + final)
        $screeningForm = Form::firstOrCreate(
            ['program_id' => 5, 'type' => 'evaluation', 'name->en' => 'Naql Thon 5 Screening Evaluation'],
            [
                'program_id' => 5,
                'type' => 'evaluation',
                'name' => ['en' => 'Naql Thon 5 Screening Evaluation', 'ar' => 'تقييم الفرز - نقل ثون 5'],
                'description' => ['en' => 'Initial screening of hackathon submissions for technical feasibility and innovation.', 'ar' => 'فرز أولي لمشاريع الهاكاثون من حيث الجدوى التقنية والابتكار.'],
                'is_published' => true,
                'is_archived' => false,
            ]
        );

        $finalForm = Form::firstOrCreate(
            ['program_id' => 5, 'type' => 'evaluation', 'name->en' => 'Naql Thon 5 Final Judging'],
            [
                'program_id' => 5,
                'type' => 'evaluation',
                'name' => ['en' => 'Naql Thon 5 Final Judging', 'ar' => 'التحكيم النهائي - نقل ثون 5'],
                'description' => ['en' => 'Final judging round with demo presentation and business pitch evaluation.', 'ar' => 'جولة التحكيم النهائية مع تقييم العرض التقديمي والعرض التجاري.'],
                'is_published' => true,
                'is_archived' => false,
            ]
        );

        // Create 2-stage evaluation config (screening → final judging)
        EvaluationStageConfig::firstOrCreate(
            ['program_id' => 5],
            [
                'program_id' => 5,
                'number_of_stages' => 2,
                'stages' => [
                    [
                        'stage_number' => 1,
                        'evaluation_form_id' => $screeningForm->id,
                        'apply_to_all_tracks' => true,
                        'track_ids' => [],
                        'submission_requirement' => 'new',
                    ],
                    [
                        'stage_number' => 2,
                        'evaluation_form_id' => $finalForm->id,
                        'apply_to_all_tracks' => true,
                        'track_ids' => [],
                        'submission_requirement' => 'previous',
                    ],
                ],
                'is_active' => true,
            ]
        );

        // Labels already seeded for comp 5 via hub, but ensure defaults exist
        $this->seedLabelsFor(5);
        $this->command->info('  ✓ Program 5: 2 eval forms, 2-stage config, labels seeded');
    }

    private function seedLabelsFor(int $programId): void
    {
        ProgramLabel::seedDefaults($programId);
    }
}
