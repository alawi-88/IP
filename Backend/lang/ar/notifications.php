<?php

return [
    'hello' => 'مرحباً :name',
    'salutation' => 'مع تحيات فريق Foundry',
    // Session Notifications
    'session_scheduled_subject' => 'تم جدولة جلسة جديدة',
    'session_updated_subject' => 'تم تحديث الجلسة',
    'session_rescheduled_subject' => 'تم إعادة جدولة الجلسة',
    'session_cancelled_subject' => 'تم إلغاء الجلسة',
    'session_reminder_subject' => 'تذكير بالجلسة',
    'session_scheduled_message' => 'تم جدولة جلسة جديدة في :date الساعة :time (:duration)',
    'session_updated_message' => 'تم تحديث الجلسة ',
    'session_rescheduled_message' => 'تم إعادة جدولة جلستك إلى :new_date الساعة :new_time',
    'session_rescheduled_message_new' => 'تم إعادة جدولة جلستك من ',
    'session_cancelled_message' => 'تم إلغاء الجلسة  المجدولة في :date الساعة :time',
    'session_reminder_message' => 'هذا تذكير ودود بأن لديك جلسة  مجدولة في :date الساعة :time (:duration)',
    'session_description' => 'وصف الجلسة:',
    'session_scheduled_footer' => 'يرجى التأكد من الانضمام للجلسة في الوقت المحدد.',
    'session_updated_footer' => 'يرجى مراجعة التفاصيل المحدثة.',
    'session_rescheduled_footer' => 'يرجى التأكد من الانضمام للجلسة في الوقت الجديد.',
    'session_cancelled_footer' => 'إذا كان لديك أي أسئلة، يرجى التواصل مع الدعم.',
    'session_reminder_footer' => 'هذا تذكير ودود حول جلستك القادمة.',
    'join_session' => 'انضم للجلسة',
    'cancellation_reason' => '',
    'session_time_changed' => 'تم تغيير وقت الجلسة إلى :new_date الساعة :new_time',
    'session_duration_changed' => 'تم تغيير مدة الجلسة إلى :new_duration',
    'session_title_changed' => 'تم تغيير عنوان الجلسة إلى ":new_title"',
    'previous_time' => 'الوقت السابق',
    'new_time' => 'الوقت الجديد',
    'previous_duration' => 'المدة السابقة',
    'new_duration' => 'المدة الجديدة',
    'previous_title' => 'العنوان السابق',
    'new_title' => 'العنوان الجديد',
    'session_details' => 'تفاصيل الجلسة:',
    'session_with_participant' => 'جلسة مع المشارك: :name',
    'session_with_mentor' => 'جلسة مع المرشد: :name',
    
    // Feedback Notifications
    'session_feedback_submitted_subject' => 'تم تقديم التغذية الراجعة للجلسة',
    'session_feedback_submitted_message' => 'تم تقديم التغذية الراجعة لجلستك.',
    'session_feedback_admin_subject' => 'تم استلام التغذية الراجعة للجلسة',
    'session_feedback_admin_message' => 'تم تقديم التغذية الراجعة لجلسة.',
    'view_in_portal' => 'عرض في البوابة',
    'view_in_panel' => 'عرض في لوحة الإدارة',
    
    // New Booking Notifications
    'new_booking_subject' => 'طلب حجز جلسة جديد',
    'new_booking_message' => ':participant_name طلب جلسة معك.',
    'new_booking_footer' => 'يرجى مراجعة الطلب والرد بقبوله أو رفضه أو اقتراح وقت جديد.',
    'participant_information' => 'معلومات المشارك:',
    'mentor_information' => 'معلومات المرشد:',
    'full_name_label' => 'الاسم الكامل',
    'email_label' => 'البريد الإلكتروني',
    'session_title_label' => 'ملاحظة',
    'program_label' => 'برنامج',
    'program_label' => 'برنامج',
    'session_date_label' => 'التاريخ',
    'session_time_label' => 'الوقت',
    'session_duration_label' => 'مدة',
    'booking_id_label' => 'رقم الحجز',
    'note_label' => 'ملاحظة:',
    'session_description_label' => 'وصف الجلسة:',
    // Session Accepted Notifications
    'session_accepted_subject' => 'تم قبول طلب الجلسة',
    'session_accepted_message' => ':mentor_name قبل طلب جلستك. التاريخ: :date، الوقت: :time، المدة: :duration',
    'session_accepted_footer' => 'تم تأكيد جلستك. يرجى التأكد من الانضمام في الوقت المحدد.',
    
    // Session Declined Notifications
    'session_declined_subject' => 'تم رفض طلب الجلسة',
    'session_declined_message' => ':mentor_name رفض طلب جلستك. العنوان: ":title"، التاريخ: :date، الوقت: :time',
    'session_declined_footer' => 'يمكنك محاولة حجز جلسة أخرى مع هذا المرشد أو اختيار مرشد آخر.',
    'decline_reason_label' => 'السبب المقدم من المرشد:',
    
    // New Time Proposed Notifications
    'new_time_proposed_subject' => 'تم اقتراح وقت جديد للجلسة',
    'new_time_proposed_message' => ':mentor_name اقترح وقتًا جديدًا لطلب جلستك. العنوان: ":title"، الوقت الأصلي: :original_date الساعة :original_time، الوقت المقترح: :proposed_date الساعة :proposed_time، المدة: :duration',
    'new_time_proposed_instructions' => 'يرجى مراجعة الوقت المقترح والرد بقبوله أو رفضه.',
    'new_time_proposed_footer' => 'يرجى الرد على الوقت المقترح من المرشد.',
    
    // Time Units
    'hour' => 'ساعة',
    'minute' => 'دقيقة',
    'hour_full' => 'ساعة',
    'minute_full' => 'دقيقة',
    'hours' => 'ساعات',
    'minutes' => 'دقائق',
];

