<?php return [
    'mvp_feature_priority' => [
        'en' => [
            'component_type' => 'comparison_table',
            'data' => [
                'headers' => ['Feature', 'Priority', 'Implementation', 'Impact'],
                'rows' => [
                    ['Transaction Screening', 'Critical', 'Week 1-2', 'High'],
                    ['Regulatory Reporting', 'Critical', 'Week 2-3', 'High'],
                    ['AML Risk Scoring', 'High', 'Week 3-4', 'High'],
                    ['Compliance Dashboard', 'High', 'Week 4-5', 'Medium'],
                    ['Audit Trail System', 'Medium', 'Week 5-6', 'Medium'],
                    ['Integration APIs', 'High', 'Week 6-8', 'High']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'comparison_table',
            'data' => [
                'headers' => ['الميزة', 'الأولوية', 'التنفيذ', 'التأثير'],
                'rows' => [
                    ['فحص المعاملات', 'حرج', 'الأسبوع 1-2', 'عالي'],
                    ['التقارير التنظيمية', 'حرج', 'الأسبوع 2-3', 'عالي'],
                    ['تسجيل مخاطر AML', 'عالي', 'الأسبوع 3-4', 'عالي'],
                    ['لوحة التحكم الامتثال', 'عالي', 'الأسبوع 4-5', 'متوسط'],
                    ['نظام دقيق للتدقيق', 'متوسط', 'الأسبوع 5-6', 'متوسط'],
                    ['واجهات برمجية التكامل', 'عالي', 'الأسبوع 6-8', 'عالي']
                ]
            ]
        ]
    ],

    'mvp_development_roadmap' => [
        'en' => [
            'component_type' => 'journey_timeline',
            'data' => [
                'stages' => [
                    [
                        'title' => 'Phase 1: Foundation',
                        'description' => 'Core infrastructure and transaction screening engine',
                        'touchpoints' => ['Database Setup', 'API Framework', 'Screening Rules'],
                        'actions' => ['Deploy infrastructure', 'Build rule engine', 'Integration testing']
                    ],
                    [
                        'title' => 'Phase 2: Compliance Engine',
                        'description' => 'Regulatory reporting and AML risk assessment',
                        'touchpoints' => ['Risk Scoring', 'Report Generation', 'Audit Logging'],
                        'actions' => ['Implement algorithms', 'Build report templates', 'Enable auditing']
                    ],
                    [
                        'title' => 'Phase 3: User Interface',
                        'description' => 'Dashboard and user management system',
                        'touchpoints' => ['Admin Dashboard', 'User Interface', 'Mobile Support'],
                        'actions' => ['Design UI/UX', 'Develop frontend', 'Mobile optimization']
                    ],
                    [
                        'title' => 'Phase 4: Launch',
                        'description' => 'Beta testing and market launch',
                        'touchpoints' => ['QA Testing', 'Customer Training', 'Go Live'],
                        'actions' => ['Execute testing', 'Train users', 'Launch product']
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'journey_timeline',
            'data' => [
                'stages' => [
                    [
                        'title' => 'المرحلة 1: الأساس',
                        'description' => 'البنية الأساسية ومحرك فحص المعاملات',
                        'touchpoints' => ['إعداد قاعدة البيانات', 'إطار العمل API', 'قواعد الفحص'],
                        'actions' => ['نشر البنية الأساسية', 'بناء محرك القواعد', 'اختبار التكامل']
                    ],
                    [
                        'title' => 'المرحلة 2: محرك الامتثال',
                        'description' => 'التقارير التنظيمية وتقييم مخاطر AML',
                        'touchpoints' => ['تسجيل المخاطر', 'إنشاء التقارير', 'تسجيل التدقيق'],
                        'actions' => ['تنفيذ الخوارزميات', 'بناء قوالب التقرير', 'تفعيل التدقيق']
                    ],
                    [
                        'title' => 'المرحلة 3: واجهة المستخدم',
                        'description' => 'لوحة التحكم ونظام إدارة المستخدمين',
                        'touchpoints' => ['لوحة التحكم', 'الواجهة', 'الدعم المحمول'],
                        'actions' => ['تصميم الواجهة', 'تطوير الواجهة الأمامية', 'تحسين الهاتف المحمول']
                    ],
                    [
                        'title' => 'المرحلة 4: الإطلاق',
                        'description' => 'الاختبار التجريبي وإطلاق السوق',
                        'touchpoints' => ['اختبار الجودة', 'تدريب العملاء', 'البث المباشر'],
                        'actions' => ['تنفيذ الاختبار', 'تدريب المستخدمين', 'إطلاق المنتج']
                    ]
                ]
            ]
        ]
    ],

    'mvp_tech_stack' => [
        'en' => [
            'component_type' => 'key_value',
            'data' => [
                'items' => [
                    ['key' => 'Backend', 'value' => 'PHP 8.2 + Laravel 11'],
                    ['key' => 'Database', 'value' => 'PostgreSQL with Redis cache'],
                    ['key' => 'Frontend', 'value' => 'React 18 + TypeScript'],
                    ['key' => 'Mobile', 'value' => 'React Native'],
                    ['key' => 'Deployment', 'value' => 'Docker + AWS ECS'],
                    ['key' => 'CI/CD', 'value' => 'GitHub Actions'],
                    ['key' => 'Monitoring', 'value' => 'DataDog + PagerDuty'],
                    ['key' => 'Security', 'value' => 'TLS 1.3, AES-256 encryption']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'key_value',
            'data' => [
                'items' => [
                    ['key' => 'الخادم الخلفي', 'value' => 'PHP 8.2 + Laravel 11'],
                    ['key' => 'قاعدة البيانات', 'value' => 'PostgreSQL مع ذاكرة تخزين Redis'],
                    ['key' => 'الواجهة الأمامية', 'value' => 'React 18 + TypeScript'],
                    ['key' => 'المحمول', 'value' => 'React Native'],
                    ['key' => 'النشر', 'value' => 'Docker + AWS ECS'],
                    ['key' => 'التكامل المستمر', 'value' => 'GitHub Actions'],
                    ['key' => 'المراقبة', 'value' => 'DataDog + PagerDuty'],
                    ['key' => 'الأمان', 'value' => 'TLS 1.3, تشفير AES-256']
                ]
            ]
        ]
    ],

    'mvp_resource_requirements' => [
        'en' => [
            'component_type' => 'stat_cards',
            'data' => [
                'metrics' => [
                    ['label' => 'Development Team', 'value' => '8', 'description' => 'Engineers and architects'],
                    ['label' => 'Timeline', 'value' => '8 weeks', 'description' => 'To MVP launch'],
                    ['label' => 'Budget', 'value' => '$280K', 'description' => 'Development costs'],
                    ['label' => 'Infrastructure', 'value' => '$15K/mo', 'description' => 'Cloud and hosting']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'stat_cards',
            'data' => [
                'metrics' => [
                    ['label' => 'فريق التطوير', 'value' => '8', 'description' => 'المهندسون والمعماريون'],
                    ['label' => 'الجدول الزمني', 'value' => '8 أسابيع', 'description' => 'حتى إطلاق MVP'],
                    ['label' => 'الميزانية', 'value' => '$280K', 'description' => 'تكاليف التطوير'],
                    ['label' => 'البنية الأساسية', 'value' => '$15K/mo', 'description' => 'السحابة والاستضافة']
                ]
            ]
        ]
    ],

    'mvp_risk_mitigation' => [
        'en' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'MVP Risk Mitigation Strategy',
                'sections' => [
                    [
                        'heading' => 'Technical Risks',
                        'content' => 'Implement redundant systems for transaction screening to ensure 99.99% uptime. Use proven technologies (Laravel, PostgreSQL) with extensive documentation. Establish comprehensive testing protocols including load testing, security audits, and penetration testing before launch.'
                    ],
                    [
                        'heading' => 'Regulatory Risks',
                        'content' => 'Partner with compliance consultants to ensure adherence to FATF and GCC regulatory requirements. Conduct regular compliance audits. Maintain detailed audit trails for all system activities. Document all compliance procedures and maintain regulatory certifications.'
                    ],
                    [
                        'heading' => 'Market Risks',
                        'content' => 'Validate product-market fit with pilot customers from major Saudi and UAE banks. Establish early feedback mechanisms. Build flexible pricing model to adapt to market demands. Plan for rapid iterations based on customer feedback.'
                    ],
                    [
                        'heading' => 'Operational Risks',
                        'content' => 'Hire experienced RegTech professionals with AML compliance background. Implement robust incident response procedures. Establish 24/7 customer support infrastructure. Create comprehensive documentation and training materials.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'استراتيجية تخفيف مخاطر MVP',
                'sections' => [
                    [
                        'heading' => 'المخاطر التقنية',
                        'content' => 'تنفيذ أنظمة زائدة لفحص المعاملات لضمان وقت التشغيل 99.99%. استخدام تقنيات مثبتة (Laravel، PostgreSQL) مع توثيق شامل. إنشاء بروتوكولات اختبار شاملة تشمل اختبار الحمل والتدقيق الأمني واختبار الاختراق قبل الإطلاق.'
                    ],
                    [
                        'heading' => 'المخاطر التنظيمية',
                        'content' => 'الشراكة مع مستشاري الامتثال لضمان الامتثال لمتطلبات FATF والمتطلبات التنظيمية لمجلس التعاون الخليجي. إجراء تدقيق امتثال منتظم. الحفاظ على سجلات تدقيق مفصلة لجميع أنشطة النظام. توثيق جميع إجراءات الامتثال والحفاظ على شهادات تنظيمية.'
                    ],
                    [
                        'heading' => 'مخاطر السوق',
                        'content' => 'التحقق من توافق المنتج مع السوق مع العملاء التجريبيين من البنوك الرئيسية في السعودية والإمارات. إنشاء آليات تغذية راجعة مبكرة. بناء نموذج تسعير مرن للتكيف مع متطلبات السوق. التخطيط للتكرارات السريعة بناءً على ملاحظات العملاء.'
                    ],
                    [
                        'heading' => 'مخاطر التشغيل',
                        'content' => 'توظيف محترفي RegTech ذوي خبرة في امتثال AML. تنفيذ إجراءات استجابة حوادث قوية. إنشاء بنية دعم العملاء على مدار الساعة طوال أيام الأسبوع. إنشاء توثيق شامل ومواد تدريبية.'
                    ]
                ]
            ]
        ]
    ],

    'usp_unique_selling_points' => [
        'en' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'Unique Selling Points',
                'sections' => [
                    [
                        'heading' => 'GCC-Specific Compliance',
                        'content' => 'Purpose-built for Saudi Arabia and GCC financial regulations, not a generic global solution. Integrated with SAMA, CBU, and local regulatory requirements. Supports Arabic documentation natively with right-to-left formatting.'
                    ],
                    [
                        'heading' => 'AI-Powered Risk Assessment',
                        'content' => 'Machine learning algorithms trained on regional transaction patterns. Real-time anomaly detection with 94% accuracy. Adaptive rules that learn from customer behavior and market conditions.'
                    ],
                    [
                        'heading' => 'Rapid Implementation',
                        'content' => 'Average deployment time of 2-3 weeks. Pre-configured rule sets for Saudi and GCC requirements. Plug-and-play integration with major regional banking platforms.'
                    ],
                    [
                        'heading' => 'Cost Efficiency',
                        'content' => '70% lower operational costs compared to legacy systems. No upfront infrastructure investment required. Pay-as-you-grow pricing model with flexible scaling.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'نقاط البيع الفريدة',
                'sections' => [
                    [
                        'heading' => 'الامتثال الخاص بمجلس التعاون الخليجي',
                        'content' => 'مصمم خصيصاً لتنظيمات السعودية والخليج المالية، وليس حلاً عاماً عالمياً. متكامل مع SAMA وCBU والمتطلبات التنظيمية المحلية. يدعم التوثيق العربي بشكل أصلي مع تنسيق من اليمين إلى اليسار.'
                    ],
                    [
                        'heading' => 'تقييم المخاطر المدعوم بالذكاء الاصطناعي',
                        'content' => 'خوارزميات التعلم الآلي المدربة على أنماط المعاملات الإقليمية. الكشف عن الحالات الشاذة في الوقت الفعلي بدقة 94%. قواعد تكيفية تتعلم من سلوك العملاء وظروف السوق.'
                    ],
                    [
                        'heading' => 'التنفيذ السريع',
                        'content' => 'وقت النشر الإجمالي من 2-3 أسابيع. مجموعات قواعد مسبقة التكوين لمتطلبات السعودية والخليج. التكامل السلس مع منصات البنوك الإقليمية الرئيسية.'
                    ],
                    [
                        'heading' => 'كفاءة التكاليف',
                        'content' => 'تكاليف تشغيلية أقل بنسبة 70٪ مقارنة بالأنظمة القديمة. لا توجد استثمارات بنية أساسية أولية مطلوبة. نموذج التسعير المرن مع التوسع المرن.'
                    ]
                ]
            ]
        ]
    ],

    'usp_competitive_advantage' => [
        'en' => [
            'component_type' => 'comparison_table',
            'data' => [
                'headers' => ['Dimension', 'SALIS', 'Competitors', 'Advantage'],
                'rows' => [
                    ['Regional Expertise', 'GCC-native', 'Global', 'Deep market knowledge'],
                    ['Implementation Speed', '2-3 weeks', '8-12 weeks', '4x faster'],
                    ['AI Accuracy', '94%', '85-88%', '+6% higher'],
                    ['Cost', '$15K/mo', '$35K+/mo', '57% lower'],
                    ['Arabic Support', 'Native RTL', 'Add-on only', 'Fully integrated'],
                    ['Regulatory Updates', 'Real-time', 'Quarterly', 'Always compliant']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'comparison_table',
            'data' => [
                'headers' => ['الجانب', 'SALIS', 'المنافسون', 'الميزة'],
                'rows' => [
                    ['الخبرة الإقليمية', 'أصلي من الخليج', 'عالمي', 'معرفة عميقة بالسوق'],
                    ['سرعة التنفيذ', '2-3 أسابيع', '8-12 أسبوع', 'أسرع 4 مرات'],
                    ['دقة الذكاء الاصطناعي', '94%', '85-88%', '+6% أعلى'],
                    ['التكلفة', '$15K/mo', '$35K+/mo', 'أقل بـ 57%'],
                    ['دعم اللغة العربية', 'RTL أصلي', 'إضافة فقط', 'متكامل بالكامل'],
                    ['التحديثات التنظيمية', 'الوقت الفعلي', 'ربع سنوي', 'دائماً متوافق']
                ]
            ]
        ]
    ],

    'usp_differentiation_strategy' => [
        'en' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'Market Differentiation Strategy',
                'sections' => [
                    [
                        'heading' => 'Localization Excellence',
                        'content' => 'SALIS is not a translated product but a natively built solution for GCC markets. Every feature, from regulatory rules to customer support, is designed with regional expertise. We understand the nuances of SAMA regulations, CBU guidelines, and local banking practices.'
                    ],
                    [
                        'heading' => 'Vertical Integration',
                        'content' => 'We combine AML screening, risk assessment, regulatory reporting, and audit compliance in one integrated platform. Eliminates data silos and reduces integration complexity. Customers get a single source of truth for compliance.'
                    ],
                    [
                        'heading' => 'Innovation at Scale',
                        'content' => 'Continuous AI model improvements based on regional data patterns. Monthly feature updates and regulatory enhancements. Proactive compliance notifications before new regulations take effect.'
                    ],
                    [
                        'heading' => 'Customer Success Focus',
                        'content' => 'Dedicated implementation teams for each customer. Regular compliance training and updates. White-glove support for regulatory interactions and audit preparation.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'استراتيجية التمايز في السوق',
                'sections' => [
                    [
                        'heading' => 'التميز في التوطين',
                        'content' => 'SALIS ليست منتجاً مترجماً بل حلاً مبنياً بشكل أصلي لأسواق مجلس التعاون الخليجي. كل ميزة، من القواعس التنظيمية إلى دعم العملاء، مصممة بخبرة إقليمية. نحن نفهم الفروقات الدقيقة في لوائح SAMA وإرشادات CBU والممارسات المصرفية المحلية.'
                    ],
                    [
                        'heading' => 'التكامل الرأسي',
                        'content' => 'نجمع بين فحص AML وتقييم المخاطر والإبلاغ التنظيمي والامتثال التدقيقي في منصة واحدة متكاملة. يلغي صوامع البيانات ويقلل من تعقيد التكامل. يحصل العملاء على مصدر موحد للحقيقة من أجل الامتثال.'
                    ],
                    [
                        'heading' => 'الابتكار على النطاق الواسع',
                        'content' => 'تحسينات نموذج الذكاء الاصطناعي المستمرة بناءً على أنماط البيانات الإقليمية. التحديثات الميزات الشهرية والتحسينات التنظيمية. إخطارات الامتثال الاستباقية قبل دخول اللوائح الجديدة حيز التنفيذ.'
                    ],
                    [
                        'heading' => 'التركيز على نجاح العملاء',
                        'content' => 'فرق التنفيذ المكرسة لكل عميل. تدريب الامتثال المنتظم والتحديثات. دعم الخدمة الكاملة للتفاعلات التنظيمية وإعداد التدقيق.'
                    ]
                ]
            ]
        ]
    ],

    'usp_value_chain' => [
        'en' => [
            'component_type' => 'key_value',
            'data' => [
                'items' => [
                    ['key' => 'Product Development', 'value' => 'In-house R&D with regional expertise'],
                    ['key' => 'Data Intelligence', 'value' => 'Proprietary AML datasets and ML models'],
                    ['key' => 'Integration', 'value' => 'Direct partnerships with regional banks'],
                    ['key' => 'Compliance Support', 'value' => 'Expert consultants and legal advisors'],
                    ['key' => 'Customer Success', 'value' => 'Dedicated implementation and support teams'],
                    ['key' => 'Infrastructure', 'value' => 'ISO 27001 certified secure cloud'],
                    ['key' => 'Regulatory Relations', 'value' => 'Direct relationships with SAMA, CBU'],
                    ['key' => 'Distribution', 'value' => 'Direct sales and channel partnerships']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'key_value',
            'data' => [
                'items' => [
                    ['key' => 'تطوير المنتج', 'value' => 'البحث والتطوير الداخلي مع الخبرة الإقليمية'],
                    ['key' => 'ذكاء البيانات', 'value' => 'مجموعات بيانات AML ملكية ونماذج ML'],
                    ['key' => 'التكامل', 'value' => 'الشراكات المباشرة مع البنوك الإقليمية'],
                    ['key' => 'دعم الامتثال', 'value' => 'مستشارون خبراء ومستشارون قانونيون'],
                    ['key' => 'نجاح العملاء', 'value' => 'فرق التنفيذ والدعم المكرسة'],
                    ['key' => 'البنية الأساسية', 'value' => 'سحابة آمنة معتمدة ISO 27001'],
                    ['key' => 'العلاقات التنظيمية', 'value' => 'العلاقات المباشرة مع SAMA و CBU'],
                    ['key' => 'التوزيع', 'value' => 'المبيعات المباشرة والشراكات الموزعة']
                ]
            ]
        ]
    ],

    'cp_primary_persona' => [
        'en' => [
            'component_type' => 'persona_card',
            'data' => [
                'name' => 'Fatima Al-Mansouri',
                'role' => 'Chief Compliance Officer',
                'age' => 48,
                'location' => 'Riyadh, Saudi Arabia',
                'quote' => 'We need a compliance system that understands our market, not something translated from elsewhere.',
                'demographics' => [
                    'experience' => '18 years in compliance',
                    'institution' => 'Major Saudi bank',
                    'education' => 'MBA Finance, CFA charterholder',
                    'annual_budget' => '$2.5M compliance'
                ],
                'pain_points' => [
                    'Manual compliance processes consume 40% of team time',
                    'Regulatory changes require constant system updates',
                    'Existing systems lack Arabic language support',
                    'High false positive rates in transaction screening',
                    'Fragmented tools across different compliance functions'
                ],
                'goals' => [
                    'Reduce compliance team operational workload by 50%',
                    'Achieve zero regulatory violations',
                    'Improve audit readiness and documentation',
                    'Implement AI-driven risk assessment',
                    'Streamline reporting to SAMA and regulators'
                ],
                'motivations' => [
                    'Protecting bank reputation and avoiding penalties',
                    'Meeting FATF and GCC regulatory requirements',
                    'Reducing operational expenses',
                    'Gaining competitive advantage through efficiency',
                    'Advancing career through digital transformation'
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'persona_card',
            'data' => [
                'name' => 'فاطمة المنصوري',
                'role' => 'رئيسة الامتثال',
                'age' => 48,
                'location' => 'الرياض، المملكة العربية السعودية',
                'quote' => 'نحتاج إلى نظام امتثال يفهم سوقنا، وليس شيء مترجم من مكان آخر.',
                'demographics' => [
                    'experience' => '18 سنة في الامتثال',
                    'institution' => 'بنك سعودي رئيسي',
                    'education' => 'ماجستير في المالية، حامل CFA',
                    'annual_budget' => '$2.5M امتثال'
                ],
                'pain_points' => [
                    'العمليات اليدوية للامتثال تستهلك 40٪ من وقت الفريق',
                    'التغييرات التنظيمية تتطلب تحديثات نظام مستمرة',
                    'الأنظمة الحالية تفتقر إلى دعم اللغة العربية',
                    'معدلات إيجابية كاذبة عالية في فحص المعاملات',
                    'أدوات مجزأة عبر وظائف الامتثال المختلفة'
                ],
                'goals' => [
                    'تقليل عبء عمل فريق الامتثال التشغيلي بنسبة 50٪',
                    'تحقيق انتهاكات تنظيمية صفر',
                    'تحسين جاهزية التدقيق والتوثيق',
                    'تنفيذ تقييم المخاطر المدعوم بالذكاء الاصطناعي',
                    'تبسيط الإبلاغ إلى SAMA والمنظمين'
                ],
                'motivations' => [
                    'حماية سمعة البنك وتجنب العقوبات',
                    'تلبية متطلبات FATF والمتطلبات التنظيمية لمجلس التعاون الخليجي',
                    'تقليل النفقات التشغيلية',
                    'اكتساب ميزة تنافسية من خلال الكفاءة',
                    'تقدم المهنية من خلال التحول الرقمي'
                ]
            ]
        ]
    ],

    'cp_secondary_persona' => [
        'en' => [
            'component_type' => 'persona_card',
            'data' => [
                'name' => 'Mohammed Al-Otaibi',
                'role' => 'Head of Risk Management',
                'age' => 42,
                'location' => 'Dubai, UAE',
                'quote' => 'We need real-time visibility into transaction risks across our entire operation.',
                'demographics' => [
                    'experience' => '14 years in risk management',
                    'institution' => 'Major UAE financial institution',
                    'education' => 'Masters Risk Management, GARP FRM',
                    'annual_budget' => '$1.8M risk systems'
                ],
                'pain_points' => [
                    'Legacy systems provide delayed risk insights',
                    'Integration between screening and risk tools is poor',
                    'Manual alert review consumes significant resources',
                    'Lack of predictive analytics for emerging risks',
                    'Difficult to explain risk decisions to stakeholders'
                ],
                'goals' => [
                    'Implement real-time risk monitoring dashboard',
                    'Reduce false positives by 60%',
                    'Achieve 95%+ alert accuracy',
                    'Automate low-risk transaction processing',
                    'Enable predictive risk assessments'
                ],
                'motivations' => [
                    'Preventing financial crime and money laundering',
                    'Maintaining regulatory compliance',
                    'Improving operational efficiency',
                    'Providing senior management with actionable insights',
                    'Building trust with regulators through transparency'
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'persona_card',
            'data' => [
                'name' => 'محمد العتيبي',
                'role' => 'رئيس إدارة المخاطر',
                'age' => 42,
                'location' => 'دبي، الإمارات العربية المتحدة',
                'quote' => 'نحتاج إلى رؤية في الوقت الفعلي لمخاطر المعاملات عبر عملياتنا بأكملها.',
                'demographics' => [
                    'experience' => '14 سنة في إدارة المخاطر',
                    'institution' => 'مؤسسة مالية رئيسية في الإمارات',
                    'education' => 'ماجستير إدارة المخاطر، GARP FRM',
                    'annual_budget' => '$1.8M أنظمة المخاطر'
                ],
                'pain_points' => [
                    'توفر الأنظمة القديمة رؤية متأخرة عن المخاطر',
                    'التكامل بين أدوات الفحص والمخاطر ضعيف',
                    'المراجعة اليدوية للتنبيهات تستهلك موارد كبيرة',
                    'نقص التحليلات التنبؤية للمخاطر الناشئة',
                    'صعوبة شرح قرارات المخاطر لأصحاب المصلحة'
                ],
                'goals' => [
                    'تنفيذ لوحة مراقبة المخاطر في الوقت الفعلي',
                    'تقليل الإيجابيات الكاذبة بنسبة 60٪',
                    'تحقيق دقة التنبيهات +95٪',
                    'أتمتة معالجة المعاملات منخفضة المخاطر',
                    'تمكين تقييمات المخاطر التنبؤية'
                ],
                'motivations' => [
                    'منع الجرائم المالية وغسل الأموال',
                    'الحفاظ على الامتثال التنظيمي',
                    'تحسين الكفاءة التشغيلية',
                    'توفير الإدارة العليا برؤى قابلة للتطبيق',
                    'بناء الثقة مع المنظمين من خلال الشفافية'
                ]
            ]
        ]
    ],

    'cp_buyer_journey' => [
        'en' => [
            'component_type' => 'journey_timeline',
            'data' => [
                'stages' => [
                    [
                        'title' => 'Awareness',
                        'description' => 'Recognize compliance challenges and seek solutions',
                        'touchpoints' => ['Industry events', 'Peer recommendations', 'Online research', 'Regulatory updates'],
                        'actions' => ['Identify pain points', 'Research vendors', 'Request demos', 'Compare solutions']
                    ],
                    [
                        'title' => 'Evaluation',
                        'description' => 'Assess technical and business fit',
                        'touchpoints' => ['Product demo', 'Technical assessment', 'Case studies', 'Pricing discussion'],
                        'actions' => ['Evaluate features', 'Check compliance', 'Review integrations', 'Negotiate terms']
                    ],
                    [
                        'title' => 'Decision',
                        'description' => 'Select vendor and sign contract',
                        'touchpoints' => ['Contract negotiation', 'Legal review', 'Security audit', 'Executive approval'],
                        'actions' => ['Final negotiations', 'Sign agreement', 'Allocate budget', 'Plan implementation']
                    ],
                    [
                        'title' => 'Implementation',
                        'description' => 'Deploy solution and train teams',
                        'touchpoints' => ['Go-live planning', 'Data migration', 'User training', 'Go-live support'],
                        'actions' => ['Configure system', 'Train staff', 'Test workflows', 'Launch production']
                    ],
                    [
                        'title' => 'Adoption',
                        'description' => 'Full usage and value realization',
                        'touchpoints' => ['Success metrics', 'Support requests', 'Feature requests', 'Renewal discussion'],
                        'actions' => ['Monitor KPIs', 'Gather feedback', 'Plan enhancements', 'Expand usage']
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'journey_timeline',
            'data' => [
                'stages' => [
                    [
                        'title' => 'الوعي',
                        'description' => 'التعرف على تحديات الامتثال والبحث عن الحلول',
                        'touchpoints' => ['فعاليات الصناعة', 'توصيات الأقران', 'البحث عبر الإنترنت', 'التحديثات التنظيمية'],
                        'actions' => ['تحديد نقاط الألم', 'البحث عن البائعين', 'طلب العروض التوضيحية', 'مقارنة الحلول']
                    ],
                    [
                        'title' => 'التقييم',
                        'description' => 'تقييم الملاءمة التقنية والتجارية',
                        'touchpoints' => ['العرض التوضيحي', 'التقييم التقني', 'دراسات الحالة', 'مناقشة التسعير'],
                        'actions' => ['تقييم الميزات', 'التحقق من الامتثال', 'مراجعة التكاملات', 'التفاوض على الشروط']
                    ],
                    [
                        'title' => 'الاختيار',
                        'description' => 'اختيار البائع والتوقيع على العقد',
                        'touchpoints' => ['التفاوض على العقد', 'المراجعة القانونية', 'تدقيق الأمان', 'الموافقة التنفيذية'],
                        'actions' => ['التفاوضات النهائية', 'التوقيع على الاتفاق', 'تخصيص الميزانية', 'التخطيط للتنفيذ']
                    ],
                    [
                        'title' => 'التنفيذ',
                        'description' => 'نشر الحل وتدريب الفرق',
                        'touchpoints' => ['التخطيط للعملية المباشرة', 'هجرة البيانات', 'تدريب المستخدمين', 'دعم العملية المباشرة'],
                        'actions' => ['تكوين النظام', 'تدريب الموظفين', 'اختبار سير العمل', 'إطلاق الإنتاج']
                    ],
                    [
                        'title' => 'التبني',
                        'description' => 'الاستخدام الكامل وإدراك القيمة',
                        'touchpoints' => ['مؤشرات النجاح', 'طلبات الدعم', 'طلبات الميزات', 'مناقشة التجديد'],
                        'actions' => ['مراقبة مؤشرات الأداء الرئيسية', 'جمع الملاحظات', 'التخطيط للتحسينات', 'توسيع الاستخدام']
                    ]
                ]
            ]
        ]
    ],

    'cp_pain_points_analysis' => [
        'en' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'Customer Pain Points Analysis',
                'sections' => [
                    [
                        'heading' => 'Operational Inefficiency',
                        'content' => 'Current compliance teams spend 40-60% of their time on manual processes like alert review, data entry, and report generation. This diverts resources from strategic compliance activities and increases operational costs. SALIS automates these processes, freeing teams for higher-value activities.'
                    ],
                    [
                        'heading' => 'Regulatory Risk',
                        'content' => 'Organizations struggle to keep pace with constant regulatory updates from SAMA, CBU, and international bodies. Delayed implementation of new rules creates compliance gaps and exposes institutions to penalties. SALIS provides real-time regulatory updates integrated directly into screening and reporting systems.'
                    ],
                    [
                        'heading' => 'Technology Fragmentation',
                        'content' => 'Most institutions use 3-4 disconnected compliance tools (screening, risk assessment, reporting, audit). This creates data silos, increases integration complexity, and makes it difficult to maintain a consistent compliance view. SALIS consolidates these functions into one integrated platform.'
                    ],
                    [
                        'heading' => 'Language and Localization',
                        'content' => 'Existing solutions are primarily designed for Western markets and poorly adapted for Arabic operations. Compliance teams must use English-language systems while documenting everything in Arabic, creating translation gaps and inconsistencies. SALIS is natively Arabic with right-to-left support throughout.'
                    ],
                    [
                        'heading' => 'Implementation Complexity',
                        'content' => 'Legacy compliance systems take 6-12 months to implement, requiring significant IT resources and business disruption. Modern institutions need faster deployment. SALIS can be deployed and operational in 2-3 weeks with minimal disruption to existing operations.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'تحليل نقاط ألم العملاء',
                'sections' => [
                    [
                        'heading' => 'عدم الكفاءة التشغيلية',
                        'content' => 'تقضي فرق الامتثال الحالية 40-60٪ من وقتها على العمليات اليدوية مثل مراجعة التنبيهات وإدخال البيانات وإنشاء التقارير. هذا يحول الموارد عن أنشطة الامتثال الاستراتيجية ويزيد من التكاليف التشغيلية. تقوم SALIS بأتمتة هذه العمليات، وتحرر الفرق للأنشطة ذات القيمة الأعلى.'
                    ],
                    [
                        'heading' => 'المخاطر التنظيمية',
                        'content' => 'تكافح المنظمات لمواكبة التحديثات التنظيمية المستمرة من SAMA و CBU والهيئات الدولية. يؤدي التنفيذ المتأخر للقواعد الجديدة إلى فجوات الامتثال ويعرض المؤسسات للعقوبات. توفر SALIS تحديثات تنظيمية في الوقت الفعلي مدمجة مباشرة في أنظمة الفحص والإبلاغ.'
                    ],
                    [
                        'heading' => 'تجزئة التكنولوجيا',
                        'content' => 'تستخدم معظم المؤسسات 3-4 أدوات امتثال غير متصلة (الفحص، تقييم المخاطر، الإبلاغ، التدقيق). هذا يخلق صوامع البيانات ويزيد من تعقيد التكامل ويجعل من الصعب الحفاظ على عرض امتثال متسق. تدمج SALIS هذه الوظائف في منصة واحدة متكاملة.'
                    ],
                    [
                        'heading' => 'اللغة والتوطين',
                        'content' => 'تم تصميم الحلول الموجودة بشكل أساسي للأسواق الغربية والتكيف السيء للعمليات العربية. يجب على فرق الامتثال استخدام أنظمة باللغة الإنجليزية أثناء توثيق كل شيء باللغة العربية، مما يخلق فجوات الترجمة والتناقضات. SALIS أصلي باللغة العربية مع دعم من اليمين إلى اليسار في جميع أنحاء.'
                    ],
                    [
                        'heading' => 'تعقيد التنفيذ',
                        'content' => 'تستغرق أنظمة الامتثال الموروثة 6-12 شهراً للتنفيذ، وتتطلب موارد تكنولوجيا المعلومات كبيرة والتعطيل التجاري. تحتاج المؤسسات الحديثة إلى نشر أسرع. يمكن نشر SALIS وتشغيلها في غضون 2-3 أسابيع مع الحد الأدنى من التعطيل للعمليات الموجودة.'
                    ]
                ]
            ]
        ]
    ],

    'fin_revenue_model' => [
        'en' => [
            'component_type' => 'pricing_cards',
            'data' => [
                'tiers' => [
                    [
                        'name' => 'Starter',
                        'price' => '$12K/mo',
                        'features' => ['Up to 500K transactions/month', 'Basic screening', 'Monthly reporting', 'Standard support', '1 admin user'],
                        'highlighted' => false,
                        'cta' => 'Start Free Trial'
                    ],
                    [
                        'name' => 'Professional',
                        'price' => '$25K/mo',
                        'features' => ['Up to 2M transactions/month', 'Advanced screening + AI', 'Weekly reporting', 'Priority support', '5 admin users', 'Custom rules'],
                        'highlighted' => true,
                        'cta' => 'Get Started'
                    ],
                    [
                        'name' => 'Enterprise',
                        'price' => 'Custom',
                        'features' => ['Unlimited transactions', 'Full AI suite', 'Real-time reporting', '24/7 support', 'Unlimited users', 'Custom integrations', 'Dedicated account manager'],
                        'highlighted' => false,
                        'cta' => 'Contact Sales'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'pricing_cards',
            'data' => [
                'tiers' => [
                    [
                        'name' => 'البداية',
                        'price' => '$12K/mo',
                        'features' => ['حتى 500K معاملة/شهر', 'الفحص الأساسي', 'التقارير الشهرية', 'الدعم القياسي', 'مستخدم إداري واحد'],
                        'highlighted' => false,
                        'cta' => 'ابدأ الاختبار المجاني'
                    ],
                    [
                        'name' => 'احترافي',
                        'price' => '$25K/mo',
                        'features' => ['حتى 2M معاملة/شهر', 'الفحص المتقدم + AI', 'التقارير الأسبوعية', 'دعم الأولوية', '5 مستخدمين إداريين', 'القواعد المخصصة'],
                        'highlighted' => true,
                        'cta' => 'ابدأ الآن'
                    ],
                    [
                        'name' => 'المشروع',
                        'price' => 'مخصص',
                        'features' => ['معاملات غير محدودة', 'مجموعة AI الكاملة', 'التقارير في الوقت الفعلي', 'دعم 24/7', 'مستخدمون غير محدودون', 'تكاملات مخصصة', 'مدير حساب مخصص'],
                        'highlighted' => false,
                        'cta' => 'اتصل بالمبيعات'
                    ]
                ]
            ]
        ]
    ],

    'fin_cost_structure' => [
        'en' => [
            'component_type' => 'progress_bars',
            'data' => [
                'items' => [
                    ['label' => 'Infrastructure & Cloud', 'value' => 25, 'suffix' => '%'],
                    ['label' => 'R&D & AI Model Training', 'value' => 30, 'suffix' => '%'],
                    ['label' => 'Sales & Marketing', 'value' => 20, 'suffix' => '%'],
                    ['label' => 'Customer Support & Success', 'value' => 15, 'suffix' => '%'],
                    ['label' => 'Operations & Admin', 'value' => 10, 'suffix' => '%']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'progress_bars',
            'data' => [
                'items' => [
                    ['label' => 'البنية الأساسية والسحابة', 'value' => 25, 'suffix' => '%'],
                    ['label' => 'البحث والتطوير وتدريب نموذج AI', 'value' => 30, 'suffix' => '%'],
                    ['label' => 'المبيعات والتسويق', 'value' => 20, 'suffix' => '%'],
                    ['label' => 'دعم العملاء والنجاح', 'value' => 15, 'suffix' => '%'],
                    ['label' => 'العمليات والإدارة', 'value' => 10, 'suffix' => '%']
                ]
            ]
        ]
    ],

    'fin_financial_projections' => [
        'en' => [
            'component_type' => 'stat_cards',
            'data' => [
                'metrics' => [
                    ['label' => 'Year 1 Revenue', 'value' => '$3.8M', 'description' => 'Initial customer base'],
                    ['label' => 'Year 2 Revenue', 'value' => '$12.5M', 'description' => '230% growth target'],
                    ['label' => 'Year 3 Revenue', 'value' => '$35.8M', 'description' => '186% growth trajectory'],
                    ['label' => 'Gross Margin', 'value' => '72%', 'description' => 'By Year 3']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'stat_cards',
            'data' => [
                'metrics' => [
                    ['label' => 'إيرادات السنة الأولى', 'value' => '$3.8M', 'description' => 'قاعدة العملاء الأولية'],
                    ['label' => 'إيرادات السنة الثانية', 'value' => '$12.5M', 'description' => 'هدف النمو 230٪'],
                    ['label' => 'إيرادات السنة الثالثة', 'value' => '$35.8M', 'description' => 'مسار النمو 186٪'],
                    ['label' => 'الهامش الإجمالي', 'value' => '72%', 'description' => 'بحلول السنة الثالثة']
                ]
            ]
        ]
    ],

    'fin_funding_requirements' => [
        'en' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'Funding Requirements & Use of Funds',
                'sections' => [
                    [
                        'heading' => 'Series A Target',
                        'content' => 'We are seeking $8.5M in Series A funding to accelerate market expansion and enhance product capabilities. This follows our successful seed round that established product-market fit and generated $3.8M in ARR.'
                    ],
                    [
                        'heading' => 'Product Development (35%)',
                        'content' => 'Enhance AI/ML capabilities with advanced anomaly detection, predictive analytics, and behavioral biometric analysis. Expand integration ecosystem with regional banking platforms. Develop mobile applications for iOS and Android. Build advanced reporting and visualization tools.'
                    ],
                    [
                        'heading' => 'Sales & Market Expansion (40%)',
                        'content' => 'Expand sales team to cover Saudi Arabia, UAE, Kuwait, and Qatar. Establish regional offices in key markets. Develop channel partnerships with consulting firms and system integrators. Launch targeted marketing campaigns in GCC financial sector.'
                    ],
                    [
                        'heading' => 'Operations & Compliance (15%)',
                        'content' => 'Strengthen compliance and regulatory expertise with senior advisors. Build robust security and infrastructure teams. Establish ISO 27001 and SOC 2 compliance infrastructure. Create customer success and support operations.'
                    ],
                    [
                        'heading' => 'Working Capital (10%)',
                        'content' => 'Maintain operational flexibility and fund growth initiatives. Support customer onboarding and implementation. Cover recruitment, training, and team expansion costs. Provide buffer for market opportunities and contingencies.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'متطلبات التمويل واستخدام الأموال',
                'sections' => [
                    [
                        'heading' => 'هدف Series A',
                        'content' => 'نحن نسعى إلى الحصول على تمويل بقيمة 8.5 مليون دولار في Series A لتسريع التوسع في السوق وتحسين قدرات المنتج. يأتي هذا بعد جولة البذور الناجحة التي أنشأت توافق المنتج مع السوق وأنتجت 3.8 مليون دولار في ARR.'
                    ],
                    [
                        'heading' => 'تطوير المنتج (35٪)',
                        'content' => 'تحسين قدرات AI/ML مع الكشف المتقدم عن الحالات الشاذة والتحليلات التنبؤية وتحليل البيومترية السلوكية. توسيع نظام البيئة للتكامل مع منصات البنوك الإقليمية. تطوير تطبيقات الهاتف المحمول لـ iOS و Android. بناء أدوات الإبلاغ والتصور المتقدمة.'
                    ],
                    [
                        'heading' => 'المبيعات وتوسيع السوق (40٪)',
                        'content' => 'توسيع فريق المبيعات لتغطية المملكة العربية السعودية والإمارات والكويت وقطر. إنشاء مكاتب إقليمية في الأسواق الرئيسية. تطوير شراكات قنوية مع شركات الاستشارات والمدمجين. إطلاق حملات تسويقية موجهة في القطاع المالي بمجلس التعاون الخليجي.'
                    ],
                    [
                        'heading' => 'العمليات والامتثال (15٪)',
                        'content' => 'تعزيز الخبرة في الامتثال والتنظيم مع المستشارين الكبار. بناء فرق الأمان والبنية الأساسية القوية. إنشاء بنية الامتثال ISO 27001 و SOC 2. إنشاء عمليات نجاح العملاء والدعم.'
                    ],
                    [
                        'heading' => 'رأس المال العامل (10٪)',
                        'content' => 'الحفاظ على المرونة التشغيلية وتمويل مبادرات النمو. دعم إعداد العملاء والتنفيذ. تغطية تكاليف التوظيف والتدريب وتوسيع الفريق. توفير مخزن مؤقت لفرص السوق والطوارئ.'
                    ]
                ]
            ]
        ]
    ],

    'fin_unit_economics' => [
        'en' => [
            'component_type' => 'key_value',
            'data' => [
                'items' => [
                    ['key' => 'Average Contract Value', 'value' => '$240K/year'],
                    ['key' => 'Customer Acquisition Cost', 'value' => '$32K'],
                    ['key' => 'Payback Period', 'value' => '1.6 months'],
                    ['key' => 'Customer Lifetime Value', 'value' => '$1.8M (5-year average)'],
                    ['key' => 'LTV:CAC Ratio', 'value' => '56:1'],
                    ['key' => 'Net Revenue Retention', 'value' => '132%'],
                    ['key' => 'Gross Margin', 'value' => '68%'],
                    ['key' => 'Magic Number', 'value' => '0.68']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'key_value',
            'data' => [
                'items' => [
                    ['key' => 'قيمة العقد الوسيطة', 'value' => '$240K/year'],
                    ['key' => 'تكلفة اكتساب العملاء', 'value' => '$32K'],
                    ['key' => 'فترة الاسترداد', 'value' => '1.6 شهر'],
                    ['key' => 'قيمة العمر الافتراضي للعميل', 'value' => '$1.8M (متوسط 5 سنوات)'],
                    ['key' => 'نسبة LTV:CAC', 'value' => '56:1'],
                    ['key' => 'الاحتفاظ بالإيرادات الصافية', 'value' => '132%'],
                    ['key' => 'الهامش الإجمالي', 'value' => '68%'],
                    ['key' => 'الرقم السحري', 'value' => '0.68']
                ]
            ]
        ]
    ],

    'gtm_launch_strategy' => [
        'en' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'Go-to-Market Launch Strategy',
                'sections' => [
                    [
                        'heading' => 'Phase 1: Anchor Customer Strategy (Months 1-3)',
                        'content' => 'Focus on landing 2-3 anchor customers from Tier-1 banks in Saudi Arabia and UAE. Provide white-glove implementation and support. Generate case studies and testimonials. Achieve high customer satisfaction and NPS scores above 70.'
                    ],
                    [
                        'heading' => 'Phase 2: Regional Expansion (Months 4-9)',
                        'content' => 'Expand into Kuwait and Qatar markets. Build sales team across GCC region. Establish partnerships with system integrators and consultants. Target both large banks and mid-size financial institutions. Launch targeted LinkedIn and industry conference campaigns.'
                    ],
                    [
                        'heading' => 'Phase 3: Market Penetration (Months 10-18)',
                        'content' => 'Penetrate secondary markets including Bahrain and Oman. Develop channel partnership program with resellers. Launch thought leadership content and regulatory compliance guides. Achieve 15-20 customer logos across GCC.'
                    ],
                    [
                        'heading' => 'Phase 4: Scale & Optimization (Months 19+)',
                        'content' => 'Build brand awareness through industry awards and analyst recognition. Develop product-led growth with self-serve capabilities. Expand to adjacent markets in MENA region. Plan for 40+ customer target by end of Year 2.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'استراتيجية الإطلاق في السوق',
                'sections' => [
                    [
                        'heading' => 'المرحلة 1: استراتيجية العميل الأساسي (الأشهر 1-3)',
                        'content' => 'التركيز على الهبوط على 2-3 عملاء أساسيين من البنوك من الدرجة الأولى في المملكة العربية السعودية والإمارات. توفير التنفيذ والدعم بالخدمة الكاملة. إنشاء دراسات الحالات والشهادات. تحقيق رضا العملاء العالي ودرجات NPS فوق 70.'
                    ],
                    [
                        'heading' => 'المرحلة 2: التوسع الإقليمي (الأشهر 4-9)',
                        'content' => 'التوسع في أسواق الكويت وقطر. بناء فريق المبيعات عبر منطقة مجلس التعاون الخليجي. إنشاء شراكات مع المدمجين والمستشارين. استهداف البنوك الكبيرة والمؤسسات المالية متوسطة الحجم. إطلاق حملات LinkedIn والمؤتمرات الصناعية الموجهة.'
                    ],
                    [
                        'heading' => 'المرحلة 3: اختراق السوق (الأشهر 10-18)',
                        'content' => 'اختراق الأسواق الثانوية بما في ذلك البحرين وعمان. تطوير برنامج شراكة القناة مع الموزعين. إطلاق محتوى القيادة الفكرية وأدلة الامتثال التنظيمي. تحقيق 15-20 شعار عميل عبر مجلس التعاون الخليجي.'
                    ],
                    [
                        'heading' => 'المرحلة 4: التوسع والتحسين (الأشهر 19+)',
                        'content' => 'بناء الوعي بالعلامة التجارية من خلال جوائز الصناعة والاعتراف بالمحلل. تطوير النمو الموجه بالمنتج مع قدرات الخدمة الذاتية. التوسع للأسواق المجاورة في منطقة الشرق الأوسط وشمال أفريقيا. التخطيط لـ 40+ هدف عميل بحلول نهاية السنة الثانية.'
                    ]
                ]
            ]
        ]
    ],

    'gtm_marketing_channels' => [
        'en' => [
            'component_type' => 'stat_cards',
            'data' => [
                'metrics' => [
                    ['label' => 'LinkedIn B2B', 'value' => '35%', 'description' => 'Primary channel for decision makers'],
                    ['label' => 'Industry Events', 'value' => '25%', 'description' => 'Conferences and trade shows'],
                    ['label' => 'Direct Sales', 'value' => '20%', 'description' => 'Enterprise relationship building'],
                    ['label' => 'Content Marketing', 'value' => '15%', 'description' => 'Compliance guides and whitepapers'],
                    ['label' => 'Referrals & Partnerships', 'value' => '5%', 'description' => 'Channel partners and integrators']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'stat_cards',
            'data' => [
                'metrics' => [
                    ['label' => 'LinkedIn B2B', 'value' => '35%', 'description' => 'القناة الأساسية لصانعي القرار'],
                    ['label' => 'فعاليات الصناعة', 'value' => '25%', 'description' => 'المؤتمرات والمعارض التجارية'],
                    ['label' => 'المبيعات المباشرة', 'value' => '20%', 'description' => 'بناء العلاقات المؤسسية'],
                    ['label' => 'تسويق المحتوى', 'value' => '15%', 'description' => 'أدلة الامتثال والكتب البيضاء'],
                    ['label' => 'الإحالات والشراكات', 'value' => '5%', 'description' => 'شركاء القنوات والمدمجون']
                ]
            ]
        ]
    ],

    'gtm_sales_funnel' => [
        'en' => [
            'component_type' => 'journey_timeline',
            'data' => [
                'stages' => [
                    [
                        'title' => 'Awareness',
                        'description' => 'Build brand awareness through content and events',
                        'touchpoints' => ['LinkedIn posts', 'Compliance guides', 'Industry events', 'Webinars'],
                        'actions' => ['Create content', 'Host events', 'Build brand', 'Generate leads']
                    ],
                    [
                        'title' => 'Interest & Consideration',
                        'description' => 'Engage prospects with product information',
                        'touchpoints' => ['Product demo', 'Case studies', 'Pricing page', 'ROI calculator'],
                        'actions' => ['Schedule demos', 'Share materials', 'Build pipeline', 'Qualify leads']
                    ],
                    [
                        'title' => 'Evaluation',
                        'description' => 'Work with procurement and technical teams',
                        'touchpoints' => ['Technical assessment', 'Security audit', 'Pricing negotiation', 'Reference calls'],
                        'actions' => ['Run assessments', 'Negotiate terms', 'Proof of concept', 'Get approvals']
                    ],
                    [
                        'title' => 'Decision',
                        'description' => 'Close deal and sign contract',
                        'touchpoints' => ['Final negotiations', 'Contract review', 'Executive sign-off', 'Payment processing'],
                        'actions' => ['Finalize terms', 'Legal review', 'Secure funding', 'Execute agreement']
                    ],
                    [
                        'title' => 'Onboarding',
                        'description' => 'Implement solution and ensure success',
                        'touchpoints' => ['Implementation plan', 'Data migration', 'User training', 'Go-live support'],
                        'actions' => ['Deploy system', 'Train users', 'Enable features', 'Achieve ROI']
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'journey_timeline',
            'data' => [
                'stages' => [
                    [
                        'title' => 'الوعي',
                        'description' => 'بناء الوعي بالعلامة التجارية من خلال المحتوى والأحداث',
                        'touchpoints' => ['منشورات LinkedIn', 'أدلة الامتثال', 'فعاليات الصناعة', 'الندوات عبر الويب'],
                        'actions' => ['إنشاء محتوى', 'استضافة الأحداث', 'بناء العلامة التجارية', 'إنشاء الفرص']
                    ],
                    [
                        'title' => 'الاهتمام والاعتبار',
                        'description' => 'التعامل مع الآفاق بمعلومات المنتج',
                        'touchpoints' => ['العرض التوضيحي', 'دراسات الحالات', 'صفحة التسعير', 'حاسبة العائد على الاستثمار'],
                        'actions' => ['جدولة العروض التوضيحية', 'مشاركة المواد', 'بناء خط الأنابيب', 'تأهيل الفرص']
                    ],
                    [
                        'title' => 'التقييم',
                        'description' => 'العمل مع فرق الشراء والفنية',
                        'touchpoints' => ['التقييم الفني', 'تدقيق الأمان', 'التفاوض على السعر', 'استدعاءات الإحالة'],
                        'actions' => ['تشغيل التقييمات', 'التفاوض على الشروط', 'إثبات المفهوم', 'الحصول على الموافقات']
                    ],
                    [
                        'title' => 'الاختيار',
                        'description' => 'إغلاق الصفقة والتوقيع على العقد',
                        'touchpoints' => ['المفاوضات النهائية', 'مراجعة العقد', 'الموافقة التنفيذية', 'معالجة الدفع'],
                        'actions' => ['إنهاء الشروط', 'المراجعة القانونية', 'تأمين التمويل', 'تنفيذ الاتفاق']
                    ],
                    [
                        'title' => 'الإعداد',
                        'description' => 'تنفيذ الحل وضمان النجاح',
                        'touchpoints' => ['خطة التنفيذ', 'هجرة البيانات', 'تدريب المستخدمين', 'دعم العملية المباشرة'],
                        'actions' => ['نشر النظام', 'تدريب المستخدمين', 'تفعيل الميزات', 'تحقيق العائد على الاستثمار']
                    ]
                ]
            ]
        ]
    ],

    'gtm_partnerships' => [
        'en' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'Partnership & Distribution Strategy',
                'sections' => [
                    [
                        'heading' => 'System Integrator Partners',
                        'content' => 'Partner with leading SI firms like ATOS, IBM regional units, and local Saudi/UAE integration companies. Enable them to resell SALIS as part of comprehensive compliance solutions. Provide 20-25% reseller margins and co-marketing support.'
                    ],
                    [
                        'heading' => 'Consulting & Advisory Partnerships',
                        'content' => 'Establish relationships with Big 4 advisory firms (Deloitte, EY, KPMG, PwC) and local compliance consultants. Position SALIS as the technology backbone for compliance implementations. Generate joint marketing and lead-sharing agreements.'
                    ],
                    [
                        'heading' => 'Banking Technology Partners',
                        'content' => 'Integrate with core banking platforms (Temenos, Backbase, SAP Fintech) used across GCC. Ensure seamless data flow and reporting integration. Support as white-label option for banking software vendors.'
                    ],
                    [
                        'heading' => 'Regulatory Relationships',
                        'content' => 'Build relationships with SAMA, CBU, and local regulatory bodies. Participate in compliance working groups. Demonstrate alignment with regulatory standards and best practices. Potential for official endorsement or recognition.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'استراتيجية الشراكة والتوزيع',
                'sections' => [
                    [
                        'heading' => 'شركاء مدمج النظام',
                        'content' => 'الشراكة مع شركات SI الرائدة مثل ATOS وفروع IBM الإقليمية وشركات التكامل السعودية والإماراتية المحلية. تمكينهم من إعادة بيع SALIS كجزء من حلول الامتثال الشاملة. توفير هوامش موزع 20-25٪ والدعم المشترك للتسويق.'
                    ],
                    [
                        'heading' => 'شراكات الاستشارات والمشورة',
                        'content' => 'إنشاء علاقات مع شركات Big 4 للمشورة (Deloitte، EY، KPMG، PwC) والمستشارين الامتثال المحليين. وضع SALIS كالعمود الفقري التكنولوجي لتنفيذ الامتثال. توليد اتفاقيات التسويق المشترك ومشاركة الفرص.'
                    ],
                    [
                        'heading' => 'شركاء تكنولوجيا البنوك',
                        'content' => 'التكامل مع منصات الخدمات المصرفية الأساسية (Temenos، Backbase، SAP Fintech) المستخدمة عبر مجلس التعاون الخليجي. ضمان تدفق البيانات والتكامل السلس في الإبلاغ. الدعم كخيار تسمية بيضاء لبائعي البرامج المصرفية.'
                    ],
                    [
                        'heading' => 'العلاقات التنظيمية',
                        'content' => 'بناء علاقات مع SAMA و CBU والهيئات التنظيمية المحلية. المشاركة في مجموعات عمل الامتثال. إظهار التوافق مع معايير ومعايير أفضل الممارسات التنظيمية. احتمال الموافقة أو الاعتراف الرسمي.'
                    ]
                ]
            ]
        ]
    ],

    'gtm_growth_metrics' => [
        'en' => [
            'component_type' => 'progress_bars',
            'data' => [
                'items' => [
                    ['label' => 'Customer Acquisition (Monthly)', 'value' => 15, 'suffix' => 'customers'],
                    ['label' => 'Revenue Growth (YoY)', 'value' => 230, 'suffix' => '%'],
                    ['label' => 'NPS Score Target', 'value' => 72, 'suffix' => 'points'],
                    ['label' => 'Churn Rate (Target)', 'value' => 5, 'suffix' => '%'],
                    ['label' => 'Market Penetration', 'value' => 8, 'suffix' => '% (Year 2)']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'progress_bars',
            'data' => [
                'items' => [
                    ['label' => 'اكتساب العملاء (شهري)', 'value' => 15, 'suffix' => 'عملاء'],
                    ['label' => 'نمو الإيرادات (سنويًا)', 'value' => 230, 'suffix' => '%'],
                    ['label' => 'هدف نقاط NPS', 'value' => 72, 'suffix' => 'نقطة'],
                    ['label' => 'معدل الفقد (الهدف)', 'value' => 5, 'suffix' => '%'],
                    ['label' => 'اختراق السوق', 'value' => 8, 'suffix' => '% (السنة 2)']
                ]
            ]
        ]
    ],

    'ca_competitor_overview' => [
        'en' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'Competitive Landscape Overview',
                'sections' => [
                    [
                        'heading' => 'Legacy Competitors',
                        'content' => 'Established players like Thomson Reuters CLEAR, Lexis-Nexis, and Refinitiv dominate the global market. However, these solutions are expensive ($50K+/month), require 8-12 month implementations, and lack Arabic language support. They treat GCC as a secondary market, not a strategic focus.'
                    ],
                    [
                        'heading' => 'Global Cloud Solutions',
                        'content' => 'Newer entrants like Feedzai, SAS AML, and IBM Cloud offerings provide cloud-based compliance. However, they are generalized for global markets and often require significant customization for GCC regulations. Implementation costs remain high, and local support is limited.'
                    ],
                    [
                        'heading' => 'Regional Players',
                        'content' => 'A few regional solutions exist but with limited capabilities. Most focus on transaction monitoring only. They lack advanced AI, comprehensive regulatory reporting, and integration capabilities. Many struggle with scalability and uptime reliability.'
                    ],
                    [
                        'heading' => 'SALIS Competitive Position',
                        'content' => 'We are the first and only purpose-built, cloud-native AML compliance platform designed specifically for GCC markets. Our competitive advantages include: 70% lower cost, 4x faster implementation, native Arabic support, 94% AI accuracy, and deep regulatory expertise.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'نظرة عامة على المشهد التنافسي',
                'sections' => [
                    [
                        'heading' => 'المنافسون الموروثون',
                        'content' => 'يهيمن اللاعبون الراسخون مثل Thomson Reuters CLEAR و Lexis-Nexis و Refinitiv على السوق العالمية. ومع ذلك، فإن هذه الحلول مكلفة (أكثر من 50K دولار/شهر)، وتتطلب تنفيذ 8-12 شهر، وتفتقر إلى دعم اللغة العربية. إنهم يتعاملون مع مجلس التعاون الخليجي كسوق ثانوية، وليس تركيز استراتيجي.'
                    ],
                    [
                        'heading' => 'حلول السحابة العالمية',
                        'content' => 'توفر الداخلون الأحدث مثل Feedzai و SAS AML و IBM Cloud حلول امتثال قائمة على السحابة. ومع ذلك، فهي عامة للأسواق العالمية وغالباً ما تتطلب تخصيصاً كبيراً للوائح مجلس التعاون الخليجي. تظل تكاليف التنفيذ مرتفعة، والدعم المحلي محدود.'
                    ],
                    [
                        'heading' => 'لاعبون إقليميون',
                        'content' => 'يوجد عدد قليل من الحلول الإقليمية ولكن بقدرات محدودة. يركز معظمها على مراقبة المعاملات فقط. إنهم يفتقرون إلى الذكاء الاصطناعي المتقدم والإبلاغ التنظيمي الشامل وقدرات التكامل. يكافح الكثير منهم مع قابلية التوسع وموثوقية وقت التشغيل.'
                    ],
                    [
                        'heading' => 'موضع SALIS التنافسي',
                        'content' => 'نحن أول وأوحد منصة امتثال AML محلية الصنع، قائمة على السحابة، مصممة خصيصاً لأسواق مجلس التعاون الخليجي. تشمل مزايانا التنافسية: تكلفة أقل بـ 70٪، تنفيذ أسرع 4 مرات، دعم عربي أصلي، دقة الذكاء الاصطناعي 94٪، وخبرة تنظيمية عميقة.'
                    ]
                ]
            ]
        ]
    ],

    'ca_feature_comparison' => [
        'en' => [
            'component_type' => 'comparison_table',
            'data' => [
                'headers' => ['Feature', 'SALIS', 'Thomson Reuters', 'Feedzai', 'Regional Competitors'],
                'rows' => [
                    ['Transaction Screening', '✓ Advanced', '✓ Basic', '✓ Advanced', '✓ Basic'],
                    ['Risk Scoring', '✓ AI-powered', '✓ Rules-based', '✓ ML-based', '✗ Manual'],
                    ['Arabic Support', '✓ Native RTL', '✗ Translation only', '✗ Limited', '✓ Partial'],
                    ['SAMA Compliance', '✓ Purpose-built', '✓ Generic', '✓ Configurable', '✓ Basic'],
                    ['Real-time Alerts', '✓ Yes', '✓ Batch', '✓ Yes', '✗ Delayed'],
                    ['Implementation Time', '2-3 weeks', '8-12 months', '4-6 months', '6-8 weeks'],
                    ['Monthly Cost', '$15K', '$50K+', '$35K+', '$18K+'],
                    ['Mobile App', '✓ iOS/Android', '✗ No', '✓ Yes', '✗ No']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'comparison_table',
            'data' => [
                'headers' => ['الميزة', 'SALIS', 'Thomson Reuters', 'Feedzai', 'المنافسون الإقليميون'],
                'rows' => [
                    ['فحص المعاملات', '✓ متقدم', '✓ أساسي', '✓ متقدم', '✓ أساسي'],
                    ['تسجيل المخاطر', '✓ مدعوم بـ AI', '✓ قائم على القواعس', '✓ قائم على ML', '✗ يدويًا'],
                    ['دعم اللغة العربية', '✓ RTL أصلي', '✗ ترجمة فقط', '✗ محدود', '✓ جزئي'],
                    ['امتثال SAMA', '✓ مبني لغرض معين', '✓ عام', '✓ قابل للتكوين', '✓ أساسي'],
                    ['التنبيهات في الوقت الفعلي', '✓ نعم', '✓ دفعة', '✓ نعم', '✗ متأخر'],
                    ['وقت التنفيذ', '2-3 أسابيع', '8-12 شهر', '4-6 أشهر', '6-8 أسابيع'],
                    ['التكلفة الشهرية', '$15K', '$50K+', '$35K+', '$18K+'],
                    ['تطبيق الهاتف المحمول', '✓ iOS/Android', '✗ لا', '✓ نعم', '✗ لا']
                ]
            ]
        ]
    ],

    'ca_market_positioning' => [
        'en' => [
            'component_type' => 'key_value',
            'data' => [
                'items' => [
                    ['key' => 'Market Positioning', 'value' => 'The GCC AML Compliance Leader'],
                    ['key' => 'Primary Target', 'value' => 'Banks and financial institutions in Saudi Arabia, UAE, Kuwait, Qatar'],
                    ['key' => 'Core Value Proposition', 'value' => 'Fast, affordable, native AML compliance for GCC'],
                    ['key' => 'Key Differentiator', 'value' => 'Purpose-built for GCC regulations with native Arabic support'],
                    ['key' => 'Competitive Price', 'value' => '70% lower than legacy solutions'],
                    ['key' => 'Implementation Speed', 'value' => '4x faster than industry average'],
                    ['key' => 'Market Opportunity', 'value' => '$2.1B regional AML compliance market'],
                    ['key' => 'Addressable Market (Year 3)', 'value' => '25+ tier-1 and tier-2 banks']
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'key_value',
            'data' => [
                'items' => [
                    ['key' => 'موضع السوق', 'value' => 'قائد امتثال AML في مجلس التعاون الخليجي'],
                    ['key' => 'الهدف الأساسي', 'value' => 'البنوك والمؤسسات المالية في السعودية والإمارات والكويت وقطر'],
                    ['key' => 'عرض القيمة الأساسي', 'value' => 'امتثال AML سريع وميسور ومحلي الصنع للخليج'],
                    ['key' => 'المميز الرئيسي', 'value' => 'مبني لغرض معين للوائح مجلس التعاون الخليجي مع دعم عربي أصلي'],
                    ['key' => 'السعر التنافسي', 'value' => 'أقل بـ 70٪ من الحلول الموروثة'],
                    ['key' => 'سرعة التنفيذ', 'value' => 'أسرع 4 مرات من متوسط الصناعة'],
                    ['key' => 'فرصة السوق', 'value' => 'سوق امتثال AML الإقليمي 2.1 مليار دولار'],
                    ['key' => 'السوق القابل للخدمة (السنة 3)', 'value' => '25+ بنك من الدرجة الأولى والثانية']
                ]
            ]
        ]
    ],

    'ca_competitive_moat' => [
        'en' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'Sustainable Competitive Moat',
                'sections' => [
                    [
                        'heading' => 'Regulatory Expertise & Data',
                        'content' => 'SALIS has built deep relationships with SAMA, CBU, and other GCC regulators. We maintain proprietary databases of compliance rules, regulatory updates, and historical enforcement patterns across the region. This expertise is difficult for competitors to replicate and creates significant switching costs.'
                    ],
                    [
                        'heading' => 'Proprietary AI Models',
                        'content' => 'Our machine learning models are trained on regional transaction data and behavioral patterns unique to GCC financial markets. The models achieve 94% accuracy on regional data but would require significant retraining for other markets. This localized AI advantage is our strongest moat.'
                    ],
                    [
                        'heading' => 'Network Effects',
                        'content' => 'As we add more customers, our AI models improve through aggregate transaction data. Customer success stories and regulatory endorsements create a flywheel effect. Early market leadership in GCC creates natural network benefits for integration partnerships and data insights.'
                    ],
                    [
                        'heading' => 'Brand & Trust',
                        'content' => 'Being the first native GCC AML platform gives us strong positioning. Early customer wins from tier-1 banks establish credibility and trust. Compliance officers are risk-averse and prefer proven, trusted solutions. Once established, brand switching costs are very high.'
                    ],
                    [
                        'heading' => 'Integration Ecosystem',
                        'content' => 'Deep integrations with core banking platforms, regional payment systems, and compliance tools create network effects. Once customers integrate SALIS with their existing systems, switching costs increase significantly. This ecosystem becomes more valuable with each new partnership.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'component_type' => 'text_content',
            'data' => [
                'title' => 'خندق تنافسي مستدام',
                'sections' => [
                    [
                        'heading' => 'خبرة تنظيمية وبيانات',
                        'content' => 'بنت SALIS علاقات عميقة مع SAMA و CBU والمنظمين الآخرين في مجلس التعاون الخليجي. نحتفظ بقواعس بيانات ملكية لقواعس الامتثال والتحديثات التنظيمية وأنماط الإنفاذ التاريخية عبر المنطقة. هذه الخبرة يصعب على المنافسين تكرارها وتخلق تكاليف تبديل كبيرة.'
                    ],
                    [
                        'heading' => 'نماذج الذكاء الاصطناعي الملكية',
                        'content' => 'تم تدريب نماذج التعلم الآلي لدينا على بيانات المعاملات الإقليمية والأنماط السلوكية الفريدة لأسواق مجلس التعاون الخليجي المالية. تحقق النماذج دقة 94٪ على البيانات الإقليمية لكن ستتطلب إعادة تدريب كبيرة للأسواق الأخرى. تعتبر هذه ميزة الذكاء الاصطناعي المحلية أقوى خندق لدينا.'
                    ],
                    [
                        'heading' => 'تأثيرات الشبكة',
                        'content' => 'مع إضافتنا للمزيد من العملاء، تتحسن نماذج الذكاء الاصطناعي لدينا من خلال بيانات المعاملات الكلية. قصص نجاح العملاء والموافقات التنظيمية تخلق تأثير عجلة. تحقق القيادة المبكرة في السوق في مجلس التعاون الخليجي فوائد شبكة طبيعية لشراكات التكامل والرؤى المتعلقة بالبيانات.'
                    ],
                    [
                        'heading' => 'العلامة التجارية والثقة',
                        'content' => 'كوننا أول منصة AML محلية الصنع في مجلس التعاون الخليجي يعطينا موضعاً قوياً. الفوز بالعملاء في وقت مبكر من بنوك من الدرجة الأولى يؤسس للمصداقية والثقة. مسؤولو الامتثال كرهاويون من المخاطر ويفضلون الحلول المثبوتة والموثوقة. بمجرد التأسيس، تكون تكاليف تبديل الماركة عالية جداً.'
                    ],
                    [
                        'heading' => 'نظام البيئة للتكامل',
                        'content' => 'تكاملات عميقة مع منصات الخدمات المصرفية الأساسية وأنظمة الدفع الإقليمية وأدوات الامتثال تخلق تأثيرات الشبكة. بمجرد دمج العملاء SALIS مع أنظمتهم الموجودة، تزداد تكاليف التبديل بشكل كبير. هذا النظام البيئي يصبح أكثر قيمة مع كل شراكة جديدة.'
                    ]
                ]
            ]
        ]
    ]
];
