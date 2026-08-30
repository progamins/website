/**
 * Chollo & Glam - Main JavaScript
 */

// Preloader
document.addEventListener('DOMContentLoaded', function() {
    const preloader = document.getElementById('preloader');
    if (preloader) {
        setTimeout(() => {
            preloader.classList.add('fade-out');
            setTimeout(() => {
                preloader.style.display = 'none';
            }, 500);
        }, 1000);
    }
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href !== '#') {
            e.preventDefault();
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

// Lazy loading images with IntersectionObserver
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                }
                observer.unobserve(img);
            }
        });
    }, {
        rootMargin: '50px 0px'
    });

    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

// Mobile menu toggle
function toggleMobileMenu() {
    const nav = document.querySelector('.nav-menu');
    const toggle = document.querySelector('.mobile-menu-toggle');
    
    if (nav && toggle) {
        nav.classList.toggle('active');
        toggle.classList.toggle('active');
    }
}

// Cart functionality
const cart = {
    getItems: function() {
        return JSON.parse(localStorage.getItem('carrito') || '{}');
    },
    
    addItem: function(productId) {
        const items = this.getItems();
        items[productId] = (items[productId] || 0) + 1;
        localStorage.setItem('carrito', JSON.stringify(items));
        this.updateCount();
        return true;
    },
    
    removeItem: function(productId) {
        const items = this.getItems();
        delete items[productId];
        localStorage.setItem('carrito', JSON.stringify(items));
        this.updateCount();
    },
    
    getCount: function() {
        const items = this.getItems();
        return Object.values(items).reduce((sum, qty) => sum + qty, 0);
    },
    
    updateCount: function() {
        const countElements = document.querySelectorAll('.cart-count');
        const count = this.getCount();
        countElements.forEach(el => {
            el.textContent = count;
            el.style.display = count > 0 ? 'flex' : 'none';
        });
    }
};

// Initialize cart count on page load
document.addEventListener('DOMContentLoaded', function() {
    cart.updateCount();
    
    // Add to cart buttons
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            if (productId) {
                cart.addItem(productId);
                showNotification('Producto añadido al carrito');
                
                // Button animation
                this.classList.add('added');
                setTimeout(() => this.classList.remove('added'), 1000);
            }
        });
    });
    
    // Wishlist buttons
    document.querySelectorAll('.product-wishlist').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const icon = this.querySelector('i');
            const productId = this.dataset.productId;
            
            if (!productId) return;
            
            icon.classList.toggle('far');
            icon.classList.toggle('fas');
            
            const favoritos = JSON.parse(localStorage.getItem('favoritos') || '[]');
            
            if (icon.classList.contains('fas')) {
                if (!favoritos.includes(productId)) {
                    favoritos.push(productId);
                    showNotification('Producto añadido a favoritos');
                }
            } else {
                const index = favoritos.indexOf(productId);
                if (index > -1) {
                    favoritos.splice(index, 1);
                    showNotification('Producto eliminado de favoritos');
                }
            }
            
            localStorage.setItem('favoritos', JSON.stringify(favoritos));
        });
    });
    
    // Load wishlist state
    const favoritos = JSON.parse(localStorage.getItem('favoritos') || '[]');
    document.querySelectorAll('.product-wishlist').forEach(btn => {
        const productId = btn.dataset.productId;
        if (favoritos.includes(productId)) {
            const icon = btn.querySelector('i');
            icon.classList.remove('far');
            icon.classList.add('fas');
        }
    });
});

// Notification system
function showNotification(message, type = 'success') {
    // Remove existing notification
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" aria-label="Cerrar">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Close button
    notification.querySelector('.notification-close').addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    });
    
    // Auto close
    setTimeout(() => {
        if (document.body.contains(notification)) {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }
    }, 3000);
}

// Search functionality
function toggleSearch() {
    const searchOverlay = document.querySelector('.search-overlay');
    if (searchOverlay) {
        searchOverlay.classList.toggle('active');
        if (searchOverlay.classList.contains('active')) {
            const input = searchOverlay.querySelector('input');
            if (input) input.focus();
        }
    }
}

// Back to top button
document.addEventListener('DOMContentLoaded', function() {
    const backToTop = document.querySelector('.back-to-top');
    if (backToTop) {
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTop.classList.add('show');
                backToTop.classList.remove('hidden');
            } else {
                backToTop.classList.remove('show');
                backToTop.classList.add('hidden');
            }
        });
    }
});

// Reduce motion for accessibility
if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.body.classList.add('reduce-motion');
}
