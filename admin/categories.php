<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$pdo = getConnection();
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_category':
                $name = trim($_POST['category_name']);
                $description = trim($_POST['category_description']);
                $icon = trim($_POST['category_icon']);
                $sort_order = intval($_POST['sort_order']);

                if (!empty($name)) {
                    $slug = strtolower(str_replace(' ', '-', $name));
                    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, icon, sort_order) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt->execute([$name, $slug, $description, $icon, $sort_order])) {
                        $message = '<div class="success">Category added successfully!</div>';
                    } else {
                        $message = '<div class="error">Failed to add category.</div>';
                    }
                } else {
                    $message = '<div class="error">Please enter a category name.</div>';
                }
                break;

            case 'add_subcategory':
                $category_id = intval($_POST['category_id']);
                $name = trim($_POST['subcategory_name']);
                $description = trim($_POST['subcategory_description']);
                $sort_order = intval($_POST['subcategory_sort_order']);

                if (!empty($name) && $category_id > 0) {
                    $slug = strtolower(str_replace(' ', '-', $name));
                    $stmt = $pdo->prepare("INSERT INTO subcategories (category_id, name, slug, description, sort_order) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt->execute([$category_id, $name, $slug, $description, $sort_order])) {
                        $message = '<div class="success">Subcategory added successfully!</div>';
                    } else {
                        $message = '<div class="error">Failed to add subcategory.</div>';
                    }
                } else {
                    $message = '<div class="error">Please fill in all required fields.</div>';
                }
                break;

            case 'edit_category':
                $id = intval($_POST['category_id']);
                $name = trim($_POST['category_name']);
                $description = trim($_POST['category_description']);
                $icon = trim($_POST['category_icon']);
                $sort_order = intval($_POST['sort_order']);

                if (!empty($name) && $id > 0) {
                    $slug = strtolower(str_replace(' ', '-', $name));
                    $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, icon = ?, sort_order = ? WHERE id = ?");
                    if ($stmt->execute([$name, $slug, $description, $icon, $sort_order, $id])) {
                        $message = '<div class="success">Category updated successfully!</div>';
                    } else {
                        $message = '<div class="error">Failed to update category.</div>';
                    }
                } else {
                    $message = '<div class="error">Please fill in all required fields.</div>';
                }
                break;

            case 'delete_category':
                $id = intval($_POST['category_id']);
                if ($id > 0) {
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $message = '<div class="success">Category deleted successfully!</div>';
                    } else {
                        $message = '<div class="error">Failed to delete category.</div>';
                    }
                }
                break;
        }
    }
}

