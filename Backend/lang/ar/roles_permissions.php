<?php

return [
    // Navigation
    'roles' => 'الأدوار',
    'permissions' => 'الصلاحيات',
    'system_management' => 'إدارة النظام',
    
    // Roles
    'role_name' => 'اسم الدور',
    'role_description' => 'الوصف',
    'role_guard' => 'الحارس',
    'role_permissions' => 'الصلاحيات',
    'role_users' => 'المستخدمين',
    'role_created_at' => 'تاريخ الإنشاء',
    
    // Role Types
    'super_admin' => 'مدير عام',
    'admin' => 'مدير',
    'supervisor' => 'مشرف',
    
    // Permissions
    'permission_name' => 'اسم الصلاحية',
    'permission_guard' => 'الحارس',
    'permission_roles' => 'الأدوار',
    'permission_created_at' => 'تاريخ الإنشاء',
    
    // Permission Types
    'view_permission' => 'عرض',
    'create_permission' => 'إنشاء',
    'update_permission' => 'تحديث',
    'delete_permission' => 'حذف',
    'archive_permission' => 'أرشفة',
    
    // Actions
    'create_role' => 'إنشاء دور',
    'edit_role' => 'تعديل الدور',
    'view_role' => 'عرض الدور',
    'delete_role' => 'حذف الدور',
    'create_permission' => 'إنشاء صلاحية',
    'edit_permission' => 'تعديل الصلاحية',
    'view_permission' => 'عرض الصلاحية',
    'delete_permission' => 'حذف الصلاحية',
    
    // Messages
    'role_created' => 'تم إنشاء الدور بنجاح',
    'role_updated' => 'تم تحديث الدور بنجاح',
    'role_deleted' => 'تم حذف الدور بنجاح',
    'permission_created' => 'تم إنشاء الصلاحية بنجاح',
    'permission_updated' => 'تم تحديث الصلاحية بنجاح',
    'permission_deleted' => 'تم حذف الصلاحية بنجاح',
    
    // Validation
    'role_name_required' => 'اسم الدور مطلوب',
    'role_name_unique' => 'اسم الدور يجب أن يكون فريداً',
    'permission_name_required' => 'اسم الصلاحية مطلوب',
    'permission_name_unique' => 'اسم الصلاحية يجب أن يكون فريداً',
    'at_least_one_permission' => 'يرجى اختيار صلاحية واحدة على الأقل',
    'at_least_one_role' => 'يرجى اختيار دور واحد على الأقل',
    
    // Filters
    'filter_by_guard' => 'تصفية حسب الحارس',
    'filter_by_type' => 'تصفية حسب النوع',
    'all_guards' => 'جميع الحراس',
    'all_types' => 'جميع الأنواع',
    
    // Descriptions
    'role_description_placeholder' => 'أدخل تفاصيل حول هذا الدور...',
    'super_admin_description' => 'وصول كامل للنظام مع جميع الصلاحيات',
    'admin_description' => 'وصول إداري مع معظم الصلاحيات',
    'supervisor_description' => 'وصول إشرافي مع صلاحيات محدودة',
];
