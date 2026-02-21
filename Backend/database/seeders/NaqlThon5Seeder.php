<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class NaqlThon5Seeder extends Seeder
{
    private int $adminUserId = 166;
    private int $programId;
    private int $regFormId;
    private int $projFormId;

    public function run(): void
    {
        $this->command->info('=== Starting Naql Thon 5 Hackathon Creation ===');

        // ================================================================
        // 1. CREATE COMPETITION
        // ================================================================
        $this->command->info('Creating program...');

        $this->programId = DB::table('programs')->insertGetId([
            'title' => json_encode(['en' => 'Naql Thon 5 - Saudi Transportation Innovation Hackathon', 'ar' => 'نقل ثون 5 - هاكاثون الابتكار في النقل السعودي']),
            'about' => json_encode(['en' => 'Naql Thon 5 is the fifth edition of the premier Saudi transportation hackathon organized by the Transport General Authority (TGA). This hackathon brings together innovators, developers, and entrepreneurs to solve critical challenges in Saudi Arabia\'s transportation ecosystem. Aligned with Vision 2030, participants will develop solutions spanning smart mobility, logistics optimization, autonomous transport, sustainable infrastructure, and passenger experience enhancement. Over 48 hours, teams will ideate, prototype, and pitch their solutions to a panel of industry experts and government officials.', 'ar' => 'نقل ثون 5 هو النسخة الخامسة من هاكاثون النقل السعودي الرائد الذي تنظمه الهيئة العامة للنقل. يجمع هذا الهاكاثون المبتكرين والمطورين ورواد الأعمال لحل التحديات الحرجة في منظومة النقل السعودية. يتماشى مع رؤية 2030، حيث يطور المشاركون حلولاً تشمل التنقل الذكي وتحسين اللوجستيات والنقل الذاتي والبنية التحتية المستدامة وتحسين تجربة الركاب.']),
            'terms_and_conditions' => json_encode(['en' => 'By participating in Naql Thon 5, you agree to: 1) All intellectual property created during the hackathon belongs to the team. 2) TGA retains a non-exclusive license to showcase solutions. 3) Participants must be 18+ years old. 4) Teams must consist of 2-5 members. 5) Solutions must address Saudi transportation challenges. 6) Code of conduct must be followed at all times. 7) Judging decisions are final.', 'ar' => 'بالمشاركة في نقل ثون 5، أنت توافق على: 1) جميع حقوق الملكية الفكرية المنشأة خلال الهاكاثون تعود للفريق. 2) تحتفظ الهيئة العامة للنقل بترخيص غير حصري لعرض الحلول. 3) يجب أن يكون عمر المشاركين 18 سنة فأكثر.']),
            'banner' => 'programs/naqlthon5/banner.jpg',
            'registration_closed_date' => Carbon::now()->addDays(30)->toDateString(),
            'is_published' => true,
            'is_archived' => false,
            'type' => 'Hackathon',
            'created_at' => now()->subDays(45),
            'updated_at' => now(),
        ]);

        $this->command->info('   Program ID: ' . $this->programId);

        // ================================================================
        // 2. CREATE TRACKS & SUB-TRACKS
        // ================================================================
        $this->command->info('Creating tracks and sub-tracks...');

        $tracksData = [
            ['name' => ['en' => 'Smart Mobility & MaaS', 'ar' => 'التنقل الذكي والتنقل كخدمة'], 'subs' => [['en' => 'Ride-Hailing & Carpooling', 'ar' => 'خدمات التوصيل والمشاركة'], ['en' => 'Micro-Mobility Solutions', 'ar' => 'حلول التنقل الصغير'], ['en' => 'Multi-Modal Integration', 'ar' => 'التكامل متعدد الوسائط']]],
            ['name' => ['en' => 'Freight & Logistics', 'ar' => 'الشحن واللوجستيات'], 'subs' => [['en' => 'Last-Mile Delivery', 'ar' => 'التوصيل للميل الأخير'], ['en' => 'Supply Chain Optimization', 'ar' => 'تحسين سلسلة الإمداد'], ['en' => 'Cross-Border Logistics', 'ar' => 'اللوجستيات العابرة للحدود']]],
            ['name' => ['en' => 'Autonomous & EV Technology', 'ar' => 'تقنيات القيادة الذاتية والمركبات الكهربائية'], 'subs' => [['en' => 'Autonomous Vehicles', 'ar' => 'المركبات ذاتية القيادة'], ['en' => 'EV Infrastructure', 'ar' => 'بنية المركبات الكهربائية'], ['en' => 'Connected Vehicles (V2X)', 'ar' => 'المركبات المتصلة']]],
            ['name' => ['en' => 'Safety & Sustainability', 'ar' => 'السلامة والاستدامة'], 'subs' => [['en' => 'Road Safety Innovation', 'ar' => 'ابتكار السلامة المرورية'], ['en' => 'Green Transport', 'ar' => 'النقل الأخضر'], ['en' => 'Accessibility Solutions', 'ar' => 'حلول إمكانية الوصول']]],
        ];

        $trackIds = [];
        $subTrackIds = [];
        foreach ($tracksData as $order => $td) {
            $trackId = DB::table('tracks')->insertGetId([
                'program_id' => $this->programId,
                'name' => json_encode($td['name']),
                'order' => $order + 1,
                'slug' => Str::slug($td['name']['en']),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $trackIds[] = $trackId;
            foreach ($td['subs'] as $sOrder => $sub) {
                $subTrackIds[] = DB::table('sub_tracks')->insertGetId([
                    'track_id' => $trackId,
                    'name' => json_encode($sub),
                    'order' => $sOrder + 1,
                    'slug' => Str::slug($sub['en']),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
        $this->command->info('   Created ' . count($trackIds) . ' tracks with ' . count($subTrackIds) . ' sub-tracks');

        // ================================================================
        // 3. CREATE STAGES
        // ================================================================
        $stagesData = [
            ['slug' => 'registration-nt5', 'title' => ['en' => 'Registration & Team Formation', 'ar' => 'التسجيل وتشكيل الفرق'], 'days_from' => -30, 'days_to' => 0],
            ['slug' => 'ideation-nt5', 'title' => ['en' => 'Ideation & Planning', 'ar' => 'توليد الأفكار والتخطيط'], 'days_from' => 1, 'days_to' => 3],
            ['slug' => 'prototyping-nt5', 'title' => ['en' => 'Prototyping & Development', 'ar' => 'النمذجة والتطوير'], 'days_from' => 4, 'days_to' => 6],
            ['slug' => 'evaluation-nt5', 'title' => ['en' => 'Evaluation & Judging', 'ar' => 'التقييم والتحكيم'], 'days_from' => 7, 'days_to' => 10],
        ];
        $stageIds = [];
        foreach ($stagesData as $sd) {
            $stageIds[$sd['slug']] = DB::table('stages')->insertGetId([
                'program_id' => $this->programId, 'slug' => $sd['slug'],
                'title' => json_encode($sd['title']),
                'description' => json_encode(['en' => 'Stage: ' . $sd['title']['en'], 'ar' => 'مرحلة: ' . $sd['title']['ar']]),
                'starts_at' => Carbon::now()->addDays($sd['days_from']), 'ends_at' => Carbon::now()->addDays($sd['days_to']),
                'is_visible' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // ================================================================
        // 4. REGISTRATION FORM CONFIG & TEAM FORM CONFIG
        // ================================================================
        DB::table('registration_form_configs')->insert([
            'program_id' => $this->programId, 'registration_type' => 'both', 'min_age' => 18, 'max_age' => 65,
            'is_active' => true, 'scoring_enabled' => true, 'minimum_score_threshold' => 60, 'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('team_form_configs')->insert([
            'program_id' => $this->programId, 'is_active' => true, 'min_team_members' => 2, 'max_team_members' => 5,
            'allow_track_selection' => true, 'require_same_track' => false, 'auto_publish_teams' => true,
            'is_archived' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);

        // ================================================================
        // 5. REGISTRATION FORM WITH STEPS
        // ================================================================
        $this->command->info('Creating registration form...');
        $this->regFormId = DB::table('forms')->insertGetId([
            'program_id' => $this->programId, 'type' => 'registration',
            'name' => json_encode(['en' => 'Naql Thon 5 Registration Form', 'ar' => 'نموذج التسجيل في نقل ثون 5']),
            'description' => json_encode(['en' => 'Complete this form to register for Naql Thon 5.', 'ar' => 'أكمل هذا النموذج للتسجيل في نقل ثون 5.']),
            'status' => 'active', 'is_published' => true, 'is_archived' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $regFields = [
            ['slug' => 'nt5_specialization', 'label' => ['en' => 'Technical Specialization', 'ar' => 'التخصص التقني'], 'type' => 'dropdown', 'sort' => 1, 'required' => true, 'options' => json_encode(['Software Engineering', 'Data Science & AI', 'UX/UI Design', 'Business & Strategy', 'Hardware & IoT', 'Cybersecurity', 'Project Management']), 'step' => 1],
            ['slug' => 'nt5_experience_level', 'label' => ['en' => 'Experience Level in Transport Sector', 'ar' => 'مستوى الخبرة في قطاع النقل'], 'type' => 'dropdown', 'sort' => 2, 'required' => true, 'options' => json_encode(['No Experience', '1-2 Years', '3-5 Years', '5-10 Years', '10+ Years']), 'step' => 1],
            ['slug' => 'nt5_saudi_city', 'label' => ['en' => 'City of Residence', 'ar' => 'مدينة الإقامة'], 'type' => 'dropdown', 'sort' => 3, 'required' => true, 'options' => json_encode(['Riyadh', 'Jeddah', 'Dammam', 'Makkah', 'Madinah', 'Abha', 'Tabuk', 'Other']), 'step' => 1],
            ['slug' => 'nt5_organization', 'label' => ['en' => 'Current Organization / University', 'ar' => 'المنظمة / الجامعة الحالية'], 'type' => 'text', 'sort' => 4, 'required' => true, 'options' => null, 'step' => 1],
            ['slug' => 'nt5_linkedin_url', 'label' => ['en' => 'LinkedIn Profile URL', 'ar' => 'رابط لينكد إن'], 'type' => 'url', 'sort' => 5, 'required' => false, 'options' => null, 'step' => 1],
            ['slug' => 'nt5_github_portfolio', 'label' => ['en' => 'GitHub / Portfolio URL', 'ar' => 'رابط GitHub / المحفظة'], 'type' => 'url', 'sort' => 6, 'required' => false, 'options' => null, 'step' => 1],
            ['slug' => 'nt5_preferred_track', 'label' => ['en' => 'Preferred Hackathon Track', 'ar' => 'المسار المفضل'], 'type' => 'dropdown', 'sort' => 7, 'required' => true, 'options' => json_encode(['Smart Mobility & MaaS', 'Freight & Logistics', 'Autonomous & EV Technology', 'Safety & Sustainability']), 'step' => 2],
            ['slug' => 'nt5_problem_statement', 'label' => ['en' => 'Transportation Problem You Want to Solve', 'ar' => 'مشكلة النقل التي تريد حلها'], 'type' => 'textarea', 'sort' => 8, 'required' => true, 'options' => null, 'step' => 2],
            ['slug' => 'nt5_proposed_solution', 'label' => ['en' => 'Proposed Solution Overview', 'ar' => 'نظرة عامة على الحل المقترح'], 'type' => 'textarea', 'sort' => 9, 'required' => true, 'options' => null, 'step' => 2],
            ['slug' => 'nt5_tech_stack', 'label' => ['en' => 'Technology Stack / Tools', 'ar' => 'المنصة التقنية / الأدوات'], 'type' => 'text', 'sort' => 10, 'required' => true, 'options' => null, 'step' => 2],
            ['slug' => 'nt5_innovation_type', 'label' => ['en' => 'Type of Innovation', 'ar' => 'نوع الابتكار'], 'type' => 'radio', 'sort' => 11, 'required' => true, 'options' => json_encode(['Product Innovation', 'Process Innovation', 'Service Innovation', 'Business Model Innovation']), 'step' => 2],
            ['slug' => 'nt5_hackathon_experience', 'label' => ['en' => 'Previous Hackathon Participations', 'ar' => 'المشاركات السابقة في الهاكاثونات'], 'type' => 'number', 'sort' => 12, 'required' => true, 'options' => null, 'step' => 2],
            ['slug' => 'nt5_team_status', 'label' => ['en' => 'Do You Have a Team?', 'ar' => 'هل لديك فريق؟'], 'type' => 'radio', 'sort' => 13, 'required' => true, 'options' => json_encode(['Yes, complete team', 'Yes, looking for more members', 'No, looking for a team']), 'step' => 3],
            ['slug' => 'nt5_skills_offered', 'label' => ['en' => 'Key Skills You Bring', 'ar' => 'المهارات الرئيسية التي تقدمها'], 'type' => 'textarea', 'sort' => 14, 'required' => true, 'options' => null, 'step' => 3],
            ['slug' => 'nt5_dietary_requirements', 'label' => ['en' => 'Dietary Requirements', 'ar' => 'المتطلبات الغذائية'], 'type' => 'dropdown', 'sort' => 15, 'required' => true, 'options' => json_encode(['None', 'Vegetarian', 'Vegan', 'Gluten-Free', 'Halal Only', 'Other']), 'step' => 3],
            ['slug' => 'nt5_tshirt_size', 'label' => ['en' => 'T-Shirt Size', 'ar' => 'مقاس القميص'], 'type' => 'dropdown', 'sort' => 16, 'required' => true, 'options' => json_encode(['XS', 'S', 'M', 'L', 'XL', 'XXL']), 'step' => 3],
            ['slug' => 'nt5_accessibility_needs', 'label' => ['en' => 'Accessibility Needs', 'ar' => 'احتياجات إمكانية الوصول'], 'type' => 'textarea', 'sort' => 17, 'required' => false, 'options' => null, 'step' => 3],
            ['slug' => 'nt5_how_heard', 'label' => ['en' => 'How Did You Hear About Naql Thon 5?', 'ar' => 'كيف سمعت عن نقل ثون 5؟'], 'type' => 'dropdown', 'sort' => 18, 'required' => true, 'options' => json_encode(['Social Media', 'TGA Website', 'University', 'Friend/Colleague', 'Previous Edition', 'News/Media', 'Other']), 'step' => 3],
            ['slug' => 'nt5_motivation', 'label' => ['en' => 'Why Do You Want to Participate?', 'ar' => 'لماذا تريد المشاركة؟'], 'type' => 'textarea', 'sort' => 19, 'required' => true, 'options' => null, 'step' => 3],
            ['slug' => 'nt5_resume', 'label' => ['en' => 'Resume / CV Upload', 'ar' => 'تحميل السيرة الذاتية'], 'type' => 'file', 'sort' => 20, 'required' => false, 'options' => null, 'step' => 3],
        ];

        $fieldIds = [];
        $stepFieldMap = [1 => [], 2 => [], 3 => []];
        foreach ($regFields as $rf) {
            $fId = DB::table('form_fields')->insertGetId([
                'form_id' => $this->regFormId, 'label' => json_encode($rf['label']), 'type' => $rf['type'],
                'required' => $rf['required'], 'placeholder' => json_encode(['en' => '', 'ar' => '']),
                'options' => $rf['options'], 'sort' => $rf['sort'], 'slug' => $rf['slug'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $fieldIds[$rf['slug']] = $fId;
            $stepFieldMap[$rf['step']][] = $fId;
        }
        $stepNames = [
            1 => ['en' => 'Personal & Professional Info', 'ar' => 'المعلومات الشخصية والمهنية'],
            2 => ['en' => 'Hackathon & Innovation Details', 'ar' => 'تفاصيل الهاكاثون والابتكار'],
            3 => ['en' => 'Team & Logistics', 'ar' => 'الفريق والترتيبات'],
        ];
        foreach ($stepNames as $order => $name) {
            DB::table('form_steps')->insert(['form_id' => $this->regFormId, 'name' => json_encode($name), 'step_order' => $order, 'field_ids' => json_encode($stepFieldMap[$order]), 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->command->info('   Created registration form with ' . count($fieldIds) . ' fields and 3 steps');

        // ================================================================
        // 6. PROJECT SUBMISSION FORM WITH STEPS
        // ================================================================
        $this->command->info('Creating project form...');
        $this->projFormId = DB::table('forms')->insertGetId([
            'program_id' => $this->programId, 'type' => 'project',
            'name' => json_encode(['en' => 'Naql Thon 5 Project Submission', 'ar' => 'تسليم مشروع نقل ثون 5']),
            'description' => json_encode(['en' => 'Submit your hackathon project.', 'ar' => 'قدم مشروع الهاكاثون.']),
            'status' => 'active', 'is_published' => true, 'is_archived' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $projFields = [
            ['slug' => 'nt5p_project_name', 'label' => ['en' => 'Project Name', 'ar' => 'اسم المشروع'], 'type' => 'text', 'sort' => 1, 'required' => true, 'step' => 1, 'options' => null],
            ['slug' => 'nt5p_tagline', 'label' => ['en' => 'Project Tagline', 'ar' => 'شعار المشروع'], 'type' => 'text', 'sort' => 2, 'required' => true, 'step' => 1, 'options' => null],
            ['slug' => 'nt5p_problem_addressed', 'label' => ['en' => 'Transportation Problem Addressed', 'ar' => 'مشكلة النقل المعالجة'], 'type' => 'textarea', 'sort' => 3, 'required' => true, 'step' => 1, 'options' => null],
            ['slug' => 'nt5p_solution_description', 'label' => ['en' => 'Solution Description', 'ar' => 'وصف الحل'], 'type' => 'textarea', 'sort' => 4, 'required' => true, 'step' => 1, 'options' => null],
            ['slug' => 'nt5p_target_users', 'label' => ['en' => 'Target Users / Beneficiaries', 'ar' => 'المستخدمون المستهدفون'], 'type' => 'textarea', 'sort' => 5, 'required' => true, 'step' => 1, 'options' => null],
            ['slug' => 'nt5p_estimated_impact', 'label' => ['en' => 'Estimated Number of Beneficiaries', 'ar' => 'العدد التقديري للمستفيدين'], 'type' => 'number', 'sort' => 6, 'required' => true, 'step' => 1, 'options' => null],
            ['slug' => 'nt5p_tech_architecture', 'label' => ['en' => 'Technical Architecture Overview', 'ar' => 'نظرة عامة على البنية التقنية'], 'type' => 'textarea', 'sort' => 7, 'required' => true, 'step' => 2, 'options' => null],
            ['slug' => 'nt5p_tech_stack_used', 'label' => ['en' => 'Technology Stack Used', 'ar' => 'المنصة التقنية المستخدمة'], 'type' => 'text', 'sort' => 8, 'required' => true, 'step' => 2, 'options' => null],
            ['slug' => 'nt5p_data_sources', 'label' => ['en' => 'Data Sources Utilized', 'ar' => 'مصادر البيانات المستخدمة'], 'type' => 'textarea', 'sort' => 9, 'required' => true, 'step' => 2, 'options' => null],
            ['slug' => 'nt5p_ai_ml_usage', 'label' => ['en' => 'AI/ML Components', 'ar' => 'مكونات الذكاء الاصطناعي'], 'type' => 'textarea', 'sort' => 10, 'required' => false, 'step' => 2, 'options' => null],
            ['slug' => 'nt5p_prototype_url', 'label' => ['en' => 'Prototype / Demo URL', 'ar' => 'رابط النموذج الأولي'], 'type' => 'url', 'sort' => 11, 'required' => true, 'step' => 2, 'options' => null],
            ['slug' => 'nt5p_github_repo', 'label' => ['en' => 'GitHub Repository URL', 'ar' => 'رابط مستودع GitHub'], 'type' => 'url', 'sort' => 12, 'required' => true, 'step' => 2, 'options' => null],
            ['slug' => 'nt5p_completion_percentage', 'label' => ['en' => 'Prototype Completion %', 'ar' => 'نسبة اكتمال النموذج'], 'type' => 'number', 'sort' => 13, 'required' => true, 'step' => 2, 'options' => null],
            ['slug' => 'nt5p_business_model', 'label' => ['en' => 'Business Model', 'ar' => 'نموذج العمل'], 'type' => 'dropdown', 'sort' => 14, 'required' => true, 'step' => 3, 'options' => json_encode(['SaaS / Subscription', 'Marketplace / Commission', 'Freemium', 'B2G (Government)', 'B2B', 'B2C', 'Open Source + Services'])],
            ['slug' => 'nt5p_revenue_projection', 'label' => ['en' => 'First Year Revenue Projection (SAR)', 'ar' => 'توقعات الإيرادات للسنة الأولى'], 'type' => 'number', 'sort' => 15, 'required' => true, 'step' => 3, 'options' => null],
            ['slug' => 'nt5p_scalability_plan', 'label' => ['en' => 'Scalability & Growth Plan', 'ar' => 'خطة التوسع والنمو'], 'type' => 'textarea', 'sort' => 16, 'required' => true, 'step' => 3, 'options' => null],
            ['slug' => 'nt5p_vision2030_alignment', 'label' => ['en' => 'Alignment with Saudi Vision 2030', 'ar' => 'التوافق مع رؤية 2030'], 'type' => 'textarea', 'sort' => 17, 'required' => true, 'step' => 3, 'options' => null],
            ['slug' => 'nt5p_sustainability_score', 'label' => ['en' => 'Environmental Sustainability Score (1-10)', 'ar' => 'درجة الاستدامة البيئية'], 'type' => 'number', 'sort' => 18, 'required' => true, 'step' => 3, 'options' => null],
            ['slug' => 'nt5p_pitch_deck', 'label' => ['en' => 'Pitch Deck Upload', 'ar' => 'تحميل العرض التقديمي'], 'type' => 'file', 'sort' => 19, 'required' => true, 'step' => 4, 'options' => null],
            ['slug' => 'nt5p_demo_video', 'label' => ['en' => 'Demo Video URL', 'ar' => 'رابط فيديو العرض'], 'type' => 'url', 'sort' => 20, 'required' => true, 'step' => 4, 'options' => null],
            ['slug' => 'nt5p_technical_doc', 'label' => ['en' => 'Technical Documentation Upload', 'ar' => 'تحميل الوثائق التقنية'], 'type' => 'file', 'sort' => 21, 'required' => true, 'step' => 4, 'options' => null],
            ['slug' => 'nt5p_additional_notes', 'label' => ['en' => 'Additional Notes for Judges', 'ar' => 'ملاحظات إضافية للمحكمين'], 'type' => 'textarea', 'sort' => 22, 'required' => false, 'step' => 4, 'options' => null],
        ];

        $projFieldIds = [];
        $projStepFieldMap = [1 => [], 2 => [], 3 => [], 4 => []];
        foreach ($projFields as $pf) {
            $pfId = DB::table('form_fields')->insertGetId([
                'form_id' => $this->projFormId, 'label' => json_encode($pf['label']), 'type' => $pf['type'],
                'required' => $pf['required'], 'placeholder' => json_encode(['en' => '', 'ar' => '']),
                'options' => $pf['options'], 'sort' => $pf['sort'], 'slug' => $pf['slug'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $projFieldIds[$pf['slug']] = $pfId;
            $projStepFieldMap[$pf['step']][] = $pfId;
        }
        $projStepNames = [1 => ['en' => 'Project Overview', 'ar' => 'نظرة عامة'], 2 => ['en' => 'Technical Details', 'ar' => 'التفاصيل التقنية'], 3 => ['en' => 'Business & Impact', 'ar' => 'الأعمال والأثر'], 4 => ['en' => 'Deliverables', 'ar' => 'المخرجات']];
        foreach ($projStepNames as $order => $name) {
            DB::table('form_steps')->insert(['form_id' => $this->projFormId, 'name' => json_encode($name), 'step_order' => $order, 'field_ids' => json_encode($projStepFieldMap[$order]), 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('project_form_configs')->insert(['form_id' => $this->projFormId, 'allow_track_change' => false, 'is_archived' => false, 'created_at' => now(), 'updated_at' => now()]);
        $this->command->info('   Created project form with ' . count($projFieldIds) . ' fields and 4 steps');

        // ================================================================
        // 7. AI EVALUATION CONFIG & CRITERIA
        // ================================================================
        $this->command->info('Configuring AI evaluation...');
        DB::table('form_ai_scoring_configs')->insert(['form_id' => $this->regFormId, 'ai_prompt' => 'You are an expert hackathon evaluator for the Saudi TGA Naql Thon 5 hackathon. Evaluate this application based on technical skills, innovation potential, problem-solution fit for Saudi transportation, and Vision 2030 alignment.', 'total_weight' => 100, 'created_at' => now(), 'updated_at' => now()]);

        $aiCriteria = [
            ['name' => 'Technical Skills & Experience', 'desc' => 'Depth of technical expertise', 'instruction' => 'Evaluate specialization, experience, portfolio, and tech stack.', 'weight' => 25, 'fields' => ['nt5_specialization', 'nt5_experience_level', 'nt5_github_portfolio', 'nt5_tech_stack']],
            ['name' => 'Problem-Solution Fit', 'desc' => 'Quality of identified problem and proposed solution', 'instruction' => 'Assess problem relevance to Saudi transport and solution viability.', 'weight' => 30, 'fields' => ['nt5_problem_statement', 'nt5_proposed_solution', 'nt5_preferred_track']],
            ['name' => 'Innovation Potential', 'desc' => 'Novelty and creativity of approach', 'instruction' => 'Evaluate innovation type and uniqueness of solution.', 'weight' => 25, 'fields' => ['nt5_innovation_type', 'nt5_proposed_solution', 'nt5_tech_stack']],
            ['name' => 'Team & Execution Readiness', 'desc' => 'Ability to execute during hackathon', 'instruction' => 'Consider hackathon experience, skills, and team readiness.', 'weight' => 20, 'fields' => ['nt5_hackathon_experience', 'nt5_skills_offered', 'nt5_team_status']],
        ];
        foreach ($aiCriteria as $sort => $ac) {
            $critId = DB::table('form_assessment_criteria')->insertGetId(['form_id' => $this->regFormId, 'name' => $ac['name'], 'description' => $ac['desc'], 'instruction' => $ac['instruction'], 'weight' => $ac['weight'], 'status' => 'active', 'sort_order' => $sort + 1, 'created_at' => now(), 'updated_at' => now()]);
            foreach ($ac['fields'] as $slug) {
                if (isset($fieldIds[$slug])) {
                    DB::table('form_assessment_criterion_form_field')->insert(['form_assessment_criterion_id' => $critId, 'form_field_id' => $fieldIds[$slug], 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }
        DB::table('form_ai_enhancement_configs')->insert(['form_id' => $this->regFormId, 'ai_enhancement_enabled' => true, 'ai_enhancement_fields' => json_encode([]), 'created_at' => now(), 'updated_at' => now()]);
        $this->command->info('   Created AI scoring config with 4 criteria');

        // ================================================================
        // 8. REGISTRATION EVALUATION FORMS
        // ================================================================
        $this->command->info('Creating registration evaluation forms...');
        $evalForm1Id = DB::table('registration_evaluation_forms')->insertGetId(['program_id' => $this->programId, 'name' => json_encode(['en' => 'Innovation & Technical Assessment', 'ar' => 'تقييم الابتكار والتقنية']), 'description' => json_encode(['en' => 'Evaluate technical depth and innovation potential', 'ar' => 'تقييم العمق التقني وإمكانات الابتكار']), 'dimension' => 'Technical', 'scoring_scale' => '1-10', 'status' => 'published', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $evalCrit1 = [
            ['name' => ['en' => 'Technical Proficiency', 'ar' => 'الكفاءة التقنية'], 'desc' => ['en' => 'Technical skills depth', 'ar' => 'عمق المهارات التقنية'], 'weight' => 30],
            ['name' => ['en' => 'Solution Creativity', 'ar' => 'إبداع الحل'], 'desc' => ['en' => 'Originality of approach', 'ar' => 'أصالة النهج'], 'weight' => 25],
            ['name' => ['en' => 'Feasibility', 'ar' => 'الجدوى'], 'desc' => ['en' => 'Technical feasibility in hackathon timeframe', 'ar' => 'الجدوى التقنية'], 'weight' => 25],
            ['name' => ['en' => 'Transport Knowledge', 'ar' => 'المعرفة بالنقل'], 'desc' => ['en' => 'Understanding of Saudi transport challenges', 'ar' => 'فهم تحديات النقل السعودية'], 'weight' => 20],
        ];
        $evalCritIds1 = [];
        foreach ($evalCrit1 as $idx => $ec) {
            $evalCritIds1[] = DB::table('registration_evaluation_criteria')->insertGetId(['registration_evaluation_form_id' => $evalForm1Id, 'name' => json_encode($ec['name']), 'description' => json_encode($ec['desc']), 'max_score' => 10, 'weight' => $ec['weight'], 'sort_order' => $idx + 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        $evalForm2Id = DB::table('registration_evaluation_forms')->insertGetId(['program_id' => $this->programId, 'name' => json_encode(['en' => 'Impact & Readiness Assessment', 'ar' => 'تقييم الأثر والجاهزية']), 'description' => json_encode(['en' => 'Evaluate impact potential and execution readiness', 'ar' => 'تقييم الأثر المحتمل والجاهزية']), 'dimension' => 'Business', 'scoring_scale' => '1-10', 'status' => 'published', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()]);

        $evalCrit2 = [
            ['name' => ['en' => 'Market Impact Potential', 'ar' => 'إمكانية التأثير'], 'desc' => ['en' => 'Impact on Saudi transport', 'ar' => 'الأثر على النقل السعودي'], 'weight' => 35],
            ['name' => ['en' => 'Vision 2030 Alignment', 'ar' => 'التوافق مع رؤية 2030'], 'desc' => ['en' => 'Alignment with national goals', 'ar' => 'التوافق مع الأهداف الوطنية'], 'weight' => 30],
            ['name' => ['en' => 'Team Completeness', 'ar' => 'اكتمال الفريق'], 'desc' => ['en' => 'Team skills and readiness', 'ar' => 'مهارات الفريق وجاهزيته'], 'weight' => 35],
        ];
        $evalCritIds2 = [];
        foreach ($evalCrit2 as $idx => $ec) {
            $evalCritIds2[] = DB::table('registration_evaluation_criteria')->insertGetId(['registration_evaluation_form_id' => $evalForm2Id, 'name' => json_encode($ec['name']), 'description' => json_encode($ec['desc']), 'max_score' => 10, 'weight' => $ec['weight'], 'sort_order' => $idx + 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        $evaluatorId = DB::table('registration_evaluators')->insertGetId(['program_id' => $this->programId, 'user_id' => $this->adminUserId, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('registration_evaluator_sections')->insert([
            ['registration_evaluator_id' => $evaluatorId, 'registration_evaluation_form_id' => $evalForm1Id, 'created_at' => now(), 'updated_at' => now()],
            ['registration_evaluator_id' => $evaluatorId, 'registration_evaluation_form_id' => $evalForm2Id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $allEvalCritIds = array_merge(
            array_map(fn($id) => ['id' => $id, 'form_id' => $evalForm1Id], $evalCritIds1),
            array_map(fn($id) => ['id' => $id, 'form_id' => $evalForm2Id], $evalCritIds2)
        );
        $this->command->info('   Created 2 evaluation forms with 7 criteria');

        // ================================================================
        // 9. CREATE 35 PARTICIPANTS + APPLICATIONS + EVALUATIONS + TEAMS + PROJECTS
        // ================================================================
        $this->command->info('Creating participants, applications, teams, and projects...');
        $profiles = $this->getParticipantProfiles();
        $maxSerial = (int) DB::table('participants')->max('serial_number') ?: 100019;
        $participantIds = [];
        $applicationIds = [];
        $approvedAppIds = [];
        $approvedParticipantMap = [];
        $teamIds = [];
        $projectIds = [];

        foreach ($profiles as $i => $p) {
            // Create participant
            $existing = DB::table('participants')->where('email', $p['email'])->first();
            if ($existing) { $participantIds[$i] = $existing->id; } else {
                $participantIds[$i] = DB::table('participants')->insertGetId([
                    'serial_number' => (string) ($maxSerial + $i + 1), 'name' => $p['name'], 'email' => $p['email'], 'phone' => $p['phone'],
                    'gender' => $p['gender'], 'date_of_birth' => $p['dob'], 'nationality_id' => 1, 'country_id' => 1, 'residence_city_id' => $p['city_id'],
                    'password' => Hash::make('password123'), 'educational_background' => $p['education'], 'current_role' => $p['role'],
                    'place_of_work_study' => $p['organization'], 'years_of_experience' => $p['experience'],
                    'experience_or_skills' => $p['skills'], 'key_achievements' => $p['achievements'],
                    'activation_code' => Str::random(6), 'is_active' => true, 'is_archived' => false,
                    'email_verified_at' => now(), 'last_login_at' => now()->subDays(rand(1, 14)),
                    'created_at' => now()->subDays(rand(20, 40)), 'updated_at' => now(),
                ]);
            }

            // Create application
            $isApproved = $i < 30;
            $status = $isApproved ? 'approved' : 'rejected';
            $totalScore = $isApproved ? rand(68, 96) : rand(25, 52);
            $appId = DB::table('program_applications')->insertGetId([
                'program_id' => $this->programId, 'form_id' => $this->regFormId, 'participant_id' => $participantIds[$i],
                'status' => $status, 'registered_as' => 'team', 'has_team' => true,
                'form_submissions' => json_encode($this->buildRegFormSubmissions($p)),
                'type' => 'submission', 'is_archived' => false,
                'assessment_scores' => json_encode($this->generateAssessmentScores($isApproved)),
                'total_score' => $totalScore,
                'ai_evaluation_response' => json_encode($this->generateAiEvalResponse($p, $isApproved, $totalScore)),
                'ai_evaluated_at' => now()->subDays(rand(3, 15)),
                'created_at' => now()->subDays(rand(15, 35)), 'updated_at' => now()->subDays(rand(1, 5)),
            ]);
            $applicationIds[$i] = $appId;

            // Evaluations
            $scoreRange = $isApproved ? [6, 10] : [2, 6];
            foreach ($allEvalCritIds as $crit) {
                DB::table('registration_evaluations')->insert([
                    'program_application_id' => $appId, 'registration_evaluator_id' => $evaluatorId,
                    'registration_evaluation_form_id' => $crit['form_id'], 'registration_evaluation_criterion_id' => $crit['id'],
                    'score' => rand($scoreRange[0], $scoreRange[1]),
                    'comment' => $isApproved ? 'Strong application with clear potential.' : 'Below minimum requirements.',
                    'created_at' => now()->subDays(rand(2, 10)), 'updated_at' => now(),
                ]);
            }

            if ($isApproved) {
                $approvedAppIds[] = $appId;
                $approvedParticipantMap[$appId] = $i;

                // Create team
                $trackIdx = $i % count($trackIds);
                $subTrackIdx = ($i % 3) + ($trackIdx * 3);
                $teamId = DB::table('teams')->insertGetId([
                    'application_id' => $appId, 'name' => $p['team_name'], 'strength' => rand(2, 5),
                    'track_id' => $trackIds[$trackIdx], 'sub_track_id' => $subTrackIds[min($subTrackIdx, count($subTrackIds) - 1)],
                    'idea_description' => $p['idea_desc'], 'previous_participation' => (bool) rand(0, 1),
                    'contact_email' => $p['email'], 'skills' => json_encode($p['team_skills']),
                    'is_published' => true, 'is_completed' => true, 'is_archived' => false,
                    'created_at' => now()->subDays(rand(10, 25)), 'updated_at' => now(),
                ]);
                $teamIds[$appId] = $teamId;
                DB::table('team_members')->insert(['team_id' => $teamId, 'participant_id' => $participantIds[$i], 'is_leader' => true, 'created_at' => now(), 'updated_at' => now()]);

                // Create project
                $projStatus = ($i < 25) ? 'qualified' : (($i < 28) ? 'pending' : 'qualified');
                $projScore = $projStatus === 'pending' ? 0 : rand(65, 98);
                $projectId = DB::table('projects')->insertGetId([
                    'program_id' => $this->programId, 'application_id' => $appId, 'team_id' => $teamId, 'form_id' => $this->projFormId,
                    'status' => $projStatus, 'evaluation_status' => $projStatus !== 'pending', 'total_score' => $projScore,
                    'type' => 'submission', 'form_submissions' => json_encode($this->buildProjectSubmissions($p)),
                    'ai_evaluation_response' => $projStatus !== 'pending' ? json_encode($this->generateProjectAiEval($p, $projScore)) : null,
                    'ai_evaluated_at' => $projStatus !== 'pending' ? now()->subDays(rand(1, 7)) : null,
                    'is_archived' => false, 'created_at' => now()->subDays(rand(3, 10)), 'updated_at' => now(),
                ]);
                $projectIds[] = $projectId;
            }
        }
        $this->command->info('   Created 35 participants, 35 applications, ' . count($teamIds) . ' teams, ' . count($projectIds) . ' projects');

        // ================================================================
        // 10. JUDGES
        // ================================================================
        $this->command->info('Creating judges...');
        $judgesData = [
            ['name' => ['en' => 'Dr. Mansour Al-Turki', 'ar' => 'د. منصور التركي'], 'email' => 'mansour.nt5@tga.gov.sa', 'phone' => '0559900101', 'exp' => ['en' => 'Smart City & Urban Mobility Expert, 15+ years', 'ar' => 'خبير المدن الذكية']],
            ['name' => ['en' => 'Eng. Huda Al-Qahtani', 'ar' => 'م. هدى القحطاني'], 'email' => 'huda.nt5@transport.sa', 'phone' => '0559900102', 'exp' => ['en' => 'AI & Data Science Director at SDAIA', 'ar' => 'مديرة الذكاء الاصطناعي']],
            ['name' => ['en' => 'Dr. Fahad Al-Dossari', 'ar' => 'د. فهد الدوسري'], 'email' => 'fahad.nt5@kacst.sa', 'phone' => '0559900103', 'exp' => ['en' => 'Autonomous Systems Researcher, KACST', 'ar' => 'باحث أنظمة ذاتية']],
        ];
        $judgeIds = [];
        foreach ($judgesData as $jd) {
            $existing = DB::table('judges')->where('email', $jd['email'])->first();
            if ($existing) { $judgeIds[] = $existing->id; continue; }
            $judgeIds[] = DB::table('judges')->insertGetId(['name' => json_encode($jd['name']), 'email' => $jd['email'], 'phone_number' => $jd['phone'], 'experience_field' => json_encode($jd['exp']), 'password' => Hash::make('password123'), 'registration_method' => 'admin-added', 'email_verified_at' => now(), 'is_archived' => false, 'created_at' => now()->subDays(20), 'updated_at' => now()]);
        }
        foreach ($judgeIds as $jid) { DB::table('program_judge')->updateOrInsert(['program_id' => $this->programId, 'judge_id' => $jid], ['created_at' => now(), 'updated_at' => now()]); }

        // ================================================================
        // 11. EVENTS
        // ================================================================
        $this->command->info('Creating events...');
        $eventsData = [
            ['title' => ['en' => 'Opening Ceremony & Keynote', 'ar' => 'حفل الافتتاح والكلمة الرئيسية'], 'brief' => ['en' => 'Welcome address by TGA President and keynote on Future of Saudi Transportation', 'ar' => 'كلمة ترحيبية من رئيس هيئة النقل'], 'badge' => 'upcoming', 'days' => 1, 'time' => '09:00:00', 'location' => 'onsite', 'speaker' => ['en' => 'H.E. Dr. Rumaih Al-Rumaih', 'ar' => 'معالي د. رميح الرميح']],
            ['title' => ['en' => 'Workshop: AI in Transport Systems', 'ar' => 'ورشة: الذكاء الاصطناعي في النقل'], 'brief' => ['en' => 'Hands-on ML workshop for traffic management', 'ar' => 'ورشة تطبيقية في التعلم الآلي'], 'badge' => 'upcoming', 'days' => 1, 'time' => '14:00:00', 'location' => 'onsite', 'speaker' => ['en' => 'Dr. Sarah Al-Amri', 'ar' => 'د. سارة العمري']],
            ['title' => ['en' => 'Mentor Office Hours', 'ar' => 'ساعات المرشدين'], 'brief' => ['en' => 'One-on-one mentoring with experts', 'ar' => 'جلسات إرشاد فردية مع الخبراء'], 'badge' => 'upcoming', 'days' => 2, 'time' => '10:00:00', 'location' => 'onsite', 'speaker' => ['en' => 'Multiple Mentors', 'ar' => 'عدة مرشدين']],
            ['title' => ['en' => 'Mid-Hackathon Check-in', 'ar' => 'متابعة منتصف الهاكاثون'], 'brief' => ['en' => 'Progress check and feedback session', 'ar' => 'جلسة مراجعة التقدم'], 'badge' => 'upcoming', 'days' => 2, 'time' => '15:00:00', 'location' => 'onsite', 'speaker' => ['en' => 'Judge Panel', 'ar' => 'لجنة التحكيم']],
            ['title' => ['en' => 'Final Pitch & Demo Day', 'ar' => 'يوم العروض النهائية'], 'brief' => ['en' => 'Teams present solutions to judges', 'ar' => 'تقدم الفرق حلولها أمام المحكمين'], 'badge' => 'upcoming', 'days' => 3, 'time' => '09:00:00', 'location' => 'onsite', 'speaker' => ['en' => 'All Teams', 'ar' => 'جميع الفرق']],
            ['title' => ['en' => 'Awards Ceremony & Closing', 'ar' => 'حفل الجوائز والختام'], 'brief' => ['en' => 'Winner announcements and networking', 'ar' => 'إعلان الفائزين والتواصل'], 'badge' => 'upcoming', 'days' => 3, 'time' => '16:00:00', 'location' => 'onsite', 'speaker' => ['en' => 'TGA Leadership', 'ar' => 'قيادة هيئة النقل']],
        ];
        foreach ($eventsData as $ev) {
            DB::table('events')->insert(['program_id' => $this->programId, 'title' => json_encode($ev['title']), 'brief' => json_encode($ev['brief']), 'badge' => $ev['badge'], 'date' => Carbon::now()->addDays($ev['days'])->toDateString(), 'time' => $ev['time'], 'location' => $ev['location'], 'speakers' => json_encode([['name' => $ev['speaker'], 'experience' => ['en' => 'Expert in Saudi Transportation', 'ar' => 'خبير في النقل السعودي'], 'brief' => ['en' => 'Leading expert', 'ar' => 'خبير رائد'], 'photo' => 'events/speaker-placeholder.jpg']]), 'event_link' => null, 'is_visible' => true, 'is_archived' => false, 'created_at' => now(), 'updated_at' => now()]);
        }

        // ================================================================
        // 12. GUIDELINES
        // ================================================================
        $guidelinesData = [['en' => 'Hackathon Rules & Code of Conduct', 'ar' => 'قواعد الهاكاثون'], ['en' => 'Technical Submission Requirements', 'ar' => 'متطلبات التسليم التقنية'], ['en' => 'Judging Criteria & Scoring', 'ar' => 'معايير التحكيم'], ['en' => 'IP Guidelines', 'ar' => 'إرشادات الملكية الفكرية'], ['en' => 'Data & API Access Guide', 'ar' => 'دليل البيانات والواجهات'], ['en' => 'Venue & Logistics', 'ar' => 'المكان واللوجستيات']];
        foreach ($guidelinesData as $gd) {
            $gId = DB::table('guidelines')->insertGetId(['program_id' => $this->programId, 'title' => json_encode($gd), 'is_visible' => true, 'is_archived' => false, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('guideline_files')->insert(['guideline_id' => $gId, 'title' => json_encode($gd), 'attachment' => 'guidelines/nt5/' . Str::slug($gd['en']) . '.pdf', 'file_type' => 'pdf', 'description' => json_encode(['en' => 'Guideline document', 'ar' => 'وثيقة إرشادية']), 'created_at' => now(), 'updated_at' => now()]);
        }

        // ================================================================
        // 13. MENTORS
        // ================================================================
        $this->command->info('Creating mentors...');
        $mentorsData = [
            ['name' => ['en' => 'Eng. Abdulaziz Al-Rashidi', 'ar' => 'م. عبدالعزيز الراشدي'], 'email' => 'abdulaziz.mentor.nt5@tga.sa', 'phone' => '0558800101', 'exp' => ['en' => 'Smart Mobility Architect, 12 years', 'ar' => 'مهندس حلول التنقل الذكي'], 'brief' => ['en' => 'ITS expert', 'ar' => 'خبير أنظمة النقل الذكية'], 'track' => 0],
            ['name' => ['en' => 'Dr. Nora Al-Ghamdi', 'ar' => 'د. نورة الغامدي'], 'email' => 'nora.mentor.nt5@sdaia.sa', 'phone' => '0558800102', 'exp' => ['en' => 'AI/ML Lead at SDAIA', 'ar' => 'قائدة الذكاء الاصطناعي في سدايا'], 'brief' => ['en' => 'Transport AI researcher', 'ar' => 'باحثة في الذكاء الاصطناعي للنقل'], 'track' => 1],
            ['name' => ['en' => 'Eng. Majed Al-Otaibi', 'ar' => 'م. ماجد العتيبي'], 'email' => 'majed.mentor.nt5@aramco.sa', 'phone' => '0558800103', 'exp' => ['en' => 'EV Director at Aramco Ventures', 'ar' => 'مدير المركبات الكهربائية'], 'brief' => ['en' => 'EV pioneer', 'ar' => 'رائد المركبات الكهربائية'], 'track' => 2],
            ['name' => ['en' => 'Dr. Amal Al-Zahrani', 'ar' => 'د. أمل الزهراني'], 'email' => 'amal.mentor.nt5@ksu.sa', 'phone' => '0558800104', 'exp' => ['en' => 'Road Safety Researcher, KSU', 'ar' => 'باحثة سلامة مرورية'], 'brief' => ['en' => 'Safety expert', 'ar' => 'خبيرة سلامة'], 'track' => 3],
        ];
        $mentorIds = [];
        foreach ($mentorsData as $md) {
            $existing = DB::table('mentors')->where('email', $md['email'])->first();
            if ($existing) { $mentorIds[] = $existing->id; continue; }
            $mId = DB::table('mentors')->insertGetId(['program_id' => $this->programId, 'name' => json_encode($md['name']), 'experience' => json_encode($md['exp']), 'brief' => json_encode($md['brief']), 'image' => 'mentors/placeholder.jpg', 'email' => $md['email'], 'phone' => $md['phone'], 'status' => 'active', 'is_visible' => true, 'track_id' => $trackIds[$md['track']] ?? null, 'is_archived' => false, 'created_at' => now()->subDays(15), 'updated_at' => now()]);
            $mentorIds[] = $mId;
            DB::table('mentor_programs')->insert(['mentor_id' => $mId, 'program_id' => $this->programId, 'created_at' => now(), 'updated_at' => now()]);
        }
        $tc = 0;
        foreach ($teamIds as $teamId) {
            DB::table('mentor_team')->updateOrInsert(['mentor_id' => $mentorIds[$tc % count($mentorIds)], 'team_id' => $teamId], ['assigned_by' => $this->adminUserId, 'assigned_at' => now()->subDays(rand(3, 10)), 'notes' => 'Naql Thon 5 mentorship', 'created_at' => now(), 'updated_at' => now()]);
            $tc++;
        }

        // ================================================================
        // 14. TASK TEMPLATES & ASSIGNMENTS
        // ================================================================
        $this->command->info('Creating tasks...');
        $taskTemplates = [
            ['title' => ['en' => 'Define Problem Statement', 'ar' => 'تحديد بيان المشكلة'], 'desc' => ['en' => 'Define the transportation problem and user personas.', 'ar' => 'حدد مشكلة النقل وشخصيات المستخدمين.'], 'difficulty' => 'easy', 'hours' => 3, 'category' => 'Ideation'],
            ['title' => ['en' => 'Technical Architecture Doc', 'ar' => 'وثيقة البنية التقنية'], 'desc' => ['en' => 'Create technical architecture diagram.', 'ar' => 'أنشئ مخطط البنية التقنية.'], 'difficulty' => 'medium', 'hours' => 5, 'category' => 'Technical'],
            ['title' => ['en' => 'Build MVP Prototype', 'ar' => 'بناء النموذج الأولي'], 'desc' => ['en' => 'Develop minimum viable prototype.', 'ar' => 'طور نموذجاً أولياً.'], 'difficulty' => 'hard', 'hours' => 16, 'category' => 'Development'],
            ['title' => ['en' => 'Prepare Pitch Deck', 'ar' => 'إعداد العرض التقديمي'], 'desc' => ['en' => 'Create 10-slide pitch deck.', 'ar' => 'أنشئ عرضاً من 10 شرائح.'], 'difficulty' => 'medium', 'hours' => 4, 'category' => 'Presentation'],
            ['title' => ['en' => 'Record Demo Video', 'ar' => 'تسجيل فيديو العرض'], 'desc' => ['en' => 'Record 3-minute demo video.', 'ar' => 'سجل فيديو عرض 3 دقائق.'], 'difficulty' => 'easy', 'hours' => 2, 'category' => 'Presentation'],
        ];
        $templateIds = [];
        foreach ($taskTemplates as $tt) {
            $templateIds[] = DB::table('task_templates')->insertGetId(['program_id' => $this->programId, 'title' => json_encode($tt['title']), 'description' => json_encode($tt['desc']), 'instructions' => json_encode(['en' => 'Follow hackathon guidelines.', 'ar' => 'اتبع إرشادات الهاكاثون.']), 'difficulty_level' => $tt['difficulty'], 'estimated_hours' => $tt['hours'], 'category' => $tt['category'], 'version' => 1, 'created_by' => $this->adminUserId, 'is_archived' => false, 'created_at' => now()->subDays(5), 'updated_at' => now()]);
        }

        // "All" assignments
        foreach ([0, 1] as $tIdx) {
            DB::table('task_assignments')->insert(['task_template_id' => $templateIds[$tIdx], 'program_id' => $this->programId, 'stage_id' => $stageIds['ideation-nt5'], 'assignment_type' => 'all', 'title' => json_encode($taskTemplates[$tIdx]['title']), 'description' => json_encode($taskTemplates[$tIdx]['desc']), 'instructions' => json_encode(['en' => 'Complete by deadline.', 'ar' => 'أكمل قبل الموعد.']), 'due_date' => Carbon::now()->addDays(3)->toDateString(), 'status' => 'not_started', 'assigned_by' => $this->adminUserId, 'is_archived' => false, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Individual team assignments with varied statuses
        $teams = collect($teamIds)->values();
        $statuses = ['approved', 'submitted', 'in_progress', 'revision_requested', 'not_started'];
        foreach ($teams->take(15) as $tIdx => $teamId) {
            $st = $statuses[$tIdx % count($statuses)];
            $appId = array_search($teamId, $teamIds);
            $pIdx = $approvedParticipantMap[$appId] ?? 0;

            $aId = DB::table('task_assignments')->insertGetId(['task_template_id' => $templateIds[2], 'program_id' => $this->programId, 'stage_id' => $stageIds['prototyping-nt5'], 'assignment_type' => 'team', 'team_id' => $teamId, 'title' => json_encode(['en' => 'Build MVP Prototype', 'ar' => 'بناء النموذج الأولي']), 'description' => json_encode(['en' => 'Develop your MVP.', 'ar' => 'طور نموذجك.']), 'instructions' => json_encode(['en' => 'Focus on core features.', 'ar' => 'ركز على الميزات الأساسية.']), 'due_date' => Carbon::now()->addDays(6)->toDateString(), 'status' => $st, 'assigned_by' => $this->adminUserId, 'submitted_at' => in_array($st, ['submitted', 'approved', 'revision_requested']) ? now()->subDays(rand(1, 3)) : null, 'reviewed_at' => in_array($st, ['approved', 'revision_requested']) ? now()->subDays(1) : null, 'reviewed_by' => in_array($st, ['approved', 'revision_requested']) ? $this->adminUserId : null, 'is_archived' => false, 'created_at' => now()->subDays(2), 'updated_at' => now()]);

            if (in_array($st, ['submitted', 'approved', 'revision_requested'])) {
                DB::table('task_submissions')->insert(['task_assignment_id' => $aId, 'submitted_by' => $participantIds[$pIdx], 'files' => json_encode([['name' => 'prototype-v1.zip', 'path' => 'task_submissions/' . $aId . '/prototype.zip', 'size' => rand(500000, 5000000), 'type' => 'application/zip']]), 'notes' => 'MVP prototype submission.', 'version' => 1, 'status' => $st === 'approved' ? 'approved' : ($st === 'revision_requested' ? 'revision_requested' : 'submitted'), 'admin_feedback' => $st === 'approved' ? 'Great work!' : ($st === 'revision_requested' ? 'Please improve error handling.' : null), 'reviewed_by' => in_array($st, ['approved', 'revision_requested']) ? $this->adminUserId : null, 'reviewed_at' => in_array($st, ['approved', 'revision_requested']) ? now()->subDays(1) : null, 'submitted_at' => now()->subDays(rand(1, 3)), 'created_at' => now(), 'updated_at' => now()]);
            }
            if (in_array($st, ['submitted', 'approved', 'in_progress', 'revision_requested'])) {
                DB::table('task_comments')->insert(['task_assignment_id' => $aId, 'commentable_type' => 'App\\Models\\User', 'commentable_id' => $this->adminUserId, 'body' => collect(['Great progress!', 'Focus on core user flow.', 'Test on mobile devices.', 'Looking forward to submission.'])->random(), 'is_internal' => false, 'created_at' => now()->subDays(1), 'updated_at' => now()]);
                DB::table('task_comments')->insert(['task_assignment_id' => $aId, 'commentable_type' => 'App\\Models\\Participant', 'commentable_id' => $participantIds[$pIdx], 'body' => collect(['Working on it!', 'Need API clarification.', 'Thanks for feedback!', 'Making good progress.'])->random(), 'is_internal' => false, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        // Individual participant assignments
        foreach ($teams->take(5) as $tIdx => $teamId) {
            $appId = array_search($teamId, $teamIds);
            $pIdx = $approvedParticipantMap[$appId] ?? 0;
            DB::table('task_assignments')->insert(['task_template_id' => $templateIds[3], 'program_id' => $this->programId, 'stage_id' => $stageIds['prototyping-nt5'], 'assignment_type' => 'participant', 'participant_id' => $participantIds[$pIdx], 'title' => json_encode(['en' => 'Prepare Pitch Deck', 'ar' => 'إعداد العرض']), 'description' => json_encode(['en' => 'Create pitch deck.', 'ar' => 'أنشئ العرض.']), 'instructions' => json_encode(['en' => '10 slides max.', 'ar' => '10 شرائح.']), 'due_date' => Carbon::now()->addDays(5)->toDateString(), 'status' => $tIdx < 2 ? 'submitted' : 'not_started', 'assigned_by' => $this->adminUserId, 'submitted_at' => $tIdx < 2 ? now()->subDays(1) : null, 'is_archived' => false, 'created_at' => now(), 'updated_at' => now()]);
        }

        // ================================================================
        // 15. DASHBOARD
        // ================================================================
        $this->command->info('Creating dashboard...');
        $dashId = DB::table('dashboards')->insertGetId(['program_id' => $this->programId, 'name' => json_encode(['en' => 'Naql Thon 5 Analytics Dashboard', 'ar' => 'لوحة تحليلات نقل ثون 5']), 'description' => json_encode(['en' => 'Comprehensive analytics for Naql Thon 5', 'ar' => 'تحليلات شاملة لنقل ثون 5']), 'data_sources' => json_encode(['applications', 'projects', 'tasks']), 'sort_order' => 1, 'created_by' => $this->adminUserId, 'is_archived' => false, 'created_at' => now(), 'updated_at' => now()]);

        $widgets = [
            ['param' => 'nt5_specialization', 'agg' => 'count', 'viz' => 'pie', 'fid' => $fieldIds['nt5_specialization'], 'cfg' => ['title' => 'Participants by Specialization']],
            ['param' => 'nt5_saudi_city', 'agg' => 'count', 'viz' => 'bar', 'fid' => $fieldIds['nt5_saudi_city'], 'cfg' => ['title' => 'Participants by City']],
            ['param' => 'nt5_preferred_track', 'agg' => 'count', 'viz' => 'pie', 'fid' => $fieldIds['nt5_preferred_track'], 'cfg' => ['title' => 'Track Distribution']],
            ['param' => 'nt5_experience_level', 'agg' => 'count', 'viz' => 'bar', 'fid' => $fieldIds['nt5_experience_level'], 'cfg' => ['title' => 'Experience Levels']],
            ['param' => 'nt5_hackathon_experience', 'agg' => 'average', 'viz' => 'kpi', 'fid' => $fieldIds['nt5_hackathon_experience'], 'cfg' => ['title' => 'Avg Hackathon Experience']],
            ['param' => 'nt5_innovation_type', 'agg' => 'count', 'viz' => 'pie', 'fid' => $fieldIds['nt5_innovation_type'], 'cfg' => ['title' => 'Innovation Types']],
            ['param' => 'nt5_team_status', 'agg' => 'count', 'viz' => 'bar', 'fid' => $fieldIds['nt5_team_status'], 'cfg' => ['title' => 'Team Status']],
            ['param' => 'nt5_how_heard', 'agg' => 'count', 'viz' => 'pie', 'fid' => $fieldIds['nt5_how_heard'], 'cfg' => ['title' => 'Marketing Channel']],
            ['param' => 'nt5p_business_model', 'agg' => 'count', 'viz' => 'bar', 'fid' => $projFieldIds['nt5p_business_model'], 'cfg' => ['title' => 'Business Models']],
            ['param' => 'nt5p_estimated_impact', 'agg' => 'sum', 'viz' => 'kpi', 'fid' => $projFieldIds['nt5p_estimated_impact'], 'cfg' => ['title' => 'Total Beneficiaries']],
            ['param' => 'nt5p_revenue_projection', 'agg' => 'average', 'viz' => 'kpi', 'fid' => $projFieldIds['nt5p_revenue_projection'], 'cfg' => ['title' => 'Avg Revenue Projection']],
            ['param' => 'nt5p_completion_percentage', 'agg' => 'average', 'viz' => 'kpi', 'fid' => $projFieldIds['nt5p_completion_percentage'], 'cfg' => ['title' => 'Avg Prototype Completion']],
            ['param' => 'nt5p_sustainability_score', 'agg' => 'average', 'viz' => 'kpi', 'fid' => $projFieldIds['nt5p_sustainability_score'], 'cfg' => ['title' => 'Avg Sustainability Score']],
        ];
        foreach ($widgets as $order => $w) {
            DB::table('dashboard_widgets')->insert(['dashboard_id' => $dashId, 'form_field_id' => $w['fid'], 'parameter_key' => $w['param'], 'aggregation_type' => $w['agg'], 'visualization_type' => $w['viz'], 'configuration' => json_encode($w['cfg']), 'sort_order' => $order + 1, 'created_at' => now(), 'updated_at' => now()]);
        }

        // ================================================================
        // 16. LANDING PAGE & SERVICES & PAGES
        // ================================================================
        $this->command->info('Creating pages...');
        DB::table('landing_pages')->insert(['title' => 'Naql Thon 5', 'content' => json_encode(['hero' => ['en' => 'Naql Thon 5 - Innovate the Future of Saudi Transportation', 'ar' => 'نقل ثون 5 - ابتكر مستقبل النقل السعودي'], 'subtitle' => ['en' => '48 Hours to Transform How Saudi Arabia Moves', 'ar' => '48 ساعة لتحويل طريقة تنقل المملكة'], 'stats' => ['participants' => '500+', 'prizes' => 'SAR 500,000', 'tracks' => '4', 'mentors' => '20+']]), 'government_verification_banner_enabled' => true, 'dga_registration_number' => 'TGA-2026-NT5', 'dga_certificate_url' => 'https://tga.gov.sa/certificates/naqlthon5', 'created_at' => now(), 'updated_at' => now()]);

        $servicesData = [
            ['title' => ['en' => 'Hackathon Registration & Team Formation', 'ar' => 'التسجيل وتشكيل الفرق'], 'content' => ['en' => 'Register and form your dream team with smart matching.', 'ar' => 'سجل وشكّل فريقك مع المطابقة الذكية.'], 'order' => 3],
            ['title' => ['en' => 'Mentorship & Expert Guidance', 'ar' => 'الإرشاد والتوجيه'], 'content' => ['en' => 'Connect with industry-leading mentors.', 'ar' => 'تواصل مع مرشدين رائدين.'], 'order' => 4],
            ['title' => ['en' => 'Project Evaluation & Judging', 'ar' => 'تقييم المشاريع والتحكيم'], 'content' => ['en' => 'AI-powered evaluation combined with expert judging.', 'ar' => 'تقييم بالذكاء الاصطناعي مع تحكيم خبير.'], 'order' => 5],
        ];
        foreach ($servicesData as $sd) {
            DB::table('services')->insert(['title' => json_encode($sd['title']), 'metadata' => json_encode(['category' => 'hackathon']), 'content' => json_encode($sd['content']), 'relatedServices' => json_encode([]), 'is_published' => true, 'order' => $sd['order'], 'created_at' => now(), 'updated_at' => now()]);
        }

        foreach ([
            ['slug' => 'naqlthon5-about', 'title' => ['en' => 'About Naql Thon 5', 'ar' => 'عن نقل ثون 5'], 'content' => ['en' => 'Naql Thon 5 is the fifth edition of Saudi Arabia\'s premier transportation hackathon.', 'ar' => 'نقل ثون 5 هو النسخة الخامسة من هاكاثون النقل الرائد.']],
            ['slug' => 'naqlthon5-prizes', 'title' => ['en' => 'Prizes & Awards', 'ar' => 'الجوائز'], 'content' => ['en' => 'Total prize pool: SAR 500,000. Grand Prize: SAR 200,000.', 'ar' => 'مجموع الجوائز 500,000 ريال.']],
            ['slug' => 'naqlthon5-faq', 'title' => ['en' => 'FAQ', 'ar' => 'الأسئلة الشائعة'], 'content' => ['en' => 'Common questions about Naql Thon 5.', 'ar' => 'أسئلة شائعة حول نقل ثون 5.']],
        ] as $pg) {
            DB::table('pages')->insert(['slug' => $pg['slug'], 'title' => json_encode($pg['title']), 'content' => json_encode($pg['content']), 'is_published' => true, 'created_at' => now(), 'updated_at' => now()]);
        }

        // ================================================================
        // SUMMARY
        // ================================================================
        $this->command->info('');
        $this->command->info('================================================');
        $this->command->info('  Naql Thon 5 Hackathon Creation Complete!');
        $this->command->info('================================================');
        $this->command->info('  Program ID: ' . $this->programId);
        $this->command->info('  Tracks: ' . count($trackIds) . ' with ' . count($subTrackIds) . ' sub-tracks');
        $this->command->info('  Reg Form: ' . count($fieldIds) . ' fields, 3 steps');
        $this->command->info('  Project Form: ' . count($projFieldIds) . ' fields, 4 steps');
        $this->command->info('  AI Scoring: 4 criteria');
        $this->command->info('  Eval Forms: 2 (7 criteria)');
        $this->command->info('  Participants: 35');
        $this->command->info('  Teams: ' . count($teamIds));
        $this->command->info('  Projects: ' . count($projectIds));
        $this->command->info('  Judges: 3, Events: 6, Guidelines: 6, Mentors: 4');
        $this->command->info('  Dashboard: 1 (' . count($widgets) . ' widgets)');
        $this->command->info('  Pages: 3 + Landing + 3 Services');
        $this->command->info('================================================');
    }

    // ================================================================
    // HELPER METHODS
    // ================================================================

    private function getParticipantProfiles(): array
    {
        $specs = ['Software Engineering', 'Data Science & AI', 'UX/UI Design', 'Business & Strategy', 'Hardware & IoT', 'Cybersecurity', 'Project Management'];
        $expLevels = ['No Experience', '1-2 Years', '3-5 Years', '5-10 Years', '10+ Years'];
        $cities = ['Riyadh', 'Jeddah', 'Dammam', 'Makkah', 'Madinah', 'Abha', 'Tabuk'];
        $tracks = ['Smart Mobility & MaaS', 'Freight & Logistics', 'Autonomous & EV Technology', 'Safety & Sustainability'];
        $innovTypes = ['Product Innovation', 'Process Innovation', 'Service Innovation', 'Business Model Innovation'];
        $teamStatuses = ['Yes, complete team', 'Yes, looking for more members', 'No, looking for a team'];
        $dietOpts = ['None', 'None', 'None', 'Vegetarian', 'None', 'None', 'Halal Only'];
        $sizes = ['S', 'M', 'L', 'XL', 'M', 'L', 'M'];
        $howHeard = ['Social Media', 'TGA Website', 'University', 'Friend/Colleague', 'Previous Edition', 'News/Media'];
        $bizModels = ['SaaS / Subscription', 'Marketplace / Commission', 'Freemium', 'B2G (Government)', 'B2B', 'B2C', 'Open Source + Services'];

        $profiles = [];
        $names = [
            ['Omar Al-Harbi', 'male'], ['Fatima Al-Zahrani', 'female'], ['Youssef Al-Qahtani', 'male'], ['Norah Al-Dosari', 'female'],
            ['Abdulrahman Al-Shehri', 'male'], ['Haya Al-Otaibi', 'female'], ['Saud Al-Maliki', 'male'], ['Lama Al-Rasheed', 'female'],
            ['Turki Al-Ghamdi', 'male'], ['Maha Al-Tamimi', 'female'], ['Bandar Al-Subaie', 'male'], ['Reem Al-Jaber', 'female'],
            ['Faisal Al-Mutairi', 'male'], ['Dalal Al-Anazi', 'female'], ['Nawaf Al-Dossary', 'male'], ['Aisha Al-Salem', 'female'],
            ['Khalid Al-Omari', 'male'], ['Salma Al-Enazi', 'female'], ['Hamad Al-Ahmadi', 'male'], ['Nouf Al-Khaldi', 'female'],
            ['Sultan Al-Balawi', 'male'], ['Ghada Al-Sulami', 'female'], ['Badr Al-Harthy', 'male'], ['Rania Al-Fahad', 'female'],
            ['Saad Al-Jubeir', 'male'], ['Arwa Al-Shamsi', 'female'], ['Majed Al-Ruwaili', 'male'], ['Wijdan Al-Sahli', 'female'],
            ['Nasser Al-Thubaiti', 'male'], ['Dana Al-Shahrani', 'female'],
            // 5 rejected
            ['Anas Al-Saadi', 'male'], ['Hadeel Al-Khateeb', 'female'], ['Rakan Al-Bluwi', 'male'], ['Munira Al-Nofal', 'female'], ['Ziyad Al-Dabbagh', 'male'],
        ];

        $projectNames = [
            'RiyadhGo', 'WaslTrack', 'SafarAI', 'NaqlTech Hub', 'Maseer Navigator',
            'TariqSmart', 'Mawj Logistics', 'Bayanat Fleet', 'HarakatEV', 'Qitar Connect',
            'DarbyAuto', 'ShahiqSafety', 'NuzulExpress', 'RukhsatGreen', 'Masar360',
            'TareeqAI', 'Wusuul', 'RahalMaaS', 'JisrBridge', 'MuruurSense',
            'Tamkeen Transit', 'Tawasul Net', 'SafeerDrone', 'NaqlChain', 'ImdadAI',
            'Raqib Safety', 'MusafirConnect', 'Tatweer Mobility', 'Sunduq Freight', 'AqelRoute',
            'QuickNaql', 'BasicRide', 'SimpleTrack', 'TestDrive', 'NoInnovation',
        ];

        for ($i = 0; $i < 35; $i++) {
            $isApproved = $i < 30;
            $slug = strtolower(str_replace(' ', '', $names[$i][0]));
            $trackIdx = $i % 4;
            $specIdx = $i % count($specs);
            $expIdx = $isApproved ? rand(2, 4) : rand(0, 1);

            $problems = [
                'Traffic congestion in Riyadh causes 45-minute average delays during peak hours, costing the economy billions annually.',
                'Last-mile delivery in Saudi cities faces 35% failure rate due to poor address systems and coordination.',
                'Public transit ridership in Jeddah is below 5% despite massive infrastructure investment in metro and BRT.',
                'Road accidents in the Kingdom claim over 9,000 lives annually, with young drivers disproportionately affected.',
                'EV adoption in Saudi Arabia is below 1% due to limited charging infrastructure and range anxiety.',
                'Freight trucks in Saudi Arabia run empty 40% of the time, wasting fuel and increasing emissions.',
                'Women in Saudi cities face limited safe transportation options for short-distance trips.',
                'Hajj and Umrah pilgrims experience severe congestion and wayfinding challenges during peak seasons.',
            ];

            $solutions = [
                'AI-powered traffic management platform using real-time data from IoT sensors to dynamically optimize signal timing and route recommendations.',
                'Blockchain-based address verification system with smart locker network for guaranteed last-mile delivery success.',
                'Gamified public transit app with rewards, real-time tracking, and multimodal trip planning.',
                'Computer vision-based driver monitoring system with real-time fatigue and distraction alerts.',
                'Smart EV charging network with solar-powered stations and AI-optimized charging schedules.',
                'Digital freight matching platform using ML to predict demand and optimize truck loading.',
                'Women-focused micro-transit service with safety features and community-verified drivers.',
                'AR wayfinding app for Hajj pilgrims with crowd density mapping and emergency routing.',
            ];

            $profiles[] = [
                'name' => $names[$i][0],
                'gender' => $names[$i][1],
                'email' => $slug . '.nt5@hackathon.sa',
                'phone' => '055' . str_pad((string)($i + 1), 7, '0', STR_PAD_LEFT),
                'dob' => Carbon::create(rand(1988, 2002), rand(1, 12), rand(1, 28))->toDateString(),
                'city_id' => ($i % 3) + 1,
                'education' => $isApproved ? collect(['master', 'bachelor', 'phd'])->random() : 'bachelor',
                'role' => $isApproved ? 'private_sector_employee' : collect(['university_student', 'recently_graduated'])->random(),
                'organization' => $isApproved ? collect(['KAUST', 'Aramco Digital', 'STC', 'NEOM', 'SDAIA', 'TGA', 'King Saud University', 'stc pay', 'Uber Saudi', 'Careem'])->random() : 'Student',
                'experience' => $expLevels[$expIdx],
                'skills' => $isApproved ? implode(', ', array_slice(['Python', 'TensorFlow', 'React', 'Flutter', 'Node.js', 'AWS', 'Docker', 'Figma', 'IoT', 'Blockchain', 'GIS', 'Computer Vision'], 0, rand(3, 6))) : 'Basic programming',
                'achievements' => $isApproved ? 'Won ' . rand(1, 5) . ' hackathons. Published ' . rand(0, 3) . ' papers. Built solutions used by ' . number_format(rand(1000, 50000)) . '+ users.' : 'University student with basic project experience.',
                'team_name' => $projectNames[$i],
                'team_skills' => array_slice(['AI/ML', 'Frontend', 'Backend', 'UX Design', 'IoT', 'Data Science', 'Mobile Dev', 'DevOps'], 0, rand(3, 5)),
                'idea_desc' => $isApproved ? $solutions[$i % count($solutions)] : 'A basic app for transportation.',
                'project_name' => $projectNames[$i],
                'project_summary' => $isApproved ? 'Innovative solution addressing ' . strtolower($tracks[$trackIdx]) . ' challenges in Saudi Arabia.' : 'Simple transportation app.',
                'project_desc' => $isApproved ? $solutions[$i % count($solutions)] . ' Built with modern tech stack and designed for Saudi market.' : 'Basic app concept.',
                'spec' => $specs[$specIdx],
                'exp_level' => $expLevels[$expIdx],
                'city' => $cities[$i % count($cities)],
                'track' => $tracks[$trackIdx],
                'problem' => $isApproved ? $problems[$i % count($problems)] : 'General transportation issues.',
                'solution' => $isApproved ? $solutions[$i % count($solutions)] : 'A basic app.',
                'tech_stack' => $isApproved ? implode(', ', array_slice(['Python', 'React', 'Node.js', 'TensorFlow', 'PostgreSQL', 'Docker', 'AWS', 'Flutter'], 0, rand(3, 5))) : 'HTML, CSS',
                'innov_type' => $innovTypes[$i % count($innovTypes)],
                'hack_exp' => $isApproved ? (string) rand(1, 8) : '0',
                'team_status' => $teamStatuses[$i % count($teamStatuses)],
                'skills_offered' => $isApproved ? 'Full-stack development, ML model training, data pipeline design, API architecture' : 'Basic coding',
                'diet' => $dietOpts[$i % count($dietOpts)],
                'tshirt' => $sizes[$i % count($sizes)],
                'how_heard' => $howHeard[$i % count($howHeard)],
                'motivation' => $isApproved ? 'Passionate about transforming Saudi transportation. Want to contribute to Vision 2030 goals and build solutions that impact millions of commuters.' : 'Want to learn about hackathons.',
                'biz_model' => $bizModels[$i % count($bizModels)],
                'revenue' => $isApproved ? (string) rand(500000, 5000000) : '50000',
                'completion_pct' => $isApproved ? (string) rand(60, 95) : '20',
                'sustainability' => $isApproved ? (string) rand(6, 10) : (string) rand(2, 4),
            ];
        }
        return $profiles;
    }

    private function buildRegFormSubmissions(array $p): array
    {
        return [
            'nt5_specialization' => $p['spec'],
            'nt5_experience_level' => $p['exp_level'],
            'nt5_saudi_city' => $p['city'],
            'nt5_organization' => $p['organization'],
            'nt5_linkedin_url' => 'https://linkedin.com/in/' . strtolower(str_replace(' ', '-', $p['name'])),
            'nt5_github_portfolio' => 'https://github.com/' . strtolower(str_replace(' ', '', $p['name'])),
            'nt5_preferred_track' => $p['track'],
            'nt5_problem_statement' => $p['problem'],
            'nt5_proposed_solution' => $p['solution'],
            'nt5_tech_stack' => $p['tech_stack'],
            'nt5_innovation_type' => $p['innov_type'],
            'nt5_hackathon_experience' => $p['hack_exp'],
            'nt5_team_status' => $p['team_status'],
            'nt5_skills_offered' => $p['skills_offered'],
            'nt5_dietary_requirements' => $p['diet'],
            'nt5_tshirt_size' => $p['tshirt'],
            'nt5_accessibility_needs' => 'None',
            'nt5_how_heard' => $p['how_heard'],
            'nt5_motivation' => $p['motivation'],
            'nt5_resume' => 'applications/nt5/' . strtolower(str_replace(' ', '-', $p['name'])) . '/resume.pdf',
        ];
    }

    private function buildProjectSubmissions(array $p): array
    {
        $slug = strtolower(str_replace(' ', '-', $p['project_name']));
        return [
            'nt5p_project_name' => $p['project_name'],
            'nt5p_tagline' => 'Transforming Saudi transportation through ' . strtolower($p['innov_type']),
            'nt5p_problem_addressed' => $p['problem'],
            'nt5p_solution_description' => $p['solution'],
            'nt5p_target_users' => 'Daily commuters, logistics companies, and government transport authorities in Saudi Arabia',
            'nt5p_estimated_impact' => (string) rand(10000, 500000),
            'nt5p_tech_architecture' => 'Microservices architecture with ' . $p['tech_stack'] . '. Deployed on cloud with auto-scaling. RESTful API backend with real-time WebSocket connections.',
            'nt5p_tech_stack_used' => $p['tech_stack'],
            'nt5p_data_sources' => 'TGA open data, OpenStreetMap, real-time GPS feeds, traffic sensor data, weather APIs',
            'nt5p_ai_ml_usage' => 'Machine learning for demand prediction, NLP for user feedback analysis, computer vision for safety monitoring',
            'nt5p_prototype_url' => 'https://' . $slug . '.demo.sa',
            'nt5p_github_repo' => 'https://github.com/naqlthon5/' . $slug,
            'nt5p_completion_percentage' => $p['completion_pct'],
            'nt5p_business_model' => $p['biz_model'],
            'nt5p_revenue_projection' => $p['revenue'],
            'nt5p_scalability_plan' => 'Phase 1: Launch in Riyadh. Phase 2: Expand to Jeddah and Dammam. Phase 3: Cover all major Saudi cities. Phase 4: GCC expansion.',
            'nt5p_vision2030_alignment' => 'Directly supports Vision 2030 goals: improving quality of life, enhancing transportation infrastructure, promoting innovation, and reducing environmental impact.',
            'nt5p_sustainability_score' => $p['sustainability'],
            'nt5p_pitch_deck' => 'projects/nt5/' . $slug . '/pitch-deck.pdf',
            'nt5p_demo_video' => 'https://youtube.com/watch?v=' . Str::random(11),
            'nt5p_technical_doc' => 'projects/nt5/' . $slug . '/technical-doc.pdf',
            'nt5p_additional_notes' => 'Our team is passionate about solving this problem. We have validated the concept with ' . rand(50, 500) . ' potential users.',
        ];
    }

    private function generateAssessmentScores(bool $isApproved): array
    {
        return [
            'technical_skills' => $isApproved ? rand(16, 25) : rand(5, 13),
            'problem_solution_fit' => $isApproved ? rand(18, 30) : rand(5, 15),
            'innovation_potential' => $isApproved ? rand(15, 25) : rand(4, 12),
            'team_execution' => $isApproved ? rand(13, 20) : rand(4, 10),
        ];
    }

    private function generateAiEvalResponse(array $p, bool $isApproved, int $totalScore): array
    {
        return [
            'status' => 'completed',
            'data' => ['criteria' => [
                ['criteriaId' => '1', 'name' => 'Technical Skills & Experience', 'totalScore' => $isApproved ? rand(16, 25) : rand(5, 13), 'maxWeight' => 25, 'feedback' => $isApproved ? 'Strong technical background in ' . $p['spec'] . ' with relevant experience.' : 'Limited technical skills for hackathon scope.'],
                ['criteriaId' => '2', 'name' => 'Problem-Solution Fit', 'totalScore' => $isApproved ? rand(18, 30) : rand(5, 15), 'maxWeight' => 30, 'feedback' => $isApproved ? 'Well-defined problem with viable solution approach for Saudi transport.' : 'Weak problem definition and generic solution.'],
                ['criteriaId' => '3', 'name' => 'Innovation Potential', 'totalScore' => $isApproved ? rand(15, 25) : rand(4, 12), 'maxWeight' => 25, 'feedback' => $isApproved ? 'Innovative approach with clear differentiation.' : 'Lacks innovation or novelty.'],
                ['criteriaId' => '4', 'name' => 'Team & Execution Readiness', 'totalScore' => $isApproved ? rand(13, 20) : rand(4, 10), 'maxWeight' => 20, 'feedback' => $isApproved ? 'Experienced team with hackathon track record.' : 'Insufficient team readiness.'],
            ]],
            'message' => 'AI evaluation completed',
            'meta' => ['total_score' => $totalScore, 'max_weight' => 100, 'normalized_score' => $totalScore],
        ];
    }

    private function generateProjectAiEval(array $p, int $score): array
    {
        return [
            'status' => 'completed',
            'data' => ['criteria' => [
                ['criteriaId' => '1', 'name' => 'Technical Implementation', 'totalScore' => (int)($score * 0.3 + rand(-3, 3)), 'maxWeight' => 30, 'feedback' => 'Solid technical implementation using ' . $p['tech_stack'] . '.'],
                ['criteriaId' => '2', 'name' => 'Innovation & Creativity', 'totalScore' => (int)($score * 0.25 + rand(-3, 3)), 'maxWeight' => 25, 'feedback' => 'Creative approach to solving transportation challenges.'],
                ['criteriaId' => '3', 'name' => 'Business Viability', 'totalScore' => (int)($score * 0.20 + rand(-3, 3)), 'maxWeight' => 20, 'feedback' => 'Viable business model with clear revenue path.'],
                ['criteriaId' => '4', 'name' => 'Impact & Scalability', 'totalScore' => (int)($score * 0.25 + rand(-3, 3)), 'maxWeight' => 25, 'feedback' => 'Strong potential impact on Saudi transport ecosystem.'],
            ]],
            'message' => 'Project AI evaluation completed',
            'meta' => ['total_score' => $score, 'max_weight' => 100, 'normalized_score' => $score],
        ];
    }
}
