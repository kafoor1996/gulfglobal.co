<?php
/**
 * Database Migration: Add Categories and Subcategories Tables
 * Run this script to create proper category structure
 */

require_once 'config/database.php';

try {
    $pdo = getConnection();

    echo "<h2>Creating Categories and Subcategories Tables...</h2>";

    // Create categories table
    $sql = "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        slug VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        icon VARCHAR(50) DEFAULT 'fas fa-tag',
        sort_order INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "✅ Categories table created successfully<br>";

    // Create subcategories table
    $sql = "CREATE TABLE IF NOT EXISTS subcategories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        description TEXT,
        sort_order INT DEFAULT 0,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
        UNIQUE KEY unique_category_subcategory (category_id, name)
    )";
    $pdo->exec($sql);
    echo "✅ Subcategories table created successfully<br>";

    // Update products table to use category_id instead of category string
    $sql = "ALTER TABLE products ADD COLUMN category_id INT NULL AFTER category";
    try {
        $pdo->exec($sql);
        echo "✅ Added category_id column to products table<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            throw $e;
        }
        echo "ℹ️ category_id column already exists<br>";
    }

    // Add foreign key constraint
    $sql = "ALTER TABLE products ADD CONSTRAINT fk_products_category
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL";
    try {
        $pdo->exec($sql);
        echo "✅ Added foreign key constraint<br>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            throw $e;
        }
        echo "ℹ️ Foreign key constraint already exists<br>";
    }

    // Insert default categories
    $default_categories = [
        [
            'name' => 'Groceries',
            'slug' => 'groceries',
            'description' => 'Food items and grocery products',
            'icon' => 'fas fa-shopping-basket',
            'sort_order' => 1
        ],
        [
            'name' => 'Fresh & Frozen Meats',
            'slug' => 'meats',
            'description' => 'Fresh and frozen meat products',
            'icon' => 'fas fa-drumstick-bite',
            'sort_order' => 2
        ],
        [
            'name' => 'Building Materials',
            'slug' => 'building',
            'description' => 'Construction and building materials',
            'icon' => 'fas fa-hammer',
            'sort_order' => 3
        ]
    ];

    foreach ($default_categories as $category) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug, description, icon, sort_order) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $category['name'],
            $category['slug'],
            $category['description'],
            $category['icon'],
            $category['sort_order']
        ]);
    }
    echo "✅ Default categories inserted<br>";

    // Insert default subcategories
    $default_subcategories = [
        // Groceries subcategories
        ['category_slug' => 'groceries', 'name' => 'Cooking Oils', 'slug' => 'oils', 'sort_order' => 1],
        ['category_slug' => 'groceries', 'name' => 'Rice & Grains', 'slug' => 'rice', 'sort_order' => 2],
        ['category_slug' => 'groceries', 'name' => 'Spices & Condiments', 'slug' => 'spices', 'sort_order' => 3],
        ['category_slug' => 'groceries', 'name' => 'Nuts & Dry Fruits', 'slug' => 'nuts', 'sort_order' => 4],

        // Meats subcategories
        ['category_slug' => 'meats', 'name' => 'Chicken', 'slug' => 'chicken', 'sort_order' => 1],
        ['category_slug' => 'meats', 'name' => 'Fish & Seafood', 'slug' => 'fish', 'sort_order' => 2],
        ['category_slug' => 'meats', 'name' => 'Mutton & Lamb', 'slug' => 'mutton', 'sort_order' => 3],
        ['category_slug' => 'meats', 'name' => 'Beef', 'slug' => 'beef', 'sort_order' => 4],

        // Building Materials subcategories
        ['category_slug' => 'building', 'name' => 'Steel & Iron', 'slug' => 'steel', 'sort_order' => 1],
        ['category_slug' => 'building', 'name' => 'Cement & Concrete', 'slug' => 'cement', 'sort_order' => 2],
        ['category_slug' => 'building', 'name' => 'Pipes & Fittings', 'slug' => 'pipes', 'sort_order' => 3],
        ['category_slug' => 'building', 'name' => 'Paint & Coatings', 'slug' => 'paint', 'sort_order' => 4]
    ];

    foreach ($default_subcategories as $subcategory) {
        // Get category_id
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmt->execute([$subcategory['category_slug']]);
        $category_id = $stmt->fetchColumn();

        if ($category_id) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO subcategories (category_id, name, slug, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $category_id,
                $subcategory['name'],
                $subcategory['slug'],
                $subcategory['sort_order']
            ]);
        }
    }
    echo "✅ Default subcategories inserted<br>";

    // Migrate existing products to use new category structure
    $stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''");
    $existing_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($existing_categories as $old_category) {
        // Find matching new category
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ? OR name LIKE ?");
        $stmt->execute([strtolower($old_category), '%' . $old_category . '%']);
        $new_category_id = $stmt->fetchColumn();

        if ($new_category_id) {
            // Update products to use new category_id
            $stmt = $pdo->prepare("UPDATE products SET category_id = ? WHERE category = ?");
            $stmt->execute([$new_category_id, $old_category]);
            echo "✅ Migrated products from '{$old_category}' to new category structure<br>";
        }
    }

    echo "<h3>🎉 Migration completed successfully!</h3>";
    echo "<p>Categories and subcategories are now properly structured in the database.</p>";
    echo "<p><a href='admin/categories.php'>Go to Admin Panel</a> to manage categories.</p>";

} catch (PDOException $e) {
    echo "<h3>❌ Migration failed:</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
