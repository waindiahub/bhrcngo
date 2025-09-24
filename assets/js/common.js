/**
 * BHRC Common JavaScript File
 * Shared functionality across all pages including header/footer management
 */

// ===== CONFIGURATION =====
const COMMON_CONFIG = {
    API_BASE_URL: '/api',
    ANIMATION_DURATION: 300,
    DEBOUNCE_DELAY: 300
};

// ===== UTILITY FUNCTIONS =====
class CommonUtils {
    static debounce(func, wait) {
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

    static formatDate(date) {
        return new Date(date).toLocaleDateString('en-IN', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    static showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-message">${message}</span>
                <button class="notification-close" onclick="this.parentElement.parentElement.remove()">Ã—</button>
            </div>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
}

// ===== HEADER FUNCTIONALITY =====
class HeaderManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupMobileMenu();
        this.setupScrollBehavior();
        this.highlightActiveNavItem();
    }

    setupMobileMenu() {
        const navToggle = document.querySelector('.nav-toggle');
        const navMenu = document.querySelector('.nav-menu');
        
        if (navToggle && navMenu) {
            navToggle.addEventListener('click', () => {
                const isExpanded = navToggle.getAttribute('aria-expanded') === 'true';
                navToggle.setAttribute('aria-expanded', !isExpanded);
                navMenu.classList.toggle('nav-menu-active');
                document.body.classList.toggle('nav-open');
            });

            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
                    navToggle.setAttribute('aria-expanded', 'false');
                    navMenu.classList.remove('nav-menu-active');
                    document.body.classList.remove('nav-open');
                }
            });

            // Close menu on escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    navToggle.setAttribute('aria-expanded', 'false');
                    navMenu.classList.remove('nav-menu-active');
                    document.body.classList.remove('nav-open');
                }
            });
        }
    }

    setupScrollBehavior() {
        const header = document.querySelector('.header');
        if (!header) return;

        let lastScrollY = window.scrollY;
        
        const handleScroll = CommonUtils.debounce(() => {
            const currentScrollY = window.scrollY;
            
            if (currentScrollY > 100) {
                header.classList.add('header-scrolled');
            } else {
                header.classList.remove('header-scrolled');
            }
            
            // Hide header on scroll down, show on scroll up
            if (currentScrollY > lastScrollY && currentScrollY > 200) {
                header.classList.add('header-hidden');
            } else {
                header.classList.remove('header-hidden');
            }
            
            lastScrollY = currentScrollY;
        }, 10);

        window.addEventListener('scroll', handleScroll);
    }

    highlightActiveNavItem() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            const href = link.getAttribute('href');
            
            if (href === currentPath || 
                (currentPath === '/' && href === 'index.html') ||
                (currentPath.includes(href.replace('.html', '')) && href !== 'index.html')) {
                link.classList.add('active');
                link.setAttribute('aria-current', 'page');
            }
        });
    }
}

// ===== FOOTER FUNCTIONALITY =====
class FooterManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupBackToTop();
        this.setupFooterLinks();
    }

    setupBackToTop() {
        // Create back to top button if it doesn't exist
        let backToTopBtn = document.getElementById('backToTop');
        if (!backToTopBtn) {
            backToTopBtn = document.createElement('button');
            backToTopBtn.id = 'backToTop';
            backToTopBtn.className = 'back-to-top';
            backToTopBtn.innerHTML = '<i class="fas fa-chevron-up"></i>';
            backToTopBtn.setAttribute('aria-label', 'Back to top');
            document.body.appendChild(backToTopBtn);
        }

        // Show/hide button based on scroll position
        const handleScroll = CommonUtils.debounce(() => {
            if (window.scrollY > 300) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        }, 100);

        window.addEventListener('scroll', handleScroll);

        // Smooth scroll to top
        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    setupFooterLinks() {
        const footerLinks = document.querySelectorAll('.footer-links a, .footer-legal a');
        
        footerLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                const href = link.getAttribute('href');
                
                // Handle internal links
                if (href.startsWith('/') || href.startsWith('#')) {
                    // Add loading state or smooth transition if needed
                    link.classList.add('loading');
                    setTimeout(() => {
                        link.classList.remove('loading');
                    }, 1000);
                }
            });
        });
    }
}

// ===== FORM UTILITIES =====
class FormUtils {
    static validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    static validatePhone(phone) {
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        return phoneRegex.test(phone.replace(/\s/g, ''));
    }

    static showFieldError(field, message) {
        const errorElement = field.parentElement.querySelector('.field-error');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
        field.classList.add('error');
    }

    static clearFieldError(field) {
        const errorElement = field.parentElement.querySelector('.field-error');
        if (errorElement) {
            errorElement.style.display = 'none';
        }
        field.classList.remove('error');
    }
}

// ===== LOADING UTILITIES =====
class LoadingManager {
    static show(element = document.body) {
        const loader = document.createElement('div');
        loader.className = 'loading-overlay';
        loader.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Loading...</p>
            </div>
        `;
        element.appendChild(loader);
    }

    static hide(element = document.body) {
        const loader = element.querySelector('.loading-overlay');
        if (loader) {
            loader.remove();
        }
    }
}

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', function() {
    // Initialize header and footer managers
    new HeaderManager();
    new FooterManager();
    
    // Add smooth scrolling to all anchor links
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

    // Add loading states to external links
    document.querySelectorAll('a[href^="http"]').forEach(link => {
        link.addEventListener('click', function() {
            this.classList.add('external-loading');
        });
    });

    // Initialize form validation for common forms
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            form.addEventListener('submit', function(e) {
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
                
                // Re-enable after 3 seconds (fallback)
                setTimeout(() => {
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                }, 3000);
            });
        }
    });

    console.log('BHRC Common JavaScript initialized successfully');
});

// Export for use in other modules
window.BHRC = {
    CommonUtils,
    HeaderManager,
    FooterManager,
    FormUtils,
    LoadingManager,
    CONFIG: COMMON_CONFIG
};
