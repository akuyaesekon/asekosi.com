// Custom JavaScript for AsekosiGo

// Cart functionality
document.addEventListener('DOMContentLoaded', function() {
    // Update cart count
    updateCartCount();
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

// Update cart count in navbar
function updateCartCount() {
    if (typeof(Storage) !== "undefined") {
        let cart = JSON.parse(localStorage.getItem('asekosigo_cart') || '[]');
        const cartCount = document.getElementById('cartCount');
        if (cartCount) {
            const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
            cartCount.textContent = totalItems;
            cartCount.style.display = totalItems > 0 ? 'inline-block' : 'none';
        }
    }
}

// Add to cart function
function addToCart(productId, productName, price, image) {
    if (typeof(Storage) !== "undefined") {
        let cart = JSON.parse(localStorage.getItem('asekosigo_cart') || '[]');
        
        // Check if product already in cart
        const existingItem = cart.find(item => item.id === productId);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                id: productId,
                name: productName,
                price: price,
                image: image,
                quantity: 1
            });
        }
        
        localStorage.setItem('asekosigo_cart', JSON.stringify(cart));
        updateCartCount();
        
        // Show notification
        showToast('Success', 'Product added to cart!', 'success');
        
        return true;
    } else {
        alert('Sorry, your browser does not support web storage...');
        return false;
    }
}

// Remove from cart
function removeFromCart(productId) {
    if (typeof(Storage) !== "undefined") {
        let cart = JSON.parse(localStorage.getItem('asekosigo_cart') || '[]');
        cart = cart.filter(item => item.id !== productId);
        localStorage.setItem('asekosigo_cart', JSON.stringify(cart));
        updateCartCount();
        
        // Reload page if on cart page
        if (window.location.pathname.includes('cart.php')) {
            window.location.reload();
        }
        
        return true;
    }
    return false;
}

// Update cart quantity
function updateCartQuantity(productId, quantity) {
    if (typeof(Storage) !== "undefined") {
        let cart = JSON.parse(localStorage.getItem('asekosigo_cart') || '[]');
        const item = cart.find(item => item.id === productId);
        
        if (item) {
            if (quantity <= 0) {
                removeFromCart(productId);
            } else {
                item.quantity = quantity;
                localStorage.setItem('asekosigo_cart', JSON.stringify(cart));
            }
            
            // Reload page if on cart page
            if (window.location.pathname.includes('cart.php')) {
                window.location.reload();
            }
            
            return true;
        }
    }
    return false;
}

// Get cart total
function getCartTotal() {
    if (typeof(Storage) !== "undefined") {
        const cart = JSON.parse(localStorage.getItem('asekosigo_cart') || '[]');
        return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    }
    return 0;
}

// Show toast notification
function showToast(title, message, type = 'info') {
    // Create toast container if it doesn't exist
    if (!document.getElementById('toastContainer')) {
        const toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1060';
        document.body.appendChild(toastContainer);
    }
    
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type}" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong><br>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    document.getElementById('toastContainer').insertAdjacentHTML('beforeend', toastHtml);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
    toast.show();
    
    // Remove toast from DOM after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function () {
        toastElement.remove();
    });
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return false;
    }
    return true;
}

// Toggle password visibility
function togglePassword(inputId, toggleId) {
    const passwordInput = document.getElementById(inputId);
    const toggleButton = document.getElementById(toggleId);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleButton.innerHTML = '<i class="bi bi-eye-slash"></i>';
    } else {
        passwordInput.type = 'password';
        toggleButton.innerHTML = '<i class="bi bi-eye"></i>';
    }
}

// Image preview for uploads
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const file = input.files[0];
    const reader = new FileReader();
    
    reader.onloadend = function () {
        preview.src = reader.result;
        preview.style.display = 'block';
    }
    
    if (file) {
        reader.readAsDataURL(file);
    } else {
        preview.src = '';
        preview.style.display = 'none';
    }
}

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialize charts
function initCharts() {
    // Sales chart
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        // Chart initialization code would go here
    }
    
    // Category chart
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx) {
        // Chart initialization code would go here
    }
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
});