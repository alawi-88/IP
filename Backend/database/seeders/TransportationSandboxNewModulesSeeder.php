<?php

namespace Database\Seeders;

use App\Models\CompetitionApplication;
use App\Models\Participant;
use App\Models\RegistrationEvaluationCriterion;
use App\Models\RegistrationEvaluationForm;
use App\Models\RegistrationEvaluator;
use App\Models\RegistrationEvaluatorSection;
use App\Models\RegistrationEvaluation;
use App\Models\TaskAssignment;
use App\Models\TaskComment;
use App\Models\TaskSubmission;
use App\Models\TaskTemplate;
use App\Services\RegistrationEvaluationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransportationSandboxNewModulesSeeder extends Seeder
{
    private int $competitionId = 3;
    private int $adminUserId = 166;

    public function run(): void
    {
        $this->command->info('🚀 Populating Registration Evaluation & Task Management modules...');

        // Clear existing data for these modules (use delete to avoid FK constraint issues)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('task_comments')->truncate();
        DB::table('task_submissions')->truncate();
        DB::table('task_assignments')->truncate();
        DB::table('task_templates')->truncate();
        DB::table('registration_evaluations')->truncate();
        DB::table('registration_evaluator_sections')->truncate();
        DB::table('registration_evaluators')->truncate();
        DB::table('registration_evaluation_criteria')->truncate();
        DB::table('registration_evaluation_forms')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Reset application review fields
        DB::table('competition_applications')
            ->where('competition_id', $this->competitionId)
            ->update([
                'final_evaluation_score' => null,
                'minimum_score_threshold' => null,
                'decision_reason' => null,
                'editable_fields' => null,
                'edit_notes' => null,
                'edit_requested_at' => null,
                'resubmitted_at' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);

        // Remove old notifications for these types
        DB::table('notifications')
            ->whereIn('type', [
                'App\\Notifications\\Participant\\RegistrationDecisionNotification',
                'App\\Notifications\\Participant\\EditRequestNotification',
                'App\\Notifications\\Participant\\TaskAssignedNotification',
                'App\\Notifications\\Participant\\TaskStatusChangedNotification',
            ])->delete();

        // ─────────────────────────────────────────────────────────────
        // 1. REGISTRATION EVALUATION FORMS & CRITERIA
        // ─────────────────────────────────────────────────────────────
        $this->command->info('📋 Creating registration evaluation forms and criteria...');

        // Form 1: Technical Assessment
        $form1 = RegistrationEvaluationForm::create([
            'competition_id' => $this->competitionId,
            'name' => ['en' => 'Technical Assessment', 'ar' => 'التقييم الفني'],
            'description' => ['en' => 'Evaluate technical readiness and innovation of the applicant', 'ar' => 'تقييم الجاهزية الفنية والابتكار لدى المتقدم'],
            'dimension' => 'Technical',
            'scoring_scale' => '1-10',
            'status' => 'published',
            'sort_order' => 1,
        ]);

        $criteria1 = [];
        $criteriaData1 = [
            ['en' => 'Technology Innovation', 'ar' => 'ابتكار التقنية', 'weight' => 30, 'desc_en' => 'Novelty and uniqueness of the technology solution', 'desc_ar' => 'حداثة وتفرد الحل التقني'],
            ['en' => 'Technical Feasibility', 'ar' => 'الجدوى الفنية', 'weight' => 25, 'desc_en' => 'Ability to implement and scale the solution', 'desc_ar' => 'القدرة على تنفيذ وتوسيع الحل'],
            ['en' => 'Team Expertise', 'ar' => 'خبرة الفريق', 'weight' => 25, 'desc_en' => 'Technical skills and experience of the team', 'desc_ar' => 'المهارات الفنية وخبرة الفريق'],
            ['en' => 'Infrastructure Readiness', 'ar' => 'جاهزية البنية التحتية', 'weight' => 20, 'desc_en' => 'Existing infrastructure and deployment readiness', 'desc_ar' => 'البنية التحتية الحالية وجاهزية النشر'],
        ];

        foreach ($criteriaData1 as $cd) {
            $criteria1[] = RegistrationEvaluationCriterion::create([
                'registration_evaluation_form_id' => $form1->id,
                'name' => ['en' => $cd['en'], 'ar' => $cd['ar']],
                'description' => ['en' => $cd['desc_en'], 'ar' => $cd['desc_ar']],
                'max_score' => 10,
                'weight' => $cd['weight'],
            ]);
        }

        // Form 2: Business Viability
        $form2 = RegistrationEvaluationForm::create([
            'competition_id' => $this->competitionId,
            'name' => ['en' => 'Business Viability', 'ar' => 'الجدوى التجارية'],
            'description' => ['en' => 'Assess business model strength and market potential', 'ar' => 'تقييم قوة نموذج العمل وإمكانات السوق'],
            'dimension' => 'Business',
            'scoring_scale' => '1-10',
            'status' => 'published',
            'sort_order' => 2,
        ]);

        $criteria2 = [];
        $criteriaData2 = [
            ['en' => 'Market Opportunity', 'ar' => 'فرصة السوق', 'weight' => 35, 'desc_en' => 'Size and accessibility of target market', 'desc_ar' => 'حجم السوق المستهدف وإمكانية الوصول'],
            ['en' => 'Revenue Model', 'ar' => 'نموذج الإيرادات', 'weight' => 30, 'desc_en' => 'Sustainability and scalability of revenue streams', 'desc_ar' => 'استدامة وقابلية التوسع في مصادر الإيرادات'],
            ['en' => 'Regulatory Compliance', 'ar' => 'الامتثال التنظيمي', 'weight' => 35, 'desc_en' => 'Compliance with TGA and government regulations', 'desc_ar' => 'الامتثال للوائح هيئة النقل والجهات الحكومية'],
        ];

        foreach ($criteriaData2 as $cd) {
            $criteria2[] = RegistrationEvaluationCriterion::create([
                'registration_evaluation_form_id' => $form2->id,
                'name' => ['en' => $cd['en'], 'ar' => $cd['ar']],
                'description' => ['en' => $cd['desc_en'], 'ar' => $cd['desc_ar']],
                'max_score' => 10,
                'weight' => $cd['weight'],
            ]);
        }

        $this->command->info("   ✅ Created 2 evaluation forms with 7 total criteria");

        // ─────────────────────────────────────────────────────────────
        // 2. EVALUATORS & ASSIGNMENTS
        // ─────────────────────────────────────────────────────────────
        $this->command->info('👤 Creating evaluators and assigning sections...');

        $evaluator1 = RegistrationEvaluator::create([
            'competition_id' => $this->competitionId,
            'user_id' => $this->adminUserId,
            'is_active' => true,
        ]);

        // Assign evaluator to both forms
        RegistrationEvaluatorSection::create([
            'registration_evaluator_id' => $evaluator1->id,
            'registration_evaluation_form_id' => $form1->id,
        ]);
        RegistrationEvaluatorSection::create([
            'registration_evaluator_id' => $evaluator1->id,
            'registration_evaluation_form_id' => $form2->id,
        ]);

        $this->command->info("   ✅ Created 1 evaluator assigned to 2 forms");

        // ─────────────────────────────────────────────────────────────
        // 3. EVALUATIONS (score approved applications)
        // ─────────────────────────────────────────────────────────────
        $this->command->info('📊 Scoring approved applications...');

        $approvedApps = CompetitionApplication::where('competition_id', $this->competitionId)
            ->where('status', 'approved')
            ->get();

        $allCriteria = array_merge($criteria1, $criteria2);

        $scoreProfiles = [
            // High performers
            ['min' => 8, 'max' => 10],
            ['min' => 7, 'max' => 9],
            ['min' => 8, 'max' => 10],
            ['min' => 7, 'max' => 9],
            // Mid performers
            ['min' => 6, 'max' => 8],
            ['min' => 5, 'max' => 8],
            ['min' => 6, 'max' => 9],
            // Lower performers
            ['min' => 4, 'max' => 7],
            ['min' => 5, 'max' => 7],
            ['min' => 6, 'max' => 8],
        ];

        foreach ($approvedApps as $idx => $app) {
            $profile = $scoreProfiles[$idx % count($scoreProfiles)];

            foreach ($allCriteria as $criterion) {
                RegistrationEvaluation::create([
                    'registration_evaluator_id' => $evaluator1->id,
                    'competition_application_id' => $app->id,
                    'registration_evaluation_form_id' => $criterion->registration_evaluation_form_id,
                    'registration_evaluation_criterion_id' => $criterion->id,
                    'score' => rand($profile['min'], $profile['max']),
                    'comment' => null,
                ]);
            }
        }

        // Calculate and store final scores
        $evalService = app(RegistrationEvaluationService::class);
        foreach ($approvedApps as $app) {
            $finalScore = $evalService->updateApplicationScore($app->id);
        }

        $this->command->info("   ✅ Scored " . $approvedApps->count() . " applications across 7 criteria each");

        // ─────────────────────────────────────────────────────────────
        // 4. ADMIN REVIEW DECISIONS (varied statuses)
        // ─────────────────────────────────────────────────────────────
        $this->command->info('✅ Setting admin review decisions on some applications...');

        // Leave first 6 as approved (already scored)
        // App 7 (index 6): rejected
        // App 8 (index 7): edit_requested
        // App 9-10 (index 8-9): approved with review

        $rejectedApps = CompetitionApplication::where('competition_id', $this->competitionId)
            ->where('status', 'approved')
            ->orderBy('id')
            ->skip(6)->take(1)->get();

        foreach ($rejectedApps as $app) {
            $app->update([
                'status' => 'rejected',
                'decision_reason' => 'Insufficient operational capacity and below-threshold evaluation scores. The applicant does not meet the minimum requirements for sandbox participation at this time.',
                'reviewed_by' => $this->adminUserId,
                'reviewed_at' => now()->subDays(3),
            ]);
        }

        $editRequestApps = CompetitionApplication::where('competition_id', $this->competitionId)
            ->where('status', 'approved')
            ->orderBy('id')
            ->skip(6)->take(1)->get();

        foreach ($editRequestApps as $app) {
            $app->update([
                'status' => 'edit_requested',
                'editable_fields' => json_encode(['business_overview', 'number_of_employees', 'number_of_saudi_employees']),
                'edit_notes' => json_encode([
                    'en' => 'Please update your business overview with more details about your regulatory compliance plan. Also verify employee counts as they seem outdated.',
                    'ar' => 'يرجى تحديث نظرة عامة على عملك بمزيد من التفاصيل حول خطة الامتثال التنظيمي. تحقق أيضًا من أعداد الموظفين لأنها تبدو قديمة.',
                ]),
                'edit_requested_at' => now()->subDays(2),
                'reviewed_by' => $this->adminUserId,
                'reviewed_at' => now()->subDays(2),
            ]);
        }

        // Mark last 2 approved apps as reviewed
        $reviewedApps = CompetitionApplication::where('competition_id', $this->competitionId)
            ->where('status', 'approved')
            ->orderBy('id', 'desc')
            ->take(2)->get();

        foreach ($reviewedApps as $app) {
            $app->update([
                'reviewed_by' => $this->adminUserId,
                'reviewed_at' => now()->subDays(rand(1, 5)),
            ]);
        }

        $this->command->info("   ✅ Set 1 rejected, 1 edit_requested, 2 reviewed-approved");

        // ─────────────────────────────────────────────────────────────
        // 5. TASK TEMPLATES
        // ─────────────────────────────────────────────────────────────
        $this->command->info('📝 Creating task templates...');

        $stages = DB::table('stages')->where('competition_id', $this->competitionId)->pluck('id', 'slug');

        $templates = [
            [
                'title' => ['en' => 'Business Model Canvas Submission', 'ar' => 'تقديم نموذج العمل التجاري'],
                'description' => ['en' => 'Submit a completed Business Model Canvas for your transportation innovation.', 'ar' => 'قدم نموذج العمل التجاري المكتمل لابتكارك في مجال النقل.'],
                'instructions' => ['en' => "1. Download the Business Model Canvas template\n2. Fill in all 9 sections\n3. Upload as PDF or DOCX\n4. Include supporting financial projections", 'ar' => "1. قم بتحميل قالب نموذج العمل التجاري\n2. املأ جميع الأقسام التسعة\n3. ارفع كملف PDF أو DOCX\n4. أرفق التوقعات المالية الداعمة"],
                'difficulty_level' => 'medium',
                'estimated_hours' => 8,
                'category' => 'Business Planning',
            ],
            [
                'title' => ['en' => 'Regulatory Compliance Checklist', 'ar' => 'قائمة التحقق من الامتثال التنظيمي'],
                'description' => ['en' => 'Complete the TGA regulatory compliance self-assessment checklist.', 'ar' => 'أكمل قائمة التقييم الذاتي للامتثال التنظيمي لهيئة النقل العام.'],
                'instructions' => ['en' => "1. Review TGA sandbox regulatory framework\n2. Complete each compliance item\n3. Attach supporting evidence for each requirement\n4. Submit with a compliance statement signed by your legal team", 'ar' => "1. راجع الإطار التنظيمي للصندوق الرملي لهيئة النقل\n2. أكمل كل عنصر امتثال\n3. أرفق الأدلة الداعمة لكل متطلب\n4. قدم مع بيان امتثال موقع من فريقك القانوني"],
                'difficulty_level' => 'hard',
                'estimated_hours' => 16,
                'category' => 'Compliance',
            ],
            [
                'title' => ['en' => 'Monthly KPI Report', 'ar' => 'تقرير مؤشرات الأداء الشهري'],
                'description' => ['en' => 'Submit monthly key performance indicators report.', 'ar' => 'قدم تقرير مؤشرات الأداء الرئيسية الشهري.'],
                'instructions' => ['en' => "1. Collect operational metrics from your systems\n2. Fill the KPI template with actual numbers\n3. Include analysis for any deviation from targets\n4. Add action plans for underperforming areas", 'ar' => "1. اجمع المقاييس التشغيلية من أنظمتك\n2. املأ قالب مؤشرات الأداء بالأرقام الفعلية\n3. أضف تحليلاً لأي انحراف عن الأهداف\n4. أضف خطط عمل للمجالات ذات الأداء المنخفض"],
                'difficulty_level' => 'easy',
                'estimated_hours' => 4,
                'category' => 'Reporting',
            ],
            [
                'title' => ['en' => 'Safety & Risk Assessment', 'ar' => 'تقييم السلامة والمخاطر'],
                'description' => ['en' => 'Conduct and submit a comprehensive safety and risk assessment for your operations.', 'ar' => 'أجرِ وقدم تقييمًا شاملاً للسلامة والمخاطر لعملياتك.'],
                'instructions' => ['en' => "1. Identify all operational risks\n2. Rate each risk by likelihood and impact\n3. Document mitigation strategies\n4. Include incident response procedures", 'ar' => "1. حدد جميع المخاطر التشغيلية\n2. صنف كل خطر حسب الاحتمالية والتأثير\n3. وثق استراتيجيات التخفيف\n4. أرفق إجراءات الاستجابة للحوادث"],
                'difficulty_level' => 'hard',
                'estimated_hours' => 12,
                'category' => 'Safety',
            ],
            [
                'title' => ['en' => 'Customer Feedback Analysis', 'ar' => 'تحليل ملاحظات العملاء'],
                'description' => ['en' => 'Analyze customer feedback data and submit improvement recommendations.', 'ar' => 'حلل بيانات ملاحظات العملاء وقدم توصيات للتحسين.'],
                'instructions' => ['en' => "1. Export customer feedback from your system\n2. Categorize feedback by theme\n3. Identify top 5 improvement areas\n4. Propose action items with timelines", 'ar' => "1. صدّر ملاحظات العملاء من نظامك\n2. صنف الملاحظات حسب الموضوع\n3. حدد أهم 5 مجالات للتحسين\n4. اقترح بنود عمل مع جداول زمنية"],
                'difficulty_level' => 'medium',
                'estimated_hours' => 6,
                'category' => 'Customer Experience',
            ],
        ];

        $templateIds = [];
        foreach ($templates as $t) {
            $template = TaskTemplate::create([
                'competition_id' => $this->competitionId,
                'form_id' => null,
                'title' => $t['title'],
                'description' => $t['description'],
                'instructions' => $t['instructions'],
                'difficulty_level' => $t['difficulty_level'],
                'estimated_hours' => $t['estimated_hours'],
                'category' => $t['category'],
                'version' => 1,
                'created_by' => $this->adminUserId,
                'is_archived' => false,
            ]);
            $templateIds[] = $template->id;
        }

        $this->command->info("   ✅ Created " . count($templateIds) . " task templates");

        // ─────────────────────────────────────────────────────────────
        // 6. TASK ASSIGNMENTS
        // ─────────────────────────────────────────────────────────────
        $this->command->info('📌 Creating task assignments...');

        // Get all teams for this competition (regardless of current app status since some were rejected/edit_requested above)
        $teams = DB::table('teams')
            ->join('competition_applications', 'teams.application_id', '=', 'competition_applications.id')
            ->where('competition_applications.competition_id', $this->competitionId)
            ->whereIn('competition_applications.status', ['approved', 'rejected', 'edit_requested'])
            ->select('teams.id as team_id', 'competition_applications.participant_id')
            ->get();

        $stageId = $stages['readiness-assessment'] ?? $stages->first();

        $assignmentCount = 0;
        $submissionCount = 0;
        $commentCount = 0;

        // Template 0 (Business Model Canvas) - Assign to ALL teams
        $allAssignment = TaskAssignment::create([
            'task_template_id' => $templateIds[0],
            'competition_id' => $this->competitionId,
            'stage_id' => $stageId,
            'assignment_type' => 'all',
            'team_id' => null,
            'participant_id' => null,
            'title' => ['en' => 'Business Model Canvas Submission', 'ar' => 'تقديم نموذج العمل التجاري'],
            'description' => ['en' => 'Submit your Business Model Canvas by the due date.', 'ar' => 'قدم نموذج العمل التجاري قبل تاريخ الاستحقاق.'],
            'instructions' => $templates[0]['instructions'],
            'due_date' => Carbon::now()->addDays(14)->toDateString(),
            'status' => 'not_started',
            'allowed_file_formats' => ['pdf', 'docx'],
            'max_file_size_mb' => 25,
            'assigned_by' => $this->adminUserId,
            'is_archived' => false,
        ]);
        $assignmentCount++;

        // Template 1 (Compliance) - Assign to each team individually with varied statuses
        foreach ($teams as $idx => $team) {
            $statuses = ['approved', 'submitted', 'in_progress', 'revision_requested', 'not_started', 'in_progress', 'submitted', 'approved'];
            $status = $statuses[$idx % count($statuses)];

            $assignment = TaskAssignment::create([
                'task_template_id' => $templateIds[1],
                'competition_id' => $this->competitionId,
                'stage_id' => $stageId,
                'assignment_type' => 'team',
                'team_id' => $team->team_id,
                'participant_id' => null,
                'title' => ['en' => 'Regulatory Compliance Checklist', 'ar' => 'قائمة التحقق من الامتثال التنظيمي'],
                'description' => ['en' => 'Complete and submit the regulatory compliance self-assessment.', 'ar' => 'أكمل وقدم التقييم الذاتي للامتثال التنظيمي.'],
                'instructions' => $templates[1]['instructions'],
                'due_date' => Carbon::now()->addDays(rand(-5, 21))->toDateString(),
                'status' => $status,
                'allowed_file_formats' => ['pdf', 'docx', 'xlsx'],
                'max_file_size_mb' => 25,
                'assigned_by' => $this->adminUserId,
                'submitted_at' => in_array($status, ['submitted', 'approved', 'revision_requested']) ? now()->subDays(rand(1, 5)) : null,
                'reviewed_at' => in_array($status, ['approved', 'revision_requested']) ? now()->subDays(rand(0, 2)) : null,
                'reviewed_by' => in_array($status, ['approved', 'revision_requested']) ? $this->adminUserId : null,
                'is_archived' => false,
            ]);
            $assignmentCount++;

            // Create submissions for submitted/approved/revision_requested tasks
            if (in_array($status, ['submitted', 'approved', 'revision_requested'])) {
                $submission = TaskSubmission::create([
                    'task_assignment_id' => $assignment->id,
                    'submitted_by' => $team->participant_id,
                    'form_submissions' => null,
                    'files' => [
                        ['name' => 'compliance-checklist-v1.pdf', 'path' => "task_submissions/{$assignment->id}/compliance-v1.pdf", 'size' => rand(100000, 500000), 'type' => 'application/pdf'],
                    ],
                    'notes' => 'Completed compliance self-assessment. All required documents attached.',
                    'version' => 1,
                    'status' => $status === 'approved' ? 'approved' : ($status === 'revision_requested' ? 'revision_requested' : 'submitted'),
                    'admin_feedback' => $status === 'revision_requested' ? 'Please provide more detail on Section 3 (Safety Protocol) and include the updated insurance certificate.' : ($status === 'approved' ? 'All compliance items verified. Excellent documentation.' : null),
                    'reviewed_by' => in_array($status, ['approved', 'revision_requested']) ? $this->adminUserId : null,
                    'reviewed_at' => in_array($status, ['approved', 'revision_requested']) ? now()->subDays(rand(0, 2)) : null,
                    'submitted_at' => now()->subDays(rand(2, 7)),
                ]);
                $submissionCount++;

                // If revision_requested, add a v2 submission
                if ($status === 'revision_requested') {
                    TaskSubmission::create([
                        'task_assignment_id' => $assignment->id,
                        'submitted_by' => $team->participant_id,
                        'form_submissions' => null,
                        'files' => [
                            ['name' => 'compliance-checklist-v2.pdf', 'path' => "task_submissions/{$assignment->id}/compliance-v2.pdf", 'size' => rand(150000, 600000), 'type' => 'application/pdf'],
                            ['name' => 'insurance-certificate-updated.pdf', 'path' => "task_submissions/{$assignment->id}/insurance-cert.pdf", 'size' => rand(50000, 200000), 'type' => 'application/pdf'],
                        ],
                        'notes' => 'Updated Section 3 with detailed safety protocols and attached the updated insurance certificate as requested.',
                        'version' => 2,
                        'status' => 'submitted',
                        'submitted_at' => now()->subDays(1),
                    ]);
                    $submissionCount++;
                }
            }

            // Add comments for some tasks
            if (in_array($status, ['submitted', 'approved', 'revision_requested', 'in_progress'])) {
                // Admin comment
                TaskComment::create([
                    'task_assignment_id' => $assignment->id,
                    'commentable_type' => 'App\\Models\\User',
                    'commentable_id' => $this->adminUserId,
                    'body' => collect([
                        'Please ensure all compliance documents are up to date before the deadline.',
                        'Good progress on this task. Let us know if you need any clarification.',
                        'Remember to include the TGA-specific compliance forms in your submission.',
                        'The deadline is approaching. Please submit at your earliest convenience.',
                    ])->random(),
                    'is_internal' => false,
                ]);
                $commentCount++;

                // Participant comment
                if ($team->participant_id) {
                    TaskComment::create([
                        'task_assignment_id' => $assignment->id,
                        'commentable_type' => 'App\\Models\\Participant',
                        'commentable_id' => $team->participant_id,
                        'body' => collect([
                            'We are working on gathering the required documents. Will submit by the deadline.',
                            'Thank you for the feedback. We will update the submission accordingly.',
                            'We have a question about Section 5 requirements. Can you clarify?',
                            'Our legal team is reviewing the compliance items. Should be ready soon.',
                        ])->random(),
                        'is_internal' => false,
                    ]);
                    $commentCount++;
                }

                // Internal admin comment on some
                if (rand(0, 1)) {
                    TaskComment::create([
                        'task_assignment_id' => $assignment->id,
                        'commentable_type' => 'App\\Models\\User',
                        'commentable_id' => $this->adminUserId,
                        'body' => 'Internal note: This team needs close monitoring on compliance. Flag for follow-up next week.',
                        'is_internal' => true,
                    ]);
                    $commentCount++;
                }
            }
        }

        // Template 2 (Monthly KPI) - Assign to first 5 teams with some completed
        foreach ($teams->take(5) as $idx => $team) {
            $status = $idx < 3 ? 'approved' : 'in_progress';
            $assignment = TaskAssignment::create([
                'task_template_id' => $templateIds[2],
                'competition_id' => $this->competitionId,
                'stage_id' => $stageId,
                'assignment_type' => 'team',
                'team_id' => $team->team_id,
                'title' => ['en' => 'January KPI Report', 'ar' => 'تقرير مؤشرات الأداء - يناير'],
                'description' => ['en' => 'Submit January 2025 KPI report.', 'ar' => 'قدم تقرير مؤشرات الأداء لشهر يناير 2025.'],
                'instructions' => $templates[2]['instructions'],
                'due_date' => Carbon::create(2025, 2, 5)->toDateString(),
                'status' => $status,
                'assigned_by' => $this->adminUserId,
                'submitted_at' => $status === 'approved' ? now()->subDays(rand(10, 15)) : null,
                'reviewed_at' => $status === 'approved' ? now()->subDays(rand(5, 8)) : null,
                'reviewed_by' => $status === 'approved' ? $this->adminUserId : null,
                'is_archived' => false,
            ]);
            $assignmentCount++;

            if ($status === 'approved') {
                TaskSubmission::create([
                    'task_assignment_id' => $assignment->id,
                    'submitted_by' => $team->participant_id,
                    'files' => [
                        ['name' => 'kpi-report-jan-2025.xlsx', 'path' => "task_submissions/{$assignment->id}/kpi-jan.xlsx", 'size' => rand(50000, 200000), 'type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                    ],
                    'notes' => 'January 2025 KPI report. All targets met or exceeded.',
                    'version' => 1,
                    'status' => 'approved',
                    'admin_feedback' => 'KPIs look good. Strong performance across all metrics.',
                    'reviewed_by' => $this->adminUserId,
                    'reviewed_at' => now()->subDays(rand(5, 8)),
                    'submitted_at' => now()->subDays(rand(10, 15)),
                ]);
                $submissionCount++;
            }
        }

        // Template 3 (Safety Assessment) - Assign to specific participants
        $participants = Participant::whereIn('id', $teams->pluck('participant_id')->take(3))->get();
        foreach ($participants as $pIdx => $participant) {
            $status = $pIdx === 0 ? 'submitted' : 'not_started';
            $assignment = TaskAssignment::create([
                'task_template_id' => $templateIds[3],
                'competition_id' => $this->competitionId,
                'stage_id' => $stageId,
                'assignment_type' => 'participant',
                'participant_id' => $participant->id,
                'title' => ['en' => 'Safety & Risk Assessment', 'ar' => 'تقييم السلامة والمخاطر'],
                'description' => ['en' => 'Submit a comprehensive safety and risk assessment for your operations.', 'ar' => 'قدم تقييمًا شاملاً للسلامة والمخاطر لعملياتك.'],
                'instructions' => $templates[3]['instructions'],
                'due_date' => Carbon::now()->addDays(21)->toDateString(),
                'status' => $status,
                'assigned_by' => $this->adminUserId,
                'submitted_at' => $status === 'submitted' ? now()->subDays(1) : null,
                'is_archived' => false,
            ]);
            $assignmentCount++;

            if ($status === 'submitted') {
                TaskSubmission::create([
                    'task_assignment_id' => $assignment->id,
                    'submitted_by' => $participant->id,
                    'files' => [
                        ['name' => 'safety-risk-assessment.pdf', 'path' => "task_submissions/{$assignment->id}/safety-assessment.pdf", 'size' => rand(200000, 800000), 'type' => 'application/pdf'],
                    ],
                    'notes' => 'Comprehensive safety and risk assessment covering all operational areas.',
                    'version' => 1,
                    'status' => 'submitted',
                    'submitted_at' => now()->subDays(1),
                ]);
                $submissionCount++;
            }
        }

        // Template 4 (Customer Feedback) - One "all" assignment
        TaskAssignment::create([
            'task_template_id' => $templateIds[4],
            'competition_id' => $this->competitionId,
            'stage_id' => $stageId,
            'assignment_type' => 'all',
            'title' => ['en' => 'Q1 Customer Feedback Analysis', 'ar' => 'تحليل ملاحظات العملاء للربع الأول'],
            'description' => ['en' => 'Analyze Q1 2025 customer feedback and submit recommendations.', 'ar' => 'حلل ملاحظات العملاء للربع الأول 2025 وقدم توصيات.'],
            'instructions' => $templates[4]['instructions'],
            'due_date' => Carbon::now()->addDays(30)->toDateString(),
            'status' => 'not_started',
            'assigned_by' => $this->adminUserId,
            'is_archived' => false,
        ]);
        $assignmentCount++;

        $this->command->info("   ✅ Created {$assignmentCount} task assignments");
        $this->command->info("   ✅ Created {$submissionCount} task submissions");
        $this->command->info("   ✅ Created {$commentCount} task comments");

        // ─────────────────────────────────────────────────────────────
        // SUMMARY
        // ─────────────────────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('🎉 New Modules Demo Data Complete!');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info("   📋 Evaluation Forms: 2 (7 criteria total)");
        $this->command->info("   👤 Evaluators: 1 (assigned to 2 forms)");
        $this->command->info("   📊 Evaluations: " . ($approvedApps->count() * 7) . " scores");
        $this->command->info("   ✅ Review Decisions: 1 rejected, 1 edit_requested");
        $this->command->info("   📝 Task Templates: " . count($templateIds));
        $this->command->info("   📌 Task Assignments: {$assignmentCount}");
        $this->command->info("   📄 Task Submissions: {$submissionCount}");
        $this->command->info("   💬 Task Comments: {$commentCount}");
        $this->command->info('═══════════════════════════════════════════════════');
    }
}