// Get categories with subcategories and product counts
$stmt = $pdo->query("
    SELECT c.*,
           COUNT(DISTINCT s.id) as subcategory_count,
           COUNT(DISTINCT p.id) as product_count
    FROM categories c
    LEFT JOIN subcategories s ON c.id = s.category_id AND s.is_active = 1
    LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
    WHERE c.is_active = 1
    GROUP BY c.id
    ORDER BY c.sort_order, c.name
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get subcategories for each category
foreach ($categories as &$category) {
    $stmt = $pdo->prepare("
        SELECT s.*, COUNT(p.id) as product_count
        FROM subcategories s
        LEFT JOIN products p ON s.id = p.subcategory_id AND p.is_active = 1
        WHERE s.category_id = ? AND s.is_active = 1
        GROUP BY s.id
        ORDER BY s.sort_order, s.name
    ");
    $stmt->execute([$category['id']]);
    $category['subcategories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - Gulf Global Co</title>
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

        /* Categories Table */
        .categories-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            background: #f8f9fa;
        }

        .table-header h3 {
            color: #2c5aa0;
            margin: 0;
        }

        .add-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .add-btn:hover {
            transform: translateY(-2px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c5aa0;
        }

        .category-icon-cell {
            font-size: 1.5rem;
            color: #667eea;
            text-align: center;
        }

        .category-name {
            font-weight: 600;
            color: #333;
        }

        .count-badge {
            background: #e9ecef;
            color: #495057;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-edit, .btn-delete {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: #28a745;
            color: white;
        }

        .btn-edit:hover {
            background: #218838;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        /* Modal Styles */
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
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .btn-save:hover {
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        .category-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .category-card:hover {
            transform: translateY(-5px);
        }

        .category-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.5rem;
            color: white;
        }

        .category-icon.groceries { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .category-icon.meats { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .category-icon.building { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }

        .category-card h3 {
            color: #2c5aa0;
            margin-bottom: 10px;
            font-size: 1.3rem;
        }

        .category-count {
            background: #f8f9fa;
            color: #666;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .category-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn-view {
            background: #2c5aa0;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.3s ease;
        }

        .btn-view:hover {
            background: #1e3d6f;
        }

        /* Add Category Form */
        .add-category {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .add-category h3 {
            color: #2c5aa0;
            margin-bottom: 20px;
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

        .btn-add {
            background: #4ade80;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-add:hover {
            background: #22c55e;
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

            .categories-grid {
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
                <a href="categories.php" class="menu-item active">
                    <i class="fas fa-tags"></i> Categories
                </a>
                <a href="subcategories.php" class="menu-item">
                    <i class="fas fa-list"></i> Subcategories
                </a>
                <a href="hot-sale.php" class="menu-item">
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
                <h1>Categories Management</h1>
                <div class="admin-info">
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?>
                    </div>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <div class="content">
                <?php echo $message; ?>


                <!-- Categories Table -->
                <div class="categories-table">
                    <div class="table-header">
                        <h3><i class="fas fa-tags"></i> Categories (<?php echo count($categories); ?>)</h3>
                        <button class="add-btn" onclick="openCategoryModal()">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Icon</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Subcategories</th>
                                <th>Products</th>
                                <th>Sort Order</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td>
                                        <div class="category-icon-cell">
                                            <i class="<?php echo htmlspecialchars($category['icon'] ?? 'fas fa-tag'); ?>"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="category-name"><?php echo htmlspecialchars($category['name'] ?? ''); ?></div>
                                        <div style="font-size: 0.8rem; color: #666;"><?php echo htmlspecialchars($category['slug'] ?? ''); ?></div>
                                    </td>
                                    <td>
                                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars($category['description'] ?? ''); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="count-badge"><?php echo $category['subcategory_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="count-badge"><?php echo $category['product_count']; ?></span>
                                    </td>
                                    <td><?php echo $category['sort_order']; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-edit" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-delete" onclick="deleteCategory(<?php echo $category['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Category Info -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 30px;">
                    <h3 style="color: #2c5aa0; margin-bottom: 15px;"><i class="fas fa-info-circle"></i> Category Management</h3>
                    <p style="color: #666; margin-bottom: 15px;">Categories help organize your products and make them easier to find for customers.</p>
                    <ul style="color: #666; margin-left: 20px;">
                        <li>Add new categories to organize products</li>
                        <li>View products in each category</li>
                        <li>Categories appear in the frontend navigation</li>
                        <li>Products are automatically grouped by category</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="categoryModalTitle">Add Category</h3>
                <span class="close" onclick="closeCategoryModal()">&times;</span>
            </div>
            <form id="categoryForm" method="POST">
                <input type="hidden" name="action" id="categoryFormAction" value="add_category">
                <input type="hidden" name="category_id" id="categoryId" value="">

                <div class="form-group">
                    <label for="category_name">Category Name *</label>
                    <input type="text" id="category_name" name="category_name" placeholder="e.g., Electronics, Clothing" required>
                </div>

                <div class="form-group">
                    <label for="category_icon">Icon Class</label>
                    <input type="text" id="category_icon" name="category_icon" placeholder="e.g., fas fa-shopping-basket" value="fas fa-tag">
                </div>

                <div class="form-group">
                    <label for="category_description">Description</label>
                    <textarea id="category_description" name="category_description" placeholder="Brief description of this category"></textarea>
                </div>

                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" value="0" min="0">
                </div>

                <div class="form-buttons">
                    <button type="button" class="btn-cancel" onclick="closeCategoryModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save Category</button>
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
            <p>Are you sure you want to delete this category? This will also delete all subcategories and products in this category.</p>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" value="delete_category">
                <input type="hidden" name="category_id" id="deleteCategoryId">
                <div class="form-buttons">
                    <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn-delete">Delete Category</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCategoryModal(category = null) {
            const modal = document.getElementById('categoryModal');
            const form = document.getElementById('categoryForm');

            if (category) {
                document.getElementById('categoryModalTitle').textContent = 'Edit Category';
                document.getElementById('categoryFormAction').value = 'edit_category';
                document.getElementById('categoryId').value = category.id;
                document.getElementById('category_name').value = category.name;
                document.getElementById('category_description').value = category.description || '';
                document.getElementById('category_icon').value = category.icon;
                document.getElementById('sort_order').value = category.sort_order;
            } else {
                document.getElementById('categoryModalTitle').textContent = 'Add Category';
                document.getElementById('categoryFormAction').value = 'add_category';
                form.reset();
            }

            modal.style.display = 'block';
        }

        function closeCategoryModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }

        function editCategory(category) {
            openCategoryModal(category);
        }

        function deleteCategory(id) {
            document.getElementById('deleteCategoryId').value = id;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const categoryModal = document.getElementById('categoryModal');
            const deleteModal = document.getElementById('deleteModal');

            if (event.target === categoryModal) {
                closeCategoryModal();
            }

            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>
