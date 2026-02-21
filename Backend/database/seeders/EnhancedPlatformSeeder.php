<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Enhanced Platform Seeder
 * 
 * Creates:
 * 1. Landing page with proper Builder block content (TGA-style)
 * 2. Two service pages linked to landing page
 * 3. Sandbox program (program)
 * 4. Registration form for Sandbox (from Arabic PDF - TGA Regulatory Sandbox Application)
 * 5. Five project forms (from Car Rental Report PDF sheets)
 * 6. Form steps and project steps
 * 7. Email and notification templates for all automated notifications
 * 8. Comprehensive permissions for all Filament resources
 */
class EnhancedPlatformSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting Enhanced Platform Seeder...');

        // ─── 1. LANDING PAGE ───────────────────────────────────────────
        $this->seedLandingPage();

        // ─── 2. SERVICES ──────────────────────────────────────────────
        $this->seedServices();

        // ─── 3. SANDBOX COMPETITION ───────────────────────────────────
        $programId = $this->seedSandboxProgram();

        // ─── 4. REGISTRATION FORM ─────────────────────────────────────
        $registrationFormId = $this->seedRegistrationForm($programId);

        // ─── 5. PROJECT FORMS ─────────────────────────────────────────
        $projectFormIds = $this->seedProjectForms($programId);

        // ─── 6. STAGES ───────────────────────────────────────────────
        $this->seedStages($programId, $registrationFormId, $projectFormIds);

        // ─── 7. FORM STEPS & PROJECT STEPS ────────────────────────────
        $this->seedFormSteps($registrationFormId);
        $this->seedProjectSteps($projectFormIds);

        // ─── 8. REGISTRATION FORM CONFIG ──────────────────────────────
        $this->seedRegistrationFormConfig($programId);

        // ─── 9. EMAIL TEMPLATES ───────────────────────────────────────
        $this->seedEmailTemplates();

        // ─── 10. NOTIFICATION MESSAGES ────────────────────────────────
        $this->seedNotificationMessages();

        // ─── 11. PERMISSIONS ──────────────────────────────────────────
        $this->seedPermissions();

        $this->command->info('Enhanced Platform Seeder completed successfully!');
    }

    // ═══════════════════════════════════════════════════════════════
    // 1. LANDING PAGE
    // ═══════════════════════════════════════════════════════════════
    private function seedLandingPage(): void
    {
        $this->command->info('  → Updating landing page...');

        $content = [
            [
                'type' => 'banner',
                'data' => [
                    'items' => [
                        [
                            'title' => ['en' => 'Transport General Authority', 'ar' => 'الهيئة العامة للنقل'],
                            'text' => ['en' => 'Innovation Platform – Empowering the future of transport through technology and collaboration', 'ar' => 'منصة الابتكار – تمكين مستقبل النقل من خلال التكنولوجيا والتعاون'],
                            'main_action' => [
                                'title' => ['en' => 'Explore Programs', 'ar' => 'استكشف البرامج'],
                                'url' => ['en' => '/en/programs', 'ar' => '/ar/programs'],
                            ],
                            'image' => ['en' => '', 'ar' => ''],
                        ],
                        [
                            'title' => ['en' => 'Regulatory Sandbox', 'ar' => 'البيئة التنظيمية التجريبية'],
                            'text' => ['en' => 'A controlled environment that enables innovators to test new transport technologies and business models under regulatory supervision.', 'ar' => 'بيئة خاضعة للرقابة تمكن المبتكرين من اختبار تقنيات النقل الجديدة ونماذج الأعمال تحت إشراف تنظيمي.'],
                            'main_action' => [
                                'title' => ['en' => 'Apply Now', 'ar' => 'قدم الآن'],
                                'url' => ['en' => '/en/programs', 'ar' => '/ar/programs'],
                            ],
                            'image' => ['en' => '', 'ar' => ''],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'about',
                'data' => [
                    'title' => ['en' => 'About the Innovation Platform', 'ar' => 'عن منصة الابتكار'],
                    'text' => ['en' => 'The Transport General Authority Innovation Platform is a comprehensive digital ecosystem designed to foster innovation in the transport sector. We connect entrepreneurs, startups, and established companies with regulatory support and resources to develop cutting-edge transport solutions for the Kingdom of Saudi Arabia.', 'ar' => 'منصة الابتكار التابعة للهيئة العامة للنقل هي منظومة رقمية شاملة مصممة لتعزيز الابتكار في قطاع النقل. نربط رواد الأعمال والشركات الناشئة والشركات الراسخة بالدعم التنظيمي والموارد لتطوير حلول نقل متطورة للمملكة العربية السعودية.'],
                    'main_action' => [
                        'title' => ['en' => 'Learn More', 'ar' => 'اعرف المزيد'],
                        'url' => ['en' => '/en/about', 'ar' => '/ar/about'],
                    ],
                    'list' => [
                        ['title' => ['en' => 'Programs Launched', 'ar' => 'البرامج المُطلقة'], 'icon' => 'heroicon-o-rocket-launch', 'number' => '5'],
                        ['title' => ['en' => 'Active Participants', 'ar' => 'المشاركون النشطون'], 'icon' => 'heroicon-o-users', 'number' => '250+'],
                        ['title' => ['en' => 'Projects Submitted', 'ar' => 'المشاريع المقدمة'], 'icon' => 'heroicon-o-document-text', 'number' => '120+'],
                        ['title' => ['en' => 'Partners', 'ar' => 'الشركاء'], 'icon' => 'heroicon-o-building-office', 'number' => '30+'],
                    ],
                ],
            ],
            [
                'type' => 'services',
                'data' => [
                    'title' => ['en' => 'Our Services', 'ar' => 'خدماتنا'],
                    'text' => ['en' => 'Explore the services we offer to support transport innovation in the Kingdom.', 'ar' => 'استكشف الخدمات التي نقدمها لدعم الابتكار في قطاع النقل بالمملكة.'],
                    'services' => [
                        [
                            'title' => ['en' => 'Regulatory Sandbox Service', 'ar' => 'خدمة البيئة التنظيمية التجريبية'],
                            'tags' => ['en' => 'Innovation, Regulation, Testing', 'ar' => 'ابتكار، تنظيم، اختبار'],
                            'description' => ['en' => 'Apply to test your innovative transport solutions in a controlled regulatory environment with TGA oversight and support.', 'ar' => 'تقدم لاختبار حلول النقل المبتكرة في بيئة تنظيمية خاضعة للرقابة مع إشراف ودعم الهيئة العامة للنقل.'],
                            'main_action' => ['title' => ['en' => 'Apply', 'ar' => 'تقديم'], 'url' => ['en' => '/en/services/1', 'ar' => '/ar/services/1']],
                            'secondary_action' => ['title' => ['en' => 'Details', 'ar' => 'التفاصيل'], 'url' => ['en' => '/en/services/1', 'ar' => '/ar/services/1']],
                            'icon' => 'heroicon-o-beaker',
                        ],
                        [
                            'title' => ['en' => 'Car Rental Compliance Service', 'ar' => 'خدمة الامتثال لتأجير السيارات'],
                            'tags' => ['en' => 'Compliance, Car Rental, Regulation', 'ar' => 'امتثال، تأجير سيارات، تنظيم'],
                            'description' => ['en' => 'Ensure your car rental operations meet all TGA regulatory requirements through our compliance assessment and certification service.', 'ar' => 'تأكد من أن عمليات تأجير السيارات الخاصة بك تلبي جميع المتطلبات التنظيمية للهيئة العامة للنقل من خلال خدمة تقييم الامتثال والشهادات.'],
                            'main_action' => ['title' => ['en' => 'Apply', 'ar' => 'تقديم'], 'url' => ['en' => '/en/services/2', 'ar' => '/ar/services/2']],
                            'secondary_action' => ['title' => ['en' => 'Details', 'ar' => 'التفاصيل'], 'url' => ['en' => '/en/services/2', 'ar' => '/ar/services/2']],
                            'icon' => 'heroicon-o-truck',
                        ],
                    ],
                ],
            ],
            [
                'type' => 'partners',
                'data' => [
                    'title' => ['en' => 'Our Partners', 'ar' => 'شركاؤنا'],
                    'logos' => [
                        ['image' => '', 'title' => ['en' => 'Ministry of Transport', 'ar' => 'وزارة النقل']],
                        ['image' => '', 'title' => ['en' => 'Saudi Technology Ventures', 'ar' => 'صندوق التقنية السعودي']],
                        ['image' => '', 'title' => ['en' => 'KAUST Innovation', 'ar' => 'ابتكارات كاوست']],
                        ['image' => '', 'title' => ['en' => 'Monsha\'at', 'ar' => 'منشآت']],
                    ],
                ],
            ],
        ];

        DB::table('landing_pages')->where('id', 1)->update([
            'title' => 'TGA Innovation Platform',
            'content' => json_encode($content),
            'government_verification_banner_enabled' => true,
            'dga_registration_number' => 'TGA-2025-IP-001',
            'dga_certificate_url' => 'https://tga.gov.sa/certificate/innovation-platform',
            'updated_at' => now(),
        ]);

        $this->command->info('    ✓ Landing page updated with Builder block content');
    }

    // ═══════════════════════════════════════════════════════════════
    // 2. SERVICES
    // ═══════════════════════════════════════════════════════════════
    private function seedServices(): void
    {
        $this->command->info('  → Updating services...');

        // Service 1: Regulatory Sandbox
        DB::table('services')->where('id', 1)->update([
            'title' => json_encode(['en' => 'Regulatory Sandbox Service', 'ar' => 'خدمة البيئة التنظيمية التجريبية']),
            'metadata' => json_encode([
                'en' => [
                    'description' => 'The Regulatory Sandbox is a controlled testing environment where innovative transport companies can trial new technologies, services, and business models under TGA regulatory supervision. This service allows you to operate with temporary regulatory exemptions while demonstrating the viability and safety of your innovation.',
                    'tags' => 'Sandbox, Innovation, Regulation, Transport Technology, Testing Environment',
                    'startServiceLink' => '/en/programs',
                    'serviceLevelLink' => '/en/service-level-agreement',
                    'targetAudience' => 'Transport technology companies, startups, and innovators seeking to test new business models in a regulated environment',
                    'serviceChannels' => 'Online Portal, Email, Phone',
                    'serviceDuration' => 'Application review: 30 days | Readiness assessment: 15 days | Testing period: 1 calendar year | Graduation: 30 days',
                    'serviceCost' => 'Free of charge',
                    'paymentChannels' => 'N/A',
                    'FAQsLink' => '/en/faqs',
                    'phone' => '+966-11-000-0000',
                    'email' => 'sandbox@tga.gov.sa',
                    'userManual' => '/uploads/sandbox-user-manual-en.pdf',
                    'mobileApp' => '',
                ],
                'ar' => [
                    'description' => 'البيئة التنظيمية التجريبية هي بيئة اختبار خاضعة للرقابة حيث يمكن لشركات النقل المبتكرة تجربة التقنيات والخدمات ونماذج الأعمال الجديدة تحت إشراف تنظيمي من الهيئة العامة للنقل. تتيح لك هذه الخدمة العمل بإعفاءات تنظيمية مؤقتة مع إثبات جدوى وسلامة ابتكارك.',
                    'tags' => 'بيئة تجريبية، ابتكار، تنظيم، تقنية النقل، بيئة اختبار',
                    'startServiceLink' => '/ar/programs',
                    'serviceLevelLink' => '/ar/service-level-agreement',
                    'targetAudience' => 'شركات تقنية النقل والشركات الناشئة والمبتكرون الذين يسعون لاختبار نماذج أعمال جديدة في بيئة منظمة',
                    'serviceChannels' => 'البوابة الإلكترونية، البريد الإلكتروني، الهاتف',
                    'serviceDuration' => 'مراجعة الطلب: 30 يوم | تقييم الجاهزية: 15 يوم | فترة الاختبار: سنة تقويمية واحدة | التخرج: 30 يوم',
                    'serviceCost' => 'مجاني',
                    'paymentChannels' => 'لا ينطبق',
                    'FAQsLink' => '/ar/faqs',
                    'phone' => '+966-11-000-0000',
                    'email' => 'sandbox@tga.gov.sa',
                    'userManual' => '/uploads/sandbox-user-manual-ar.pdf',
                    'mobileApp' => '',
                ],
            ]),
            'content' => json_encode([
                'en' => [
                    'steps' => '<ol><li>Register on the Innovation Platform and create your company profile.</li><li>Select the Regulatory Sandbox program and complete the application form.</li><li>Upload all required documents including commercial registration, financial summaries, and operational plans.</li><li>Submit your application for review by TGA.</li><li>Receive readiness assessment results within 15 business days.</li><li>Upon approval, begin your 1-year testing period under regulatory supervision.</li><li>Submit monthly compliance reports and project updates.</li><li>Graduate from the sandbox with a full operating license.</li></ol>',
                    'requiredDocuments' => '<ul><li>Valid Commercial Registration (linked with Wathiq verification)</li><li>Valid Incorporation Contract Copy</li><li>Operational Plan (target market, geographic areas, profit/wage expectations)</li><li>Exit Plan with challenges, risks, and success factors</li><li>Technology Details Document</li><li>Risk Summary for transport users with mitigation plan</li><li>Financial Summary (P&L past 3 years, projected P&L 3 years, audited financials, current funding)</li><li>Pricing Policy Document</li><li>Beneficiary Protection Policy</li></ul>',
                    'conditions' => '<ul><li>The applicant must be a legally registered entity in the Kingdom of Saudi Arabia.</li><li>The proposed innovation must be related to the transport sector.</li><li>The applicant must demonstrate financial viability and operational capacity.</li><li>All testing must comply with safety standards and consumer protection requirements.</li><li>Monthly reports must be submitted during the testing period.</li><li>The applicant agrees to TGA inspection and monitoring at all times.</li></ul>',
                ],
                'ar' => [
                    'steps' => '<ol><li>سجّل في منصة الابتكار وأنشئ ملف شركتك.</li><li>اختر برنامج البيئة التنظيمية التجريبية وأكمل نموذج التقديم.</li><li>ارفع جميع المستندات المطلوبة بما في ذلك السجل التجاري والملخصات المالية والخطط التشغيلية.</li><li>قدم طلبك لمراجعة الهيئة العامة للنقل.</li><li>استلم نتائج تقييم الجاهزية خلال 15 يوم عمل.</li><li>عند الموافقة، ابدأ فترة الاختبار لمدة سنة واحدة تحت الإشراف التنظيمي.</li><li>قدم تقارير الامتثال الشهرية وتحديثات المشروع.</li><li>تخرج من البيئة التجريبية بترخيص تشغيل كامل.</li></ol>',
                    'requiredDocuments' => '<ul><li>سجل تجاري ساري المفعول (مرتبط بالتحقق عبر واثق)</li><li>نسخة من عقد التأسيس ساري المفعول</li><li>خطة تشغيلية (السوق المستهدف، المناطق الجغرافية، توقعات الأرباح/الأجور)</li><li>خطة الخروج مع التحديات والمخاطر وعوامل النجاح</li><li>وثيقة تفاصيل التقنية</li><li>ملخص المخاطر لمستخدمي النقل مع خطة التخفيف</li><li>ملخص مالي (الأرباح والخسائر لآخر 3 سنوات، توقعات 3 سنوات، القوائم المالية المدققة، التمويل الحالي)</li><li>وثيقة سياسة التسعير</li><li>سياسة حماية المستفيدين</li></ul>',
                    'conditions' => '<ul><li>يجب أن يكون المتقدم كيانًا مسجلًا قانونيًا في المملكة العربية السعودية.</li><li>يجب أن يكون الابتكار المقترح متعلقًا بقطاع النقل.</li><li>يجب على المتقدم إثبات الجدوى المالية والقدرة التشغيلية.</li><li>يجب أن تتوافق جميع الاختبارات مع معايير السلامة ومتطلبات حماية المستهلك.</li><li>يجب تقديم تقارير شهرية خلال فترة الاختبار.</li><li>يوافق المتقدم على التفتيش والمراقبة من قبل الهيئة في جميع الأوقات.</li></ul>',
                ],
            ]),
            'relatedServices' => json_encode([
                'en' => ['title' => 'Related Services', 'description' => 'Explore other services offered by the Innovation Platform', 'list' => []],
                'ar' => ['title' => 'خدمات ذات صلة', 'description' => 'استكشف الخدمات الأخرى التي تقدمها منصة الابتكار', 'list' => []],
            ]),
            'is_published' => true,
            'updated_at' => now(),
        ]);

        // Service 2: Car Rental Compliance
        DB::table('services')->where('id', 2)->update([
            'title' => json_encode(['en' => 'Car Rental Compliance & Regulatory Assessment', 'ar' => 'الامتثال التنظيمي لتأجير السيارات']),
            'metadata' => json_encode([
                'en' => [
                    'description' => 'A comprehensive compliance assessment service for car rental operators to ensure adherence to TGA regulatory guidelines. This service evaluates establishment requirements, vehicle standards, and customer protection measures through structured reporting and periodic inspections.',
                    'tags' => 'Compliance, Car Rental, Vehicle Standards, Customer Protection, Regulatory Assessment',
                    'startServiceLink' => '/en/programs',
                    'serviceLevelLink' => '/en/service-level-agreement',
                    'targetAudience' => 'Licensed car rental operators in the Kingdom of Saudi Arabia',
                    'serviceChannels' => 'Online Portal, Field Inspection',
                    'serviceDuration' => 'Monthly compliance reports | Quarterly on-site inspections',
                    'serviceCost' => 'Included in operator licensing fees',
                    'paymentChannels' => 'SADAD, Bank Transfer',
                    'FAQsLink' => '/en/faqs',
                    'phone' => '+966-11-000-0000',
                    'email' => 'compliance@tga.gov.sa',
                    'userManual' => '/uploads/compliance-manual-en.pdf',
                    'mobileApp' => '',
                ],
                'ar' => [
                    'description' => 'خدمة تقييم الامتثال الشاملة لمشغلي تأجير السيارات لضمان الالتزام بالمبادئ التوجيهية التنظيمية للهيئة العامة للنقل. تقيّم هذه الخدمة متطلبات المنشأة ومعايير المركبات وإجراءات حماية العملاء من خلال التقارير المنظمة والتفتيشات الدورية.',
                    'tags' => 'امتثال، تأجير سيارات، معايير المركبات، حماية العملاء، تقييم تنظيمي',
                    'startServiceLink' => '/ar/programs',
                    'serviceLevelLink' => '/ar/service-level-agreement',
                    'targetAudience' => 'مشغلو تأجير السيارات المرخصون في المملكة العربية السعودية',
                    'serviceChannels' => 'البوابة الإلكترونية، التفتيش الميداني',
                    'serviceDuration' => 'تقارير امتثال شهرية | تفتيشات ميدانية ربع سنوية',
                    'serviceCost' => 'مشمول في رسوم ترخيص المشغل',
                    'paymentChannels' => 'سداد، تحويل بنكي',
                    'FAQsLink' => '/ar/faqs',
                    'phone' => '+966-11-000-0000',
                    'email' => 'compliance@tga.gov.sa',
                    'userManual' => '/uploads/compliance-manual-ar.pdf',
                    'mobileApp' => '',
                ],
            ]),
            'content' => json_encode([
                'en' => [
                    'steps' => '<ol><li>Log in to the Innovation Platform with your operator credentials.</li><li>Navigate to the Car Rental Compliance section.</li><li>Complete the monthly compliance report forms covering establishment, vehicle, and customer requirements.</li><li>Upload supporting documentation and evidence of compliance.</li><li>Submit your report for TGA review.</li><li>Receive compliance assessment results and any corrective action requirements.</li></ol>',
                    'requiredDocuments' => '<ul><li>Valid operator license</li><li>Vehicle fleet registration documents</li><li>Insurance certificates for all vehicles</li><li>Customer complaint log</li><li>Vehicle inspection records</li><li>GPS tracking system documentation</li><li>Employee training records</li></ul>',
                    'conditions' => '<ul><li>Reports must be submitted monthly by the 15th of each month.</li><li>All vehicles must maintain valid registration and inspection certificates.</li><li>Operators must maintain comprehensive insurance coverage.</li><li>Customer complaints must be resolved within the specified timeframes.</li><li>GPS tracking must be active on all rental vehicles.</li></ul>',
                ],
                'ar' => [
                    'steps' => '<ol><li>سجل الدخول إلى منصة الابتكار ببيانات اعتماد المشغل.</li><li>انتقل إلى قسم الامتثال لتأجير السيارات.</li><li>أكمل نماذج تقارير الامتثال الشهرية التي تغطي متطلبات المنشأة والمركبات والعملاء.</li><li>ارفع الوثائق الداعمة وأدلة الامتثال.</li><li>قدم تقريرك لمراجعة الهيئة العامة للنقل.</li><li>استلم نتائج تقييم الامتثال وأي متطلبات إجراءات تصحيحية.</li></ol>',
                    'requiredDocuments' => '<ul><li>رخصة مشغل سارية المفعول</li><li>وثائق تسجيل أسطول المركبات</li><li>شهادات التأمين لجميع المركبات</li><li>سجل شكاوى العملاء</li><li>سجلات فحص المركبات</li><li>وثائق نظام تتبع GPS</li><li>سجلات تدريب الموظفين</li></ul>',
                    'conditions' => '<ul><li>يجب تقديم التقارير شهريًا بحلول الخامس عشر من كل شهر.</li><li>يجب أن تحافظ جميع المركبات على شهادات تسجيل وفحص سارية.</li><li>يجب على المشغلين الحفاظ على تغطية تأمينية شاملة.</li><li>يجب حل شكاوى العملاء ضمن الأطر الزمنية المحددة.</li><li>يجب أن يكون تتبع GPS نشطًا على جميع مركبات التأجير.</li></ul>',
                ],
            ]),
            'relatedServices' => json_encode([
                'en' => ['title' => 'Related Services', 'description' => 'Other services for transport operators', 'list' => []],
                'ar' => ['title' => 'خدمات ذات صلة', 'description' => 'خدمات أخرى لمشغلي النقل', 'list' => []],
            ]),
            'is_published' => true,
            'updated_at' => now(),
        ]);

        $this->command->info('    ✓ Services updated');
    }

    // ═══════════════════════════════════════════════════════════════
    // 3. SANDBOX COMPETITION
    // ═══════════════════════════════════════════════════════════════
    private function seedSandboxProgram(): int
    {
        $this->command->info('  → Creating Sandbox program...');

        $programId = DB::table('programs')->insertGetId([
            'title' => json_encode(['en' => 'TGA Regulatory Sandbox 2026', 'ar' => 'البيئة التنظيمية التجريبية للهيئة العامة للنقل 2026']),
            'about' => json_encode([
                'en' => '<p>The TGA Regulatory Sandbox is a controlled testing environment established by the Transport General Authority to enable innovative companies to test new transport technologies, services, and business models. The sandbox provides a structured pathway for innovators to demonstrate the viability and safety of their solutions under regulatory supervision.</p><p>The program consists of four stages: Application Submission (30 days), Readiness Assessment (15 days), Business Model Testing (1 calendar year), and Graduation (30 days). Successful participants will receive full operating licenses upon graduation.</p><p>This initiative aligns with Saudi Vision 2030 goals to foster innovation and technological advancement in the transport sector.</p>',
                'ar' => '<p>البيئة التنظيمية التجريبية للهيئة العامة للنقل هي بيئة اختبار خاضعة للرقابة أنشأتها الهيئة العامة للنقل لتمكين الشركات المبتكرة من اختبار تقنيات النقل الجديدة والخدمات ونماذج الأعمال. توفر البيئة التجريبية مسارًا منظمًا للمبتكرين لإثبات جدوى وسلامة حلولهم تحت الإشراف التنظيمي.</p><p>يتكون البرنامج من أربع مراحل: تقديم الطلب (30 يومًا)، تقييم الجاهزية (15 يومًا)، اختبار نموذج العمل (سنة تقويمية واحدة)، والتخرج من البيئة (30 يومًا). سيحصل المشاركون الناجحون على تراخيص تشغيل كاملة عند التخرج.</p><p>تتماشى هذه المبادرة مع أهداف رؤية المملكة 2030 لتعزيز الابتكار والتقدم التكنولوجي في قطاع النقل.</p>',
            ]),
            'type' => 'Sandbox',
            'terms_and_conditions' => json_encode([
                'en' => '<h3>Terms and Commitments</h3><ol><li>The applicant commits to submitting all required information accurately and pledges to update the Authority on any changes.</li><li>The applicant commits to not using the regulatory sandbox environment for any activity outside the scope of the approved test.</li><li>The applicant commits to providing periodic reports as required by the Authority.</li><li>The applicant understands that the Authority reserves the right to modify the terms of the sandbox at any time.</li><li>The applicant commits to protecting the data and privacy of all beneficiaries and users.</li><li>The applicant commits to maintaining adequate insurance coverage throughout the testing period.</li><li>The applicant acknowledges that participation in the sandbox does not guarantee a permanent license.</li><li>The applicant commits to immediately reporting any safety incidents or concerns.</li><li>The applicant agrees to cooperate fully with any TGA inspections or audits.</li><li>The applicant commits to ceasing operations immediately if requested by the Authority for safety reasons.</li><li>The applicant understands that the testing period may be extended or shortened at the Authority\'s discretion.</li><li>The applicant commits to returning all temporary exemptions upon exit from the sandbox.</li></ol>',
                'ar' => '<h3>الشروط والالتزامات</h3><ol><li>يلتزم المتقدم بتقديم جميع المعلومات المطلوبة بدقة ويتعهد بإبلاغ الهيئة بأي تغييرات.</li><li>يلتزم المتقدم بعدم استخدام البيئة التنظيمية التجريبية لأي نشاط خارج نطاق الاختبار المعتمد.</li><li>يلتزم المتقدم بتقديم التقارير الدورية حسب متطلبات الهيئة.</li><li>يدرك المتقدم أن الهيئة تحتفظ بالحق في تعديل شروط البيئة التجريبية في أي وقت.</li><li>يلتزم المتقدم بحماية بيانات وخصوصية جميع المستفيدين والمستخدمين.</li><li>يلتزم المتقدم بالحفاظ على تغطية تأمينية كافية طوال فترة الاختبار.</li><li>يقر المتقدم بأن المشاركة في البيئة التجريبية لا تضمن الحصول على ترخيص دائم.</li><li>يلتزم المتقدم بالإبلاغ فورًا عن أي حوادث أو مخاوف تتعلق بالسلامة.</li><li>يوافق المتقدم على التعاون الكامل مع أي تفتيشات أو تدقيقات من الهيئة.</li><li>يلتزم المتقدم بوقف العمليات فورًا إذا طلبت الهيئة ذلك لأسباب تتعلق بالسلامة.</li><li>يدرك المتقدم أن فترة الاختبار قد تمتد أو تقصر وفقًا لتقدير الهيئة.</li><li>يلتزم المتقدم بإعادة جميع الإعفاءات المؤقتة عند الخروج من البيئة التجريبية.</li></ol>',
            ]),
            'banner' => '',
            'is_published' => true,
            'is_archived' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Link to admin user
        $adminUser = DB::table('users')->where('email', 'admin@innovation-platform.com')->first();
        if ($adminUser) {
            DB::table('user_programs')->insert([
                'user_id' => $adminUser->id,
                'program_id' => $programId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info("    ✓ Sandbox program created (ID: {$programId})");
        return $programId;
    }

    // ═══════════════════════════════════════════════════════════════
    // 4. REGISTRATION FORM (from Arabic PDF - TGA Regulatory Sandbox)
    // ═══════════════════════════════════════════════════════════════
    private function seedRegistrationForm(int $programId): int
    {
        $this->command->info('  → Creating Sandbox registration form...');

        $formId = DB::table('forms')->insertGetId([
            'program_id' => $programId,
            'type' => 'registration',
            'name' => json_encode(['en' => 'Regulatory Sandbox Application Form', 'ar' => 'نموذج التقديم على البيئة التنظيمية التجريبية']),
            'description' => json_encode(['en' => 'Complete this application to apply for the TGA Regulatory Sandbox program. All fields marked as required must be completed.', 'ar' => 'أكمل هذا الطلب للتقديم على برنامج البيئة التنظيمية التجريبية للهيئة العامة للنقل. يجب إكمال جميع الحقول المطلوبة.']),
            'is_published' => true,
            'is_archived' => false,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ── Sections ──
        $section1Id = DB::table('form_sections')->insertGetId([
            'form_id' => $formId,
            'title' => json_encode(['en' => 'Company Information', 'ar' => 'معلومات الشركة']),
            'description' => json_encode(['en' => 'Basic company details and contact information', 'ar' => 'تفاصيل الشركة الأساسية ومعلومات الاتصال']),
            'sort' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $section2Id = DB::table('form_sections')->insertGetId([
            'form_id' => $formId,
            'title' => json_encode(['en' => 'Legal & Registration Documents', 'ar' => 'الوثائق القانونية والتسجيل']),
            'description' => json_encode(['en' => 'Upload required legal and registration documents', 'ar' => 'ارفع الوثائق القانونية والتسجيل المطلوبة']),
            'sort' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $section3Id = DB::table('form_sections')->insertGetId([
            'form_id' => $formId,
            'title' => json_encode(['en' => 'Business & Operations', 'ar' => 'الأعمال والعمليات']),
            'description' => json_encode(['en' => 'Describe your business model and operational plans', 'ar' => 'صف نموذج عملك وخططك التشغيلية']),
            'sort' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $section4Id = DB::table('form_sections')->insertGetId([
            'form_id' => $formId,
            'title' => json_encode(['en' => 'Financial Information', 'ar' => 'المعلومات المالية']),
            'description' => json_encode(['en' => 'Financial summaries and pricing information', 'ar' => 'الملخصات المالية ومعلومات التسعير']),
            'sort' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $section5Id = DB::table('form_sections')->insertGetId([
            'form_id' => $formId,
            'title' => json_encode(['en' => 'Beneficiary Information & Protection', 'ar' => 'معلومات المستفيدين والحماية']),
            'description' => json_encode(['en' => 'Details about your users and beneficiary protection policies', 'ar' => 'تفاصيل حول المستخدمين وسياسات حماية المستفيدين']),
            'sort' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $section6Id = DB::table('form_sections')->insertGetId([
            'form_id' => $formId,
            'title' => json_encode(['en' => 'Terms, Commitments & Signature', 'ar' => 'الشروط والالتزامات والتوقيع']),
            'description' => json_encode(['en' => 'Review and accept the terms and commitments', 'ar' => 'مراجعة وقبول الشروط والالتزامات']),
            'sort' => 6,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ── Section 1: Company Information Fields ──
        $fields = [
            // 1. Company Name
            ['form_id' => $formId, 'section_id' => $section1Id, 'label' => json_encode(['en' => 'Company Name', 'ar' => 'اسم الشركة']), 'type' => 'text', 'required' => true, 'slug' => 'company_name', 'placeholder' => json_encode(['en' => 'Enter company name', 'ar' => 'أدخل اسم الشركة']), 'hint' => json_encode(['en' => 'Pre-filled from your registration profile', 'ar' => 'يتم تعبئته تلقائياً من ملف التسجيل']), 'sort' => 1, 'options' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 2. Licensed by TGA?
            ['form_id' => $formId, 'section_id' => $section1Id, 'label' => json_encode(['en' => 'Is the applicant licensed by TGA or any other government entity?', 'ar' => 'هل المتقدم مرخص من الهيئة العامة للنقل أو أي جهة حكومية أخرى؟']), 'type' => 'radio', 'required' => true, 'slug' => 'is_licensed_by_tga', 'placeholder' => json_encode(['en' => '', 'ar' => '']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 2, 'options' => json_encode(['en' => ['Yes', 'No'], 'ar' => ['نعم', 'لا']]), 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 3. Business model
            ['form_id' => $formId, 'section_id' => $section1Id, 'label' => json_encode(['en' => 'Business Model to Apply For', 'ar' => 'نموذج العمل المراد التقديم عليه']), 'type' => 'dropdown', 'required' => true, 'slug' => 'business_model', 'placeholder' => json_encode(['en' => 'Select business model', 'ar' => 'اختر نموذج العمل']), 'hint' => json_encode(['en' => 'Select the transport business model you wish to test', 'ar' => 'اختر نموذج أعمال النقل الذي ترغب في اختباره']), 'sort' => 3, 'options' => json_encode(['en' => ['Ride-hailing', 'Car Rental', 'Logistics & Delivery', 'Public Transport', 'Micro-mobility', 'Autonomous Vehicles', 'Other'], 'ar' => ['نقل الركاب', 'تأجير السيارات', 'الخدمات اللوجستية والتوصيل', 'النقل العام', 'التنقل الصغير', 'المركبات ذاتية القيادة', 'أخرى']]), 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 4. Company type
            ['form_id' => $formId, 'section_id' => $section1Id, 'label' => json_encode(['en' => 'Company Type', 'ar' => 'نوع الشركة']), 'type' => 'dropdown', 'required' => true, 'slug' => 'company_type', 'placeholder' => json_encode(['en' => 'Select company type', 'ar' => 'اختر نوع الشركة']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 4, 'options' => json_encode(['en' => ['LLC', 'Joint Stock Company', 'Sole Proprietorship', 'Partnership', 'Branch of Foreign Company', 'Other'], 'ar' => ['شركة ذات مسؤولية محدودة', 'شركة مساهمة', 'مؤسسة فردية', 'شراكة', 'فرع شركة أجنبية', 'أخرى']]), 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 5. Offices and branches
            ['form_id' => $formId, 'section_id' => $section1Id, 'label' => json_encode(['en' => 'Offices and Branches in the Kingdom', 'ar' => 'المكاتب والفروع في المملكة']), 'type' => 'textarea', 'required' => true, 'slug' => 'offices_and_branches', 'placeholder' => json_encode(['en' => 'List your offices and branches locations', 'ar' => 'اذكر مواقع مكاتبك وفروعك']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 5, 'options' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 6. Contact numbers
            ['form_id' => $formId, 'section_id' => $section1Id, 'label' => json_encode(['en' => 'Contact Numbers', 'ar' => 'أرقام التواصل']), 'type' => 'phone', 'required' => true, 'slug' => 'contact_numbers', 'placeholder' => json_encode(['en' => '+966-XX-XXX-XXXX', 'ar' => '+966-XX-XXX-XXXX']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 6, 'options' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 7. Email
            ['form_id' => $formId, 'section_id' => $section1Id, 'label' => json_encode(['en' => 'Company Email & Representative Email', 'ar' => 'البريد الإلكتروني للشركة والممثل']), 'type' => 'email', 'required' => true, 'slug' => 'company_email', 'placeholder' => json_encode(['en' => 'company@example.com', 'ar' => 'company@example.com']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 7, 'options' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 8. Website
            ['form_id' => $formId, 'section_id' => $section1Id, 'label' => json_encode(['en' => 'Company Website', 'ar' => 'الموقع الإلكتروني للشركة']), 'type' => 'url', 'required' => false, 'slug' => 'company_website', 'placeholder' => json_encode(['en' => 'https://www.example.com', 'ar' => 'https://www.example.com']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 8, 'options' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 9. Other activities
            ['form_id' => $formId, 'section_id' => $section1Id, 'label' => json_encode(['en' => 'Other Business Activities', 'ar' => 'أنشطة تجارية أخرى']), 'type' => 'textarea', 'required' => false, 'slug' => 'other_business_activities', 'placeholder' => json_encode(['en' => 'Describe any other business activities', 'ar' => 'صف أي أنشطة تجارية أخرى']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 9, 'options' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null],

            // ── Section 2: Legal & Registration Documents ──
            // 10. Commercial registration
            ['form_id' => $formId, 'section_id' => $section2Id, 'label' => json_encode(['en' => 'Valid Commercial Registration', 'ar' => 'السجل التجاري الساري']), 'type' => 'file', 'required' => true, 'slug' => 'file_commercial_registration', 'placeholder' => json_encode(['en' => 'Upload commercial registration', 'ar' => 'ارفع السجل التجاري']), 'hint' => json_encode(['en' => 'Linked with Wathiq verification system', 'ar' => 'مرتبط بنظام التحقق واثق']), 'sort' => 10, 'options' => null, 'validation_rules' => json_encode(['max_size' => '10MB', 'allowed_types' => 'pdf,jpg,png']), 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 11. Incorporation contract
            ['form_id' => $formId, 'section_id' => $section2Id, 'label' => json_encode(['en' => 'Valid Incorporation Contract Copy', 'ar' => 'نسخة من عقد التأسيس ساري المفعول']), 'type' => 'file', 'required' => true, 'slug' => 'file_incorporation_contract', 'placeholder' => json_encode(['en' => 'Upload incorporation contract', 'ar' => 'ارفع عقد التأسيس']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 11, 'options' => null, 'validation_rules' => json_encode(['max_size' => '10MB', 'allowed_types' => 'pdf']), 'conditional_logic' => false, 'conditional_logic_rules' => null],

            // ── Section 3: Business & Operations ──
            // 12. Business overview
            ['form_id' => $formId, 'section_id' => $section3Id, 'label' => json_encode(['en' => 'Business Overview', 'ar' => 'نظرة عامة على الأعمال']), 'type' => 'textarea', 'required' => true, 'slug' => 'business_overview', 'placeholder' => json_encode(['en' => 'Provide a comprehensive overview of your business', 'ar' => 'قدم نظرة شاملة عن أعمالك']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 12, 'options' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 13.1 Number of employees
            ['form_id' => $formId, 'section_id' => $section3Id, 'label' => json_encode(['en' => 'Total Number of Employees', 'ar' => 'إجمالي عدد الموظفين']), 'type' => 'number', 'required' => true, 'slug' => 'total_employees', 'placeholder' => json_encode(['en' => 'Enter number', 'ar' => 'أدخل الرقم']), 'hint' => json_encode(['en' => 'Company size - total headcount', 'ar' => 'حجم الشركة - إجمالي عدد الموظفين']), 'sort' => 13, 'options' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 13.2 Saudi employees
            ['form_id' => $formId, 'section_id' => $section3Id, 'label' => json_encode(['en' => 'Number of Saudi Employees', 'ar' => 'عدد الموظفين السعوديين']), 'type' => 'number', 'required' => true, 'slug' => 'saudi_employees', 'placeholder' => json_encode(['en' => 'Enter number', 'ar' => 'أدخل الرقم']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 14, 'options' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 14. Operational plan
            ['form_id' => $formId, 'section_id' => $section3Id, 'label' => json_encode(['en' => 'Operational Plan', 'ar' => 'الخطة التشغيلية']), 'type' => 'file', 'required' => true, 'slug' => 'file_operational_plan', 'placeholder' => json_encode(['en' => 'Upload operational plan', 'ar' => 'ارفع الخطة التشغيلية']), 'hint' => json_encode(['en' => 'Include target market, geographic areas, profit/wage expectations', 'ar' => 'يشمل السوق المستهدف والمناطق الجغرافية وتوقعات الأرباح/الأجور']), 'sort' => 15, 'options' => null, 'validation_rules' => json_encode(['max_size' => '20MB', 'allowed_types' => 'pdf,doc,docx']), 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 15. Exit plan
            ['form_id' => $formId, 'section_id' => $section3Id, 'label' => json_encode(['en' => 'Exit Plan, Challenges, Risks & Success Factors', 'ar' => 'خطة الخروج والتحديات والمخاطر وعوامل النجاح']), 'type' => 'file', 'required' => true, 'slug' => 'file_exit_plan', 'placeholder' => json_encode(['en' => 'Upload exit plan document', 'ar' => 'ارفع وثيقة خطة الخروج']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 16, 'options' => null, 'validation_rules' => json_encode(['max_size' => '20MB', 'allowed_types' => 'pdf,doc,docx']), 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 16. Technology details
            ['form_id' => $formId, 'section_id' => $section3Id, 'label' => json_encode(['en' => 'Technology Details', 'ar' => 'تفاصيل التقنية']), 'type' => 'file', 'required' => true, 'slug' => 'file_technology_details', 'placeholder' => json_encode(['en' => 'Upload technology details document', 'ar' => 'ارفع وثيقة تفاصيل التقنية']), 'hint' => json_encode(['en' => 'Describe the technology used in your solution', 'ar' => 'صف التقنية المستخدمة في حلك']), 'sort' => 17, 'options' => null, 'validation_rules' => json_encode(['max_size' => '20MB', 'allowed_types' => 'pdf,doc,docx']), 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 17. Risk summary
            ['form_id' => $formId, 'section_id' => $section3Id, 'label' => json_encode(['en' => 'Risk Summary for Transport Users & Mitigation Plan', 'ar' => 'ملخص المخاطر لمستخدمي النقل وخطة التخفيف']), 'type' => 'file', 'required' => true, 'slug' => 'file_risk_summary', 'placeholder' => json_encode(['en' => 'Upload risk summary', 'ar' => 'ارفع ملخص المخاطر']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 18, 'options' => null, 'validation_rules' => json_encode(['max_size' => '20MB', 'allowed_types' => 'pdf,doc,docx']), 'conditional_logic' => false, 'conditional_logic_rules' => null],

            // ── Section 4: Financial Information ──
            // 18.1 P&L past 3 years
            ['form_id' => $formId, 'section_id' => $section4Id, 'label' => json_encode(['en' => 'Profit & Loss Statement - Past 3 Years', 'ar' => 'قائمة الأرباح والخسائر - آخر 3 سنوات']), 'type' => 'file', 'required' => true, 'slug' => 'file_pnl_past_3_years', 'placeholder' => json_encode(['en' => 'Upload P&L statement', 'ar' => 'ارفع قائمة الأرباح والخسائر']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 19, 'options' => null, 'validation_rules' => json_encode(['max_size' => '20MB', 'allowed_types' => 'pdf,xlsx,xls']), 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 18.2 Projected P&L
            ['form_id' => $formId, 'section_id' => $section4Id, 'label' => json_encode(['en' => 'Projected Profit & Loss - Next 3 Years', 'ar' => 'توقعات الأرباح والخسائر - السنوات الثلاث القادمة']), 'type' => 'file', 'required' => true, 'slug' => 'file_projected_pnl', 'placeholder' => json_encode(['en' => 'Upload projected P&L', 'ar' => 'ارفع التوقعات المالية']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 20, 'options' => null, 'validation_rules' => json_encode(['max_size' => '20MB', 'allowed_types' => 'pdf,xlsx,xls']), 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 18.3 Audited financials
            ['form_id' => $formId, 'section_id' => $section4Id, 'label' => json_encode(['en' => 'Audited Financial Statements', 'ar' => 'القوائم المالية المدققة']), 'type' => 'file', 'required' => true, 'slug' => 'file_audited_financials', 'placeholder' => json_encode(['en' => 'Upload audited financials', 'ar' => 'ارفع القوائم المالية المدققة']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 21, 'options' => null, 'validation_rules' => json_encode(['max_size' => '20MB', 'allowed_types' => 'pdf']), 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 18.4 Current funding
            ['form_id' => $formId, 'section_id' => $section4Id, 'label' => json_encode(['en' => 'Current Funding Sources & Amounts', 'ar' => 'مصادر ومبالغ التمويل الحالي']), 'type' => 'file', 'required' => true, 'slug' => 'file_current_funding', 'placeholder' => json_encode(['en' => 'Upload funding details', 'ar' => 'ارفع تفاصيل التمويل']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 22, 'options' => null, 'validation_rules' => json_encode(['max_size' => '20MB', 'allowed_types' => 'pdf,xlsx,xls']), 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 19. Pricing policy
            ['form_id' => $formId, 'section_id' => $section4Id, 'label' => json_encode(['en' => 'Pricing Policy', 'ar' => 'سياسة التسعير']), 'type' => 'file', 'required' => true, 'slug' => 'file_pricing_policy', 'placeholder' => json_encode(['en' => 'Upload pricing policy document', 'ar' => 'ارفع وثيقة سياسة التسعير']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 23, 'options' => null, 'validation_rules' => json_encode(['max_size' => '10MB', 'allowed_types' => 'pdf']), 'conditional_logic' => false, 'conditional_logic_rules' => null],

            // ── Section 5: Beneficiary Information ──
            // 20.1 Active users count
            ['form_id' => $formId, 'section_id' => $section5Id, 'label' => json_encode(['en' => 'Number of Active Users/Beneficiaries', 'ar' => 'عدد المستخدمين/المستفيدين النشطين']), 'type' => 'number', 'required' => true, 'slug' => 'active_users_count', 'placeholder' => json_encode(['en' => 'Enter number', 'ar' => 'أدخل الرقم']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 24, 'options' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 20.2 Brief description
            ['form_id' => $formId, 'section_id' => $section5Id, 'label' => json_encode(['en' => 'Brief Description of Beneficiaries', 'ar' => 'وصف موجز للمستفيدين']), 'type' => 'textarea', 'required' => true, 'slug' => 'beneficiary_description', 'placeholder' => json_encode(['en' => 'Describe your target beneficiaries', 'ar' => 'صف المستفيدين المستهدفين']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 25, 'options' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 20.3 Complaints
            ['form_id' => $formId, 'section_id' => $section5Id, 'label' => json_encode(['en' => 'Complaints Received in Last 12 Months', 'ar' => 'الشكاوى المستلمة في آخر 12 شهراً']), 'type' => 'textarea', 'required' => false, 'slug' => 'complaints_last_12_months', 'placeholder' => json_encode(['en' => 'Describe complaints and resolutions', 'ar' => 'صف الشكاوى والحلول']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 26, 'options' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 21.1 Commitments
            ['form_id' => $formId, 'section_id' => $section5Id, 'label' => json_encode(['en' => 'Beneficiary Protection Commitments', 'ar' => 'التزامات حماية المستفيدين']), 'type' => 'textarea', 'required' => true, 'slug' => 'beneficiary_protection_commitments', 'placeholder' => json_encode(['en' => 'Describe your commitments to protect beneficiaries', 'ar' => 'صف التزاماتك لحماية المستفيدين']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 27, 'options' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // 21.2 Protection policy document
            ['form_id' => $formId, 'section_id' => $section5Id, 'label' => json_encode(['en' => 'Beneficiary Protection Policy & Examples', 'ar' => 'سياسة حماية المستفيدين والأمثلة']), 'type' => 'file', 'required' => true, 'slug' => 'file_beneficiary_protection_policy', 'placeholder' => json_encode(['en' => 'Upload protection policy', 'ar' => 'ارفع سياسة الحماية']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 28, 'options' => null, 'validation_rules' => json_encode(['max_size' => '10MB', 'allowed_types' => 'pdf']), 'conditional_logic' => false, 'conditional_logic_rules' => null],

            // ── Section 6: Terms & Signature ──
            // 22. Terms acknowledgment (checkbox with 12 mandatory options)
            ['form_id' => $formId, 'section_id' => $section6Id, 'label' => json_encode(['en' => 'Terms and Commitments Acknowledgment', 'ar' => 'الإقرار بالشروط والالتزامات']), 'type' => 'checkbox', 'required' => true, 'slug' => 'terms_acknowledgment', 'placeholder' => json_encode(['en' => '', 'ar' => '']), 'hint' => json_encode(['en' => 'You must accept all terms and commitments to proceed', 'ar' => 'يجب عليك قبول جميع الشروط والالتزامات للمتابعة']), 'sort' => 29, 'options' => json_encode(['en' => ['I commit to submitting all required information accurately', 'I commit to not using the sandbox for any unapproved activity', 'I commit to providing periodic reports as required', 'I understand the Authority may modify sandbox terms', 'I commit to protecting data and privacy of beneficiaries', 'I commit to maintaining adequate insurance coverage', 'I acknowledge participation does not guarantee a permanent license', 'I commit to immediately reporting safety incidents', 'I agree to cooperate with TGA inspections and audits', 'I commit to ceasing operations if requested for safety reasons', 'I understand the testing period may be adjusted', 'I commit to returning temporary exemptions upon exit'], 'ar' => ['ألتزم بتقديم جميع المعلومات المطلوبة بدقة', 'ألتزم بعدم استخدام البيئة التجريبية لأي نشاط غير معتمد', 'ألتزم بتقديم التقارير الدورية حسب المطلوب', 'أدرك أن الهيئة قد تعدل شروط البيئة التجريبية', 'ألتزم بحماية بيانات وخصوصية المستفيدين', 'ألتزم بالحفاظ على تغطية تأمينية كافية', 'أقر بأن المشاركة لا تضمن ترخيصاً دائماً', 'ألتزم بالإبلاغ فوراً عن حوادث السلامة', 'أوافق على التعاون مع تفتيشات وتدقيقات الهيئة', 'ألتزم بوقف العمليات إذا طُلب لأسباب السلامة', 'أدرك أن فترة الاختبار قد تُعدَّل', 'ألتزم بإعادة الإعفاءات المؤقتة عند الخروج']]), 'validation_rules' => null, 'mandatory_options' => json_encode([1,2,3,4,5,6,7,8,9,10,11,12]), 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // Applicant Name
            ['form_id' => $formId, 'section_id' => $section6Id, 'label' => json_encode(['en' => 'Authorized Signatory Name', 'ar' => 'اسم المفوض بالتوقيع']), 'type' => 'text', 'required' => true, 'slug' => 'signatory_name', 'placeholder' => json_encode(['en' => 'Enter full name', 'ar' => 'أدخل الاسم الكامل']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 30, 'options' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null],
            // Date
            ['form_id' => $formId, 'section_id' => $section6Id, 'label' => json_encode(['en' => 'Date of Submission', 'ar' => 'تاريخ التقديم']), 'type' => 'date', 'required' => true, 'slug' => 'submission_date', 'placeholder' => json_encode(['en' => '', 'ar' => '']), 'hint' => json_encode(['en' => '', 'ar' => '']), 'sort' => 31, 'options' => null, 'validation_rules' => null, 'conditional_logic' => false, 'conditional_logic_rules' => null],
        ];

        foreach ($fields as $field) {
            $field['created_at'] = now();
            $field['updated_at'] = now();
            if (!isset($field['mandatory_options'])) {
                $field['mandatory_options'] = null;
            }
            DB::table('form_fields')->insert($field);
        }

        $this->command->info("    ✓ Registration form created with " . count($fields) . " fields (ID: {$formId})");
        return $formId;
    }

    // ═══════════════════════════════════════════════════════════════
    // 5. PROJECT FORMS (from Car Rental Report PDF - 5 sheets)
    // ═══════════════════════════════════════════════════════════════
    private function seedProjectForms(int $programId): array
    {
        $this->command->info('  → Creating 5 project forms...');
        $formIds = [];

        // ── Form 1: Establishment Requirements Part 1 (1.1-1.10) ──
        $formIds[] = $this->createProjectForm($programId, 
            ['en' => 'Establishment Requirements - Insurance & Contracts', 'ar' => 'متطلبات المنشأة - التأمين والعقود'],
            ['en' => 'Compliance report covering establishment requirements related to insurance, contracts, and financial handling (Guidelines 1.1-1.10)', 'ar' => 'تقرير الامتثال الذي يغطي متطلبات المنشأة المتعلقة بالتأمين والعقود والتعامل المالي (المبادئ التوجيهية 1.1-1.10)'],
            [
                ['en' => 'Insurance verification for vehicles against accidents', 'ar' => 'التحقق من تأمين المركبات ضد الحوادث'],
                ['en' => 'Right of the lessee to terminate the contract before its expiry without being obligated to pay the remaining rental amount', 'ar' => 'حق المستأجر في إنهاء العقد قبل انتهاء مدته دون إلزامه بسداد المبلغ المتبقي'],
                ['en' => 'Amounts are only collected through approved electronic payment methods', 'ar' => 'تحصيل المبالغ فقط من خلال وسائل الدفع الإلكترونية المعتمدة'],
                ['en' => 'Refund of the insurance deposit to the lessee within (10) business days from the vehicle return date', 'ar' => 'إعادة مبلغ التأمين للمستأجر خلال (10) أيام عمل من تاريخ إرجاع المركبة'],
                ['en' => 'Handling of found items left by the lessee in the rented vehicle', 'ar' => 'التعامل مع المفقودات التي يتركها المستأجر في المركبة المؤجرة'],
                ['en' => 'Availability of means for electronic communication with customers (e.g. app, website)', 'ar' => 'توفر وسائل التواصل الإلكتروني مع العملاء (مثل التطبيق أو الموقع الإلكتروني)'],
                ['en' => 'Availability of an electronic system for managing reservations', 'ar' => 'توفر نظام إلكتروني لإدارة الحجوزات'],
                ['en' => 'Providing a unified number and customer service available from 8 AM to 10 PM', 'ar' => 'توفير رقم موحد وخدمة عملاء متاحة من الساعة 8 صباحاً حتى 10 مساءً'],
                ['en' => 'Provision of a complaint system accessible to the lessee', 'ar' => 'توفير نظام شكاوى يمكن للمستأجر الوصول إليه'],
                ['en' => 'Compliance with parking regulations in designated areas', 'ar' => 'الامتثال لأنظمة وقوف المركبات في المناطق المخصصة'],
            ]
        );

        // ── Form 2: Establishment Requirements Part 2 (1.11-1.21) ──
        $formIds[] = $this->createProjectForm($programId,
            ['en' => 'Establishment Requirements - Operations & Safety', 'ar' => 'متطلبات المنشأة - العمليات والسلامة'],
            ['en' => 'Compliance report covering establishment operational and safety requirements (Guidelines 1.11-1.21)', 'ar' => 'تقرير الامتثال الذي يغطي متطلبات التشغيل والسلامة للمنشأة (المبادئ التوجيهية 1.11-1.21)'],
            [
                ['en' => 'Publication of rental terms and conditions for customer review before contract', 'ar' => 'نشر شروط وأحكام التأجير لمراجعة العميل قبل العقد'],
                ['en' => 'Providing the lessee with a copy of the contract in Arabic', 'ar' => 'تزويد المستأجر بنسخة من العقد باللغة العربية'],
                ['en' => 'All contract terms comply with the regulations issued by the Authority', 'ar' => 'جميع شروط العقد تتوافق مع الأنظمة الصادرة عن الهيئة'],
                ['en' => 'Adherence to maximum mileage limits specified in the contract', 'ar' => 'الالتزام بالحدود القصوى للمسافة المحددة في العقد'],
                ['en' => 'The facility has branches or delivery service covering major cities', 'ar' => 'المنشأة لديها فروع أو خدمة توصيل تغطي المدن الرئيسية'],
                ['en' => 'Providing 24-hour roadside assistance for rented vehicles', 'ar' => 'توفير خدمة مساعدة على الطريق على مدار 24 ساعة للمركبات المؤجرة'],
                ['en' => 'Notifying lessees in advance of any changes to terms or pricing', 'ar' => 'إخطار المستأجرين مسبقاً بأي تغييرات في الشروط أو الأسعار'],
                ['en' => 'Employee training on customer service and safety protocols', 'ar' => 'تدريب الموظفين على خدمة العملاء وبروتوكولات السلامة'],
                ['en' => 'Maintaining data privacy and protection of customer information', 'ar' => 'الحفاظ على خصوصية البيانات وحماية معلومات العملاء'],
                ['en' => 'Implementation of anti-fraud measures in payment processing', 'ar' => 'تطبيق إجراءات مكافحة الاحتيال في معالجة المدفوعات'],
                ['en' => 'Regular audit of rental operations and compliance documentation', 'ar' => 'مراجعة دورية لعمليات التأجير ووثائق الامتثال'],
            ]
        );

        // ── Form 3: Vehicle Requirements (2.1-2.5) ──
        $formIds[] = $this->createProjectForm($programId,
            ['en' => 'Vehicle Requirements Compliance', 'ar' => 'امتثال متطلبات المركبات'],
            ['en' => 'Compliance report covering vehicle registration, inspection, maintenance, GPS, and replacement requirements (Guidelines 2.1-2.5)', 'ar' => 'تقرير الامتثال الذي يغطي متطلبات تسجيل المركبات والفحص والصيانة ونظام تحديد المواقع والاستبدال (المبادئ التوجيهية 2.1-2.5)'],
            [
                ['en' => 'All rental vehicles have valid registration certificates', 'ar' => 'جميع مركبات التأجير لديها شهادات تسجيل سارية المفعول'],
                ['en' => 'All vehicles pass periodic technical inspection (Fahas)', 'ar' => 'جميع المركبات تجتاز الفحص الفني الدوري (فحص)'],
                ['en' => 'Regular maintenance schedule followed and documented', 'ar' => 'اتباع جدول صيانة منتظم وموثق'],
                ['en' => 'GPS tracking system installed and operational in all vehicles', 'ar' => 'نظام تتبع GPS مثبت وعامل في جميع المركبات'],
                ['en' => 'Replacement vehicle provided within specified timeframe in case of breakdown', 'ar' => 'توفير مركبة بديلة خلال الإطار الزمني المحدد في حالة العطل'],
            ]
        );

        // ── Form 4: Customer Requirements (3.1-3.10) ──
        $formIds[] = $this->createProjectForm($programId,
            ['en' => 'Customer Requirements Compliance', 'ar' => 'امتثال متطلبات العملاء'],
            ['en' => 'Compliance report covering customer-related requirements including age, licensing, and responsibility (Guidelines 3.1-3.10)', 'ar' => 'تقرير الامتثال الذي يغطي المتطلبات المتعلقة بالعملاء بما في ذلك العمر والترخيص والمسؤولية (المبادئ التوجيهية 3.1-3.10)'],
            [
                ['en' => 'Minimum age requirement verified for all lessees', 'ar' => 'التحقق من شرط الحد الأدنى للعمر لجميع المستأجرين'],
                ['en' => 'Valid driving license verified before rental', 'ar' => 'التحقق من رخصة القيادة السارية قبل التأجير'],
                ['en' => 'Clear liability and responsibility terms provided to lessee', 'ar' => 'تزويد المستأجر بشروط المسؤولية الواضحة'],
                ['en' => 'Video recording policy disclosed and implemented', 'ar' => 'سياسة التسجيل بالفيديو معلنة ومطبقة'],
                ['en' => 'Accident reporting procedures clearly communicated', 'ar' => 'إجراءات الإبلاغ عن الحوادث واضحة ومُبلّغة'],
                ['en' => 'Damage assessment and reporting process documented', 'ar' => 'عملية تقييم الأضرار والإبلاغ عنها موثقة'],
                ['en' => 'Customer identity verification procedures followed', 'ar' => 'اتباع إجراءات التحقق من هوية العميل'],
                ['en' => 'International driving permit acceptance policy defined', 'ar' => 'سياسة قبول رخصة القيادة الدولية محددة'],
                ['en' => 'Additional driver registration requirements enforced', 'ar' => 'متطلبات تسجيل السائق الإضافي مطبقة'],
                ['en' => 'Customer orientation on vehicle features and safety provided', 'ar' => 'توجيه العميل حول ميزات المركبة والسلامة مُقدَّم'],
            ]
        );

        // ── Form 5: Monthly Operations & KPI Summary ──
        $formIds[] = $this->createProjectForm($programId,
            ['en' => 'Monthly Operations & KPI Report', 'ar' => 'تقرير العمليات الشهرية ومؤشرات الأداء'],
            ['en' => 'Monthly summary of operational metrics and key performance indicators for the sandbox period', 'ar' => 'ملخص شهري لمقاييس العمليات ومؤشرات الأداء الرئيسية لفترة البيئة التجريبية'],
            null // Custom fields below
        );

        // Add custom fields for Form 5
        $lastFormId = end($formIds);
        $kpiSectionId = DB::table('form_sections')->insertGetId([
            'form_id' => $lastFormId,
            'title' => json_encode(['en' => 'Monthly KPI Report', 'ar' => 'تقرير مؤشرات الأداء الشهري']),
            'description' => json_encode(['en' => 'Report your monthly operational metrics', 'ar' => 'أبلغ عن مقاييس العمليات الشهرية']),
            'sort' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kpiFields = [
            ['label' => json_encode(['en' => 'Reporting Month', 'ar' => 'شهر التقرير']), 'type' => 'date', 'required' => true, 'slug' => 'reporting_month', 'sort' => 1],
            ['label' => json_encode(['en' => 'Total Rentals This Month', 'ar' => 'إجمالي التأجيرات هذا الشهر']), 'type' => 'number', 'required' => true, 'slug' => 'total_rentals', 'sort' => 2],
            ['label' => json_encode(['en' => 'Total Revenue (SAR)', 'ar' => 'إجمالي الإيرادات (ريال)']), 'type' => 'number', 'required' => true, 'slug' => 'total_revenue', 'sort' => 3],
            ['label' => json_encode(['en' => 'Number of Active Vehicles', 'ar' => 'عدد المركبات النشطة']), 'type' => 'number', 'required' => true, 'slug' => 'active_vehicles', 'sort' => 4],
            ['label' => json_encode(['en' => 'Vehicle Utilization Rate (%)', 'ar' => 'معدل استخدام المركبات (%)']), 'type' => 'number', 'required' => true, 'slug' => 'utilization_rate', 'sort' => 5],
            ['label' => json_encode(['en' => 'Customer Satisfaction Score (1-10)', 'ar' => 'درجة رضا العملاء (1-10)']), 'type' => 'rating', 'required' => true, 'slug' => 'satisfaction_score', 'sort' => 6],
            ['label' => json_encode(['en' => 'Number of Complaints Received', 'ar' => 'عدد الشكاوى المستلمة']), 'type' => 'number', 'required' => true, 'slug' => 'complaints_count', 'sort' => 7],
            ['label' => json_encode(['en' => 'Number of Accidents/Incidents', 'ar' => 'عدد الحوادث']), 'type' => 'number', 'required' => true, 'slug' => 'accidents_count', 'sort' => 8],
            ['label' => json_encode(['en' => 'Average Response Time to Complaints (hours)', 'ar' => 'متوسط وقت الاستجابة للشكاوى (ساعات)']), 'type' => 'number', 'required' => false, 'slug' => 'avg_response_time', 'sort' => 9],
            ['label' => json_encode(['en' => 'Compliance Issues Identified', 'ar' => 'مشاكل الامتثال المحددة']), 'type' => 'textarea', 'required' => false, 'slug' => 'compliance_issues', 'sort' => 10],
            ['label' => json_encode(['en' => 'Corrective Actions Taken', 'ar' => 'الإجراءات التصحيحية المتخذة']), 'type' => 'textarea', 'required' => false, 'slug' => 'corrective_actions', 'sort' => 11],
            ['label' => json_encode(['en' => 'Monthly Operations Report Upload', 'ar' => 'رفع تقرير العمليات الشهري']), 'type' => 'file', 'required' => true, 'slug' => 'file_monthly_report', 'sort' => 12],
        ];

        foreach ($kpiFields as $field) {
            DB::table('form_fields')->insert(array_merge($field, [
                'form_id' => $lastFormId,
                'section_id' => $kpiSectionId,
                'placeholder' => json_encode(['en' => '', 'ar' => '']),
                'hint' => json_encode(['en' => '', 'ar' => '']),
                'options' => null,
                'validation_rules' => null,
                'conditional_logic' => false,
                'conditional_logic_rules' => null,
                'mandatory_options' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $this->command->info("    ✓ 5 project forms created (IDs: " . implode(', ', $formIds) . ")");
        return $formIds;
    }

    /**
     * Helper: Create a project form with guideline-based fields
     * Each guideline gets: section_header, radio (adherence), text (applied limit), textarea (notes)
     */
    private function createProjectForm(int $programId, array $name, array $description, ?array $guidelines): int
    {
        $formId = DB::table('forms')->insertGetId([
            'program_id' => $programId,
            'type' => 'project',
            'name' => json_encode($name),
            'description' => json_encode($description),
            'is_published' => true,
            'is_archived' => false,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($guidelines === null) {
            return $formId;
        }

        // Create a single section for the guidelines
        $sectionId = DB::table('form_sections')->insertGetId([
            'form_id' => $formId,
            'title' => json_encode(['en' => 'Compliance Assessment', 'ar' => 'تقييم الامتثال']),
            'description' => json_encode(['en' => 'Assess adherence to each regulatory guideline', 'ar' => 'تقييم الالتزام بكل مبدأ توجيهي تنظيمي']),
            'sort' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Default project name field
        DB::table('form_fields')->insert([
            'form_id' => $formId, 'section_id' => $sectionId,
            'label' => json_encode(['en' => 'Project Name', 'ar' => 'اسم المشروع']),
            'type' => 'text', 'required' => true, 'slug' => 'project_name',
            'placeholder' => json_encode(['en' => 'Enter project/report name', 'ar' => 'أدخل اسم المشروع/التقرير']),
            'hint' => json_encode(['en' => 'This field is required and cannot be deleted.', 'ar' => 'هذا الحقل مطلوب ولا يمكن حذفه.']),
            'sort' => 1, 'options' => null, 'validation_rules' => null,
            'conditional_logic' => false, 'conditional_logic_rules' => null, 'mandatory_options' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $sortOrder = 2;
        foreach ($guidelines as $index => $guideline) {
            $num = $index + 1;
            $slugBase = 'guideline_' . $num;

            // Section header for the guideline
            DB::table('form_fields')->insert([
                'form_id' => $formId, 'section_id' => $sectionId,
                'label' => json_encode(['en' => "Guideline {$num}: {$guideline['en']}", 'ar' => "المبدأ التوجيهي {$num}: {$guideline['ar']}"]),
                'type' => 'section_header', 'required' => false, 'slug' => $slugBase . '_header',
                'placeholder' => json_encode(['en' => '', 'ar' => '']),
                'hint' => json_encode(['en' => '', 'ar' => '']),
                'sort' => $sortOrder++, 'options' => null, 'validation_rules' => null,
                'conditional_logic' => false, 'conditional_logic_rules' => null, 'mandatory_options' => null,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            // Adherence radio
            DB::table('form_fields')->insert([
                'form_id' => $formId, 'section_id' => $sectionId,
                'label' => json_encode(['en' => "Adherence to Guideline {$num}", 'ar' => "الالتزام بالمبدأ التوجيهي {$num}"]),
                'type' => 'radio', 'required' => true, 'slug' => $slugBase . '_adherence',
                'placeholder' => json_encode(['en' => '', 'ar' => '']),
                'hint' => json_encode(['en' => 'Does the operator adhere to this guideline?', 'ar' => 'هل يلتزم المشغل بهذا المبدأ التوجيهي؟']),
                'sort' => $sortOrder++,
                'options' => json_encode(['en' => ['Yes', 'No', 'Partially'], 'ar' => ['نعم', 'لا', 'جزئياً']]),
                'validation_rules' => null,
                'conditional_logic' => false, 'conditional_logic_rules' => null, 'mandatory_options' => null,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            // Applied limit
            DB::table('form_fields')->insert([
                'form_id' => $formId, 'section_id' => $sectionId,
                'label' => json_encode(['en' => "Applied Limit for Guideline {$num}", 'ar' => "الحد المطبق للمبدأ التوجيهي {$num}"]),
                'type' => 'text', 'required' => false, 'slug' => $slugBase . '_applied_limit',
                'placeholder' => json_encode(['en' => 'Enter applied limit if any', 'ar' => 'أدخل الحد المطبق إن وجد']),
                'hint' => json_encode(['en' => '', 'ar' => '']),
                'sort' => $sortOrder++, 'options' => null, 'validation_rules' => null,
                'conditional_logic' => false, 'conditional_logic_rules' => null, 'mandatory_options' => null,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            // Notes
            DB::table('form_fields')->insert([
                'form_id' => $formId, 'section_id' => $sectionId,
                'label' => json_encode(['en' => "Notes for Guideline {$num}", 'ar' => "ملاحظات للمبدأ التوجيهي {$num}"]),
                'type' => 'textarea', 'required' => false, 'slug' => $slugBase . '_notes',
                'placeholder' => json_encode(['en' => 'Enter any additional notes', 'ar' => 'أدخل أي ملاحظات إضافية']),
                'hint' => json_encode(['en' => '', 'ar' => '']),
                'sort' => $sortOrder++, 'options' => null, 'validation_rules' => null,
                'conditional_logic' => false, 'conditional_logic_rules' => null, 'mandatory_options' => null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $formId;
    }

    // ═══════════════════════════════════════════════════════════════
    // 6. STAGES for Sandbox Program
    // ═══════════════════════════════════════════════════════════════
    private function seedStages(int $programId, int $registrationFormId, array $projectFormIds): void
    {
        $this->command->info('  → Creating stages for Sandbox...');

        $now = Carbon::now();

        // Stage 1: Application Submission (Registration) - 30 days
        DB::table('stages')->insert([
            'program_id' => $programId,
            'slug' => 'registration',
            'title' => json_encode(['en' => 'Application Submission', 'ar' => 'تقديم الطلب']),
            'description' => json_encode(['en' => 'Submit your Regulatory Sandbox application with all required documents. Review period: 30 business days.', 'ar' => 'قدم طلبك للبيئة التنظيمية التجريبية مع جميع المستندات المطلوبة. فترة المراجعة: 30 يوم عمل.']),
            'form_id' => $registrationFormId,
            'form_ids' => null,
            'starts_at' => $now->copy()->toDateTimeString(),
            'ends_at' => $now->copy()->addDays(60)->toDateTimeString(),
            'is_visible' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Stage 2: Readiness Assessment - 15 days
        DB::table('stages')->insert([
            'program_id' => $programId,
            'slug' => 'evaluation',
            'title' => json_encode(['en' => 'Readiness Assessment', 'ar' => 'تقييم الجاهزية']),
            'description' => json_encode(['en' => 'TGA evaluates your readiness to enter the sandbox environment. Assessment period: 15 business days.', 'ar' => 'تقيّم الهيئة جاهزيتك لدخول البيئة التجريبية. فترة التقييم: 15 يوم عمل.']),
            'form_id' => null,
            'form_ids' => null,
            'starts_at' => $now->copy()->addDays(60)->toDateTimeString(),
            'ends_at' => $now->copy()->addDays(75)->toDateTimeString(),
            'is_visible' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Stage 3: Business Model Testing (Project Submission) - 1 calendar year
        DB::table('stages')->insert([
            'program_id' => $programId,
            'slug' => 'project-submission',
            'title' => json_encode(['en' => 'Business Model Testing', 'ar' => 'اختبار نموذج العمل']),
            'description' => json_encode(['en' => 'Test your business model in a controlled environment. Submit monthly compliance reports and project updates. Duration: 1 calendar year.', 'ar' => 'اختبر نموذج عملك في بيئة خاضعة للرقابة. قدم تقارير الامتثال الشهرية وتحديثات المشروع. المدة: سنة تقويمية واحدة.']),
            'form_id' => $projectFormIds[0] ?? null,
            'form_ids' => json_encode($projectFormIds),
            'starts_at' => $now->copy()->addDays(75)->toDateTimeString(),
            'ends_at' => $now->copy()->addDays(75)->addYear()->toDateTimeString(),
            'is_visible' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Stage 4: Graduation - 30 days
        DB::table('stages')->insert([
            'program_id' => $programId,
            'slug' => 'evaluation',
            'title' => json_encode(['en' => 'Graduation from Sandbox', 'ar' => 'التخرج من البيئة التجريبية']),
            'description' => json_encode(['en' => 'Final evaluation and graduation process. Successful participants receive full operating licenses. Duration: 30 days.', 'ar' => 'عملية التقييم النهائي والتخرج. يحصل المشاركون الناجحون على تراخيص تشغيل كاملة. المدة: 30 يومًا.']),
            'form_id' => null,
            'form_ids' => null,
            'starts_at' => $now->copy()->addDays(75)->addYear()->toDateTimeString(),
            'ends_at' => $now->copy()->addDays(105)->addYear()->toDateTimeString(),
            'is_visible' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('    ✓ 4 stages created for Sandbox');
    }

    // ═══════════════════════════════════════════════════════════════
    // 7. FORM STEPS (Registration) & PROJECT STEPS
    // ═══════════════════════════════════════════════════════════════
    private function seedFormSteps(int $registrationFormId): void
    {
        $this->command->info('  → Creating registration form steps...');

        // Get field IDs for the registration form, grouped by section
        $fields = DB::table('form_fields')
            ->where('form_id', $registrationFormId)
            ->orderBy('sort')
            ->get();

        $fieldsBySection = $fields->groupBy('section_id');
        $sectionNames = DB::table('form_sections')
            ->where('form_id', $registrationFormId)
            ->orderBy('sort')
            ->pluck('title', 'id');

        $stepOrder = 1;
        foreach ($sectionNames as $sectionId => $titleJson) {
            $title = json_decode($titleJson, true);
            $sectionFields = $fieldsBySection->get($sectionId, collect());
            $fieldIds = $sectionFields->pluck('id')->toArray();

            if (empty($fieldIds)) continue;

            DB::table('form_steps')->insert([
                'form_id' => $registrationFormId,
                'name' => json_encode($title),
                'step_order' => $stepOrder++,
                'field_ids' => json_encode($fieldIds),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info("    ✓ " . ($stepOrder - 1) . " registration form steps created");
    }

    private function seedProjectSteps(array $projectFormIds): void
    {
        $this->command->info('  → Creating project form steps...');

        $totalSteps = 0;
        foreach ($projectFormIds as $formId) {
            $fields = DB::table('form_fields')
                ->where('form_id', $formId)
                ->orderBy('sort')
                ->get();

            $fieldsBySection = $fields->groupBy('section_id');
            $sectionNames = DB::table('form_sections')
                ->where('form_id', $formId)
                ->orderBy('sort')
                ->pluck('title', 'id');

            $stepOrder = 1;
            foreach ($sectionNames as $sectionId => $titleJson) {
                $title = json_decode($titleJson, true);
                $sectionFields = $fieldsBySection->get($sectionId, collect());
                $fieldIds = $sectionFields->pluck('id')->toArray();

                if (empty($fieldIds)) continue;

                DB::table('project_steps')->insert([
                    'form_id' => $formId,
                    'name' => json_encode($title),
                    'step_order' => $stepOrder++,
                    'field_ids' => json_encode($fieldIds),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $totalSteps++;
            }
        }

        $this->command->info("    ✓ {$totalSteps} project form steps created");
    }

    // ═══════════════════════════════════════════════════════════════
    // 8. REGISTRATION FORM CONFIG
    // ═══════════════════════════════════════════════════════════════
    private function seedRegistrationFormConfig(int $programId): void
    {
        $this->command->info('  → Creating registration form config...');

        DB::table('registration_form_configs')->insert([
            'program_id' => $programId,
            'registration_type' => 'individual',
            'min_age' => null,
            'max_age' => null,
            'min_team_members' => 1,
            'max_team_members' => 1,
            'team_fields_enabled' => 0,
            'is_active' => true,
            'is_archived' => false,
            'scoring_enabled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('    ✓ Registration form config created');
    }

    // ═══════════════════════════════════════════════════════════════
    // 9. EMAIL TEMPLATES
    // ═══════════════════════════════════════════════════════════════
    private function seedEmailTemplates(): void
    {
        $this->command->info('  → Creating email templates...');

        $templates = [
            // Admin templates
            ['key' => 'admin.credentials', 'subject' => ['en' => 'Your Admin Account Credentials', 'ar' => 'بيانات اعتماد حسابك الإداري'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Your admin account has been created. Please use the following credentials to log in:</p><p>Email: {{email}}<br>Password: {{password}}</p><p>Please change your password after first login.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم إنشاء حسابك الإداري. يرجى استخدام البيانات التالية لتسجيل الدخول:</p><p>البريد الإلكتروني: {{email}}<br>كلمة المرور: {{password}}</p><p>يرجى تغيير كلمة المرور بعد أول تسجيل دخول.</p>']],
            ['key' => 'admin.otp', 'subject' => ['en' => 'Your OTP Code', 'ar' => 'رمز التحقق الخاص بك'], 'body' => ['en' => '<p>Dear Admin,</p><p>Your OTP code is: <strong>{{otp}}</strong></p><p>This code is valid for 10 minutes.</p>', 'ar' => '<p>عزيزي المسؤول،</p><p>رمز التحقق الخاص بك هو: <strong>{{otp}}</strong></p><p>هذا الرمز صالح لمدة 10 دقائق.</p>']],
            ['key' => 'admin.account_updated', 'subject' => ['en' => 'Your Account Has Been Updated', 'ar' => 'تم تحديث حسابك'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Your admin account has been updated. If you did not request this change, please contact your system administrator.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم تحديث حسابك الإداري. إذا لم تطلب هذا التغيير، يرجى الاتصال بمسؤول النظام.</p>']],

            // Judge templates
            ['key' => 'judge.signup_confirmation', 'subject' => ['en' => 'Welcome - Judge Account Created', 'ar' => 'مرحباً - تم إنشاء حساب المحكم'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Your judge account has been created for the Innovation Platform. Your credentials:</p><p>Email: {{email}}<br>Password: {{password}}</p><p>Please log in and review your assigned evaluations.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم إنشاء حسابك كمحكم في منصة الابتكار. بياناتك:</p><p>البريد الإلكتروني: {{email}}<br>كلمة المرور: {{password}}</p><p>يرجى تسجيل الدخول ومراجعة التقييمات المسندة إليك.</p>']],
            ['key' => 'judge.forgot_password', 'subject' => ['en' => 'Password Reset Request', 'ar' => 'طلب إعادة تعيين كلمة المرور'], 'body' => ['en' => '<p>Dear {{name}},</p><p>We received a password reset request. Click the link below to reset your password:</p><p><a href="{{reset_url}}">Reset Password</a></p><p>If you did not make this request, please ignore this email.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تلقينا طلب إعادة تعيين كلمة المرور. انقر على الرابط أدناه:</p><p><a href="{{reset_url}}">إعادة تعيين كلمة المرور</a></p><p>إذا لم تقم بهذا الطلب، يرجى تجاهل هذا البريد.</p>']],

            // Participant templates
            ['key' => 'user.registration_confirmation', 'subject' => ['en' => 'Registration Confirmed', 'ar' => 'تأكيد التسجيل'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Your registration on the Innovation Platform has been confirmed. You can now explore programs and submit applications.</p><p>Thank you for joining us!</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم تأكيد تسجيلك في منصة الابتكار. يمكنك الآن استكشاف البرامج وتقديم الطلبات.</p><p>شكراً لانضمامك إلينا!</p>']],
            ['key' => 'user.activation', 'subject' => ['en' => 'Activate Your Account', 'ar' => 'تفعيل حسابك'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Please activate your account by clicking the link below:</p><p><a href="{{activation_url}}">Activate Account</a></p>', 'ar' => '<p>عزيزي {{name}}،</p><p>يرجى تفعيل حسابك بالنقر على الرابط أدناه:</p><p><a href="{{activation_url}}">تفعيل الحساب</a></p>']],
            ['key' => 'user.otp', 'subject' => ['en' => 'Your Login OTP Code', 'ar' => 'رمز الدخول الخاص بك'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Your OTP code is: <strong>{{otp}}</strong></p><p>Valid for 10 minutes.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>رمز التحقق الخاص بك: <strong>{{otp}}</strong></p><p>صالح لمدة 10 دقائق.</p>']],
            ['key' => 'user.password_reset', 'subject' => ['en' => 'Password Reset', 'ar' => 'إعادة تعيين كلمة المرور'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Click the link below to reset your password:</p><p><a href="{{reset_url}}">Reset Password</a></p>', 'ar' => '<p>عزيزي {{name}}،</p><p>انقر على الرابط أدناه لإعادة تعيين كلمة المرور:</p><p><a href="{{reset_url}}">إعادة تعيين كلمة المرور</a></p>']],
            ['key' => 'user.recovery_email_otp', 'subject' => ['en' => 'Recovery Email Verification', 'ar' => 'التحقق من البريد الإلكتروني للاسترداد'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Your recovery email verification code is: <strong>{{otp}}</strong></p>', 'ar' => '<p>عزيزي {{name}}،</p><p>رمز التحقق من بريد الاسترداد: <strong>{{otp}}</strong></p>']],

            // Mentor templates
            ['key' => 'mentor.approved', 'subject' => ['en' => 'Mentor Application Approved', 'ar' => 'تمت الموافقة على طلب المرشد'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Your mentor application has been approved. You can now access the mentoring dashboard and start scheduling sessions.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تمت الموافقة على طلبك كمرشد. يمكنك الآن الوصول إلى لوحة الإرشاد وبدء جدولة الجلسات.</p>']],
            ['key' => 'mentor.rejected', 'subject' => ['en' => 'Mentor Application Update', 'ar' => 'تحديث طلب المرشد'], 'body' => ['en' => '<p>Dear {{name}},</p><p>We regret to inform you that your mentor application was not approved at this time. You may reapply in the future.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>يؤسفنا إبلاغك بأنه لم تتم الموافقة على طلبك كمرشد في هذا الوقت. يمكنك إعادة التقديم مستقبلاً.</p>']],
            ['key' => 'mentor.deactivated', 'subject' => ['en' => 'Mentor Account Deactivated', 'ar' => 'تم تعطيل حساب المرشد'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Your mentor account has been deactivated. Please contact the admin for more information.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم تعطيل حسابك كمرشد. يرجى التواصل مع المسؤول لمزيد من المعلومات.</p>']],
            ['key' => 'mentor.credentials', 'subject' => ['en' => 'Your Mentor Account Credentials', 'ar' => 'بيانات اعتماد حساب المرشد'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Your mentor account has been created. Credentials:</p><p>Email: {{email}}<br>Password: {{password}}</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم إنشاء حسابك كمرشد. بياناتك:</p><p>البريد الإلكتروني: {{email}}<br>كلمة المرور: {{password}}</p>']],
            ['key' => 'mentor.password_reset', 'subject' => ['en' => 'Mentor Password Reset', 'ar' => 'إعادة تعيين كلمة مرور المرشد'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Click below to reset your password:</p><p><a href="{{reset_url}}">Reset Password</a></p>', 'ar' => '<p>عزيزي {{name}}،</p><p>انقر أدناه لإعادة تعيين كلمة المرور:</p><p><a href="{{reset_url}}">إعادة تعيين كلمة المرور</a></p>']],
            ['key' => 'mentor.registration_pending', 'subject' => ['en' => 'Mentor Registration Received', 'ar' => 'تم استلام تسجيل المرشد'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Your mentor registration has been received and is under review. We will notify you once a decision is made.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم استلام تسجيلك كمرشد وهو قيد المراجعة. سنخطرك فور اتخاذ القرار.</p>']],

            // Application & Project templates
            ['key' => 'application.status_updated', 'subject' => ['en' => 'Application Status Updated', 'ar' => 'تم تحديث حالة الطلب'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Your application for <strong>{{program}}</strong> has been updated to: <strong>{{status}}</strong>.</p><p>Log in to view details.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم تحديث حالة طلبك لـ <strong>{{program}}</strong> إلى: <strong>{{status}}</strong>.</p><p>سجل الدخول لعرض التفاصيل.</p>']],
            ['key' => 'application.comment_added', 'subject' => ['en' => 'New Comment on Your Application', 'ar' => 'تعليق جديد على طلبك'], 'body' => ['en' => '<p>Dear {{name}},</p><p>A new comment has been added to your application. Log in to view and respond.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تمت إضافة تعليق جديد على طلبك. سجل الدخول للعرض والرد.</p>']],
            ['key' => 'program.registration', 'subject' => ['en' => 'Registration Confirmation', 'ar' => 'تأكيد التسجيل في البرنامج'], 'body' => ['en' => '<p>Dear {{name}},</p><p>You have successfully registered for <strong>{{program}}</strong>. Keep an eye on your dashboard for updates.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم تسجيلك بنجاح في <strong>{{program}}</strong>. تابع لوحة التحكم للحصول على التحديثات.</p>']],
            ['key' => 'project.submitted', 'subject' => ['en' => 'Project Submitted Successfully', 'ar' => 'تم تقديم المشروع بنجاح'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Your project <strong>{{project}}</strong> has been submitted for <strong>{{program}}</strong>.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم تقديم مشروعك <strong>{{project}}</strong> لـ <strong>{{program}}</strong>.</p>']],
            ['key' => 'project.status_updated', 'subject' => ['en' => 'Project Status Updated', 'ar' => 'تم تحديث حالة المشروع'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Your project status has been updated to: <strong>{{status}}</strong>.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم تحديث حالة مشروعك إلى: <strong>{{status}}</strong>.</p>']],
            ['key' => 'project.comment_added', 'subject' => ['en' => 'New Comment on Your Project', 'ar' => 'تعليق جديد على مشروعك'], 'body' => ['en' => '<p>Dear {{name}},</p><p>A new comment has been added to your project. Log in to view.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تمت إضافة تعليق جديد على مشروعك. سجل الدخول للعرض.</p>']],
            ['key' => 'project.evaluation_result', 'subject' => ['en' => 'Project Evaluation Results', 'ar' => 'نتائج تقييم المشروع'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Your project evaluation results are available. Log in to view your scores and feedback.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>نتائج تقييم مشروعك متاحة. سجل الدخول لعرض درجاتك وملاحظاتك.</p>']],

            // Session templates
            ['key' => 'session.scheduled', 'subject' => ['en' => 'Mentoring Session Scheduled', 'ar' => 'تم جدولة جلسة الإرشاد'], 'body' => ['en' => '<p>Dear {{name}},</p><p>A mentoring session has been scheduled:</p><p>Date: {{date}}<br>Time: {{time}}<br>Mentor: {{mentor}}</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم جدولة جلسة إرشاد:</p><p>التاريخ: {{date}}<br>الوقت: {{time}}<br>المرشد: {{mentor}}</p>']],
            ['key' => 'session.cancelled', 'subject' => ['en' => 'Session Cancelled', 'ar' => 'تم إلغاء الجلسة'], 'body' => ['en' => '<p>Dear {{name}},</p><p>The mentoring session scheduled for {{date}} has been cancelled.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم إلغاء جلسة الإرشاد المقررة في {{date}}.</p>']],
            ['key' => 'session.reminder', 'subject' => ['en' => 'Session Reminder', 'ar' => 'تذكير بالجلسة'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Reminder: You have a mentoring session tomorrow at {{time}}.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تذكير: لديك جلسة إرشاد غداً في الساعة {{time}}.</p>']],
            ['key' => 'session.rescheduled', 'subject' => ['en' => 'Session Rescheduled', 'ar' => 'تم إعادة جدولة الجلسة'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Your session has been rescheduled to {{date}} at {{time}}.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم إعادة جدولة جلستك إلى {{date}} في الساعة {{time}}.</p>']],
            ['key' => 'session.accepted', 'subject' => ['en' => 'Session Request Accepted', 'ar' => 'تم قبول طلب الجلسة'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Your session request has been accepted. Details: {{date}} at {{time}}.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم قبول طلب جلستك. التفاصيل: {{date}} في الساعة {{time}}.</p>']],
            ['key' => 'session.declined', 'subject' => ['en' => 'Session Request Declined', 'ar' => 'تم رفض طلب الجلسة'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Your session request has been declined. Please try another time slot.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم رفض طلب جلستك. يرجى محاولة موعد آخر.</p>']],
            ['key' => 'session.feedback', 'subject' => ['en' => 'Session Feedback Submitted', 'ar' => 'تم تقديم ملاحظات الجلسة'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Feedback has been submitted for your session on {{date}}. Log in to view.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم تقديم ملاحظات لجلستك في {{date}}. سجل الدخول للعرض.</p>']],
            ['key' => 'session.new_time_proposed', 'subject' => ['en' => 'New Time Proposed', 'ar' => 'اقتراح وقت جديد'], 'body' => ['en' => '<p>Dear {{name}},</p><p>A new time has been proposed for your session: {{date}} at {{time}}. Please accept or decline.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم اقتراح وقت جديد لجلستك: {{date}} في الساعة {{time}}. يرجى القبول أو الرفض.</p>']],

            // Approval & team templates
            ['key' => 'approval.status_changed', 'subject' => ['en' => 'Approval Request Status Changed', 'ar' => 'تم تغيير حالة طلب الموافقة'], 'body' => ['en' => '<p>Dear {{name}},</p><p>An approval request status has changed to: <strong>{{status}}</strong>.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم تغيير حالة طلب الموافقة إلى: <strong>{{status}}</strong>.</p>']],
            ['key' => 'approval.assigned', 'subject' => ['en' => 'New Approval Request Assigned', 'ar' => 'تم تعيين طلب موافقة جديد'], 'body' => ['en' => '<p>Dear {{name}},</p><p>A new approval request has been assigned to you. Please review and take action.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم تعيين طلب موافقة جديد لك. يرجى المراجعة واتخاذ الإجراء.</p>']],
            ['key' => 'team.member_added', 'subject' => ['en' => 'You Have Been Added to a Team', 'ar' => 'تمت إضافتك إلى فريق'], 'body' => ['en' => '<p>Dear {{name}},</p><p>You have been added as a team member for <strong>{{program}}</strong>. Log in to view your team.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تمت إضافتك كعضو في فريق لـ <strong>{{program}}</strong>. سجل الدخول لعرض فريقك.</p>']],
            ['key' => 'mentor.participant_assigned', 'subject' => ['en' => 'New Participant Assigned', 'ar' => 'تم تعيين مشارك جديد'], 'body' => ['en' => '<p>Dear {{name}},</p><p>A new participant has been assigned to you for mentoring.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم تعيين مشارك جديد لك للإرشاد.</p>']],
            ['key' => 'mentor.team_assigned', 'subject' => ['en' => 'New Team Assigned', 'ar' => 'تم تعيين فريق جديد'], 'body' => ['en' => '<p>Dear {{name}},</p><p>A new team has been assigned to you for mentoring.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم تعيين فريق جديد لك للإرشاد.</p>']],
            ['key' => 'participant.account_imported', 'subject' => ['en' => 'Your Account Has Been Imported', 'ar' => 'تم استيراد حسابك'], 'body' => ['en' => '<p>Dear {{name}},</p><p>Your participant account has been imported to the Innovation Platform. You can now log in.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم استيراد حسابك كمشارك إلى منصة الابتكار. يمكنك الآن تسجيل الدخول.</p>']],
            ['key' => 'application.reply', 'subject' => ['en' => 'Reply to Your Application', 'ar' => 'رد على طلبك'], 'body' => ['en' => '<p>Dear {{name}},</p><p>A reply has been posted on your application. Log in to view.</p>', 'ar' => '<p>عزيزي {{name}}،</p><p>تم نشر رد على طلبك. سجل الدخول للعرض.</p>']],
        ];

        // Delete existing and insert fresh
        DB::table('email_templates')->truncate();

        foreach ($templates as $template) {
            DB::table('email_templates')->insert([
                'key' => $template['key'],
                'subject' => json_encode($template['subject']),
                'body' => json_encode($template['body']),
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info("    ✓ " . count($templates) . " email templates created");
    }

    // ═══════════════════════════════════════════════════════════════
    // 10. NOTIFICATION MESSAGES (in-app notifications)
    // ═══════════════════════════════════════════════════════════════
    private function seedNotificationMessages(): void
    {
        $this->command->info('  → Creating notification messages...');

        $notifications = [
            // Participant notifications
            ['key' => 'participant.activation', 'subject' => ['en' => 'Account Activated', 'ar' => 'تم تفعيل الحساب'], 'body' => ['en' => 'Your account has been activated. Welcome to the Innovation Platform!', 'ar' => 'تم تفعيل حسابك. مرحباً بك في منصة الابتكار!'], 'type' => 'notification'],
            ['key' => 'participant.login_otp', 'subject' => ['en' => 'Login OTP', 'ar' => 'رمز تسجيل الدخول'], 'body' => ['en' => 'Your OTP code is: {{otp}}. Valid for 10 minutes.', 'ar' => 'رمز التحقق الخاص بك: {{otp}}. صالح لمدة 10 دقائق.'], 'type' => 'notification'],
            ['key' => 'participant.password_reset', 'subject' => ['en' => 'Password Reset', 'ar' => 'إعادة تعيين كلمة المرور'], 'body' => ['en' => 'A password reset was requested for your account.', 'ar' => 'تم طلب إعادة تعيين كلمة المرور لحسابك.'], 'type' => 'notification'],

            // Application notifications
            ['key' => 'application.approved', 'subject' => ['en' => 'Application Approved', 'ar' => 'تمت الموافقة على الطلب'], 'body' => ['en' => 'Your application for {{program}} has been approved.', 'ar' => 'تمت الموافقة على طلبك لـ {{program}}.'], 'type' => 'notification'],
            ['key' => 'application.rejected', 'subject' => ['en' => 'Application Not Approved', 'ar' => 'لم تتم الموافقة على الطلب'], 'body' => ['en' => 'Your application for {{program}} was not approved at this time.', 'ar' => 'لم تتم الموافقة على طلبك لـ {{program}} في هذا الوقت.'], 'type' => 'notification'],
            ['key' => 'application.under_review', 'subject' => ['en' => 'Application Under Review', 'ar' => 'الطلب قيد المراجعة'], 'body' => ['en' => 'Your application is being reviewed by our team.', 'ar' => 'طلبك قيد المراجعة من قبل فريقنا.'], 'type' => 'notification'],
            ['key' => 'application.comment', 'subject' => ['en' => 'New Comment on Application', 'ar' => 'تعليق جديد على الطلب'], 'body' => ['en' => 'A new comment was added to your application.', 'ar' => 'تمت إضافة تعليق جديد على طلبك.'], 'type' => 'notification'],

            // Program notifications
            ['key' => 'program.registered', 'subject' => ['en' => 'Successfully Registered', 'ar' => 'تم التسجيل بنجاح'], 'body' => ['en' => 'You have successfully registered for {{program}}.', 'ar' => 'تم تسجيلك بنجاح في {{program}}.'], 'type' => 'notification'],
            ['key' => 'program.stage_changed', 'subject' => ['en' => 'Program Stage Updated', 'ar' => 'تم تحديث مرحلة البرنامج'], 'body' => ['en' => 'The program {{program}} has moved to a new stage: {{stage}}.', 'ar' => 'انتقل البرنامج {{program}} إلى مرحلة جديدة: {{stage}}.'], 'type' => 'notification'],

            // Project notifications
            ['key' => 'project.submitted', 'subject' => ['en' => 'Project Submitted', 'ar' => 'تم تقديم المشروع'], 'body' => ['en' => 'Your project "{{project}}" has been submitted successfully.', 'ar' => 'تم تقديم مشروعك "{{project}}" بنجاح.'], 'type' => 'notification'],
            ['key' => 'project.status_changed', 'subject' => ['en' => 'Project Status Changed', 'ar' => 'تم تغيير حالة المشروع'], 'body' => ['en' => 'Your project status changed to: {{status}}.', 'ar' => 'تم تغيير حالة مشروعك إلى: {{status}}.'], 'type' => 'notification'],
            ['key' => 'project.comment', 'subject' => ['en' => 'New Project Comment', 'ar' => 'تعليق جديد على المشروع'], 'body' => ['en' => 'A new comment was added to your project.', 'ar' => 'تمت إضافة تعليق جديد على مشروعك.'], 'type' => 'notification'],
            ['key' => 'project.evaluated', 'subject' => ['en' => 'Project Evaluated', 'ar' => 'تم تقييم المشروع'], 'body' => ['en' => 'Your project has been evaluated. Check your results.', 'ar' => 'تم تقييم مشروعك. تحقق من نتائجك.'], 'type' => 'notification'],

            // Mentor notifications
            ['key' => 'mentor.approved', 'subject' => ['en' => 'Mentor Application Approved', 'ar' => 'تمت الموافقة على طلب المرشد'], 'body' => ['en' => 'Your mentor application has been approved.', 'ar' => 'تمت الموافقة على طلبك كمرشد.'], 'type' => 'notification'],
            ['key' => 'mentor.rejected', 'subject' => ['en' => 'Mentor Application Not Approved', 'ar' => 'لم تتم الموافقة على طلب المرشد'], 'body' => ['en' => 'Your mentor application was not approved.', 'ar' => 'لم تتم الموافقة على طلبك كمرشد.'], 'type' => 'notification'],
            ['key' => 'mentor.deactivated', 'subject' => ['en' => 'Mentor Account Deactivated', 'ar' => 'تم تعطيل حساب المرشد'], 'body' => ['en' => 'Your mentor account has been deactivated.', 'ar' => 'تم تعطيل حسابك كمرشد.'], 'type' => 'notification'],
            ['key' => 'mentor.participant_assigned', 'subject' => ['en' => 'Participant Assigned', 'ar' => 'تم تعيين مشارك'], 'body' => ['en' => 'A new participant has been assigned to you for mentoring.', 'ar' => 'تم تعيين مشارك جديد لك للإرشاد.'], 'type' => 'notification'],
            ['key' => 'mentor.team_assigned', 'subject' => ['en' => 'Team Assigned', 'ar' => 'تم تعيين فريق'], 'body' => ['en' => 'A new team has been assigned to you for mentoring.', 'ar' => 'تم تعيين فريق جديد لك للإرشاد.'], 'type' => 'notification'],
            ['key' => 'mentor.new_booking', 'subject' => ['en' => 'New Session Booking', 'ar' => 'حجز جلسة جديدة'], 'body' => ['en' => 'A new mentoring session has been booked with you.', 'ar' => 'تم حجز جلسة إرشاد جديدة معك.'], 'type' => 'notification'],

            // Session notifications
            ['key' => 'session.scheduled', 'subject' => ['en' => 'Session Scheduled', 'ar' => 'تم جدولة الجلسة'], 'body' => ['en' => 'Your mentoring session is scheduled for {{date}} at {{time}}.', 'ar' => 'تم جدولة جلسة الإرشاد في {{date}} الساعة {{time}}.'], 'type' => 'notification'],
            ['key' => 'session.cancelled', 'subject' => ['en' => 'Session Cancelled', 'ar' => 'تم إلغاء الجلسة'], 'body' => ['en' => 'Your mentoring session on {{date}} has been cancelled.', 'ar' => 'تم إلغاء جلسة الإرشاد في {{date}}.'], 'type' => 'notification'],
            ['key' => 'session.reminder', 'subject' => ['en' => 'Session Reminder', 'ar' => 'تذكير بالجلسة'], 'body' => ['en' => 'Reminder: You have a session tomorrow at {{time}}.', 'ar' => 'تذكير: لديك جلسة غداً الساعة {{time}}.'], 'type' => 'notification'],
            ['key' => 'session.updated', 'subject' => ['en' => 'Session Updated', 'ar' => 'تم تحديث الجلسة'], 'body' => ['en' => 'Your mentoring session details have been updated.', 'ar' => 'تم تحديث تفاصيل جلسة الإرشاد.'], 'type' => 'notification'],
            ['key' => 'session.accepted', 'subject' => ['en' => 'Session Accepted', 'ar' => 'تم قبول الجلسة'], 'body' => ['en' => 'Your session request has been accepted.', 'ar' => 'تم قبول طلب جلستك.'], 'type' => 'notification'],
            ['key' => 'session.declined', 'subject' => ['en' => 'Session Declined', 'ar' => 'تم رفض الجلسة'], 'body' => ['en' => 'Your session request has been declined.', 'ar' => 'تم رفض طلب جلستك.'], 'type' => 'notification'],
            ['key' => 'session.feedback', 'subject' => ['en' => 'Feedback Submitted', 'ar' => 'تم تقديم الملاحظات'], 'body' => ['en' => 'Feedback for your session has been submitted.', 'ar' => 'تم تقديم ملاحظات جلستك.'], 'type' => 'notification'],
            ['key' => 'session.new_time', 'subject' => ['en' => 'New Time Proposed', 'ar' => 'وقت جديد مقترح'], 'body' => ['en' => 'A new time has been proposed for your session.', 'ar' => 'تم اقتراح وقت جديد لجلستك.'], 'type' => 'notification'],

            // Admin notifications
            ['key' => 'admin.new_application', 'subject' => ['en' => 'New Application Received', 'ar' => 'طلب جديد مستلم'], 'body' => ['en' => 'A new application has been submitted for {{program}}.', 'ar' => 'تم تقديم طلب جديد لـ {{program}}.'], 'type' => 'notification'],
            ['key' => 'admin.new_project', 'subject' => ['en' => 'New Project Submitted', 'ar' => 'مشروع جديد مقدم'], 'body' => ['en' => 'A new project has been submitted for {{program}}.', 'ar' => 'تم تقديم مشروع جديد لـ {{program}}.'], 'type' => 'notification'],
            ['key' => 'admin.comment_added', 'subject' => ['en' => 'New Admin Comment', 'ar' => 'تعليق إداري جديد'], 'body' => ['en' => 'A comment was added on an application you manage.', 'ar' => 'تمت إضافة تعليق على طلب تديره.'], 'type' => 'notification'],

            // Approval notifications
            ['key' => 'approval.request_created', 'subject' => ['en' => 'Approval Request Created', 'ar' => 'تم إنشاء طلب موافقة'], 'body' => ['en' => 'A new approval request has been created and needs your review.', 'ar' => 'تم إنشاء طلب موافقة جديد يحتاج مراجعتك.'], 'type' => 'notification'],
            ['key' => 'approval.status_changed', 'subject' => ['en' => 'Approval Status Changed', 'ar' => 'تم تغيير حالة الموافقة'], 'body' => ['en' => 'An approval request status changed to: {{status}}.', 'ar' => 'تم تغيير حالة طلب الموافقة إلى: {{status}}.'], 'type' => 'notification'],

            // Team notifications
            ['key' => 'team.member_added', 'subject' => ['en' => 'Added to Team', 'ar' => 'تمت الإضافة للفريق'], 'body' => ['en' => 'You have been added as a team member.', 'ar' => 'تمت إضافتك كعضو في الفريق.'], 'type' => 'notification'],
            ['key' => 'participant.account_imported', 'subject' => ['en' => 'Account Imported', 'ar' => 'تم استيراد الحساب'], 'body' => ['en' => 'Your participant account has been imported.', 'ar' => 'تم استيراد حسابك كمشارك.'], 'type' => 'notification'],
        ];

        // Delete existing and insert fresh
        DB::table('notification_messages')->truncate();

        foreach ($notifications as $notification) {
            DB::table('notification_messages')->insert([
                'key' => $notification['key'],
                'subject' => json_encode($notification['subject']),
                'body' => json_encode($notification['body']),
                'type' => $notification['type'],
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info("    ✓ " . count($notifications) . " notification messages created");
    }

    // ═══════════════════════════════════════════════════════════════
    // 11. PERMISSIONS
    // ═══════════════════════════════════════════════════════════════
    private function seedPermissions(): void
    {
        $this->command->info('  → Creating permissions...');

        // Standard CRUD resources (view, create, update, delete)
        $crudResources = [
            'Program', 'ProgramApplication', 'Form', 'FormField',
            'Stage', 'Track', 'SubTrack', 'Team', 'Project',
            'ProjectEvaluation', 'Participant', 'Judge', 'Mentor',
            'MentorSession', 'Event', 'Guideline', 'Service',
            'Page', 'LandingPage', 'ContactUs', 'Satisfaction',
            'EmailTemplate', 'NotificationMessage', 'NotificationManagement',
            'Committee', 'Winner', 'BrandingProgram',
            'RegistrationFormConfig', 'ProjectFormConfig', 'TeamFormConfig',
            'EvaluationStageConfig', 'FormAiScoringConfig',
            'ActivityLog', 'User', 'Role', 'Permission',
        ];

        $crudActions = ['view', 'create', 'update', 'delete'];

        // Archive/Restore resources
        $archiveResources = [
            'Program', 'ProgramApplication', 'Form',
            'ContactUs', 'ProjectEvaluation', 'Event',
            'Guideline', 'Judge', 'Mentor', 'Participant',
            'Project', 'ProjectFormConfig', 'RegistrationFormConfig',
            'Team', 'TeamFormConfig', 'Admin',
        ];

        // Special permissions
        $specialPermissions = [
            // Approval workflow
            'view ApprovalRequest', 'update ApprovalRequest', 'delete ApprovalRequest',
            'approve ApprovalRequest', 'reject ApprovalRequest',
            'view ApprovalWorkflow', 'create ApprovalWorkflow', 'update ApprovalWorkflow', 'delete ApprovalWorkflow',

            // Mentor tools
            'view MentorVideoTool', 'update MentorVideoTool', 'delete MentorVideoTool',

            // Integration config
            'configure Integrations',

            // Program participant management
            'view ProgramParticipant', 'create ProgramParticipant', 'update ProgramParticipant', 'delete ProgramParticipant',

            // Registration & project steps
            'view RegistrationStep', 'create RegistrationStep', 'update RegistrationStep', 'delete RegistrationStep',
            'view ProjectStep', 'create ProjectStep', 'update ProjectStep', 'delete ProjectStep',

            // Form AI hints
            'view FormAiHints', 'create FormAiHints', 'update FormAiHints', 'delete FormAiHints',

            // Judge contact us
            'view JudgeContactUs', 'update JudgeContactUs', 'delete JudgeContactUs',

            // My requests (for supervisors)
            'view MyRequests',

            // Dashboard access
            'view Dashboard',
            'view Analytics',

            // Export data
            'export Data',

            // Manage branding
            'manage Branding',
        ];

        $allPermissions = [];

        // Generate CRUD permissions
        foreach ($crudResources as $resource) {
            foreach ($crudActions as $action) {
                $allPermissions[] = "{$action} {$resource}";
            }
        }

        // Generate archive/restore permissions
        foreach ($archiveResources as $resource) {
            $allPermissions[] = "archive {$resource}";
            $allPermissions[] = "restore {$resource}";
        }

        // Add special permissions
        $allPermissions = array_merge($allPermissions, $specialPermissions);

        // Remove duplicates
        $allPermissions = array_unique($allPermissions);

        // Insert permissions
        $guardName = 'web';
        $inserted = 0;
        foreach ($allPermissions as $permission) {
            $exists = DB::table('permissions')->where('name', $permission)->where('guard_name', $guardName)->exists();
            if (!$exists) {
                DB::table('permissions')->insert([
                    'name' => $permission,
                    'guard_name' => $guardName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $inserted++;
            }
        }

        // Assign all permissions to super-admin role
        $superAdminRole = DB::table('roles')->where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $allPermissionIds = DB::table('permissions')->where('guard_name', $guardName)->pluck('id');
            foreach ($allPermissionIds as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('permission_id', $permissionId)
                    ->where('role_id', $superAdminRole->id)
                    ->exists();
                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permissionId,
                        'role_id' => $superAdminRole->id,
                    ]);
                }
            }
            $this->command->info("    ✓ All permissions assigned to super-admin role");
        }

        // Assign view-only permissions to supervisor role
        $supervisorRole = DB::table('roles')->where('name', 'supervisor')->first();
        if ($supervisorRole) {
            $viewPermissions = DB::table('permissions')
                ->where('guard_name', $guardName)
                ->where('name', 'like', 'view %')
                ->pluck('id');
            foreach ($viewPermissions as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('permission_id', $permissionId)
                    ->where('role_id', $supervisorRole->id)
                    ->exists();
                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permissionId,
                        'role_id' => $supervisorRole->id,
                    ]);
                }
            }
            $this->command->info("    ✓ View permissions assigned to supervisor role");
        }

        // Assign full CRUD to admin role
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        if ($adminRole) {
            $adminPermissions = DB::table('permissions')
                ->where('guard_name', $guardName)
                ->whereIn('name', function ($query) {
                    $query->select('name')->from('permissions')
                        ->where('name', 'like', 'view %')
                        ->orWhere('name', 'like', 'create %')
                        ->orWhere('name', 'like', 'update %')
                        ->orWhere('name', 'like', 'delete %');
                })
                ->pluck('id');
            foreach ($adminPermissions as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('permission_id', $permissionId)
                    ->where('role_id', $adminRole->id)
                    ->exists();
                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'permission_id' => $permissionId,
                        'role_id' => $adminRole->id,
                    ]);
                }
            }
            $this->command->info("    ✓ CRUD permissions assigned to admin role");
        }

        $this->command->info("    ✓ {$inserted} new permissions created (" . count($allPermissions) . " total)");
    }
}
