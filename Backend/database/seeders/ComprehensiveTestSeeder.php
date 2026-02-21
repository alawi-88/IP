<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ComprehensiveTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Comprehensive Test Seeder...');

        // ─── CLEANUP: Truncate all tables we'll seed (to allow re-running) ───
        $this->command->info('Cleaning up existing data...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $tablesToTruncate = [
            'assessment_criteria', 'form_assessment_criteria', 'form_ai_enhancement_configs', 'form_ai_scoring_configs',
            'notification_management', 'email_templates', 'notification_messages',
            'approval_levels', 'approval_workflows',
            'satisfactions', 'contact_us', 'application_comments', 'project_comments',
            'winners', 'guideline_files', 'guidelines',
            'mentor_sessions', 'mentor_availabilities', 'mentor_participant', 'mentor_team', 'mentor_programs', 'mentors',
            'disclaimer_acceptances', 'form_evaluation_scores', 'project_evaluations', 'judge_projects',
            'committee_judges', 'committees', 'program_judge',
            'judges', 'projects', 'team_members', 'teams',
            'program_applications', 'participants',
            'evaluation_stage_configs', 'team_form_configs', 'project_form_configs', 'registration_form_configs',
            'form_fields', 'form_sections', 'forms',
            'stages', 'program_tabs', 'sub_tracks', 'tracks',
            'branding_programs', 'user_programs', 'programs',
            'landing_pages', 'services', 'pages', 'socials', 'branding_settings',
            'cities', 'countries', 'nationalities',
            'events',
        ];
        foreach ($tablesToTruncate as $table) {
            DB::table($table)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ─── 1. NATIONALITIES & COUNTRIES & CITIES ───
        $this->command->info('Seeding nationalities, countries, cities...');

        $nationalityId = DB::table('nationalities')->insertGetId([
            'name' => json_encode(['en' => 'Saudi', 'ar' => 'سعودي']),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $nationality2Id = DB::table('nationalities')->insertGetId([
            'name' => json_encode(['en' => 'Emirati', 'ar' => 'إماراتي']),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $nationality3Id = DB::table('nationalities')->insertGetId([
            'name' => json_encode(['en' => 'Jordanian', 'ar' => 'أردني']),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $countryId = DB::table('countries')->insertGetId([
            'name' => json_encode(['en' => 'Saudi Arabia', 'ar' => 'المملكة العربية السعودية']),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $country2Id = DB::table('countries')->insertGetId([
            'name' => json_encode(['en' => 'United Arab Emirates', 'ar' => 'الإمارات العربية المتحدة']),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $cityId = DB::table('cities')->insertGetId([
            'country_id' => $countryId,
            'name' => json_encode(['en' => 'Riyadh', 'ar' => 'الرياض']),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $city2Id = DB::table('cities')->insertGetId([
            'country_id' => $countryId,
            'name' => json_encode(['en' => 'Jeddah', 'ar' => 'جدة']),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $city3Id = DB::table('cities')->insertGetId([
            'country_id' => $country2Id,
            'name' => json_encode(['en' => 'Dubai', 'ar' => 'دبي']),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ─── 2. BRANDING SETTINGS ───
        $this->command->info('Seeding branding settings...');

        $brandingId = DB::table('branding_settings')->insertGetId([
            'logo' => null,
            'white_logo' => null,
            'favicon' => null,
            'primary_color' => '#1E40AF',
            'secondary_color' => '#F59E0B',
            'font' => 'Inter',
            'email_bg_color' => '#FFFFFF',
            'email_text_color' => '#111827',
            'email_link_color' => '#1E40AF',
            'email_border_color' => '#E5E7EB',
            'email_footer' => 'Innovation Platform © 2025',
            'email_logo' => null,
            'email_footer_footer' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ─── 3. SOCIALS ───
        $this->command->info('Seeding social links...');

        DB::table('socials')->insert([
            ['name' => 'Twitter', 'url' => 'https://twitter.com/innovationplatform', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'LinkedIn', 'url' => 'https://linkedin.com/company/innovationplatform', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Instagram', 'url' => 'https://instagram.com/innovationplatform', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ─── 4. PAGES ───
        $this->command->info('Seeding pages...');

        DB::table('pages')->insert([
            [
                'slug' => 'about-us',
                'title' => json_encode(['en' => 'About Us', 'ar' => 'من نحن']),
                'content' => json_encode(['en' => '<p>Innovation Platform is a comprehensive hackathon and innovation management system.</p>', 'ar' => '<p>منصة الابتكار هي نظام شامل لإدارة الهاكاثونات والابتكارات.</p>']),
                'is_published' => true,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'slug' => 'privacy-policy',
                'title' => json_encode(['en' => 'Privacy Policy', 'ar' => 'سياسة الخصوصية']),
                'content' => json_encode(['en' => '<p>Your privacy is important to us. We collect only necessary data to provide our services.</p>', 'ar' => '<p>خصوصيتك مهمة لنا. نحن نجمع فقط البيانات اللازمة لتقديم خدماتنا.</p>']),
                'is_published' => true,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'slug' => 'terms-and-conditions',
                'title' => json_encode(['en' => 'Terms & Conditions', 'ar' => 'الشروط والأحكام']),
                'content' => json_encode(['en' => '<p>By using this platform, you agree to these terms.</p>', 'ar' => '<p>باستخدامك لهذه المنصة، فإنك توافق على هذه الشروط.</p>']),
                'is_published' => true,
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        // ─── 5. SERVICES ───
        $this->command->info('Seeding services...');

        DB::table('services')->insert([
            [
                'title' => json_encode(['en' => 'Hackathon Management', 'ar' => 'إدارة الهاكاثون']),
                'content' => json_encode(['en' => ['description' => 'End-to-end hackathon management'], 'ar' => ['description' => 'إدارة الهاكاثون من البداية إلى النهاية']]),
                'metadata' => json_encode(['en' => ['icon' => 'rocket'], 'ar' => ['icon' => 'rocket']]),
                'relatedServices' => json_encode([]),
                'is_published' => true, 'order' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'title' => json_encode(['en' => 'Innovation Sandbox', 'ar' => 'صندوق الابتكار']),
                'content' => json_encode(['en' => ['description' => 'Test and validate innovative ideas'], 'ar' => ['description' => 'اختبار والتحقق من الأفكار المبتكرة']]),
                'metadata' => json_encode(['en' => ['icon' => 'lightbulb'], 'ar' => ['icon' => 'lightbulb']]),
                'relatedServices' => json_encode([]),
                'is_published' => true, 'order' => 2,
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        // ─── 6. LANDING PAGE ───
        $this->command->info('Seeding landing page...');

        DB::table('landing_pages')->insertGetId([
            'title' => 'Innovation Platform',
            'content' => json_encode([
                'hero' => ['en' => ['title' => 'Innovation Starts Here', 'subtitle' => 'Join the future of innovation'], 'ar' => ['title' => 'الابتكار يبدأ هنا', 'subtitle' => 'انضم إلى مستقبل الابتكار']],
            ]),
            'government_verification_banner_enabled' => false,
            'dga_registration_number' => null,
            'dga_certificate_url' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ─── 7. COMPETITION (Hackathon) ───
        $this->command->info('Seeding program...');

        $programId = DB::table('programs')->insertGetId([
            'title' => json_encode(['en' => 'Saudi Innovation Hackathon 2025', 'ar' => 'هاكاثون الابتكار السعودي 2025']),
            'about' => json_encode(['en' => 'A national hackathon bringing together the brightest minds to solve real-world challenges using technology and innovation. Teams will compete to build prototypes in 48 hours.', 'ar' => 'هاكاثون وطني يجمع أذكى العقول لحل تحديات العالم الحقيقي باستخدام التكنولوجيا والابتكار. ستتنافس الفرق لبناء نماذج أولية في 48 ساعة.']),
            'terms_and_conditions' => json_encode(['en' => 'Participants must be at least 18 years old and agree to the code of conduct.', 'ar' => 'يجب أن يكون المشاركون بعمر 18 عامًا على الأقل وأن يوافقوا على قواعد السلوك.']),
            'type' => 'Hackathon',
            'registration_closed_date' => Carbon::now()->addMonths(2)->format('Y-m-d'),
            'banner' => '',
            'is_published' => true,
            'is_archived' => false,
            'archived_at' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Assign admin user to program
        $adminUser = DB::table('users')->where('email', 'admin@innovation-platform.com')->first();
        if ($adminUser) {
            DB::table('user_programs')->insert([
                'user_id' => $adminUser->id,
                'program_id' => $programId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // ─── 8. BRANDING COMPETITION ───
        DB::table('branding_programs')->insert([
            'program_id' => $programId,
            'logo' => null, 'white_logo' => null, 'favicon' => null,
            'primary_color' => '#1E40AF', 'secondary_color' => '#F59E0B', 'font' => 'Inter',
            'is_published' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ─── 9. TRACKS & SUB-TRACKS ───
        $this->command->info('Seeding tracks & sub-tracks...');

        $track1Id = DB::table('tracks')->insertGetId([
            'program_id' => $programId,
            'name' => json_encode(['en' => 'FinTech', 'ar' => 'التقنية المالية']),
            'slug' => 'fintech', 'order' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $track2Id = DB::table('tracks')->insertGetId([
            'program_id' => $programId,
            'name' => json_encode(['en' => 'HealthTech', 'ar' => 'التقنية الصحية']),
            'slug' => 'healthtech', 'order' => 2,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $track3Id = DB::table('tracks')->insertGetId([
            'program_id' => $programId,
            'name' => json_encode(['en' => 'EdTech', 'ar' => 'التقنية التعليمية']),
            'slug' => 'edtech', 'order' => 3,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $subTrack1Id = DB::table('sub_tracks')->insertGetId([
            'track_id' => $track1Id,
            'name' => json_encode(['en' => 'Digital Payments', 'ar' => 'المدفوعات الرقمية']),
            'slug' => 'digital_payments', 'order' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $subTrack2Id = DB::table('sub_tracks')->insertGetId([
            'track_id' => $track2Id,
            'name' => json_encode(['en' => 'Telemedicine', 'ar' => 'الطب عن بعد']),
            'slug' => 'telemedicine', 'order' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ─── 10. COMPETITION TABS ───
        DB::table('program_tabs')->insert([
            ['program_id' => $programId, 'tab' => 'my-team', 'is_visible' => true, 'created_at' => now(), 'updated_at' => now()],
            ['program_id' => $programId, 'tab' => 'projects', 'is_visible' => true, 'created_at' => now(), 'updated_at' => now()],
            ['program_id' => $programId, 'tab' => 'leaderboard', 'is_visible' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ─── 11. FORMS ───
        $this->command->info('Seeding forms...');

        $regFormId = DB::table('forms')->insertGetId([
            'program_id' => $programId,
            'type' => 'registration',
            'name' => json_encode(['en' => 'Hackathon Registration Form', 'ar' => 'استمارة التسجيل للهاكاثون']),
            'description' => json_encode(['en' => 'Register to participate in the hackathon', 'ar' => 'سجل للمشاركة في الهاكاثون']),
            'status' => 'active', 'is_published' => true, 'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $projFormId = DB::table('forms')->insertGetId([
            'program_id' => $programId,
            'type' => 'project',
            'name' => json_encode(['en' => 'Project Submission Form', 'ar' => 'استمارة تقديم المشروع']),
            'description' => json_encode(['en' => 'Submit your project details', 'ar' => 'أرسل تفاصيل مشروعك']),
            'status' => 'active', 'is_published' => true, 'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $evalFormId = DB::table('forms')->insertGetId([
            'program_id' => $programId,
            'type' => 'evaluation',
            'name' => json_encode(['en' => 'Judge Evaluation Form', 'ar' => 'استمارة تقييم الحكم']),
            'description' => json_encode(['en' => 'Evaluate the submitted projects', 'ar' => 'قيم المشاريع المقدمة']),
            'evaluation_config' => json_encode([
                'evaluation_agreement_text' => ['en' => 'I agree to evaluate fairly and impartially.', 'ar' => 'أوافق على التقييم بعدل وحيادية.'],
                'evaluation_criteria' => [
                    ['label' => ['en' => 'Innovation', 'ar' => 'الابتكار'], 'slug' => 'innovation', 'weight' => 25, 'subcriteria' => []],
                    ['label' => ['en' => 'Technical Implementation', 'ar' => 'التنفيذ التقني'], 'slug' => 'technical_implementation', 'weight' => 30, 'subcriteria' => []],
                    ['label' => ['en' => 'Business Viability', 'ar' => 'الجدوى التجارية'], 'slug' => 'business_viability', 'weight' => 25, 'subcriteria' => []],
                    ['label' => ['en' => 'Presentation', 'ar' => 'العرض'], 'slug' => 'presentation', 'weight' => 20, 'subcriteria' => []],
                ],
            ]),
            'status' => 'active', 'is_published' => true, 'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ─── 12. FORM SECTIONS & FIELDS ───
        $this->command->info('Seeding form fields...');

        // Registration form sections
        $regSectionId = DB::table('form_sections')->insertGetId([
            'form_id' => $regFormId,
            'title' => json_encode(['en' => 'Personal Information', 'ar' => 'المعلومات الشخصية']),
            'description' => json_encode(['en' => 'Please fill in your details', 'ar' => 'يرجى ملء بياناتك']),
            'sort' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Registration form fields
        DB::table('form_fields')->insert([
            ['form_id' => $regFormId, 'section_id' => $regSectionId, 'label' => json_encode(['en' => 'Participant Name', 'ar' => 'اسم المشارك']), 'type' => 'text', 'required' => true, 'slug' => 'participant_name', 'sort' => 1, 'options' => null, 'placeholder' => null, 'hint' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null, 'created_at' => now(), 'updated_at' => now()],
            ['form_id' => $regFormId, 'section_id' => $regSectionId, 'label' => json_encode(['en' => 'Participant Email', 'ar' => 'البريد الإلكتروني']), 'type' => 'email', 'required' => true, 'slug' => 'participant_email', 'sort' => 2, 'options' => null, 'placeholder' => null, 'hint' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null, 'created_at' => now(), 'updated_at' => now()],
            ['form_id' => $regFormId, 'section_id' => $regSectionId, 'label' => json_encode(['en' => 'Why do you want to participate?', 'ar' => 'لماذا تريد المشاركة؟']), 'type' => 'textarea', 'required' => true, 'slug' => 'why_participate', 'sort' => 3, 'options' => null, 'placeholder' => json_encode(['en' => 'Tell us about your motivation', 'ar' => 'أخبرنا عن دافعك']), 'hint' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null, 'created_at' => now(), 'updated_at' => now()],
            ['form_id' => $regFormId, 'section_id' => $regSectionId, 'label' => json_encode(['en' => 'Experience Level', 'ar' => 'مستوى الخبرة']), 'type' => 'dropdown', 'required' => true, 'slug' => 'experience_level', 'sort' => 4, 'options' => json_encode([['en' => 'Beginner', 'ar' => 'مبتدئ'], ['en' => 'Intermediate', 'ar' => 'متوسط'], ['en' => 'Advanced', 'ar' => 'متقدم']]), 'placeholder' => null, 'hint' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Project form fields
        $projSectionId = DB::table('form_sections')->insertGetId([
            'form_id' => $projFormId,
            'title' => json_encode(['en' => 'Project Details', 'ar' => 'تفاصيل المشروع']),
            'description' => json_encode(['en' => 'Describe your project', 'ar' => 'صف مشروعك']),
            'sort' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('form_fields')->insert([
            ['form_id' => $projFormId, 'section_id' => $projSectionId, 'label' => json_encode(['en' => 'Project Name', 'ar' => 'اسم المشروع']), 'type' => 'text', 'required' => true, 'slug' => 'project_name', 'sort' => 1, 'options' => null, 'placeholder' => null, 'hint' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null, 'created_at' => now(), 'updated_at' => now()],
            ['form_id' => $projFormId, 'section_id' => $projSectionId, 'label' => json_encode(['en' => 'Project Description', 'ar' => 'وصف المشروع']), 'type' => 'textarea', 'required' => true, 'slug' => 'project_description', 'sort' => 2, 'options' => null, 'placeholder' => null, 'hint' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null, 'created_at' => now(), 'updated_at' => now()],
            ['form_id' => $projFormId, 'section_id' => $projSectionId, 'label' => json_encode(['en' => 'Demo URL', 'ar' => 'رابط العرض']), 'type' => 'url', 'required' => false, 'slug' => 'demo_url', 'sort' => 3, 'options' => null, 'placeholder' => null, 'hint' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Eval form fields
        $evalSectionId = DB::table('form_sections')->insertGetId([
            'form_id' => $evalFormId,
            'title' => json_encode(['en' => 'Evaluation Criteria', 'ar' => 'معايير التقييم']),
            'description' => json_encode(['en' => 'Rate the project', 'ar' => 'قيم المشروع']),
            'sort' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('form_fields')->insert([
            ['form_id' => $evalFormId, 'section_id' => $evalSectionId, 'label' => json_encode(['en' => 'Innovation Score', 'ar' => 'درجة الابتكار']), 'type' => 'rating', 'required' => true, 'slug' => 'innovation_score', 'sort' => 1, 'options' => json_encode([['en' => '1', 'ar' => '1'], ['en' => '2', 'ar' => '2'], ['en' => '3', 'ar' => '3'], ['en' => '4', 'ar' => '4'], ['en' => '5', 'ar' => '5']]), 'placeholder' => null, 'hint' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null, 'created_at' => now(), 'updated_at' => now()],
            ['form_id' => $evalFormId, 'section_id' => $evalSectionId, 'label' => json_encode(['en' => 'Technical Score', 'ar' => 'الدرجة التقنية']), 'type' => 'rating', 'required' => true, 'slug' => 'technical_score', 'sort' => 2, 'options' => json_encode([['en' => '1', 'ar' => '1'], ['en' => '2', 'ar' => '2'], ['en' => '3', 'ar' => '3'], ['en' => '4', 'ar' => '4'], ['en' => '5', 'ar' => '5']]), 'placeholder' => null, 'hint' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null, 'created_at' => now(), 'updated_at' => now()],
            ['form_id' => $evalFormId, 'section_id' => $evalSectionId, 'label' => json_encode(['en' => 'Judge Comments', 'ar' => 'تعليقات الحكم']), 'type' => 'textarea', 'required' => false, 'slug' => 'judge_comments', 'sort' => 3, 'options' => null, 'placeholder' => null, 'hint' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ─── 13. FORM CONFIG TABLES ───
        $this->command->info('Seeding form configs...');

        $regFormConfigId = DB::table('registration_form_configs')->insertGetId([
            'program_id' => $programId,
            'registration_type' => 'both',
            'min_age' => 18, 'max_age' => 45,
            'min_team_members' => 2, 'max_team_members' => 5,
            'team_fields_enabled' => 'team_name,team_logo,team_serial',
            'label_register_as' => json_encode(['en' => 'Register as', 'ar' => 'التسجيل ك']),
            'option_register_individual' => json_encode(['en' => 'Individual', 'ar' => 'فردي']),
            'option_register_team' => json_encode(['en' => 'Team', 'ar' => 'فريق']),
            'label_team_name' => json_encode(['en' => 'Team Name', 'ar' => 'اسم الفريق']),
            'label_team_logo' => json_encode(['en' => 'Team Logo', 'ar' => 'شعار الفريق']),
            'label_team_serial' => json_encode(['en' => 'Team Code', 'ar' => 'رمز الفريق']),
            'help_team_serial' => json_encode(['en' => 'Share this code with teammates', 'ar' => 'شارك هذا الرمز مع زملائك']),
            'is_active' => true, 'is_archived' => false, 'scoring_enabled' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('project_form_configs')->insert([
            'form_id' => $projFormId, 'allow_track_change' => false, 'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('team_form_configs')->insert([
            'program_id' => $programId, 'is_active' => true,
            'min_team_members' => 2, 'max_team_members' => 5,
            'allow_track_selection' => true, 'require_same_track' => false, 'auto_publish_teams' => true,
            'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('evaluation_stage_configs')->insert([
            'program_id' => $programId,
            'number_of_stages' => 1,
            'stages' => json_encode([
                ['stage_number' => 1, 'evaluation_form_id' => $evalFormId, 'apply_to_all_tracks' => true, 'track_ids' => [], 'submission_requirement' => 'new'],
            ]),
            'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ─── 14. STAGES ───
        $this->command->info('Seeding stages...');

        $regStageId = DB::table('stages')->insertGetId([
            'program_id' => $programId,
            'form_id' => $regFormId,
            'form_ids' => json_encode([$regFormId]),
            'slug' => 'registration',
            'title' => json_encode(['en' => 'Registration', 'ar' => 'التسجيل']),
            'description' => json_encode(['en' => 'Register for the hackathon', 'ar' => 'سجل في الهاكاثون']),
            'starts_at' => Carbon::now()->subDays(30), 'ends_at' => Carbon::now()->addMonths(1),
            'is_visible' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $teamStageId = DB::table('stages')->insertGetId([
            'program_id' => $programId,
            'form_id' => null,
            'form_ids' => json_encode([]),
            'slug' => 'team-formation',
            'title' => json_encode(['en' => 'Team Formation', 'ar' => 'تشكيل الفرق']),
            'description' => json_encode(['en' => 'Form your teams', 'ar' => 'شكّل فريقك']),
            'starts_at' => Carbon::now()->addMonths(1), 'ends_at' => Carbon::now()->addMonths(2),
            'is_visible' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $projStageId = DB::table('stages')->insertGetId([
            'program_id' => $programId,
            'form_id' => $projFormId,
            'form_ids' => json_encode([$projFormId]),
            'slug' => 'project-submission',
            'title' => json_encode(['en' => 'Project Submission', 'ar' => 'تقديم المشروع']),
            'description' => json_encode(['en' => 'Submit your project', 'ar' => 'قدم مشروعك']),
            'starts_at' => Carbon::now()->addMonths(2), 'ends_at' => Carbon::now()->addMonths(3),
            'is_visible' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $evalStageId = DB::table('stages')->insertGetId([
            'program_id' => $programId,
            'form_id' => $evalFormId,
            'form_ids' => json_encode([$evalFormId]),
            'slug' => 'evaluation',
            'title' => json_encode(['en' => 'Evaluation', 'ar' => 'التقييم']),
            'description' => json_encode(['en' => 'Projects will be evaluated by judges', 'ar' => 'سيتم تقييم المشاريع من قبل الحكام']),
            'starts_at' => Carbon::now()->addMonths(3), 'ends_at' => Carbon::now()->addMonths(4),
            'is_visible' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ─── 15. PARTICIPANTS ───
        $this->command->info('Seeding participants...');

        $participant1Id = DB::table('participants')->insertGetId([
            'serial_number' => '100001', 'name' => 'Ahmed Saad', 'email' => 'ahmed@test.com',
            'phone' => '0501234567', 'gender' => 'male', 'date_of_birth' => '1995-06-15',
            'nationality_id' => $nationalityId, 'country_id' => $countryId, 'residence_city_id' => $cityId,
            'password' => Hash::make('password'), 'educational_background' => 'bachelor',
            'current_role' => 'private_sector_employee', 'place_of_work_study' => 'NEOM Tech',
            'years_of_experience' => 'five_to_ten', 'experience_or_skills' => 'Full-stack development, AI/ML, Cloud Architecture',
            'key_achievements' => 'Won 3 hackathons, Published 2 research papers',
            'activation_code' => Str::random(6), 'is_active' => true, 'is_archived' => false,
            'email_verified_at' => now(), 'last_login_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $participant2Id = DB::table('participants')->insertGetId([
            'serial_number' => '100002', 'name' => 'Fatima Al-Rashid', 'email' => 'fatima@test.com',
            'phone' => '0559876543', 'gender' => 'female', 'date_of_birth' => '1998-03-22',
            'nationality_id' => $nationalityId, 'country_id' => $countryId, 'residence_city_id' => $city2Id,
            'password' => Hash::make('password'), 'educational_background' => 'master',
            'current_role' => 'private_sector_employee', 'place_of_work_study' => 'STC Solutions',
            'years_of_experience' => 'three_to_five', 'experience_or_skills' => 'Python, TensorFlow, Data Analytics',
            'key_achievements' => 'Led AI team at STC, Top 10 in Kaggle program',
            'activation_code' => Str::random(6), 'is_active' => true, 'is_archived' => false,
            'email_verified_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $participant3Id = DB::table('participants')->insertGetId([
            'serial_number' => '100003', 'name' => 'Omar Hassan', 'email' => 'omar@test.com',
            'phone' => '0541112233', 'gender' => 'male', 'date_of_birth' => '1997-11-08',
            'nationality_id' => $nationality3Id, 'country_id' => $countryId, 'residence_city_id' => $cityId,
            'password' => Hash::make('password'), 'educational_background' => 'bachelor',
            'current_role' => 'freelancer', 'place_of_work_study' => 'Freelance',
            'years_of_experience' => 'three_to_five', 'experience_or_skills' => 'Figma, UI/UX Design, User Research',
            'key_achievements' => 'Designed apps with 1M+ downloads',
            'activation_code' => Str::random(6), 'is_active' => true, 'is_archived' => false,
            'email_verified_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $participant4Id = DB::table('participants')->insertGetId([
            'serial_number' => '100004', 'name' => 'Noura Al-Fahad', 'email' => 'noura@test.com',
            'phone' => '0567778899', 'gender' => 'female', 'date_of_birth' => '1996-01-30',
            'nationality_id' => $nationality2Id, 'country_id' => $country2Id, 'residence_city_id' => $city3Id,
            'password' => Hash::make('password'), 'educational_background' => 'master',
            'current_role' => 'private_sector_employee', 'place_of_work_study' => 'Aramco Digital',
            'years_of_experience' => 'five_to_ten', 'experience_or_skills' => 'Product Strategy, Agile, Business Analysis',
            'key_achievements' => 'Launched 5 digital products, PMP certified',
            'activation_code' => Str::random(6), 'is_active' => true, 'is_archived' => false,
            'email_verified_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ─── 16. COMPETITION APPLICATIONS ───
        $this->command->info('Seeding applications...');

        $app1Id = DB::table('program_applications')->insertGetId([
            'program_id' => $programId, 'form_id' => $regFormId,
            'participant_id' => $participant1Id, 'status' => 'approved',
            'registered_as' => 'team', 'has_team' => true, 'has_idea' => true,
            'participation_interest' => 'Building innovative fintech solutions',
            'team_name' => 'InnoVault', 'team_serial' => 'IV-2025',
            'form_submissions' => json_encode(['participant_name' => 'Ahmed Saad', 'participant_email' => 'ahmed@test.com', 'why_participate' => 'I want to build innovative fintech solutions for the unbanked population.', 'experience_level' => '3']),
            'type' => 'submission', 'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $app2Id = DB::table('program_applications')->insertGetId([
            'program_id' => $programId, 'form_id' => $regFormId,
            'participant_id' => $participant2Id, 'status' => 'approved',
            'registered_as' => 'team', 'has_team' => true, 'has_idea' => true,
            'participation_interest' => 'Using AI to improve healthcare outcomes',
            'form_submissions' => json_encode(['participant_name' => 'Fatima Al-Rashid', 'participant_email' => 'fatima@test.com', 'why_participate' => 'Passionate about using AI to improve healthcare outcomes.', 'experience_level' => '2']),
            'type' => 'submission', 'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $app3Id = DB::table('program_applications')->insertGetId([
            'program_id' => $programId, 'form_id' => $regFormId,
            'participant_id' => $participant3Id, 'status' => 'approved',
            'registered_as' => 'team', 'has_team' => true, 'has_idea' => true,
            'participation_interest' => 'Designing accessible EdTech solutions',
            'form_submissions' => json_encode(['participant_name' => 'Omar Hassan', 'participant_email' => 'omar@test.com', 'why_participate' => 'Designing accessible tech solutions for education.', 'experience_level' => '2']),
            'type' => 'submission', 'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $app4Id = DB::table('program_applications')->insertGetId([
            'program_id' => $programId, 'form_id' => $regFormId,
            'participant_id' => $participant4Id, 'status' => 'approved',
            'registered_as' => 'individual', 'has_team' => false, 'has_idea' => true,
            'participation_interest' => 'Building next-gen digital products',
            'form_submissions' => json_encode(['participant_name' => 'Noura Al-Fahad', 'participant_email' => 'noura@test.com', 'why_participate' => 'Building the next generation of digital products.', 'experience_level' => '3']),
            'type' => 'submission', 'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ─── 17. TEAMS & TEAM MEMBERS ───
        $this->command->info('Seeding teams...');

        $team1Id = DB::table('teams')->insertGetId([
            'application_id' => $app1Id, 'name' => 'InnoVault', 'strength' => 3,
            'track_id' => $track1Id, 'sub_track_id' => $subTrack1Id,
            'idea_description' => 'A blockchain-based micro-lending platform for small businesses.',
            'previous_participation' => true, 'contact_email' => 'ahmed@test.com',
            'skills' => json_encode(['Full-stack Development', 'Blockchain', 'Finance']), 'is_published' => true, 'is_completed' => true,
            'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $team2Id = DB::table('teams')->insertGetId([
            'application_id' => $app2Id, 'name' => 'HealthPulse AI', 'strength' => 2,
            'track_id' => $track2Id, 'sub_track_id' => $subTrack2Id,
            'idea_description' => 'AI-powered diagnostic tool for remote patient monitoring.',
            'previous_participation' => false, 'contact_email' => 'fatima@test.com',
            'skills' => json_encode(['AI/ML', 'Healthcare', 'Data Science']), 'is_published' => true, 'is_completed' => true,
            'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Team members
        DB::table('team_members')->insert([
            ['team_id' => $team1Id, 'participant_id' => $participant1Id, 'is_leader' => true, 'created_at' => now(), 'updated_at' => now()],
            ['team_id' => $team1Id, 'participant_id' => $participant3Id, 'is_leader' => false, 'created_at' => now(), 'updated_at' => now()],
            ['team_id' => $team1Id, 'participant_id' => $participant4Id, 'is_leader' => false, 'created_at' => now(), 'updated_at' => now()],
            ['team_id' => $team2Id, 'participant_id' => $participant2Id, 'is_leader' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ─── 18. PROJECTS ───
        $this->command->info('Seeding projects...');

        $project1Id = DB::table('projects')->insertGetId([
            'program_id' => $programId, 'application_id' => $app1Id,
            'team_id' => $team1Id, 'form_id' => $projFormId,
            'status' => 'pending', 'evaluation_status' => false, 'total_score' => 0,
            'type' => 'submission',
            'form_submissions' => json_encode(['project_name' => 'MicroLend', 'project_description' => 'A decentralized micro-lending platform using blockchain to provide affordable financial services to underbanked SMEs in Saudi Arabia.', 'demo_url' => 'https://microlend-demo.example.com']),
            'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $project2Id = DB::table('projects')->insertGetId([
            'program_id' => $programId, 'application_id' => $app2Id,
            'team_id' => $team2Id, 'form_id' => $projFormId,
            'status' => 'qualified', 'evaluation_status' => true, 'total_score' => 82.50,
            'type' => 'submission',
            'form_submissions' => json_encode(['project_name' => 'DiagnoAI', 'project_description' => 'An AI-powered telemedicine platform that uses computer vision to assist doctors in remote diagnosis of skin conditions and respiratory issues.', 'demo_url' => 'https://diagnoai-demo.example.com']),
            'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ─── 19. JUDGES ───
        $this->command->info('Seeding judges...');

        $judge1Id = DB::table('judges')->insertGetId([
            'serial_number' => '200001',
            'name' => json_encode(['en' => 'Dr. Khalid Al-Otaibi', 'ar' => 'د. خالد العتيبي']),
            'email' => 'khalid.judge@test.com', 'phone_number' => '0501111111',
            'experience_field' => json_encode(['en' => 'Venture Capital & Tech Startups', 'ar' => 'رأس المال المغامر والشركات الناشئة']),
            'password' => Hash::make('password'),
            'registration_method' => 'admin-added',
            'email_verified_at' => now(), 'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $judge2Id = DB::table('judges')->insertGetId([
            'serial_number' => '200002',
            'name' => json_encode(['en' => 'Prof. Sara Mansour', 'ar' => 'أ. سارة منصور']),
            'email' => 'sara.judge@test.com', 'phone_number' => '0502222222',
            'experience_field' => json_encode(['en' => 'Artificial Intelligence & Machine Learning', 'ar' => 'الذكاء الاصطناعي وتعلم الآلة']),
            'password' => Hash::make('password'),
            'registration_method' => 'admin-added',
            'email_verified_at' => now(), 'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Assign judges to program
        DB::table('program_judge')->insert([
            ['program_id' => $programId, 'judge_id' => $judge1Id, 'created_at' => now(), 'updated_at' => now()],
            ['program_id' => $programId, 'judge_id' => $judge2Id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ─── 20. COMMITTEES & JUDGE ASSIGNMENTS ───
        $this->command->info('Seeding committees...');

        $committeeId = DB::table('committees')->insertGetId([
            'program_id' => $programId, 'title' => 'Main Judging Panel',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('committee_judges')->insert([
            ['committee_id' => $committeeId, 'judge_id' => $judge1Id, 'created_at' => now(), 'updated_at' => now()],
            ['committee_id' => $committeeId, 'judge_id' => $judge2Id, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Judge-project assignments
        $jp1Id = DB::table('judge_projects')->insertGetId([
            'judge_id' => $judge1Id, 'project_id' => $project2Id, 'evaluation_score' => 85.00,
            'disclaimer_accepted' => true, 'disclaimer_accepted_at' => now(), 'final_comment' => 'Excellent use of AI for healthcare.',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $jp2Id = DB::table('judge_projects')->insertGetId([
            'judge_id' => $judge2Id, 'project_id' => $project2Id, 'evaluation_score' => 80.00,
            'disclaimer_accepted' => true, 'disclaimer_accepted_at' => now(), 'final_comment' => 'Strong technical implementation.',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ─── 21. PROJECT EVALUATIONS & SCORES ───
        $this->command->info('Seeding evaluations...');

        DB::table('project_evaluations')->insert([
            ['judge_project_id' => $jp1Id, 'form_id' => $evalFormId, 'stage_id' => $evalStageId, 'question' => 'Innovation', 'answer' => 90, 'comment' => 'Very innovative approach.', 'weight' => 25, 'is_archived' => false, 'created_at' => now(), 'updated_at' => now()],
            ['judge_project_id' => $jp1Id, 'form_id' => $evalFormId, 'stage_id' => $evalStageId, 'question' => 'Technical Implementation', 'answer' => 80, 'comment' => 'Solid architecture.', 'weight' => 30, 'is_archived' => false, 'created_at' => now(), 'updated_at' => now()],
            ['judge_project_id' => $jp2Id, 'form_id' => $evalFormId, 'stage_id' => $evalStageId, 'question' => 'Innovation', 'answer' => 85, 'comment' => 'Good innovation.', 'weight' => 25, 'is_archived' => false, 'created_at' => now(), 'updated_at' => now()],
            ['judge_project_id' => $jp2Id, 'form_id' => $evalFormId, 'stage_id' => $evalStageId, 'question' => 'Technical Implementation', 'answer' => 75, 'comment' => 'Needs scaling improvements.', 'weight' => 30, 'is_archived' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('form_evaluation_scores')->insert([
            ['judge_project_id' => $jp1Id, 'form_id' => $evalFormId, 'stage_id' => $evalStageId, 'evaluation_score' => 85.00, 'is_archived' => false, 'exclude_from_calculation' => false, 'created_at' => now(), 'updated_at' => now()],
            ['judge_project_id' => $jp2Id, 'form_id' => $evalFormId, 'stage_id' => $evalStageId, 'evaluation_score' => 80.00, 'is_archived' => false, 'exclude_from_calculation' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ─── 22. DISCLAIMER ACCEPTANCES ───
        DB::table('disclaimer_acceptances')->insert([
            ['judge_id' => $judge1Id, 'form_id' => $evalFormId, 'stage_id' => $evalStageId, 'accepted' => true, 'accepted_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['judge_id' => $judge2Id, 'form_id' => $evalFormId, 'stage_id' => $evalStageId, 'accepted' => true, 'accepted_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ─── 23. MENTORS ───
        $this->command->info('Seeding mentors...');

        $mentor1Id = DB::table('mentors')->insertGetId([
            'program_id' => $programId, 'track_id' => $track1Id,
            'name' => json_encode(['en' => 'Eng. Faisal Al-Mutairi', 'ar' => 'م. فيصل المطيري']),
            'experience' => json_encode(['en' => '15 years in FinTech and banking technology', 'ar' => '15 سنة في التقنية المالية والمصرفية']),
            'brief' => json_encode(['en' => 'Former CTO of a leading Saudi digital bank.', 'ar' => 'مدير تقنية سابق لبنك رقمي رائد.']),
            'profession' => json_encode(['en' => 'FinTech Advisor', 'ar' => 'مستشار تقنية مالية']),
            'email' => 'faisal.mentor@test.com', 'phone' => '0503333333',
            'password' => Hash::make('password'), 'image' => '',
            'linkedin' => 'https://linkedin.com/in/faisal-mutairi',
            'is_visible' => true, 'status' => 'approved', 'approved_at' => now(),
            'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $mentor2Id = DB::table('mentors')->insertGetId([
            'program_id' => $programId, 'track_id' => $track2Id,
            'name' => json_encode(['en' => 'Dr. Lama Al-Sheikh', 'ar' => 'د. لمى الشيخ']),
            'experience' => json_encode(['en' => '10 years in healthcare AI and digital health', 'ar' => '10 سنوات في الذكاء الاصطناعي الصحي']),
            'brief' => json_encode(['en' => 'AI researcher specializing in medical imaging.', 'ar' => 'باحثة في الذكاء الاصطناعي متخصصة في التصوير الطبي.']),
            'profession' => json_encode(['en' => 'AI Researcher', 'ar' => 'باحثة ذكاء اصطناعي']),
            'email' => 'lama.mentor@test.com', 'phone' => '0504444444',
            'password' => Hash::make('password'), 'image' => '',
            'linkedin' => 'https://linkedin.com/in/lama-alsheikh',
            'is_visible' => true, 'status' => 'approved', 'approved_at' => now(),
            'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Mentor-program pivot
        DB::table('mentor_programs')->insert([
            ['mentor_id' => $mentor1Id, 'program_id' => $programId, 'created_at' => now(), 'updated_at' => now()],
            ['mentor_id' => $mentor2Id, 'program_id' => $programId, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Mentor-team assignments
        DB::table('mentor_team')->insert([
            ['mentor_id' => $mentor1Id, 'team_id' => $team1Id, 'assigned_by' => $adminUser?->id, 'assigned_at' => now(), 'notes' => 'FinTech track mentor', 'created_at' => now(), 'updated_at' => now()],
            ['mentor_id' => $mentor2Id, 'team_id' => $team2Id, 'assigned_by' => $adminUser?->id, 'assigned_at' => now(), 'notes' => 'HealthTech track mentor', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Mentor-participant assignment (individual)
        DB::table('mentor_participant')->insert([
            ['mentor_id' => $mentor1Id, 'participant_id' => $participant4Id, 'assigned_by' => $adminUser?->id, 'assigned_at' => now(), 'notes' => 'Individual participant mentoring', 'program_id' => $programId, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ─── 24. MENTOR AVAILABILITY & SESSIONS ───
        $this->command->info('Seeding mentor availability & sessions...');

        DB::table('mentor_availabilities')->insert([
            ['mentor_id' => $mentor1Id, 'date' => null, 'day_of_week' => 'sunday', 'start_time' => '09:00', 'end_time' => '12:00', 'is_recurring' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['mentor_id' => $mentor1Id, 'date' => null, 'day_of_week' => 'tuesday', 'start_time' => '14:00', 'end_time' => '17:00', 'is_recurring' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['mentor_id' => $mentor2Id, 'date' => null, 'day_of_week' => 'monday', 'start_time' => '10:00', 'end_time' => '13:00', 'is_recurring' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('mentor_sessions')->insert([
            ['mentor_id' => $mentor1Id, 'participant_id' => $participant1Id, 'program_id' => $programId, 'title' => 'FinTech Strategy Session', 'description' => 'Discuss blockchain implementation strategy for MicroLend.', 'scheduled_at' => Carbon::now()->addDays(7)->setHour(10), 'duration_minutes' => 60, 'status' => 'scheduled', 'video_tool' => 'zoom', 'created_at' => now(), 'updated_at' => now()],
            ['mentor_id' => $mentor2Id, 'participant_id' => $participant2Id, 'program_id' => $programId, 'title' => 'AI Model Review', 'description' => 'Review the diagnostic AI model architecture.', 'scheduled_at' => Carbon::now()->addDays(5)->setHour(11), 'duration_minutes' => 45, 'status' => 'confirmed', 'video_tool' => 'google_meet', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ─── 25. EVENTS ───
        $this->command->info('Seeding events...');

        DB::table('events')->insert([
            [
                'program_id' => $programId,
                'title' => json_encode(['en' => 'Opening Ceremony', 'ar' => 'حفل الافتتاح']),
                'brief' => json_encode(['en' => 'Welcome to the Saudi Innovation Hackathon 2025!', 'ar' => 'مرحباً بكم في هاكاثون الابتكار السعودي 2025!']),
                'badge' => 'upcoming', 'date' => Carbon::now()->addDays(30), 'time' => '09:00:00',
                'location' => 'onsite', 'event_link' => '',
                'speakers' => json_encode([['name' => ['en' => 'Dr. Ahmed Nasser', 'ar' => 'د. أحمد ناصر'], 'experience' => ['en' => 'Innovation Director', 'ar' => 'مدير الابتكار'], 'brief' => ['en' => 'Keynote speaker', 'ar' => 'المتحدث الرئيسي'], 'photo' => null]]),
                'is_visible' => true, 'is_archived' => false,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'program_id' => $programId,
                'title' => json_encode(['en' => 'Workshop: Building with AI', 'ar' => 'ورشة: البناء باستخدام الذكاء الاصطناعي']),
                'brief' => json_encode(['en' => 'Learn how to integrate AI into your hackathon projects.', 'ar' => 'تعلم كيفية دمج الذكاء الاصطناعي في مشاريعك.']),
                'badge' => 'upcoming', 'date' => Carbon::now()->addDays(31), 'time' => '14:00:00',
                'location' => 'virtual', 'event_link' => 'https://zoom.us/meeting/example',
                'speakers' => json_encode([]),
                'is_visible' => true, 'is_archived' => false,
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        // ─── 26. GUIDELINES ───
        $this->command->info('Seeding guidelines...');

        $guideline1Id = DB::table('guidelines')->insertGetId([
            'program_id' => $programId,
            'title' => json_encode(['en' => 'Submission Guidelines', 'ar' => 'إرشادات التقديم']),
            'is_visible' => true, 'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('guideline_files')->insert([
            'guideline_id' => $guideline1Id,
            'title' => json_encode(['en' => 'How to Submit Your Project', 'ar' => 'كيفية تقديم مشروعك']),
            'description' => json_encode(['en' => 'Step-by-step guide for project submission.', 'ar' => 'دليل خطوة بخطوة لتقديم المشروع.']),
            'attachment' => '', 'file_type' => 'document',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ─── 27. WINNERS ───
        DB::table('winners')->insert([
            'program_id' => $programId, 'track_id' => $track2Id,
            'rank' => 1,
            'name' => json_encode(['en' => 'HealthPulse AI', 'ar' => 'هيلث بالس AI']),
            'subtitle' => json_encode(['en' => 'AI-powered diagnostic platform', 'ar' => 'منصة تشخيص بالذكاء الاصطناعي']),
            'image' => null, 'is_visible' => true, 'notes' => 'Outstanding innovation in HealthTech.',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ─── 28. PROJECT COMMENTS ───
        DB::table('project_comments')->insert([
            ['project_id' => $project2Id, 'user_id' => $adminUser?->id, 'comment' => 'Great progress! Please add more details about the AI model accuracy.', 'attachments' => null, 'is_read' => true, 'author_id' => $adminUser?->id, 'author_type' => 'App\\Models\\User', 'created_at' => now(), 'updated_at' => now()],
            ['project_id' => $project2Id, 'user_id' => null, 'comment' => 'Thank you! We will update the documentation with accuracy metrics.', 'attachments' => null, 'is_read' => false, 'author_id' => $participant2Id, 'author_type' => 'App\\Models\\Participant', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ─── 29. APPLICATION COMMENTS ───
        DB::table('application_comments')->insert([
            'application_id' => $app1Id, 'user_id' => $adminUser?->id,
            'comment' => 'Application approved. Welcome to the hackathon!',
            'attachments' => null, 'is_read' => true,
            'author_id' => $adminUser?->id, 'author_type' => 'App\\Models\\User',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ─── 30. CONTACT US ───
        DB::table('contact_us')->insert([
            'title' => 'Question about registration',
            'message' => 'Can I change my team members after registration closes?',
            'status' => 'resolved', 'reply' => 'Yes, team changes are allowed up to 1 week before the hackathon starts.',
            'replied_at' => now(), 'replied_by' => $adminUser?->id,
            'model_id' => $participant1Id, 'model_type' => 'App\\Models\\Participant',
            'is_archived' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ─── 31. SATISFACTION ───
        DB::table('satisfactions')->insert([
            ['program_id' => $programId, 'participant_id' => $participant2Id, 'question' => 'How would you rate the hackathon organization?', 'answer' => '5', 'created_at' => now(), 'updated_at' => now()],
            ['program_id' => $programId, 'participant_id' => $participant2Id, 'question' => 'Would you recommend this hackathon to others?', 'answer' => 'Yes, absolutely!', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ─── 32. APPROVAL WORKFLOW ───
        $this->command->info('Seeding approval workflows...');

        $workflowId = DB::table('approval_workflows')->insertGetId([
            'action' => 'program_management', 'levels' => 2, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $superAdminRole = DB::table('roles')->where('name', 'super-admin')->first();
        $adminRole = DB::table('roles')->where('name', 'admin')->first();

        if ($superAdminRole && $adminRole) {
            DB::table('approval_levels')->insert([
                ['approval_workflow_id' => $workflowId, 'level_number' => 1, 'role_ids' => json_encode([$adminRole->id]), 'required_approvals' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['approval_workflow_id' => $workflowId, 'level_number' => 2, 'role_ids' => json_encode([$superAdminRole->id]), 'required_approvals' => 1, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // ─── 33. NOTIFICATION & EMAIL TEMPLATES ───
        $this->command->info('Seeding notification templates...');

        DB::table('notification_messages')->insert([
            ['key' => 'application_approved', 'subject' => json_encode(['en' => 'Application Approved', 'ar' => 'تم قبول الطلب']), 'body' => json_encode(['en' => 'Your application has been approved. Welcome aboard!', 'ar' => 'تم قبول طلبك. مرحباً بك!']), 'type' => 'notification', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'project_evaluated', 'subject' => json_encode(['en' => 'Project Evaluated', 'ar' => 'تم تقييم المشروع']), 'body' => json_encode(['en' => 'Your project has been evaluated by the judges.', 'ar' => 'تم تقييم مشروعك من قبل الحكام.']), 'type' => 'notification', 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('email_templates')->insert([
            ['key' => 'welcome_participant', 'subject' => json_encode(['en' => 'Welcome to the Hackathon', 'ar' => 'مرحباً بك في الهاكاثون']), 'body' => json_encode(['en' => 'Dear {name}, welcome to the Innovation Hackathon!', 'ar' => 'عزيزي {name}، مرحباً بك في هاكاثون الابتكار!']), 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'evaluation_complete', 'subject' => json_encode(['en' => 'Evaluation Complete', 'ar' => 'اكتمال التقييم']), 'body' => json_encode(['en' => 'Dear {name}, your project evaluation is now complete.', 'ar' => 'عزيزي {name}، اكتمل تقييم مشروعك.']), 'is_default' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ─── 34. NOTIFICATION MANAGEMENT (bulk notification) ───
        DB::table('notification_management')->insert([
            'title' => 'Hackathon Kickoff Reminder',
            'body' => 'Don\'t forget: the hackathon starts next week! Make sure your team is ready.',
            'user_type' => 'participant',
            'program_id' => $programId,
            'user_ids' => json_encode([$participant1Id, $participant2Id, $participant3Id, $participant4Id]),
            'recipient_count' => 4,
            'admin_id' => $adminUser?->id,
            'send_email' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ─── 35. FORM AI CONFIGS ───
        DB::table('form_ai_scoring_configs')->insert([
            'form_id' => $projFormId, 'ai_prompt' => 'Evaluate this project submission based on innovation, technical quality, and business potential.', 'total_weight' => 100,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('form_ai_enhancement_configs')->insert([
            'form_id' => $regFormId, 'ai_enhancement_enabled' => true,
            'ai_enhancement_fields' => json_encode([['slug' => 'why_participate', 'instructions' => 'Improve grammar and clarity', 'context' => 'Hackathon registration motivation']]),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ─── 36. FORM ASSESSMENT CRITERIA ───
        $criterion1Id = DB::table('form_assessment_criteria')->insertGetId([
            'form_id' => $evalFormId, 'name' => 'Innovation & Creativity', 'description' => 'How innovative and creative is the solution?',
            'instruction' => 'Rate from 1-5 based on novelty and creative approach.', 'weight' => 25, 'status' => 'active', 'sort_order' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $criterion2Id = DB::table('form_assessment_criteria')->insertGetId([
            'form_id' => $evalFormId, 'name' => 'Technical Quality', 'description' => 'Quality of the technical implementation.',
            'instruction' => 'Assess code quality, architecture, and scalability.', 'weight' => 30, 'status' => 'active', 'sort_order' => 2,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $criterion3Id = DB::table('form_assessment_criteria')->insertGetId([
            'form_id' => $evalFormId, 'name' => 'Business Impact', 'description' => 'Potential business and social impact.',
            'instruction' => 'Evaluate market potential and feasibility.', 'weight' => 25, 'status' => 'active', 'sort_order' => 3,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // ─── 37. ASSESSMENT CRITERIA (for registration scoring) ───
        DB::table('assessment_criteria')->insert([
            ['registration_form_config_id' => $regFormConfigId, 'description' => 'Motivation and clarity of purpose', 'max_score' => 50, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['registration_form_config_id' => $regFormConfigId, 'description' => 'Relevant experience and skills', 'max_score' => 50, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ─── DONE ───
        $this->command->info('');
        $this->command->info('=== Comprehensive Test Seeder Complete! ===');
        $this->command->info('');
        $this->command->info('LOGIN CREDENTIALS:');
        $this->command->info('─────────────────────────────────────');
        $this->command->info('Admin:       admin@innovation-platform.com / password');
        $this->command->info('Participant: ahmed@test.com / password');
        $this->command->info('Participant: fatima@test.com / password');
        $this->command->info('Participant: omar@test.com / password');
        $this->command->info('Participant: noura@test.com / password');
        $this->command->info('Judge:       khalid.judge@test.com / password');
        $this->command->info('Judge:       sara.judge@test.com / password');
        $this->command->info('Mentor:      faisal.mentor@test.com / password');
        $this->command->info('Mentor:      lama.mentor@test.com / password');
        $this->command->info('─────────────────────────────────────');
    }
}
