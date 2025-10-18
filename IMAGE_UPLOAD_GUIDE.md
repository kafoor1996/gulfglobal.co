# Product Image Upload Guide

## Overview
This guide explains the new product image upload functionality implemented for the Gulf Global Co website.

## Features Implemented

### 1. Admin Panel Image Upload
- **Main Product Image**: Single primary image for each product
- **Gallery Images**: Multiple additional images for product gallery
- **File Validation**: Supports JPEG, PNG, GIF, and WebP formats
- **Size Limit**: Maximum 5MB per image
- **Automatic Resizing**: Images are optimized for web display

### 2. Frontend Image Display
- **Product Listings**: Shows main product images in product cards
- **Product Details**: Displays main image with interactive gallery
- **Related Products**: Shows images for related product suggestions
- **Fallback Icons**: Displays category-based icons when no image is available

### 3. Database Structure
- **products table**: Added `main_image` column
- **product_images table**: New table for gallery images
- **Image Types**: 'main', 'gallery', 'thumbnail'
- **Sort Order**: Images can be ordered in gallery

## How to Use

### For Administrators

1. **Adding Product Images**:
   - Go to Admin Panel → Products
   - Click "Add Product" or edit existing product
   - Upload main product image (recommended: 800x600px)
   - Upload multiple gallery images
   - Save the product

2. **Image Requirements**:
   - Format: JPEG, PNG, GIF, WebP
   - Size: Maximum 5MB per image
   - Main image: Recommended 800x600px
   - Gallery images: Any size (will be automatically optimized)

### For Users

1. **Viewing Product Images**:
   - Product listings show main images
   - Product details page shows main image with gallery
   - Click gallery thumbnails to change main image view
   - Related products also display images

## Technical Implementation

### File Structure
```
httpdocs/
├── images/
│   └── products/          # Product images storage
├── admin/
│   └── products.php      # Admin image upload
├── products.php          # Frontend product listing
└── product-details.php   # Product details with gallery
```

### Database Tables
```sql
-- Products table (updated)
ALTER TABLE products ADD COLUMN main_image VARCHAR(255);

-- Product images table (new)
CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    image_type ENUM('main', 'gallery', 'thumbnail') DEFAULT 'gallery',
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

## Image Upload Process

1. **File Validation**: Checks file type and size
2. **File Naming**: Generates unique filenames with timestamps
3. **Storage**: Saves to `images/products/` directory
4. **Database**: Records image metadata in database
5. **Display**: Frontend queries and displays images

## Security Features

- File type validation
- File size limits
- Secure file naming
- Database sanitization
- Path traversal protection

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Mobile responsive design
- Touch-friendly gallery navigation
- Progressive image loading

## Troubleshooting

### Common Issues

1. **Images not displaying**:
   - Check file permissions on `images/products/` directory
   - Verify image paths in database
   - Clear browser cache

2. **Upload failures**:
   - Check file size (max 5MB)
   - Verify file format (JPEG, PNG, GIF, WebP only)
   - Check server upload limits

3. **Gallery not working**:
   - Ensure JavaScript is enabled
   - Check browser console for errors
   - Verify image paths are correct

### File Permissions
```bash
chmod 755 images/products/
chmod 644 images/products/*
```

## Future Enhancements

- Image compression and optimization
- Multiple image formats support
- Image cropping and editing tools
- Bulk image upload
- Image CDN integration
- Advanced gallery features (zoom, lightbox)

## Support

For technical support or questions about the image upload functionality, contact the development team.
