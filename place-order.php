<?php
require_once 'config/database.php';

// Get product details if ID is provided
$product = null;
if (isset($_GET['id'])) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$_GET['id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get cart items from URL parameters (from JavaScript cart)
session_start();
$cart_items = [];

if (isset($_GET['cart'])) {
    $cart_items = json_decode($_GET['cart'], true) ?? [];
} else {
    // Fallback to session cart
    $cart_items = $_SESSION['cart'] ?? [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order - Gulf Global Co</title>
    <link rel="stylesheet" href="css/style.css?v=3.1">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .order-page {
            padding: 2rem 0;
            min-height: 80vh;
        }
        .order-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        .order-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .order-header h1 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .order-form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2c3e50;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e8ed;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #25d366;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .order-items {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #e1e8ed;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .item-info h4 {
            margin: 0 0 0.5rem 0;
            color: #2c3e50;
        }
        .item-price {
            font-weight: 600;
            color: #25d366;
            font-size: 1.1rem;
        }
        .order-total {
            background: #25d366;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: 600;
        }
        .order-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        .btn-order {
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .btn-order:hover {
            transform: translateY(-2px);
        }
        .btn-back {
            background: #6c757d;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s;
        }
        .btn-back:hover {
            transform: translateY(-2px);
            color: white;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .order-actions {
                flex-direction: column;
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
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle">Products <i class="fas fa-chevron-down"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="products.php#groceries" class="dropdown-link">Groceries</a></li>
                        <li><a href="products.php#meats" class="dropdown-link">Fresh & Frozen Meats</a></li>
                        <li><a href="products.php#building" class="dropdown-link">Building Materials</a></li>
                        <li><a href="products.php" class="dropdown-link">View All Products</a></li>
                    </ul>
                </li>
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

    <!-- Order Page -->
    <section class="order-page">
        <div class="order-container">
            <div class="order-header">
                <h1><i class="fas fa-shopping-cart"></i> Place Your Order</h1>
                <p>Review your order details and provide your information</p>
            </div>

            <form class="order-form" id="order-form">
                <!-- Customer Information -->
                <h3><i class="fas fa-user"></i> Customer Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="customer_name">Full Name *</label>
                        <input type="text" id="customer_name" name="customer_name" required>
                    </div>
                    <div class="form-group">
                        <label for="customer_phone">Phone Number *</label>
                        <input type="tel" id="customer_phone" name="customer_phone" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="customer_email">Email Address</label>
                    <input type="email" id="customer_email" name="customer_email">
                </div>
                <div class="form-group">
                    <label for="delivery_address">Delivery Address *</label>
                    <textarea id="delivery_address" name="delivery_address" rows="3" required placeholder="Please provide your complete delivery address"></textarea>
                </div>

                <!-- Order Items -->
                <div class="order-items">
                    <h3><i class="fas fa-list"></i> Order Items</h3>
                    <div id="order-items-list">
                        <?php if ($product): ?>
                            <!-- Single Product Order -->
                            <div class="order-item">
                                <div class="item-info">
                                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                    <p><?php echo htmlspecialchars($product['description']); ?></p>
                                    <small>Quantity: 1</small>
                                </div>
                                <div class="item-price">₹<?php echo number_format($product['price'], 2); ?></div>
                            </div>
                        <?php elseif (!empty($cart_items)): ?>
                            <!-- Cart Items Order -->
                            <?php
                            $total = 0;
                            foreach ($cart_items as $item):
                                $total += $item['price'] * $item['quantity'];
                            ?>
                            <div class="order-item">
                                <div class="item-info">
                                    <h4><?php echo htmlspecialchars($item['product']); ?></h4>
                                    <p>Quantity: <?php echo $item['quantity']; ?></p>
                                </div>
                                <div class="item-price">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No items in your order. <a href="products.php">Browse Products</a></p>
                        <?php endif; ?>
                    </div>

                    <?php
                    $order_total = $product ? $product['price'] : $total;
                    if ($order_total > 0):
                    ?>
                    <div class="order-total">
                        Total Amount: ₹<?php echo number_format($order_total, 2); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Order Notes -->
                <div class="form-group">
                    <label for="order_notes">Special Instructions (Optional)</label>
                    <textarea id="order_notes" name="order_notes" rows="3" placeholder="Any special instructions for your order..."></textarea>
                </div>

                <!-- Order Actions -->
                <div class="order-actions">
                    <a href="javascript:history.back()" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <button type="submit" class="btn-order" id="place-order-btn">
                        <i class="fas fa-check"></i> Confirm Order
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Success Modal -->
    <div id="success-modal" class="success-modal" style="display: none;">
        <div class="success-modal-content">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3>Order Placed Successfully!</h3>
            <p>Thank you for your order. We have received your request and will contact you soon with order confirmation and delivery details.</p>
            <button class="btn btn-success" onclick="goToHome()">
                <i class="fas fa-home"></i> Go to Home
            </button>
        </div>
    </div>

    <style>
        .success-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        }
        .success-modal-content {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            text-align: center;
            max-width: 500px;
            margin: 0 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .success-icon {
            font-size: 4rem;
            color: #25d366;
            margin-bottom: 1rem;
        }
        .success-modal-content h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        .success-modal-content p {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .btn-success {
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .btn-success:hover {
            transform: translateY(-2px);
        }
    </style>

    <script src="js/script.js?v=2.4"></script>
    <script src="js/whatsapp.js?v=1.7"></script>
    <script>
        document.getElementById('order-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const orderData = Object.fromEntries(formData.entries());

            // Get order items
            const orderItems = [];
            <?php if ($product): ?>
            orderItems.push({
                name: '<?php echo addslashes($product['name']); ?>',
                price: <?php echo $product['price']; ?>,
                quantity: 1
            });
            <?php elseif (!empty($cart_items)): ?>
            <?php foreach ($cart_items as $item): ?>
            orderItems.push({
                name: '<?php echo addslashes($item['product']); ?>',
                price: <?php echo $item['price']; ?>,
                quantity: <?php echo $item['quantity']; ?>
            });
            <?php endforeach; ?>
            <?php endif; ?>

            // Create WhatsApp message
            let message = `🛒 *New Order from Gulf Global Co Website*\n\n`;
            message += `👤 *Customer Details:*\n`;
            message += `• Name: ${orderData.customer_name}\n`;
            message += `• Phone: ${orderData.customer_phone}\n`;
            message += `• Email: ${orderData.customer_email || 'Not provided'}\n`;
            message += `• Address: ${orderData.delivery_address}\n\n`;

            message += `📋 *Order Details:*\n`;
            let total = 0;
            orderItems.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                message += `${index + 1}. ${item.name}\n`;
                message += `   Quantity: ${item.quantity}\n`;
                message += `   Price: ₹${item.price} each\n`;
                message += `   Subtotal: ₹${itemTotal}\n\n`;
            });

            message += `💰 *Total Amount: ₹${total}*\n\n`;

            if (orderData.order_notes) {
                message += `📝 *Special Instructions:*\n${orderData.order_notes}\n\n`;
            }

            message += `Please confirm the order and provide delivery details. Thank you!`;

            // Send via WhatsApp
            if (window.whatsappManager) {
                await window.whatsappManager.sendMessage(message);
            } else {
                // Fallback to direct WhatsApp
                const whatsappNumber = '919789350475';
                const whatsappURL = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(message)}`;
                window.open(whatsappURL, '_blank');
            }

            // Show success modal
            document.getElementById('success-modal').style.display = 'flex';
        });

        // Function to go to home page
        function goToHome() {
            window.location.href = 'index.php';
        }
    </script>
</body>
</html>
