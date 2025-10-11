// Shopping Cart Functionality
let cart = [];
let cartTotal = 0;

// Mobile Navigation Toggle
const hamburger = document.querySelector('.hamburger');
const navMenu = document.querySelector('.nav-menu');

hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    navMenu.classList.toggle('active');
});

// Close mobile menu when clicking on a link
document.querySelectorAll('.nav-link').forEach(n => n.addEventListener('click', () => {
    hamburger.classList.remove('active');
    navMenu.classList.remove('active');
}));

// Add to Cart functionality
document.addEventListener('DOMContentLoaded', () => {
    // Add to cart buttons
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', (e) => {
            const product = e.target.getAttribute('data-product');
            const price = parseInt(e.target.getAttribute('data-price'));
            addToCart(product, price);
        });
    });

    // Buy now buttons
    document.querySelectorAll('.buy-now').forEach(button => {
        button.addEventListener('click', (e) => {
            const product = e.target.getAttribute('data-product');
            const price = parseInt(e.target.getAttribute('data-price'));
            buyNow(product, price);
        });
    });

    // Cart modal functionality
    const cartModal = document.getElementById('cart-modal');
    const cartLink = document.getElementById('cart-link');
    const closeCart = document.querySelector('.close-cart');
    const checkoutBtn = document.getElementById('checkout-whatsapp');

    cartLink.addEventListener('click', (e) => {
        e.preventDefault();
        cartModal.style.display = 'block';
        updateCartDisplay();
    });

    closeCart.addEventListener('click', () => {
        cartModal.style.display = 'none';
    });

    checkoutBtn.addEventListener('click', () => {
        checkoutToWhatsApp();
    });

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === cartModal) {
            cartModal.style.display = 'none';
        }
    });

    // Quality info buttons
    document.querySelectorAll('.quality-info').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            openQualityModal();
        });
    });

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

function addToCart(product, price) {
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
    showNotification(`${product} added to cart!`, 'success');
}

function buyNow(product, price) {
    // Clear cart and add single item
    cart = [{
        product: product,
        price: price,
        quantity: 1
    }];

    updateCartCount();
    checkoutToWhatsApp();
}

function updateCartCount() {
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    document.getElementById('cart-count').textContent = totalItems;
}

function updateCartDisplay() {
    const cartItems = document.getElementById('cart-items');
    const cartTotalElement = document.getElementById('cart-total');

    if (cart.length === 0) {
        cartItems.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <p>Your cart is empty</p>
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

    let message = `🛒 *New Order from Gulf Global Co Website*\n\n`;
    message += `📋 *Order Details:*\n`;

    let total = 0;
    cart.forEach((item, index) => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        message += `${index + 1}. ${item.product}\n`;
        message += `   Quantity: ${item.quantity}\n`;
        message += `   Price: ₹${item.price} each\n`;
        message += `   Subtotal: ₹${itemTotal}\n\n`;
    });

    message += `💰 *Total Amount: ₹${total}*\n\n`;
    message += `📞 *Contact Details:*\n`;
    message += `Name: [Your Name]\n`;
    message += `Phone: [Your Phone Number]\n`;
    message += `Address: [Your Delivery Address]\n\n`;
    message += `Please confirm the order and provide delivery details. Thank you!`;

    const whatsappNumber = '919789350475'; // Gulf Global Co WhatsApp number
    const whatsappURL = `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(message)}`;

    window.open(whatsappURL, '_blank');

    // Clear cart after checkout
    cart = [];
    updateCartCount();
    document.getElementById('cart-modal').style.display = 'none';
}

// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
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

// Parallax effect for hero section
window.addEventListener('scroll', () => {
    const scrolled = window.pageYOffset;
    const hero = document.querySelector('.hero');
    if (hero) {
        const rate = scrolled * -0.5;
        hero.style.transform = `translateY(${rate}px)`;
    }
});

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
`;

document.head.appendChild(notificationStyles);

// Quality Modal Functions
function openQualityModal() {
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
