<?php
require_once 'config/database.php';

// Get product ID from URL
$product_id = $_GET['id'] ?? '';

if (empty($product_id)) {
    header('Location: products.php');
    exit();
}

$pdo = getConnection();

// Get product details with category and subcategory
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, s.name as subcategory_name,
           (SELECT image_path FROM product_images WHERE product_id = p.id AND image_type = 'main' AND is_active = 1 LIMIT 1) as main_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN subcategories s ON p.subcategory_id = s.id
    WHERE p.id = ? AND p.is_active = 1
");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// Get gallery images
$stmt = $pdo->prepare("
    SELECT image_path, sort_order
    FROM product_images
    WHERE product_id = ? AND image_type = 'gallery' AND is_active = 1
    ORDER BY sort_order ASC
");
$stmt->execute([$product_id]);
$gallery_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: products.php');
    exit();
}

// Get related products (same category)
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, s.name as subcategory_name,
           (SELECT image_path FROM product_images WHERE product_id = p.id AND image_type = 'main' AND is_active = 1 LIMIT 1) as main_image
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN subcategories s ON p.subcategory_id = s.id
    WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1
    ORDER BY p.is_hot_sale DESC, p.created_at DESC
    LIMIT 4
");
$stmt->execute([$product['category_id'], $product_id]);
$related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Gulf Global Co</title>
    <link rel="stylesheet" href="css/style.css?v=4.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .product-quality-info {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 40px;
            width: 100%;
            max-width: 100%;
        }
        .product-quality-info h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        .quality-details {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            width: 100%;
        }
        .quality-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            width: 100%;
        }
        .quality-item i {
            font-size: 1.5rem;
            color: #25d366;
            margin-top: 0.25rem;
        }
        .quality-item h4 {
            color: #2c3e50;
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
        }
        .quality-item p {
            color: #666;
            margin: 0;
            line-height: 1.5;
        }
        @media (max-width: 768px) {
            .quality-details {
                gap: 0.8rem;
            }
            .quality-item {
                padding: 0.8rem;
            }
        }
    </style>
    <style>
        .product-details-hero {
            background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
            padding: 120px 0 80px;
            color: white;
            text-align: center;
        }

        .product-details-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .product-details-hero p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .product-details-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        .product-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            margin-bottom: 80px;
        }

        .product-image-section {
            position: relative;
        }

        .product-main-image {
            width: 100%;
            height: 400px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #2c5aa0;
            border: 2px solid #e9ecef;
            overflow: hidden;
            position: relative;
        }

        .product-main-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 15px;
        }

        .product-gallery {
            margin-top: 20px;
        }

        .product-gallery h4 {
            color: #2c5aa0;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        .gallery-thumbnails {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .gallery-thumb {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .gallery-thumb:hover,
        .gallery-thumb.active {
            border-color: #2c5aa0;
            transform: scale(1.05);
        }

        .gallery-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-info-section {
            padding: 20px 0;
        }

        .product-category {
            color: #2c5aa0;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .product-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .product-price {
            font-size: 2rem;
            font-weight: 700;
            color: #22c55e;
            margin-bottom: 30px;
        }

        .product-description {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #666;
            margin-bottom: 30px;
        }

        .product-features {
            margin-bottom: 40px;
        }

        .product-features h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .product-features ul {
            list-style: none;
            padding: 0;
        }

        .product-features li {
            padding: 8px 0;
            color: #666;
            display: flex;
            align-items: center;
        }

        .product-features li i {
            color: #22c55e;
            margin-right: 10px;
            width: 20px;
        }

        .product-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 40px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(44, 90, 160, 0.3);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #2c5aa0;
            padding: 15px 30px;
            border: 2px solid #2c5aa0;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #2c5aa0;
            color: white;
        }

        .product-specifications {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 40px;
        }

        .product-specifications h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }

        .specs-list {
            margin-top: 20px;
        }

        .specifications-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .specifications-list .spec-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            margin-bottom: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .specifications-list .spec-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .spec-label {
            font-weight: 600;
            color: #2c5aa0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .spec-label i {
            color: #2c5aa0;
            width: 20px;
            text-align: center;
        }

        .spec-value {
            color: #333;
            font-weight: 500;
        }

        .related-products {
            margin-top: 80px;
        }

        .related-products h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 40px;
            text-align: center;
        }

        .related-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .related-product-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            text-align: center;
        }

        .related-product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .related-product-image {
            width: 100%;
            height: 150px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #2c5aa0;
            margin-bottom: 15px;
            overflow: hidden;
        }

        .related-product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }

        .related-product-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .related-product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #22c55e;
            margin-bottom: 15px;
        }

        .related-product-link {
            background: #2c5aa0;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .related-product-link:hover {
            background: #1e3d6f;
        }

        @media (max-width: 768px) {
            .product-details-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .product-title {
                font-size: 2rem;
            }

            .product-actions {
                flex-direction: column;
            }

            .specs-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Top Header Bar -->
    <div class="top-header">
        <div class="container">
            <div class="top-header-content">
                <div class="free-shipping">
                    <i class="fas fa-truck"></i>
                    <span>Free shipping on order above ₹500</span>
                </div>
                <div class="top-right">
                    <div class="contact-phone">
                        <i class="fas fa-phone"></i>
                        <span>+91 97893 50475</span>
                    </div>
                    <div class="social-links">
                        <a href="#" target="_blank" title="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="#" target="_blank" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="https://wa.me/919789350475" target="_blank" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <h2>Gulf Global Co</h2>
            </div>
            <div class="hamburger" id="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
            <ul class="nav-menu" id="nav-menu">
                <!-- Mobile Search -->
                <li class="mobile-search">
                    <div class="mobile-search-container">
                        <input type="text" class="mobile-search-input" placeholder="Search products..." id="mobile-search-input">
                        <button class="mobile-search-btn" id="mobile-search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="index.php#hot-sale" class="nav-link">Hot Sale</a>
                </li>
                <li class="nav-item">
                    <a href="index.php" class="nav-link">Home</a>
                </li>
                <li class="nav-item">
                    <a href="index.php#about" class="nav-link">About</a>
                </li>
                <li class="nav-item">
                    <a href="index.php#quality" class="nav-link">Quality</a>
                </li>
                <li class="nav-item">
                    <a href="index.php#contact" class="nav-link">Contact</a>
                </li>
                <?php
                // Generate dynamic navigation from database
                require_once 'includes/navigation.php';
                $pdo = getConnection();

                // Get categories with subcategories
                $stmt = $pdo->query("
                    SELECT c.*,
                           COUNT(DISTINCT s.id) as subcategory_count
                    FROM categories c
                    LEFT JOIN subcategories s ON c.id = s.category_id AND s.is_active = 1
                    WHERE c.is_active = 1
                    GROUP BY c.id
                    ORDER BY c.sort_order, c.name
                ");
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Get subcategories for each category
                foreach ($categories as &$category) {
                    $stmt = $pdo->prepare("
                        SELECT s.*
                        FROM subcategories s
                        WHERE s.category_id = ? AND s.is_active = 1
                        ORDER BY s.sort_order, s.name
                    ");
                    $stmt->execute([$category['id']]);
                    $category['subcategories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }

                // Generate Products dropdown
                if (!empty($categories)): ?>
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle">Products <i class="fas fa-chevron-down"></i></a>
                    <ul class="dropdown-menu">
                        <?php foreach ($categories as $category): ?>
                            <?php if (!empty($category['subcategories'])): ?>
                                <li class="dropdown-submenu">
                                    <a href="products.php#<?php echo $category['slug']; ?>" class="dropdown-link"><?php echo htmlspecialchars($category['name']); ?> <i class="fas fa-chevron-right"></i></a>
                                    <ul class="submenu">
                                        <?php foreach ($category['subcategories'] as $subcategory): ?>
                                            <li><a href="products.php#<?php echo $subcategory['slug']; ?>" class="submenu-link"><?php echo htmlspecialchars($subcategory['name']); ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                            <?php else: ?>
                                <li><a href="products.php#<?php echo $category['slug']; ?>" class="dropdown-link"><?php echo htmlspecialchars($category['name']); ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <li><a href="products.php" class="dropdown-link">View All Products</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            <div class="nav-actions">
                <a href="index.php#hot-sale" class="nav-link hot-sale-link" title="Hot Sale">
                    <i class="fas fa-fire"></i>
                    <span>Hot Sale</span>
                </a>
                <div class="search-container">
                    <input type="text" id="product-search" placeholder="Search products..." class="search-input">
                    <button class="search-btn" id="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <a href="#" class="nav-link cart-link" id="cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cart-count">0</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <div class="breadcrumb-content">
            <a href="index.php">Home</a> /
            <a href="products.php">Products</a> /
            <?php echo htmlspecialchars($product['name']); ?>
        </div>
    </div>

    <!-- Product Details Hero -->
    <section class="product-details-hero">
        <div class="container">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <p>Premium Quality Product</p>
        </div>
    </section>

    <!-- Product Details -->
    <section class="product-details-container">
        <div class="product-details-grid">
            <div class="product-image-section">
                <div class="product-main-image">
                    <?php if (!empty($product['main_image'])): ?>
                        <img src="<?php echo htmlspecialchars($product['main_image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" id="main-product-image" onerror="this.src='images/default-product.svg';">
                    <?php else: ?>
                        <img src="images/default-product.svg" alt="<?php echo htmlspecialchars($product['name']); ?>" id="main-product-image">
                    <?php endif; ?>
                </div>

                <?php if (!empty($gallery_images)): ?>
                <div class="product-gallery">
                    <h4>Product Gallery</h4>
                    <div class="gallery-thumbnails">
                        <?php foreach ($gallery_images as $index => $gallery_image): ?>
                            <div class="gallery-thumb <?php echo $index === 0 ? 'active' : ''; ?>" onclick="changeMainImage('<?php echo htmlspecialchars($gallery_image['image_path']); ?>', this)">
                                <img src="<?php echo htmlspecialchars($gallery_image['image_path']); ?>" alt="Gallery Image <?php echo $index + 1; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="product-info-section">
                <div class="product-category">
                    <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                    <?php if (!empty($product['subcategory_name'])): ?>
                        / <?php echo htmlspecialchars($product['subcategory_name']); ?>
                    <?php endif; ?>
                </div>

                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

                <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>

                <div class="product-description">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>

                <div class="product-features">
                    <h3>Key Features</h3>
                    <ul>
                        <li><i class="fas fa-check"></i> Premium Quality Materials</li>
                        <li><i class="fas fa-check"></i> International Standards</li>
                        <li><i class="fas fa-check"></i> Quality Assured</li>
                        <li><i class="fas fa-check"></i> Fast Delivery</li>
                        <li><i class="fas fa-check"></i> Competitive Pricing</li>
                    </ul>
                </div>

                <div class="product-actions">
                    <button class="btn-primary add-to-cart" data-product="<?php echo htmlspecialchars($product['name']); ?>" data-price="<?php echo $product['price']; ?>">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                    <button onclick="window.location.href='place-order.php?id=<?php echo $product['id']; ?>'" class="btn btn-whatsapp">
                        <i class="fab fa-whatsapp"></i> Buy Now
                    </button>
                </div>
            </div>
        </div>

        <!-- Product Specifications -->
        <div class="product-specifications">
            <h3>Product Specifications</h3>
            <div class="specs-list">
                <ul class="specifications-list">
                    <li class="spec-item">
                        <span class="spec-label"><i class="fas fa-tag"></i> Product Name</span>
                        <span class="spec-value"><?php echo htmlspecialchars($product['name']); ?></span>
                    </li>
                    <li class="spec-item">
                        <span class="spec-label"><i class="fas fa-folder"></i> Category</span>
                        <span class="spec-value"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                    </li>
                    <?php if (!empty($product['subcategory_name'])): ?>
                    <li class="spec-item">
                        <span class="spec-label"><i class="fas fa-folder-open"></i> Subcategory</span>
                        <span class="spec-value"><?php echo htmlspecialchars($product['subcategory_name']); ?></span>
                    </li>
                    <?php endif; ?>
                    <li class="spec-item">
                        <span class="spec-label"><i class="fas fa-rupee-sign"></i> Price</span>
                        <span class="spec-value">₹<?php echo number_format($product['price'], 2); ?></span>
                    </li>
                    <li class="spec-item">
                        <span class="spec-label"><i class="fas fa-info-circle"></i> Status</span>
                        <span class="spec-value"><?php echo $product['is_hot_sale'] ? 'Hot Sale' : 'Available'; ?></span>
                    </li>
                    <li class="spec-item">
                        <span class="spec-label"><i class="fas fa-award"></i> Quality Grade</span>
                        <span class="spec-value">Premium Grade</span>
                    </li>
                    <li class="spec-item">
                        <span class="spec-label"><i class="fas fa-check-circle"></i> Standards</span>
                        <span class="spec-value">International Standards</span>
                    </li>
                    <li class="spec-item">
                        <span class="spec-label"><i class="fas fa-shipping-fast"></i> Delivery</span>
                        <span class="spec-value">Fast & Reliable</span>
                    </li>
                    <li class="spec-item">
                        <span class="spec-label"><i class="fas fa-shield-alt"></i> Quality Assurance</span>
                        <span class="spec-value">100% Quality Guaranteed</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Quality Information -->
        <div class="product-quality-info">
            <h3><i class="fas fa-award"></i> Quality Information</h3>
            <div class="quality-details">
                <div class="quality-item">
                    <i class="fas fa-certificate"></i>
                    <div>
                        <h4>ISO 9001:2015 Certified</h4>
                        <p>Quality Management System ensuring consistent product quality and customer satisfaction.</p>
                    </div>
                </div>
                <div class="quality-item">
                    <i class="fas fa-shield-alt"></i>
                    <div>
                        <h4>Rigorous Testing</h4>
                        <p>Every product undergoes comprehensive quality testing before reaching our customers.</p>
                    </div>
                </div>
                <div class="quality-item">
                    <i class="fas fa-microscope"></i>
                    <div>
                        <h4>Laboratory Testing</h4>
                        <p>Advanced laboratory testing for safety, purity, and content verification.</p>
                    </div>
                </div>
                <div class="quality-item">
                    <i class="fas fa-truck"></i>
                    <div>
                        <h4>Cold Chain Management</h4>
                        <p>Temperature-controlled storage and transportation for perishable goods.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
        <div class="related-products">
            <h2>Related Products</h2>
            <div class="related-products-grid">
                <?php foreach ($related_products as $related): ?>
                    <div class="related-product-card">
                        <div class="related-product-image">
                            <?php if (!empty($related['main_image'])): ?>
                                <img src="<?php echo htmlspecialchars($related['main_image']); ?>" alt="<?php echo htmlspecialchars($related['name']); ?>" onerror="this.src='images/default-product.svg';">
                            <?php else: ?>
                                <img src="images/default-product.svg" alt="<?php echo htmlspecialchars($related['name']); ?>">
                            <?php endif; ?>
                        </div>
                        <h3 class="related-product-title"><?php echo htmlspecialchars($related['name']); ?></h3>
                        <div class="related-product-price">₹<?php echo number_format($related['price'], 2); ?></div>
                        <a href="product-details.php?id=<?php echo $related['id']; ?>" class="related-product-link">View Details</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Gulf Global Co</h3>
                    <p>Your trusted partner for global imports and trade solutions.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="index.php#about">About</a></li>
                        <li><a href="index.php#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <p><i class="fas fa-phone"></i> +91 97893 50475</p>
                    <p><i class="fas fa-envelope"></i> info@gulfglobal.co</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Gulf Global Co. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Shopping Cart Modal -->
    <div id="cart-modal" class="cart-modal">
        <div class="cart-modal-content">
            <div class="cart-header">
                <h3>Shopping Cart</h3>
                <span class="close-cart">&times;</span>
            </div>
            <div class="cart-items" id="cart-items">
                <!-- Cart items will be added here dynamically -->
            </div>
            <div class="cart-footer">
                <div class="cart-total">
                    <strong>Total: ₹<span id="cart-total">0</span></strong>
                </div>
                <button class="btn btn-primary checkout-whatsapp" id="checkout-whatsapp">
                    <i class="fas fa-shopping-cart"></i> Place Order
                </button>
            </div>
        </div>
    </div>

    <script src="js/script.js?v=2.6"></script>
    <script src="js/whatsapp.js?v=1.9"></script>
    <script>
        function changeMainImage(imagePath, thumbElement) {
            // Update main image
            const mainImage = document.getElementById('main-product-image');
            if (mainImage) {
                mainImage.src = imagePath;
            }

            // Update active thumbnail
            document.querySelectorAll('.gallery-thumb').forEach(thumb => {
                thumb.classList.remove('active');
            });
            thumbElement.classList.add('active');
        }
    </script>
</body>
</html>
