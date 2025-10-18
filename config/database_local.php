<?php
// Local Database Configuration
// Use this file for local development environment

define('DB_HOST', 'localhost');
define('DB_NAME', 'mediawor_gulf_global_co');
define('DB_USER', 'mediawor_gulf_global_co');
define('DB_PASS', '69]j6S0xW8QzBlcw');

// Create connection
function getConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Initialize database tables
function initializeDatabase() {
    $pdo = getConnection();

    // Create products table
    $sql = "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        category VARCHAR(100) NOT NULL,
        image VARCHAR(255),
        is_hot_sale BOOLEAN DEFAULT FALSE,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // Create roles table
    $sql = "CREATE TABLE IF NOT EXISTS admin_roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) UNIQUE NOT NULL,
        display_name VARCHAR(100) NOT NULL,
        description TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // Create permissions table
    $sql = "CREATE TABLE IF NOT EXISTS admin_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) UNIQUE NOT NULL,
        display_name VARCHAR(100) NOT NULL,
        description TEXT,
        module VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // Create role_permissions table
    $sql = "CREATE TABLE IF NOT EXISTS admin_role_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role_id INT NOT NULL,
        permission_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (role_id) REFERENCES admin_roles(id) ON DELETE CASCADE,
        FOREIGN KEY (permission_id) REFERENCES admin_permissions(id) ON DELETE CASCADE,
        UNIQUE KEY unique_role_permission (role_id, permission_id)
    )";
    $pdo->exec($sql);

    // Create admin users table
    $sql = "CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        role_id INT NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (role_id) REFERENCES admin_roles(id)
    )";
    $pdo->exec($sql);

    // Insert default roles
    $roles = [
        ['super_admin', 'Super Administrator', 'Full access to all features and user management', 1],
        ['admin', 'Administrator', 'Full access to all features except user management', 1],
        ['manager', 'Manager', 'Access to products, categories, and orders management', 1],
        ['editor', 'Editor', 'Access to products and categories only', 1],
        ['viewer', 'Viewer', 'Read-only access to dashboard and reports', 1]
    ];

    foreach ($roles as $role) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO admin_roles (name, display_name, description, is_active) VALUES (?, ?, ?, ?)");
        $stmt->execute($role);
    }

    // Insert default permissions
    $permissions = [
        // Dashboard permissions
        ['view_dashboard', 'View Dashboard', 'Access to dashboard and statistics', 'dashboard'],

        // Product permissions
        ['view_products', 'View Products', 'View products list', 'products'],
        ['add_products', 'Add Products', 'Create new products', 'products'],
        ['edit_products', 'Edit Products', 'Modify existing products', 'products'],
        ['delete_products', 'Delete Products', 'Delete products', 'products'],

        // Category permissions
        ['view_categories', 'View Categories', 'View categories list', 'categories'],
        ['add_categories', 'Add Categories', 'Create new categories', 'categories'],
        ['edit_categories', 'Edit Categories', 'Modify existing categories', 'categories'],
        ['delete_categories', 'Delete Categories', 'Delete categories', 'categories'],

        // Subcategory permissions
        ['view_subcategories', 'View Subcategories', 'View subcategories list', 'subcategories'],
        ['add_subcategories', 'Add Subcategories', 'Create new subcategories', 'subcategories'],
        ['edit_subcategories', 'Edit Subcategories', 'Modify existing subcategories', 'subcategories'],
        ['delete_subcategories', 'Delete Subcategories', 'Delete subcategories', 'subcategories'],

        // Hot sale permissions
        ['view_hot_sale', 'View Hot Sale', 'View hot sale products', 'hot_sale'],
        ['manage_hot_sale', 'Manage Hot Sale', 'Add/remove products from hot sale', 'hot_sale'],

        // Order permissions
        ['view_orders', 'View Orders', 'View orders list', 'orders'],
        ['manage_orders', 'Manage Orders', 'Update order status and details', 'orders'],

        // Settings permissions
        ['view_settings', 'View Settings', 'View system settings', 'settings'],
        ['edit_settings', 'Edit Settings', 'Modify system settings', 'settings'],

        // WhatsApp permissions
        ['view_whatsapp', 'View WhatsApp Settings', 'View WhatsApp configuration', 'whatsapp'],
        ['edit_whatsapp', 'Edit WhatsApp Settings', 'Modify WhatsApp configuration', 'whatsapp'],

        // User management permissions
        ['view_users', 'View Users', 'View admin users list', 'users'],
        ['add_users', 'Add Users', 'Create new admin users', 'users'],
        ['edit_users', 'Edit Users', 'Modify existing admin users', 'users'],
        ['delete_users', 'Delete Users', 'Delete admin users', 'users'],
        ['manage_roles', 'Manage Roles', 'Create and assign user roles', 'users']
    ];

    foreach ($permissions as $permission) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO admin_permissions (name, display_name, description, module) VALUES (?, ?, ?, ?)");
        $stmt->execute($permission);
    }

    // Assign permissions to roles
    $rolePermissions = [
        // Super Admin - All permissions
        ['super_admin', ['view_dashboard', 'view_products', 'add_products', 'edit_products', 'delete_products',
                        'view_categories', 'add_categories', 'edit_categories', 'delete_categories',
                        'view_subcategories', 'add_subcategories', 'edit_subcategories', 'delete_subcategories',
                        'view_hot_sale', 'manage_hot_sale', 'view_orders', 'manage_orders',
                        'view_settings', 'edit_settings', 'view_whatsapp', 'edit_whatsapp',
                        'view_users', 'add_users', 'edit_users', 'delete_users', 'manage_roles']],

        // Admin - All except user management
        ['admin', ['view_dashboard', 'view_products', 'add_products', 'edit_products', 'delete_products',
                   'view_categories', 'add_categories', 'edit_categories', 'delete_categories',
                   'view_subcategories', 'add_subcategories', 'edit_subcategories', 'delete_subcategories',
                   'view_hot_sale', 'manage_hot_sale', 'view_orders', 'manage_orders',
                   'view_settings', 'edit_settings', 'view_whatsapp', 'edit_whatsapp']],

        // Manager - Products, categories, orders
        ['manager', ['view_dashboard', 'view_products', 'add_products', 'edit_products', 'delete_products',
                     'view_categories', 'add_categories', 'edit_categories', 'delete_categories',
                     'view_subcategories', 'add_subcategories', 'edit_subcategories', 'delete_subcategories',
                     'view_hot_sale', 'manage_hot_sale', 'view_orders', 'manage_orders']],

        // Editor - Products and categories only
        ['editor', ['view_dashboard', 'view_products', 'add_products', 'edit_products',
                    'view_categories', 'add_categories', 'edit_categories',
                    'view_subcategories', 'add_subcategories', 'edit_subcategories',
                    'view_hot_sale', 'manage_hot_sale']],

        // Viewer - Read-only access
        ['viewer', ['view_dashboard', 'view_products', 'view_categories', 'view_subcategories',
                    'view_hot_sale', 'view_orders']]
    ];

    foreach ($rolePermissions as $roleData) {
        $roleName = $roleData[0];
        $permissionNames = $roleData[1];

        // Get role ID
        $stmt = $pdo->prepare("SELECT id FROM admin_roles WHERE name = ?");
        $stmt->execute([$roleName]);
        $roleId = $stmt->fetchColumn();

        foreach ($permissionNames as $permissionName) {
            // Get permission ID
            $stmt = $pdo->prepare("SELECT id FROM admin_permissions WHERE name = ?");
            $stmt->execute([$permissionName]);
            $permissionId = $stmt->fetchColumn();

            // Assign permission to role
            $stmt = $pdo->prepare("INSERT IGNORE INTO admin_role_permissions (role_id, permission_id) VALUES (?, ?)");
            $stmt->execute([$roleId, $permissionId]);
        }
    }

    // Create default admin user if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        // Get super admin role ID
        $stmt = $pdo->prepare("SELECT id FROM admin_roles WHERE name = 'super_admin'");
        $stmt->execute();
        $roleId = $stmt->fetchColumn();

        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, email, password, full_name, role_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@gulfglobal.co', $hashedPassword, 'Administrator', $roleId]);
    }

    // Insert sample products if table is empty
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $sampleProducts = [
            ['Sunrise Sunflower Oil', 'Refined, light oil for daily cooking - 1L bottle', 120.00, 'groceries', 'oil-bottle.jpg', 0],
            ['Royal Almonds (California)', 'Crunchy, rich in protein - Grade A quality - 500g pack', 450.00, 'groceries', 'almonds.jpg', 1],
            ['Fresh Chicken (Whole)', 'Daily sourced, hygienically packed - 1kg', 180.00, 'meats', 'chicken.jpg', 0],
            ['Premium Steel Rods (TMT)', 'High-grade steel rods for construction - 12mm diameter', 65.00, 'building', 'steel.jpg', 1],
            ['Basmati Gold 1121 Rice', 'Aged, aromatic rice - 5kg pack', 280.00, 'groceries', 'rice.jpg', 0],
            ['Fresh Fish (Pomfret)', 'Daily coastal catch - 1kg', 320.00, 'meats', 'fish.jpg', 0],
            ['SuperCem PPC 43', 'Portland Pozzolana Cement for general construction - 50kg bag', 320.00, 'building', 'cement.jpg', 1],
            ['ProShield Interior Emulsion', 'Smooth finish for interiors - 1L can', 180.00, 'building', 'paint.jpg', 0]
        ];

        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category, image, is_hot_sale) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($sampleProducts as $product) {
            $stmt->execute($product);
        }
    }
}

// Initialize database on first run
initializeDatabase();
?>
