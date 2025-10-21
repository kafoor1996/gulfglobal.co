<?php
session_start();
require_once 'includes/auth.php';

// Check if admin is logged in and has appropriate permissions
requireLogin();

// Refresh session variables if needed
refreshSession();

$pdo = getConnection();

// Check if is_hot_sale column exists, if not create it
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'is_hot_sale'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE products ADD COLUMN is_hot_sale TINYINT(1) DEFAULT 0");
    }
} catch (Exception $e) {
    // Column might already exist or table doesn't exist
}
// Display success/error messages from session
$message = '';
if (isset($_SESSION['success_message'])) {
    $message = '<div class="success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $message = '<div class="error">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_hot_sale':
                $product_id = intval($_POST['product_id']);
                $is_hot_sale = isset($_POST['is_hot_sale']) ? 1 : 0;

                $stmt = $pdo->prepare("UPDATE products SET is_hot_sale = ? WHERE id = ?");
                if ($stmt->execute([$is_hot_sale, $product_id])) {
                    $_SESSION['success_message'] = 'Hot sale status updated successfully!';
                    header('Location: hot-sale.php');
                    exit();
                } else {
                    $_SESSION['error_message'] = 'Failed to update hot sale status.';
                    header('Location: hot-sale.php');
                    exit();
                }
                break;
        }
    }
}

// Get all products with category information
try {
    $stmt = $pdo->query("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_active = 1
        ORDER BY p.is_hot_sale DESC, p.name ASC
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $products = [];
    $message = '<div class="error">Error loading products: ' . $e->getMessage() . '</div>';
}

// Get hot sale statistics
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_hot_sale = 1 AND is_active = 1");
    $hot_sale_count = $stmt->fetchColumn();
} catch (Exception $e) {
    $hot_sale_count = 0;
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1");
    $total_products = $stmt->fetchColumn();
} catch (Exception $e) {
    $total_products = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hot Sale Management - Gulf Global Co</title>
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

        .content {
            padding: 30px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.hot-sale { background: #fb641b; }
        .stat-icon.total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }

        .stat-content h3 {
            font-size: 2rem;
            color: #2c5aa0;
            margin-bottom: 5px;
        }

        .stat-content p {
            color: #666;
            font-size: 0.9rem;
        }

        /* Products Table */
        .products-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .table-header {
            background: #2c5aa0;
            color: white;
            padding: 20px;
        }

        .table-header h3 {
            font-size: 1.2rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .product-name {
            font-weight: 500;
            color: #2c5aa0;
        }

        .product-price {
            font-weight: bold;
            color: #22c55e;
        }

        .hot-sale-badge {
            background: #fb641b;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
        }

        .regular-badge {
            background: #6c757d;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #fb641b;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
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
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
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
                <a href="dashboard.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="products.php" class="menu-item">
                    <i class="fas fa-box"></i> Products
                </a>
                <a href="categories.php" class="menu-item">
                    <i class="fas fa-tags"></i> Categories
                </a>
                <a href="subcategories.php" class="menu-item">
                    <i class="fas fa-list"></i> Subcategories
                </a>
                <a href="hot-sale.php" class="menu-item active">
                    <i class="fas fa-fire"></i> Hot Sale
                </a>
                <a href="orders.php" class="menu-item">
                    <i class="fas fa-shopping-cart"></i> Orders
                </a>
                <a href="settings.php" class="menu-item">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-bar">
                <h1><i class="fas fa-fire"></i> Hot Sale Management</h1>
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

            <div class="content">
                <?php echo $message; ?>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon hot-sale">
                            <i class="fas fa-fire"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $hot_sale_count; ?></h3>
                            <p>Hot Sale Products</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon total">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $total_products; ?></h3>
                            <p>Total Products</p>
                        </div>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="products-table">
                    <div class="table-header">
                        <h3><i class="fas fa-fire"></i> Manage Hot Sale Products</h3>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Hot Sale</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #666;">
                                        <i class="fas fa-box" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                                        No products found. <a href="products.php">Add some products first</a>.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                            <div style="font-size: 0.8rem; color: #666;"><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 50)) . '...'; ?></div>
                                        </td>
                                        <td><?php echo ucfirst(htmlspecialchars($product['category_name'] ?? 'Uncategorized')); ?></td>
                                        <td class="product-price">₹<?php echo number_format($product['price'], 2); ?></td>
                                        <td>
                                            <?php if ($product['is_hot_sale']): ?>
                                                <span class="hot-sale-badge">Hot Sale</span>
                                            <?php else: ?>
                                                <span class="regular-badge">Regular</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_hot_sale">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <label class="toggle-switch">
                                                    <input type="checkbox" name="is_hot_sale" <?php echo $product['is_hot_sale'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                                                    <span class="slider"></span>
                                                </label>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Hot Sale Info -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 30px;">
                    <h3 style="color: #2c5aa0; margin-bottom: 15px;"><i class="fas fa-info-circle"></i> Hot Sale Management</h3>
                    <p style="color: #666; margin-bottom: 15px;">Hot sale products are featured prominently on your website and help drive sales.</p>
                    <ul style="color: #666; margin-left: 20px;">
                        <li>Toggle products on/off for hot sale status</li>
                        <li>Hot sale products appear in the "Hot Sale" section</li>
                        <li>They get special badges and styling</li>
                        <li>Use this feature to highlight your best products</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
