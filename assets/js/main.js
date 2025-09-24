/**
 * BHRC Main JavaScript File
 * Modern ES6+ JavaScript with API integration and interactive features
 */

// ===== CONFIGURATION & CONSTANTS =====
const CONFIG = {
    API_BASE_URL: 'https://bhrcdata.online/backend/api',
    ANIMATION_DURATION: 300,
    SCROLL_THRESHOLD: 100,
    DEBOUNCE_DELAY: 300,
    MAX_RETRIES: 3,
    CACHE_DURATION: 5 * 60 * 1000, // 5 minutes
};

// ===== UTILITY FUNCTIONS =====
class Utils {
    /**
     * Debounce function to limit function calls
     */
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

    /**
     * Throttle function to limit function calls
     */
    static throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    /**
     * Format number with animation
     */
    static animateNumber(element, target, duration = 2000) {
        const start = 0;
        const increment = target / (duration / 16);
        let current = start;

        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current).toLocaleString();
        }, 16);
    }

    /**
     * Check if element is in viewport
     */
    static isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    /**
     * Smooth scroll to element
     */
    static scrollToElement(element, offset = 0) {
        const elementPosition = element.getBoundingClientRect().top;
        const offsetPosition = elementPosition + window.pageYOffset - offset;

        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
        });
    }

    /**
     * Show loading state
     */
    static showLoading(button) {
        const btnText = button.querySelector('.btn-text');
        const btnLoading = button.querySelector('.btn-loading');
        
        if (btnText) btnText.style.opacity = '0';
        if (btnLoading) btnLoading.classList.remove('d-none');
        
        button.disabled = true;
    }

    /**
     * Hide loading state
     */
    static hideLoading(button) {
        const btnText = button.querySelector('.btn-text');
        const btnLoading = button.querySelector('.btn-loading');
        
        if (btnText) btnText.style.opacity = '1';
        if (btnLoading) btnLoading.classList.add('d-none');
        
        button.disabled = false;
    }

    /**
     * Show toast notification
     */
    static showToast(message, type = 'info', duration = 5000) {
        // Create toast container if it doesn't exist
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.className = 'position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '1055';
            document.body.appendChild(toastContainer);
        }

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;

        toastContainer.appendChild(toast);

        // Initialize and show toast
        const bsToast = new bootstrap.Toast(toast, { delay: duration });
        bsToast.show();

        // Remove toast element after it's hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
}

// ===== API SERVICE =====
class ApiService {
    constructor() {
        this.cache = new Map();
    }

    /**
     * Make API request with error handling and retry logic
     */
    async request(endpoint, options = {}) {
        const url = `${CONFIG.API_BASE_URL}${endpoint}`;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const finalOptions = { ...defaultOptions, ...options };

        // Add auth token if available
        const token = localStorage.getItem('token');
        if (token) {
            finalOptions.headers['Authorization'] = `Bearer ${token}`;
        }

        // Add CSRF token if available
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            finalOptions.headers['X-CSRF-TOKEN'] = csrfToken;
        }

        let lastError;
        for (let i = 0; i < CONFIG.MAX_RETRIES; i++) {
            try {
                const response = await fetch(url, finalOptions);
                
                if (!response.ok) {
                    // Handle authentication errors
                    if (response.status === 401) {
                        localStorage.removeItem('token');
                        window.location.href = '/pages/login.html';
                        return;
                    }
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                
                // Handle API response format
                if (data.success === false) {
                    throw new Error(data.message || 'API request failed');
                }
                
                return data;
            } catch (error) {
                lastError = error;
                if (i < CONFIG.MAX_RETRIES - 1) {
                    await new Promise(resolve => setTimeout(resolve, 1000 * (i + 1)));
                }
            }
        }

        throw lastError;
    }

