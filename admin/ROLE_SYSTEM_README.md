# Role-Based Access Control System

## Overview

The Gulf Global Co admin panel now includes a comprehensive role-based access control (RBAC) system that allows you to manage different user roles with specific permissions. This ensures that users only see and can access the features they're authorized to use.

## User Roles

### 1. Super Administrator
- **Role Name**: `super_admin`
- **Description**: Full access to all features and user management
- **Permissions**: All permissions including user management
- **Use Case**: System administrators who need complete control

### 2. Administrator
- **Role Name**: `admin`
- **Description**: Full access to all features except user management
- **Permissions**: All permissions except user management
- **Use Case**: Senior staff who manage the system but don't need to create users

### 3. Manager
- **Role Name**: `manager`
- **Description**: Access to products, categories, and orders management
- **Permissions**: Products, categories, subcategories, hot sale, and orders
- **Use Case**: Store managers who handle inventory and orders

### 4. Editor
- **Role Name**: `editor`
- **Description**: Access to products and categories only
- **Permissions**: Products, categories, subcategories, and hot sale
- **Use Case**: Content editors who manage product information

### 5. Viewer
- **Role Name**: `viewer`
- **Description**: Read-only access to dashboard and reports
- **Permissions**: View-only access to dashboard, products, categories, subcategories, hot sale, and orders
- **Use Case**: Staff who need to view information but not make changes

## Permission System

The system includes granular permissions organized by modules:

### Dashboard Permissions
- `view_dashboard` - Access to dashboard and statistics

### Product Permissions
- `view_products` - View products list
- `add_products` - Create new products
- `edit_products` - Modify existing products
- `delete_products` - Delete products

### Category Permissions
- `view_categories` - View categories list
- `add_categories` - Create new categories
- `edit_categories` - Modify existing categories
- `delete_categories` - Delete categories

### Subcategory Permissions
- `view_subcategories` - View subcategories list
- `add_subcategories` - Create new subcategories
- `edit_subcategories` - Modify existing subcategories
- `delete_subcategories` - Delete subcategories

### Hot Sale Permissions
- `view_hot_sale` - View hot sale products
- `manage_hot_sale` - Add/remove products from hot sale

### Order Permissions
- `view_orders` - View orders list
- `manage_orders` - Update order status and details

### Settings Permissions
- `view_settings` - View system settings
- `edit_settings` - Modify system settings

### WhatsApp Permissions
- `view_whatsapp` - View WhatsApp configuration
- `edit_whatsapp` - Modify WhatsApp configuration

### User Management Permissions (Super Admin Only)
- `view_users` - View admin users list
- `add_users` - Create new admin users
- `edit_users` - Modify existing admin users
- `delete_users` - Delete admin users
- `manage_roles` - Create and assign user roles

## Menu System

The navigation menu automatically adapts based on user permissions:
- Users only see menu items for features they have access to
- Menu items are hidden if the user lacks the required permission
- The current page is highlighted in the navigation

## Implementation Details

### Database Schema

The system uses four main tables:

1. **admin_roles** - Stores role definitions
2. **admin_permissions** - Stores permission definitions
3. **admin_role_permissions** - Links roles to permissions
4. **admin_users** - Updated to include role_id foreign key

### Authentication System

The `AdminAuth` class handles:
- User authentication and session management
- Permission checking
- Role-based menu generation
- User data retrieval

### Helper Functions

- `requireLogin()` - Ensures user is logged in
- `requirePermission($permission)` - Requires specific permission
- `requireAnyPermission($permissions)` - Requires any of the specified permissions
- `hasPermission($permission)` - Checks if user has permission
- `getCurrentUser()` - Gets current user data
- `getMenuItems()` - Gets menu items based on permissions

## Setup Instructions

### 1. Database Migration

The database schema is automatically updated when you access any admin page. The system will:
- Create the new role and permission tables
- Insert default roles and permissions
- Assign permissions to roles
- Update the admin_users table structure

### 2. Update Existing Admin User

Run the update script to assign the super_admin role to the existing admin user:

```bash
php update_admin_role.php
```

### 3. Default Login Credentials

- **Username**: admin
- **Password**: admin123
- **Role**: Super Administrator

## Usage

### Creating New Users

1. Login as a Super Administrator
2. Go to "User Management" in the sidebar
3. Click "Add New User"
4. Fill in user details and select appropriate role
5. Click "Create User"

### Managing Users

Super Administrators can:
- View all users
- Edit user details and roles
- Activate/deactivate users
- Delete users (except their own account)

### Role Assignment

When creating or editing users, you can assign any of the five predefined roles:
- Super Administrator
- Administrator
- Manager
- Editor
- Viewer

## Security Features

1. **Session Management**: Secure session handling with proper logout
2. **Permission Checking**: Every page checks for required permissions
3. **Role Validation**: Users can only access features their role allows
4. **Self-Protection**: Users cannot delete their own accounts
5. **Menu Filtering**: Navigation automatically hides unauthorized features

## Customization

### Adding New Permissions

1. Add permission to `admin_permissions` table
2. Update the `getMenuItems()` method in `AdminAuth` class
3. Assign permission to appropriate roles in `admin_role_permissions` table

### Adding New Roles

1. Add role to `admin_roles` table
2. Assign permissions to the new role in `admin_role_permissions` table
3. Update the role selection in user management forms

## Troubleshooting

### Common Issues

1. **Permission Denied**: Check if user has the required permission
2. **Menu Items Missing**: Verify user's role has the necessary permissions
3. **Database Errors**: Ensure all tables are created and populated correctly

### Debug Mode

To debug permission issues, you can check:
- User's current role: `$_SESSION['admin_role']`
- User's permissions: Use `getUserPermissions()` method
- Available menu items: Use `getMenuItems()` function

## Best Practices

1. **Principle of Least Privilege**: Give users only the permissions they need
2. **Regular Audits**: Periodically review user roles and permissions
3. **Strong Passwords**: Enforce strong password policies
4. **Role Separation**: Use different roles for different responsibilities
5. **Regular Updates**: Keep the system updated with security patches

## Support

For technical support or questions about the role system, contact the system administrator or refer to the code documentation in the `includes/auth.php` file.
