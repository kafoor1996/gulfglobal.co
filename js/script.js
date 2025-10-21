// Shopping Cart Functionality
let cart = [];
let cartTotal = 0;

// Product Search Functionality
function initProductSearch() {
    const searchInput = document.getElementById('product-search');
    const searchBtn = document.getElementById('search-btn');

    if (searchInput && searchBtn) {
        // Search only on button click
        searchBtn.addEventListener('click', () => {
            performSearch();
        });
    }
}

function performSearch() {
    const searchInput = document.getElementById('product-search');
    const searchTerm = searchInput.value.trim();

    if (searchTerm.length >= 2) {
        // Redirect to products page with search parameter
        window.location.href = `products.php?search=${encodeURIComponent(searchTerm)}`;
    } else {
        // If search term is too short, go to all products
        window.location.href = 'products.php';
    }
}


// Add to Cart functionality
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing cart functionality');

    // Initialize product search
    initProductSearch();

    // Use event delegation for better handling of dynamic content
    document.addEventListener('click', (e) => {

        // Handle Add to Cart buttons
        if (e.target.classList.contains('add-to-cart') || e.target.closest('.add-to-cart')) {
            e.preventDefault();
            e.stopPropagation();

            const button = e.target.classList.contains('add-to-cart') ? e.target : e.target.closest('.add-to-cart');
            const product = button.getAttribute('data-product');
            const price = parseFloat(button.getAttribute('data-price'));


            if (product && price && !isNaN(price)) {
                addToCart(product, price, button);
            } else {
                console.error('Invalid product data:', { product, price });
                showNotification('Error: Invalid product data', 'error');
            }
        }

        // Buy Now buttons are now handled by WhatsApp Manager system
        // Removed direct handler to prevent conflicts

        // Handle View Cart buttons
        if (e.target.classList.contains('view-cart') || e.target.closest('.view-cart')) {
            e.preventDefault();
            e.stopPropagation();

            const cartModal = document.getElementById('cart-modal');
            if (cartModal) {
                cartModal.style.display = 'block';
                updateCartDisplay();
            }
        }
    });

    // Cart modal functionality
    const cartModal = document.getElementById('cart-modal');
    const cartLink = document.getElementById('cart-link');
    const closeCart = document.querySelector('.close-cart');
    const checkoutBtn = document.getElementById('checkout-whatsapp');

    if (cartLink && cartModal) {
        cartLink.addEventListener('click', (e) => {
            e.preventDefault();
            console.log('Cart link clicked, cart items:', cart);
            cartModal.style.display = 'block';
            updateCartDisplay();
        });
    } else {
        console.log('Cart link or modal not found:', { cartLink, cartModal });
    }

    if (closeCart && cartModal) {
        closeCart.addEventListener('click', () => {
            cartModal.style.display = 'none';
        });
    }

    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', () => {
            checkoutToWhatsApp();
        });
    }

    // Close modal when clicking outside
    if (cartModal) {
        window.addEventListener('click', (e) => {
            if (e.target === cartModal) {
                cartModal.style.display = 'none';
            }
        });
    }

    // Quality info buttons
    document.querySelectorAll('.quality-info:not([onclick])').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            openQualityModal();
        });
    });

    // Add direct event listeners as fallback
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const product = button.getAttribute('data-product');
            const price = parseFloat(button.getAttribute('data-price'));
            if (product && price && !isNaN(price)) {
                addToCart(product, price, button);
            }
        });
    });

    // Buy Now buttons are now handled by WhatsApp Manager system
    // Removed direct WhatsApp handler to prevent conflicts

    // Quality modal functionality
    const qualityModal = document.getElementById('quality-modal');
    const closeQuality = document.querySelector('.close-quality');

    if (closeQuality) {
        closeQuality.addEventListener('click', () => {
            closeQualityModal();
        });
    }

    // Close quality modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === qualityModal) {
            closeQualityModal();
        }
    });
});

