// Donate Page JavaScript

class DonationManager {
    constructor() {
        this.selectedAmount = 0;
        this.donationType = 'one-time';
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeAnimations();
        this.loadDonationStats();
    }

    bindEvents() {
        // Amount selection
        document.querySelectorAll('.amount-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.selectAmount(e));
        });

        // Custom amount input
        const customAmountInput = document.getElementById('customAmount');
        if (customAmountInput) {
            customAmountInput.addEventListener('input', (e) => this.handleCustomAmount(e));
        }

        // Donation type change
        document.querySelectorAll('input[name="donationType"]').forEach(radio => {
            radio.addEventListener('change', (e) => this.handleDonationTypeChange(e));
        });

        // Form submission
        const donationForm = document.getElementById('donationForm');
        if (donationForm) {
            donationForm.addEventListener('submit', (e) => this.handleDonationSubmit(e));
        }

        // Newsletter form
        const newsletterForm = document.getElementById('newsletterForm');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', (e) => this.handleNewsletterSubmit(e));
        }

        // PAN number formatting
        const panInput = document.getElementById('donorPan');
        if (panInput) {
            panInput.addEventListener('input', (e) => this.formatPanNumber(e));
        }

        // Phone number validation
        const phoneInput = document.getElementById('donorPhone');
        if (phoneInput) {
            phoneInput.addEventListener('input', (e) => this.validatePhoneNumber(e));
        }
    }

    selectAmount(e) {
        e.preventDefault();
        
        // Remove active class from all buttons
        document.querySelectorAll('.amount-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Add active class to clicked button
        e.target.classList.add('active');

        const amount = e.target.dataset.amount;
        
        if (amount === undefined) {
            // Custom amount button clicked
            this.showCustomAmountInput();
        } else {
            this.selectedAmount = parseInt(amount);
            document.getElementById('selectedAmount').value = this.selectedAmount;
            this.hideCustomAmountInput();
            this.updateDonationSummary();
        }
    }

    showCustomAmountInput() {
        const customAmountDiv = document.querySelector('.custom-amount-input');
        const customAmountInput = document.getElementById('customAmount');
        
        customAmountDiv.style.display = 'block';
        customAmountInput.focus();
        
        // Clear selected amount
        this.selectedAmount = 0;
        document.getElementById('selectedAmount').value = '';
    }

    hideCustomAmountInput() {
        const customAmountDiv = document.querySelector('.custom-amount-input');
        customAmountDiv.style.display = 'none';
        document.getElementById('customAmount').value = '';
    }

    handleCustomAmount(e) {
        const amount = parseInt(e.target.value);
        
        if (amount && amount >= 100) {
            this.selectedAmount = amount;
            document.getElementById('selectedAmount').value = amount;
            this.updateDonationSummary();
        } else {
            this.selectedAmount = 0;
            document.getElementById('selectedAmount').value = '';
        }
    }

    handleDonationTypeChange(e) {
        this.donationType = e.target.value;
        this.updateDonationSummary();
        
        // Update button text based on donation type
        const donateBtn = document.querySelector('.donate-btn');
        if (this.donationType === 'monthly') {
            donateBtn.innerHTML = '<i class="fas fa-heart"></i> Start Monthly Donation';
        } else {
            donateBtn.innerHTML = '<i class="fas fa-heart"></i> Donate Now';
        }
    }

    updateDonationSummary() {
        // This could update a summary section if needed
        console.log(`Donation Type: ${this.donationType}, Amount: â‚¹${this.selectedAmount}`);
    }

    async handleDonationSubmit(e) {
        e.preventDefault();
        
        if (!this.validateDonationForm()) {
            return;
        }

        this.showLoading();

        try {
            const formData = new FormData(e.target);
            const donationData = {
                amount: this.selectedAmount,
                donationType: this.donationType,
                purpose: formData.get('purpose'),
                donorName: formData.get('donorName'),
                donorEmail: formData.get('donorEmail'),
                donorPhone: formData.get('donorPhone'),
                donorPan: formData.get('donorPan'),
                donorAddress: formData.get('donorAddress'),
                anonymous: formData.get('anonymous') === '1',
                terms: formData.get('terms') === 'on'
            };

            const response = await fetch(window.BHRC_CONFIG ? window.BHRC_CONFIG.API.BASE_URL + ''.replace('/api', '') : 'https://bhrcdata.online/backend/api'.replace('/api', '')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(donationData)
            });

            const result = await response.json();

            if (response.ok) {
                // Redirect to payment gateway
                if (result.paymentUrl) {
                    window.location.href = result.paymentUrl;
                } else {
                    this.showToast('Donation submitted successfully! You will receive payment instructions via email.', 'success');
                    this.resetForm();
                }
            } else {
                throw new Error(result.message || 'Failed to process donation');
            }
        } catch (error) {
            console.error('Donation submission error:', error);
            this.showToast('Failed to process donation. Please try again.', 'error');
        } finally {
            this.hideLoading();
        }
    }

    validateDonationForm() {
        const validator = new FormValidator();
        let isValid = true;

        // Validate amount
        if (!this.selectedAmount || this.selectedAmount < 100) {
            this.showToast('Please select a donation amount of at least â‚¹100', 'error');
            isValid = false;
        }

        // Validate required fields
        const requiredFields = [
            { id: 'purpose', name: 'Donation Purpose' },
            { id: 'donorName', name: 'Full Name' },
            { id: 'donorEmail', name: 'Email Address' },
            { id: 'donorPhone', name: 'Phone Number' }
        ];

        requiredFields.forEach(field => {
            const element = document.getElementById(field.id);
            if (!element.value.trim()) {
                this.showToast(`${field.name} is required`, 'error');
                element.focus();
                isValid = false;
            }
        });

        // Validate email
        const email = document.getElementById('donorEmail').value;
        if (email && !validator.isValidEmail(email)) {
            this.showToast('Please enter a valid email address', 'error');
            document.getElementById('donorEmail').focus();
            isValid = false;
        }

        // Validate phone
        const phone = document.getElementById('donorPhone').value;
        if (phone && !validator.isValidPhone(phone)) {
            this.showToast('Please enter a valid phone number', 'error');
            document.getElementById('donorPhone').focus();
            isValid = false;
        }

        // Validate PAN if provided
        const pan = document.getElementById('donorPan').value;
        if (pan && !validator.isValidPAN(pan)) {
            this.showToast('Please enter a valid PAN number', 'error');
            document.getElementById('donorPan').focus();
            isValid = false;
        }

        // Validate terms acceptance
        const termsCheckbox = document.querySelector('input[name="terms"]');
        if (!termsCheckbox.checked) {
            this.showToast('Please accept the terms and conditions', 'error');
            isValid = false;
        }

        return isValid;
    }

    formatPanNumber(e) {
        let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        
        // Format: ABCDE1234F
        if (value.length > 10) {
            value = value.substring(0, 10);
        }
        
        e.target.value = value;
    }

    validatePhoneNumber(e) {
        let value = e.target.value.replace(/[^0-9]/g, '');
        
        if (value.length > 10) {
            value = value.substring(0, 10);
        }
        
        e.target.value = value;
    }

    async handleNewsletterSubmit(e) {
        e.preventDefault();
        
        const email = e.target.querySelector('input[type="email"]').value;
        const validator = new FormValidator();
        
        if (!validator.isValidEmail(email)) {
            this.showToast('Please enter a valid email address', 'error');
            return;
        }

        try {
            const response = await fetch(window.BHRC_CONFIG ? window.BHRC_CONFIG.API.BASE_URL + ''.replace('/api', '') : 'https://bhrcdata.online/backend/api'.replace('/api', '')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email })
            });

            const result = await response.json();

            if (response.ok) {
                this.showToast('Successfully subscribed to newsletter!', 'success');
                e.target.reset();
            } else {
                throw new Error(result.message || 'Failed to subscribe');
            }
        } catch (error) {
            console.error('Newsletter subscription error:', error);
            this.showToast('Failed to subscribe. Please try again.', 'error');
        }
    }

    async loadDonationStats() {
        try {
            const response = await fetch(window.BHRC_CONFIG ? window.BHRC_CONFIG.API.BASE_URL + ''.replace('/api', '') : 'https://bhrcdata.online/backend/api'.replace('/api', '')));
            const stats = await response.json();
            
            if (response.ok) {
                this.updateStatsDisplay(stats);
            }
        } catch (error) {
            console.error('Failed to load donation stats:', error);
        }
    }

    updateStatsDisplay(stats) {
        // Update any stats displays on the page
        const statsElements = {
            totalDonations: document.querySelector('.total-donations'),
            totalDonors: document.querySelector('.total-donors'),
            averageDonation: document.querySelector('.average-donation')
        };

        Object.keys(statsElements).forEach(key => {
            const element = statsElements[key];
            if (element && stats[key]) {
                this.animateCounter(element, stats[key]);
            }
        });
    }

    animateCounter(element, target) {
        const start = 0;
        const duration = 2000;
        const startTime = performance.now();

        const animate = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const current = Math.floor(start + (target - start) * progress);
            element.textContent = current.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };

        requestAnimationFrame(animate);
    }

    initializeAnimations() {
        // Intersection Observer for scroll animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.querySelectorAll('.impact-card, .story-card, .info-card').forEach(el => {
            el.classList.add('fade-in');
            observer.observe(el);
        });
    }

    resetForm() {
        const form = document.getElementById('donationForm');
        form.reset();
        
        // Reset amount selection
        document.querySelectorAll('.amount-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        this.hideCustomAmountInput();
        this.selectedAmount = 0;
        this.donationType = 'one-time';
        
        // Reset donate button text
        const donateBtn = document.querySelector('.donate-btn');
        donateBtn.innerHTML = '<i class="fas fa-heart"></i> Donate Now';
    }

    showLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'flex';
        }
    }

    hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    showToast(message, type = 'info') {
        // Remove existing toasts
        document.querySelectorAll('.toast').forEach(toast => toast.remove());

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Trigger animation
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
}