    /**
     * GET request with caching
     */
    async get(endpoint, useCache = true) {
        const cacheKey = `GET:${endpoint}`;
        
        if (useCache && this.cache.has(cacheKey)) {
            const cached = this.cache.get(cacheKey);
            if (Date.now() - cached.timestamp < CONFIG.CACHE_DURATION) {
                return cached.data;
            }
        }

        const data = await this.request(endpoint);
        
        if (useCache) {
            this.cache.set(cacheKey, {
                data,
                timestamp: Date.now()
            });
        }

        return data;
    }

    /**
     * POST request
     */
    async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    /**
     * PUT request
     */
    async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    /**
     * DELETE request
     */
    async delete(endpoint) {
        return this.request(endpoint, {
            method: 'DELETE'
        });
    }
}

// ===== MAIN APPLICATION CLASS =====
class BHRCApp {
    constructor() {
        this.api = new ApiService();
        this.init();
    }

    /**
     * Initialize the application
     */
    init() {
        this.setupEventListeners();
        this.initializeComponents();
        this.loadDynamicContent();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // DOM Content Loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.onDOMReady());
        } else {
            this.onDOMReady();
        }

        // Window events
        window.addEventListener('scroll', Utils.throttle(() => this.handleScroll(), 100));
        window.addEventListener('resize', Utils.debounce(() => this.handleResize(), 250));
        window.addEventListener('load', () => this.onWindowLoad());

        // Form submissions
        document.addEventListener('submit', (e) => this.handleFormSubmit(e));