function addToCart(product, price, button = null) {
    // Prevent multiple rapid clicks
    if (button && button.disabled) {
        return;
    }

    // Disable button temporarily to prevent multiple clicks
    if (button) {
        button.disabled = true;
        setTimeout(() => {
            button.disabled = false;
        }, 500);
    }

    const existingItem = cart.find(item => item.product === product);

    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            product: product,
            price: price,
            quantity: 1
        });
    }

    updateCartCount();
    updateCartDisplay();
    showNotification(`${product} added to cart!`, 'success');

    // Change button text to "View Cart" if button is provided
    if (button && button.classList.contains('add-to-cart')) {
        button.textContent = 'View Cart';
        button.classList.remove('add-to-cart');
        button.classList.add('view-cart');
        button.onclick = function() {
            const cartModal = document.getElementById('cart-modal');
            if (cartModal) {
                cartModal.style.display = 'block';
            }
        };
    }
}

function buyNow(product, price) {
    // Direct WhatsApp for single item purchase
    let message = `🛒 *New Order from Gulf Global Co Website*\n\n`;
    message += `📋 *Order Details:*\n`;
    message += `1. ${product}\n`;
    message += `   Quantity: 1\n`;
    message += `   Price: ₹${price} each\n`;
    message += `   Subtotal: ₹${price}\n\n`;
    message += `💰 *Total Amount: ₹${price}*\n\n`;
    message += `📞 *Please provide your details:*\n`;
    message += `• Name: [Your Name]\n`;
    message += `• Phone: [Your Phone Number]\n`;
    message += `• Address: [Your Delivery Address]\n\n`;
    message += `Please confirm the order and provide delivery details. Thank you!`;

    const whatsappNumber = '919789350475';
    const whatsappURL = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(message)}`;

    window.open(whatsappURL, '_blank');
}

function updateCartCount() {
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartCount = document.getElementById('cart-count');
    if (cartCount) {
        cartCount.textContent = totalItems;
    }
}

function updateCartDisplay() {
    const cartItems = document.getElementById('cart-items');
    const cartTotalElement = document.getElementById('cart-total');

    if (!cartItems || !cartTotalElement) {
        console.error('Cart elements not found');
        return;
    }

    if (cart.length === 0) {
        cartItems.innerHTML = `
            <div class="empty-cart" style="text-align: center; padding: 2rem; color: #666;">
                <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p>Your cart is empty</p>
                <p style="font-size: 0.9rem; margin-top: 0.5rem;">Add some products to get started!</p>
            </div>
        `;
        cartTotalElement.textContent = '0';
        return;
    }

    let total = 0;
    let itemsHTML = '';

    cart.forEach((item, index) => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;

        itemsHTML += `
            <div class="cart-item">
                <div class="cart-item-info">
                    <h4>${item.product}</h4>
                    <p>Quantity: ${item.quantity}</p>
                </div>
                <div class="cart-item-price">₹${itemTotal}</div>
                <button class="remove-item" onclick="removeFromCart(${index})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
    });

    cartItems.innerHTML = itemsHTML;
    cartTotalElement.textContent = total;
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartCount();
    updateCartDisplay();
}

function checkoutToWhatsApp() {
    if (cart.length === 0) {
        showNotification('Your cart is empty!', 'error');
        return;
    }

    // Store cart items in session storage for the order page
    sessionStorage.setItem('cart', JSON.stringify(cart));

    // Redirect to place order page with cart data
    const cartData = encodeURIComponent(JSON.stringify(cart));
    window.location.href = `place-order.php?cart=${cartData}`;
    const cartModal = document.getElementById('cart-modal');
    if (cartModal) {
        cartModal.style.display = 'none';
    }
}

// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const href = this.getAttribute('href');
        if (href && href !== '#') {
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });
});

// Navbar background change on scroll
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.style.background = 'rgba(255, 255, 255, 0.98)';
        navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.15)';
    } else {
        navbar.style.background = 'rgba(255, 255, 255, 0.95)';
        navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
    }
});

// Active navigation link highlighting
window.addEventListener('scroll', () => {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link');

    let current = '';
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        if (window.scrollY >= (sectionTop - 200)) {
            current = section.getAttribute('id');
        }
    });

    navLinks.forEach(link => {
        link.classList.remove('active');
        const href = link.getAttribute('href');
        if (href && (href === `#${current}` || href === `index.html#${current}`)) {
            link.classList.add('active');
        }
    });
});