// Form Validator Class
class FormValidator {
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    isValidPhone(phone) {
        const phoneRegex = /^[6-9]\d{9}$/;
        return phoneRegex.test(phone);
    }

    isValidPAN(pan) {
        const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;
        return panRegex.test(pan);
    }

    sanitizeInput(input) {
        const div = document.createElement('div');
        div.textContent = input;
        return div.innerHTML;
    }
}

// Payment Integration Class
class PaymentProcessor {
    constructor() {
        this.paymentMethods = ['razorpay', 'payu', 'ccavenue'];
    }

    async initializePayment(donationData) {
        try {
            // This would integrate with actual payment gateway
            const response = await fetch(window.BHRC_CONFIG ? window.BHRC_CONFIG.API.BASE_URL + ''.replace('/api', '') : 'https://bhrcdata.online/backend/api'.replace('/api', '')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(donationData)
            });

            const result = await response.json();
            
            if (response.ok) {
                return this.redirectToPaymentGateway(result);
            } else {
                throw new Error(result.message || 'Payment initialization failed');
            }
        } catch (error) {
            console.error('Payment initialization error:', error);
            throw error;
        }
    }

    redirectToPaymentGateway(paymentData) {
        // Redirect to payment gateway
        window.location.href = paymentData.paymentUrl;
    }

    handlePaymentCallback(params) {
        // Handle payment success/failure callback
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const transactionId = urlParams.get('txnid');
        
        if (status === 'success') {
            this.showPaymentSuccess(transactionId);
        } else {
            this.showPaymentFailure();
        }
    }

    showPaymentSuccess(transactionId) {
        const message = `Payment successful! Transaction ID: ${transactionId}. You will receive a receipt via email.`;
        document.querySelector('.donation-manager').showToast(message, 'success');
    }

    showPaymentFailure() {
        const message = 'Payment failed. Please try again or contact support.';
        document.querySelector('.donation-manager').showToast(message, 'error');
    }
}

// Utility Functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR'
    }).format(amount);
}

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

function smoothScrollTo(element) {
    element.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const donationManager = new DonationManager();
    
    // Handle payment callback if present
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('payment_status')) {
        const paymentProcessor = new PaymentProcessor();
        paymentProcessor.handlePaymentCallback(urlParams);
    }
    
    // Add smooth scrolling to anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                smoothScrollTo(target);
            }
        });
    });
    
    // Add loading states to buttons
    document.querySelectorAll('button[type="submit"]').forEach(button => {
        button.addEventListener('click', function() {
            if (this.form && this.form.checkValidity()) {
                this.classList.add('loading');
                this.disabled = true;
                
                // Re-enable after 10 seconds as fallback
                setTimeout(() => {
                    this.classList.remove('loading');
                    this.disabled = false;
                }, 10000);
            }
        });
    });
});

// Export for testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        DonationManager,
        FormValidator,
        PaymentProcessor
    };
}
