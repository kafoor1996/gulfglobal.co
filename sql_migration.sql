-- Gulf Global Co - Database Migration SQL
-- Run these commands in your MySQL database

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'fas fa-tag',
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create subcategories table
CREATE TABLE IF NOT EXISTS subcategories (
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
);

-- Add category_id column to products table
ALTER TABLE products ADD COLUMN category_id INT NULL AFTER category;

-- Add subcategory_id column to products table
ALTER TABLE products ADD COLUMN subcategory_id INT NULL AFTER category_id;

-- Add foreign key constraints
ALTER TABLE products ADD CONSTRAINT fk_products_category
FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;

ALTER TABLE products ADD CONSTRAINT fk_products_subcategory
FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE SET NULL;

-- Insert default categories
INSERT INTO categories (name, slug, description, icon, sort_order) VALUES
('Groceries', 'groceries', 'Food items and grocery products', 'fas fa-shopping-basket', 1),
('Fresh & Frozen Meats', 'meats', 'Fresh and frozen meat products', 'fas fa-drumstick-bite', 2),
('Building Materials', 'building', 'Construction and building materials', 'fas fa-hammer', 3);

-- Insert default subcategories
-- Groceries subcategories
INSERT INTO subcategories (category_id, name, slug, sort_order) VALUES
((SELECT id FROM categories WHERE slug = 'groceries'), 'Cooking Oils', 'oils', 1),
((SELECT id FROM categories WHERE slug = 'groceries'), 'Rice & Grains', 'rice', 2),
((SELECT id FROM categories WHERE slug = 'groceries'), 'Spices & Condiments', 'spices', 3),
((SELECT id FROM categories WHERE slug = 'groceries'), 'Nuts & Dry Fruits', 'nuts', 4);

-- Meats subcategories
INSERT INTO subcategories (category_id, name, slug, sort_order) VALUES
((SELECT id FROM categories WHERE slug = 'meats'), 'Chicken', 'chicken', 1),
((SELECT id FROM categories WHERE slug = 'meats'), 'Fish & Seafood', 'fish', 2),
((SELECT id FROM categories WHERE slug = 'meats'), 'Mutton & Lamb', 'mutton', 3),
((SELECT id FROM categories WHERE slug = 'meats'), 'Beef', 'beef', 4);

-- Building Materials subcategories
INSERT INTO subcategories (category_id, name, slug, sort_order) VALUES
((SELECT id FROM categories WHERE slug = 'building'), 'Steel & Iron', 'steel', 1),
((SELECT id FROM categories WHERE slug = 'building'), 'Cement & Concrete', 'cement', 2),
((SELECT id FROM categories WHERE slug = 'building'), 'Pipes & Fittings', 'pipes', 3),
((SELECT id FROM categories WHERE slug = 'building'), 'Paint & Coatings', 'paint', 4);

-- Migrate existing products to use new category structure
-- Update products with category_id based on existing category field
UPDATE products p
JOIN categories c ON LOWER(p.category) = c.slug
SET p.category_id = c.id
WHERE p.category IS NOT NULL AND p.category != '';

-- Alternative migration for products that don't match existing categories
-- This will create new categories for any existing product categories
INSERT IGNORE INTO categories (name, slug, description, icon, sort_order)
SELECT DISTINCT
    UPPER(LEFT(category, 1)) + LOWER(SUBSTRING(category, 2)) as name,
    LOWER(REPLACE(category, ' ', '-')) as slug,
    CONCAT('Products in ', category, ' category') as description,
    'fas fa-tag' as icon,
    999 as sort_order
FROM products
WHERE category IS NOT NULL
AND category != ''
AND category NOT IN (SELECT slug FROM categories);

-- Update remaining products with newly created categories
UPDATE products p
JOIN categories c ON LOWER(REPLACE(p.category, ' ', '-')) = c.slug
SET p.category_id = c.id
WHERE p.category_id IS NULL
AND p.category IS NOT NULL
AND p.category != '';

-- Show migration results
SELECT 'Migration completed successfully!' as status;
SELECT 'Categories created:' as info;
SELECT id, name, slug FROM categories ORDER BY sort_order;

SELECT 'Subcategories created:' as info;
SELECT s.id, s.name, s.slug, c.name as category_name
FROM subcategories s
JOIN categories c ON s.category_id = c.id
ORDER BY c.sort_order, s.sort_order;

SELECT 'Products migrated:' as info;
SELECT COUNT(*) as total_products,
       COUNT(category_id) as products_with_categories,
       COUNT(*) - COUNT(category_id) as products_without_categories
FROM products;