        // Navigation clicks
        document.addEventListener('click', (e) => this.handleNavigation(e));
    }

    /**
     * DOM Ready handler
     */
    onDOMReady() {
        this.initBackToTop();
        this.initNewsletterForm();
        this.initAnimations();
        this.initAccessibility();
    }

    /**
     * Window load handler
     */
    onWindowLoad() {
        this.initCounterAnimations();
        this.preloadImages();
    }

    /**
     * Initialize components
     */
    initializeComponents() {
        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

        // Initialize Bootstrap popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
    }

    /**
     * Initialize back to top button
     */
    initBackToTop() {
        const backToTopBtn = document.getElementById('backToTop');
        if (!backToTopBtn) return;

        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }

    /**
     * Initialize newsletter form
     */
    initNewsletterForm() {
        const form = document.getElementById('newsletter-form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleNewsletterSubmission(form);
        });
    }

    /**
     * Handle newsletter form submission
     */
    async handleNewsletterSubmission(form) {
        const emailInput = form.querySelector('#newsletter-email');
        const submitBtn = form.querySelector('#newsletter-btn');
        const email = emailInput.value.trim();

        // Validate email
        if (!this.validateEmail(email)) {
            Utils.showToast('Please enter a valid email address', 'danger');
            emailInput.focus();
            return;
        }

        try {
            Utils.showLoading(submitBtn);

            const response = await this.api.post('/newsletter/subscribe', { email });

            if (response.success) {
                Utils.showToast('Successfully subscribed to newsletter!', 'success');
                form.reset();
            } else {
                Utils.showToast(response.message || 'Subscription failed', 'danger');
            }
        } catch (error) {
            console.error('Newsletter subscription error:', error);
            Utils.showToast('An error occurred. Please try again.', 'danger');
        } finally {
            Utils.hideLoading(submitBtn);
        }
    }

    /**
     * Initialize counter animations
     */
    initCounterAnimations() {
        const counters = document.querySelectorAll('[data-count]');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = parseInt(entry.target.dataset.count);
                    Utils.animateNumber(entry.target, target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(counter => observer.observe(counter));
    }

    /**
     * Initialize scroll animations
     */
    initAnimations() {
        const animatedElements = document.querySelectorAll('.service-card, .event-card, .about-content');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        animatedElements.forEach(element => observer.observe(element));
    }

    /**
     * Initialize accessibility features
     */
    initAccessibility() {
        // Skip link functionality
        const skipLink = document.querySelector('.skip-link');
        if (skipLink) {
            skipLink.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(skipLink.getAttribute('href'));
                if (target) {
                    target.focus();
                    Utils.scrollToElement(target, 100);
                }
            });
        }

        // Keyboard navigation for dropdowns
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                // Close any open dropdowns
                const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
                openDropdowns.forEach(dropdown => {
                    const toggle = dropdown.previousElementSibling;
                    if (toggle) {
                        bootstrap.Dropdown.getInstance(toggle)?.hide();
                    }
                });
            }
        });
    }

    /**
     * Handle scroll events
     */
    handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Back to top button visibility
        const backToTopBtn = document.getElementById('backToTop');
        if (backToTopBtn) {
            if (scrollTop > CONFIG.SCROLL_THRESHOLD) {
                backToTopBtn.classList.add('show');
            } else {
                backToTopBtn.classList.remove('show');
            }
        }

        // Header shadow on scroll
        const header = document.querySelector('.header');
        if (header) {
            if (scrollTop > 10) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }
    }

    /**
     * Handle window resize
     */
    handleResize() {
        // Update any size-dependent calculations
        this.updateViewportHeight();
    }

    /**
     * Update viewport height for mobile browsers
     */
    updateViewportHeight() {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }

    /**
     * Handle form submissions
     */
    async handleFormSubmit(e) {
        const form = e.target;
        
        // Skip if not a form or already handled
        if (!form.matches('form') || form.dataset.handled === 'true') {
            return;
        }

        // Skip newsletter form (handled separately)
        if (form.id === 'newsletter-form') {
            return;
        }

        e.preventDefault();
        form.dataset.handled = 'true';

        try {
            await this.processForm(form);
        } catch (error) {
            console.error('Form submission error:', error);
            Utils.showToast('An error occurred. Please try again.', 'danger');
        } finally {
            form.dataset.handled = 'false';
        }
    }

    /**
     * Process form submission
     */
    async processForm(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        const action = form.action || form.dataset.action;
        const method = form.method || 'POST';

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            Utils.showLoading(submitBtn);
        }

        try {
            let response;
            if (method.toLowerCase() === 'post') {
                response = await this.api.post(action, data);
            } else if (method.toLowerCase() === 'put') {
                response = await this.api.put(action, data);
            }

            if (response.success) {
                Utils.showToast(response.message || 'Form submitted successfully!', 'success');
                form.reset();
                
                // Redirect if specified
                if (response.redirect) {
                    setTimeout(() => {
                        window.location.href = response.redirect;
                    }, 1500);
                }
            } else {
                Utils.showToast(response.message || 'Form submission failed', 'danger');
                
                // Show field-specific errors
                if (response.errors) {
                    this.showFieldErrors(form, response.errors);
                }
            }
        } finally {
            if (submitBtn) {
                Utils.hideLoading(submitBtn);
            }
        }
    }

    /**
     * Show field-specific errors
     */
    showFieldErrors(form, errors) {
        // Clear existing errors
        form.querySelectorAll('.is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });
        form.querySelectorAll('.invalid-feedback').forEach(feedback => {
            feedback.remove();
        });

        // Show new errors
        Object.entries(errors).forEach(([field, messages]) => {
            const fieldElement = form.querySelector(`[name="${field}"]`);
            if (fieldElement) {
                fieldElement.classList.add('is-invalid');
                
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = Array.isArray(messages) ? messages[0] : messages;
                
                fieldElement.parentNode.appendChild(feedback);
            }
        });
    }

    /**
     * Handle navigation clicks
     */
    handleNavigation(e) {
        const link = e.target.closest('a[href^="#"]');
        if (!link) return;

        const href = link.getAttribute('href');
        if (href === '#') return;

        const target = document.querySelector(href);
        if (target) {
            e.preventDefault();
            Utils.scrollToElement(target, 100);
        }
    }

    /**
     * Load dynamic content
     */
    async loadDynamicContent() {
        try {
            await Promise.all([
                this.loadRecentEvents(),
                this.loadStatistics()
            ]);
        } catch (error) {
            console.error('Error loading dynamic content:', error);
        }
    }

    /**
     * Load recent events
     */
    async loadRecentEvents() {
        try {
            const response = await this.api.get(window.BHRC_CONFIG.API.ENDPOINTS.EVENTS_RECENT + '?limit=3');
            if (response.success && response.data.length > 0) {
                this.renderEvents(response.data);
            }
        } catch (error) {
            console.error('Error loading recent events:', error);
        }
    }

    /**
     * Render events
     */
    renderEvents(events) {
        const container = document.getElementById('recent-events');
        if (!container) return;

        container.innerHTML = events.map(event => `
            <div class="col-md-6 col-lg-4">
                <div class="event-card">
                    <div class="event-image">
                        <img src="${event.image || './assets/images/event-placeholder.svg'}" 
                             alt="${event.title}" class="img-fluid" loading="lazy">
                        <div class="event-date">
                            <span class="date">${new Date(event.date).getDate()}</span>
                            <span class="month">${new Date(event.date).toLocaleDateString('en', { month: 'short' })}</span>
                        </div>
                    </div>
                    <div class="event-content">
                        <h3 class="event-title">${event.title}</h3>
                        <p class="event-description">${event.description}</p>
                        <div class="event-meta">
                            <span class="event-location">
                                <i class="fas fa-map-marker-alt"></i> ${event.location}
                            </span>
                            <span class="event-time">
                                <i class="fas fa-clock"></i> ${event.time}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    /**
     * Load statistics
     */
    async loadStatistics() {
        try {
            const response = await this.api.get(window.BHRC_CONFIG.API.ENDPOINTS.STATISTICS);
            if (response.success) {
                this.updateStatistics(response.data);
            }
        } catch (error) {
            console.error('Error loading statistics:', error);
        }
    }

    /**
     * Update statistics
     */
    updateStatistics(stats) {
        const statElements = document.querySelectorAll('[data-count]');
        statElements.forEach(element => {
            const statType = element.dataset.stat;
            if (stats[statType]) {
                element.dataset.count = stats[statType];
            }
        });
    }

    /**
     * Preload critical images
     */
    /**
     * Preload critical images
     */
    preloadImages() {
        const criticalImages = [
            './assets/images/hero-justice.svg',
            './assets/images/about-us.svg',
            './images/logo-dark.png'
        ];

        criticalImages.forEach(src => {
            const img = new Image();
            img.src = src;
        });
    }

    /**
     * Validate email address
     */
    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Validate phone number
     */
    validatePhone(phone) {
        const phoneRegex = /^[+]?[\d\s\-\(\)]{10,}$/;
        return phoneRegex.test(phone);
    }

    /**
     * Format date
     */
    formatDate(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        return new Date(date).toLocaleDateString('en-IN', { ...defaultOptions, ...options });
    }

    /**
     * Format currency
     */
    formatCurrency(amount, currency = 'INR') {
        return new Intl.NumberFormat('en-IN', {
            style: 'currency',
            currency: currency
        }).format(amount);
    }
}

// ===== INITIALIZE APPLICATION =====
const app = new BHRCApp();

// ===== GLOBAL UTILITIES =====
window.BHRC = {
    app,
    utils: Utils,
    showToast: Utils.showToast,
    formatDate: app.formatDate.bind(app),
    formatCurrency: app.formatCurrency.bind(app)
};

// ===== ERROR HANDLING =====
window.addEventListener('error', (e) => {
    console.error('Global error:', e.error);
    // You might want to send this to your error tracking service
});

window.addEventListener('unhandledrejection', (e) => {
    console.error('Unhandled promise rejection:', e.reason);
    // You might want to send this to your error tracking service
});

// ===== PERFORMANCE MONITORING =====
if ('performance' in window) {
    window.addEventListener('load', () => {
        setTimeout(() => {
            const perfData = performance.getEntriesByType('navigation')[0];
            console.log('Page load time:', perfData.loadEventEnd - perfData.fetchStart, 'ms');
        }, 0);
    });
}

// ===== EXPORT FOR MODULE SYSTEMS =====
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { BHRCApp, Utils, ApiService };
}
