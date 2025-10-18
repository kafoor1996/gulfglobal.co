<?php
/**
 * Role-Based Authentication and Authorization System
 * Handles user authentication, role checking, and permission validation
 */

require_once '../config/database.php';

class AdminAuth {
    private $pdo;

    public function __construct() {
        $this->pdo = getConnection();
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }

    /**
     * Get current user data
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT u.*, r.name as role_name, r.display_name as role_display_name
            FROM admin_users u
            JOIN admin_roles r ON u.role_id = r.id
            WHERE u.id = ? AND u.is_active = 1
        ");
        $stmt->execute([$_SESSION['admin_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Refresh session variables if they're missing
        if ($user && (!isset($_SESSION['admin_role']) || !isset($_SESSION['admin_name']))) {
            $_SESSION['admin_role'] = $user['role_name'];
            $_SESSION['admin_name'] = $user['full_name'];
            $_SESSION['admin_username'] = $user['username'];
        }

        return $user;
    }

    /**
     * Get user permissions
     */
    public function getUserPermissions($userId = null) {
        if ($userId === null) {
            $userId = $_SESSION['admin_id'] ?? null;
        }

        if (!$userId) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT p.name, p.display_name, p.module
            FROM admin_permissions p
            JOIN admin_role_permissions rp ON p.id = rp.permission_id
            JOIN admin_users u ON u.role_id = rp.role_id
            WHERE u.id = ? AND u.is_active = 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $permissions = $this->getUserPermissions();
        $permissionNames = array_column($permissions, 'name');
        return in_array($permission, $permissionNames);
    }

    /**
     * Check if user has any of the specified permissions
     */
    public function hasAnyPermission($permissions) {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the specified permissions
     */
    public function hasAllPermissions($permissions) {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Require specific permission or redirect
     */
    public function requirePermission($permission, $redirectUrl = 'dashboard.php') {
        if (!$this->hasPermission($permission)) {
            $_SESSION['error_message'] = 'You do not have permission to access this page.';
            header("Location: $redirectUrl");
            exit();
        }
    }

    /**
     * Require any of the specified permissions
     */
    public function requireAnyPermission($permissions, $redirectUrl = 'dashboard.php') {
        if (!$this->hasAnyPermission($permissions)) {
            $_SESSION['error_message'] = 'You do not have permission to access this page.';
            header("Location: $redirectUrl");
            exit();
        }
    }

    /**
     * Get menu items based on user permissions
     */
    public function getMenuItems() {
        $menuItems = [];
        $permissions = $this->getUserPermissions();
        $permissionNames = array_column($permissions, 'name');

        // Dashboard - always available if logged in
        if ($this->isLoggedIn()) {
            $menuItems[] = [
                'name' => 'Dashboard',
                'url' => 'dashboard.php',
                'icon' => 'fas fa-tachometer-alt',
                'permission' => 'view_dashboard'
            ];
        }

        // Products
        if (in_array('view_products', $permissionNames)) {
            $menuItems[] = [
                'name' => 'Products',
                'url' => 'products.php',
                'icon' => 'fas fa-box',
                'permission' => 'view_products'
            ];
        }

        // Categories
        if (in_array('view_categories', $permissionNames)) {
            $menuItems[] = [
                'name' => 'Categories',
                'url' => 'categories.php',
                'icon' => 'fas fa-tags',
                'permission' => 'view_categories'
            ];
        }

        // Subcategories
        if (in_array('view_subcategories', $permissionNames)) {
            $menuItems[] = [
                'name' => 'Subcategories',
                'url' => 'subcategories.php',
                'icon' => 'fas fa-list',
                'permission' => 'view_subcategories'
            ];
        }

        // Hot Sale
        if (in_array('view_hot_sale', $permissionNames)) {
            $menuItems[] = [
                'name' => 'Hot Sale',
                'url' => 'hot-sale.php',
                'icon' => 'fas fa-fire',
                'permission' => 'view_hot_sale'
            ];
        }

        // Orders
        if (in_array('view_orders', $permissionNames)) {
            $menuItems[] = [
                'name' => 'Orders',
                'url' => 'orders.php',
                'icon' => 'fas fa-shopping-cart',
                'permission' => 'view_orders'
            ];
        }

        // WhatsApp Settings
        if (in_array('view_whatsapp', $permissionNames)) {
            $menuItems[] = [
                'name' => 'WhatsApp Settings',
                'url' => 'whatsapp-settings.php',
                'icon' => 'fab fa-whatsapp',
                'permission' => 'view_whatsapp'
            ];
        }

        // Settings
        if (in_array('view_settings', $permissionNames)) {
            $menuItems[] = [
                'name' => 'Settings',
                'url' => 'settings.php',
                'icon' => 'fas fa-cog',
                'permission' => 'view_settings'
            ];
        }

        // User Management (Super Admin only)
        if (in_array('view_users', $permissionNames)) {
            $menuItems[] = [
                'name' => 'User Management',
                'url' => 'users.php',
                'icon' => 'fas fa-users',
                'permission' => 'view_users'
            ];
        }

        return $menuItems;
    }

    /**
     * Login user
     */
    public function login($username, $password) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.username, u.password, u.full_name, u.is_active, r.name as role_name
                FROM admin_users u
                JOIN admin_roles r ON u.role_id = r.id
                WHERE u.username = ? AND u.is_active = 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_name'] = $user['full_name'];
                $_SESSION['admin_role'] = $user['role_name'];
                return true;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        session_start();
    }

    /**
     * Refresh session variables from database
     */
    public function refreshSession() {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $user = $this->getCurrentUser();
        if ($user) {
            $_SESSION['admin_role'] = $user['role_name'];
            $_SESSION['admin_name'] = $user['full_name'];
            $_SESSION['admin_username'] = $user['username'];
            return true;
        }
        return false;
    }

    /**
     * Get all roles for user management
     */
    public function getAllRoles() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM admin_roles WHERE is_active = 1 ORDER BY name");
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $roles;
        } catch (Exception $e) {
            error_log("Error in getAllRoles(): " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all permissions grouped by module
     */
    public function getAllPermissions() {
        $stmt = $this->pdo->query("SELECT * FROM admin_permissions ORDER BY module, name");
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($permissions as $permission) {
            $grouped[$permission['module']][] = $permission;
        }

        return $grouped;
    }
}

// Initialize auth system
$auth = new AdminAuth();

// Helper functions for easy use in other files
function requireLogin() {
    global $auth;
    if (!$auth->isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requirePermission($permission) {
    global $auth;
    $auth->requirePermission($permission);
}

function requireAnyPermission($permissions) {
    global $auth;
    $auth->requireAnyPermission($permissions);
}

function hasPermission($permission) {
    global $auth;
    return $auth->hasPermission($permission);
}

function hasAnyPermission($permissions) {
    global $auth;
    return $auth->hasAnyPermission($permissions);
}

function getCurrentUser() {
    global $auth;
    return $auth->getCurrentUser();
}

function getMenuItems() {
    global $auth;
    return $auth->getMenuItems();
}

function refreshSession() {
    global $auth;
    return $auth->refreshSession();
}
?>