// Intersection Observer for fade-in animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('fade-in');
        }
    });
}, observerOptions);

// Observe elements for animation
document.addEventListener('DOMContentLoaded', () => {
    const animateElements = document.querySelectorAll('.product-card, .stat-item, .certificate, .contact-item');
    animateElements.forEach(el => observer.observe(el));
});

// Contact form handling
const contactForm = document.querySelector('.contact-form');
if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
        e.preventDefault();

        // Get form data
        const formData = new FormData(contactForm);
        const name = contactForm.querySelector('input[type="text"]').value;
        const email = contactForm.querySelector('input[type="email"]').value;
        const subject = contactForm.querySelectorAll('input[type="text"]')[1].value;
        const message = contactForm.querySelector('textarea').value;

        // Basic validation
        if (!name || !email || !subject || !message) {
            showNotification('Please fill in all fields', 'error');
            return;
        }

        if (!isValidEmail(email)) {
            showNotification('Please enter a valid email address', 'error');
            return;
        }

        // Simulate form submission
        showNotification('Thank you for your message! We will get back to you soon.', 'success');
        contactForm.reset();
    });
}

// Email validation function
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Notification system
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `;

    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#4ade80' : type === 'error' ? '#ef4444' : '#2c5aa0'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        z-index: 10000;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        max-width: 400px;
    `;

    // Add to page
    document.body.appendChild(notification);

    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);

    // Close button functionality
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        notification.style.transform = 'translateX(400px)';
        setTimeout(() => notification.remove(), 300);
    });

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Parallax effect for hero section - REMOVED
// window.addEventListener('scroll', () => {
//     const scrolled = window.pageYOffset;
//     const hero = document.querySelector('.hero');
//     if (hero) {
//         const rate = scrolled * -0.5;
//         hero.style.transform = `translateY(${rate}px)`;
//     }
// });

// Counter animation for statistics
function animateCounters() {
    const counters = document.querySelectorAll('.stat-item h4');
    counters.forEach(counter => {
        const originalText = counter.textContent;

        // Check if the text contains numbers
        const hasNumbers = /\d/.test(originalText);

        if (hasNumbers) {
            const target = parseInt(originalText.replace(/\D/g, ''));
            if (!isNaN(target) && target > 0) {
                const increment = target / 100;
                let current = 0;

                const updateCounter = () => {
                    if (current < target) {
                        current += increment;
                        counter.textContent = Math.ceil(current) + (originalText.includes('+') ? '+' : '');
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = target + (originalText.includes('+') ? '+' : '');
                    }
                };

                updateCounter();
            }
        } else {
            // For text-based stats, just add a fade-in animation
            counter.style.opacity = '0';
            counter.style.transform = 'translateY(20px)';

            setTimeout(() => {
                counter.style.transition = 'all 0.6s ease';
                counter.style.opacity = '1';
                counter.style.transform = 'translateY(0)';
            }, 200);
        }
    });
}

// Trigger counter animation when stats section is visible
const statsObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            animateCounters();
            statsObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

const statsSection = document.querySelector('.about-stats');
if (statsSection) {
    statsObserver.observe(statsSection);
}

// Product card hover effects
document.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('mouseenter', () => {
        card.style.transform = 'translateY(-10px) scale(1.02)';
    });

    card.addEventListener('mouseleave', () => {
        card.style.transform = 'translateY(0) scale(1)';
    });
});

// Loading animation
window.addEventListener('load', () => {
    document.body.classList.add('loaded');

    // Add fade-in class to hero content
    const heroContent = document.querySelector('.hero-content');
    if (heroContent) {
        heroContent.classList.add('fade-in');
    }
});

// Back to top button
const backToTopButton = document.createElement('button');
backToTopButton.innerHTML = '<i class="fas fa-arrow-up"></i>';
backToTopButton.className = 'back-to-top';
backToTopButton.style.cssText = `
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 50px;
    height: 50px;
    background: #2c5aa0;
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    box-shadow: 0 5px 15px rgba(44, 90, 160, 0.3);
    transition: all 0.3s ease;
    z-index: 1000;
`;

document.body.appendChild(backToTopButton);

