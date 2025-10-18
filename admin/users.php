<?php

session_start();
require_once 'includes/auth.php';

// Check if admin is logged in and has permission to manage users
requireLogin();
requirePermission('view_users');

// Refresh session variables if needed
refreshSession();

$pdo = getConnection();


// Display success/error messages from session
$message = '';
$error = '';
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $full_name = trim($_POST['full_name']);
        $role_id = $_POST['role_id'];

        if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($role_id)) {
            $_SESSION['error_message'] = 'Please fill in all fields';
        } else {
            try {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admin_users (username, email, password, full_name, role_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, $email, $hashedPassword, $full_name, $role_id]);
                $_SESSION['success_message'] = 'User created successfully';
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Failed to create user: ' . $e->getMessage();
            }
        }
        // Redirect to prevent form resubmission
        header('Location: users.php');
        exit();
    } elseif ($action === 'edit_user') {
        $user_id = $_POST['user_id'];
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $full_name = trim($_POST['full_name']);
        $role_id = $_POST['role_id'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;


        // Validate role_id is numeric
        if (!is_numeric($role_id)) {
            $_SESSION['error_message'] = 'Invalid role selected. Please refresh the page and try again.';
        } elseif (empty($username) || empty($email) || empty($full_name) || empty($role_id)) {
            $_SESSION['error_message'] = 'Please fill in all fields. Missing: ' .
                (empty($username) ? 'username ' : '') .
                (empty($email) ? 'email ' : '') .
                (empty($full_name) ? 'full_name ' : '') .
                (empty($role_id) ? 'role_id ' : '');
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE admin_users SET username = ?, email = ?, full_name = ?, role_id = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$username, $email, $full_name, $role_id, $is_active, $user_id]);
                $_SESSION['success_message'] = 'User updated successfully';
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Failed to update user: ' . $e->getMessage();
            }
        }
        // Redirect to prevent form resubmission
        header('Location: users.php');
        exit();
    } elseif ($action === 'delete_user') {
        $user_id = $_POST['user_id'];

        // Prevent deleting own account
        if ($user_id == $_SESSION['admin_id']) {
            $_SESSION['error_message'] = 'You cannot delete your own account';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['success_message'] = 'User deleted successfully';
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Failed to delete user: ' . $e->getMessage();
            }
        }
        // Redirect to prevent form resubmission
        header('Location: users.php');
        exit();
    }
}

