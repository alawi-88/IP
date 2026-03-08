<?php

return [
    // Navigation & Page Titles
    'dashboards' => 'لوحات المعلومات',
    'dashboard' => 'لوحة المعلومات',
    'create_dashboard' => 'إنشاء لوحة معلومات',
    'edit_dashboard' => 'تعديل لوحة المعلومات',
    'view_dashboard' => 'عرض لوحة المعلومات',
    'manage_dashboards' => 'إدارة لوحات المعلومات',

    // Form Labels
    'dashboard_name' => 'اسم لوحة المعلومات',
    'dashboard_name_en' => 'اسم لوحة المعلومات (إنجليزي)',
    'dashboard_name_ar' => 'اسم لوحة المعلومات (عربي)',
    'description' => 'الوصف',
    'description_en' => 'الوصف (إنجليزي)',
    'description_ar' => 'الوصف (عربي)',
    'data_source' => 'مصدر البيانات',
    'parameter' => 'المعامل',
    'parameters' => 'المعاملات',
    'aggregation_type' => 'نوع التجميع',
    'visualization_type' => 'نوع التصور',
    'filter' => 'عامل التصفية',
    'filters' => 'عوامل التصفية',
    'group_by' => 'تجميع حسب',
    'widgets' => 'الأدوات',
    'widget' => 'أداة',
    'add_widget' => 'إضافة أداة',

    // Placeholders
    'enter_dashboard_name' => 'أدخل اسم لوحة المعلومات',
    'select_data_source' => 'اختر مصدر البيانات',
    'select_parameter' => 'اختر المعامل',
    'select_aggregation' => 'اختر نوع التجميع',
    'select_visualization' => 'اختر نوع التصور',
    'select_group_by' => 'اختر حقل التجميع',
    'add_filter' => 'أضف عامل تصفية',

    // Data Sources
    'data_source_applications' => 'الطلبات',
    'data_source_projects' => 'المشاريع',

    // Visualization Types
    'viz_bar' => 'مخطط شريطي',
    'viz_pie' => 'مخطط دائري',
    'viz_line' => 'مخطط خطي',
    'viz_table' => 'جدول',
    'viz_kpi' => 'بطاقة مؤشر أداء',

    // Aggregation Types
    'agg_sum' => 'المجموع',
    'agg_average' => 'المتوسط',
    'agg_min' => 'الحد الأدنى',
    'agg_max' => 'الحد الأقصى',
    'agg_count' => 'العدد',
    'agg_rate' => 'النسبة المئوية',
    'agg_count_distinct' => 'العدد المميز',
    'agg_group_by_period' => 'تجميع حسب الفترة',

    // Actions
    'preview' => 'معاينة',
    'save' => 'حفظ',
    'edit' => 'تعديل',
    'delete' => 'حذف',
    'duplicate' => 'نسخ',
    'export' => 'تصدير',
    'export_csv' => 'تصدير CSV',
    'export_excel' => 'تصدير Excel',
    'export_pdf' => 'تصدير PDF',
    'retry' => 'إعادة المحاولة',
    'archive' => 'أرشفة',
    'restore' => 'استعادة',

    // Filter Labels
    'filter_by_program' => 'تصفية حسب المسابقة',
    'filter_by_status' => 'تصفية حسب الحالة',
    'date_range' => 'النطاق الزمني',
    'date_from' => 'من تاريخ',
    'date_to' => 'إلى تاريخ',
    'filter_by_track' => 'تصفية حسب المسار',

    // Table Columns
    'created_date' => 'تاريخ الإنشاء',
    'last_modified' => 'آخر تعديل',
    'created_by' => 'أنشئ بواسطة',
    'sort_order' => 'ترتيب العرض',

    // Messages
    'dashboard_created' => 'تم إنشاء لوحة المعلومات بنجاح.',
    'dashboard_updated' => 'تم تحديث لوحة المعلومات بنجاح.',
    'dashboard_deleted' => 'تم حذف لوحة المعلومات بنجاح.',
    'dashboard_duplicated' => 'تم نسخ لوحة المعلومات بنجاح.',
    'dashboard_archived' => 'تمت أرشفة لوحة المعلومات بنجاح.',
    'dashboard_restored' => 'تمت استعادة لوحة المعلومات بنجاح.',
    'export_failed' => 'فشل التصدير. يرجى المحاولة مرة أخرى.',

    // Validation
    'name_required' => 'الاسم مطلوب.',
    'data_source_required' => 'مصدر البيانات مطلوب.',
    'parameter_required' => 'مطلوب معامل واحد على الأقل.',
    'aggregation_required' => 'نوع التجميع مطلوب.',
    'visualization_required' => 'نوع التصور مطلوب.',
    'invalid_filter' => 'عامل التصفية غير صالح.',
    'invalid_group_by' => 'حقل التجميع غير صالح.',

    // Confirmation
    'confirm_delete' => 'هل أنت متأكد أنك تريد حذف لوحة المعلومات؟ لا يمكن التراجع عن هذا الإجراء.',
    'confirm_archive' => 'هل أنت متأكد أنك تريد أرشفة لوحة المعلومات؟',

    // Empty & Error States
    'no_dashboards' => 'لم يتم العثور على لوحات معلومات.',
    'no_data' => 'لا توجد بيانات متاحة لهذه اللوحة.',
    'loading' => 'جاري التحميل...',
    'error_loading' => 'فشل في تحميل بيانات لوحة المعلومات.',
    'field_unavailable' => 'الحقل غير متاح',
    'preview_failed' => 'فشلت المعاينة. يرجى التحقق من الإعدادات.',

    // Status
    'status_active' => 'نشط',
    'status_archived' => 'مؤرشف',
    'all' => 'الكل',
];
