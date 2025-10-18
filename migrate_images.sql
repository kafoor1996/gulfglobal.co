-- Add image columns to products table
ALTER TABLE products ADD COLUMN main_image VARCHAR(255) AFTER description;
ALTER TABLE products ADD COLUMN image_gallery TEXT AFTER main_image;

-- Create product_images table for multiple images per product
CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    image_type ENUM('main', 'gallery', 'thumbnail') DEFAULT 'gallery',
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create index for better performance
CREATE INDEX idx_product_images_product_id ON product_images(product_id);
CREATE INDEX idx_product_images_type ON product_images(image_type);
