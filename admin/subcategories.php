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

            case 'edit_subcategory':
                $id = intval($_POST['subcategory_id']);
                $category_id = intval($_POST['category_id']);
                $name = trim($_POST['subcategory_name']);
                $description = trim($_POST['subcategory_description']);
                $sort_order = intval($_POST['subcategory_sort_order']);

                if (!empty($name) && $id > 0 && $category_id > 0) {
                    $slug = strtolower(str_replace(' ', '-', $name));
                    $stmt = $pdo->prepare("UPDATE subcategories SET category_id = ?, name = ?, slug = ?, description = ?, sort_order = ? WHERE id = ?");
                    if ($stmt->execute([$category_id, $name, $slug, $description, $sort_order, $id])) {
                        $message = '<div class="success">Subcategory updated successfully!</div>';
                    } else {
                        $message = '<div class="error">Failed to update subcategory.</div>';
                    }
                } else {
                    $message = '<div class="error">Please fill in all required fields.</div>';
                }
                break;

            case 'delete_subcategory':
                $id = intval($_POST['subcategory_id']);
                if ($id > 0) {
                    $stmt = $pdo->prepare("DELETE FROM subcategories WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $message = '<div class="success">Subcategory deleted successfully!</div>';
                    } else {
                        $message = '<div class="error">Failed to delete subcategory.</div>';
                    }
                }
                break;
        }
    }
}

