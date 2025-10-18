<?php
require_once 'config/database.php';

// Get products from database
$pdo = getConnection();
$stmt = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC LIMIT 6");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get hot sale products
$stmt = $pdo->query("SELECT * FROM products WHERE is_hot_sale = 1 AND is_active = 1 ORDER BY created_at DESC LIMIT 3");
$hot_sale_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gulf Global Co - Your Trusted Partner for Global Imports & Trade</title>
    <link rel="stylesheet" href="css/style.css">
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
                <li class="nav-item">
                    <a href="index.php#home" class="nav-link">Home</a>
                </li>
                <li class="nav-item">
                    <a href="index.php#about" class="nav-link">About</a>
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
                                    <a href="products.php?category=<?php echo $category['id']; ?>" class="dropdown-link"><?php echo htmlspecialchars($category['name']); ?> <i class="fas fa-chevron-right"></i></a>
                                    <ul class="submenu">
                                        <?php foreach ($category['subcategories'] as $subcategory): ?>
                                            <li><a href="products.php?category=<?php echo $category['id']; ?>&subcategory=<?php echo $subcategory['id']; ?>" class="submenu-link"><?php echo htmlspecialchars($subcategory['name']); ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                            <?php else: ?>
                                <li><a href="products.php?category=<?php echo $category['id']; ?>" class="dropdown-link"><?php echo htmlspecialchars($category['name']); ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <li><a href="products.php" class="dropdown-link">View All Products</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="index.php#quality" class="nav-link">Quality</a>
                </li>
                <li class="nav-item">
                    <a href="index.php#contact" class="nav-link">Contact</a>
                </li>
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

    <!-- Hero Section -->
    <section id="home" class="hero">

        <div class="hero-content">
            <h1 class="hero-title">EMPOWERING TRADE. DELIVERING EXCELLENCE.</h1>
            <p class="hero-subtitle">Your trusted partner for global imports, trade, and supply solutions - connecting businesses across India and worldwide</p>
            <div class="hero-buttons">
                <a href="#products" class="btn btn-primary">Shop Now</a>
                <a href="#contact" class="btn btn-secondary">Contact Us</a>
            </div>
        </div>
        <div class="hero-image">
            <div class="hero-banner">
                <div class="banner-overlay">
                    <div class="banner-content">
                        <h3>Premium Quality</h3>
                        <p>Fresh & Authentic Products</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="section-header">
                <h2>About Gulf Global Co</h2>
                <p>Your trusted partner in excellence and innovation</p>
            </div>
            <div class="about-content">
                <div class="about-text">
                    <h3>About Gulf Global Co</h3>
                    <p>Gulf Global Co is a global import and trading company that delivers quality groceries, meats, and building materials across India and internationally. We connect trusted suppliers with growing markets through reliable logistics and transparent trade practices.</p>
                    <p>We specialize in premium groceries, fresh & frozen meats, and durable building materials, ensuring quality and reliability in every delivery. Our commitment to excellence has made us a trusted partner for businesses and consumers worldwide.</p>
                    <div class="about-features">
                        <div class="feature-item">
                            <i class="fas fa-shipping-fast"></i>
                            <span>Fast & Reliable Delivery</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>Quality Guaranteed</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-handshake"></i>
                            <span>Trusted Partnership</span>
                        </div>
                    </div>
                </div>
                <div class="about-stats">
                    <div class="stat-item">
                        <h4>Fast</h4>
                        <p>Delivery</p>
                    </div>
                    <div class="stat-item">
                        <h4>Secured</h4>
                        <p>Payment</p>
                    </div>
                    <div class="stat-item">
                        <h4>Money-Back</h4>
                        <p>Guarantee</p>
                    </div>
                    <div class="stat-item">
                        <h4>24/7</h4>
                        <p>Support</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Hot Sale Section -->
    <section id="hot-sale" class="products">
        <div class="container">
            <div class="section-header">
                <h2><i class="fas fa-fire"></i> Hot Sale Products</h2>
                <p>Limited time offers on our best products</p>
            </div>
            <div class="products-grid">
                <?php foreach ($hot_sale_products as $product): ?>
                    <div class="product-card">
                        <div class="product-image-container">
                            <div class="product-image">
                                <div class="product-img <?php echo strtolower(str_replace(' ', '-', $product['category'])); ?>"></div>
                            </div>
                        </div>
                        <div class="product-badge hot-sale">Hot Sale</div>
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
    </section>

    <!-- Products Section -->
    <section id="products" class="products">
        <div class="container">
            <div class="section-header">
                <h2>Our Products</h2>
                <p>Discover our comprehensive range of premium products</p>
            </div>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image-container">
                            <div class="product-image">
                                <div class="product-img <?php echo strtolower(str_replace(' ', '-', $product['category'])); ?>"></div>
                            </div>
                        </div>
                        <?php if ($product['is_hot_sale']): ?>
                            <div class="product-badge hot-sale">Hot Sale</div>
                        <?php else: ?>
                            <div class="product-badge <?php echo $product['category']; ?>"><?php echo ucfirst($product['category']); ?></div>
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
    </section>

    <!-- Quality Section -->
    <section id="quality" class="quality">
        <div class="container">
            <div class="quality-content">
                <div class="quality-text">
                    <h2>Quality Standards</h2>
                    <p>We are committed to maintaining the highest quality standards in all our products and services. Our quality assurance processes ensure that every product meets international standards and exceeds customer expectations.</p>
                    <ul class="quality-features">
                        <li><i class="fas fa-check"></i> ISO Certified Products</li>
                        <li><i class="fas fa-check"></i> Rigorous Testing Procedures</li>
                        <li><i class="fas fa-check"></i> Continuous Quality Monitoring</li>
                        <li><i class="fas fa-check"></i> Customer Satisfaction Guarantee</li>
                    </ul>
                </div>
                <div class="quality-certificates">
                    <div class="certificate">
                        <i class="fas fa-certificate"></i>
                        <h4>ISO 9001:2015</h4>
                        <p>Quality Management</p>
                    </div>
                    <div class="certificate">
                        <i class="fas fa-award"></i>
                        <h4>ISO 14001:2015</h4>
                        <p>Environmental Management</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <div class="section-header">
                <h2>Get In Touch</h2>
                <p>We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
            </div>
            <div class="contact-content">
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h4>Address</h4>
                            <p>Plot No. 142, Global Trade Complex<br>Industrial Avenue, Guindy<br>Chennai - 600032, India</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h4>Phone & WhatsApp</h4>
                            <p>+91 97893 50475<br>+91 91234 56789</p>
                            <a href="#" class="whatsapp-btn" data-message="Hello, I am interested in your products and services.">
                                <i class="fab fa-whatsapp"></i> Chat on WhatsApp
                            </a>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>Email</h4>
                            <p>info@gulfglobal.co<br>sales@gulfglobal.co</p>
                        </div>
                    </div>
                </div>
                <form class="contact-form">
                    <div class="form-group">
                        <input type="text" placeholder="Your Name" required>
                    </div>
                    <div class="form-group">
                        <input type="email" placeholder="Your Email" required>
                    </div>
                    <div class="form-group">
                        <input type="text" placeholder="Subject" required>
                    </div>
                    <div class="form-group">
                        <textarea placeholder="Your Message" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-whatsapp" data-message="Hello, I would like to get in touch regarding your products and services.">
                        <i class="fab fa-whatsapp"></i> Send via WhatsApp
                    </button>
                </form>
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
                        <li><a href="products.php#groceries">Groceries</a></li>
                        <li><a href="products.php#meats">Fresh & Frozen Meats</a></li>
                        <li><a href="products.php#building">Building Materials</a></li>
                        <li><a href="products.php">All Products</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Our Services</h4>
                    <ul>
                        <li><a href="#quality">Quality Assurance</a></li>
                        <li><a href="#contact">Global Import Solutions</a></li>
                        <li><a href="#contact">Trade Consultancy</a></li>
                        <li><a href="#contact">Supply Chain Management</a></li>
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

    <script src="js/script.js?v=2.1"></script>
    <script src="js/whatsapp.js?v=1.7"></script>
</body>
</html>
