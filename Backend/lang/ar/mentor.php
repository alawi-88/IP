<?php

return [
    // Registration Messages
    'registration_successful' => 'تم التسجيل بنجاح',
    'registration_failed' => 'فشل التسجيل. يرجى المحاولة مرة أخرى لاحقاً.',
    'admin_registration_subject' => 'تسجيل مرشد جديد - يتطلب الموافقة',
    'admin_registration_message' => 'تم تسجيل مرشد جديد ويتطلب موافقتك.',
    'admin_registration_details' => 'تفاصيل المرشد: الاسم: :name، البريد الإلكتروني: :email، الهاتف: :phone، المهنة: :profession، الخبرة: :experience، تاريخ التسجيل: :date',
    'registration_pending_subject' => 'تسجيل المرشد - في انتظار الموافقة',
    'registration_pending_message' => 'عزيزي/عزيزتي :name،',
    'registration_pending_details' => 'شكراً لك على التسجيل كمرشد. طلبك في انتظار الموافقة من فريق الإدارة. ستتلقى إشعاراً بالبريد الإلكتروني بمجرد الموافقة على حسابك ويمكنك البدء في إرشاد المشاركين.',
    'name' => 'الاسم الكامل',
    'email' => 'البريد الإلكتروني',
    'phone' => 'رقم الجوال',
    'profession' => 'المهنة',
    'experience' => 'الخبرة',
    'date' => 'تاريخ التسحيل',
    // Login Messages
    'login_successful' => 'تم تسجيل الدخول بنجاح',
    'login_failed' => 'فشل تسجيل الدخول. يرجى التحقق من بياناتك.',
    'logged_out_successfully' => 'تم تسجيل الخروج بنجاح',
    
    // OTP Messages
    'otp_code_sent' => 'تم إرسال رمز التحقق بنجاح',
    'invalid_otp_code' => 'رمز التحقق غير صحيح',
    'otp_resend_successful' => 'تم إعادة إرسال رمز التحقق بنجاح',
    
    // Account Status Messages
    'account_not_activated' => 'يرجى تفعيل حسابك عن طريق رابط التفعيل المرسل على البريد الإلكتروني',
    'account_activated' => 'تم تفعيل الحساب بنجاح',
    'account_not_approved' => 'حسابك في انتظار الموافقة من الإدارة. يرجى الانتظار للموافقة قبل تسجيل الدخول.',
    'account_rejected' => 'تم رفض طلب حسابك من قبل الإدارة.',
    'archived_account' => 'تم أرشفة حسابك ولا يمكنك الوصول إلى النظام.',
    
    // Validation Messages
    'invalid_credentials' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
    'email_not_found' => 'البريد الإلكتروني غير موجود.',
    'email_already_exists' => 'هذا البريد الإلكتروني مسجل مسبقاً.',
    
    // Form Labels
    'name' => 'الاسم',
    'email' => 'البريد الإلكتروني',
    'phone' => 'رقم الهاتف',
    'password' => 'كلمة المرور',
    'profession' => 'المهنة',
    'experience' => 'الخبرة',
    'brief' => 'نبذة',
    'track' => 'المسار',
    'remember_me' => 'تذكرني',
    
    // Placeholders
    'enter_name' => 'أدخل اسمك',
    'enter_email' => 'أدخل بريدك الإلكتروني',
    'enter_phone' => 'أدخل رقم هاتفك',
    'enter_password' => 'أدخل كلمة المرور',
    'enter_profession' => 'أدخل مهنتك',
    'enter_experience' => 'أدخل خبرتك',
    'enter_brief' => 'أدخل نبذتك',
    'select_track' => 'اختر مساراً',
    'enter_otp' => 'أدخل رمز التحقق',
    
    // Admin Approval Messages
    'approved_successfully' => 'تم الموافقة على المرشد بنجاح',
    'rejected_successfully' => 'تم رفض المرشد بنجاح',
    'already_processed' => 'تم معالجة هذا المرشد مسبقاً',
    'approved_subject' => 'تم الموافقة على تسجيل المرشد',
    'approved_message' => 'عزيزي/عزيزتي :name،',
    'approved_details' => 'تهانينا! تم الموافقة على تسجيلك كمرشد. يمكنك الآن الوصول إلى جميع ميزات الإرشاد وبدء مساعدة المشاركين.',
    'rejected_subject' => 'تم رفض تسجيل المرشد',
    'rejected_message' => 'عزيزي/عزيزتي :name،',
    'rejected_details' => 'نأسف لإعلامك بأن تسجيلك كمرشد قد تم رفضه. السبب: :reason',
    'no_reason_provided' => 'لم يتم تقديم سبب محدد',
    'deactivated_subject' => 'تم إلغاء تنشيط حساب المرشد',
    'deactivated_message' => 'عزيزي/عزيزتي :name،',
    'deactivated_details' => 'نود إعلامك بأن حسابك كمرشد قد تم إلغاء تنشيطه. لن تعود مرئياً للمشاركين ولا يمكنك الوصول إلى النظام. إذا كان لديك أي استفسارات، يرجى الاتصال بالإدارة.',
    
    // Password Reset Messages
    'code_sent' => 'تم إرسال رمز إعادة تعيين كلمة المرور بنجاح',
    'password_changed' => 'تم تغيير كلمة المرور بنجاح',
    'invalid_code' => 'رمز إعادة التعيين غير صحيح',
    'code_expired' => 'انتهت صلاحية رمز إعادة التعيين. يرجى طلب رمز جديد.',
    'reset_password' => 'إعادة تعيين كلمة المرور',
    'reset_password_message' => 'أنت تتلقى هذا البريد الإلكتروني لأننا تلقينا طلب إعادة تعيين كلمة المرور لحسابك.',
    'reset_password_code' => 'رمز إعادة تعيين كلمة المرور الخاص بك هو: :code',
    
    // Email Notifications
    'otp_email_subject' => 'رمز التحقق لتسجيل دخول المرشد',
    'otp_email_greeting' => 'مرحباً!',
    'otp_email_message' => 'أنت تسجل الدخول كمرشد باستخدام: :email',
    'otp_email_code' => 'رمز التحقق الخاص بك هو: **:code**',
    'otp_email_expires' => 'سينتهي هذا الرمز خلال 10 دقائق.',
    'otp_email_signature' => 'مع أطيب التحيات، فريق منصة الابتكار',
    
    // Auto Credentials
    'auto_credentials_subject' => 'بيانات حسابك كمرشد',
    'auto_credentials_greeting' => 'مرحباً :name!',
    'auto_credentials_intro' => 'تم إنشاء حساب مرشد لك. يرجى استخدام البيانات التالية لتسجيل الدخول:',
    'auto_credentials_email_label' => 'البريد الإلكتروني',
    'auto_credentials_password_label' => 'كلمة المرور',
    'auto_credentials_footer' => 'يرجى الحفاظ على هذه البيانات بأمان وعدم مشاركتها مع أي شخص. ننصح بتغيير كلمة المرور بعد تسجيل الدخول الأول.',
    'login_button' => 'تسجيل الدخول الآن',

     // Profile Update Messages
     'profile_updated_successfully' => 'تم تحديث الملف الشخصي بنجاح',
     'profile_update_failed' => 'فشل في تحديث الملف الشخصي. يرجى المحاولة مرة أخرى.',
    
    // Teams/Participants Messages
    'no_teams_assigned' => 'لا توجد فرق أو مشاركون معينون',
    'failed_to_load_teams' => 'فشل في تحميل الفرق المعينة. يرجى المحاولة مرة أخرى لاحقًا.',
    'failed_to_load_team_details' => 'فشل في تحميل تفاصيل الفريق. يرجى المحاولة مرة أخرى لاحقًا.',
    'failed_to_load_participants' => 'فشل في تحميل المشاركين. يرجى المحاولة مرة أخرى لاحقًا.',
    'failed_to_load_summary' => 'فشل في تحميل الملخص. يرجى المحاولة مرة أخرى لاحقًا.',
    'team_not_found' => 'الفريق غير موجود أو غير معين لك',
    
    // Projects Messages
    'project_not_found' => 'المشروع غير موجود أو غير معين لفرقك',
    'failed_to_load_project' => 'فشل في تحميل تفاصيل المشروع. يرجى المحاولة مرة أخرى لاحقًا.',
    'failed_to_load_projects' => 'فشل في تحميل المشاريع. يرجى المحاولة مرة أخرى لاحقًا.',
    
    // Individual Participants Messages
    'no_individual_participants_assigned' => 'لا يوجد مشاركون فرديون معينون',
    'failed_to_load_individual_participants' => 'فشل في تحميل المشاركين الفرديين. يرجى المحاولة مرة أخرى لاحقًا.',
    'participant_not_found' => 'المشارك غير موجود أو غير معين لك',
    'failed_to_load_participant_details' => 'فشل في تحميل تفاصيل المشارك. يرجى المحاولة مرة أخرى لاحقًا.',
    
    // Team Assignment Messages
    'you_have_been_assigned_to_team' => 'تم تعيينك للفريق: :name',
    'a_mentor_has_been_assigned_to_your_team' => 'تم تعيين مرشد لفريقك: :name',
    'mentor_assigned_to_your_team' => 'تم تعيين مرشد لفريقك: :name',
    'mentor' => 'المرشد: :name',
    'view_team_details' => 'عرض تفاصيل الفريق',
    'thank_you_for_being_a_mentor' => 'شكراً لكونك مرشداً!',
    'good_luck_with_your_project' => 'بالتوفيق في مشروعك!',
    'view_mentor' => 'عرض المرشد',
    'view_mentor_details' => 'عرض تفاصيل المرشد',
    'hello' => 'مرحباً :name,',
    'a_mentor_has_been_assigned_to_guide_you' => 'تم تعيين مرشد لتوجيهك: :name',
    'no_answer' => 'لا يوجد إجابة',
    
    // Participant Assignment Notifications (for mentor)
    'participant_assigned_subject' => 'تم تعيين مشارك جديد',
    'participant_assigned_to_you' => 'تم تعيين مشارك جديد لك: :name (:email)',
    'you_can_now_guide_participant' => 'يمكنك الآن البدء في توجيه هذا المشارك خلال رحلته.',
    'participant_assigned_title' => 'تم تعيين مشارك جديد',
    'participant_assigned_body' => 'تم تعيين المشارك :name (:email) لك',
    'view_participants' => 'عرض المشاركين',
    
    // Participant Assignment Notifications (for participant)
    'mentor_assigned_subject' => 'تم تعيين مرشد',
    'mentor_assigned_title' => 'تم تعيين مرشد',
    'mentor_assigned_body' => 'تم تعيين المرشد :name لتوجيهك',
    'view_dashboard' => 'عرض لوحة التحكم',
];