// Get subcategories with category names and product counts
$stmt = $pdo->query("
    SELECT s.*,
           c.name as category_name,
           COUNT(p.id) as product_count
    FROM subcategories s
    JOIN categories c ON s.category_id = c.id
    LEFT JOIN products p ON s.id = p.subcategory_id AND p.is_active = 1
    WHERE s.is_active = 1 AND c.is_active = 1
    GROUP BY s.id
    ORDER BY c.sort_order, c.name, s.sort_order, s.name
");
$subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for dropdown
$stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order, name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subcategories Management - Gulf Global Co</title>
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

        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            font-size: 1.2rem;
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
            border-left-color: #fff;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 0;
        }

        .top-bar {
            background: white;
            padding: 20px 30px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        .content {
            padding: 30px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .add-subcategory {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .add-subcategory h3 {
            color: #2c5aa0;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
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

        .btn-add {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: transform 0.3s ease;
        }

        .btn-add:hover {
            transform: translateY(-2px);
        }

        .subcategories-table {
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

        .subcategory-name {
            font-weight: 600;
            color: #333;
        }

        .category-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
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

        .subcategory-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .subcategory-card:hover {
            transform: translateY(-5px);
        }

        .subcategory-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .subcategory-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c5aa0;
        }

        .subcategory-category {
            background: #e9ecef;
            color: #6c757d;
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
        }

        .subcategory-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .subcategory-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .stat {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 0.9rem;
        }

        .subcategory-actions {
            display: flex;
            gap: 10px;
        }

        .btn-edit {
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.3s ease;
        }

        .btn-edit:hover {
            background: #218838;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: background 0.3s ease;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }

            .main-content {
                margin-left: 0;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .subcategories-grid {
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
                <a href="subcategories.php" class="menu-item active">
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
                <h1>Subcategories Management</h1>
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


                <!-- Subcategories Table -->
                <div class="subcategories-table">
                    <div class="table-header">
                        <h3><i class="fas fa-list"></i> Subcategories (<?php echo count($subcategories); ?>)</h3>
                        <button class="add-btn" onclick="openSubcategoryModal()">
                            <i class="fas fa-plus"></i> Add Subcategory
                        </button>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Parent Category</th>
                                <th>Description</th>
                                <th>Products</th>
                                <th>Sort Order</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subcategories as $subcategory): ?>
                                <tr>
                                    <td>
                                        <div class="subcategory-name"><?php echo htmlspecialchars($subcategory['name'] ?? ''); ?></div>
                                        <div style="font-size: 0.8rem; color: #666;"><?php echo htmlspecialchars($subcategory['slug'] ?? ''); ?></div>
                                    </td>
                                    <td>
                                        <span class="category-badge"><?php echo htmlspecialchars($subcategory['category_name'] ?? ''); ?></span>
                                    </td>
                                    <td>
                                        <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                                            <?php echo htmlspecialchars($subcategory['description'] ?? ''); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="count-badge"><?php echo $subcategory['product_count']; ?></span>
                                    </td>
                                    <td><?php echo $subcategory['sort_order']; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-edit" onclick="editSubcategory(<?php echo htmlspecialchars(json_encode($subcategory)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-delete" onclick="deleteSubcategory(<?php echo $subcategory['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Subcategory Info -->
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 30px;">
                    <h3 style="color: #2c5aa0; margin-bottom: 15px;"><i class="fas fa-info-circle"></i> Subcategory Management</h3>
                    <p style="color: #666; line-height: 1.6;">
                        Manage subcategories to organize your products into more specific groups.
                        Subcategories help customers find products more easily and improve your site's navigation.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Subcategory Modal -->
    <div id="subcategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="subcategoryModalTitle">Add Subcategory</h3>
                <span class="close" onclick="closeSubcategoryModal()">&times;</span>
            </div>
            <form id="subcategoryForm" method="POST">
                <input type="hidden" name="action" id="subcategoryFormAction" value="add_subcategory">
                <input type="hidden" name="subcategory_id" id="subcategoryId" value="">

                <div class="form-group">
                    <label for="category_id">Parent Category *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name'] ?? ''); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="subcategory_name">Subcategory Name *</label>
                    <input type="text" id="subcategory_name" name="subcategory_name" placeholder="e.g., Smartphones, Laptops" required>
                </div>

                <div class="form-group">
                    <label for="subcategory_description">Description</label>
                    <textarea id="subcategory_description" name="subcategory_description" placeholder="Brief description of this subcategory"></textarea>
                </div>

                <div class="form-group">
                    <label for="subcategory_sort_order">Sort Order</label>
                    <input type="number" id="subcategory_sort_order" name="subcategory_sort_order" value="0" min="0">
                </div>

                <div class="form-buttons">
                    <button type="button" class="btn-cancel" onclick="closeSubcategoryModal()">Cancel</button>
                    <button type="submit" class="btn-save">Save Subcategory</button>
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
            <p>Are you sure you want to delete this subcategory? This will also delete all products in this subcategory.</p>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" value="delete_subcategory">
                <input type="hidden" name="subcategory_id" id="deleteSubcategoryId">
                <div class="form-buttons">
                    <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn-delete">Delete Subcategory</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openSubcategoryModal(subcategory = null) {
            const modal = document.getElementById('subcategoryModal');
            const form = document.getElementById('subcategoryForm');

            if (subcategory) {
                document.getElementById('subcategoryModalTitle').textContent = 'Edit Subcategory';
                document.getElementById('subcategoryFormAction').value = 'edit_subcategory';
                document.getElementById('subcategoryId').value = subcategory.id;
                document.getElementById('category_id').value = subcategory.category_id;
                document.getElementById('subcategory_name').value = subcategory.name;
                document.getElementById('subcategory_description').value = subcategory.description || '';
                document.getElementById('subcategory_sort_order').value = subcategory.sort_order;
            } else {
                document.getElementById('subcategoryModalTitle').textContent = 'Add Subcategory';
                document.getElementById('subcategoryFormAction').value = 'add_subcategory';
                form.reset();
            }

            modal.style.display = 'block';
        }

        function closeSubcategoryModal() {
            document.getElementById('subcategoryModal').style.display = 'none';
        }

        function editSubcategory(subcategory) {
            openSubcategoryModal(subcategory);
        }

        function deleteSubcategory(id) {
            document.getElementById('deleteSubcategoryId').value = id;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const subcategoryModal = document.getElementById('subcategoryModal');
            const deleteModal = document.getElementById('deleteModal');

            if (event.target === subcategoryModal) {
                closeSubcategoryModal();
            }

            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        }
    </script>
</body>
</html>
