// UltraCem OPC 53 Product Diagnostic Script
console.log('🔧 UltraCem OPC 53 Diagnostic Starting...');

// Test 1: Check if product exists
const ultracemProduct = document.querySelector('[data-product="UltraCem OPC 53"]');
if (ultracemProduct) {
    console.log('✅ UltraCem OPC 53 product found');
    console.log('📊 Product details:', {
        product: ultracemProduct.getAttribute('data-product'),
        price: ultracemProduct.getAttribute('data-price'),
        class: ultracemProduct.className
    });
} else {
    console.log('❌ UltraCem OPC 53 product not found');
}

// Test 2: Check all UltraCem buttons
const ultracemButtons = document.querySelectorAll('[data-product="UltraCem OPC 53"]');
console.log(`🔘 Found ${ultracemButtons.length} UltraCem buttons`);

ultracemButtons.forEach((button, index) => {
    console.log(`Button ${index + 1}:`, {
        type: button.className.includes('add-to-cart') ? 'Add to Cart' :
              button.className.includes('buy-now') ? 'Buy Now' : 'Other',
        hasOnclick: button.hasAttribute('onclick'),
        onclick: button.getAttribute('onclick')
    });
});

// Test 3: Check cart functionality
if (typeof addToCart === 'function') {
    console.log('✅ addToCart function exists');
} else {
    console.log('❌ addToCart function not found');
}

if (typeof buyNow === 'function') {
    console.log('✅ buyNow function exists');
} else {
    console.log('❌ buyNow function not found');
}

// Test 4: Check quality modal
if (typeof openQualityModal === 'function') {
    console.log('✅ openQualityModal function exists');
} else {
    console.log('❌ openQualityModal function not found');
}

// Test 5: Test add to cart functionality
function testUltracemAddToCart() {
    console.log('🧪 Testing UltraCem Add to Cart...');
    try {
        if (typeof addToCart === 'function') {
            addToCart('UltraCem OPC 53', 350);
            console.log('✅ Add to cart test completed');
        } else {
            console.log('❌ addToCart function not available');
        }
    } catch (error) {
        console.log('❌ Error in add to cart test:', error.message);
    }
}

// Test 6: Test buy now functionality
function testUltracemBuyNow() {
    console.log('🧪 Testing UltraCem Buy Now...');
    try {
        if (typeof buyNow === 'function') {
            buyNow('UltraCem OPC 53', 350);
            console.log('✅ Buy now test completed');
        } else {
            console.log('❌ buyNow function not available');
        }
    } catch (error) {
        console.log('❌ Error in buy now test:', error.message);
    }
}

// Test 7: Check cart state
function checkCartState() {
    if (typeof cart !== 'undefined') {
        console.log('📦 Current cart state:', cart);
        console.log('📊 Cart total items:', cart.length);
    } else {
        console.log('❌ Cart variable not found');
    }
}

// Run all tests
console.log('🚀 Running all diagnostic tests...');
testUltracemAddToCart();
testUltracemBuyNow();
checkCartState();

console.log('🏁 UltraCem OPC 53 Diagnostic Complete');
