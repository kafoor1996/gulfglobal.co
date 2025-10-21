<?php
session_start();
require_once 'includes/auth.php';

// Check if admin is logged in and has permission to view WhatsApp settings
requireLogin();
requirePermission('view_whatsapp');

// Refresh session variables if needed
refreshSession();

$pdo = getConnection();

// Display success/error messages from session
$message = '';
$error = '';
if (isset($_SESSION['success_message'])) {
    $message = '<div class="success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error = '<div class="error">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $whatsapp_method = $_POST['whatsapp_method'] ?? 'direct';
    $instance_id = $_POST['instance_id'] ?? '';
    $access_token = $_POST['access_token'] ?? '';
    $api_url = $_POST['api_url'] ?? 'https://dealsms.in/api/send';

    try {
        // Update or insert WhatsApp settings
        $stmt = $pdo->prepare("
            INSERT INTO whatsapp_settings (method, instance_id, access_token, api_url, updated_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            method = VALUES(method),
            instance_id = VALUES(instance_id),
            access_token = VALUES(access_token),
            api_url = VALUES(api_url),
            updated_at = VALUES(updated_at)
        ");

        $stmt->execute([$whatsapp_method, $instance_id, $access_token, $api_url]);
        $_SESSION['success_message'] = 'WhatsApp settings updated successfully!';
        header('Location: whatsapp-settings.php');
        exit();
    } catch (PDOException $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Get current settings
try {
    $stmt = $pdo->prepare("SELECT * FROM whatsapp_settings ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching settings: " . $e->getMessage();
    $settings = null;
}

// Display success message from session
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Settings - Gulf Global Co</title>
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
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
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
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
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

        .top-bar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .top-bar h1 {
            color: #2c5aa0;
            font-size: 1.8rem;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
            position: absolute;
            right: 30px;
            top: 50%;
            transform: translateY(-50%);
        }

        /* Right Side Dots Menu */
        .admin-menu {
            position: relative;
            display: inline-block;
        }

        .admin-menu-btn {
            background: none;
            border: none;
            color: #666;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .admin-menu-btn:hover {
            background: #f0f0f0;
            color: #333;
        }

        .admin-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            z-index: 1000;
            display: none;
        }

        .admin-dropdown.show {
            display: block;
        }

        .admin-dropdown-header {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .admin-dropdown-header .admin-details {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .admin-dropdown-header .admin-name {
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }

        .admin-dropdown-header .role-badge {
            background: #2c5aa0;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.7rem;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .admin-dropdown-divider {
            height: 1px;
            background: #f0f0f0;
            margin: 0;
        }

        .admin-dropdown-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #333;
            text-decoration: none;
            transition: background 0.3s ease;
            font-size: 0.9rem;
        }

        .admin-dropdown-item:hover {
            background: #f8f9fa;
            color: #2c5aa0;
        }

        .admin-dropdown-item i {
            margin-right: 10px;
            width: 16px;
            text-align: center;
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

        .content {
            padding: 30px;
        }

        .settings-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .mobile-menu-btn {
            display: block !important;
            background: #2c5aa0;
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1rem;
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 1001;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .mobile-menu-btn:hover {
            background: #1e3d6f;
            transform: translateY(-50%) scale(1.05);
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .overlay.show {
            display: block;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2c5aa0;
        }

        .form-group textarea {
            height: 80px;
            resize: vertical;
        }

        .method-options {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .method-option {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .method-option input[type="radio"] {
            width: auto;
        }

        .api-fields {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 10px;
            border: 1px solid #e9ecef;
        }

        .btn {
            background: #2c5aa0;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn:hover {
            background: #1e3d6f;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .top-bar {
                padding: 15px 20px;
                position: relative;
                justify-content: center;
            }

            .admin-info {
                right: 20px;
            }

            .sidebar {
                transform: translateX(-100%);
                width: 280px;
                z-index: 1000;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 0;
                width: 100%;
                max-width: 100%;
            }

            .content {
                width: 100%;
                max-width: 100%;
            }

            .content > div {
                padding: 20px;
                width: 100%;
                max-width: 100%;
            }

            .method-options {
                flex-direction: column;
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
            <div class="top-bar">
                <button class="mobile-menu-btn" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>WhatsApp Settings</h1>
                <div class="admin-info">
                    <div class="admin-menu">
                        <button class="admin-menu-btn" onclick="toggleAdminMenu()">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="admin-dropdown" id="adminDropdown">
                            <div class="admin-dropdown-header">
                                <div class="admin-details">
                                    <span class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                                    <small class="role-badge"><?php echo htmlspecialchars($_SESSION['admin_role'] ?? 'Unknown'); ?></small>
                                </div>
                            </div>
                            <div class="admin-dropdown-divider"></div>
                            <a href="settings.php" class="admin-dropdown-item">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                            <a href="users.php" class="admin-dropdown-item">
                                <i class="fas fa-users"></i> Users
                            </a>
                            <a href="../index.php" class="admin-dropdown-item" target="_blank">
                                <i class="fas fa-external-link-alt"></i> View Website
                            </a>
                            <a href="logout.php" class="admin-dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <?php if ($message): ?>
                    <div class="success"><?php echo $message; ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="settings-form">
                    <form method="POST">
                        <div class="form-group">
                            <label>WhatsApp Method</label>
                            <div class="method-options">
                                <div class="method-option">
                                    <input type="radio" id="direct" name="whatsapp_method" value="direct" <?php echo ($settings['method'] ?? 'direct') == 'direct' ? 'checked' : ''; ?>>
                                    <label for="direct">Direct Link</label>
                                </div>
                                <div class="method-option">
                                    <input type="radio" id="api" name="whatsapp_method" value="api" <?php echo ($settings['method'] ?? '') == 'api' ? 'checked' : ''; ?>>
                                    <label for="api">API Integration</label>
                                </div>
                            </div>
                        </div>

                        <div id="api-fields" class="api-fields" style="<?php echo ($settings['method'] ?? 'direct') == 'api' ? 'display: block;' : 'display: none;'; ?>">
                            <div class="form-group">
                                <label for="instance_id">Instance ID</label>
                                <input type="text" id="instance_id" name="instance_id" value="<?php echo htmlspecialchars($settings['instance_id'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="access_token">Access Token</label>
                                <input type="text" id="access_token" name="access_token" value="<?php echo htmlspecialchars($settings['access_token'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="api_url">API URL</label>
                                <input type="url" id="api_url" name="api_url" value="<?php echo htmlspecialchars($settings['api_url'] ?? 'https://dealsms.in/api/send'); ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn">
                            <i class="fas fa-save"></i> Save WhatsApp Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show/hide API fields based on method selection
        document.addEventListener('DOMContentLoaded', function() {
            const directRadio = document.getElementById('direct');
            const apiRadio = document.getElementById('api');
            const apiFields = document.getElementById('api-fields');

            function toggleApiFields() {
                if (apiRadio.checked) {
                    apiFields.style.display = 'block';
                } else {
                    apiFields.style.display = 'none';
                }
            }

            directRadio.addEventListener('change', toggleApiFields);
            apiRadio.addEventListener('change', toggleApiFields);
        });

        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.overlay');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            const menuIcon = menuBtn.querySelector('i');

            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');

            // Toggle icon between hamburger and X
            if (sidebar.classList.contains('show')) {
                menuIcon.className = 'fas fa-times';
            } else {
                menuIcon.className = 'fas fa-bars';
            }
        }

        function closeSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.overlay');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            const menuIcon = menuBtn.querySelector('i');

            sidebar.classList.remove('show');
            overlay.classList.remove('show');

            // Reset icon to hamburger
            menuIcon.className = 'fas fa-bars';
        }

        // Close sidebar when clicking on menu items
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    closeSidebar();
                }
            });
        });

        // Close sidebar on window resize if desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        });

        function toggleAdminMenu() {
            const dropdown = document.getElementById('adminDropdown');
            dropdown.classList.toggle('show');
        }

        // Close admin menu when clicking outside
        document.addEventListener('click', (event) => {
            const adminMenu = document.querySelector('.admin-menu');
            const adminDropdown = document.getElementById('adminDropdown');
            
            if (adminMenu && !adminMenu.contains(event.target)) {
                adminDropdown.classList.remove('show');
            }
        });
    </script>

    <!-- Mobile Overlay -->
    <div class="overlay" onclick="closeSidebar()"></div>
</body>
</html>