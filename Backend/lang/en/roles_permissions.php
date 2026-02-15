<?php

return [
    // Navigation
    'roles' => 'Roles',
    'permissions' => 'Permissions',
    'system_management' => 'System Management',
    
    // Roles
    'role_name' => 'Role Name',
    'role_description' => 'Description',
    'role_guard' => 'Guard',
    'role_permissions' => 'Permissions',
    'role_users' => 'Users',
    'role_created_at' => 'Created At',
    
    // Role Types
    'super_admin' => 'Super Admin',
    'admin' => 'Admin',
    'supervisor' => 'Supervisor',
    
    // Permissions
    'permission_name' => 'Permission Name',
    'permission_guard' => 'Guard',
    'permission_roles' => 'Roles',
    'permission_created_at' => 'Created At',
    
    // Permission Types
    'view_permission' => 'View',
    'create_permission' => 'Create',
    'update_permission' => 'Update',
    'delete_permission' => 'Delete',
    'archive_permission' => 'Archive',
    
    // Actions
    'create_role' => 'Create Role',
    'edit_role' => 'Edit Role',
    'view_role' => 'View Role',
    'delete_role' => 'Delete Role',
    'create_permission' => 'Create Permission',
    'edit_permission' => 'Edit Permission',
    'view_permission' => 'View Permission',
    'delete_permission' => 'Delete Permission',
    
    // Messages
    'role_created' => 'Role created successfully',
    'role_updated' => 'Role updated successfully',
    'role_deleted' => 'Role deleted successfully',
    'permission_created' => 'Permission created successfully',
    'permission_updated' => 'Permission updated successfully',
    'permission_deleted' => 'Permission deleted successfully',
    
    // Validation
    'role_name_required' => 'Role name is required',
    'role_name_unique' => 'Role name must be unique',
    'permission_name_required' => 'Permission name is required',
    'permission_name_unique' => 'Permission name must be unique',
    'at_least_one_permission' => 'Please select at least one permission',
    'at_least_one_role' => 'Please select at least one role',
    
    // Filters
    'filter_by_guard' => 'Filter by Guard',
    'filter_by_type' => 'Filter by Type',
    'all_guards' => 'All Guards',
    'all_types' => 'All Types',
    
    // Descriptions
    'role_description_placeholder' => 'Enter details about this role...',
    'super_admin_description' => 'Full system access with all permissions',
    'admin_description' => 'Administrative access with most permissions',
    'supervisor_description' => 'Supervisory access with limited permissions',
];
