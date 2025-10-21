<?php
session_start();
require_once 'includes/auth.php';

// Check if admin is logged in and has permission to view orders
requireLogin();
requirePermission('view_orders');

// Refresh session variables if needed
refreshSession();

$pdo = getConnection();
$message = '';

// For now, we'll show a placeholder since we don't have orders table yet
// This would typically show WhatsApp orders or form submissions
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Gulf Global Co</title>
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
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .top-bar h1 {
            color: #2c5aa0;
            font-size: 1.8rem;
        }

        .admin-info {
            position: absolute;
            right: 30px;
            top: 50%;
            transform: translateY(-50%);
        }

        .admin-details {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
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
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .admin-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .admin-dropdown-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #333;
            text-decoration: none;
            transition: background 0.3s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .admin-dropdown-item:last-child {
            border-bottom: none;
        }

        .admin-dropdown-item:hover {
            background: #f8f9fa;
            color: #2c5aa0;
        }

        .admin-dropdown-item i {
            width: 20px;
            margin-right: 10px;
            color: #666;
        }

        .admin-dropdown-item:hover i {
            color: #2c5aa0;
        }

        .admin-dropdown-header {
            display: flex;
            align-items: center;
            padding: 16px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
        }


        .admin-dropdown-header .admin-details {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .admin-dropdown-header .admin-name {
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }

        .admin-dropdown-header .role-badge {
            background: #4ade80;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 500;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .admin-dropdown-divider {
            height: 1px;
            background: #e0e0e0;
            margin: 8px 0;
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

        .placeholder-card {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .placeholder-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            color: white;
        }

        .placeholder-card h3 {
            color: #2c5aa0;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .placeholder-card p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }

        .info-box h3 {
            color: #2c5aa0;
            margin-bottom: 15px;
        }

        .info-box ul {
            color: #666;
            margin-left: 20px;
        }

        .info-box li {
            margin-bottom: 8px;
        }

        @media (max-width: 768px) {
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

            .top-bar {
                padding: 15px 20px;
                position: relative;
                justify-content: center;
            }

            .top-bar h1 {
                font-size: 1.4rem;
            }

            .admin-info {
                position: absolute;
                right: 20px;
                top: 50%;
                transform: translateY(-50%);
            }

            /* Mobile Table Responsive */
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                margin: 20px 0;
            }

            .table-responsive table {
                min-width: 700px;
                width: 100%;
                border-collapse: collapse;
            }

            .table-responsive th,
            .table-responsive td {
                padding: 8px 12px;
                text-align: left;
                border-bottom: 1px solid #e0e0e0;
                white-space: nowrap;
            }

            .table-responsive th {
                background: #f8f9fa;
                font-weight: 600;
                color: #333;
                font-size: 0.85rem;
                position: sticky;
                top: 0;
                z-index: 10;
            }

            .table-responsive td {
                font-size: 0.8rem;
                color: #666;
            }

            .table-responsive tr:hover {
                background: #f8f9fa;
            }

            .orders-table {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .orders-table table {
                min-width: 800px;
            }

            .orders-table th,
            .orders-table td {
                padding: 8px 12px;
                font-size: 0.8rem;
            }

            .orders-table .order-actions {
                display: flex;
                gap: 5px;
                flex-wrap: wrap;
            }

            .orders-table .btn {
                padding: 4px 8px;
                font-size: 0.75rem;
                min-width: auto;
            }

            .order-actions {
                flex-direction: column;
                gap: 5px;
            }

            .btn {
                padding: 6px 10px;
                font-size: 0.8rem;
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
        }

        .mobile-menu-btn {
            display: none;
        }

        .overlay {
            display: none;
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
                <h1><i class="fas fa-shopping-cart"></i> Orders Management</h1>
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
                                <i class="fas fa-cog"></i>
                                Settings
                            </a>
                            <a href="users.php" class="admin-dropdown-item">
                                <i class="fas fa-users"></i>
                                Users
                            </a>
                            <a href="../index.php" class="admin-dropdown-item" target="_blank">
                                <i class="fas fa-external-link-alt"></i>
                                View Website
                            </a>
                            <a href="logout.php" class="admin-dropdown-item">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="placeholder-card">
                    <div class="placeholder-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Orders Management</h3>
                    <p>This section will show customer orders received through WhatsApp and contact forms. Orders are currently processed through WhatsApp integration.</p>
                </div>

                <div class="info-box">
                    <h3><i class="fas fa-info-circle"></i> Order Processing</h3>
                    <ul>
                        <li>Orders are received through WhatsApp integration</li>
                        <li>Customers can add products to cart and checkout via WhatsApp</li>
                        <li>Order details are sent directly to your WhatsApp number</li>
                        <li>You can track and manage orders through WhatsApp</li>
                        <li>Future versions will include order tracking in admin panel</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Mobile Overlay -->
        <div class="overlay" onclick="closeSidebar()"></div>
    </div>

    <script>
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

        function toggleAdminMenu() {
            const dropdown = document.getElementById('adminDropdown');
            dropdown.classList.toggle('show');
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

        // Close admin menu when clicking outside
        document.addEventListener('click', (event) => {
            const adminMenu = document.querySelector('.admin-menu');
            const adminDropdown = document.getElementById('adminDropdown');

            if (adminMenu && !adminMenu.contains(event.target)) {
                adminDropdown.classList.remove('show');
            }
        });
    </script>
</body>
</html>