// Get all users with their roles
$stmt = $pdo->query("
    SELECT u.*, r.display_name as role_name
    FROM admin_users u
    JOIN admin_roles r ON u.role_id = r.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all roles for forms - Use direct query instead of auth class
try {
    $stmt = $pdo->query("SELECT * FROM admin_roles WHERE is_active = 1 ORDER BY name");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Direct query found " . count($roles) . " roles");
} catch (Exception $e) {
    error_log("Direct query failed: " . $e->getMessage());
    $roles = [];
}



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Gulf Global Co</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: background 0.3s ease;
            border-left: 3px solid transparent;
        }

        .menu-item:hover,
        .menu-item.active {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: #4ade80;
        }

        .menu-item i {
            width: 20px;
            margin-right: 10px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 0;
        }

        /* Top Bar */
        .top-bar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .top-bar h1 {
            color: #2c5aa0;
            font-size: 1.8rem;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-details {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .role-badge {
            background: #4ade80;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 500;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        /* Content Area */
        .content {
            padding: 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-title {
            color: #2c5aa0;
            font-size: 2rem;
        }

        .btn {
            background: #2c5aa0;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
        }

        .btn:hover {
            background: #1e3d6f;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        /* Messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Table */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c5aa0;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .modal-title {
            color: #2c5aa0;
            font-size: 1.5rem;
        }

        .close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2c5aa0;
        }

        .form-row {
            display: flex;
            gap: 15px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        /* Role Summary Styles */
        .role-summary-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 30px;
        }

        .role-summary-container h3 {
            color: #2c5aa0;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .role-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }

        .role-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid #2c5aa0;
        }

        .role-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .role-header h4 {
            color: #2c5aa0;
            margin: 0;
            font-size: 1.1rem;
        }

        .permission-count {
            background: #2c5aa0;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .role-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .restricted-modules {
            margin-bottom: 15px;
        }

        .restricted-modules h5 {
            color: #333;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .module-access {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .module-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .module-item.has-access {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .module-item.no-access {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .module-permissions h5 {
            color: #333;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .modules-list {
            max-height: 150px;
            overflow-y: auto;
        }

        .module-group {
            margin-bottom: 8px;
            font-size: 0.85rem;
        }

        .module-group strong {
            color: #2c5aa0;
            display: block;
            margin-bottom: 2px;
        }

        .permissions {
            color: #666;
            font-size: 0.8rem;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .table-container {
                overflow-x: auto;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }

            .role-summary-grid {
                grid-template-columns: 1fr;
            }

            .role-card {
                padding: 15px;
            }

            .module-access {
                flex-direction: column;
            }

            .module-item {
                font-size: 0.8rem;
                padding: 6px 10px;
            }
        }

        /* Role Summary & Permissions Styles */
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .role-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            transition: box-shadow 0.2s;
        }

        .role-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .role-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .role-header h4 {
            margin: 0;
            color: #333;
            font-size: 1.1rem;
        }

        .role-badge {
            background: #007bff;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .role-description {
            color: #666;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .permissions-list h5 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 0.9rem;
        }

        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .permission-module {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 12px;
        }

        .permission-module h6 {
            margin: 0 0 8px 0;
            color: #495057;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .permission-module ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .permission-module li {
            padding: 4px 0;
            color: #6c757d;
            font-size: 0.8rem;
            border-bottom: 1px solid #f8f9fa;
        }

        .permission-module li:last-child {
            border-bottom: none;
        }

        .no-permissions {
            color: #6c757d;
            font-style: italic;
            margin: 0;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .roles-grid {
                grid-template-columns: 1fr;
            }

            .permissions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-shield-alt"></i> Admin Panel</h2>
                <p>Gulf Global Co</p>
            </div>
            <nav class="sidebar-menu">
                <?php
                $menuItems = getMenuItems();
                $currentPage = basename($_SERVER['PHP_SELF']);
                foreach ($menuItems as $item):
                    $isActive = ($currentPage === $item['url']);
                ?>
                <a href="<?php echo $item['url']; ?>" class="menu-item <?php echo $isActive ? 'active' : ''; ?>">
                    <i class="<?php echo $item['icon']; ?>"></i> <?php echo $item['name']; ?>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1>User Management</h1>
                <div class="admin-info">
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?>
                    </div>
                    <div class="admin-details">
                        <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                        <small class="role-badge"><?php echo htmlspecialchars($_SESSION['admin_role'] ?? 'Unknown'); ?></small>
                    </div>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Content -->
            <div class="content">
                <!-- Messages -->
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Page Header -->
                <div class="page-header">
                    <h2 class="page-title">Admin Users</h2>
                    <?php if (hasPermission('add_users')): ?>
                    <button class="btn btn-success" onclick="openModal('addUserModal')">
                        <i class="fas fa-plus"></i> Add New User
                    </button>
                    <?php endif; ?>
                </div>


                <!-- Role Summary Section -->
                <div class="role-summary-container">
                    <h3><i class="fas fa-info-circle"></i> Role Summary & Permissions</h3>
                    <div class="role-summary-grid">
                        <?php
                        // Get all roles with their permissions
                        $stmt = $pdo->query("
                            SELECT r.name, r.display_name, r.description,
                                   COUNT(rp.permission_id) as permission_count
                            FROM admin_roles r
                            LEFT JOIN admin_role_permissions rp ON r.id = rp.role_id
                            WHERE r.is_active = 1
                            GROUP BY r.id, r.name, r.display_name, r.description
                            ORDER BY r.name = 'super_admin' DESC, r.name
                        ");
                        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($roles as $role):
                            // Get specific permissions for this role
                            $stmt = $pdo->prepare("
                                SELECT p.name, p.display_name, p.module
                                FROM admin_permissions p
                                JOIN admin_role_permissions rp ON p.id = rp.permission_id
                                JOIN admin_roles r ON rp.role_id = r.id
                                WHERE r.name = ?
                                ORDER BY p.module, p.name
                            ");
                            $stmt->execute([$role['name']]);
                            $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            // Group permissions by module
                            $modules = [];
                            foreach ($permissions as $perm) {
                                $modules[$perm['module']][] = $perm['display_name'];
                            }

                            // Check for restricted modules
                            $hasUserManagement = false;
                            $hasSettings = false;
                            $hasWhatsApp = false;

                            foreach ($permissions as $perm) {
                                if (in_array($perm['name'], ['view_users', 'add_users', 'edit_users', 'delete_users', 'manage_roles'])) {
                                    $hasUserManagement = true;
                                }
                                if (in_array($perm['name'], ['view_settings', 'edit_settings'])) {
                                    $hasSettings = true;
                                }
                                if (in_array($perm['name'], ['view_whatsapp', 'edit_whatsapp'])) {
                                    $hasWhatsApp = true;
                                }
                            }
                        ?>
                        <div class="role-card">
                            <div class="role-header">
                                <h4><?php echo htmlspecialchars($role['display_name']); ?></h4>
                                <span class="permission-count"><?php echo $role['permission_count']; ?> permissions</span>
                            </div>
                            <div class="role-description">
                                <?php echo htmlspecialchars($role['description']); ?>
                            </div>

                            <!-- Restricted Modules Access -->
                            <div class="restricted-modules">
                                <h5>Restricted Modules:</h5>
                                <div class="module-access">
                                    <span class="module-item <?php echo $hasUserManagement ? 'has-access' : 'no-access'; ?>">
                                        <i class="fas fa-users"></i> User Management
                                        <?php echo $hasUserManagement ? '✓' : '✗'; ?>
                                    </span>
                                    <span class="module-item <?php echo $hasSettings ? 'has-access' : 'no-access'; ?>">
                                        <i class="fas fa-cog"></i> Settings
                                        <?php echo $hasSettings ? '✓' : '✗'; ?>
                                    </span>
                                    <span class="module-item <?php echo $hasWhatsApp ? 'has-access' : 'no-access'; ?>">
                                        <i class="fab fa-whatsapp"></i> WhatsApp Settings
                                        <?php echo $hasWhatsApp ? '✓' : '✗'; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Module Permissions -->
                            <div class="module-permissions">
                                <h5>Available Modules:</h5>
                                <div class="modules-list">
                                    <?php foreach ($modules as $module => $modulePermissions): ?>
                                    <div class="module-group">
                                        <strong><?php echo ucfirst(str_replace('_', ' ', $module)); ?>:</strong>
                                        <span class="permissions"><?php echo implode(', ', $modulePermissions); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if (hasPermission('edit_users') && $user['id'] != $_SESSION['admin_id']): ?>
                                    <button class="btn btn-sm" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <?php endif; ?>
                                    <?php if (hasPermission('delete_users') && $user['id'] != $_SESSION['admin_id']): ?>
                                    <button class="btn btn-danger btn-sm" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>



    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New User</h3>
                <button class="close" onclick="closeModal('addUserModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="role_id">Role</label>
                    <select id="role_id" name="role_id" required>
                        <option value="">Select Role</option>
                        <?php
                        echo "<!-- Add User Roles count: " . count($roles) . " -->";
                        if (empty($roles)) {
                            echo '<option value="1">Super Administrator (TEST)</option>';
                            echo '<option value="2">Administrator (TEST)</option>';
                        } else {
                            foreach ($roles as $role): ?>
                        <option value="<?php echo isset($role['id']) ? $role['id'] : 'ERROR_NO_ID'; ?>"><?php echo htmlspecialchars(isset($role['display_name']) ? $role['display_name'] : 'Unknown Role'); ?></option>
                        <?php
                            endforeach;
                        }
                        ?>
                    </select>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="closeModal('addUserModal')">Cancel</button>
                    <button type="submit" class="btn btn-success">Create User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit User</h3>
                <button class="close" onclick="closeModal('editUserModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="form-group">
                    <label for="edit_username">Username</label>
                    <input type="text" id="edit_username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit_full_name">Full Name</label>
                    <input type="text" id="edit_full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_role_id">Role</label>
                    <select id="edit_role_id" name="role_id" required>
                        <option value="">Select Role</option>
                        <?php

                        // Force show some roles for testing
                        if (empty($roles)) {
                            echo '<option value="" disabled>No roles available - Check database connection</option>';
                            // Add some test roles
                            echo '<option value="1">Super Administrator (TEST)</option>';
                            echo '<option value="2">Administrator (TEST)</option>';
                        } else {
                            foreach ($roles as $role):
                        ?>
                        <option value="<?php echo isset($role['id']) ? $role['id'] : 'ERROR_NO_ID'; ?>"><?php echo htmlspecialchars(isset($role['display_name']) ? $role['display_name'] : 'Unknown Role'); ?></option>
                        <?php
                            endforeach;
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="edit_is_active" name="is_active" value="1">
                        <label for="edit_is_active">Active</label>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="closeModal('editUserModal')">Cancel</button>
                    <button type="submit" class="btn btn-success">Update User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div id="deleteUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Delete User</h3>
                <button class="close" onclick="closeModal('deleteUserModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="delete_user_id">
                <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="closeModal('deleteUserModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_full_name').value = user.full_name;

            const roleSelect = document.getElementById('edit_role_id');
            roleSelect.value = user.role_id;

            // If value didn't set, try to find matching option
            if (roleSelect.value !== user.role_id.toString()) {
                for (let i = 0; i < roleSelect.options.length; i++) {
                    if (roleSelect.options[i].value == user.role_id) {
                        roleSelect.selectedIndex = i;
                        break;
                    }
                }
            }

            document.getElementById('edit_is_active').checked = user.is_active == 1;
            openModal('editUserModal');
        }

        function deleteUser(userId) {
            document.getElementById('delete_user_id').value = userId;
            openModal('deleteUserModal');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
