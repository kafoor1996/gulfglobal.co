<?php
session_start();
require_once 'includes/auth.php';

// Check if admin is logged in and has permission to view products
requireLogin();
requirePermission('view_products');

// Refresh session variables if needed
refreshSession();

$pdo = getConnection();
$message = '';

// Display success message from session
if (isset($_SESSION['success_message'])) {
    $message = '<div class="success">' . $_SESSION['success_message'] . '</div>';
    unset($_SESSION['success_message']);
}

// Image upload functions
function handleImageUpload($file, $product_id, $type = 'gallery') {
    $upload_dir = '../images/products/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.'];
    }

    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size too large. Maximum 5MB allowed.'];
    }

    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'product_' . $product_id . '_' . $type . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // Save to product_images table
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, image_type, sort_order) VALUES (?, ?, ?, 0)");
        $stmt->execute([$product_id, 'images/products/' . $filename, $type]);

        return ['success' => true, 'path' => 'images/products/' . $filename];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file.'];
    }
}

function handleMultipleImageUpload($files, $product_id) {
    global $pdo;

    $upload_dir = '../images/products/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] == 0) {
            if (!in_array($files['type'][$i], $allowed_types)) {
                continue; // Skip invalid files
            }

            if ($files['size'][$i] > $max_size) {
                continue; // Skip oversized files
            }

            $file_extension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $filename = 'product_' . $product_id . '_gallery_' . time() . '_' . $i . '.' . $file_extension;
            $file_path = $upload_dir . $filename;

            if (move_uploaded_file($files['tmp_name'][$i], $file_path)) {
                // Save to database
                $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, image_type, sort_order) VALUES (?, ?, 'gallery', ?)");
                $stmt->execute([$product_id, 'images/products/' . $filename, $i]);
            }
        }
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $category_id = intval($_POST['category_id']);
                $subcategory_id = !empty($_POST['subcategory_id']) ? intval($_POST['subcategory_id']) : null;
                $is_hot_sale = isset($_POST['is_hot_sale']) ? 1 : 0;

                if (!empty($name) && $price > 0 && $category_id > 0) {
                    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category_id, subcategory_id, is_hot_sale) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$name, $description, $price, $category_id, $subcategory_id, $is_hot_sale])) {
                        $product_id = $pdo->lastInsertId();

                        // Handle image upload
                        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
                            $upload_result = handleImageUpload($_FILES['main_image'], $product_id, 'main');
                            if ($upload_result['success']) {
                                $stmt = $pdo->prepare("UPDATE products SET main_image = ? WHERE id = ?");
                                $stmt->execute([$upload_result['path'], $product_id]);
                            }
                        }

                        // Handle gallery images
                        if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
                            handleMultipleImageUpload($_FILES['gallery_images'], $product_id);
                        }

                        $_SESSION['success_message'] = 'Product added successfully!';
                        header('Location: products.php');
                        exit();
                    } else {
                        $message = '<div class="error">Failed to add product.</div>';
                    }
                } else {
                    $message = '<div class="error">Please fill in all required fields.</div>';
                }
                break;

            case 'edit':
                $id = intval($_POST['id']);
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $category_id = intval($_POST['category_id']);
                $subcategory_id = !empty($_POST['subcategory_id']) ? intval($_POST['subcategory_id']) : null;
                $is_hot_sale = isset($_POST['is_hot_sale']) ? 1 : 0;

                $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, subcategory_id = ?, is_hot_sale = ? WHERE id = ?");
                if ($stmt->execute([$name, $description, $price, $category_id, $subcategory_id, $is_hot_sale, $id])) {
                    // Handle main image upload
                    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
                        $upload_result = handleImageUpload($_FILES['main_image'], $id, 'main');
                        if ($upload_result['success']) {
                            $stmt = $pdo->prepare("UPDATE products SET main_image = ? WHERE id = ?");
                            $stmt->execute([$upload_result['path'], $id]);
                        }
                    }

                    // Handle gallery images
                    if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
                        handleMultipleImageUpload($_FILES['gallery_images'], $id);
                    }

                    $_SESSION['success_message'] = 'Product updated successfully!';
                    header('Location: products.php');
                    exit();
                } else {
                    $message = '<div class="error">Failed to update product.</div>';
                }
                break;

            case 'delete':
                $id = intval($_POST['id']);
                $stmt = $pdo->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $_SESSION['success_message'] = 'Product deleted successfully!';
                    header('Location: products.php');
                    exit();
                } else {
                    $message = '<div class="error">Failed to delete product.</div>';
                }
                break;
        }
    }
}

