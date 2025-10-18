<?php
/**
 * Gulf Global Co - Database Setup Script
 * Run this script once to initialize the database and create admin user
 */

// Database configuration
$db_config = [
    'host' => 'localhost',
    'name' => 'gulf_global_co',
    'user' => 'root',
    'pass' => 'MySQL123!'
];

$setup_complete = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update database config if provided
        if (!empty($_POST['db_host'])) $db_config['host'] = $_POST['db_host'];
        if (!empty($_POST['db_name'])) $db_config['name'] = $_POST['db_name'];
        if (!empty($_POST['db_user'])) $db_config['user'] = $_POST['db_user'];
        if (!empty($_POST['db_pass'])) $db_config['pass'] = $_POST['db_pass'];

        // Test connection
        $pdo = new PDO("mysql:host={$db_config['host']}", $db_config['user'], $db_config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_config['name']}`");
        $pdo->exec("USE `{$db_config['name']}`");

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

        // Create default admin user
        $admin_username = $_POST['admin_username'] ?? 'admin';
        $admin_email = $_POST['admin_email'] ?? 'admin@gulfglobal.co';
        $admin_password = $_POST['admin_password'] ?? 'admin123';
        $admin_name = $_POST['admin_name'] ?? 'Administrator';

        // Check if admin already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ?");
        $stmt->execute([$admin_username]);

        if ($stmt->fetchColumn() == 0) {
            $hashedPassword = password_hash($admin_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$admin_username, $admin_email, $hashedPassword, $admin_name]);
        }

        // Insert sample products
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

        // Update config file
        $config_content = "<?php
// Database Configuration
define('DB_HOST', '{$db_config['host']}');
define('DB_NAME', '{$db_config['name']}');
define('DB_USER', '{$db_config['user']}');
define('DB_PASS', '{$db_config['pass']}');

// Create connection
function getConnection() {
    try {
        \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME, DB_USER, DB_PASS);
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return \$pdo;
    } catch(PDOException \$e) {
        die(\"Connection failed: \" . \$e->getMessage());
    }
}
?>";

        file_put_contents('config/database.php', $config_content);

        $setup_complete = true;

    } catch (Exception $e) {
        $error_message = 'Setup failed: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Gulf Global Co</title>
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
            background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .setup-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }

        .setup-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .setup-header h1 {
            color: #2c5aa0;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .setup-header p {
            color: #666;
            font-size: 1rem;
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

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2c5aa0;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .setup-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .setup-btn:hover {
            transform: translateY(-2px);
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .action-btn {
            flex: 1;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .action-btn.primary {
            background: #2c5aa0;
            color: white;
        }

        .action-btn.secondary {
            background: #6c757d;
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .info-box h3 {
            color: #2c5aa0;
            margin-bottom: 10px;
        }

        .info-box ul {
            margin-left: 20px;
        }

        .info-box li {
            margin-bottom: 5px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1><i class="fas fa-cog"></i> Database Setup</h1>
            <p>Gulf Global Co - Initialize your database and admin account</p>
        </div>

        <?php if ($setup_complete): ?>
            <div class="success">
                <h3><i class="fas fa-check-circle"></i> Setup Complete!</h3>
                <p>Your database has been initialized successfully. You can now start using the admin panel.</p>

                <div class="success-actions">
                    <a href="admin/login.php" class="action-btn primary">
                        <i class="fas fa-shield-alt"></i> Admin Login
                    </a>
                    <a href="index.php" class="action-btn secondary">
                        <i class="fas fa-home"></i> View Website
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php if ($error_message): ?>
                <div class="error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="info-box">
                <h3><i class="fas fa-info-circle"></i> Setup Information</h3>
                <ul>
                    <li>This will create the database and required tables</li>
                    <li>Sample products will be added automatically</li>
                    <li>An admin account will be created for you</li>
                    <li>Make sure MySQL is running on your system</li>
                </ul>
            </div>

            <form method="POST">
                <h3 style="color: #2c5aa0; margin-bottom: 20px;">Database Configuration</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="db_host">Database Host</label>
                        <input type="text" id="db_host" name="db_host" value="localhost" required>
                    </div>
                    <div class="form-group">
                        <label for="db_name">Database Name</label>
                        <input type="text" id="db_name" name="db_name" value="gulf_global_co" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="db_user">Database User</label>
                        <input type="text" id="db_user" name="db_user" value="root" required>
                    </div>
                    <div class="form-group">
                        <label for="db_pass">Database Password</label>
                        <input type="password" id="db_pass" name="db_pass" value="MySQL123!" placeholder="Enter MySQL password">
                    </div>
                </div>

                <h3 style="color: #2c5aa0; margin: 30px 0 20px 0;">Admin Account</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="admin_username">Admin Username</label>
                        <input type="text" id="admin_username" name="admin_username" value="admin" required>
                    </div>
                    <div class="form-group">
                        <label for="admin_email">Admin Email</label>
                        <input type="email" id="admin_email" name="admin_email" value="admin@gulfglobal.co" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="admin_name">Admin Full Name</label>
                        <input type="text" id="admin_name" name="admin_name" value="Administrator" required>
                    </div>
                    <div class="form-group">
                        <label for="admin_password">Admin Password</label>
                        <input type="password" id="admin_password" name="admin_password" value="admin123" required>
                    </div>
                </div>

                <button type="submit" class="setup-btn">
                    <i class="fas fa-rocket"></i> Initialize Database
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
