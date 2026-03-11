<?php return [
    'mvp_feature_priority' => [
        'en' => [
            'comparison_table' => [
                'headers' => ['Feature', 'Priority', 'Timeline', 'Impact', 'Status'],
                'rows' => [
                    ['AI-Powered Resume Screening', 'Critical', 'Month 1-2', 'High', 'In Development'],
                    ['Candidate Ranking Engine', 'Critical', 'Month 2-3', 'High', 'In Development'],
                    ['Interview Scheduling Automation', 'High', 'Month 3-4', 'Medium', 'Planned'],
                    ['Performance Analytics Dashboard', 'High', 'Month 4-5', 'High', 'Planned'],
                    ['Employee Skills Assessment', 'Medium', 'Month 5-6', 'Medium', 'Planned'],
                    ['Candidate Communication Portal', 'Medium', 'Month 3-4', 'Medium', 'Planned'],
                ]
            ]
        ],
        'ar' => [
            'comparison_table' => [
                'headers' => ['الميزة', 'الأولوية', 'الجدول الزمني', 'التأثير', 'الحالة'],
                'rows' => [
                    ['فحص السيرة الذاتية بقوة الذكاء الاصطناعي', 'حرج', 'الشهر 1-2', 'مرتفع', 'قيد التطوير'],
                    ['محرك ترتيب المرشحين', 'حرج', 'الشهر 2-3', 'مرتفع', 'قيد التطوير'],
                    ['أتمتة جدولة المقابلات', 'مرتفع', 'الشهر 3-4', 'متوسط', 'مخطط'],
                    ['لوحة معلومات تحليل الأداء', 'مرتفع', 'الشهر 4-5', 'مرتفع', 'مخطط'],
                    ['تقييم مهارات الموظفين', 'متوسط', 'الشهر 5-6', 'متوسط', 'مخطط'],
                    ['بوابة التواصل مع المرشحين', 'متوسط', 'الشهر 3-4', 'متوسط', 'مخطط'],
                ]
            ]
        ]
    ],

    'mvp_development_roadmap' => [
        'en' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'Phase 1: Core Foundation',
                        'description' => 'Build AI resume screening and candidate database infrastructure',
                        'touchpoints' => ['API Architecture', 'ML Model Training', 'Database Setup'],
                        'actions' => ['Complete infrastructure setup', 'Train initial models', 'Security implementation']
                    ],
                    [
                        'title' => 'Phase 2: Intelligence Layer',
                        'description' => 'Develop ranking algorithms and performance analytics',
                        'touchpoints' => ['Algorithm Development', 'Analytics Engine', 'Reporting Dashboard'],
                        'actions' => ['Build ranking engine', 'Create analytics suite', 'Design UI/UX']
                    ],
                    [
                        'title' => 'Phase 3: Automation & Integration',
                        'description' => 'Implement workflow automation and third-party integrations',
                        'touchpoints' => ['Interview Scheduling', 'HR System Integration', 'Communication Tools'],
                        'actions' => ['Develop automation workflows', 'Integration APIs', 'Testing & QA']
                    ],
                    [
                        'title' => 'Phase 4: Enterprise Ready',
                        'description' => 'Scale, security hardening, and enterprise deployment',
                        'touchpoints' => ['Security Audit', 'Performance Optimization', 'Documentation'],
                        'actions' => ['Enterprise testing', 'Compliance certification', 'Go-to-market']
                    ]
                ]
            ]
        ],
        'ar' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'المرحلة 1: الأساس الأساسي',
                        'description' => 'بناء فحص السيرة الذاتية بالذكاء الاصطناعي وبنية قاعدة بيانات المرشحين',
                        'touchpoints' => ['معمارية API', 'تدريب نموذج التعلم الآلي', 'إعداد قاعدة البيانات'],
                        'actions' => ['إكمال إعداد البنية التحتية', 'تدريب النماذج الأولية', 'تنفيذ الأمان']
                    ],
                    [
                        'title' => 'المرحلة 2: طبقة الذكاء',
                        'description' => 'تطوير خوارزميات الترتيب وتحليلات الأداء',
                        'touchpoints' => ['تطوير الخوارزميات', 'محرك التحليلات', 'لوحة التقارير'],
                        'actions' => ['بناء محرك الترتيب', 'إنشاء مجموعة التحليلات', 'تصميم الواجهة']
                    ],
                    [
                        'title' => 'المرحلة 3: الأتمتة والتكامل',
                        'description' => 'تنفيذ أتمتة سير العمل والتكامل مع الأطراف الثالثة',
                        'touchpoints' => ['جدولة المقابلات', 'تكامل نظام الموارد البشرية', 'أدوات التواصل'],
                        'actions' => ['تطوير سير العمل الآلي', 'واجهات برمجية للتكامل', 'الاختبار والتحقق']
                    ],
                    [
                        'title' => 'المرحلة 4: جاهزة للمؤسسات',
                        'description' => 'الحجم والتقوية الأمنية ونشر المؤسسات',
                        'touchpoints' => ['تدقيق الأمان', 'تحسين الأداء', 'التوثيق'],
                        'actions' => ['اختبار المؤسسات', 'شهادة الامتثال', 'نزول إلى السوق']
                    ]
                ]
            ]
        ]
    ],

    'mvp_tech_stack' => [
        'en' => [
            'key_value' => [
                'items' => [
                    ['key' => 'Backend Framework', 'value' => 'Laravel / Node.js'],
                    ['key' => 'Frontend Framework', 'value' => 'React.js / Vue.js'],
                    ['key' => 'AI/ML Engine', 'value' => 'TensorFlow / PyTorch'],
                    ['key' => 'Database', 'value' => 'PostgreSQL + MongoDB'],
                    ['key' => 'Cloud Infrastructure', 'value' => 'AWS / Microsoft Azure'],
                    ['key' => 'Real-time Processing', 'value' => 'Apache Kafka / RabbitMQ'],
                    ['key' => 'Search Engine', 'value' => 'Elasticsearch'],
                    ['key' => 'Caching', 'value' => 'Redis'],
                ]
            ]
        ],
        'ar' => [
            'key_value' => [
                'items' => [
                    ['key' => 'إطار العمل الخلفي', 'value' => 'Laravel / Node.js'],
                    ['key' => 'إطار العمل الأمامي', 'value' => 'React.js / Vue.js'],
                    ['key' => 'محرك الذكاء الاصطناعي والتعلم الآلي', 'value' => 'TensorFlow / PyTorch'],
                    ['key' => 'قاعدة البيانات', 'value' => 'PostgreSQL + MongoDB'],
                    ['key' => 'البنية التحتية السحابية', 'value' => 'AWS / Microsoft Azure'],
                    ['key' => 'المعالجة في الوقت الفعلي', 'value' => 'Apache Kafka / RabbitMQ'],
                    ['key' => 'محرك البحث', 'value' => 'Elasticsearch'],
                    ['key' => 'التخزين المؤقت', 'value' => 'Redis'],
                ]
            ]
        ]
    ],

    'mvp_resource_requirements' => [
        'en' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'Engineering Team', 'value' => '12', 'description' => 'Full-stack developers, ML engineers, DevOps'],
                    ['label' => 'Product & Design', 'value' => '3', 'description' => 'Product manager, UX/UI designers, researcher'],
                    ['label' => 'Initial Budget', 'value' => '$450K', 'description' => 'Infrastructure, tools, and operations'],
                    ['label' => 'Development Timeline', 'value' => '6 Months', 'description' => 'MVP to market-ready product'],
                ]
            ]
        ],
        'ar' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'فريق الهندسة', 'value' => '12', 'description' => 'مطورو المكدس الكامل، مهندسو التعلم الآلي، DevOps'],
                    ['label' => 'المنتج والتصميم', 'value' => '3', 'description' => 'مدير المنتج، مصممو UX/UI، باحث'],
                    ['label' => 'الميزانية الأولية', 'value' => '450000 ريال', 'description' => 'البنية التحتية والأدوات والعمليات'],
                    ['label' => 'جدول التطوير', 'value' => '6 أشهر', 'description' => 'MVP إلى منتج جاهز للسوق'],
                ]
            ]
        ]
    ],

    'mvp_risk_mitigation' => [
        'en' => [
            'text_content' => [
                'title' => 'Risk Mitigation Strategy',
                'sections' => [
                    [
                        'heading' => 'AI Model Accuracy Risk',
                        'content' => 'Implement continuous model monitoring and retraining pipelines. Establish human review checkpoints for high-stakes hiring decisions. Maintain version control for all ML models with rollback capabilities.'
                    ],
                    [
                        'heading' => 'Data Privacy & Compliance Risk',
                        'content' => 'Comply with GDPR, CCPA, and local Saudi Arabia data protection regulations. Implement end-to-end encryption, data anonymization, and audit trails. Engage legal counsel for regulatory compliance.'
                    ],
                    [
                        'heading' => 'Market Adoption Risk',
                        'content' => 'Conduct extensive user research with HR professionals and hiring managers. Build product with Saudi Arabia enterprises first. Establish early partnerships with HR consulting firms.'
                    ],
                    [
                        'heading' => 'Technical Scalability Risk',
                        'content' => 'Design for horizontal scalability from day one. Implement auto-scaling infrastructure. Load testing and performance optimization throughout development cycle.'
                    ],
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'استراتيجية تخفيف المخاطر',
                'sections' => [
                    [
                        'heading' => 'مخاطر دقة نموذج الذكاء الاصطناعي',
                        'content' => 'تنفيذ مراقبة النموذج المستمرة وخطوط الأنابيب إعادة التدريب. إنشاء نقاط مراجعة بشرية لقرارات التوظيف عالية المخاطر. الحفاظ على التحكم في الإصدار لجميع نماذج التعلم الآلي مع القدرة على التراجع.'
                    ],
                    [
                        'heading' => 'مخاطر خصوصية البيانات والامتثال',
                        'content' => 'الامتثال لـ GDPR و CCPA واللوائح المحلية لحماية البيانات في المملكة العربية السعودية. تنفيذ التشفير من طرف إلى طرف وإخفاء الهوية عن البيانات وآثار التدقيق. التعاقد مع المستشارين القانونيين للامتثال التنظيمي.'
                    ],
                    [
                        'heading' => 'مخاطر اعتماد السوق',
                        'content' => 'إجراء أبحاث مستخدمين مكثفة مع متخصصي الموارد البشرية ومديري التوظيف. بناء المنتج مع مؤسسات المملكة العربية السعودية أولاً. إنشاء شراكات مبكرة مع شركات استشارات الموارد البشرية.'
                    ],
                    [
                        'heading' => 'مخاطر قابلية التوسع التقنية',
                        'content' => 'التصميم للقابلية الأفقية من اليوم الأول. تنفيذ البنية التحتية قابلة للتوسع التلقائي. اختبار الحمل وتحسين الأداء خلال دورة التطوير.'
                    ],
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
                        'heading' => 'Advanced AI-Powered Resume Screening',
                        'content' => 'Connect AI uses proprietary deep learning models trained on millions of successful hires across GCC enterprises. Reduces hiring time by 70% while improving candidate quality, understanding cultural fit and technical competencies beyond keyword matching.'
                    ],
                    [
                        'heading' => 'Localized for GCC Market',
                        'content' => 'Purpose-built for Saudi Arabia and Gulf region enterprises. Understands local hiring practices, cultural values, and regulatory requirements. Multilingual support for Arabic and English with region-specific compliance features.'
                    ],
                    [
                        'heading' => 'Integrated Performance Management',
                        'content' => 'Beyond recruitment, Connect AI provides continuous employee performance tracking, skills assessment, and career development planning. Creates a complete talent management ecosystem within single platform.'
                    ],
                    [
                        'heading' => 'Explainable AI Decisions',
                        'content' => 'Every AI recommendation includes clear explanation of decision factors. HR teams can understand why candidates are ranked, increasing trust and enabling better hiring decisions.'
                    ],
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'نقاط البيع الفريدة',
                'sections' => [
                    [
                        'heading' => 'فحص السيرة الذاتية المتقدم بقوة الذكاء الاصطناعي',
                        'content' => 'يستخدم Connect AI نماذج التعلم العميق الملكية المدربة على ملايين عمليات التوظيف الناجحة عبر مؤسسات مجلس التعاون الخليجي. يقلل وقت التوظيف بنسبة 70٪ مع تحسين جودة المرشحين، مع فهم التوافق الثقافي والكفاءات الفنية بما يتجاوز مطابقة الكلمات الرئيسية.'
                    ],
                    [
                        'heading' => 'مخصص لسوق مجلس التعاون الخليجي',
                        'content' => 'مصمم خصيصًا لمؤسسات المملكة العربية السعودية والمنطقة الخليجية. يفهم ممارسات التوظيف المحلية والقيم الثقافية والمتطلبات التنظيمية. دعم متعدد اللغات للعربية والإنجليزية مع ميزات الامتثال الخاصة بالمنطقة.'
                    ],
                    [
                        'heading' => 'إدارة الأداء المتكاملة',
                        'content' => 'بما يتجاوز التوظيف، يوفر Connect AI تتبع أداء الموظفين المستمر وتقييم المهارات وتخطيط التطور الوظيفي. ينشئ نظام إدارة المواهب الكامل ضمن منصة واحدة.'
                    ],
                    [
                        'heading' => 'قرارات الذكاء الاصطناعي القابلة للتفسير',
                        'content' => 'تتضمن كل توصية ذكاء اصطناعي شرحًا واضحًا لعوامل القرار. يمكن لفرق الموارد البشرية أن تفهم سبب ترتيب المرشحين، مما يزيد الثقة ويمكن من اتخاذ قرارات توظيف أفضل.'
                    ],
                ]
            ]
        ]
    ],

    'usp_competitive_advantage' => [
        'en' => [
            'comparison_table' => [
                'headers' => ['Feature', 'Connect AI', 'Competitor A', 'Competitor B', 'Competitor C'],
                'rows' => [
                    ['GCC-Specific AI Training', 'Yes', 'No', 'No', 'No'],
                    ['Integrated Performance Management', 'Yes', 'Partial', 'No', 'Partial'],
                    ['Arabic Language Support', 'Native', 'Basic', 'Basic', 'None'],
                    ['Explainable AI', 'Yes', 'No', 'Yes', 'No'],
                    ['Real-time Analytics Dashboard', 'Yes', 'Yes', 'No', 'Yes'],
                    ['Workforce Planning Tools', 'Advanced', 'Basic', 'Basic', 'Advanced'],
                    ['Integration Capabilities', 'Extensive', 'Limited', 'Extensive', 'Limited'],
                    ['Pricing Model', 'SaaS/Per-Seat', 'SaaS/Per-Seat', 'Enterprise', 'SaaS/Per-Seat'],
                ]
            ]
        ],
        'ar' => [
            'comparison_table' => [
                'headers' => ['الميزة', 'Connect AI', 'المنافس أ', 'المنافس ب', 'المنافس ج'],
                'rows' => [
                    ['تدريب الذكاء الاصطناعي الخاص بمجلس التعاون الخليجي', 'نعم', 'لا', 'لا', 'لا'],
                    ['إدارة الأداء المتكاملة', 'نعم', 'جزئي', 'لا', 'جزئي'],
                    ['دعم اللغة العربية', 'أصلي', 'أساسي', 'أساسي', 'لا'],
                    ['الذكاء الاصطناعي القابل للتفسير', 'نعم', 'لا', 'نعم', 'لا'],
                    ['لوحة معلومات التحليلات في الوقت الفعلي', 'نعم', 'نعم', 'لا', 'نعم'],
                    ['أدوات التخطيط القوى العاملة', 'متقدم', 'أساسي', 'أساسي', 'متقدم'],
                    ['إمكانيات التكامل', 'واسع', 'محدود', 'واسع', 'محدود'],
                    ['نموذج التسعير', 'SaaS/Per-Seat', 'SaaS/Per-Seat', 'مؤسسة', 'SaaS/Per-Seat'],
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
                        'heading' => 'Regional Expertise & Localization',
                        'content' => 'Deep understanding of GCC labor markets, cultural nuances, and regulatory landscape. Built by team with direct experience in Saudi Arabian enterprises. Every feature considers local hiring practices and compliance requirements.'
                    ],
                    [
                        'heading' => 'AI-First Architecture',
                        'content' => 'Entire platform designed around AI decision-making, not as add-on feature. Proprietary algorithms trained on relevant GCC hiring data. Continuous learning from customer data improves model accuracy over time.'
                    ],
                    [
                        'heading' => 'End-to-End Talent Lifecycle',
                        'content' => 'Unlike competitors focused only on recruitment, Connect AI covers entire employee journey from recruitment through performance management and development. Single source of truth for all talent data.'
                    ],
                    [
                        'heading' => 'Customer-Centric Innovation',
                        'content' => 'Built in partnership with leading Saudi Arabian enterprises. Flexible architecture allows rapid customization and feature development based on customer feedback. Regular sprints deliver continuous improvements.'
                    ],
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'استراتيجية التمييز',
                'sections' => [
                    [
                        'heading' => 'الخبرة الإقليمية والتوطين',
                        'content' => 'فهم عميق لأسواق مجلس التعاون الخليجي والفروقات الثقافية والمشهد التنظيمي. مبني من قبل فريق لديه خبرة مباشرة في المؤسسات السعودية. تأخذ كل ميزة في الاعتبار الممارسات المحلية للتوظيف ومتطلبات الامتثال.'
                    ],
                    [
                        'heading' => 'معمارية موجهة بالذكاء الاصطناعي أولاً',
                        'content' => 'تم تصميم المنصة بالكامل حول صنع القرار بالذكاء الاصطناعي، وليس كميزة إضافية. خوارزميات ملكية مدربة على بيانات التوظيف الخليجية ذات الصلة. التعلم المستمر من بيانات العملاء يحسن دقة النموذج بمرور الوقت.'
                    ],
                    [
                        'heading' => 'دورة حياة الموهبة الشاملة',
                        'content' => 'على عكس المنافسين الذين يركزون على التوظيف فقط، يغطي Connect AI رحلة الموظف بالكامل من التوظيف من خلال إدارة الأداء والتطوير. مصدر الحقيقة الوحيد لجميع بيانات الموهبة.'
                    ],
                    [
                        'heading' => 'الابتكار الموجه للعميل',
                        'content' => 'مبني بالشراكة مع المؤسسات السعودية الرائدة. المعمارية المرنة تسمح بالتخصيص السريع وتطوير الميزات بناءً على ملاحظات العملاء. الركضات العادية توفر تحسينات مستمرة.'
                    ],
                ]
            ]
        ]
    ],

    'usp_value_chain' => [
        'en' => [
            'key_value' => [
                'items' => [
                    ['key' => 'Data Collection & Enrichment', 'value' => 'Resume parsing, LinkedIn data, employment history'],
                    ['key' => 'AI Model Processing', 'value' => 'Screening, ranking, skills assessment algorithms'],
                    ['key' => 'Decision Support', 'value' => 'Explainable recommendations with confidence scores'],
                    ['key' => 'Workflow Integration', 'value' => 'Calendar sync, communication tools, HRIS systems'],
                    ['key' => 'Performance Analytics', 'value' => 'Employee tracking, KPI monitoring, reports'],
                    ['key' => 'Continuous Improvement', 'value' => 'Model retraining, feedback loops, optimization'],
                ]
            ]
        ],
        'ar' => [
            'key_value' => [
                'items' => [
                    ['key' => 'جمع البيانات والإثراء', 'value' => 'تحليل السيرة الذاتية، بيانات LinkedIn، سجل العمل'],
                    ['key' => 'معالجة نموذج الذكاء الاصطناعي', 'value' => 'فحص الخوارزميات، الترتيب، تقييم المهارات'],
                    ['key' => 'دعم القرار', 'value' => 'توصيات قابلة للتفسير مع درجات الثقة'],
                    ['key' => 'تكامل سير العمل', 'value' => 'مزامنة التقويم، أدوات الاتصال، أنظمة HRIS'],
                    ['key' => 'تحليلات الأداء', 'value' => 'تتبع الموظفين، مراقبة KPI، التقارير'],
                    ['key' => 'التحسين المستمر', 'value' => 'إعادة تدريب النموذج، حلقات الملاحظات، التحسين'],
                ]
            ]
        ]
    ],

    'cp_primary_persona' => [
        'en' => [
            'persona_card' => [
                'name' => 'Fatima Al-Dosari',
                'role' => 'HR Director',
                'age' => 38,
                'location' => 'Riyadh, Saudi Arabia',
                'quote' => 'I need to hire top talent quickly without the 3-month recruitment cycle.',
                'demographics' => [
                    'company_size' => '500-2000 employees',
                    'industry' => 'Technology & Finance',
                    'education' => 'Masters in HR Management',
                    'experience' => '12 years in talent management'
                ],
                'pain_points' => [
                    'Lengthy recruitment processes consuming 3-4 months',
                    'High volume of unqualified resume screening',
                    'Difficulty assessing cultural fit for Saudi enterprises',
                    'Limited visibility into employee performance metrics',
                    'Decentralized talent data across multiple systems'
                ],
                'goals' => [
                    'Reduce time-to-hire from 90 days to 30 days',
                    'Improve quality of new hires and retention rates',
                    'Create standardized hiring process across 15 branches',
                    'Implement data-driven talent management decisions',
                    'Enhance employee development and engagement'
                ],
                'motivations' => [
                    'Career advancement through operational excellence',
                    'Recognition for building high-performing teams',
                    'Modernizing outdated HR processes',
                    'Supporting company growth through better talent acquisition'
                ]
            ]
        ],
        'ar' => [
            'persona_card' => [
                'name' => 'فاطمة الدوسري',
                'role' => 'مديرة الموارد البشرية',
                'age' => 38,
                'location' => 'الرياض، المملكة العربية السعودية',
                'quote' => 'أحتاج إلى توظيف أفضل المواهب بسرعة بدون دورة التوظيف لمدة 3 أشهر.',
                'demographics' => [
                    'company_size' => '500-2000 موظف',
                    'industry' => 'التكنولوجيا والمالية',
                    'education' => 'ماجستير في إدارة الموارد البشرية',
                    'experience' => '12 سنة في إدارة المواهب'
                ],
                'pain_points' => [
                    'عمليات توظيف طويلة تستهلك 3-4 أشهر',
                    'حجم كبير من فحص السيرة الذاتية غير المؤهل',
                    'صعوبة تقييم التوافق الثقافي لمؤسسات سعودية',
                    'قابلية محدودة لرؤية مقاييس أداء الموظفين',
                    'بيانات الموهبة اللامركزية عبر الأنظمة المتعددة'
                ],
                'goals' => [
                    'تقليل وقت التوظيف من 90 يوم إلى 30 يوم',
                    'تحسين جودة الموظفين الجدد ومعدلات الاحتفاظ',
                    'إنشاء عملية توظيف موحدة عبر 15 فرع',
                    'تنفيذ قرارات إدارة المواهب المستندة إلى البيانات',
                    'تعزيز تطوير الموظفين والمشاركة'
                ],
                'motivations' => [
                    'التطور الوظيفي من خلال التميز التشغيلي',
                    'الاعتراف ببناء فرق عالية الأداء',
                    'تحديث عمليات الموارد البشرية القديمة',
                    'دعم نمو الشركة من خلال اكتساب المواهب الأفضل'
                ]
            ]
        ]
    ],

    'cp_secondary_persona' => [
        'en' => [
            'persona_card' => [
                'name' => 'Ahmed Al-Rashid',
                'role' => 'Hiring Manager / Department Head',
                'age' => 45,
                'location' => 'Dubai, UAE',
                'quote' => 'I need to fill 10 positions this quarter with qualified candidates.',
                'demographics' => [
                    'company_size' => '1000-5000 employees',
                    'industry' => 'Financial Services',
                    'education' => 'MBA',
                    'experience' => '15 years in management'
                ],
                'pain_points' => [
                    'Receiving hundreds of irrelevant applications',
                    'Spending 20 hours per week on recruitment tasks',
                    'Difficulty finding candidates with specific skill combinations',
                    'No visibility into hiring pipeline and candidate status',
                    'Poor collaboration between HR and hiring managers'
                ],
                'goals' => [
                    'Reduce time spent on recruitment admin tasks by 70%',
                    'Hire 10 qualified candidates within 8 weeks',
                    'Improve quality of initial interview candidates',
                    'Better track candidates through hiring process',
                    'Build stronger partnerships with HR team'
                ],
                'motivations' => [
                    'Meeting team hiring targets on schedule',
                    'Reducing operational workload and stress',
                    'Access to quality candidate information',
                    'Streamlined communication with HR team'
                ]
            ]
        ],
        'ar' => [
            'persona_card' => [
                'name' => 'أحمد الرشيد',
                'role' => 'مدير التوظيف / رئيس القسم',
                'age' => 45,
                'location' => 'دبي، الإمارات العربية المتحدة',
                'quote' => 'أحتاج إلى ملء 10 مواقع هذا الربع مع مرشحين مؤهلين.',
                'demographics' => [
                    'company_size' => '1000-5000 موظف',
                    'industry' => 'الخدمات المالية',
                    'education' => 'ماجستير في إدارة الأعمال',
                    'experience' => '15 سنة في الإدارة'
                ],
                'pain_points' => [
                    'تلقي مئات الطلبات غير ذات الصلة',
                    'قضاء 20 ساعة في الأسبوع على مهام التوظيف',
                    'صعوبة إيجاد مرشحين بمجموعات مهارات محددة',
                    'عدم وجود رؤية في خط أنابيب التوظيف وحالة المرشح',
                    'تعاون ضعيف بين الموارد البشرية ومديري التوظيف'
                ],
                'goals' => [
                    'تقليل الوقت الذي تقضيه على مهام إدارة التوظيف بنسبة 70٪',
                    'توظيف 10 مرشحين مؤهلين في غضون 8 أسابيع',
                    'تحسين جودة مرشحي المقابلة الأولية',
                    'تتبع أفضل للمرشحين من خلال عملية التوظيف',
                    'بناء شراكات أقوى مع فريق الموارس البشرية'
                ],
                'motivations' => [
                    'تحقيق أهداف التوظيف في الموعد المحدد',
                    'تقليل العبء التشغيلي والإجهاد',
                    'الوصول إلى معلومات المرشح ذات الجودة',
                    'تواصل مبسط مع فريق الموارد البشرية'
                ]
            ]
        ]
    ],

    'cp_buyer_journey' => [
        'en' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'Awareness Stage',
                        'description' => 'HR directors discover recruitment challenges in industry discussions and peer recommendations',
                        'touchpoints' => ['Industry events', 'Peer networks', 'LinkedIn content', 'HR publications'],
                        'actions' => ['Content marketing', 'Thought leadership', 'Speaking engagements', 'Case studies']
                    ],
                    [
                        'title' => 'Consideration Stage',
                        'description' => 'Evaluate Connect AI against competitors through demos, trials, and customer references',
                        'touchpoints' => ['Product demo', 'Free trial', 'Customer testimonials', 'ROI calculator'],
                        'actions' => ['Request demo', 'Start trial', 'Read reviews', 'Contact sales']
                    ],
                    [
                        'title' => 'Decision Stage',
                        'description' => 'Final negotiations on pricing, implementation timeline, and customization requirements',
                        'touchpoints' => ['Sales negotiation', 'Implementation plan', 'Service terms', 'Integration requirements'],
                        'actions' => ['Sign contract', 'Schedule onboarding', 'Configure system', 'Train teams']
                    ],
                    [
                        'title' => 'Adoption Stage',
                        'description' => 'Successful implementation and team adoption of Connect AI platform',
                        'touchpoints' => ['Onboarding training', 'Support resources', 'Success manager', 'User community'],
                        'actions' => ['Use platform daily', 'Provide feedback', 'Expand usage', 'Renew contract']
                    ]
                ]
            ]
        ],
        'ar' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'مرحلة الوعي',
                        'description' => 'يكتشف مديرو الموارس البشرية تحديات التوظيف في نقاشات الصناعة والتوصيات من الأقران',
                        'touchpoints' => ['فعاليات الصناعة', 'شبكات الأقران', 'محتوى LinkedIn', 'منشورات الموارد البشرية'],
                        'actions' => ['تسويق المحتوى', 'قيادة الفكر', 'المشاركة في الفعاليات', 'دراسات الحالات']
                    ],
                    [
                        'title' => 'مرحلة الاعتبار',
                        'description' => 'تقييم Connect AI مقابل المنافسين من خلال العروض التوضيحية والتجارب والمراجع من العملاء',
                        'touchpoints' => ['عرض توضيحي للمنتج', 'تجربة مجانية', 'شهادات العملاء', 'حاسبة العائد على الاستثمار'],
                        'actions' => ['طلب عرض توضيحي', 'بدء التجربة', 'قراءة المراجعات', 'الاتصال بالمبيعات']
                    ],
                    [
                        'title' => 'مرحلة القرار',
                        'description' => 'المفاوضات النهائية بشأن التسعير وجدول الزمني للتنفيذ ومتطلبات التخصيص',
                        'touchpoints' => ['مفاوضات المبيعات', 'خطة التنفيذ', 'شروط الخدمة', 'متطلبات التكامل'],
                        'actions' => ['توقيع العقد', 'جدولة التدريب', 'تكوين النظام', 'تدريب الفرق']
                    ],
                    [
                        'title' => 'مرحلة التبني',
                        'description' => 'التنفيذ الناجح وتبني الفريق لمنصة Connect AI',
                        'touchpoints' => ['تدريب الإعداد', 'موارد الدعم', 'مدير النجاح', 'مجتمع المستخدمين'],
                        'actions' => ['استخدم المنصة يوميًا', 'قدم ملاحظات', 'توسيع الاستخدام', 'تجديد العقد']
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
                        'heading' => 'Recruitment Process Inefficiency',
                        'content' => 'Current hiring processes take 90+ days across GCC enterprises. HR teams spend 60% of time on administrative tasks like screening resumes. Manual review of hundreds of applications leads to fatigue and poor decision-making. Lack of standardized processes across multiple branches creates inconsistency.'
                    ],
                    [
                        'heading' => 'Poor Quality Hiring Decisions',
                        'content' => 'Without data-driven insights, hiring managers rely on gut feeling and limited interview time. High failure rate within first year of employment. Difficulty assessing soft skills, cultural fit, and potential for growth. No predictive analytics on which candidates will succeed.'
                    ],
                    [
                        'heading' => 'Talent Data Fragmentation',
                        'content' => 'Employee information scattered across HR systems, email, spreadsheets. No single source of truth for candidate and employee data. Difficulty tracking candidate journey from application to onboarding. Limited visibility into employee performance post-hire.'
                    ],
                    [
                        'heading' => 'Compliance and Regulatory Challenges',
                        'content' => 'Saudi Arabia hiring regulations require careful compliance. Limited documentation and audit trails for hiring decisions. Risk of discrimination claims without clear decision rationale. Difficulty maintaining GDPR compliance for candidate data from international applicants.'
                    ],
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'تحليل نقاط الألم للعملاء',
                'sections' => [
                    [
                        'heading' => 'عدم كفاءة عملية التوظيف',
                        'content' => 'تستغرق عمليات التوظيف الحالية 90+ يومًا عبر مؤسسات مجلس التعاون الخليجي. تقضي فرق الموارس البشرية 60٪ من الوقت في مهام إدارية مثل فحص السيرة الذاتية. يؤدي الفحص اليدوي لمئات التطبيقات إلى الإرهاق وسوء اتخاذ القرار. يؤدي عدم وجود عمليات موحدة عبر فروع متعددة إلى عدم الاتساق.'
                    ],
                    [
                        'heading' => 'قرارات التوظيف منخفضة الجودة',
                        'content' => 'بدون رؤى قائمة على البيانات، يعتمد مديرو التوظيف على الحدس والوقت المحدود للمقابلة. معدل فشل عالي في السنة الأولى من العمل. صعوبة تقييم المهارات الناعمة والتوافق الثقافي والإمكانات للنمو. لا توجد تحليلات تنبؤية حول المرشحين الذين سينجحون.'
                    ],
                    [
                        'heading' => 'تجزئة بيانات الموهبة',
                        'content' => 'معلومات الموظف مبعثرة عبر أنظمة الموارس البشرية والبريد الإلكتروني والجداول. لا يوجد مصدر وحيد للحقيقة لبيانات المرشح والموظف. صعوبة تتبع رحلة المرشح من التطبيق إلى الإعداد. قابلية محدودة لرؤية أداء الموظفين بعد التوظيف.'
                    ],
                    [
                        'heading' => 'تحديات الامتثال التنظيمي',
                        'content' => 'تتطلب لوائح التوظيف في المملكة العربية السعودية الامتثال الدقيق. التوثيق والآثار المحدودة لقرارات التوظيف. خطر المطالبات بالتمييز بدون توضيح واضح للقرار. صعوبة الحفاظ على الامتثال GDPR لبيانات المرشح من المتقدمين الدوليين.'
                    ],
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
                        'price' => '$299/month',
                        'features' => [
                            'Up to 50 monthly hires',
                            'AI resume screening',
                            'Basic candidate ranking',
                            'Email support',
                            '2 user seats'
                        ],
                        'highlighted' => false,
                        'cta' => 'Start Free Trial'
                    ],
                    [
                        'name' => 'Professional',
                        'price' => '$899/month',
                        'features' => [
                            'Unlimited monthly hires',
                            'Advanced AI screening',
                            'Predictive analytics',
                            'Performance management',
                            '10 user seats',
                            'Priority support',
                            'Custom integrations'
                        ],
                        'highlighted' => true,
                        'cta' => 'Request Demo'
                    ],
                    [
                        'name' => 'Enterprise',
                        'price' => 'Custom',
                        'features' => [
                            'Unlimited everything',
                            'Dedicated account manager',
                            'Custom workflows',
                            'Advanced security',
                            'Unlimited user seats',
                            '24/7 premium support',
                            'On-premise deployment'
                        ],
                        'highlighted' => false,
                        'cta' => 'Contact Sales'
                    ]
                ]
            ]
        ],
        'ar' => [
            'pricing_cards' => [
                'tiers' => [
                    [
                        'name' => 'المبتدئ',
                        'price' => '1121 ريال/شهر',
                        'features' => [
                            'حتى 50 عملية توظيف شهرية',
                            'فحص السيرة الذاتية بالذكاء الاصطناعي',
                            'ترتيب المرشحين الأساسي',
                            'دعم البريد الإلكتروني',
                            'مقاعد المستخدم 2'
                        ],
                        'highlighted' => false,
                        'cta' => 'ابدأ النسخة التجريبية المجانية'
                    ],
                    [
                        'name' => 'احترافي',
                        'price' => '3371 ريال/شهر',
                        'features' => [
                            'عمليات توظيف شهرية غير محدودة',
                            'فحص متقدم بالذكاء الاصطناعي',
                            'تحليلات تنبؤية',
                            'إدارة الأداء',
                            'مقاعد المستخدم 10',
                            'دعم ذي أولوية',
                            'تكاملات مخصصة'
                        ],
                        'highlighted' => true,
                        'cta' => 'طلب عرض توضيحي'
                    ],
                    [
                        'name' => 'مؤسسة',
                        'price' => 'مخصص',
                        'features' => [
                            'كل شيء غير محدود',
                            'مدير حساب مخصص',
                            'سير عمل مخصص',
                            'أمان متقدم',
                            'مقاعد المستخدم غير محدود',
                            'دعم متميز 24/7',
                            'نشر محلي'
                        ],
                        'highlighted' => false,
                        'cta' => 'اتصل بفريق المبيعات'
                    ]
                ]
            ]
        ]
    ],

    'fin_cost_structure' => [
        'en' => [
            'progress_bars' => [
                'items' => [
                    ['label' => 'AI & ML Infrastructure', 'value' => 35, 'suffix' => '%'],
                    ['label' => 'Engineering & Development', 'value' => 25, 'suffix' => '%'],
                    ['label' => 'Sales & Marketing', 'value' => 20, 'suffix' => '%'],
                    ['label' => 'Operations & Support', 'value' => 15, 'suffix' => '%'],
                    ['label' => 'General & Administrative', 'value' => 5, 'suffix' => '%'],
                ]
            ]
        ],
        'ar' => [
            'progress_bars' => [
                'items' => [
                    ['label' => 'البنية التحتية للذكاء الاصطناعي والتعلم الآلي', 'value' => 35, 'suffix' => '٪'],
                    ['label' => 'الهندسة والتطوير', 'value' => 25, 'suffix' => '٪'],
                    ['label' => 'المبيعات والتسويق', 'value' => 20, 'suffix' => '٪'],
                    ['label' => 'العمليات والدعم', 'value' => 15, 'suffix' => '٪'],
                    ['label' => 'العام والإداري', 'value' => 5, 'suffix' => '٪'],
                ]
            ]
        ]
    ],

    'fin_financial_projections' => [
        'en' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'Year 1 Revenue', 'value' => '$1.8M', 'description' => 'ARR from current customer base'],
                    ['label' => 'Year 2 Projection', 'value' => '$4.2M', 'description' => 'Targeting 133% YoY growth'],
                    ['label' => 'Year 3 Projection', 'value' => '$9.8M', 'description' => 'Expanding GCC presence'],
                    ['label' => 'Gross Margin', 'value' => '72%', 'description' => 'SaaS model with platform leverage'],
                ]
            ]
        ],
        'ar' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'إيرادات السنة 1', 'value' => '1.8 مليون دولار', 'description' => 'ARR من قاعدة العملاء الحالية'],
                    ['label' => 'توقعات السنة 2', 'value' => '4.2 مليون دولار', 'description' => 'استهداف نمو سنوي بنسبة 133٪'],
                    ['label' => 'توقعات السنة 3', 'value' => '9.8 مليون دولار', 'description' => 'توسيع الحضور في مجلس التعاون الخليجي'],
                    ['label' => 'إجمالي الهامش', 'value' => '72٪', 'description' => 'نموذج SaaS مع نفوذ المنصة'],
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
                        'content' => 'Seeking $3M in Series A funding to accelerate product development, expand GCC market presence, and build sales team. Current ARR of $1.8M demonstrates product-market fit and strong customer traction.'
                    ],
                    [
                        'heading' => 'Use of Funds',
                        'content' => 'Product Development (40%): Advanced AI features, integrations, mobile app. Sales & Marketing (35%): Regional sales team, marketing campaigns, partnerships. Operations (15%): Infrastructure scaling, support team expansion. General (10%): Legal, finance, administration.'
                    ],
                    [
                        'heading' => 'Funding Timeline',
                        'content' => 'Series A funding will support 24-month runway to profitability. Target break-even by end of Year 2 based on projected growth trajectory and improving unit economics.'
                    ],
                    [
                        'heading' => 'Capital Efficiency',
                        'content' => 'CAC of $8,000 per customer with 36-month LTV of $180,000 results in LTV:CAC ratio of 22.5x. Payback period of 5.3 months demonstrates strong capital efficiency.'
                    ],
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'متطلبات التمويل',
                'sections' => [
                    [
                        'heading' => 'هدف تمويل Series A',
                        'content' => 'البحث عن 3 ملايين دولار في تمويل Series A لتسريع تطوير المنتج وتوسيع الحضور في سوق مجلس التعاون الخليجي وبناء فريق المبيعات. يوضح ARR الحالي البالغ 1.8 مليون دولار توافق المنتج مع السوق وجاذبية العملاء القوية.'
                    ],
                    [
                        'heading' => 'استخدام الأموال',
                        'content' => 'تطوير المنتج (40٪): ميزات الذكاء الاصطناعي المتقدمة والتكاملات والتطبيق الجوال. المبيعات والتسويق (35٪): فريق المبيعات الإقليمي وحملات التسويق والشراكات. العمليات (15٪): توسيع البنية التحتية وتوسيع فريق الدعم. عام (10٪): القانونية والمالية والإدارة.'
                    ],
                    [
                        'heading' => 'جدول التمويل',
                        'content' => 'سيوفر تمويل Series A مدرج زمني بمدة 24 شهرًا للوصول إلى الربحية. استهدف تحقيق التعادل بحلول نهاية السنة 2 بناءً على مسار النمو المتوقع واقتصاديات الوحدة المحسنة.'
                    ],
                    [
                        'heading' => 'كفاءة رأس المال',
                        'content' => 'CAC البالغ 8000 دولار لكل عميل مع LTV لمدة 36 شهرًا بقيمة 180000 دولار ينتج نسبة LTV:CAC بقيمة 22.5x. فترة استرجاع الاستثمار 5.3 أشهر توضح كفاءة رأس المال القوية.'
                    ],
                ]
            ]
        ]
    ],

    'fin_unit_economics' => [
        'en' => [
            'key_value' => [
                'items' => [
                    ['key' => 'Average Contract Value', 'value' => '$15,000/year'],
                    ['key' => 'Customer Acquisition Cost', 'value' => '$8,000'],
                    ['key' => 'Customer Lifetime Value', 'value' => '$180,000'],
                    ['key' => 'LTV:CAC Ratio', 'value' => '22.5x'],
                    ['key' => 'Payback Period', 'value' => '5.3 months'],
                    ['key' => 'Annual Churn Rate', 'value' => '8%'],
                    ['key' => 'Net Revenue Retention', 'value' => '115%'],
                ]
            ]
        ],
        'ar' => [
            'key_value' => [
                'items' => [
                    ['key' => 'متوسط قيمة العقد', 'value' => '15000 دولار/سنة'],
                    ['key' => 'تكلفة اكتساب العملاء', 'value' => '8000 دولار'],
                    ['key' => 'قيمة عمر العميل', 'value' => '180000 دولار'],
                    ['key' => 'نسبة LTV:CAC', 'value' => '22.5x'],
                    ['key' => 'فترة الاسترجاع', 'value' => '5.3 أشهر'],
                    ['key' => 'معدل الخسارة السنوي', 'value' => '8%'],
                    ['key' => 'صافي الاحتفاظ بالإيرادات', 'value' => '115٪'],
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
                        'heading' => 'Market Entry Strategy',
                        'content' => 'Launch first in Saudi Arabia with Riyadh and Jeddah as primary markets. Target mid-market to enterprise segment (500-5000+ employees) in technology, finance, and manufacturing sectors. Build brand as the GCC-native AI recruiting platform vs. Western competitors.'
                    ],
                    [
                        'heading' => 'Sales Approach',
                        'content' => 'Build direct sales team focused on enterprise customers with 6-9 month sales cycles. Implement freemium model for mid-market with land-and-expand strategy. Hire sales leaders with deep Saudi/GCC enterprise relationships. Target Fortune 500 GCC companies and local market leaders.'
                    ],
                    [
                        'heading' => 'Partnership Strategy',
                        'content' => 'Partner with HR consulting firms and management consultancies as channel partners. Create integrations with major HRIS systems (SAP SuccessFactors, Workday) and local payroll providers. Build reseller program for regional HR service providers.'
                    ],
                    [
                        'heading' => 'Marketing & Brand',
                        'content' => 'Position as innovative Saudi Arabian AI company solving GCC hiring challenges. Content marketing on HR transformation and AI adoption. Speaking at regional HR conferences and events. Build social proof through customer case studies and testimonials from respected enterprises.'
                    ],
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'استراتيجية إطلاق الدخول إلى السوق',
                'sections' => [
                    [
                        'heading' => 'استراتيجية الدخول إلى السوق',
                        'content' => 'الإطلاق أولاً في المملكة العربية السعودية مع الرياض وجدة كأسواق أساسية. استهدف قطاع منتصف السوق إلى المؤسسات (500-5000+ موظف) في قطاعات التكنولوجيا والمالية والتصنيع. بناء العلامة التجارية كمنصة التوظيف الأصلية لمجلس التعاون الخليجي بالذكاء الاصطناعي مقابل المنافسين الغربيين.'
                    ],
                    [
                        'heading' => 'نهج المبيعات',
                        'content' => 'بناء فريق مبيعات مباشر يركز على عملاء المؤسسات مع دورات مبيعات 6-9 أشهر. تنفيذ نموذج freemium لمنتصف السوق مع استراتيجية التوسع. توظيف قادة المبيعات ذوي العلاقات العميقة بمؤسسات السعودية ومجلس التعاون الخليجي. استهدف شركات Fortune 500 في مجلس التعاون الخليجي والقادة في السوق المحلية.'
                    ],
                    [
                        'heading' => 'استراتيجية الشراكة',
                        'content' => 'شراكة مع شركات استشارات الموارس البشرية والاستشارات الإدارية كشركاء قنوات. إنشاء تكاملات مع أنظمة HRIS الرئيسية (SAP SuccessFactors، Workday) ومزودي الرواتب المحليين. بناء برنامج بائع جديد لمزودي خدمات الموارس البشرية الإقليميين.'
                    ],
                    [
                        'heading' => 'التسويق والعلامة التجارية',
                        'content' => 'موضعها كشركة ذكاء اصطناعي سعودية مبتكرة تحل تحديات التوظيف في مجلس التعاون الخليجي. تسويق المحتوى حول تحول الموارس البشرية واعتماد الذكاء الاصطناعي. الحديث في مؤتمرات الموارس البشرية والفعاليات الإقليمية. بناء الإثبات الاجتماعي من خلال دراسات حالات العملاء والشهادات من المؤسسات المحترمة.'
                    ],
                ]
            ]
        ]
    ],

    'gtm_marketing_channels' => [
        'en' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'Content Marketing', 'value' => '25%', 'description' => 'Blog, whitepapers, case studies, webinars'],
                    ['label' => 'Direct Sales', 'value' => '35%', 'description' => 'Enterprise sales team, account executives'],
                    ['label' => 'Partnerships', 'value' => '20%', 'description' => 'Channel partners, integrations, resellers'],
                    ['label' => 'Events & PR', 'value' => '15%', 'description' => 'Conferences, speaking engagements, media'],
                    ['label' => 'Digital Marketing', 'value' => '5%', 'description' => 'LinkedIn, Google Ads, paid social'],
                ]
            ]
        ],
        'ar' => [
            'stat_cards' => [
                'metrics' => [
                    ['label' => 'تسويق المحتوى', 'value' => '25%', 'description' => 'مدونة ودراسات بيضاء ودراسات حالات وندوات عبر الويب'],
                    ['label' => 'المبيعات المباشرة', 'value' => '35%', 'description' => 'فريق مبيعات المؤسسات ومديرو الحسابات'],
                    ['label' => 'الشراكات', 'value' => '20%', 'description' => 'شركاء القنوات والتكاملات والبائعون'],
                    ['label' => 'الفعاليات والعلاقات العامة', 'value' => '15%', 'description' => 'المؤتمرات والمحاضرات والإعلام'],
                    ['label' => 'التسويق الرقمي', 'value' => '5%', 'description' => 'LinkedIn و Google Ads والوسائط الاجتماعية المدفوعة'],
                ]
            ]
        ]
    ],

    'gtm_sales_funnel' => [
        'en' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'Awareness',
                        'description' => 'Marketing generates leads through content, events, and partnerships',
                        'touchpoints' => ['Company website', 'LinkedIn content', 'Industry events', 'Referrals'],
                        'actions' => ['Visit website', 'Download content', 'Attend event', 'Get referral']
                    ],
                    [
                        'title' => 'Engagement',
                        'description' => 'Qualified leads schedule demos and explore product capabilities',
                        'touchpoints' => ['Product demo', 'Free trial signup', 'Email campaigns', 'Sales call'],
                        'actions' => ['Request demo', 'Start trial', 'Respond to email', 'Schedule call']
                    ],
                    [
                        'title' => 'Evaluation',
                        'description' => 'Prospects evaluate pricing, implementation, and fit with requirements',
                        'touchpoints' => ['Pricing details', 'Implementation plan', 'References', 'ROI calculator'],
                        'actions' => ['Review pricing', 'Get implementation details', 'Talk to reference', 'Calculate ROI']
                    ],
                    [
                        'title' => 'Closure',
                        'description' => 'Contract negotiation and signing of enterprise agreement',
                        'touchpoints' => ['Legal review', 'Deal terms', 'Contract signing', 'Onboarding start'],
                        'actions' => ['Negotiate terms', 'Sign contract', 'Begin onboarding', 'Make first payment']
                    ]
                ]
            ]
        ],
        'ar' => [
            'journey_timeline' => [
                'stages' => [
                    [
                        'title' => 'الوعي',
                        'description' => 'التسويق يولد عملاء محتملين من خلال المحتوى والفعاليات والشراكات',
                        'touchpoints' => ['موقع الشركة', 'محتوى LinkedIn', 'فعاليات الصناعة', 'الإحالات'],
                        'actions' => ['زيارة الموقع', 'تنزيل المحتوى', 'حضور الفعالية', 'الحصول على إحالة']
                    ],
                    [
                        'title' => 'المشاركة',
                        'description' => 'يجدول العملاء المحتملون المؤهلون عروضًا توضيحية واستكشاف إمكانيات المنتج',
                        'touchpoints' => ['عرض توضيحي للمنتج', 'التسجيل للتجربة المجانية', 'حملات البريد الإلكتروني', 'مكالمة المبيعات'],
                        'actions' => ['طلب عرض توضيحي', 'ابدأ التجربة', 'الرد على البريد الإلكتروني', 'جدولة مكالمة']
                    ],
                    [
                        'title' => 'التقييم',
                        'description' => 'يقيم العملاء المحتملون التسعير والتنفيذ والملاءمة مع المتطلبات',
                        'touchpoints' => ['تفاصيل التسعير', 'خطة التنفيذ', 'المراجع', 'حاسبة العائد على الاستثمار'],
                        'actions' => ['مراجعة التسعير', 'الحصول على تفاصيل التنفيذ', 'التحدث إلى مرجع', 'حساب العائد على الاستثمار']
                    ],
                    [
                        'title' => 'الإغلاق',
                        'description' => 'مفاوضات العقد وتوقيع اتفاقية المؤسسة',
                        'touchpoints' => ['المراجعة القانونية', 'شروط التعامل', 'توقيع العقد', 'بدء الإعداد'],
                        'actions' => ['التفاوض على الشروط', 'توقيع العقد', 'بدء الإعداد', 'الدفع الأول']
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
                        'heading' => 'Technology Partnerships',
                        'content' => 'Integration partnerships with HRIS vendors (SAP SuccessFactors, Workday, BambooHR) to extend market reach. Integration with recruitment platforms (LinkedIn Recruiter, Indeed) for enhanced candidate sourcing. Cloud infrastructure partnerships (AWS, Azure) for reliable scaling.'
                    ],
                    [
                        'heading' => 'Consulting & Services',
                        'content' => 'Partner with top HR consulting firms as channel partners to reach enterprise clients. Training and certification programs for implementation partners. Revenue sharing model to incentivize partner sales efforts and growth.'
                    ],
                    [
                        'heading' => 'Industry Associations',
                        'content' => 'Membership in Saudi Arabia HR Association and GCC Business Councils to gain credibility. Sponsorship of HR conferences and talent management summits. Thought leadership positions in industry publications.'
                    ],
                    [
                        'heading' => 'Academic & Research',
                        'content' => 'Partnerships with universities for talent pipeline and research collaborations. Sponsorship of HR research studies to build brand awareness. Case studies and research publications to establish thought leadership.'
                    ],
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'الشراكات الاستراتيجية',
                'sections' => [
                    [
                        'heading' => 'شراكات التكنولوجيا',
                        'content' => 'شراكات التكامل مع بائعي HRIS (SAP SuccessFactors، Workday، BambooHR) لتوسيع نطاق السوق. التكامل مع منصات التوظيف (LinkedIn Recruiter، Indeed) للبحث عن المرشحين المحسّن. شراكات البنية التحتية السحابية (AWS، Azure) للتوسع الموثوق.'
                    ],
                    [
                        'heading' => 'الاستشارات والخدمات',
                        'content' => 'شراكة مع أفضل شركات استشارات الموارس البشرية كشركاء قنوات للوصول إلى عملاء المؤسسات. برامج التدريب والشهادات لشركاء التنفيذ. نموذج تقاسم الإيرادات لتحفيز جهود المبيعات والنمو.'
                    ],
                    [
                        'heading' => 'جمعيات الصناعة',
                        'content' => 'العضوية في جمعية الموارس البشرية بالمملكة العربية السعودية ومجالس الأعمال بمجلس التعاون الخليجي للحصول على المصداقية. رعاية مؤتمرات الموارس البشرية وقمم إدارة المواهب. مواقف قيادة الفكر في منشورات الصناعة.'
                    ],
                    [
                        'heading' => 'الأكاديمية والأبحاث',
                        'content' => 'الشراكات مع الجامعات لخط أنابيب المواهب والتعاون في البحث. رعاية دراسات البحث في الموارس البشرية لبناء الوعي بالعلامة التجارية. دراسات الحالات ومنشورات البحث لتأسيس قيادة الفكر.'
                    ],
                ]
            ]
        ]
    ],

    'gtm_growth_metrics' => [
        'en' => [
            'progress_bars' => [
                'items' => [
                    ['label' => 'Customer Acquisition Growth', 'value' => 85, 'suffix' => '%'],
                    ['label' => 'Net Revenue Retention', 'value' => 115, 'suffix' => '%'],
                    ['label' => 'Product Usage Adoption', 'value' => 78, 'suffix' => '%'],
                    ['label' => 'Customer Satisfaction (NPS)', 'value' => 72, 'suffix' => '%'],
                    ['label' => 'Market Penetration in Target', 'value' => 12, 'suffix' => '%'],
                ]
            ]
        ],
        'ar' => [
            'progress_bars' => [
                'items' => [
                    ['label' => 'نمو اكتساب العملاء', 'value' => 85, 'suffix' => '٪'],
                    ['label' => 'صافي الاحتفاظ بالإيرادات', 'value' => 115, 'suffix' => '٪'],
                    ['label' => 'اعتماد استخدام المنتج', 'value' => 78, 'suffix' => '٪'],
                    ['label' => 'رضا العملاء (NPS)', 'value' => 72, 'suffix' => '٪'],
                    ['label' => 'اختراق السوق في الهدف', 'value' => 12, 'suffix' => '٪'],
                ]
            ]
        ]
    ],

    'ca_competitor_overview' => [
        'en' => [
            'text_content' => [
                'title' => 'Competitive Analysis Overview',
                'sections' => [
                    [
                        'heading' => 'Direct Competitors',
                        'content' => 'Workable (Ireland): General recruitment platform with basic AI, limited Arabic support, established in GCC but generic approach. Lever (USA): Enterprise recruiting tool, strong in tech sector, limited performance management. Ashby (USA): Modern ATS with good UX, growing GCC presence, minimal AI capabilities.'
                    ],
                    [
                        'heading' => 'Indirect Competitors',
                        'content' => 'LinkedIn Recruiter: Strong network effects, expensive for SMB, limited integration with internal systems. SAP SuccessFactors: Enterprise HRIS with talent module, lacks modern UX, AI features limited. Workday: Comprehensive HR platform, slow to innovate, implementation complexity.'
                    ],
                    [
                        'heading' => 'Market Position',
                        'content' => 'Connect AI uniquely combines GCC-specific expertise, advanced AI capabilities, and integrated talent management. Competitors either focus on recruitment (lacking performance management) or are HRIS-centric (lacking recruitment focus). No direct competitor combines all three elements with GCC localization.'
                    ],
                    [
                        'heading' => 'Competitive Advantages',
                        'content' => 'Native Arabic support with cultural understanding. Regional team with deep GCC enterprise knowledge. Purpose-built AI trained on GCC hiring data. Integrated lifecycle approach vs. point solutions. Superior user experience for modern workforce. Flexible pricing for mid-market segment.'
                    ],
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'نظرة عامة على التحليل التنافسي',
                'sections' => [
                    [
                        'heading' => 'المنافسون المباشرون',
                        'content' => 'Workable (أيرلندا): منصة توظيف عامة مع ذكاء اصطناعي أساسي، دعم عربي محدود، راسخة في مجلس التعاون الخليجي لكن نهج عام. Lever (الولايات المتحدة): أداة توظيف المؤسسات، قوية في قطاع التكنولوجيا، إدارة الأداء محدودة. Ashby (الولايات المتحدة): ATS حديث مع UX جيد، حضور متزايد في مجلس التعاون الخليجي، قدرات الذكاء الاصطناعي ضئيلة.'
                    ],
                    [
                        'heading' => 'المنافسون غير المباشرين',
                        'content' => 'LinkedIn Recruiter: آثار شبكة قوية، مكلفة للشركات الصغيرة والمتوسطة، تكامل محدود مع الأنظمة الداخلية. SAP SuccessFactors: HRIS المؤسسة مع وحدة الموهبة، تفتقد UX الحديثة، ميزات الذكاء الاصطناعي محدودة. Workday: منصة موارد بشرية شاملة، بطيئة في الابتكار، تعقيد التنفيذ.'
                    ],
                    [
                        'heading' => 'موقف السوق',
                        'content' => 'يجمع Connect AI بشكل فريد بين الخبرة الخاصة بمجلس التعاون الخليجي وقدرات الذكاء الاصطناعي المتقدمة وإدارة الموهبة المتكاملة. يركز المنافسون إما على التوظيف (افتقار إدارة الأداء) أو يركزون على HRIS (افتقار التركيز على التوظيف). لا يوجد منافس مباشر يجمع بين جميع العناصر الثلاثة مع التوطين في مجلس التعاون الخليجي.'
                    ],
                    [
                        'heading' => 'المزايا التنافسية',
                        'content' => 'دعم عربي أصلي مع فهم ثقافي. فريق إقليمي لديه معرفة عميقة بمؤسسات مجلس التعاون الخليجي. ذكاء اصطناعي مصمم خصيصًا مدرب على بيانات التوظيف في مجلس التعاون الخليجي. نهج دورة حياة متكامل مقابل الحلول النقطية. تجربة مستخدم متفوقة للقوى العاملة الحديثة. تسعير مرن لقطاع منتصف السوق.'
                    ],
                ]
            ]
        ]
    ],

    'ca_feature_comparison' => [
        'en' => [
            'comparison_table' => [
                'headers' => ['Feature Category', 'Connect AI', 'Workable', 'Lever', 'Ashby', 'SAP SuccessFactors'],
                'rows' => [
                    ['AI Resume Screening', 'Advanced', 'Basic', 'Moderate', 'Basic', 'Limited'],
                    ['Performance Management', 'Full Suite', 'Limited', 'None', 'None', 'Full Suite'],
                    ['Arabic Language Support', 'Native', 'Basic', 'None', 'None', 'Basic'],
                    ['GCC-Specific Features', 'Yes', 'No', 'No', 'No', 'No'],
                    ['Interview Scheduling', 'Automated', 'Manual', 'Automated', 'Manual', 'Manual'],
                    ['Candidate Analytics', 'Predictive', 'Basic', 'Basic', 'Limited', 'Basic'],
                    ['API & Integrations', 'Extensive', 'Moderate', 'Extensive', 'Limited', 'Extensive'],
                    ['User Experience', 'Modern', 'Outdated', 'Modern', 'Modern', 'Outdated'],
                    ['Pricing Flexibility', 'High', 'Moderate', 'Low', 'Moderate', 'Low'],
                ]
            ]
        ],
        'ar' => [
            'comparison_table' => [
                'headers' => ['فئة الميزة', 'Connect AI', 'Workable', 'Lever', 'Ashby', 'SAP SuccessFactors'],
                'rows' => [
                    ['فحص السيرة الذاتية بالذكاء الاصطناعي', 'متقدم', 'أساسي', 'معتدل', 'أساسي', 'محدود'],
                    ['إدارة الأداء', 'مجموعة كاملة', 'محدود', 'لا شيء', 'لا شيء', 'مجموعة كاملة'],
                    ['دعم اللغة العربية', 'أصلي', 'أساسي', 'لا', 'لا', 'أساسي'],
                    ['ميزات خاصة بمجلس التعاون الخليجي', 'نعم', 'لا', 'لا', 'لا', 'لا'],
                    ['جدولة المقابلات', 'آلي', 'يدوي', 'آلي', 'يدوي', 'يدوي'],
                    ['تحليلات المرشحين', 'تنبؤي', 'أساسي', 'أساسي', 'محدود', 'أساسي'],
                    ['API والتكاملات', 'واسع', 'معتدل', 'واسع', 'محدود', 'واسع'],
                    ['تجربة المستخدم', 'حديث', 'قديم', 'حديث', 'حديث', 'قديم'],
                    ['مرونة التسعير', 'مرتفع', 'معتدل', 'منخفض', 'معتدل', 'منخفض'],
                ]
            ]
        ]
    ],

    'ca_market_positioning' => [
        'en' => [
            'key_value' => [
                'items' => [
                    ['key' => 'Primary Market', 'value' => 'GCC Enterprises (Mid-Market to Enterprise)'],
                    ['key' => 'Target Industries', 'value' => 'Technology, Finance, Manufacturing, Consulting'],
                    ['key' => 'Positioning', 'value' => 'The AI-Native Talent Platform Built for GCC'],
                    ['key' => 'Key Differentiator', 'value' => 'GCC-Specific AI + Integrated Talent Lifecycle'],
                    ['key' => 'Price Point', 'value' => '$299-$899/month SaaS + Enterprise Custom'],
                    ['key' => 'Sales Model', 'value' => 'Direct Enterprise Sales + Channel Partnerships'],
                    ['key' => 'Market Opportunity', 'value' => '$2.3B TAM in GCC region'],
                ]
            ]
        ],
        'ar' => [
            'key_value' => [
                'items' => [
                    ['key' => 'السوق الأساسي', 'value' => 'مؤسسات مجلس التعاون الخليجي (منتصف السوق إلى المؤسسة)'],
                    ['key' => 'الصناعات المستهدفة', 'value' => 'التكنولوجيا والمالية والتصنيع والاستشارات'],
                    ['key' => 'الموضعة', 'value' => 'منصة الموهبة الأصلية بالذكاء الاصطناعي المبنية لمجلس التعاون الخليجي'],
                    ['key' => 'المتمايزة الرئيسية', 'value' => 'ذكاء اصطناعي خاص بمجلس التعاون الخليجي + دورة حياة الموهبة المتكاملة'],
                    ['key' => 'نقطة السعر', 'value' => '299-899 دولار/شهر SaaS + مخصص المؤسسات'],
                    ['key' => 'نموذج المبيعات', 'value' => 'مبيعات المؤسسات المباشرة + شراكات القنوات'],
                    ['key' => 'فرصة السوق', 'value' => '2.3 مليار دولار TAM في منطقة مجلس التعاون الخليجي'],
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
                        'heading' => 'Data Network Effect',
                        'content' => 'As Connect AI processes more hiring decisions in GCC market, AI models become increasingly accurate and valuable. Proprietary dataset of GCC hiring patterns creates network effect that competitors cannot replicate. First-mover advantage in GCC-trained AI models.'
                    ],
                    [
                        'heading' => 'Switching Costs & Customer Lock-in',
                        'content' => 'Historical hiring data stored in Connect AI platform becomes valuable organizational asset. Integration with existing HRIS and recruitment workflows increases switching costs. Performance metrics and analytics dependent on continuous data accumulation in platform.'
                    ],
                    [
                        'heading' => 'Brand & Regional Expertise',
                        'content' => 'Deep understanding of GCC labor laws, cultural values, and business practices hard to replicate. Team with 15+ years of direct GCC enterprise experience. Brand positioning as "Saudi Arabian AI company for Saudis" creates local loyalty and trust.'
                    ],
                    [
                        'heading' => 'Technology & Product Innovation',
                        'content' => 'Continuous investment in AI capabilities keeps product ahead of competitors. Integrated talent lifecycle (recruitment + performance) vs. point solutions. User experience designed specifically for GCC workforce expectations. Product roadmap driven by local customer feedback.'
                    ],
                ]
            ]
        ],
        'ar' => [
            'text_content' => [
                'title' => 'الخندق التنافسي والقابلية للدفاع',
                'sections' => [
                    [
                        'heading' => 'تأثير شبكة البيانات',
                        'content' => 'مع قيام Connect AI بمعالجة المزيد من قرارات التوظيف في سوق مجلس التعاون الخليجي، تصبح نماذج الذكاء الاصطناعي أكثر دقة وقيمة. تنشئ مجموعة البيانات الملكية لأنماط التوظيف في مجلس التعاون الخليجي تأثير شبكة لا يمكن للمنافسين تكراره. ميزة الدخول الأول في نماذج الذكاء الاصطناعي المدربة على مجلس التعاون الخليجي.'
                    ],
                    [
                        'heading' => 'تكاليف التبديل وقفل العميل',
                        'content' => 'تصبح البيانات التاريخية للتوظيف المخزنة في منصة Connect AI أصلًا تنظيميًا قيمًا. يزيد التكامل مع سير عمل HRIS والتوظيف الموجود من تكاليف التبديل. تعتمد مقاييس الأداء والتحليلات على التراكم المستمر للبيانات في المنصة.'
                    ],
                    [
                        'heading' => 'العلامة التجارية والخبرة الإقليمية',
                        'content' => 'الفهم العميق لقوانين العمل في مجلس التعاون الخليجي والقيم الثقافية والممارسات التجارية يصعب تكراره. فريق لديه 15+ سنة من الخبرة المباشرة مع مؤسسات مجلس التعاون الخليجي. وضع العلامة التجارية باعتبارها "شركة ذكاء اصطناعي سعودية للسعوديين" ينشئ الولاء والثقة المحلية.'
                    ],
                    [
                        'heading' => 'الابتكار التكنولوجي والمنتج',
                        'content' => 'الاستثمار المستمر في قدرات الذكاء الاصطناعي يبقي المنتج متقدمًا على المنافسين. دورة حياة الموهبة المتكاملة (التوظيف + الأداء) مقابل الحلول النقطية. تجربة مستخدم مصممة خصيصًا لتوقعات القوى العاملة في مجلس التعاون الخليجي. خريطة الطريق للمنتج مدفوعة بملاحظات العملاء المحليين.'
                    ],
                ]
            ]
        ]
    ]
];