// Get products
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category_id'] ?? '';
$subcategory_filter = $_GET['subcategory_id'] ?? '';
$hot_sale_filter = $_GET['hot_sale'] ?? '';

$where_conditions = ["p.is_active = 1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($subcategory_filter)) {
    $where_conditions[] = "p.subcategory_id = ?";
    $params[] = $subcategory_filter;
}

if ($hot_sale_filter === '1') {
    $where_conditions[] = "p.is_hot_sale = 1";
}

$where_clause = implode(' AND ', $where_conditions);
$sql = "SELECT p.*, c.name as category_name, s.name as subcategory_name,
               (SELECT image_path FROM product_images WHERE product_id = p.id AND image_type = 'main' AND is_active = 1 LIMIT 1) as main_image
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN subcategories s ON p.subcategory_id = s.id
        WHERE $where_clause ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories and subcategories from database
$stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order, name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id, name, category_id FROM subcategories WHERE is_active = 1 ORDER BY sort_order, name");
$subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get product images
function getProductImages($product_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? AND is_active = 1 ORDER BY image_type, sort_order");
    $stmt->execute([$product_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Group subcategories by category
$subcategories_by_category = [];
foreach ($subcategories as $subcategory) {
    $subcategories_by_category[$subcategory['category_id']][] = $subcategory;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - Gulf Global Co</title>
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

        /* Filters */
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .filter-row {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #2c5aa0;
        }

        .filter-btn {
            background: #2c5aa0;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .filter-btn:hover {
            background: #1e3d6f;
        }

        /* Products Table */
        .products-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .table-header {
            background: #2c5aa0;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 {
            font-size: 1.2rem;
        }

        .add-btn {
            background: #4ade80;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .add-btn:hover {
            background: #22c55e;
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

        .category-name {
            font-weight: 500;
            color: #2c5aa0;
        }

        .subcategory-name {
            font-weight: 400;
            color: #666;
            font-size: 0.9rem;
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

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
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
            margin: 2% auto;
            padding: 30px;
            border-radius: 10px;
            width: 95%;
            max-width: 1200px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            color: #2c5aa0;
        }

        .close {
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
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2c5aa0;
        }

        .form-group textarea {
            height: 80px;
            resize: vertical;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-col {
            flex: 1;
        }

        .form-col-6 {
            flex: 0 0 calc(50% - 10px);
        }

        .form-col-12 {
            flex: 0 0 100%;
        }

        .image-preview-section {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .image-preview-section h4 {
            margin: 0 0 15px 0;
            color: #2c5aa0;
            font-size: 1rem;
        }

        .current-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .image-item {
            position: relative;
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e9ecef;
        }

        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-item .image-type {
            position: absolute;
            top: 2px;
            left: 2px;
            background: rgba(44, 90, 160, 0.8);
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .image-item .delete-image {
            position: absolute;
            top: 2px;
            right: 2px;
            background: rgba(220, 53, 69, 0.8);
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .image-item .delete-image:hover {
            background: rgba(220, 53, 69, 1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        .form-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-save {
            background: #2c5aa0;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
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

            .filter-row {
                flex-direction: column;
            }

            .filter-group {
                min-width: 100%;
            }

            .modal-content {
                width: 98%;
                margin: 1% auto;
                padding: 15px;
            }

            .form-row {
                flex-direction: column;
            }

            .form-col-6 {
                flex: 0 0 100%;
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
                <h1>Products Management</h1>
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

                <!-- Filters -->
                <div class="filters">
                    <form method="GET">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="search">Search Products</label>
                                <input type="text" id="search" name="search" placeholder="Search by name or description..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="filter-group">
                                <label for="category_id">Category</label>
                                <select id="category_id" name="category_id" onchange="updateFilterSubcategories()">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo $category_filter === $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="subcategory_id">Subcategory</label>
                                <select id="subcategory_id" name="subcategory_id">
                                    <option value="">All Subcategories</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="hot_sale">Hot Sale</label>
                                <select id="hot_sale" name="hot_sale">
                                    <option value="">All Products</option>
                                    <option value="1" <?php echo $hot_sale_filter === '1' ? 'selected' : ''; ?>>Hot Sale Only</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <button type="submit" class="filter-btn">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Products Table -->
                <div class="products-table">
                    <div class="table-header">
                        <h3><i class="fas fa-box"></i> Products (<?php echo count($products); ?>)</h3>
                        <button class="add-btn" onclick="openModal()">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Subcategory</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($product['main_image'])): ?>
                                            <img src="../<?php echo htmlspecialchars($product['main_image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 5px; display: none; align-items: center; justify-content: center; color: #999;">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #999;">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <div style="font-size: 0.8rem; color: #666;"><?php echo htmlspecialchars(substr($product['description'], 0, 50)) . '...'; ?></div>
                                    </td>
                                    <td>
                                        <div class="category-name"><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></div>
                                    </td>
                                    <td>
                                        <div class="subcategory-name"><?php echo htmlspecialchars($product['subcategory_name'] ?? 'No Subcategory'); ?></div>
                                    </td>
                                    <td class="product-price">₹<?php echo number_format($product['price'], 2); ?></td>
                                    <td>
                                        <?php if ($product['is_hot_sale']): ?>
                                            <span class="hot-sale-badge">Hot Sale</span>
                                        <?php else: ?>
                                            <span style="color: #666;">Regular</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-edit" onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-delete" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add Product</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="productForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="productId" value="">

                <div class="form-row">
                    <div class="form-col form-col-6">
                        <div class="form-group">
                            <label for="name">Product Name *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                    </div>
                    <div class="form-col form-col-6">
                        <div class="form-group">
                            <label for="price">Price (₹) *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col form-col-6">
                        <div class="form-group">
                            <label for="product_category_id">Category *</label>
                            <select id="product_category_id" name="category_id" required onchange="updateSubcategories()">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-col form-col-6">
                        <div class="form-group">
                            <label for="product_subcategory_id">Subcategory</label>
                            <select id="product_subcategory_id" name="subcategory_id">
                                <option value="">Select Subcategory (Optional)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col form-col-12">
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description"></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col form-col-6">
                        <div class="form-group">
                            <label for="main_image">Main Product Image</label>
                            <input type="file" id="main_image" name="main_image" accept="image/*">
                            <small style="color: #666; font-size: 0.8rem;">Recommended: 800x600px, max 5MB</small>
                        </div>
                    </div>
                    <div class="form-col form-col-6">
                        <div class="form-group">
                            <label for="gallery_images">Gallery Images</label>
                            <input type="file" id="gallery_images" name="gallery_images[]" accept="image/*" multiple>
                            <small style="color: #666; font-size: 0.8rem;">Select multiple images for product gallery</small>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-col form-col-12">
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_hot_sale" name="is_hot_sale">
                                <label for="is_hot_sale">Mark as Hot Sale</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Images Preview -->
                <div id="current-images-section" class="image-preview-section" style="display: none;">
                    <h4>Current Images</h4>
                    <div id="current-images" class="current-images">
                        <!-- Images will be loaded here via JavaScript -->
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Delete</h3>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <p>Are you sure you want to delete this product? This action cannot be undone.</p>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteProductId">
                <div class="form-buttons">
                    <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn-delete">Delete Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Subcategories data from PHP
        const subcategoriesData = <?php echo json_encode($subcategories_by_category); ?>;

        function updateSubcategories() {
            const categorySelect = document.getElementById('product_category_id');
            const subcategorySelect = document.getElementById('product_subcategory_id');
            const categoryId = categorySelect.value;


            // Clear existing options
            subcategorySelect.innerHTML = '<option value="">Select Subcategory (Optional)</option>';

            // Add subcategories for selected category
            if (categoryId && subcategoriesData[categoryId]) {
                subcategoriesData[categoryId].forEach(subcategory => {
                    const option = document.createElement('option');
                    option.value = subcategory.id;
                    option.textContent = subcategory.name;
                    subcategorySelect.appendChild(option);
                });
            } else {
            }
        }

        function updateFilterSubcategories() {
            const categorySelect = document.getElementById('category_id');
            const subcategorySelect = document.getElementById('subcategory_id');
            const categoryId = categorySelect.value;

            // Clear existing options
            subcategorySelect.innerHTML = '<option value="">All Subcategories</option>';

            // Add subcategories for selected category
            if (categoryId && subcategoriesData[categoryId]) {
                subcategoriesData[categoryId].forEach(subcategory => {
                    const option = document.createElement('option');
                    option.value = subcategory.id;
                    option.textContent = subcategory.name;
                    subcategorySelect.appendChild(option);
                });
            }
        }

        // Initialize filter subcategories on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateFilterSubcategories();
        });

        function openModal(product = null) {
            const modal = document.getElementById('productModal');
            const form = document.getElementById('productForm');

            if (product) {
                document.getElementById('modalTitle').textContent = 'Edit Product';
                document.getElementById('formAction').value = 'edit';
                document.getElementById('productId').value = product.id;
                document.getElementById('name').value = product.name;
                document.getElementById('description').value = product.description;
                document.getElementById('price').value = product.price;
                document.getElementById('product_category_id').value = product.category_id || '';
                document.getElementById('is_hot_sale').checked = product.is_hot_sale == 1;

                // Load current images
                loadCurrentImages(product.id);

                // Update subcategories and set selected value
                setTimeout(() => {
                    updateSubcategories();
                    if (product.subcategory_id) {
                        setTimeout(() => {
                            document.getElementById('product_subcategory_id').value = product.subcategory_id;
                        }, 100);
                    }
                }, 50);
            } else {
                document.getElementById('modalTitle').textContent = 'Add Product';
                document.getElementById('formAction').value = 'add';
                form.reset();
                updateSubcategories();

                // Hide current images section for new products
                document.getElementById('current-images-section').style.display = 'none';
            }

            modal.style.display = 'block';
        }

        function loadCurrentImages(productId) {
            if (!productId) return;

            fetch(`get-product-images.php?id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('current-images');
                    const section = document.getElementById('current-images-section');

                    if (data.length > 0) {
                        container.innerHTML = '';
                        data.forEach(image => {
                            const imageItem = document.createElement('div');
                            imageItem.className = 'image-item';
                            imageItem.innerHTML = `
                                <img src="../${image.image_path}" alt="Product Image" onerror="this.parentElement.style.display='none'">
                                <div class="image-type">${image.image_type}</div>
                                <button type="button" class="delete-image" onclick="deleteImage(${image.id}, this)" title="Delete Image">×</button>
                            `;
                            container.appendChild(imageItem);
                        });
                        section.style.display = 'block';
                    } else {
                        section.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error loading images:', error);
                    document.getElementById('current-images-section').style.display = 'none';
                });
        }

        function deleteImage(imageId, button) {
            if (confirm('Are you sure you want to delete this image?')) {
                fetch('delete-image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `image_id=${imageId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        button.parentElement.remove();
                        // Hide section if no images left
                        const container = document.getElementById('current-images');
                        if (container.children.length === 0) {
                            document.getElementById('current-images-section').style.display = 'none';
                        }
                    } else {
                        alert('Error deleting image: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting image');
                });
            }
        }

        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        // Prevent double form submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('productForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Saving...';
                        submitBtn.style.opacity = '0.6';

                        // Re-enable button after 5 seconds in case of errors
                        setTimeout(function() {
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Save Product';
                            submitBtn.style.opacity = '1';
                        }, 5000);
                    }
                });
            }
        });

        function editProduct(product) {
            openModal(product);
        }

        function deleteProduct(id) {
            document.getElementById('deleteProductId').value = id;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const productModal = document.getElementById('productModal');
            const deleteModal = document.getElementById('deleteModal');

            if (event.target === productModal) {
                closeModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>
