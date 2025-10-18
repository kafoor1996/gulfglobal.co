<?php
require_once 'config/database.php';

// Get products by category/subcategory/search
$pdo = getConnection();
$category_filter = $_GET['category'] ?? '';
$subcategory_filter = $_GET['subcategory'] ?? '';
$search_term = $_GET['search'] ?? '';

$where_clause = "p.is_active = 1";
$params = [];

if (!empty($category_filter)) {
    $where_clause .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($subcategory_filter)) {
    $where_clause .= " AND p.subcategory_id = ?";
    $params[] = $subcategory_filter;
}

if (!empty($search_term)) {
    $where_clause .= " AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ? OR s.name LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql = "SELECT p.*, c.name as category_name, s.name as subcategory_name,
               (SELECT image_path FROM product_images WHERE product_id = p.id AND image_type = 'main' AND is_active = 1 LIMIT 1) as main_image
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN subcategories s ON p.subcategory_id = s.id
        WHERE $where_clause ORDER BY p.is_hot_sale DESC, p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order, name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get subcategories for filter (if category is selected)
$subcategories = [];
if (!empty($category_filter)) {
    $stmt = $pdo->prepare("SELECT id, name FROM subcategories WHERE category_id = ? AND is_active = 1 ORDER BY sort_order, name");
    $stmt->execute([$category_filter]);
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Group products by category
$products_by_category = [];
foreach ($products as $product) {
    $category_name = $product['category_name'] ?? 'Uncategorized';
    $products_by_category[$category_name][] = $product;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Gulf Global Co</title>
    <link rel="stylesheet" href="css/style.css?v=3.1">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .product-title-link {
            color: #2c3e50;
            text-decoration: none;
            transition: color 0.3s;
        }
        .product-title-link:hover {
            color: #25d366;
            text-decoration: none;
        }

        .product-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
        }
    </style>
    <style>
        .products-hero {
            background: linear-gradient(135deg, #2c5aa0 0%, #1e3d6f 100%);
            padding: 120px 0 80px;
            color: white;
            text-align: center;
        }

        .products-hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .products-hero p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        .product-categories {
            padding: 80px 0;
        }

        .category-section {
            margin-bottom: 4rem;
        }

        .category-title {
            font-size: 2.5rem;
            color: #2c5aa0;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 700;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .breadcrumb {
            background: #f8f9fa;
            padding: 1rem 0;
            margin-bottom: 0;
        }

        .breadcrumb-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .breadcrumb a {
            color: #2c5aa0;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .category-filter {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #2c5aa0;
            background: white;
            color: #2c5aa0;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: #2c5aa0;
            color: white;
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
            <a href="products.php">Products</a>
            <?php if (!empty($category_filter)): ?>
                <?php
                $category_name = $categories[array_search($category_filter, array_column($categories, 'id'))]['name'] ?? 'Category';
                ?>
                / <?php echo htmlspecialchars($category_name); ?>
            <?php endif; ?>
            <?php if (!empty($subcategory_filter)): ?>
                <?php
                $subcategory_name = $subcategories[0]['name'] ?? 'Subcategory';
                ?>
                / <?php echo htmlspecialchars($subcategory_name); ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Products Hero -->
    <section class="products-hero">
        <div class="container">
            <?php if (!empty($search_term)): ?>
                <h1>Search Results for "<?php echo htmlspecialchars($search_term); ?>"</h1>
                <p>Found <?php echo count($products); ?> product(s) matching your search</p>
            <?php elseif (!empty($category_filter) || !empty($subcategory_filter)): ?>
                <h1>
                    <?php
                    if (!empty($subcategory_filter)) {
                        $subcategory_name = $subcategories[0]['name'] ?? 'Selected Subcategory';
                        echo htmlspecialchars($subcategory_name);
                    } elseif (!empty($category_filter)) {
                        $category_name = $categories[array_search($category_filter, array_column($categories, 'id'))]['name'] ?? 'Selected Category';
                        echo htmlspecialchars($category_name);
                    }
                    ?>
                </h1>
                <p>Products in this category</p>
            <?php else: ?>
                <h1>Our Products</h1>
                <p>Discover our comprehensive range of premium products designed to meet your industrial and commercial needs</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Product Categories -->
    <section class="product-categories">
        <div class="container">
            <!-- Category Filter -->
            <div class="category-filter">
                <div class="filter-header">
                    <h3 style="margin-bottom: 15px; color: #2c5aa0;">Filter by Category</h3>
                    <!-- Mobile/Tablet Three-Dot Menu -->
                    <div class="mobile-filter-menu">
                        <button class="filter-menu-toggle" id="filter-menu-toggle">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="filter-dropdown" id="filter-dropdown">
                            <div class="filter-dropdown-content">
                                <a href="products.php" class="filter-dropdown-btn <?php echo empty($category_filter) ? 'active' : ''; ?>">
                                    <i class="fas fa-th"></i> All Products
                                </a>
                                <?php foreach ($categories as $cat): ?>
                                    <a href="products.php?category=<?php echo $cat['id']; ?>"
                                       class="filter-dropdown-btn <?php echo $category_filter == $cat['id'] ? 'active' : ''; ?>">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($cat['name']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="filter-buttons">
                    <a href="products.php" class="filter-btn <?php echo empty($category_filter) ? 'active' : ''; ?>">
                        All Products
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="products.php?category=<?php echo $cat['id']; ?>"
                           class="filter-btn <?php echo $category_filter == $cat['id'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (empty($products)): ?>
                <!-- No products found -->
                <div class="no-products">
                    <div class="no-products-content">
                        <i class="fas fa-search"></i>
                        <h3>No Products Found</h3>
                        <p>Sorry, there are no products available in this category at the moment.</p>
                        <a href="products.php" class="btn btn-primary">View All Products</a>
                    </div>
                </div>
            <?php elseif (empty($category_filter)): ?>
                <!-- Show all categories -->
                <?php foreach ($products_by_category as $cat_name => $cat_products): ?>
                    <div id="<?php echo strtolower(str_replace(' ', '-', $cat_name)); ?>" class="category-section">
                        <h2 class="category-title"><?php echo htmlspecialchars($cat_name); ?></h2>
                        <div class="products-grid">
                            <?php foreach ($cat_products as $product): ?>
                                <div class="product-card">
                                <div class="product-image-container">
                                    <div class="product-image">
                                        <?php if (!empty($product['main_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['main_image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img" onerror="this.src='images/default-product.svg';">
                                        <?php else: ?>
                                            <img src="images/default-product.svg" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img">
                                        <?php endif; ?>
                                    </div>
                                </div>
                                    <?php if ($product['is_hot_sale']): ?>
                                        <div class="product-badge hot-sale">Hot Sale</div>
                                    <?php else: ?>
                                        <div class="product-badge <?php echo strtolower(str_replace(' ', '-', $product['category_name'] ?? 'default')); ?>"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></div>
                                    <?php endif; ?>
                                    <h3><a href="product-details.php?id=<?php echo $product['id']; ?>" class="product-title-link"><?php echo htmlspecialchars($product['name']); ?></a></h3>
                                    <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                    <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                                    <div class="product-actions">
                                        <button class="btn btn-secondary add-to-cart" data-product="<?php echo htmlspecialchars($product['name']); ?>" data-price="<?php echo $product['price']; ?>">Add to Cart</button>
                                        <button onclick="window.location.href='place-order.php?id=<?php echo $product['id']; ?>'" class="btn btn-whatsapp">
                                            <i class="fab fa-whatsapp"></i> Buy Now
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Show specific category -->
                <div class="category-section">
                    <h2 class="category-title"><?php echo htmlspecialchars($products[0]['category_name'] ?? 'Products'); ?></h2>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-image-container">
                                    <div class="product-image">
                                        <?php if (!empty($product['main_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($product['main_image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img" onerror="this.src='images/default-product.svg';">
                                        <?php else: ?>
                                            <img src="images/default-product.svg" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img">
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($product['is_hot_sale']): ?>
                                    <div class="product-badge hot-sale">Hot Sale</div>
                                <?php else: ?>
                                    <div class="product-badge <?php echo strtolower(str_replace(' ', '-', $product['category_name'] ?? 'default')); ?>"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></div>
                                <?php endif; ?>
                                <h3><a href="product-details.php?id=<?php echo $product['id']; ?>" class="product-title-link"><?php echo htmlspecialchars($product['name']); ?></a></h3>
                                <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                                <div class="product-actions">
                                    <button class="btn btn-secondary add-to-cart" data-product="<?php echo htmlspecialchars($product['name']); ?>" data-price="<?php echo $product['price']; ?>">Add to Cart</button>
                                    <a href="place-order.php?id=<?php echo $product['id']; ?>" class="btn btn-whatsapp">
                                        <i class="fab fa-whatsapp"></i> Buy Now
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="quality">
        <div class="container">
            <div class="quality-content">
                <div class="quality-text">
                    <h2>Need Custom Solutions?</h2>
                    <p>Our team of experts can help you design and implement custom solutions tailored to your specific requirements. Contact us today to discuss your needs.</p>
                    <ul class="quality-features">
                        <li><i class="fas fa-check"></i> Custom Design & Engineering</li>
                        <li><i class="fas fa-check"></i> Technical Consultation</li>
                        <li><i class="fas fa-check"></i> Installation & Training</li>
                        <li><i class="fas fa-check"></i> Ongoing Support</li>
                    </ul>
                </div>
                <div class="quality-certificates">
                    <div class="certificate">
                        <i class="fas fa-phone"></i>
                        <h4>Call Us</h4>
                        <p>+91 98765 43210</p>
                    </div>
                    <div class="certificate">
                        <i class="fas fa-envelope"></i>
                        <h4>Email Us</h4>
                        <p>info@gulfglobal.co</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Gulf Global Co</h3>
                    <p>Leading the way in excellence and innovation across the Gulf region.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>Our Products</h4>
                    <ul>
                        <li><a href="products.php?category=groceries">Groceries</a></li>
                        <li><a href="products.php?category=meats">Fresh & Frozen Meats</a></li>
                        <li><a href="products.php?category=building">Building Materials</a></li>
                        <li><a href="products.php">All Products</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Our Services</h4>
                    <ul>
                        <li><a href="index.php#quality">Quality Assurance</a></li>
                        <li><a href="index.php#contact">Global Import Solutions</a></li>
                        <li><a href="index.php#contact">Trade Consultancy</a></li>
                        <li><a href="index.php#contact">Supply Chain Management</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <p><i class="fas fa-map-marker-alt"></i> Chennai, India</p>
                    <p><i class="fas fa-phone"></i> +91 98765 43210</p>
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

    <!-- Quality Popup Modal -->
    <div id="quality-modal" class="quality-modal">
        <div class="quality-modal-content">
            <div class="quality-header">
                <h3><i class="fas fa-award"></i> Quality Standards</h3>
                <span class="close-quality">&times;</span>
            </div>
            <div class="quality-body">
                <div class="quality-intro">
                    <p>At Gulf Global Co, we maintain the highest quality standards across all our products and services.</p>
                </div>
                <div class="quality-standards">
                    <div class="standard-item">
                        <div class="standard-icon">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <div class="standard-content">
                            <h4>ISO 9001:2015 Certified</h4>
                            <p>Quality Management System ensuring consistent product quality and customer satisfaction.</p>
                        </div>
                    </div>
                    <div class="standard-item">
                        <div class="standard-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="standard-content">
                            <h4>Rigorous Testing</h4>
                            <p>Every product undergoes comprehensive quality testing before reaching our customers.</p>
                        </div>
                    </div>
                    <div class="standard-item">
                        <div class="standard-icon">
                            <i class="fas fa-microscope"></i>
                        </div>
                        <div class="standard-content">
                            <h4>Laboratory Testing</h4>
                            <p>Advanced laboratory testing for food safety, purity, and nutritional content verification.</p>
                        </div>
                    </div>
                    <div class="standard-item">
                        <div class="standard-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <div class="standard-content">
                            <h4>Fresh & Organic</h4>
                            <p>Fresh products sourced daily with organic certification where applicable.</p>
                        </div>
                    </div>
                    <div class="standard-item">
                        <div class="standard-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="standard-content">
                            <h4>Cold Chain Management</h4>
                            <p>Temperature-controlled storage and transportation for perishable goods.</p>
                        </div>
                    </div>
                    <div class="standard-item">
                        <div class="standard-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div class="standard-content">
                            <h4>Quality Guarantee</h4>
                            <p>100% satisfaction guarantee with money-back policy for quality issues.</p>
                        </div>
                    </div>
                </div>
                <div class="quality-certificates">
                    <h4>Our Certifications</h4>
                    <div class="certificate-grid">
                        <div class="certificate-item">
                            <i class="fas fa-award"></i>
                            <span>ISO 9001:2015</span>
                        </div>
                        <div class="certificate-item">
                            <i class="fas fa-globe"></i>
                            <span>ISO 14001:2015</span>
                        </div>
                        <div class="certificate-item">
                            <i class="fas fa-utensils"></i>
                            <span>FSSAI Certified</span>
                        </div>
                        <div class="certificate-item">
                            <i class="fas fa-seedling"></i>
                            <span>Organic Certified</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js?v=2.4"></script>
    <script src="js/whatsapp.js?v=1.7"></script>
</body>
</html>
