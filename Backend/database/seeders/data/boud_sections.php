<?php return [
    'mvp_feature_priority' => [
        'en' => [
            'comparison_table' => [
                'headers' => ['Feature', 'Priority', 'Impact', 'Timeline (Weeks)'],
                'rows' => [
                    ['AI-Powered Transformation Assessment', 'Critical', 'High', '4-6'],
                    ['Digital Maturity Dashboard', 'Critical', 'High', '6-8'],
                    ['Innovation Roadmap Generator', 'High', 'High', '5-7'],
                    ['Consulting Project Management', 'High', 'Medium', '4-5'],
                    ['Vision 2030 Compliance Framework', 'High', 'High', '6-8'],
                    ['Real-Time Analytics & Reporting', 'Medium', 'High', '7-9'],
                    ['Team Collaboration Portal', 'Medium', 'Medium', '3-4'],
                    ['Integration Marketplace', 'Medium', 'Medium', '8-10'],
                ]
            ]
        ],
        'ar' => [
            'comparison_table' => [
                'headers' => ['الميزة', 'الأولوية', 'التأثير', 'الجدول الزمني (أسابيع)'],
                'rows' => [
                    ['تقييم التحول الرقمي بتقنية الذكاء الاصطناعي', 'حرج', 'عالي', '4-6'],
                    ['لوحة معلومات النضج الرقمي', 'حرج', 'عالي', '6-8'],
                    ['مولد خريطة الطريق للابتكار', 'عالي', 'عالي', '5-7'],
                    ['إدارة مشاريع الاستشارات', 'عالي', 'متوسط', '4-5'],
                    ['إطار العمل لرؤية 2030', 'عالي', 'عالي', '6-8'],
                    ['التحليلات والتقارير في الوقت الفعلي', 'متوسط', 'عالي', '7-9'],
                    ['بوابة التعاون بين الفرق', 'متوسط', 'متوسط', '3-4'],
                    ['سوق التكاملات', 'متوسط', 'متوسط', '8-10'],
                ]
            ]
        ]
    ],

    'mvp_development_roadmap' => [
        'en' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'Phase 1: Foundation (Weeks 1-6)',
                        'description' => 'Core platform architecture and AI assessment engine',
                        'touchpoints' => ['Team onboarding', 'Technology stack finalization', 'Database design', 'API framework setup'],
                        'actions' => ['Establish dev environment', 'Create CI/CD pipeline', 'Build core assessment module', 'Initial testing framework']
                    ],
                    [
                        'title' => 'Phase 2: Core Features (Weeks 7-14)',
                        'description' => 'Digital maturity dashboard and roadmap generator',
                        'touchpoints' => ['Frontend development', 'AI model integration', 'Dashboard design', 'User testing begins'],
                        'actions' => ['Build dashboard UI', 'Integrate AI models', 'Create roadmap generation logic', 'Beta user onboarding']
                    ],
                    [
                        'title' => 'Phase 3: Enhancement (Weeks 15-20)',
                        'description' => 'Vision 2030 compliance and analytics',
                        'touchpoints' => ['Compliance module launch', 'Advanced analytics', 'Performance optimization', 'Security hardening'],
                        'actions' => ['Implement compliance checks', 'Add analytics engine', 'Performance tuning', 'Security audit']
                    ],
                    [
                        'title' => 'Phase 4: Launch & Scale (Weeks 21-24)',
                        'description' => 'Production deployment and market release',
                        'touchpoints' => ['Production environment', 'Monitoring setup', 'Marketing campaign', 'Customer support training'],
                        'actions' => ['Deploy to production', 'Set up monitoring', 'Launch marketing', 'Begin customer onboarding']
                    ]
                ]
            ]
        ],
        'ar' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'المرحلة 1: الأساس (الأسابيع 1-6)',
                        'description' => 'هندسة المنصة الأساسية ومحرك تقييم الذكاء الاصطناعي',
                        'touchpoints' => ['إعداد الفريق', 'إنهاء المكدس التكنولوجي', 'تصميم قاعدة البيانات', 'إعداد إطار العمل API'],
                        'actions' => ['إنشاء بيئة التطوير', 'إنشاء خط أنابيب CI/CD', 'بناء وحدة التقييم الأساسية', 'إطار الاختبار الأولي']
                    ],
                    [
                        'title' => 'المرحلة 2: الميزات الأساسية (الأسابيع 7-14)',
                        'description' => 'لوحة معلومات النضج الرقمي ومولد خريطة الطريق',
                        'touchpoints' => ['تطوير واجهة المستخدم', 'تكامل نماذج الذكاء الاصطناعي', 'تصميم لوحة المعلومات', 'بدء اختبار المستخدم'],
                        'actions' => ['بناء واجهة لوحة المعلومات', 'دمج نماذج الذكاء الاصطناعي', 'إنشاء منطق توليد خريطة الطريق', 'إدراج المستخدمين التجريبيين']
                    ],
                    [
                        'title' => 'المرحلة 3: التحسين (الأسابيع 15-20)',
                        'description' => 'رؤية 2030 والامتثال والتحليلات المتقدمة',
                        'touchpoints' => ['إطلاق وحدة الامتثال', 'التحليلات المتقدمة', 'تحسين الأداء', 'تقوية الأمان'],
                        'actions' => ['تطبيق فحوصات الامتثال', 'إضافة محرك التحليلات', 'ضبط الأداء', 'تدقيق الأمان']
                    ],
                    [
                        'title' => 'المرحلة 4: الإطلاق والتوسع (الأسابيع 21-24)',
                        'description' => 'نشر الإنتاج وإطلاق السوق',
                        'touchpoints' => ['بيئة الإنتاج', 'إعداد المراقبة', 'حملة التسويق', 'تدريب دعم العملاء'],
                        'actions' => ['النشر للإنتاج', 'إعداد المراقبة', 'إطلاق التسويق', 'بدء إدراج العملاء']
                    ]
                ]
            ]
        ]
    ],

    'mvp_tech_stack' => [
        'en' => [
            'key_value' => [
                'items' => [
                    ['key' => 'Backend', 'value' => 'Laravel 10, Node.js with Express'],
                    ['key' => 'Frontend', 'value' => 'React 18, Vue.js 3'],
                    ['key' => 'Database', 'value' => 'PostgreSQL, MongoDB'],
                    ['key' => 'AI/ML', 'value' => 'TensorFlow, OpenAI API, LangChain'],
                    ['key' => 'Cloud Infrastructure', 'value' => 'AWS, Microsoft Azure'],
                    ['key' => 'DevOps', 'value' => 'Docker, Kubernetes, GitHub Actions'],
                    ['key' => 'Analytics', 'value' => 'Mixpanel, DataDog'],
                    ['key' => 'Real-time Communication', 'value' => 'WebSocket, Socket.io'],
                ]
            ]
        ],
        'ar' => [
            'key_value' => [
                'items' => [
                    ['key' => 'الخادم الخلفي', 'value' => 'Laravel 10، Node.js مع Express'],
                    ['key' => 'واجهة المستخدم', 'value' => 'React 18، Vue.js 3'],
                    ['key' => 'قاعدة البيانات', 'value' => 'PostgreSQL، MongoDB'],
                    ['key' => 'الذكاء الاصطناعي والتعلم الآلي', 'value' => 'TensorFlow، OpenAI API، LangChain'],
                    ['key' => 'البنية التحتية السحابية', 'value' => 'AWS، Microsoft Azure'],
                    ['key' => 'DevOps', 'value' => 'Docker، Kubernetes، GitHub Actions'],
                    ['key' => 'التحليلات', 'value' => 'Mixpanel، DataDog'],
                    ['key' => 'الاتصال في الوقت الفعلي', 'value' => 'WebSocket، Socket.io'],
                ]
            ]
        ]
    ],

    'mvp_resource_requirements' => [
        'en' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'Development Team', 'value' => '12', 'description' => 'Backend, Frontend, QA, DevOps engineers'],
                    ['label' => 'AI/ML Specialists', 'value' => '3', 'description' => 'ML engineers, Data scientists'],
                    ['label' => 'Design & UX', 'value' => '2', 'description' => 'UI/UX designers'],
                    ['label' => 'Product Manager', 'value' => '1', 'description' => 'Product strategy and roadmap'],
                    ['label' => 'Budget (USD)', 'value' => '450K', 'description' => 'Total 6-month MVP development'],
                    ['label' => 'Infrastructure Cost', 'value' => '15K', 'description' => 'Monthly cloud and AI API costs'],
                ]
            ]
        ],
        'ar' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'فريق التطوير', 'value' => '12', 'description' => 'مهندسو الخادم الخلفي، الواجهة الأمامية، ضمان الجودة، DevOps'],
                    ['label' => 'متخصصو الذكاء الاصطناعي والتعلم الآلي', 'value' => '3', 'description' => 'مهندسو التعلم الآلي، علماء البيانات'],
                    ['label' => 'التصميم وتجربة المستخدم', 'value' => '2', 'description' => 'مصممو واجهة المستخدم / تجربة المستخدم'],
                    ['label' => 'مدير المنتج', 'value' => '1', 'description' => 'استراتيجية المنتج وخريطة الطريق'],
                    ['label' => 'الميزانية (USD)', 'value' => '450K', 'description' => 'إجمالي تطوير MVP لمدة 6 أشهر'],
                    ['label' => 'تكلفة البنية التحتية', 'value' => '15K', 'description' => 'تكاليف الخدمات السحابية و API الشهرية'],
                ]
            ]
        ]
    ],

    'mvp_risk_mitigation' => [
        'en' => [
            'text_content' => [
                'title' => 'MVP Risk Mitigation Strategy',
                'sections' => [
                    [
                        'heading' => 'Technical Risk Management',
                        'content' => 'Mitigate AI model accuracy risks through extensive testing and validation against Saudi market scenarios. Implement robust API fallbacks and circuit breakers. Maintain multiple cloud provider relationships to avoid vendor lock-in. Regular security audits and penetration testing to address cybersecurity threats.'
                    ],
                    [
                        'heading' => 'Market & Adoption Risk',
                        'content' => 'Conduct early market validation through pilot programs with select Saudi enterprises. Establish advisory board of Saudi decision-makers. Build flexible pricing models that align with Vision 2030 initiatives. Create localized marketing materials in Arabic targeting government and private sectors.'
                    ],
                    [
                        'heading' => 'Resource & Timeline Risk',
                        'content' => 'Build contingency planning into project timeline with 20% buffer for critical phases. Maintain backup resources for key technical roles. Implement agile methodology with bi-weekly sprints to enable course correction. Establish clear success metrics for each phase gate.'
                    ],
                    [
                        'heading' => 'Regulatory & Compliance Risk',
                        'content' => 'Engage with Saudi regulatory bodies early for data protection and AI governance compliance. Implement data residency requirements for Saudi market. Build compliance frameworks aligned with MISA guidelines. Maintain audit trails and documentation for all AI decision-making processes.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'استراتيجية تخفيف المخاطر للمنتج الحد الأدنى القابل للتطبيق',
                'sections' => [
                    [
                        'heading' => 'إدارة المخاطر التقنية',
                        'content' => 'تخفيف مخاطر دقة نموذج الذكاء الاصطناعي من خلال الاختبار الشامل والتحقق من سيناريوهات السوق السعودي. تطبيق آليات الرجوع القوية للواجهات البرمجية وقواطع الدوائر. الحفاظ على علاقات موفري الخدمات السحابية المتعددة لتجنب الارتباط بالبائع. عمليات التدقيق الأمني المنتظمة واختبار الاختراق لمعالجة تهديدات الأمن السيبراني.'
                    ],
                    [
                        'heading' => 'مخاطر السوق والتبني',
                        'content' => 'إجراء التحقق المبكر من السوق من خلال برامج التجريب مع الشركات السعودية المختارة. إنشاء مجلس استشاري من صناع القرار السعوديين. بناء نماذج تسعير مرنة تتوافق مع مبادرات رؤية 2030. إنشاء مواد تسويقية محلية باللغة العربية تستهدف القطاعات الحكومية والخاصة.'
                    ],
                    [
                        'heading' => 'مخاطر الموارد والجدول الزمني',
                        'content' => 'بناء التخطيط الطارئ في الجدول الزمني للمشروع مع هامش بنسبة 20٪ للمراحل الحرجة. الحفاظ على الموارد الاحتياطية للأدوار التقنية الرئيسية. تطبيق منهجية Agile مع الفترات ثنائية الأسبوعية لتمكين التصحيح. إنشاء مقاييس النجاح الواضحة لكل مرحلة بوابة.'
                    ],
                    [
                        'heading' => 'المخاطر التنظيمية والالتزامية',
                        'content' => 'الانخراط مع الجهات التنظيمية السعودية مبكراً لامتثال حماية البيانات وحوكمة الذكاء الاصطناعي. تطبيق متطلبات إقامة البيانات لسوق السعودية. بناء أطر الامتثال المتوافقة مع إرشادات MISA. الحفاظ على مسارات التدقيق والتوثيق لجميع عمليات اتخاذ القرار بالذكاء الاصطناعي.'
                    ]
                ]
            ]
        ]
    ],

    'usp_unique_selling_points' => [
        'en' => [
            'text_content' => [
                'title' => 'Unique Selling Points',
                'sections' => [
                    [
                        'heading' => 'AI-Powered Digital Transformation',
                        'content' => 'Boud Platform leverages advanced AI and machine learning to automate and optimize digital transformation processes. Our proprietary algorithms analyze organizational DNA to provide personalized transformation roadmaps aligned with enterprise goals and Saudi Vision 2030 objectives.'
                    ],
                    [
                        'heading' => 'Vision 2030 Alignment',
                        'content' => 'Purpose-built compliance and impact measurement frameworks ensure all recommendations and implementations directly support Saudi Vision 2030 pillars. Unique integration with government digitalization standards and economic diversification initiatives makes Boud the preferred partner for Saudi enterprises.'
                    ],
                    [
                        'heading' => 'Integrated Ecosystem',
                        'content' => 'Boud serves as parent company to specialized innovation platforms (Hackify, SALIS, Connect AI) creating a comprehensive ecosystem. Clients benefit from seamless integration of consulting services with cutting-edge innovation tools, reducing complexity and accelerating time-to-value.'
                    ],
                    [
                        'heading' => 'Comprehensive Service Delivery',
                        'content' => 'From assessment and strategy to implementation and ongoing optimization, Boud provides end-to-end support. Our consulting experts work alongside software tools to ensure successful adoption, change management, and sustained digital excellence.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'نقاط البيع الفريدة',
                'sections' => [
                    [
                        'heading' => 'التحول الرقمي بتقنية الذكاء الاصطناعي',
                        'content' => 'تستفيد منصة Boud من الذكاء الاصطناعي المتقدم والتعلم الآلي لأتمتة وتحسين عمليات التحول الرقمي. تحلل خوارزمياتنا الملكية الحمض النووي التنظيمي لتقديم خرائط طريق تحول مخصصة متوافقة مع أهداف الشركة ورؤية السعودية 2030.'
                    ],
                    [
                        'heading' => 'توافق رؤية 2030',
                        'content' => 'تضمن أطر الامتثال وقياس التأثير المصممة خصيصاً أن جميع التوصيات والتطبيقات تدعم بشكل مباشر أعمدة رؤية السعودية 2030. يجعل التكامل الفريد مع معايير الرقمنة الحكومية ومبادرات التنويع الاقتصادي Boud الشريك المفضل للشركات السعودية.'
                    ],
                    [
                        'heading' => 'النظام البيئي المتكامل',
                        'content' => 'تعمل Boud كشركة أم لمنصات الابتكار المتخصصة (Hackify و SALIS و Connect AI) مما يخلق نظاماً بيئياً شاملاً. يستفيد العملاء من التكامل السلس بين خدمات الاستشارات والأدوات الابتكار المتطورة، مما يقلل التعقيد ويسرع الوقت لتحقيق القيمة.'
                    ],
                    [
                        'heading' => 'تقديم الخدمات الشامل',
                        'content' => 'من التقييم والاستراتيجية إلى التطبيق والتحسين المستمر، تقدم Boud الدعم الشامل. يعمل خبراء الاستشارات لدينا جنباً إلى جنب مع أدوات البرمجيات لضمان النجاح في التبني وإدارة التغيير والتميز الرقمي المستدام.'
                    ]
                ]
            ]
        ]
    ],

    'usp_competitive_advantage' => [
        'en' => [
            'comparison_table' => [
                'headers' => ['Criteria', 'Boud Platform', 'Traditional Consultants', 'Generic SaaS Tools'],
                'rows' => [
                    ['AI-Powered Assessment', 'Advanced algorithms + human expertise', 'Manual assessment only', 'Basic analysis'],
                    ['Vision 2030 Alignment', 'Native compliance & impact tracking', 'Custom implementation needed', 'Not designed for KSA'],
                    ['Integrated Tools', 'Hackify, SALIS, Connect AI included', 'External tool dependencies', 'Feature-limited'],
                    ['Implementation Support', 'Full consulting + software support', 'Engagement-based only', 'Self-service only'],
                    ['Time-to-Value', '3-6 months', '6-12 months', '2-4 months (limited)',
                    ['Cost Structure', 'Flexible SaaS + advisory pricing', 'High fixed costs', 'Subscription only'],
                    ['Local Expertise', 'Saudi Vision 2030 specialized team', 'General global approach', 'No local focus'],
                ]
            ]
        ],
        'ar' => [
            'comparison_table' => [
                'headers' => ['المعايير', 'منصة Boud', 'استشاريون تقليديون', 'أدوات SaaS عامة'],
                'rows' => [
                    ['التقييم المدعوم بالذكاء الاصطناعي', 'خوارزميات متقدمة + خبرة بشرية', 'التقييم اليدوي فقط', 'تحليل أساسي'],
                    ['توافق رؤية 2030', 'الامتثال الأصلي وتتبع التأثير', 'التطبيق المخصص مطلوب', 'غير مصمم للمملكة'],
                    ['الأدوات المتكاملة', 'تتضمن Hackify و SALIS و Connect AI', 'اعتماديات الأدوات الخارجية', 'محدود الميزات'],
                    ['دعم التطبيق', 'الاستشارات الكاملة ودعم البرمجيات', 'دعم مبني على الالتزام فقط', 'خدمة ذاتية فقط'],
                    ['الوقت لتحقيق القيمة', '3-6 أشهر', '6-12 شهر', '2-4 أشهر (محدود)'],
                    ['هيكل التكلفة', 'تسعير SaaS المرن والاستشارات', 'تكاليف ثابتة عالية', 'الاشتراك فقط'],
                    ['الخبرة المحلية', 'فريق متخصص في رؤية السعودية 2030', 'نهج عام عالمي', 'لا يوجد تركيز محلي'],
                ]
            ]
        ]
    ],

    'usp_differentiation_strategy' => [
        'en' => [
            'text_content' => [
                'title' => 'Differentiation Strategy',
                'sections' => [
                    [
                        'heading' => 'Technology Differentiation',
                        'content' => 'Invest heavily in proprietary AI models trained on Saudi organizational data. Build unique assessment frameworks combining industry benchmarks with Vision 2030 KPIs. Develop advanced predictive analytics for transformation success probability. Maintain continuous innovation through AI model updates and emerging technology integration.'
                    ],
                    [
                        'heading' => 'Market Positioning Differentiation',
                        'content' => 'Position Boud as the leading AI-powered partner for Saudi digital transformation. Emphasize Vision 2030 alignment and government partnership credentials. Build brand through successful case studies with marquee Saudi enterprises. Establish thought leadership through research publications and industry speaking engagements.'
                    ],
                    [
                        'heading' => 'Service Delivery Differentiation',
                        'content' => 'Combine consulting expertise with software empowerment unlike competitors. Provide dedicated transformation advisors paired with AI tools for each client. Offer outcome-based pricing tied to transformation KPI achievement. Deliver continuous support through advisory board and innovation updates.'
                    ],
                    [
                        'heading' => 'Ecosystem Differentiation',
                        'content' => 'Leverage parent company ecosystem (Hackify for innovation management, SALIS for AI solutions, Connect AI for collaboration). Provide seamless integration across platforms reducing tool complexity. Create unique value through cross-product recommendations. Build network effects as more customers use the integrated ecosystem.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'استراتيجية التمايز',
                'sections' => [
                    [
                        'heading' => 'تمايز التكنولوجيا',
                        'content' => 'استثمر بكثافة في نماذج الذكاء الاصطناعي الملكية المدربة على بيانات المنظمات السعودية. بناء أطر تقييم فريدة تجمع بين معايير الصناعة ومؤشرات رؤية 2030. تطوير تحليلات تنبؤية متقدمة لاحتمالية نجاح التحول. الحفاظ على الابتكار المستمر من خلال تحديثات نموذج الذكاء الاصطناعي وتكامل التكنولوجيا الناشئة.'
                    ],
                    [
                        'heading' => 'تمايز تحديد موضع السوق',
                        'content' => 'وضع Boud كشريك رائد مدعوم بالذكاء الاصطناعي للتحول الرقمي السعودي. التأكيد على توافق رؤية 2030 وأوراق اعتماد الشراكة الحكومية. بناء العلامة التجارية من خلال دراسات الحالة الناجحة مع الشركات السعودية المعروفة. إنشاء الفكر القيادي من خلال منشورات البحث والمشاركة في المؤتمرات الصناعية.'
                    ],
                    [
                        'heading' => 'تمايز تسليم الخدمات',
                        'content' => 'دمج خبرة الاستشارات مع تمكين البرمجيات على عكس المنافسين. توفير مستشاري تحول مخصصين مقترنين بأدوات الذكاء الاصطناعي لكل عميل. تقديم تسعير قائم على النتائج مرتبط بتحقيق KPI التحول. تقديم دعم مستمر من خلال مجلس استشاري وتحديثات الابتكار.'
                    ],
                    [
                        'heading' => 'تمايز النظام البيئي',
                        'content' => 'الاستفادة من النظام البيئي لشركة الأم (Hackify لإدارة الابتكار و SALIS لحلول الذكاء الاصطناعي و Connect AI للتعاون). توفير تكامل سلس عبر المنصات تقليل تعقيد الأدوات. إنشاء قيمة فريدة من خلال توصيات المنتجات المتقاطعة. بناء تأثيرات الشبكة مع استخدام المزيد من العملاء للنظام البيئي المتكامل.'
                    ]
                ]
            ]
        ]
    ],

    'usp_value_chain' => [
        'en' => [
            'key_value' => [
                'items' => [
                    ['key' => 'Assessment & Strategy', 'value' => 'AI-powered digital maturity evaluation + customized transformation roadmap'],
                    ['key' => 'Technology Implementation', 'value' => 'Full-stack modernization with cloud migration and system integration'],
                    ['key' => 'AI & Automation', 'value' => 'Process automation, predictive analytics, and intelligent decision support'],
                    ['key' => 'Change Management', 'value' => 'Organizational alignment, training programs, and adoption support'],
                    ['key' => 'Continuous Optimization', 'value' => 'Performance monitoring, AI model updates, and innovation integration'],
                    ['key' => 'Advisory Support', 'value' => 'Ongoing strategic guidance through dedicated transformation advisor'],
                    ['key' => 'Innovation Ecosystem', 'value' => 'Access to Hackify, SALIS, Connect AI for extended capabilities'],
                ]
            ]
        ],
        'ar' => [
            'key_value' => [
                'items' => [
                    ['key' => 'التقييم والاستراتيجية', 'value' => 'تقييم النضج الرقمي المدعوم بالذكاء الاصطناعي + خريطة طريق تحول مخصصة'],
                    ['key' => 'تطبيق التكنولوجيا', 'value' => 'الحداثة الشاملة مع هجرة السحابة وتكامل الأنظمة'],
                    ['key' => 'الذكاء الاصطناعي والأتمتة', 'value' => 'أتمتة العمليات والتحليلات التنبؤية ودعم القرار الذكي'],
                    ['key' => 'إدارة التغيير', 'value' => 'المحاذاة التنظيمية وبرامج التدريب ودعم التبني'],
                    ['key' => 'التحسين المستمر', 'value' => 'مراقبة الأداء وتحديثات نموذج الذكاء الاصطناعي وتكامل الابتكار'],
                    ['key' => 'الدعم الاستشاري', 'value' => 'التوجيه الاستراتيجي المستمر من خلال مستشار تحول مخصص'],
                    ['key' => 'نظام الابتكار البيئي', 'value' => 'الوصول إلى Hackify و SALIS و Connect AI للإمكانيات الموسعة'],
                ]
            ]
        ]
    ],

    'cp_primary_persona' => [
        'en' => [
            'persona_card' => [
                'name' => 'Fatima Al-Dosari',
                'role' => 'Chief Digital Officer',
                'age' => 42,
                'location' => 'Riyadh, Saudi Arabia',
                'quote' => 'We need to transform quickly to meet Vision 2030 targets, but our legacy systems are holding us back. We need a partner who understands both our challenges and the regulatory landscape.',
                'demographics' => [
                    'company_size' => 'Large Enterprise (5000+ employees)',
                    'industry' => 'Financial Services',
                    'education' => 'MBA in Information Systems',
                    'years_in_role' => 5,
                    'salary_range' => '$150K-$200K'
                ],
                'pain_points' => [
                    'Legacy system modernization complexity',
                    'Pressure to meet Vision 2030 KPIs',
                    'Difficulty finding AI/digital transformation expertise',
                    'Budget constraints despite transformation urgency',
                    'Change management across traditional organizational structure',
                    'Regulatory compliance in rapidly evolving digital landscape'
                ],
                'goals' => [
                    'Reduce digital transformation timeline from 24 months to 6-9 months',
                    'Achieve Vision 2030 alignment within first year',
                    'Improve customer experience through digital channels by 40%',
                    'Build internal digital capabilities and culture',
                    'Reduce operational costs through automation',
                    'Secure board approval for transformation budget'
                ],
                'motivations' => [
                    'Career advancement through successful digital transformation',
                    'Organizational competitiveness in evolving market',
                    'Personal legacy of modernizing enterprise',
                    'Alignment with Saudi Vision 2030 objectives',
                    'Pressure from C-suite and board for measurable results'
                ]
            ]
        ],
        'ar' => [
            'persona_card' => [
                'name' => 'فاطمة الدوسري',
                'role' => 'كبير مسؤولي الرقميات',
                'age' => 42,
                'location' => 'الرياض، المملكة العربية السعودية',
                'quote' => 'نحتاج إلى التحول بسرعة لتحقيق أهداف رؤية 2030، لكن أنظمتنا القديمة تعيقنا. نحتاج إلى شريك يفهم تحدياتنا والمشهد التنظيمي.',
                'demographics' => [
                    'company_size' => 'مؤسسة كبيرة (5000+ موظف)',
                    'industry' => 'الخدمات المالية',
                    'education' => 'ماجستير إدارة الأعمال في نظم المعلومات',
                    'years_in_role' => 5,
                    'salary_range' => '$150K-$200K'
                ],
                'pain_points' => [
                    'تعقيد تحديث الأنظمة القديمة',
                    'الضغط لتحقيق مؤشرات رؤية 2030',
                    'صعوبة إيجاد خبرة في الذكاء الاصطناعي والتحول الرقمي',
                    'قيود الميزانية رغم إلحاح التحول',
                    'إدارة التغيير عبر الهياكل التنظيمية التقليدية',
                    'الامتثال التنظيمي في المشهد الرقمي سريع التطور'
                ],
                'goals' => [
                    'تقليل جدول زمني للتحول الرقمي من 24 شهر إلى 6-9 أشهر',
                    'تحقيق توافق رؤية 2030 في السنة الأولى',
                    'تحسين تجربة العملاء من خلال القنوات الرقمية بنسبة 40٪',
                    'بناء القدرات الرقمية والثقافة الداخلية',
                    'تقليل تكاليف التشغيل من خلال الأتمتة',
                    'الحصول على موافقة المجلس على ميزانية التحول'
                ],
                'motivations' => [
                    'التقدم الوظيفي من خلال التحول الرقمي الناجح',
                    'تنافسية المنظمة في السوق المتطورة',
                    'الإرث الشخصي لتحديث المؤسسة',
                    'التوافق مع أهداف رؤية السعودية 2030',
                    'الضغط من الإدارة العليا والمجلس للحصول على نتائج ملموسة'
                ]
            ]
        ]
    ],

    'cp_secondary_persona' => [
        'en' => [
            'persona_card' => [
                'name' => 'Ahmed Al-Shammari',
                'role' => 'VP of Innovation & Technology',
                'age' => 38,
                'location' => 'Jeddah, Saudi Arabia',
                'quote' => 'Our technical team can build anything, but we struggle with strategy and vision alignment. We need a partner to guide our innovation roadmap.',
                'demographics' => [
                    'company_size' => 'Mid-to-Large Enterprise (2000-5000 employees)',
                    'industry' => 'Retail & E-commerce',
                    'education' => 'BS in Computer Science, Executive Leadership Program',
                    'years_in_role' => 3,
                    'salary_range' => '$120K-$160K'
                ],
                'pain_points' => [
                    'Translating business strategy into technical roadmaps',
                    'Justifying technology investments to finance leadership',
                    'Keeping pace with rapidly evolving AI and cloud technologies',
                    'Recruiting and retaining top technical talent',
                    'Balancing innovation with operational stability',
                    'Managing technical debt while pursuing new initiatives'
                ],
                'goals' => [
                    'Develop comprehensive innovation strategy aligned with business objectives',
                    'Build or acquire AI capabilities within 12 months',
                    'Establish cloud-native architecture',
                    'Implement agile transformation across technology organization',
                    'Reduce time-to-market for new digital products',
                    'Create internal technical thought leadership'
                ],
                'motivations' => [
                    'Proving ROI of technology investments',
                    'Building world-class technical organization',
                    'Making competitive impact through innovation',
                    'Technical advancement and emerging tech adoption',
                    'Recognition as innovation leader in industry'
                ]
            ]
        ],
        'ar' => [
            'persona_card' => [
                'name' => 'أحمد الشمري',
                'role' => 'نائب رئيس الابتكار والتكنولوجيا',
                'age' => 38,
                'location' => 'جدة، المملكة العربية السعودية',
                'quote' => 'فريقنا التقني يمكنه بناء أي شيء، لكننا نكافح مع الاستراتيجية والمحاذاة الرؤية. نحتاج إلى شريك لتوجيه خريطة طريق الابتكار لدينا.',
                'demographics' => [
                    'company_size' => 'مؤسسة متوسطة إلى كبيرة (2000-5000 موظف)',
                    'industry' => 'البيع بالتجزئة والتجارة الإلكترونية',
                    'education' => 'درجة البكالوريوس في علوم الحاسب الآلي، برنامج القيادة التنفيذية',
                    'years_in_role' => 3,
                    'salary_range' => '$120K-$160K'
                ],
                'pain_points' => [
                    'ترجمة استراتيجية الأعمال إلى خرائط طريق تقنية',
                    'تبرير استثمارات التكنولوجيا لقيادة المالية',
                    'مواكبة تطور الذكاء الاصطناعي والتقنيات السحابية',
                    'استقطاب والاحتفاظ بأفضل المواهب التقنية',
                    'توازن الابتكار مع الاستقرار التشغيلي',
                    'إدارة الديون التقنية مع متابعة المبادرات الجديدة'
                ],
                'goals' => [
                    'تطوير استراتيجية ابتكار شاملة متوافقة مع أهداف الأعمال',
                    'بناء أو الاستحواذ على قدرات الذكاء الاصطناعي في غضون 12 شهر',
                    'إنشاء معمارية سحابية أصلية',
                    'تطبيق تحول رشيق عبر المنظمة التقنية',
                    'تقليل الوقت المستغرق لإصدار منتجات رقمية جديدة',
                    'إنشاء قيادة فكرية تقنية داخلية'
                ],
                'motivations' => [
                    'إثبات العائد على الاستثمار في التكنولوجيا',
                    'بناء منظمة تقنية عالمية المستوى',
                    'تحقيق تأثير تنافسي من خلال الابتكار',
                    'التقدم التقني واعتماد التكنولوجيا الناشئة',
                    'الاعتراف كقائد ابتكار في الصناعة'
                ]
            ]
        ]
    ],

    'cp_buyer_journey' => [
        'en' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'Awareness Stage (Weeks 1-4)',
                        'description' => 'Decision-maker recognizes need for digital transformation',
                        'touchpoints' => ['Industry reports and market research', 'Thought leadership content', 'Industry conferences', 'Peer recommendations'],
                        'actions' => ['Identify problem through business metrics', 'Research available solutions', 'Define transformation objectives']
                    ],
                    [
                        'title' => 'Consideration Stage (Weeks 5-12)',
                        'description' => 'Evaluation of transformation partners and approaches',
                        'touchpoints' => ['Solution demos and trials', 'Case studies and ROI calculators', 'Reference customer calls', 'Detailed feature comparisons'],
                        'actions' => ['Issue RFP to potential vendors', 'Conduct capability assessments', 'Evaluate pricing models', 'Build business case']
                    ],
                    [
                        'title' => 'Decision Stage (Weeks 13-16)',
                        'description' => 'Final vendor selection and contract negotiation',
                        'touchpoints' => ['Vendor presentations to C-suite', 'Legal and contract review', 'Budget allocation decisions', 'Board approval process'],
                        'actions' => ['Negotiate contract terms', 'Finalize pricing and scope', 'Approve project charter', 'Establish governance']
                    ],
                    [
                        'title' => 'Implementation Stage (Months 4-12)',
                        'description' => 'Active transformation execution',
                        'touchpoints' => ['Weekly steering committee meetings', 'Milestone reviews', 'Stakeholder training sessions', 'Progress dashboards'],
                        'actions' => ['Launch transformation program', 'Establish transformation office', 'Deliver quick wins', 'Manage organizational change']
                    ]
                ]
            ]
        ],
        'ar' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'مرحلة الوعي (الأسابيع 1-4)',
                        'description' => 'يدرك صاحب القرار الحاجة للتحول الرقمي',
                        'touchpoints' => ['تقارير الصناعة والبحث عن السوق', 'محتوى القيادة الفكرية', 'مؤتمرات الصناعة', 'توصيات الأقران'],
                        'actions' => ['تحديد المشكلة من خلال مقاييس الأعمال', 'البحث عن الحلول المتاحة', 'تحديد أهداف التحول']
                    ],
                    [
                        'title' => 'مرحلة الاعتبار (الأسابيع 5-12)',
                        'description' => 'تقييم شركاء وأساليب التحول',
                        'touchpoints' => ['عروض توضيحية وتجارب للحل', 'دراسات الحالات وآلات حساب العائد على الاستثمار', 'استدعاءات العملاء المرجعيين', 'مقارنات الميزات التفصيلية'],
                        'actions' => ['إصدار RFP للبائعين المحتملين', 'إجراء تقييمات القدرات', 'تقييم نماذج التسعير', 'بناء حالة العمل']
                    ],
                    [
                        'title' => 'مرحلة القرار (الأسابيع 13-16)',
                        'description' => 'الاختيار النهائي للبائع والتفاوض على العقد',
                        'touchpoints' => ['عروض البائع للمسؤولين التنفيذيين', 'مراجعة قانونية وعقدية', 'قرارات تخصيص الميزانية', 'عملية موافقة المجلس'],
                        'actions' => ['التفاوض على شروط العقد', 'إنهاء التسعير والنطاق', 'الموافقة على ميثاق المشروع', 'إنشاء الحوكمة']
                    ],
                    [
                        'title' => 'مرحلة التطبيق (الأشهر 4-12)',
                        'description' => 'تنفيذ التحول النشط',
                        'touchpoints' => ['اجتماعات لجنة التوجيه الأسبوعية', 'استعراضات المعالم', 'جلسات تدريب أصحاب المصلحة', 'لوحات معلومات التقدم'],
                        'actions' => ['إطلاق برنامج التحول', 'إنشاء مكتب التحول', 'تقديم الانتصارات السريعة', 'إدارة التغيير التنظيمي']
                    ]
                ]
            ]
        ]
    ],

    'cp_pain_points_analysis' => [
        'en' => [
            'text_content' => [
                'title' => 'Customer Pain Points Analysis',
                'sections' => [
                    [
                        'heading' => 'Strategic Challenges',
                        'content' => 'Saudi enterprises struggle to translate Vision 2030 objectives into concrete transformation strategies. Most lack clear understanding of which digital capabilities matter most for their business model. Executive teams face pressure for rapid transformation but lack frameworks to prioritize initiatives and allocate resources effectively.'
                    ],
                    [
                        'heading' => 'Organizational & Talent Gaps',
                        'content' => 'Digital transformation expertise is scarce in Saudi market. Organizations lack in-house AI and advanced analytics capabilities. Recruiting expatriate talent faces visa and regulatory challenges. Internal teams often resist change due to lack of understanding and involvement in transformation planning.'
                    ],
                    [
                        'heading' => 'Technology & Integration Issues',
                        'content' => 'Legacy systems create significant modernization challenges and integration complexities. Cloud migration requires substantial capital investment and operational disruption. Organizations lack clear technology roadmaps aligned with business objectives. Multiple disparate tools create inefficiencies and prevent holistic digital capability.'
                    ],
                    [
                        'heading' => 'Financial & ROI Uncertainties',
                        'content' => 'Transformation budgets face scrutiny without clear business case and ROI projections. Hidden costs and scope creep become major issues without structured governance. Difficulty quantifying digital transformation benefits delays investment approval. CFOs demand measurable outcomes tied to Vision 2030 KPIs.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'تحليل نقاط الألم لدى العملاء',
                'sections' => [
                    [
                        'heading' => 'التحديات الاستراتيجية',
                        'content' => 'تكافح الشركات السعودية لترجمة أهداف رؤية 2030 إلى استراتيجيات تحول ملموسة. تفتقد معظمها فهماً واضحاً للقدرات الرقمية التي تهم أكثر لنموذج أعمالها. تواجه الفرق التنفيذية ضغوطاً للتحول السريع لكنها تفتقد أطر العمل لتحديد أولويات المبادرات وتخصيص الموارد بفعالية.'
                    ],
                    [
                        'heading' => 'فجوات الموارد البشرية والمنظمة',
                        'content' => 'خبرة التحول الرقمي نادرة في السوق السعودية. تفتقد المنظمات القدرات الداخلية للذكاء الاصطناعي والتحليلات المتقدمة. يواجه استقطاب الموهبة الأجنبية تحديات تأشيرة وتنظيمية. غالباً ما تقاوم الفرق الداخلية التغيير بسبب نقص الفهم والمشاركة في تخطيط التحول.'
                    ],
                    [
                        'heading' => 'مشاكل التكنولوجيا والتكامل',
                        'content' => 'الأنظمة القديمة تخلق تحديات حداثة كبيرة وتعقيدات التكامل. هجرة السحابة تتطلب استثمار رأس مال كبير واضطراب تشغيلي. تفتقد المنظمات خرائط طريق تقنية واضحة متوافقة مع أهداف الأعمال. أدوات متعددة غير متجانسة تخلق عدم كفاءة وتمنع القدرة الرقمية الشاملة.'
                    ],
                    [
                        'heading' => 'عدم اليقين المالي والعائد على الاستثمار',
                        'content' => 'تواجه ميزانيات التحول التدقيق بدون حالة عمل واضحة وتنبؤات العائد على الاستثمار. التكاليف المخفية والزحف النطاق تصبح مشاكل كبيرة بدون حوكمة منظمة. صعوبة قياس فوائد التحول الرقمي تؤخر موافقة الاستثمار. يطالب مديرو المالية بنتائج قابلة للقياس مرتبطة بمؤشرات رؤية 2030.'
                    ]
                ]
            ]
        ]
    ],

    'fin_revenue_model' => [
        'en' => [
            'pricing_cards' => [
                'tiers' => [
                    [
                        'name' => 'Starter',
                        'price' => '$15K/mo',
                        'features' => ['Digital maturity assessment', 'Transformation roadmap (basic)', 'Dashboard access (read-only)', 'Monthly consulting hours (4)', 'Email support'],
                        'highlighted' => false,
                        'cta' => 'Start Assessment'
                    ],
                    [
                        'name' => 'Professional',
                        'price' => '$45K/mo',
                        'features' => ['All Starter features', 'Advanced AI assessment', 'Customized roadmap generation', 'Full dashboard access', 'Monthly consulting hours (16)', 'Integration with one tool', 'Priority email & phone support'],
                        'highlighted' => true,
                        'cta' => 'Start Transformation'
                    ],
                    [
                        'name' => 'Enterprise',
                        'price' => 'Custom',
                        'features' => ['All Professional features', 'Dedicated transformation advisor', 'Full ecosystem integration (Hackify, SALIS, Connect AI)', 'Unlimited consulting hours', 'Custom AI model training', 'On-site implementation support', '24/7 phone & video support', 'Quarterly business reviews'],
                        'highlighted' => false,
                        'cta' => 'Schedule Demo'
                    ]
                ]
            ]
        ],
        'ar' => [
            'pricing_cards' => [
                'tiers' => [
                    [
                        'name' => 'مبتدئ',
                        'price' => '$15K/mo',
                        'features' => ['تقييم النضج الرقمي', 'خريطة طريق التحول (أساسية)', 'وصول لوحة المعلومات (قراءة فقط)', 'ساعات الاستشارة الشهرية (4)', 'دعم البريد الإلكتروني'],
                        'highlighted' => false,
                        'cta' => 'ابدأ التقييم'
                    ],
                    [
                        'name' => 'احترافي',
                        'price' => '$45K/mo',
                        'features' => ['جميع ميزات Starter', 'التقييم المتقدم بالذكاء الاصطناعي', 'توليد خريطة الطريق المخصصة', 'الوصول الكامل إلى لوحة المعلومات', 'ساعات الاستشارة الشهرية (16)', 'التكامل مع أداة واحدة', 'دعم البريد الإلكتروني والهاتف الأولوي'],
                        'highlighted' => true,
                        'cta' => 'ابدأ التحول'
                    ],
                    [
                        'name' => 'مؤسسة',
                        'price' => 'مخصص',
                        'features' => ['جميع ميزات Professional', 'مستشار تحول مخصص', 'تكامل النظام البيئي الكامل (Hackify و SALIS و Connect AI)', 'ساعات استشارة غير محدودة', 'تدريب نموذج الذكاء الاصطناعي المخصص', 'دعم التطبيق على الموقع', 'دعم الهاتف والفيديو 24/7', 'الاستعراضات الفصلية للأعمال'],
                        'highlighted' => false,
                        'cta' => 'جدولة عرض توضيحي'
                    ]
                ]
            ]
        ]
    ],

    'fin_cost_structure' => [
        'en' => [
            'progress_bars' => [
                'items' => [
                    ['label' => 'Technology & Infrastructure', 'value' => 35, 'suffix' => '%'],
                    ['label' => 'Personnel & Salaries', 'value' => 40, 'suffix' => '%'],
                    ['label' => 'AI Model Development', 'value' => 15, 'suffix' => '%'],
                    ['label' => 'Sales & Marketing', 'value' => 7, 'suffix' => '%'],
                    ['label' => 'Operations & Admin', 'value' => 3, 'suffix' => '%'],
                ]
            ]
        ],
        'ar' => [
            'progress_bars' => [
                'items' => [
                    ['label' => 'التكنولوجيا والبنية التحتية', 'value' => 35, 'suffix' => '%'],
                    ['label' => 'الموارد البشرية والرواتب', 'value' => 40, 'suffix' => '%'],
                    ['label' => 'تطوير نموذج الذكاء الاصطناعي', 'value' => 15, 'suffix' => '%'],
                    ['label' => 'المبيعات والتسويق', 'value' => 7, 'suffix' => '%'],
                    ['label' => 'العمليات والإدارة', 'value' => 3, 'suffix' => '%'],
                ]
            ]
        ]
    ],

    'fin_financial_projections' => [
        'en' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'Year 1 Revenue', 'value' => '$8.2M', 'description' => '18-24 customer contracts at average ARR'],
                    ['label' => 'Year 2 Revenue', 'value' => '$18.5M', 'description' => '40-50 customer contracts, 65% YoY growth'],
                    ['label' => 'Year 3 Revenue', 'value' => '$35.2M', 'description' => '75-85 customer contracts, 90% YoY growth'],
                    ['label' => 'Gross Margin (Year 2)', 'value' => '68%', 'description' => 'High-margin SaaS with consulting services'],
                    ['label' => 'Customer Acquisition Cost', 'value' => '$35K', 'description' => 'Payback period ~8 months'],
                    ['label' => 'Net Revenue Retention', 'value' => '115%', 'description' => 'Expansion revenue from existing customers'],
                ]
            ]
        ],
        'ar' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'إيرادات السنة الأولى', 'value' => '$8.2M', 'description' => '18-24 عقد عميل بمعدل ARR متوسط'],
                    ['label' => 'إيرادات السنة الثانية', 'value' => '$18.5M', 'description' => '40-50 عقد عميل، نمو 65% السنة على السنة'],
                    ['label' => 'إيرادات السنة الثالثة', 'value' => '$35.2M', 'description' => '75-85 عقد عميل، نمو 90% السنة على السنة'],
                    ['label' => 'إجمالي الهامش (السنة الثانية)', 'value' => '68%', 'description' => 'SaaS عالي الهامش مع خدمات استشارات'],
                    ['label' => 'تكلفة الحصول على العميل', 'value' => '$35K', 'description' => 'فترة الاسترداد حوالي 8 أشهر'],
                    ['label' => 'احتفاظ صافي الإيرادات', 'value' => '115%', 'description' => 'إيرادات التوسع من العملاء الحاليين'],
                ]
            ]
        ]
    ],

    'fin_funding_requirements' => [
        'en' => [
            'text_content' => [
                'title' => 'Funding Requirements',
                'sections' => [
                    [
                        'heading' => 'Series A Funding Goal',
                        'content' => 'Seeking $8-10M Series A investment to accelerate product development, market expansion, and team growth. This funding will enable Boud to dominate the AI-powered digital transformation consulting space in Saudi Arabia and expand regionally across GCC markets.'
                    ],
                    [
                        'heading' => 'Use of Funds',
                        'content' => 'Product Development & AI (35%): Advanced AI model development, platform enhancement, and ecosystem integration. Go-to-Market (30%): Sales team expansion, marketing campaigns, and regional partnerships. Talent Acquisition (25%): Senior engineering, AI specialists, and business development roles. Operations & Working Capital (10%): Infrastructure, tools, and operational efficiency.'
                    ],
                    [
                        'heading' => 'Funding Timeline',
                        'content' => 'Target closing Series A by Q3 2026. This timeline aligns with completing MVP and achieving initial customer traction. Funds will be deployed immediately post-close with 24-month horizon for Series B at $25-30M valuation targeting 2028.'
                    ],
                    [
                        'heading' => 'Exit Strategy',
                        'content' => 'Multiple exit pathways including acquisition by major consulting firms (McKinsey, Accenture, Deloitte) interested in AI/digital capabilities, or IPO within 5-7 years with target unicorn valuation. Strong profitability trajectory and large addressable market support attractive exit multiples for investors.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'متطلبات التمويل',
                'sections' => [
                    [
                        'heading' => 'هدف تمويل السلسلة أ',
                        'content' => 'البحث عن استثمار Series A بقيمة 8-10 ملايين دولار لتسريع تطوير المنتج وتوسع السوق ونمو الفريق. سيمكن هذا التمويل Boud من السيطرة على مساحة استشارات التحول الرقمي المدعومة بالذكاء الاصطناعي في المملكة العربية السعودية والتوسع إقليمياً عبر أسواق مجلس التعاون الخليجي.'
                    ],
                    [
                        'heading' => 'استخدام الأموال',
                        'content' => 'تطوير المنتج والذكاء الاصطناعي (35٪): تطوير نموذج الذكاء الاصطناعي المتقدم وتحسين المنصة وتكامل النظام البيئي. الذهاب إلى السوق (30٪): توسع فريق المبيعات والحملات التسويقية والشراكات الإقليمية. استقطاب المواهب (25٪): أدوار الهندسة الكبرى ومتخصصي الذكاء الاصطناعي والتطوير التجاري. العمليات وتمويل العاملين (10٪): البنية التحتية والأدوات والكفاءة التشغيلية.'
                    ],
                    [
                        'heading' => 'جدول التمويل',
                        'content' => 'الهدف إغلاق Series A بحلول الربع الثالث 2026. يتوافق هذا الجدول الزمني مع إكمال MVP وتحقيق جر العملاء الأولي. سيتم نشر الأموال فوراً بعد الإغلاق مع أفق 24 شهر لـ Series B بتقييم 25-30 ملايين دولار يستهدف 2028.'
                    ],
                    [
                        'heading' => 'استراتيجية الخروج',
                        'content' => 'مسارات خروج متعددة تشمل الاستحواذ من قبل شركات الاستشارات الكبرى (McKinsey و Accenture و Deloitte) المهتمة بقدرات الذكاء الاصطناعي / الرقمية، أو الاكتتاب العام في غضون 5-7 سنوات مع استهداف تقييم يونيكورن. مسار الربحية القوي والسوق العنوان الكبير يدعم مضاعفات الخروج الجذابة للمستثمرين.'
                    ]
                ]
            ]
        ]
    ],

    'fin_unit_economics' => [
        'en' => [
            'key_value' => [
                'items' => [
                    ['key' => 'Average Contract Value (ACV)', 'value' => '$450K annually'],
                    ['key' => 'Customer Acquisition Cost (CAC)', 'value' => '$35K'],
                    ['key' => 'CAC Payback Period', 'value' => '8 months'],
                    ['key' => 'Lifetime Value (LTV)', 'value' => '$1.8M (4-year average'],
                    ['key' => 'LTV/CAC Ratio', 'value' => '51.4x (highly attractive)'],
                    ['key' => 'Monthly Recurring Revenue (MRR)', 'value' => '$37.5K per customer'],
                    ['key' => 'Gross Margin per Customer', 'value' => '68%'],
                    ['key' => 'Net Revenue Retention (NRR)', 'value' => '115%'],
                ]
            ]
        ],
        'ar' => [
            'key_value' => [
                'items' => [
                    ['key' => 'متوسط قيمة العقد (ACV)', 'value' => '$450K سنوياً'],
                    ['key' => 'تكلفة الحصول على العميل (CAC)', 'value' => '$35K'],
                    ['key' => 'فترة استرجاع CAC', 'value' => '8 أشهر'],
                    ['key' => 'القيمة الدائمة (LTV)', 'value' => '$1.8M (متوسط 4 سنوات)'],
                    ['key' => 'نسبة LTV/CAC', 'value' => '51.4x (جذابة جداً)'],
                    ['key' => 'الإيرادات المتكررة الشهرية (MRR)', 'value' => '$37.5K لكل عميل'],
                    ['key' => 'إجمالي الهامش لكل عميل', 'value' => '68%'],
                    ['key' => 'احتفاظ صافي الإيرادات (NRR)', 'value' => '115%'],
                ]
            ]
        ]
    ],

    'gtm_launch_strategy' => [
        'en' => [
            'text_content' => [
                'title' => 'Go-to-Market Launch Strategy',
                'sections' => [
                    [
                        'heading' => 'Market Segmentation',
                        'content' => 'Primary focus on large enterprises (500+ employees) in financial services, retail, telecommunications, and manufacturing sectors. Secondary segments include government agencies and semi-government organizations with Vision 2030 mandates. Geographic prioritization: Riyadh, Jeddah, Dammam initially with regional expansion to UAE, Kuwait, Qatar within 18 months.'
                    ],
                    [
                        'heading' => 'Sales Strategy',
                        'content' => 'Enterprise sales model with dedicated account executives targeting C-suite decision-makers. Establish strategic partnerships with Big 4 consulting firms and system integrators for referrals. Build alliances with industry associations and chambers of commerce for lead generation. Implement inside sales team for mid-market opportunities. Leverage parent company ecosystem (Hackify, SALIS, Connect AI) for cross-selling.'
                    ],
                    [
                        'heading' => 'Marketing & Brand',
                        'content' => 'Thought leadership positioning through industry conferences, whitepapers, and research on digital transformation and Vision 2030. Create Arabic-language content marketing addressing Saudi business needs. Establish Boud as the premium AI consulting brand. Build proof points through early customer case studies. Leverage social media and LinkedIn for B2B engagement. Sponsor industry events and webinars.'
                    ],
                    [
                        'heading' => 'Customer Success & Retention',
                        'content' => 'Dedicated customer success managers for each enterprise client. Quarterly business reviews with C-suite stakeholders. Continuous value delivery through innovation updates and new feature releases. Build customer advisory board for strategic feedback. Implement NPS tracking and proactive churn prevention. Expand ACV through upsells and cross-sells of ecosystem products.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'استراتيجية الإطلاق للسوق',
                'sections' => [
                    [
                        'heading' => 'تقسيم السوق',
                        'content' => 'التركيز الأساسي على الشركات الكبيرة (500+ موظف) في قطاعات الخدمات المالية والبيع بالتجزئة والاتصالات والتصنيع. تشمل القطاعات الثانوية الوكالات الحكومية والمنظمات شبه الحكومية مع ولايات رؤية 2030. ترتيب الأولويات الجغرافية: الرياض وجدة والدمام في البداية مع التوسع الإقليمي إلى الإمارات والكويت وقطر خلال 18 شهر.'
                    ],
                    [
                        'heading' => 'استراتيجية المبيعات',
                        'content' => 'نموذج المبيعات للمؤسسات مع متخصصي الحسابات المخصصة يستهدفون صناع القرار في المستوى التنفيذي. إنشاء شراكات استراتيجية مع شركات الاستشارات الأربع الكبرى والمدمجات النظام لأغراض الإحالة. بناء تحالفات مع جمعيات الصناعة وغرف التجارة لتوليد الرصاص. تطبيق فريق المبيعات الداخلية لفرص السوق المتوسطة. الاستفادة من النظام البيئي لشركة الأم (Hackify و SALIS و Connect AI) للبيع المتقاطع.'
                    ],
                    [
                        'heading' => 'التسويق والعلامة التجارية',
                        'content' => 'تحديد موضع القيادة الفكرية من خلال مؤتمرات الصناعة والأوراق البيضاء والبحث عن التحول الرقمي ورؤية 2030. إنشاء محتوى تسويقي باللغة العربية يعالج احتياجات الأعمال السعودية. إنشاء Boud كعلامة تجارية استشارات ذكاء اصطناعي متميزة. بناء نقاط الإثبات من خلال دراسات حالات العملاء المبكرة. الاستفادة من وسائل التواصل الاجتماعي و LinkedIn للمشاركة B2B. رعاية أحداث الصناعة والندوات عبر الإنترنت.'
                    ],
                    [
                        'heading' => 'نجاح العملاء والاحتفاظ',
                        'content' => 'مديرو نجاح عملاء مخصصون لكل عميل مؤسسة. الاستعراضات الفصلية للأعمال مع أصحاب المصلحة من المستوى التنفيذي. تسليم القيمة المستمرة من خلال تحديثات الابتكار وإصدارات الميزات الجديدة. بناء مجلس استشاري للعملاء للحصول على تعليقات استراتيجية. تنفيذ تتبع NPS ومنع الضجيج الاستباقي. توسيع ACV من خلال عمليات البيع الإضافي والبيع المتقاطع لمنتجات النظام البيئي.'
                    ]
                ]
            ]
        ]
    ],

    'gtm_marketing_channels' => [
        'en' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'Thought Leadership', 'value' => '25%', 'description' => 'Conference speaking, whitepapers, research reports'],
                    ['label' => 'Direct Sales', 'value' => '35%', 'description' => 'Enterprise account executives and partnerships'],
                    ['label' => 'Digital Marketing', 'value' => '20%', 'description' => 'LinkedIn, content marketing, SEO, webinars'],
                    ['label' => 'Strategic Partnerships', 'value' => '15%', 'description' => 'Big 4 firms, integrators, ecosystem companies'],
                    ['label' => 'Community & Events', 'value' => '5%', 'description' => 'Industry associations, chambers of commerce'],
                ]
            ]
        ],
        'ar' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'قيادة الفكر', 'value' => '25%', 'description' => 'مؤتمرات التحدث والأوراق البيضاء وتقارير البحث'],
                    ['label' => 'المبيعات المباشرة', 'value' => '35%', 'description' => 'متخصصو الحسابات للمؤسسات والشراكات'],
                    ['label' => 'التسويق الرقمي', 'value' => '20%', 'description' => 'LinkedIn والتسويق بالمحتوى و SEO والندوات'],
                    ['label' => 'الشراكات الاستراتيجية', 'value' => '15%', 'description' => 'شركات Big 4 والمدمجات وشركات النظام البيئي'],
                    ['label' => 'المجتمع والأحداث', 'value' => '5%', 'description' => 'جمعيات الصناعة وغرف التجارة'],
                ]
            ]
        ]
    ],

    'gtm_sales_funnel' => [
        'en' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'Top of Funnel (Awareness)',
                        'description' => 'Generate awareness among target decision-makers',
                        'touchpoints' => ['Content marketing campaigns', 'Industry conference presence', 'LinkedIn outreach', 'Partner referrals', 'Webinar series'],
                        'actions' => ['Build prospect database', 'Create content library', 'Launch digital campaigns', 'Establish partnerships']
                    ],
                    [
                        'title' => 'Middle of Funnel (Consideration)',
                        'description' => 'Engage prospects and move toward evaluation',
                        'touchpoints' => ['Product demos and trials', 'White papers and case studies', 'Executive briefings', 'Reference calls', 'ROI calculator'],
                        'actions' => ['Schedule product demos', 'Distribute resources', 'Conduct needs analysis', 'Prepare business case']
                    ],
                    [
                        'title' => 'Bottom of Funnel (Decision)',
                        'description' => 'Close deals and onboard customers',
                        'touchpoints' => ['Contract negotiation', 'Legal review', 'Budget allocation', 'Executive approval', 'Onboarding kickoff'],
                        'actions' => ['Finalize contracts', 'Complete due diligence', 'Establish governance', 'Plan implementation']
                    ],
                    [
                        'title' => 'Post-Sale (Expansion)',
                        'description' => 'Maximize customer lifetime value',
                        'touchpoints' => ['Regular check-ins', 'Success reviews', 'Expansion opportunities', 'Referral generation', 'Advocacy programs'],
                        'actions' => ['Track KPIs', 'Identify upsell opportunities', 'Gather feedback', 'Generate case studies']
                    ]
                ]
            ]
        ],
        'ar' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'أعلى القمع (الوعي)',
                        'description' => 'تولد الوعي بين صناع القرار المستهدفين',
                        'touchpoints' => ['حملات التسويق بالمحتوى', 'وجود مؤتمر الصناعة', 'LinkedIn الوصول', 'إحالات الشركاء', 'سلسلة الندوات'],
                        'actions' => ['بناء قاعدة بيانات المحتملين', 'إنشاء مكتبة محتوى', 'إطلاق حملات رقمية', 'إنشاء شراكات']
                    ],
                    [
                        'title' => 'وسط القمع (الاعتبار)',
                        'description' => 'الانخراط مع المحتملين والتحرك نحو التقييم',
                        'touchpoints' => ['عروض المنتجات والتجارب', 'الأوراق البيضاء ودراسات الحالات', 'الإحاطات التنفيذية', 'استدعاءات الرجوع', 'آلة حساب العائد على الاستثمار'],
                        'actions' => ['جدولة عروض المنتج', 'توزيع الموارد', 'إجراء تحليل الاحتياجات', 'تحضير حالة العمل']
                    ],
                    [
                        'title' => 'أسفل القمع (القرار)',
                        'description' => 'إغلاق الصفقات وإدراج العملاء',
                        'touchpoints' => ['التفاوض على العقد', 'المراجعة القانونية', 'تخصيص الميزانية', 'الموافقة التنفيذية', 'بدء بدء الإعداد'],
                        'actions' => ['إنهاء العقود', 'إكمال العناية الواجبة', 'إنشاء الحوكمة', 'خطة التطبيق']
                    ],
                    [
                        'title' => 'ما بعد البيع (التوسع)',
                        'description' => 'زيادة قيمة العميل مدى الحياة',
                        'touchpoints' => ['الفحوصات المنتظمة', 'استعراضات النجاح', 'فرص التوسع', 'توليد الإحالات', 'برامج الدفاع'],
                        'actions' => ['تتبع مؤشرات الأداء', 'تحديد فرص البيع الإضافي', 'جمع التعليقات', 'توليد دراسات الحالات']
                    ]
                ]
            ]
        ]
    ],

    'gtm_partnerships' => [
        'en' => [
            'text_content' => [
                'title' => 'Strategic Partnerships',
                'sections' => [
                    [
                        'heading' => 'Consulting Firm Partnerships',
                        'content' => 'Strategic partnerships with Big 4 consulting firms (McKinsey, Accenture, Deloitte, EY) and regional consulting leaders. Boud provides AI and digital tools; partners provide client relationships and implementation services. Revenue sharing model with mutual benefit and co-selling opportunities. Integration of Boud platform into partner service offerings.'
                    ],
                    [
                        'heading' => 'Technology & System Integrator Partnerships',
                        'content' => 'Partnerships with cloud providers (AWS, Azure, Google Cloud) and system integrators. Embedded Boud platform in SI service offerings for digital transformation projects. Technology partnerships ensure seamless integration and cloud deployment. Co-marketing and lead sharing arrangements.'
                    ],
                    [
                        'heading' => 'Government & Public Sector Partnerships',
                        'content' => 'Strategic engagement with Saudi government agencies and Vision 2030 implementation bodies. Established as approved vendor for digital transformation initiatives. Partnership with MISA and regulatory bodies for compliance and best practices. Government mandates drive enterprise adoption.'
                    ],
                    [
                        'heading' => 'Ecosystem Partner Collaboration',
                        'content' => 'Leverage Boud parent company ecosystem (Hackify for innovation management, SALIS for AI solutions, Connect AI for collaboration) for customer cross-selling and expanded value delivery. Create bundled offerings combining Boud with ecosystem tools. Network effects as more ecosystem customers adopt multiple products.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'الشراكات الاستراتيجية',
                'sections' => [
                    [
                        'heading' => 'شراكات شركات الاستشارات',
                        'content' => 'شراكات استراتيجية مع شركات الاستشارات الأربع الكبرى (McKinsey و Accenture و Deloitte و EY) والقادة الاستشاريين الإقليميين. تقدم Boud أدوات الذكاء الاصطناعي والرقميات؛ يقدم الشركاء علاقات العملاء وخدمات التطبيق. نموذج تقاسم الإيرادات مع الفائدة المتبادلة وفرص البيع المشترك. تكامل منصة Boud في عروض الخدمات للشركاء.'
                    ],
                    [
                        'heading' => 'شراكات التكنولوجيا والمدمجات',
                        'content' => 'شراكات مع موفري السحابة (AWS و Azure و Google Cloud) والمدمجات النظام. منصة Boud المدمجة في عروض خدمات SI لمشاريع التحول الرقمي. تضمن شراكات التكنولوجيا التكامل السلس والنشر السحابي. ترتيبات التسويق المشترك وتقاسم الرصاص.'
                    ],
                    [
                        'heading' => 'شراكات الحكومة والقطاع العام',
                        'content' => 'الانخراط الاستراتيجي مع الوكالات الحكومية السعودية وهيئات تطبيق رؤية 2030. تأسس كبائع معتمد لمبادرات التحول الرقمي. الشراكة مع MISA والهيئات التنظيمية للامتثال وأفضل الممارسات. تفويضات حكومية تحرك التبني الحكومي.'
                    ],
                    [
                        'heading' => 'تعاون شركاء النظام البيئي',
                        'content' => 'استفد من النظام البيئي لشركة الأم Boud (Hackify لإدارة الابتكار و SALIS لحلول الذكاء الاصطناعي و Connect AI للتعاون) لتعيين البيع المتقاطع للعملاء وتسليم القيمة الموسعة. إنشاء عروض مجمعة تجمع Boud مع أدوات النظام البيئي. تأثيرات الشبكة مع اعتماد عملاء النظام البيئي لمنتجات متعددة.'
                    ]
                ]
            ]
        ]
    ],

    'gtm_growth_metrics' => [
        'en' => [
            'progress_bars' => [
                'items' => [
                    ['label' => 'Customer Acquisition', 'value' => 85, 'suffix' => '% target'],
                    ['label' => 'Monthly Recurring Revenue (MRR)', 'value' => 72, 'suffix' => '% target'],
                    ['label' => 'Net Revenue Retention (NRR)', 'value' => 115, 'suffix' => '% achieved'],
                    ['label' => 'Customer Satisfaction (NPS)', 'value' => 68, 'suffix' => '% target'],
                    ['label' => 'Market Share (Year 2)', 'value' => 18, 'suffix' => '% target'],
                ]
            ]
        ],
        'ar' => [
            'progress_bars' => [
                'items' => [
                    ['label' => 'الحصول على العميل', 'value' => 85, 'suffix' => '% هدف'],
                    ['label' => 'الإيرادات المتكررة الشهرية (MRR)', 'value' => 72, 'suffix' => '% هدف'],
                    ['label' => 'احتفاظ صافي الإيرادات (NRR)', 'value' => 115, 'suffix' => '% تم تحقيقه'],
                    ['label' => 'رضا العملاء (NPS)', 'value' => 68, 'suffix' => '% هدف'],
                    ['label' => 'حصة السوق (السنة 2)', 'value' => 18, 'suffix' => '% هدف'],
                ]
            ]
        ]
    ],

    'ca_competitor_overview' => [
        'en' => [
            'text_content' => [
                'title' => 'Competitive Landscape Overview',
                'sections' => [
                    [
                        'heading' => 'Traditional Consulting Competitors',
                        'content' => 'McKinsey, Accenture, Deloitte, BCG, and EY dominate enterprise transformation consulting. Strong client relationships and brand recognition but slow to adopt AI tools. High cost structures limit innovation. Limited AI/digital product offerings. Opportunity: Boud offers faster, more cost-effective solutions with superior technology.'
                    ],
                    [
                        'heading' => 'SaaS & Generic Digital Tools',
                        'content' => 'Salesforce, Microsoft, SAP, and Oracle provide digital solutions but lack transformation consulting expertise. Feature-heavy platforms without strategic guidance. Minimal Vision 2030 alignment. Require extensive customization and integration. Opportunity: Boud combines technology with expert consulting and local market knowledge.'
                    ],
                    [
                        'heading' => 'Regional Competitors',
                        'content' => 'Local consulting firms and digital transformation startups lack scale, resources, and international credibility. Limited AI capabilities and technology infrastructure. Narrow service offerings. Opportunity: Boud combines global capabilities with deep Saudi market expertise and specialized AI-powered tools.'
                    ],
                    [
                        'heading' => 'Competitive Moats',
                        'content' => 'Boud advantages include proprietary AI models trained on Saudi data, Vision 2030 alignment framework, integrated ecosystem (Hackify, SALIS, Connect AI), combination of consulting + software, and local market expertise with global standards. Strong defensibility against new entrants.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'نظرة عامة على المشهد التنافسي',
                'sections' => [
                    [
                        'heading' => 'منافسو الاستشارات التقليدية',
                        'content' => 'تهيمن McKinsey و Accenture و Deloitte و BCG و EY على استشارات تحول المؤسسات. علاقات عملاء قوية وتقدير العلامة التجارية لكن بطء اعتماد أدوات الذكاء الاصطناعي. هياكل تكاليف عالية تحد من الابتكار. عروض منتجات الذكاء الاصطناعي / الرقميات محدودة. الفرصة: توفر Boud حلولاً أسرع وأكثر فعالية من حيث التكلفة مع تكنولوجيا فائقة.'
                    ],
                    [
                        'heading' => 'منافسو SaaS والأدوات الرقمية العامة',
                        'content' => 'توفر Salesforce و Microsoft و SAP و Oracle حلولاً رقمية لكن تفتقد خبرة استشارات التحول. منصات ميزات ثقيلة بدون توجيه استراتيجي. توافق رؤية 2030 الحد الأدنى. تتطلب تخصيص وتكامل شامل. الفرصة: تجمع Boud بين التكنولوجيا والاستشارات الخبيرة والمعرفة المحلية.'
                    ],
                    [
                        'heading' => 'المنافسون الإقليميون',
                        'content' => 'شركات الاستشارات المحلية وشركات التحول الرقمي الناشئة تفتقد الحجم والموارد والمصداقية الدولية. قدرات الذكاء الاصطناعي المحدودة والبنية التحتية للتكنولوجيا. عروض الخدمات الضيقة. الفرصة: تجمع Boud بين القدرات العالمية والخبرة السعودية العميقة مع أدوات الذكاء الاصطناعي المتخصصة.'
                    ],
                    [
                        'heading' => 'الخنادق التنافسية',
                        'content' => 'تشمل مزايا Boud نماذج الذكاء الاصطناعي الملكية المدربة على البيانات السعودية وإطار عمل توافق رؤية 2030 والنظام البيئي المتكامل (Hackify و SALIS و Connect AI) ومزيج الاستشارات والبرمجيات والخبرة المحلية مع المعايير العالمية. قابلية دفاع قوية ضد الداخلين الجدد.'
                    ]
                ]
            ]
        ]
    ],

    'ca_feature_comparison' => [
        'en' => [
            'comparison_table' => [
                'headers' => ['Feature/Capability', 'Boud', 'Traditional Consulting', 'SaaS Platforms'],
                'rows' => [
                    ['AI-Powered Assessment', 'Advanced', 'Manual process', 'Basic analysis'],
                    ['Digital Maturity Evaluation', 'Proprietary + customized', 'Generic approach', 'Limited scope'],
                    ['Vision 2030 Framework', 'Native integration', 'Custom development', 'Not available'],
                    ['Software + Consulting Combo', 'Integrated', 'Separate vendors', 'Software only'],
                    ['Implementation Support', 'Full program', 'High-touch', 'Self-service'],
                    ['Cost Structure', 'Flexible SaaS', 'High fixed costs', 'Subscription'],
                    ['Time to Value', '3-6 months', '6-12 months', '2-4 months'],
                    ['Ongoing Optimization', 'Continuous', 'Engagement-based', 'Self-directed'],
                    ['Ecosystem Integration', 'Hackify, SALIS, AI', 'External tools', 'Limited'],
                ]
            ]
        ],
        'ar' => [
            'comparison_table' => [
                'headers' => ['الميزة / الإمكانية', 'Boud', 'الاستشارات التقليدية', 'منصات SaaS'],
                'rows' => [
                    ['التقييم المدعوم بالذكاء الاصطناعي', 'متقدم', 'عملية يدوية', 'تحليل أساسي'],
                    ['تقييم النضج الرقمي', 'ملكية مخصصة', 'نهج عام', 'نطاق محدود'],
                    ['إطار رؤية 2030', 'تكامل أصلي', 'تطوير مخصص', 'غير متوفر'],
                    ['مزيج البرمجيات والاستشارات', 'متكامل', 'بائعون منفصلون', 'البرمجيات فقط'],
                    ['دعم التطبيق', 'برنامج كامل', 'عالي اللمس', 'خدمة ذاتية'],
                    ['هيكل التكلفة', 'SaaS مرن', 'تكاليف ثابتة عالية', 'الاشتراك'],
                    ['الوقت لتحقيق القيمة', '3-6 أشهر', '6-12 شهر', '2-4 أشهر'],
                    ['التحسين المستمر', 'مستمر', 'قائم على الانخراط', 'موجه ذاتي'],
                    ['تكامل النظام البيئي', 'Hackify و SALIS و AI', 'أدوات خارجية', 'محدود'],
                ]
            ]
        ]
    ],

    'ca_market_positioning' => [
        'en' => [
            'key_value' => [
                'items' => [
                    ['key' => 'Brand Positioning', 'value' => 'The leading AI-powered digital transformation partner for Saudi Vision 2030'],
                    ['key' => 'Target Market', 'value' => 'Large enterprises (500+) in financial, retail, telecom, manufacturing'],
                    ['key' => 'Key Differentiators', 'value' => 'AI + Consulting combo, Vision 2030 alignment, Integrated ecosystem'],
                    ['key' => 'Value Proposition', 'value' => 'Faster transformation, better outcomes, integrated solutions, local expertise'],
                    ['key' => 'Pricing Strategy', 'value' => 'Premium SaaS + advisory pricing, outcome-based options'],
                    ['key' => 'Go-to-Market Model', 'value' => 'Enterprise direct sales, strategic partnerships, thought leadership'],
                    ['key' => 'Competitive Advantage', 'value' => 'Proprietary AI, Vision 2030 compliance, ecosystem integration, proven ROI'],
                    ['key' => 'Market Opportunity', 'value' => '$2.5B+ addressable market in KSA and GCC region'],
                ]
            ]
        ],
        'ar' => [
            'key_value' => [
                'items' => [
                    ['key' => 'تحديد موضع العلامة التجارية', 'value' => 'الشريك الرائد للتحول الرقمي المدعوم بالذكاء الاصطناعي لرؤية السعودية 2030'],
                    ['key' => 'السوق المستهدفة', 'value' => 'المؤسسات الكبيرة (500+) في المالية والبيع بالتجزئة والاتصالات والتصنيع'],
                    ['key' => 'المميزات الرئيسية', 'value' => 'مزيج الذكاء الاصطناعي والاستشارات وتوافق رؤية 2030 والنظام البيئي المتكامل'],
                    ['key' => 'الاقتراح القيمي', 'value' => 'تحول أسرع ونتائج أفضل وحلول متكاملة وخبرة محلية'],
                    ['key' => 'استراتيجية التسعير', 'value' => 'تسعير SaaS + استشاري متميز وخيارات قائمة على النتائج'],
                    ['key' => 'نموذج الذهاب إلى السوق', 'value' => 'مبيعات المؤسسات المباشرة والشراكات الاستراتيجية والقيادة الفكرية'],
                    ['key' => 'الميزة التنافسية', 'value' => 'ذكاء اصطناعي ملكي ورؤية 2030 امتثال وتكامل النظام البيئي و ROI مثبت'],
                    ['key' => 'فرصة السوق', 'value' => 'سوق قابلة للعنوان بقيمة 2.5 مليار دولار + في المملكة العربية السعودية ومنطقة مجلس التعاون'],
                ]
            ]
        ]
    ],

    'ca_competitive_moat' => [
        'en' => [
            'text_content' => [
                'title' => 'Competitive Moat & Defensibility',
                'sections' => [
                    [
                        'heading' => 'Technology Moat',
                        'content' => 'Proprietary AI models trained specifically on Saudi Arabian organizational and market data create significant technological advantage. Continuous AI model improvement through customer data and feedback. Patent-eligible IP around assessment algorithms and Vision 2030 compliance frameworks. Barrier to entry: Requires significant R&D investment and data to replicate capabilities.'
                    ],
                    [
                        'heading' => 'Network & Ecosystem Moat',
                        'content' => 'Parent company ecosystem (Hackify, SALIS, Connect AI) creates significant switching costs and cross-selling opportunities. Growing network of partners, integrations, and ecosystem companies raises barriers for competitors. Network effects strengthen as more customers adopt multiple ecosystem products. Difficult for new entrants to replicate multi-product ecosystem.'
                    ],
                    [
                        'heading' => 'Market & Regulatory Moat',
                        'content' => 'Deep expertise in Saudi regulatory environment and Vision 2030 alignment creates competitive advantage. Government relationships and approved vendor status provide privileged market access. Local market knowledge and cultural understanding difficult to replicate by global competitors. First-mover advantage in Vision 2030 compliant solutions.'
                    ],
                    [
                        'heading' => 'Customer & Data Moat',
                        'content' => 'Growing customer base provides valuable training data for AI models. Long-term customer relationships (4+ year average) create strong retention and expansion opportunities. Established case studies and proof points create trust and lower customer acquisition barriers. Customer advisory board provides strategic insights and roadmap validation.'
                    ]
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'الخندق التنافسي وقابلية الدفاع',
                'sections' => [
                    [
                        'heading' => 'خندق التكنولوجيا',
                        'content' => 'تشكل نماذج الذكاء الاصطناعي الملكية المدربة خصيصاً على بيانات المنظمة والسوق السعودية ميزة تكنولوجية كبيرة. تحسين نموذج الذكاء الاصطناعي المستمر من خلال بيانات العملاء والتعليقات. ملكية فكرية قابلة للبراءات تتعلق بخوارزميات التقييم وأطر عمل الامتثال لرؤية 2030. حاجز الدخول: يتطلب استثمار R&D كبير والبيانات لتكرار الإمكانيات.'
                    ],
                    [
                        'heading' => 'خندق الشبكة والنظام البيئي',
                        'content' => 'يخلق النظام البيئي لشركة الأم (Hackify و SALIS و Connect AI) تكاليف التبديل الكبيرة وفرص البيع المتقاطع. شبكة متزايدة من الشركاء والتكاملات وشركات النظام البيئي ترفع حواجز المنافسين. تأثيرات الشبكة تقوي مع اعتماد المزيد من العملاء لمنتجات النظام البيئي المتعددة. من الصعب على الداخلين الجدد تكرار النظام البيئي متعدد المنتجات.'
                    ],
                    [
                        'heading' => 'خندق السوق والتنظيم',
                        'content' => 'الخبرة العميقة في البيئة التنظيمية السعودية وتوافق رؤية 2030 تخلق ميزة تنافسية. العلاقات الحكومية وحالة البائع المعتمد توفر وصول سوق مميز. المعرفة المحلية والفهم الثقافي يصعب تكراره من قبل المنافسين العالميين. ميزة الحركة الأولى في حلول الامتثال لرؤية 2030.'
                    ],
                    [
                        'heading' => 'خندق العميل والبيانات',
                        'content' => 'قاعدة العملاء المتنامية توفر بيانات تدريب قيمة لنماذج الذكاء الاصطناعي. تشكل علاقات العملاء طويلة الأجل (متوسط 4+ سنة) فرص احتفاظ وتوسع قوية. دراسات حالات وإثباتات نقاط مؤسسة تخلق ثقة وتقلل حواجز الحصول على العملاء. يوفر مجلس استشاري العملاء رؤية استراتيجية والتحقق من صحة خريطة الطريق.'
                    ]
                ]
            ]
        ]
    ]
];
