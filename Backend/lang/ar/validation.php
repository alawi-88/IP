<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'required' => 'هذا الحقل مطلوب',
    'string' => 'هذا الحقل يجب أن يكون نصًا',
    'max' => [
        'numeric' => 'يجب ألا يتجاوز حقل :attribute :max.',
        'string' => 'الحد الأقصى للحقل هو :max حرفًا',
    ],
    'regex' => 'التنسيق غير صحيح',
    'url' => 'حقل :attribute يجب أن يكون رابطًا صحيحًا',
    'exists' => 'القيمة المدخلة غير موجودة',
    'linkedin' => [
        'url' => 'حقل لينكد إن يجب أن يكون رابطًا صحيحًا',
        'max' => 'حقل لينكد إن يجب أن لا يتجاوز 255 حرفًا',
    ],
    'instagram' => [
        'url' => 'حقل انستجرام يجب أن يكون رابطًا صحيحًا',
        'max' => 'حقل انستجرام يجب أن لا يتجاوز 255 حرفًا',
    ],
    'facebook' => [
        'url' => 'حقل فيسبوك يجب أن يكون رابطًا صحيحًا',
        'max' => 'حقل فيسبوك يجب أن لا يتجاوز 255 حرفًا',
    ],
    'name' => [
        'regex' => 'حقل الإسم لا يقبل الرموز والأرقام',
        'min' => 'يجب ألا يقل الاسم عن حرفين',
    ],

    'email' => [
        'format' => 'صيغة البريد الإلكتروني غير صحيحة',
        'unique' => 'البريد الإلكتروني مسجل بالفعل. جرب تسجيل الدخول أو استخدم بريد إلكتروني آخر.',
        'exists' => 'إذا كان هذا البريد الإلكتروني مسجلاً، فسوف تتلقى رمز تحقق.',
    ],

    'phone' => [
        'regex' => 'رقم الجوال المدخل غير صحيح',
        'unique' => 'رقم الجوال مسجل مسبقًا, الرجاء استخدام رقم جوال آخر',
        'numeric' => 'رقم الجوال يجب أن يكون رقمًا',
        'digits_between' => 'رقم الجوال يجب أن يتكون من 8 إلى 15 رقمًا',
    ],
    'experience_field' => [
        'regex' => 'حقل الخبرة المهنية لا يقبل الرموز والأرقام',
        'min' => 'حقل الخبرة المهنية يجب أن يكون على الأقل 2 حرف',
    ],
    'date_of_birth' => [
        'format' => 'صيغة تاريخ الميلاد غير صحيحة',
        'age' => 'للتسجيل في المنصة، يجب ألا يقل العمر عن ١٠ سنوات',
    ],

    'password' => [
        'regex' => 'كلمة المرور يجب أن تكون من 12 خانة على الأقل تتضمن حرف كبير، وحرف صغير، ورقم، ورمز.',
        'confirmed' => 'كلمة المرور غير متطابقة',
    ],

    'gender' => [
        'in' => 'الجنس يجب أن يكون ذكر أو أنثى',
    ],

    'educational_background' => [
        'in' => 'المؤهل العلمي المدخل غير صحيح',
    ],

    'current_role' => [
        'in' => 'الدور الحالي المدخل غير صحيح',
    ],

    'place_of_work_study' => [
        'string' => 'جهة العمل/الدراسة يجب أن تكون نصًا',
        'max' => 'جهة العمل/الدراسة يجب ألا تتجاوز 255 حرفًا',
    ],

    'years_of_experience' => [
        'in' => 'عدد سنوات الخبرة المدخل غير صحيح',
    ],

    'experience_or_skills' => [
        'string' => 'الخبرات أو المهارات يجب أن تكون نصًا',
        'max' => 'الخبرات أو المهارات يجب ألا تتجاوز 300 حرفًا',
    ],

    'key_achievements' => [
        'string' => 'الإنجازات يجب أن تكون نصًا',
        'max' => 'الإنجازات يجب ألا تتجاوز 300 حرفًا',
    ],

    'summary' => [
        'max' => 'الحد الأعلى للملخص ١٥٠ حرف',
    ],

    'description' => [
        'max' => 'الحد الأعلى للوصف ٨٠٠ حرف',
    ],

    'presentation_file' => [
        'mimes' => 'يجب أن يكون الملف المرفق بصيغة PDF أو PowerPoint format',
        'max' => 'يجب أن لا يتجاوز حجم الملفات المرفوعة ٣٠ ميجابايت',
    ],

    'link' => [
        'url' => 'الرجاء إدخال رابط فقط.',
    ],

    'references' => [
        'url' => 'الرجاء إدخال رابط فقط.',
    ],

    'documents' => [
        'max' => '"يجب أن لا يتجاوز حجم الملفات المرفوعة ١٠٠ ميجابايت"',
    ],

    'recaptcha' => [
        'required' => 'الرمز مطلوب',
        'failed' => 'الرجاء التحقق من صحة الرمز',
        'score_too_low' => 'درجة التحقق غير كافية.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'evaluation_criteria.*.weight' => [
            'max' => 'لا يمكن أن يتجاوز وزن المعيار الرئيسي 100%.',
        ],
        'evaluation_criteria.*.subcriteria.*.weight' => [
            'max' => 'لا يمكن أن يتجاوز وزن المعيار الفرعي 100%.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'evaluation_criteria.*.weight' => 'وزن المعيار الرئيسي',
        'evaluation_criteria.*.subcriteria.*.weight' => 'وزن المعيار الفرعي',
    ],

];
