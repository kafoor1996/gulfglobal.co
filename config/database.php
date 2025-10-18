<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'gulf_global_co');
define('DB_USER', 'root');
define('DB_PASS', 'MySQL123!');

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

    // Create admin users table
    $sql = "CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // Create default admin user if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@gulfglobal.co', $hashedPassword, 'Administrator']);
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