// Show/hide back to top button
window.addEventListener('scroll', () => {
    if (window.scrollY > 300) {
        backToTopButton.style.display = 'flex';
    } else {
        backToTopButton.style.display = 'none';
    }
});

// Back to top functionality
backToTopButton.addEventListener('click', () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// Add hover effect to back to top button
backToTopButton.addEventListener('mouseenter', () => {
    backToTopButton.style.background = '#1e3d6f';
    backToTopButton.style.transform = 'translateY(-2px)';
});

backToTopButton.addEventListener('mouseleave', () => {
    backToTopButton.style.background = '#2c5aa0';
    backToTopButton.style.transform = 'translateY(0)';
});

// Preloader (optional)
window.addEventListener('load', () => {
    const preloader = document.querySelector('.preloader');
    if (preloader) {
        preloader.style.opacity = '0';
        setTimeout(() => {
            preloader.style.display = 'none';
        }, 500);
    }
});

// Add CSS for notification styles
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    .notification-content {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .notification-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        margin-left: auto;
        padding: 0;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .notification-close:hover {
        opacity: 0.7;
    }

    .nav-link.active {
        color: #2c5aa0;
    }

    .nav-link.active::after {
        width: 100%;
    }

    .loaded .hero-content {
        animation: fadeInUp 0.8s ease-out;
    }

    .cart-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem;
        border-bottom: 1px solid #eee;
    }

    .cart-item-info h4 {
        margin: 0 0 0.5rem 0;
        font-size: 1rem;
        color: #333;
    }

    .cart-item-info p {
        margin: 0;
        font-size: 0.9rem;
        color: #666;
    }

    .cart-item-price {
        font-weight: bold;
        color: #2c5aa0;
        font-size: 1.1rem;
    }

    .remove-item {
        background: #ff4444;
        color: white;
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.3s ease;
    }

    .remove-item:hover {
        background: #cc0000;
    }

    .view-cart {
        background: #28a745 !important;
        color: white !important;
    }

    .view-cart:hover {
        background: #218838 !important;
    }
`;

document.head.appendChild(notificationStyles);

// Quality Modal Functions
function openQualityModal() {
    // Close any other open modals first
    const customerModal = document.getElementById('customer-modal');
    if (customerModal && customerModal.style.display === 'block') {
        customerModal.style.display = 'none';
    }

    const qualityModal = document.getElementById('quality-modal');
    if (qualityModal) {
        qualityModal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevent background scrolling

        // Add entrance animation
        const modalContent = qualityModal.querySelector('.quality-modal-content');
        if (modalContent) {
            modalContent.style.animation = 'slideInUp 0.4s ease';
            modalContent.style.transform = 'translateY(0)';
        }

        // Focus management for accessibility
        const firstFocusable = qualityModal.querySelector('button, input, textarea, select, a[href]');
        if (firstFocusable) {
            firstFocusable.focus();
        }
    }
}

function closeQualityModal() {
    const qualityModal = document.getElementById('quality-modal');
    if (qualityModal) {
        // Add exit animation
        const modalContent = qualityModal.querySelector('.quality-modal-content');
        if (modalContent) {
            modalContent.style.animation = 'slideOutDown 0.3s ease';
            modalContent.style.transform = 'translateY(50px)';
        }

        // Hide modal after animation
        setTimeout(() => {
            qualityModal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Restore scrolling
        }, 300);
    }
}

// Close quality modal with Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const qualityModal = document.getElementById('quality-modal');
        if (qualityModal && qualityModal.style.display === 'block') {
            closeQualityModal();
        }
    }
});

// WhatsApp Contact Function
function sendWhatsAppMessage() {

    const name = document.querySelector('input[placeholder="Your Name"]').value || 'Customer';
    const email = document.querySelector('input[placeholder="Your Email"]').value || '';
    const subject = document.querySelector('input[placeholder="Subject"]').value || 'General Inquiry';
    const message = document.querySelector('textarea[placeholder="Your Message"]').value || '';


    const whatsappNumber = '919789350475';

    const fullMessage = `Hello Gulf Global Co! 👋

*Contact Form Submission:*
• Name: ${name}
• Email: ${email}
• Subject: ${subject}

*Message:*
${message}

I'm interested in your products and services. Please get back to me soon!

Thank you! 🙏`;

    const whatsappURL = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(fullMessage)}`;


    window.open(whatsappURL, '_blank');
}

// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('nav-menu');
    const cartLink = document.querySelector('.cart-link');


    if (hamburger && navMenu) {
        hamburger.addEventListener('click', function(e) {
            e.stopPropagation();
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        // Close mobile menu when clicking on a nav link (but not dropdown toggles or dropdown links)
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                // Don't close menu if it's a dropdown toggle or dropdown link
                if (!link.classList.contains('dropdown-toggle') &&
                    !link.classList.contains('dropdown-link') &&
                    !link.classList.contains('submenu-link')) {
                    hamburger.classList.remove('active');
                    navMenu.classList.remove('active');
                }
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!hamburger.contains(event.target) && !navMenu.contains(event.target)) {
                hamburger.classList.remove('active');
                navMenu.classList.remove('active');
            }
        });
    }

    // Dropdown menu functionality
    const dropdowns = document.querySelectorAll('.dropdown');

    dropdowns.forEach(dropdown => {
        const dropdownToggle = dropdown.querySelector('.dropdown-toggle');
        const dropdownMenu = dropdown.querySelector('.dropdown-menu');

        if (dropdownToggle && dropdownMenu) {
            // Desktop hover functionality
            dropdown.addEventListener('mouseenter', () => {
                if (window.innerWidth > 768) {
                    dropdownMenu.style.opacity = '1';
                    dropdownMenu.style.visibility = 'visible';
                    dropdownMenu.style.transform = 'translateY(0)';
                }
            });

            dropdown.addEventListener('mouseleave', () => {
                if (window.innerWidth > 768) {
                    dropdownMenu.style.opacity = '0';
                    dropdownMenu.style.visibility = 'hidden';
                    dropdownMenu.style.transform = 'translateY(-10px)';
                }
            });

            // Mobile click functionality
            dropdownToggle.addEventListener('click', (e) => {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
                }
            });
        }
    });

    // Close dropdowns when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768) {
            dropdowns.forEach(dropdown => {
                const dropdownMenu = dropdown.querySelector('.dropdown-menu');
                if (dropdownMenu && !dropdown.contains(event.target)) {
                    dropdownMenu.style.display = 'none';
                }
            });
        }
    });

    // Handle window resize
    window.addEventListener('resize', () => {
        dropdowns.forEach(dropdown => {
            const dropdownMenu = dropdown.querySelector('.dropdown-menu');
            if (dropdownMenu) {
                if (window.innerWidth > 768) {
                    dropdownMenu.style.display = '';
                    dropdownMenu.style.opacity = '0';
                    dropdownMenu.style.visibility = 'hidden';
                    dropdownMenu.style.transform = 'translateY(-10px)';
                } else {
                    dropdownMenu.style.display = 'none';
                }
            }
        });
    });
});

// Mobile Dropdown Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle mobile dropdown toggles
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Only handle on mobile/tablet
            if (window.innerWidth <= 768) {
                const dropdown = this.nextElementSibling;
                if (dropdown && dropdown.classList.contains('dropdown-menu')) {
                    // Close other dropdowns
                    document.querySelectorAll('.dropdown-menu.active').forEach(activeDropdown => {
                        if (activeDropdown !== dropdown) {
                            activeDropdown.classList.remove('active');
                        }
                    });

                    // Toggle current dropdown
                    dropdown.classList.toggle('active');
                }
            }
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu.active').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        }
    });

    // Mobile Filter Menu Toggle
    const filterMenuToggle = document.getElementById('filter-menu-toggle');
    const filterDropdown = document.getElementById('filter-dropdown');

    if (filterMenuToggle && filterDropdown) {
        filterMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            filterDropdown.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.mobile-filter-menu')) {
                filterDropdown.classList.remove('active');
            }
        });

        // Close dropdown when clicking on a filter option
        const filterDropdownBtns = document.querySelectorAll('.filter-dropdown-btn');
        filterDropdownBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterDropdown.classList.remove('active');
            });
        });
    }

});

