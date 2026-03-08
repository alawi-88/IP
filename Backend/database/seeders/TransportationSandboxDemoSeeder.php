<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\ProgramApplication;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Judge;
use App\Models\Mentor;
use App\Models\Participant;
use App\Models\Project;
use App\Models\Stage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TransportationSandboxDemoSeeder extends Seeder
{
    private int $programId = 3;
    private int $regFormId = 10;

    public function run(): void
    {
        $this->command->info('🚀 Starting Transportation Sandbox 2025 Demo Data Population...');

        // ─────────────────────────────────────────────────────────────
        // 1. CREATE 15 PARTICIPANTS (realistic Saudi transport companies)
        // ─────────────────────────────────────────────────────────────
        $this->command->info('📋 Creating 15 participant profiles...');

        $companies = $this->getCompanyProfiles();
        $participantIds = [];

        // Find the max serial number
        $maxSerial = (int) Participant::max('serial_number') ?: 100004;

        foreach ($companies as $i => $company) {
            // Check if participant already exists
            $existing = Participant::where('email', $company['email'])->first();
            if ($existing) {
                $participantIds[$i] = $existing->id;
                continue;
            }

            $serial = (string) ($maxSerial + $i + 1);
            $participantIds[$i] = DB::table('participants')->insertGetId([
                'serial_number'          => $serial,
                'name'                   => $company['founder_name'],
                'email'                  => $company['email'],
                'phone'                  => $company['phone'],
                'gender'                 => $company['gender'],
                'date_of_birth'          => $company['dob'],
                'nationality_id'         => 1, // Saudi
                'country_id'             => 1, // Saudi Arabia
                'residence_city_id'      => $company['city_id'],
                'password'               => Hash::make('password123'),
                'educational_background' => $company['education'],
                'current_role'           => $company['role'],
                'place_of_work_study'    => $company['company_name'],
                'years_of_experience'    => $company['experience'],
                'experience_or_skills'   => $company['skills'],
                'key_achievements'       => $company['achievements'],
                'activation_code'        => Str::random(6),
                'is_active'              => true,
                'is_archived'            => false,
                'email_verified_at'      => now(),
                'last_login_at'          => now()->subDays(rand(1, 10)),
                'created_at'             => now()->subDays(rand(30, 60)),
                'updated_at'             => now(),
            ]);
        }

        $this->command->info("   ✅ Created " . count($participantIds) . " participants");

        // ─────────────────────────────────────────────────────────────
        // 2. CREATE 15 APPLICATIONS (10 approved, 5 rejected)
        // ─────────────────────────────────────────────────────────────
        $this->command->info('📝 Creating 15 program applications...');

        // Delete existing applications for this program (clean slate)
        ProgramApplication::where('program_id', $this->programId)->delete();

        $applicationIds = [];
        $approvedAppIds = [];
        $approvedParticipantMap = [];

        // Create all 15 applications (indices 0-14)
        // First 10 are APPROVED, last 5 are REJECTED
        for ($i = 0; $i < 15; $i++) {
            $company = $companies[$i];
            $isApproved = $i < 10; // First 10 approved, last 5 rejected
            $status = $isApproved ? 'approved' : 'rejected';

            $formSubmissions = $this->buildRegistrationFormSubmissions($company);

            $pId = $participantIds[$i];

            $appId = DB::table('program_applications')->insertGetId([
                'program_id'   => $this->programId,
                'form_id'          => $this->regFormId,
                'participant_id'   => $pId,
                'status'           => $status,
                'registered_as'    => 'individual',
                'has_team'         => false,
                'team_name'        => null,
                'team_logo'        => null,
                'team_serial'      => null,
                'form_submissions' => json_encode($formSubmissions),
                'type'             => 'submission',
                'is_archived'      => false,
                'assessment_scores' => $isApproved
                    ? json_encode($this->generateAssessmentScores($company, true))
                    : json_encode($this->generateAssessmentScores($company, false)),
                'total_score'       => $isApproved ? rand(72, 95) : rand(30, 55),
                'ai_evaluation_response' => json_encode($this->generateAiEvaluationResponse($company, $isApproved)),
                'ai_evaluated_at'  => now()->subDays(rand(5, 20)),
                'created_at'       => now()->subDays(rand(25, 55)),
                'updated_at'       => now()->subDays(rand(1, 10)),
            ]);

            $applicationIds[$i] = $appId;

            if ($isApproved) {
                $approvedAppIds[] = $appId;
                $approvedParticipantMap[$appId] = $i;
            }
        }

        $this->command->info("   ✅ Created 15 applications (10 approved + 5 rejected)");

        // ─────────────────────────────────────────────────────────────
        // 3. CREATE TEAMS FOR APPROVED APPLICATIONS
        // ─────────────────────────────────────────────────────────────
        $this->command->info('👥 Creating teams for approved applicants...');

        $teamIds = [];
        foreach ($approvedAppIds as $appId) {
            $idx = $approvedParticipantMap[$appId] ?? 0;
            $company = $companies[$idx];
            $pId = $idx === 0 ? 1 : $participantIds[$idx];

            $teamId = DB::table('teams')->insertGetId([
                'application_id'         => $appId,
                'name'                   => $company['company_name'],
                'logo'                   => null,
                'strength'               => 1,
                'track_id'               => null,
                'sub_track_id'           => null,
                'idea_description'       => $company['idea_description'],
                'previous_participation' => (bool) rand(0, 1),
                'contact_email'          => $company['email'],
                'skills'                 => json_encode($company['team_skills']),
                'is_published'           => true,
                'is_completed'           => true,
                'is_archived'            => false,
                'created_at'             => now()->subDays(rand(20, 40)),
                'updated_at'             => now(),
            ]);

            $teamIds[$appId] = $teamId;

            // Add the participant as team leader
            DB::table('team_members')->updateOrInsert(
                ['team_id' => $teamId, 'participant_id' => $pId],
                [
                    'is_leader'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info("   ✅ Created " . count($teamIds) . " teams");

        // ─────────────────────────────────────────────────────────────
        // 4. CREATE PROJECTS WITH MONTHLY REPORT SUBMISSIONS
        // ─────────────────────────────────────────────────────────────
        $this->command->info('📊 Creating projects with monthly performance reports...');

        $projectFormIds = range(11, 22); // Jan-Dec monthly report forms
        $projectIds = [];

        foreach ($approvedAppIds as $appId) {
            $idx = $approvedParticipantMap[$appId] ?? 0;
            $company = $companies[$idx];
            $teamId = $teamIds[$appId];

            // Each approved company submits projects (monthly reports)
            // Simulate 3 months of reports submitted (Jan, Feb, Mar)
            $submittedMonths = min(3, count($projectFormIds));

            for ($m = 0; $m < $submittedMonths; $m++) {
                $formId = $projectFormIds[$m];
                $monthName = Carbon::create(2025, $m + 1, 1)->format('F');
                $projectData = $this->buildMonthlyReportSubmission($company, $m + 1, $formId);

                // Determine project status based on quality
                $statuses = ['qualified', 'qualified', 'qualified', 'qualified', 'qualified',
                             'qualified', 'qualified', 'pending', 'not_qualified', 'qualified'];
                $status = $statuses[$idx % count($statuses)] ?? 'pending';

                // For month 3, keep some pending
                if ($m == 2) {
                    $status = (rand(0, 1) == 0) ? 'pending' : $status;
                }

                $totalScore = match ($status) {
                    'qualified' => rand(70, 98),
                    'pending'   => 0,
                    'not_qualified' => rand(30, 55),
                    default     => 0,
                };

                $projectId = DB::table('projects')->insertGetId([
                    'program_id'   => $this->programId,
                    'application_id'   => $appId,
                    'team_id'          => $teamId,
                    'form_id'          => $formId,
                    'status'           => $status,
                    'evaluation_status' => $status !== 'pending',
                    'total_score'      => $totalScore,
                    'type'             => 'submission',
                    'form_submissions' => json_encode($projectData),
                    'ai_evaluation_response' => ($status !== 'pending')
                        ? json_encode($this->generateProjectAiEvaluation($company, $totalScore, $monthName))
                        : null,
                    'ai_evaluated_at'  => ($status !== 'pending') ? now()->subDays(rand(1, 15)) : null,
                    'is_archived'      => false,
                    'created_at'       => Carbon::create(2025, $m + 1, rand(25, 28)),
                    'updated_at'       => now(),
                ]);

                $projectIds[] = $projectId;
            }
        }

        $this->command->info("   ✅ Created " . count($projectIds) . " project submissions (monthly reports)");

        // ─────────────────────────────────────────────────────────────
        // 5. ASSIGN JUDGES TO COMPETITION AND PROJECTS
        // ─────────────────────────────────────────────────────────────
        $this->command->info('⚖️  Setting up judge evaluations...');

        // Create 3 new judges specific to transportation
        $transportJudges = [
            [
                'name' => json_encode(['en' => 'Dr. Abdullah Al-Ghamdi', 'ar' => 'د. عبدالله الغامدي']),
                'email' => 'abdullah.judge@tga.gov.sa',
                'phone_number' => '0551234001',
                'experience_field' => json_encode(['en' => 'Transportation Policy & Regulation, 20+ years with TGA', 'ar' => 'سياسات النقل والتنظيم، أكثر من 20 سنة مع هيئة النقل']),
            ],
            [
                'name' => json_encode(['en' => 'Eng. Maha Al-Qahtani', 'ar' => 'م. مها القحطاني']),
                'email' => 'maha.judge@transport.sa',
                'phone_number' => '0551234002',
                'experience_field' => json_encode(['en' => 'Smart Mobility & Urban Planning, Former Director at NEOM Transport', 'ar' => 'التنقل الذكي والتخطيط العمراني، مديرة سابقة في نقل نيوم']),
            ],
            [
                'name' => json_encode(['en' => 'Dr. Turki Al-Harbi', 'ar' => 'د. تركي الحربي']),
                'email' => 'turki.judge@kacst.sa',
                'phone_number' => '0551234003',
                'experience_field' => json_encode(['en' => 'Autonomous Vehicles & AI in Transportation, KACST Research Fellow', 'ar' => 'المركبات ذاتية القيادة والذكاء الاصطناعي في النقل، باحث في مدينة الملك عبدالعزيز']),
            ],
        ];

        $judgeIds = [];
        foreach ($transportJudges as $jd) {
            // Check if judge exists
            $existing = Judge::where('email', $jd['email'])->first();
            if ($existing) {
                $judgeIds[] = $existing->id;
                continue;
            }

            $judgeIds[] = DB::table('judges')->insertGetId([
                'name'                => $jd['name'],
                'email'               => $jd['email'],
                'phone_number'        => $jd['phone_number'],
                'experience_field'    => $jd['experience_field'],
                'password'            => Hash::make('password123'),
                'registration_method' => 'admin-added',
                'email_verified_at'   => now(),
                'is_archived'         => false,
                'created_at'          => now()->subDays(30),
                'updated_at'          => now(),
            ]);
        }

        // Link judges to program
        foreach ($judgeIds as $jid) {
            DB::table('program_judge')->updateOrInsert(
                ['program_id' => $this->programId, 'judge_id' => $jid],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Assign judges to projects and create evaluations
        foreach ($projectIds as $projectId) {
            $project = Project::find($projectId);
            if (!$project || $project->status === 'pending') continue;

            foreach ($judgeIds as $jid) {
                $score = $project->total_score + rand(-8, 8);
                $score = max(20, min(100, $score));

                $jpId = DB::table('judge_projects')->insertGetId([
                    'judge_id'                => $jid,
                    'project_id'              => $projectId,
                    'evaluation_score'        => $score,
                    'disclaimer_accepted'     => true,
                    'disclaimer_accepted_at'  => now()->subDays(rand(1, 10)),
                    'final_comment'           => $this->generateJudgeComment($score),
                    'created_at'              => now()->subDays(rand(1, 10)),
                    'updated_at'              => now(),
                ]);

                // Create individual evaluation criteria answers
                $evalCriteria = [
                    ['question' => 'Regulatory Compliance', 'weight' => 25],
                    ['question' => 'Operational Performance', 'weight' => 25],
                    ['question' => 'Customer Satisfaction & Safety', 'weight' => 25],
                    ['question' => 'Financial Viability', 'weight' => 25],
                ];

                $evalStageId = Stage::where('program_id', $this->programId)
                    ->where('slug', 'evaluation')->value('id');

                foreach ($evalCriteria as $ec) {
                    $critScore = ($score / 100) * $ec['weight'] + rand(-3, 3);
                    $critScore = max(0, min($ec['weight'], round($critScore)));

                    DB::table('project_evaluations')->insert([
                        'judge_project_id' => $jpId,
                        'form_id'          => null,
                        'stage_id'         => $evalStageId,
                        'question'         => $ec['question'],
                        'answer'           => $critScore,
                        'comment'          => $this->generateCriterionComment($ec['question'], $critScore, $ec['weight']),
                        'weight'           => $ec['weight'],
                        'is_archived'      => false,
                        'created_at'       => now()->subDays(rand(1, 10)),
                        'updated_at'       => now(),
                    ]);
                }
            }
        }

        $this->command->info("   ✅ Created 3 judges with evaluations on all qualified projects");

        // ─────────────────────────────────────────────────────────────
        // 6. CONFIGURE AI EVALUATION FOR REGISTRATION FORM
        // ─────────────────────────────────────────────────────────────
        $this->command->info('🤖 Configuring AI evaluation for registration form...');

        // Check if config exists
        $existingConfig = DB::table('form_ai_scoring_configs')->where('form_id', $this->regFormId)->first();
        if (!$existingConfig) {
            DB::table('form_ai_scoring_configs')->insert([
                'form_id'      => $this->regFormId,
                'ai_prompt'    => 'You are an expert regulatory sandbox evaluator for the Saudi General Transport Authority (TGA). Evaluate this company\'s application to join the Transportation Regulatory Sandbox 2025. Assess their readiness to test innovative transportation business models under regulatory supervision. Consider their business viability, regulatory compliance readiness, technology innovation, operational capacity, and potential benefit to the Saudi transport ecosystem aligned with Vision 2030.',
                'total_weight' => 100,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            $this->command->info("   ✅ Created AI scoring config for registration form");
        }

        // Create assessment criteria for registration form
        $regCriteria = [
            [
                'name'        => 'Business Model Innovation',
                'description' => 'Novelty and innovation of the transportation business model',
                'instruction' => 'Evaluate how innovative the business model is for Saudi Arabia\'s transport sector. Consider if this model exists elsewhere, what unique value it brings, and alignment with TGA\'s vision for transport modernization.',
                'weight'      => 25,
            ],
            [
                'name'        => 'Regulatory Readiness',
                'description' => 'Preparedness to operate within a regulatory sandbox framework',
                'instruction' => 'Assess the company\'s understanding of regulatory requirements, their compliance history, exit plan quality, and risk mitigation strategies. Check if they have valid commercial registration and necessary documentation.',
                'weight'      => 25,
            ],
            [
                'name'        => 'Operational Capacity',
                'description' => 'Technical and operational capability to execute the business model',
                'instruction' => 'Evaluate the company\'s team strength, technology infrastructure, operational plan feasibility, office presence in the Kingdom, and Saudization ratio. Consider employee count and experience.',
                'weight'      => 25,
            ],
            [
                'name'        => 'Market Impact & Beneficiary Protection',
                'description' => 'Potential positive impact on Saudi transport market and user safety',
                'instruction' => 'Assess the potential number of beneficiaries, quality of beneficiary protection policies, pricing fairness, and overall contribution to improving transportation services in the Kingdom.',
                'weight'      => 25,
            ],
        ];

        foreach ($regCriteria as $sort => $rc) {
            $existing = DB::table('form_assessment_criteria')
                ->where('form_id', $this->regFormId)
                ->where('name', $rc['name'])
                ->first();

            if (!$existing) {
                $criterionId = DB::table('form_assessment_criteria')->insertGetId([
                    'form_id'     => $this->regFormId,
                    'name'        => $rc['name'],
                    'description' => $rc['description'],
                    'instruction' => $rc['instruction'],
                    'weight'      => $rc['weight'],
                    'status'      => 'active',
                    'sort_order'  => $sort + 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                // Map relevant form fields to this criterion
                $fieldMappings = $this->getCriterionFieldMappings($rc['name']);
                foreach ($fieldMappings as $slug) {
                    $field = FormField::where('form_id', $this->regFormId)->where('slug', $slug)->first();
                    if ($field) {
                        DB::table('form_assessment_criterion_form_field')->insert([
                            'form_assessment_criterion_id' => $criterionId,
                            'form_field_id'                => $field->id,
                            'created_at'                   => now(),
                            'updated_at'                   => now(),
                        ]);
                    }
                }
            }
        }

        $this->command->info("   ✅ Created 4 assessment criteria with field mappings");

        // ─────────────────────────────────────────────────────────────
        // 7. ASSIGN MENTORS TO TEAMS
        // ─────────────────────────────────────────────────────────────
        $this->command->info('🎓 Assigning mentors to teams...');

        $mentorIds = Mentor::whereHas('programs', function ($q) {
            $q->where('program_id', $this->programId);
        })->pluck('id')->toArray();

        // Also check via direct program_id
        $directMentors = Mentor::where('program_id', $this->programId)->pluck('id')->toArray();
        $mentorIds = array_unique(array_merge($mentorIds, $directMentors));

        if (!empty($mentorIds)) {
            $teamCount = 0;
            foreach ($teamIds as $teamId) {
                // Assign 1-2 mentors per team
                $assignedMentors = array_slice($mentorIds, $teamCount % count($mentorIds), rand(1, min(2, count($mentorIds))));
                foreach ($assignedMentors as $mid) {
                    DB::table('mentor_team')->updateOrInsert(
                        ['mentor_id' => $mid, 'team_id' => $teamId],
                        [
                            'assigned_by' => DB::table('users')->min('id'),
                            'assigned_at' => now()->subDays(rand(5, 15)),
                            'notes'       => 'Assigned for Transportation Sandbox mentorship',
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]
                    );
                }
                $teamCount++;
            }
            $this->command->info("   ✅ Assigned " . count($mentorIds) . " mentors across " . count($teamIds) . " teams");
        }

        // ─────────────────────────────────────────────────────────────
        // DONE
        // ─────────────────────────────────────────────────────────────
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('🎉 Transportation Sandbox 2025 Demo Data Complete!');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info("   📋 Participants: 15");
        $this->command->info("   📝 Applications: 15 (10 approved, 5 rejected)");
        $this->command->info("   👥 Teams: " . count($teamIds));
        $this->command->info("   📊 Projects: " . count($projectIds) . " monthly reports");
        $this->command->info("   ⚖️  Judges: 3 with full evaluations");
        $this->command->info("   🎓 Mentors: " . count($mentorIds) . " assigned");
        $this->command->info("   🤖 AI Evaluation: Configured with 4 criteria");
        $this->command->info('═══════════════════════════════════════════════════');
    }

    // ═══════════════════════════════════════════════════════════════
    // DATA GENERATORS
    // ═══════════════════════════════════════════════════════════════

    private function getCompanyProfiles(): array
    {
        return [
            // ── COMPANY 0: Existing (Ahmed Saad) ──
            [
                'company_name'     => 'NaqlaHub',
                'founder_name'     => 'Ahmed Saad',
                'email'            => 'ahmed@test.com',
                'phone'            => '0501234567',
                'gender'           => 'male',
                'dob'              => '1990-03-15',
                'city_id'          => 1,
                'education'        => 'master',
                'role'             => 'private_sector_employee',
                'experience'       => 'five_to_ten',
                'skills'           => 'AI-powered route optimization, fleet management, mobile platform development, urban mobility planning',
                'achievements'     => 'Built an AI fleet management system serving 50K+ users in Riyadh. Winner of KAUST Innovation Award 2024.',
                'company_type'     => 'Private Company',
                'business_overview' => 'AI-powered ride-hailing and micro-mobility platform for sustainable urban transportation in Saudi cities.',
                'idea_description' => 'Smart urban mobility platform combining ride-hailing with e-scooter/bike sharing, using AI for demand prediction and dynamic routing.',
                'team_skills'      => ['AI/ML', 'Mobile Development', 'Fleet Management', 'Urban Planning'],
                'business_model'   => 'Ride-hailing & Micro-mobility',
                'employees'        => 57,
                'saudi_employees'  => 45,
                'beneficiaries'    => 50000,
            ],
            // ── COMPANY 1: Autonomous Logistics ──
            [
                'company_name'     => 'Wasl Logistics',
                'founder_name'     => 'Khalid Al-Dosari',
                'email'            => 'khalid.dosari@wasl.sa',
                'phone'            => '0551100201',
                'gender'           => 'male',
                'dob'              => '1988-07-22',
                'city_id'          => 1,
                'education'        => 'phd',
                'role'             => 'private_sector_employee',
                'experience'       => 'more_than_ten',
                'skills'           => 'Autonomous vehicles, last-mile delivery, robotics, supply chain optimization, LiDAR systems',
                'achievements'     => 'Led Saudi Arabia\'s first autonomous delivery pilot in NEOM. Former VP Engineering at Aramco Digital. Published 15 papers on AV safety.',
                'company_type'     => 'Private Company',
                'business_overview' => 'Autonomous last-mile delivery platform using self-driving vehicles for e-commerce and grocery deliveries across Saudi cities.',
                'idea_description' => 'Fleet of autonomous delivery vehicles (Level 4) integrated with major e-commerce platforms for same-day delivery in urban zones.',
                'team_skills'      => ['Autonomous Driving', 'Robotics', 'Computer Vision', 'Supply Chain'],
                'business_model'   => 'Autonomous Last-Mile Delivery',
                'employees'        => 83,
                'saudi_employees'  => 62,
                'beneficiaries'    => 120000,
            ],
            // ── COMPANY 2: Electric Vehicle Fleet ──
            [
                'company_name'     => 'Volt Mobility',
                'founder_name'     => 'Noura Al-Shehri',
                'email'            => 'noura@voltmobility.sa',
                'phone'            => '0551100202',
                'gender'           => 'female',
                'dob'              => '1992-11-03',
                'city_id'          => 2, // Jeddah
                'education'        => 'master',
                'role'             => 'private_sector_employee',
                'experience'       => 'five_to_ten',
                'skills'           => 'EV fleet management, charging infrastructure, renewable energy, smart grid integration',
                'achievements'     => 'Deployed 200+ EV charging stations across Western Region. Recipient of Saudi Green Initiative Innovation Prize 2024.',
                'company_type'     => 'Private Company',
                'business_overview' => 'All-electric corporate fleet management and charging infrastructure provider supporting Saudi Vision 2030 sustainability goals.',
                'idea_description' => 'End-to-end EV fleet-as-a-service for corporate clients with smart charging network and battery-swap stations.',
                'team_skills'      => ['EV Engineering', 'Charging Infrastructure', 'Fleet Analytics', 'Sustainability'],
                'business_model'   => 'EV Fleet-as-a-Service',
                'employees'        => 45,
                'saudi_employees'  => 34,
                'beneficiaries'    => 35000,
            ],
            // ── COMPANY 3: Drone Delivery ──
            [
                'company_name'     => 'SkyBridge Delivery',
                'founder_name'     => 'Faisal Al-Otaibi',
                'email'            => 'faisal@skybridge.sa',
                'phone'            => '0551100203',
                'gender'           => 'male',
                'dob'              => '1985-01-19',
                'city_id'          => 1,
                'education'        => 'phd',
                'role'             => 'private_sector_employee',
                'experience'       => 'more_than_ten',
                'skills'           => 'UAV systems, aerospace engineering, airspace management, drone logistics, BVLOS operations',
                'achievements'     => 'First GACA-certified drone delivery company in Saudi Arabia. Completed 10,000+ successful drone deliveries in pilot program.',
                'company_type'     => 'Private Company',
                'business_overview' => 'Commercial drone delivery network for medical supplies, e-commerce packages, and food delivery in urban and remote areas.',
                'idea_description' => 'Autonomous drone delivery network with hub-and-spoke model, AI-powered flight planning, and real-time traffic deconfliction.',
                'team_skills'      => ['Aerospace Engineering', 'AI Flight Planning', 'Regulatory Affairs', 'Drone Operations'],
                'business_model'   => 'Drone Logistics Network',
                'employees'        => 67,
                'saudi_employees'  => 48,
                'beneficiaries'    => 75000,
            ],
            // ── COMPANY 4: Smart Freight ──
            [
                'company_name'     => 'Hamlah Freight',
                'founder_name'     => 'Reem Al-Zahrani',
                'email'            => 'reem@hamlah.sa',
                'phone'            => '0551100204',
                'gender'           => 'female',
                'dob'              => '1991-09-07',
                'city_id'          => 1,
                'education'        => 'master',
                'role'             => 'private_sector_employee',
                'experience'       => 'five_to_ten',
                'skills'           => 'Freight technology, IoT tracking, load matching algorithms, cross-border logistics',
                'achievements'     => 'Built Saudi\'s largest digital freight marketplace connecting 5,000+ trucking companies. Raised SAR 40M Series A.',
                'company_type'     => 'Private Company',
                'business_overview' => 'Digital freight marketplace using AI for load matching, route optimization, and real-time cargo tracking across the GCC.',
                'idea_description' => 'Uber-for-freight platform with AI load matching, IoT cargo monitoring, blockchain-based documentation, and cross-border customs automation.',
                'team_skills'      => ['Logistics Tech', 'IoT', 'Blockchain', 'Cross-border Trade'],
                'business_model'   => 'Digital Freight Marketplace',
                'employees'        => 92,
                'saudi_employees'  => 71,
                'beneficiaries'    => 200000,
            ],
            // ── COMPANY 5: Maritime Tech ──
            [
                'company_name'     => 'Bahr Systems',
                'founder_name'     => 'Sultan Al-Malki',
                'email'            => 'sultan@bahr.sa',
                'phone'            => '0551100205',
                'gender'           => 'male',
                'dob'              => '1987-04-11',
                'city_id'          => 2, // Jeddah
                'education'        => 'master',
                'role'             => 'private_sector_employee',
                'experience'       => 'more_than_ten',
                'skills'           => 'Maritime technology, port automation, vessel tracking, marine logistics optimization',
                'achievements'     => 'Deployed smart port management system at Jeddah Islamic Port. Partner with Saudi Ports Authority on digitization initiative.',
                'company_type'     => 'Private Company',
                'business_overview' => 'Maritime technology platform for smart port operations, vessel scheduling, and automated cargo handling in Saudi ports.',
                'idea_description' => 'AI-powered port operations platform with automated berth planning, container tracking, and predictive maintenance for port equipment.',
                'team_skills'      => ['Maritime Engineering', 'Port Automation', 'AI/ML', 'IoT Sensors'],
                'business_model'   => 'Smart Port Operations',
                'employees'        => 38,
                'saudi_employees'  => 28,
                'beneficiaries'    => 45000,
            ],
            // ── COMPANY 6: Public Transit Tech ──
            [
                'company_name'     => 'Masaar Transit',
                'founder_name'     => 'Lina Al-Rasheed',
                'email'            => 'lina@masaar.sa',
                'phone'            => '0551100206',
                'gender'           => 'female',
                'dob'              => '1993-06-25',
                'city_id'          => 1,
                'education'        => 'master',
                'role'             => 'private_sector_employee',
                'experience'       => 'three_to_five',
                'skills'           => 'Public transit systems, MaaS platforms, ticketing technology, passenger analytics',
                'achievements'     => 'Integrated multi-modal trip planner used by 100K+ commuters in Riyadh. Partnership with Riyadh Transport Authority.',
                'company_type'     => 'Private Company',
                'business_overview' => 'Mobility-as-a-Service platform integrating metro, bus, ride-hailing, and bike-sharing into a single app with unified ticketing.',
                'idea_description' => 'Unified MaaS app for Riyadh connecting all transport modes with subscription plans, dynamic pricing, and accessibility features.',
                'team_skills'      => ['Transit Planning', 'Payment Systems', 'UX Design', 'Data Analytics'],
                'business_model'   => 'Mobility-as-a-Service (MaaS)',
                'employees'        => 29,
                'saudi_employees'  => 22,
                'beneficiaries'    => 150000,
            ],
            // ── COMPANY 7: Connected Vehicle Platform ──
            [
                'company_name'     => 'Sayara Connect',
                'founder_name'     => 'Mohammed Al-Qahtani',
                'email'            => 'mohammed@sayaraconnect.sa',
                'phone'            => '0551100207',
                'gender'           => 'male',
                'dob'              => '1989-12-30',
                'city_id'          => 1,
                'education'        => 'bachelor',
                'role'             => 'private_sector_employee',
                'experience'       => 'five_to_ten',
                'skills'           => 'V2X communication, telematics, OBD-II diagnostics, connected car platforms',
                'achievements'     => 'Largest V2X telematics provider in Saudi Arabia with 200K+ connected vehicles. Strategic partnership with STC.',
                'company_type'     => 'Private Company',
                'business_overview' => 'Connected vehicle platform providing real-time telematics, predictive maintenance, and vehicle-to-infrastructure communication.',
                'idea_description' => 'V2X platform enabling connected vehicles to communicate with smart traffic infrastructure, reducing accidents by 40% and congestion by 25%.',
                'team_skills'      => ['V2X Technology', 'Telematics', 'Cloud Infrastructure', 'Embedded Systems'],
                'business_model'   => 'Connected Vehicle Platform',
                'employees'        => 51,
                'saudi_employees'  => 38,
                'beneficiaries'    => 200000,
            ],
            // ── COMPANY 8: Hyperloop/Rail Tech ──
            [
                'company_name'     => 'Sariyah Rail Tech',
                'founder_name'     => 'Abdulrahman Al-Harbi',
                'email'            => 'abdulrahman@sariyah.sa',
                'phone'            => '0551100208',
                'gender'           => 'male',
                'dob'              => '1986-08-14',
                'city_id'          => 1,
                'education'        => 'phd',
                'role'             => 'private_sector_employee',
                'experience'       => 'more_than_ten',
                'skills'           => 'High-speed rail systems, maglev technology, rail safety, infrastructure monitoring',
                'achievements'     => 'Former Chief Engineer at Saudi Railway Organization. Published 20+ papers on high-speed rail safety. Advisor to Haramain Rail.',
                'company_type'     => 'Private Company',
                'business_overview' => 'Advanced rail technology company developing predictive maintenance AI and digital twin solutions for Saudi railway networks.',
                'idea_description' => 'AI-powered digital twin platform for railway infrastructure monitoring, predicting maintenance needs 6 months ahead and reducing downtime by 60%.',
                'team_skills'      => ['Rail Engineering', 'Digital Twin', 'Predictive Maintenance', 'Sensor Networks'],
                'business_model'   => 'Railway Digital Twin Platform',
                'employees'        => 43,
                'saudi_employees'  => 35,
                'beneficiaries'    => 500000,
            ],
            // ── COMPANY 9: Shared Mobility ──
            [
                'company_name'     => 'Taawun Mobility',
                'founder_name'     => 'Sara Al-Mutlaq',
                'email'            => 'sara@taawun.sa',
                'phone'            => '0551100209',
                'gender'           => 'female',
                'dob'              => '1994-02-18',
                'city_id'          => 2, // Jeddah
                'education'        => 'bachelor',
                'role'             => 'private_sector_employee',
                'experience'       => 'three_to_five',
                'skills'           => 'Shared mobility platforms, peer-to-peer car sharing, insurance tech, community building',
                'achievements'     => 'Launched Saudi\'s first peer-to-peer car sharing platform with 15,000 registered vehicles. Featured in Forbes Middle East 30 Under 30.',
                'company_type'     => 'Private Company',
                'business_overview' => 'Peer-to-peer car sharing and carpooling platform enabling vehicle owners to earn income while reducing urban congestion.',
                'idea_description' => 'P2P car sharing marketplace with embedded insurance, dynamic pricing, IoT vehicle tracking, and AI-matched carpooling for daily commuters.',
                'team_skills'      => ['Platform Development', 'InsurTech', 'Community Building', 'Mobile Apps'],
                'business_model'   => 'P2P Car Sharing & Carpooling',
                'employees'        => 24,
                'saudi_employees'  => 19,
                'beneficiaries'    => 80000,
            ],

            // ── COMPANIES 10-14: REJECTED APPLICATIONS ──

            // ── COMPANY 10: Rejected - Insufficient documentation ──
            [
                'company_name'     => 'QuickRide SA',
                'founder_name'     => 'Hassan Al-Jaber',
                'email'            => 'hassan@quickride.sa',
                'phone'            => '0551100210',
                'gender'           => 'male',
                'dob'              => '1996-05-12',
                'city_id'          => 1,
                'education'        => 'bachelor',
                'role'             => 'recently_graduated',
                'experience'       => 'less_than_one',
                'skills'           => 'Mobile app development, basic ride-sharing concepts',
                'achievements'     => 'Built a university campus ride-sharing prototype. Computer science graduate from KSU.',
                'company_type'     => 'Private Company',
                'business_overview' => 'Ride-sharing app focused on university campuses and short-distance trips within city districts.',
                'idea_description' => 'A basic ride-sharing app connecting drivers and passengers for short urban trips.',
                'team_skills'      => ['Mobile Development', 'Basic Backend'],
                'business_model'   => 'Campus Ride-sharing',
                'employees'        => 3,
                'saudi_employees'  => 3,
                'beneficiaries'    => 2000,
            ],
            // ── COMPANY 11: Rejected - No innovation ──
            [
                'company_name'     => 'Naqli Express',
                'founder_name'     => 'Tariq Al-Anazi',
                'email'            => 'tariq@naqliexpress.sa',
                'phone'            => '0551100211',
                'gender'           => 'male',
                'dob'              => '1980-10-01',
                'city_id'          => 1,
                'education'        => 'diploma',
                'role'             => 'private_sector_employee',
                'experience'       => 'more_than_ten',
                'skills'           => 'Traditional logistics management, truck fleet operations',
                'achievements'     => '20 years in traditional trucking business. Fleet of 50 trucks operating across Saudi Arabia.',
                'company_type'     => 'Private Company',
                'business_overview' => 'Traditional trucking and cargo transport services between major Saudi cities with a manual dispatch system.',
                'idea_description' => 'Moving current manual trucking dispatch to a basic website for booking.',
                'team_skills'      => ['Trucking Operations', 'Basic IT'],
                'business_model'   => 'Traditional Trucking with Website',
                'employees'        => 85,
                'saudi_employees'  => 30,
                'beneficiaries'    => 5000,
            ],
            // ── COMPANY 12: Rejected - Non-compliant ──
            [
                'company_name'     => 'SwiftMove International',
                'founder_name'     => 'Layla Al-Saeed',
                'email'            => 'layla@swiftmove.sa',
                'phone'            => '0551100212',
                'gender'           => 'female',
                'dob'              => '1991-03-28',
                'city_id'          => 3, // Dubai-registered but applying in SA
                'education'        => 'master',
                'role'             => 'private_sector_employee',
                'experience'       => 'five_to_ten',
                'skills'           => 'International logistics, cross-border transport, fleet management',
                'achievements'     => 'Successfully operated in 5 GCC countries. Revenue of $10M in 2024.',
                'company_type'     => 'Private Company',
                'business_overview' => 'Cross-border transport service operating between GCC countries without proper Saudi commercial registration.',
                'idea_description' => 'Expanding existing UAE-based cross-border logistics service into Saudi Arabia.',
                'team_skills'      => ['International Logistics', 'Cross-border Trade'],
                'business_model'   => 'Cross-border Logistics',
                'employees'        => 120,
                'saudi_employees'  => 5,
                'beneficiaries'    => 15000,
            ],
            // ── COMPANY 13: Rejected - Weak financials ──
            [
                'company_name'     => 'Mawsil Delivery',
                'founder_name'     => 'Yousef Al-Tamimi',
                'email'            => 'yousef@mawsil.sa',
                'phone'            => '0551100213',
                'gender'           => 'male',
                'dob'              => '1997-07-14',
                'city_id'          => 1,
                'education'        => 'bachelor',
                'role'             => 'university_student',
                'experience'       => 'less_than_one',
                'skills'           => 'Delivery app design, customer service',
                'achievements'     => 'Won university entrepreneurship program. Currently completing final year at KFUPM.',
                'company_type'     => 'Private Company',
                'business_overview' => 'On-demand delivery service for small packages within Riyadh using bicycle couriers.',
                'idea_description' => 'Bicycle-based package delivery within neighborhoods with a mobile app.',
                'team_skills'      => ['App Design', 'Customer Service'],
                'business_model'   => 'Bicycle Delivery Service',
                'employees'        => 2,
                'saudi_employees'  => 2,
                'beneficiaries'    => 500,
            ],
            // ── COMPANY 14: Rejected - Outside scope ──
            [
                'company_name'     => 'TravelPlan SA',
                'founder_name'     => 'Mona Al-Dossary',
                'email'            => 'mona@travelplan.sa',
                'phone'            => '0551100214',
                'gender'           => 'female',
                'dob'              => '1990-12-05',
                'city_id'          => 1,
                'education'        => 'bachelor',
                'role'             => 'private_sector_employee',
                'experience'       => 'three_to_five',
                'skills'           => 'Travel planning, hospitality tech, booking systems',
                'achievements'     => 'Built a successful travel agency with 10K+ customers. Specialized in Hajj/Umrah packages.',
                'company_type'     => 'Private Company',
                'business_overview' => 'Online travel agency providing Hajj/Umrah packages with hotel and flight booking — not a transport innovation.',
                'idea_description' => 'Travel booking platform for Hajj and Umrah packages with integrated hotel and flight reservations.',
                'team_skills'      => ['Travel Tech', 'Booking Systems', 'Hospitality'],
                'business_model'   => 'Online Travel Agency',
                'employees'        => 18,
                'saudi_employees'  => 15,
                'beneficiaries'    => 10000,
            ],
        ];
    }

    private function buildRegistrationFormSubmissions(array $company): array
    {
        $slug = strtolower(str_replace(' ', '-', $company['company_name']));
        $city = $company['city_id'] == 1 ? 'Riyadh' : ($company['city_id'] == 2 ? 'Jeddah' : 'Dubai');
        $nonSaudi = $company['employees'] - $company['saudi_employees'];

        // Uses EXACT field slugs from form 10 (registration form)
        return [
            // Radio: Is the applicant licensed?
            'is_the_applicant_licensed_by_tga_or_any_government_entity?' => $company['employees'] > 10 ? 'Yes' : 'No',
            // Number: License number
            'license_number'          => (string) rand(4400000, 4499999),
            // Dropdown: Company Type (options: Startup, LLC, Non-profit)
            'company_type'            => $company['employees'] < 10 ? 'Startup' : 'LLC',
            // Textarea: Offices and branches
            'offices_and_branches_in_the_kingdom' => "{$city} Head Office (King Fahd Road, Building " . rand(10, 99) . ", Floor " . rand(1, 12) . ")" . ($company['employees'] > 30 ? ". Branch offices in " . ($city === 'Riyadh' ? 'Jeddah and Dammam' : 'Riyadh and Dammam') . "." : '.'),
            // Text: Contact numbers
            'contact_numbers'         => $company['phone'] . ', +966-11-' . rand(200, 499) . '-' . rand(1000, 9999),
            // Email: Email address
            'email_address'           => $company['email'],
            // URL: Company website
            'company_website'         => 'https://' . $slug . '.sa',
            // Textarea: Other business activities
            'other_business_activities' => $company['business_model'] . '. Additional activities include: technology consulting, fleet management advisory, and data analytics services for the transportation sector.',
            // File: Commercial registration
            'file_valid_commercial_registration_-_attachment' => 'applications/sandbox-2025/' . $slug . '/commercial-registration-' . rand(1000, 9999) . '.pdf',
            // File: Articles of association
            'file_articles_of_association_-_attachment' => 'applications/sandbox-2025/' . $slug . '/articles-of-association.pdf',
            // Textarea: Business overview
            'business_overview'       => $company['business_overview'] . ' The company was established in ' . rand(2019, 2024) . ' and currently operates with a team of ' . $company['employees'] . ' employees (' . $company['saudi_employees'] . ' Saudi nationals). Our ' . strtolower($company['business_model']) . ' solution addresses a critical gap in Saudi Arabia\'s transportation ecosystem aligned with Vision 2030.',
            // Number: Total employees
            'number_of_employees'     => (string) $company['employees'],
            // Number: Saudi employees
            'number_of_saudi_employees' => (string) $company['saudi_employees'],
            // File: Operational plan
            'file_operational_plan_-_attachment' => 'applications/sandbox-2025/' . $slug . '/operational-plan-2025.pdf',
            // File: Exit plan
            'file_exit_plan,_challenges_&_risks_-_attachment' => 'applications/sandbox-2025/' . $slug . '/exit-plan-and-risks.pdf',
            // File: Technology details
            'file_technology_details_-_attachment' => 'applications/sandbox-2025/' . $slug . '/technology-architecture.pdf',
            // File: Risk summary & safety plan
            'file_risk_summary_&_safety_plan_-_attachment' => 'applications/sandbox-2025/' . $slug . '/risk-summary-safety-plan.pdf',
            // File: Financial summary
            'file_financial_summary_-_attachment' => 'applications/sandbox-2025/' . $slug . '/financial-projections-2025.pdf',
            // File: Pricing policy
            'file_pricing_policy_-_attachment' => 'applications/sandbox-2025/' . $slug . '/pricing-policy.pdf',
            // Number: Active beneficiaries
            'number_of_active_beneficiaries' => (string) $company['beneficiaries'],
            // Textarea: Description of beneficiaries
            'brief_description_of_beneficiaries' => 'Our primary beneficiaries include urban commuters and business travelers in ' . $city . ' and surrounding areas. Secondary beneficiaries include logistics companies, fleet operators, and government entities seeking efficient transport solutions. Total projected impact: ' . number_format($company['beneficiaries']) . '+ users across all service tiers within the first 12 months of sandbox operations.',
            // File: Beneficiary protection policy
            'file_beneficiary_protection_policy_-_attachment' => 'applications/sandbox-2025/' . $slug . '/beneficiary-protection-policy.pdf',
        ];
    }

    private function buildMonthlyReportSubmission(array $company, int $month, int $formId): array
    {
        $p = 'f' . $formId . '_'; // Prefix matching actual DB slugs, e.g. "f11_", "f12_"
        $baseUsers = $company['beneficiaries'];
        $growthFactor = 1 + ($month * 0.15);
        $activeUsers = (int) ($baseUsers * $growthFactor * (rand(5, 15) / 100));

        $revenue = rand(200000, 2000000);
        $capex = (int) ($revenue * (rand(20, 40) / 100));
        $opex = (int) ($revenue * (rand(50, 70) / 100));
        $grossProfit = $revenue - $opex;
        $netProfit = $grossProfit - $capex;

        $fleetSize = rand(20, 200);
        $complaints = rand(5, 50);
        $satisfaction = (string) (rand(35, 50) / 10);
        $monthName = Carbon::create(2025, $month, 1)->format('F');
        $completionDate = Carbon::create(2025, $month, rand(25, 28))->format('Y-m-d');

        // Business model dropdown IDs: 1=Micromobility, 2=Car Rental, 3=On-demand Bus
        $businessModelId = match (true) {
            str_contains(strtolower($company['business_model']), 'micro') ||
            str_contains(strtolower($company['business_model']), 'scooter') ||
            str_contains(strtolower($company['business_model']), 'mobility') => '1',
            str_contains(strtolower($company['business_model']), 'rental') ||
            str_contains(strtolower($company['business_model']), 'fleet') ||
            str_contains(strtolower($company['business_model']), 'sharing') => '2',
            default => '3', // On-demand Bus for logistics/freight/transit
        };

        return [
            $p . 'company_name'                 => $company['company_name'],
            $p . 'business_model'               => $businessModelId,
            $p . 'respondent_name'              => $company['founder_name'],
            $p . 'contact_number'               => $company['phone'],
            $p . 'email'                        => $company['email'],
            $p . 'completion_date'              => $completionDate,
            $p . 'report_period'                => $monthName . ' 2025',
            $p . 'customer_satisfaction_rating'  => $satisfaction,
            $p . 'customer_complaints_count'     => (string) $complaints,
            $p . 'complaint_processing_rate'     => (string) rand(85, 99),
            $p . 'complaints_under_10_days'      => (string) rand(70, 95),
            $p . 'avg_insurance_claims_per_user'  => (string) (rand(1, 30) / 10),
            $p . 'avg_traffic_incidents_per_user' => (string) (rand(1, 15) / 10),
            $p . 'vehicle_inspection_rate'       => (string) rand(90, 100),
            $p . 'avg_inspection_violations'     => (string) (rand(0, 30) / 10),
            $p . 'fleet_size'                    => (string) $fleetSize,
            $p . 'avg_trip_distance'             => (string) (rand(50, 250) / 10),
            $p . 'utilization_rate'              => (string) rand(60, 95),
            $p . 'avg_price_per_km'              => (string) (rand(10, 50) / 10),
            $p . 'total_active_users'            => (string) $activeUsers,
            $p . 'financial_investments'         => (string) rand(1000000, 10000000),
            $p . 'capital_expenditure'           => (string) $capex,
            $p . 'operational_expenditure'       => (string) $opex,
            $p . 'revenue'                       => (string) $revenue,
            $p . 'gross_profit'                  => (string) $grossProfit,
            $p . 'net_profit'                    => (string) $netProfit,
            $p . 'id_license_verification'       => '1', // Radio option ID: 1=Yes
            $p . 'contract_termination_compliance' => '1',
            $p . 'no_unauthorized_charges'       => '1',
            $p . 'compliance_notes'              => 'All regulatory requirements met for ' . $monthName . ' 2025. ' . $company['company_name'] . ' completed its internal audit on ' . Carbon::create(2025, $month, 15)->format('Y-m-d') . '. Fleet inspection pass rate at ' . rand(95, 100) . '%. Customer complaint resolution rate within SLA targets. No regulatory violations or safety incidents reported during this period. Monthly report submitted by ' . $company['founder_name'] . '.',
        ];
    }

    private function generateAssessmentScores(array $company, bool $isApproved): array
    {
        if ($isApproved) {
            return [
                'business_model_innovation' => rand(15, 25),
                'regulatory_readiness'      => rand(15, 25),
                'operational_capacity'      => rand(15, 25),
                'market_impact'             => rand(15, 25),
            ];
        }

        return [
            'business_model_innovation' => rand(5, 15),
            'regulatory_readiness'      => rand(5, 12),
            'operational_capacity'      => rand(5, 12),
            'market_impact'             => rand(5, 15),
        ];
    }

    private function generateAiEvaluationResponse(array $company, bool $isApproved): array
    {
        $score = $isApproved ? rand(72, 95) : rand(30, 55);

        $criteria = [
            [
                'criteriaId'  => '1',
                'name'        => 'Business Model Innovation',
                'totalScore'  => $isApproved ? rand(18, 25) : rand(5, 14),
                'maxWeight'   => 25,
                'feedback'    => $isApproved
                    ? "The {$company['business_model']} model demonstrates strong innovation potential for Saudi Arabia's transport sector. The approach addresses a clear market gap and aligns well with Vision 2030 transport modernization goals."
                    : "The proposed business model lacks sufficient innovation. The concept is either too similar to existing solutions or does not introduce meaningful technological advancement for the sandbox program.",
            ],
            [
                'criteriaId'  => '2',
                'name'        => 'Regulatory Readiness',
                'totalScore'  => $isApproved ? rand(17, 25) : rand(5, 12),
                'maxWeight'   => 25,
                'feedback'    => $isApproved
                    ? "The applicant demonstrates good understanding of regulatory requirements. Documentation is comprehensive with valid commercial registration and a well-structured exit plan."
                    : "Significant gaps in regulatory documentation. Missing key compliance documents or insufficient understanding of the regulatory framework required for sandbox participation.",
            ],
            [
                'criteriaId'  => '3',
                'name'        => 'Operational Capacity',
                'totalScore'  => $isApproved ? rand(17, 25) : rand(6, 13),
                'maxWeight'   => 25,
                'feedback'    => $isApproved
                    ? "Strong operational foundation with {$company['employees']} employees ({$company['saudi_employees']} Saudi nationals). The team possesses relevant skills in " . implode(', ', array_slice($company['team_skills'], 0, 2)) . " and has demonstrated execution capability."
                    : "Insufficient operational capacity to execute the proposed business model. Team size and expertise may not support the scope of the proposed sandbox activities.",
            ],
            [
                'criteriaId'  => '4',
                'name'        => 'Market Impact & Beneficiary Protection',
                'totalScore'  => $isApproved ? rand(16, 25) : rand(5, 14),
                'maxWeight'   => 25,
                'feedback'    => $isApproved
                    ? "Projected to serve {$company['beneficiaries']}+ beneficiaries with robust protection policies in place. The pricing model is transparent and competitive."
                    : "Limited market impact potential or insufficient beneficiary protection mechanisms. The target beneficiary count and protection policies need significant strengthening.",
            ],
        ];

        return [
            'status'  => 'completed',
            'data'    => ['criteria' => $criteria],
            'message' => 'AI evaluation completed successfully',
            'meta'    => [
                'total_score'        => $score,
                'max_weight'         => 100,
                'target_total_weight' => 100,
                'normalized_score'   => $score,
            ],
        ];
    }

    private function generateProjectAiEvaluation(array $company, int $score, string $month): array
    {
        return [
            'status'  => 'completed',
            'data'    => [
                'criteria' => [
                    [
                        'criteriaId' => '1',
                        'name'       => 'Regulatory Compliance',
                        'totalScore' => (int) ($score * 0.25 + rand(-3, 3)),
                        'maxWeight'  => 25,
                        'feedback'   => "The {$month} performance report from {$company['company_name']} shows " . ($score > 70 ? 'strong' : 'adequate') . " regulatory compliance. All required metrics have been reported on time.",
                    ],
                    [
                        'criteriaId' => '2',
                        'name'       => 'Operational Performance',
                        'totalScore' => (int) ($score * 0.25 + rand(-3, 3)),
                        'maxWeight'  => 25,
                        'feedback'   => "Operational metrics indicate " . ($score > 70 ? 'solid growth and efficiency' : 'room for improvement in fleet utilization and service delivery') . " for the {$month} period.",
                    ],
                    [
                        'criteriaId' => '3',
                        'name'       => 'Customer Satisfaction & Safety',
                        'totalScore' => (int) ($score * 0.25 + rand(-3, 3)),
                        'maxWeight'  => 25,
                        'feedback'   => "Customer satisfaction and safety indicators are " . ($score > 70 ? 'above the sandbox benchmark' : 'below expected thresholds and require corrective action') . ".",
                    ],
                    [
                        'criteriaId' => '4',
                        'name'       => 'Financial Viability',
                        'totalScore' => (int) ($score * 0.25 + rand(-3, 3)),
                        'maxWeight'  => 25,
                        'feedback'   => "Financial performance for {$month} shows " . ($score > 70 ? 'positive trajectory toward sustainability' : 'concerns regarding unit economics and burn rate') . ".",
                    ],
                ],
            ],
            'message' => 'AI evaluation completed successfully for ' . $month . ' report',
            'meta'    => [
                'total_score'         => $score,
                'max_weight'          => 100,
                'target_total_weight' => 100,
                'normalized_score'    => $score,
            ],
        ];
    }

    private function generateJudgeComment(float $score): string
    {
        if ($score >= 85) {
            return collect([
                'Excellent performance across all metrics. The company demonstrates strong regulatory compliance and operational excellence. Recommended for continued sandbox participation.',
                'Outstanding results this period. Innovation and execution are both at a high level. The team clearly understands the regulatory sandbox requirements.',
                'Impressive growth trajectory with solid fundamentals. The beneficiary impact metrics are particularly noteworthy. Well-positioned for graduation.',
            ])->random();
        }

        if ($score >= 70) {
            return collect([
                'Good overall performance with room for improvement in some areas. Recommend focusing on customer satisfaction metrics and operational efficiency.',
                'Solid progress this period. The team should address the minor compliance gaps identified and continue strengthening their operational processes.',
                'Satisfactory performance meeting most sandbox requirements. Financial metrics show promise but need monitoring in the next reporting period.',
            ])->random();
        }

        return collect([
            'Below expectations in several key areas. Significant improvement needed in regulatory compliance and operational metrics. Recommend close monitoring.',
            'Performance needs substantial improvement. The team should prioritize addressing compliance gaps and improving customer satisfaction scores.',
            'Concerning trend in key metrics. Recommend an intervention meeting to discuss corrective actions and timeline for improvement.',
        ])->random();
    }

    private function generateCriterionComment(string $question, float $score, int $weight): string
    {
        $pct = ($score / max(1, $weight)) * 100;
        $level = $pct >= 80 ? 'strong' : ($pct >= 60 ? 'adequate' : 'needs improvement');

        $comments = [
            'Regulatory Compliance' => [
                'strong'           => 'All regulatory requirements met. Documentation is complete and timely.',
                'adequate'         => 'Most requirements met with minor gaps in documentation. Corrective action noted.',
                'needs improvement' => 'Several compliance gaps identified. Missing required documentation and late reporting.',
            ],
            'Operational Performance' => [
                'strong'           => 'Fleet utilization and service delivery metrics exceed benchmarks.',
                'adequate'         => 'Operational performance is within acceptable range. Some efficiency improvements possible.',
                'needs improvement' => 'Operational metrics significantly below expected thresholds. Utilization and delivery need urgent attention.',
            ],
            'Customer Satisfaction & Safety' => [
                'strong'           => 'High customer satisfaction scores with minimal complaints. Safety record is exemplary.',
                'adequate'         => 'Customer satisfaction is acceptable. Safety metrics are within range but complaint resolution could improve.',
                'needs improvement' => 'Customer satisfaction below threshold. Safety incidents require investigation and corrective measures.',
            ],
            'Financial Viability' => [
                'strong'           => 'Strong revenue growth and healthy unit economics. On track for financial sustainability.',
                'adequate'         => 'Financial performance is acceptable. Monitoring burn rate and path to profitability.',
                'needs improvement' => 'Financial metrics are concerning. High burn rate with insufficient revenue growth.',
            ],
        ];

        return $comments[$question][$level] ?? "Score: {$score}/{$weight}. Performance level: {$level}.";
    }

    private function getCriterionFieldMappings(string $criterionName): array
    {
        return match ($criterionName) {
            'Business Model Innovation' => [
                'business_overview',
                'other_business_activities',
            ],
            'Regulatory Readiness' => [
                'is_the_applicant_licensed_by_tga_or_any_government_entity?',
                'file_valid_commercial_registration_-_attachment',
                'file_exit_plan,_challenges_&_risks_-_attachment',
                'file_risk_summary_&_safety_plan_-_attachment',
            ],
            'Operational Capacity' => [
                'number_of_employees',
                'number_of_saudi_employees',
                'offices_and_branches_in_the_kingdom',
                'file_operational_plan_-_attachment',
                'file_technology_details_-_attachment',
            ],
            'Market Impact & Beneficiary Protection' => [
                'number_of_active_beneficiaries',
                'brief_description_of_beneficiaries',
                'file_beneficiary_protection_policy_-_attachment',
                'file_pricing_policy_-_attachment',
                'file_financial_summary_-_attachment',
            ],
            default => [],
        };
    }
}
