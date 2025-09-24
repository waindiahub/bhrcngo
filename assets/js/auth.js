/**
 * Authentication JavaScript
 * Handles login, OTP verification, password reset, and form validation
 */

class AuthManager {
    constructor() {
        // Use the global config if available, otherwise fallback to production URL
        this.apiBase = window.BHRC_CONFIG ? window.BHRC_CONFIG.API.BASE_URL : 'https://bhrcdata.online/backend/api';
        this.currentForm = 'login';
        this.otpTimer = null;
        this.otpTimeLeft = 60;
        this.maxOtpAttempts = 3;
        this.otpAttempts = 0;
        this.init();
    }

    /**
     * Initialize authentication manager
     */
    init() {
        this.setupEventListeners();
        this.initializeFormSwitching();
        this.initializeOtpInputs();
        this.initializePasswordToggle();
        this.setupFormValidation();
        
        // Check if user is already logged in
        this.checkExistingSession();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Login form
        const loginForm = document.getElementById('loginFormElement');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        // OTP login form
        const otpLoginForm = document.getElementById('otpLoginFormElement');
        if (otpLoginForm) {
            otpLoginForm.addEventListener('submit', (e) => this.handleOtpLogin(e));
        }

        // OTP verification form
        const otpVerifyForm = document.getElementById('otpVerifyFormElement');
        if (otpVerifyForm) {
            otpVerifyForm.addEventListener('submit', (e) => this.handleOtpVerification(e));
        }

        // Forgot password form
        const forgotPasswordForm = document.getElementById('forgotPasswordFormElement');
        if (forgotPasswordForm) {
            forgotPasswordForm.addEventListener('submit', (e) => this.handleForgotPassword(e));
        }

        // Form switching buttons
        this.setupFormSwitchingButtons();

        // Resend OTP
        const resendOtpBtn = document.getElementById('resendOtpBtn');
        if (resendOtpBtn) {
            resendOtpBtn.addEventListener('click', () => this.resendOtp());
        }
    }

    /**
     * Setup form switching buttons
     */
    setupFormSwitchingButtons() {
        const buttons = {
            otpLoginBtn: () => this.switchForm('otpLogin'),
            backToLoginBtn: () => this.switchForm('login'),
            forgotPasswordLink: () => this.switchForm('forgotPassword'),
            backToLoginFromResetBtn: () => this.switchForm('login'),
            changePhoneBtn: () => this.switchForm('otpLogin')
        };

        Object.entries(buttons).forEach(([id, handler]) => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('click', (e) => {
                    e.preventDefault();
                    handler();
                });
            }
        });
    }

    /**
     * Initialize form switching
     */
    initializeFormSwitching() {
        this.forms = {
            login: document.getElementById('loginForm'),
            otpLogin: document.getElementById('otpLoginForm'),
            otpVerify: document.getElementById('otpVerifyForm'),
            forgotPassword: document.getElementById('forgotPasswordForm')
        };
    }

    /**
     * Initialize OTP inputs
     */
    initializeOtpInputs() {
        const otpInputs = document.querySelectorAll('.otp-input');
        
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                const value = e.target.value;
                
                // Only allow numbers
                if (!/^\d$/.test(value)) {
                    e.target.value = '';
                    return;
                }

                // Move to next input
                if (value && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }

                // Update input styling
                this.updateOtpInputStyling();
            });

            input.addEventListener('keydown', (e) => {
                // Handle backspace
                if (e.key === 'Backspace' && !input.value && index > 0) {
                    otpInputs[index - 1].focus();
                }

                // Handle paste
                if (e.key === 'v' && (e.ctrlKey || e.metaKey)) {
                    e.preventDefault();
                    this.handleOtpPaste(e);
                }
            });

            input.addEventListener('focus', () => {
                input.select();
            });
        });
    }

    /**
     * Handle OTP paste
     */
    handleOtpPaste(e) {
        const pasteData = (e.clipboardData || window.clipboardData).getData('text');
        const otpCode = pasteData.replace(/\D/g, '').slice(0, 6);
        
        if (otpCode.length === 6) {
            const otpInputs = document.querySelectorAll('.otp-input');
            otpCode.split('').forEach((digit, index) => {
                if (otpInputs[index]) {
                    otpInputs[index].value = digit;
                }
            });
            this.updateOtpInputStyling();
        }
    }

    /**
     * Update OTP input styling
     */
    updateOtpInputStyling() {
        const otpInputs = document.querySelectorAll('.otp-input');
        otpInputs.forEach(input => {
            input.classList.remove('filled', 'error');
            if (input.value) {
                input.classList.add('filled');
            }
        });
    }

    /**
     * Initialize password toggle
     */
    initializePasswordToggle() {
        const toggleButtons = document.querySelectorAll('.password-toggle');
        
        toggleButtons.forEach(button => {
            button.addEventListener('click', () => {
                const passwordInput = button.previousElementSibling;
                const icon = button.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    }

    /**
     * Setup form validation
     */
    setupFormValidation() {
        // Real-time validation
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });

        // Phone number formatting
        const phoneInput = document.getElementById('otpPhone');
        if (phoneInput) {
            phoneInput.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 10) {
                    value = value.slice(0, 10);
                }
                e.target.value = value;
            });
        }
    }

    /**
     * Switch between forms
     */
    switchForm(formName) {
        // Hide all forms
        Object.values(this.forms).forEach(form => {
            if (form) form.style.display = 'none';
        });

        // Show target form
        if (this.forms[formName]) {
            this.forms[formName].style.display = 'block';
            this.currentForm = formName;
            
            // Focus first input
            const firstInput = this.forms[formName].querySelector('.form-control');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }

        // Clear any existing errors
        this.clearAllErrors();
    }

    /**
     * Handle login form submission
     */
    async handleLogin(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const email = formData.get('email');
        const password = formData.get('password');
        const remember = formData.get('remember') === 'on';

        // Validate form
        if (!this.validateLoginForm(email, password)) {
            return;
        }

        this.setButtonLoading('loginBtn', true);

        try {
            const response = await this.apiCall('POST', '/auth/login', {
                email,
                password,
                remember
            });

            if (response.success) {
                this.showToast('Success', 'Login successful! Redirecting...', 'success');
                
                // Store user data
                this.storeUserData(response.user, response.token);
                
                // Redirect based on role
                setTimeout(() => {
                    this.redirectAfterLogin(response.user.role);
                }, 1500);
            } else {
                this.showToast('Error', response.message || 'Login failed', 'error');
            }
        } catch (error) {
            console.error('Login error:', error);
            this.showToast('Error', 'Login failed. Please try again.', 'error');
        } finally {
            this.setButtonLoading('loginBtn', false);
        }
    }

    /**
     * Handle OTP login form submission
     */
    async handleOtpLogin(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const phone = formData.get('phone');
        const countryCode = document.getElementById('countryCode').value;
        const fullPhone = countryCode + phone;

        // Validate phone number
        if (!this.validatePhoneNumber(phone)) {
            this.showFieldError('otpPhoneError', 'Please enter a valid 10-digit phone number');
            return;
        }

        this.setButtonLoading('sendOtpBtn', true);

        try {
            const response = await this.apiCall('POST', '/auth/send-otp', {
                phone: fullPhone
            });

            if (response.success) {
                this.showToast('Success', 'OTP sent successfully!', 'success');
                
                // Store phone number and switch to verification form
                this.currentPhone = fullPhone;
                document.getElementById('otpPhoneDisplay').textContent = fullPhone;
                this.switchForm('otpVerify');
                this.startOtpTimer();
            } else {
                this.showToast('Error', response.message || 'Failed to send OTP', 'error');
            }
        } catch (error) {
            console.error('OTP send error:', error);
            this.showToast('Error', 'Failed to send OTP. Please try again.', 'error');
        } finally {
            this.setButtonLoading('sendOtpBtn', false);
        }
    }

    /**
     * Handle OTP verification
     */
    async handleOtpVerification(e) {
        e.preventDefault();
        
        const otpInputs = document.querySelectorAll('.otp-input');
        const otp = Array.from(otpInputs).map(input => input.value).join('');

        // Validate OTP
        if (otp.length !== 6) {
            this.showFieldError('otpVerifyError', 'Please enter the complete 6-digit OTP');
            this.markOtpInputsError();
            return;
        }

        this.setButtonLoading('verifyOtpBtn', true);

        try {
            const response = await this.apiCall('POST', '/auth/verify-otp', {
                phone: this.currentPhone,
                otp: otp
            });

            if (response.success) {
                this.showToast('Success', 'OTP verified! Logging you in...', 'success');
                
                // Store user data
                this.storeUserData(response.user, response.token);
                
                // Redirect based on role
                setTimeout(() => {
                    this.redirectAfterLogin(response.user.role);
                }, 1500);
            } else {
                this.otpAttempts++;
                this.showFieldError('otpVerifyError', response.message || 'Invalid OTP');
                this.markOtpInputsError();
                
                // Clear OTP inputs
                otpInputs.forEach(input => input.value = '');
                otpInputs[0].focus();
                
                // Block after max attempts
                if (this.otpAttempts >= this.maxOtpAttempts) {
                    this.showToast('Error', 'Too many failed attempts. Please try again later.', 'error');
                    this.switchForm('login');
                }
            }
        } catch (error) {
            console.error('OTP verification error:', error);
            this.showToast('Error', 'OTP verification failed. Please try again.', 'error');
        } finally {
            this.setButtonLoading('verifyOtpBtn', false);
        }
    }

    /**
     * Handle forgot password
     */
    async handleForgotPassword(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const email = formData.get('email');

        // Validate email
        if (!this.validateEmail(email)) {
            this.showFieldError('resetEmailError', 'Please enter a valid email address');
            return;
        }

        this.setButtonLoading('resetPasswordBtn', true);

        try {
            const response = await this.apiCall('POST', '/auth/forgot-password', {
                email
            });

            if (response.success) {
                this.showToast('Success', 'Password reset link sent to your email!', 'success');
                this.switchForm('login');
            } else {
                this.showToast('Error', response.message || 'Failed to send reset link', 'error');
            }
        } catch (error) {
            console.error('Forgot password error:', error);
            this.showToast('Error', 'Failed to send reset link. Please try again.', 'error');
        } finally {
            this.setButtonLoading('resetPasswordBtn', false);
        }
    }

    /**
     * Start OTP timer
     */
    startOtpTimer() {
        this.otpTimeLeft = 60;
        const timerElement = document.getElementById('timerCount');
        const resendBtn = document.getElementById('resendOtpBtn');
        const timerText = document.getElementById('otpTimer');
        
        if (resendBtn) resendBtn.style.display = 'none';
        if (timerText) timerText.style.display = 'block';
        
        this.otpTimer = setInterval(() => {
            this.otpTimeLeft--;
            if (timerElement) {
                timerElement.textContent = this.otpTimeLeft;
            }
            
            if (this.otpTimeLeft <= 0) {
                clearInterval(this.otpTimer);
                if (resendBtn) resendBtn.style.display = 'inline-block';
                if (timerText) timerText.style.display = 'none';
            }
        }, 1000);
    }

    /**
     * Resend OTP
     */
    async resendOtp() {
        if (!this.currentPhone) return;

        try {
            const response = await this.apiCall('POST', '/auth/send-otp', {
                phone: this.currentPhone
            });

            if (response.success) {
                this.showToast('Success', 'OTP resent successfully!', 'success');
                this.startOtpTimer();
                this.otpAttempts = 0; // Reset attempts
                
                // Clear OTP inputs
                document.querySelectorAll('.otp-input').forEach(input => {
                    input.value = '';
                    input.classList.remove('filled', 'error');
                });
            } else {
                this.showToast('Error', response.message || 'Failed to resend OTP', 'error');
            }
        } catch (error) {
            console.error('Resend OTP error:', error);
            this.showToast('Error', 'Failed to resend OTP. Please try again.', 'error');
        }
    }

    /**
     * Validate login form
     */
    validateLoginForm(email, password) {
        let isValid = true;

        if (!this.validateEmail(email)) {
            this.showFieldError('loginEmailError', 'Please enter a valid email address');
            isValid = false;
        }

        if (!password || password.length < 6) {
            this.showFieldError('loginPasswordError', 'Password must be at least 6 characters long');
            isValid = false;
        }

        return isValid;
    }

    /**
     * Validate field
     */
    validateField(input) {
        const value = input.value.trim();
        const type = input.type;
        const name = input.name;

        switch (type) {
            case 'email':
                if (!this.validateEmail(value)) {
                    this.showFieldError(input.id + 'Error', 'Please enter a valid email address');
                    return false;
                }
                break;
            case 'password':
                if (value.length < 6) {
                    this.showFieldError(input.id + 'Error', 'Password must be at least 6 characters long');
                    return false;
                }
                break;
            case 'tel':
                if (name === 'phone' && !this.validatePhoneNumber(value)) {
                    this.showFieldError(input.id + 'Error', 'Please enter a valid 10-digit phone number');
                    return false;
                }
                break;
        }

        this.clearFieldError(input);
        return true;
    }

    /**
     * Validate email
     */
    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Validate phone number
     */
    validatePhoneNumber(phone) {
        const phoneRegex = /^[6-9]\d{9}$/;
        return phoneRegex.test(phone);
    }

    /**
     * Show field error
     */
    showFieldError(errorId, message) {
        const errorElement = document.getElementById(errorId);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.add('show');
        }
    }

    /**
     * Clear field error
     */
    clearFieldError(input) {
        const errorElement = document.getElementById(input.id + 'Error');
        if (errorElement) {
            errorElement.classList.remove('show');
        }
    }

    /**
     * Clear all errors
     */
    clearAllErrors() {
        document.querySelectorAll('.form-error').forEach(error => {
            error.classList.remove('show');
        });
    }

    /**
     * Mark OTP inputs as error
     */
    markOtpInputsError() {
        document.querySelectorAll('.otp-input').forEach(input => {
            input.classList.add('error');
        });
    }

    /**
     * Set button loading state
     */
    setButtonLoading(buttonId, loading) {
        const button = document.getElementById(buttonId);
        if (!button) return;

        const btnText = button.querySelector('.btn-text');
        const btnLoader = button.querySelector('.btn-loader');

        if (loading) {
            button.disabled = true;
            if (btnText) btnText.style.display = 'none';
            if (btnLoader) btnLoader.style.display = 'block';
        } else {
            button.disabled = false;
            if (btnText) btnText.style.display = 'block';
            if (btnLoader) btnLoader.style.display = 'none';
        }
    }

    /**
     * Store user data
     */
    storeUserData(user, token) {
        localStorage.setItem('bhrc_user', JSON.stringify(user));
        localStorage.setItem('bhrc_token', token);
        
        // Set token for future API calls
        this.authToken = token;
    }

    /**
     * Check existing session
     */
    async checkExistingSession() {
        const token = localStorage.getItem('bhrc_token');
        const user = localStorage.getItem('bhrc_user');

        if (token && user) {
            try {
                // Verify token with server
                const response = await this.apiCall('GET', '/auth/verify-token');
                
                if (response.success) {
                    // Token is valid, redirect to appropriate page
                    const userData = JSON.parse(user);
                    this.redirectAfterLogin(userData.role);
                } else {
                    // Token is invalid, clear storage
                    this.clearUserData();
                }
            } catch (error) {
                // Error verifying token, clear storage
                this.clearUserData();
            }
        }
    }

    /**
     * Clear user data
     */
    clearUserData() {
        localStorage.removeItem('bhrc_user');
        localStorage.removeItem('bhrc_token');
        this.authToken = null;
    }

    /**
     * Redirect after login
     */
    redirectAfterLogin(role) {
        const redirectUrl = new URLSearchParams(window.location.search).get('redirect');
        
        if (redirectUrl) {
            window.location.href = decodeURIComponent(redirectUrl);
        } else {
            // Default redirects based on role
            switch (role) {
                case 'admin':
                case 'super_admin':
                case 'moderator':
                    window.location.href = '/pages/admin/dashboard.html';
                    break;
                default:
                    window.location.href = '/dashboard.html';
            }
        }
    }

    /**
     * Show toast notification
     */
    showToast(title, message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) return;

        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas fa-${this.getToastIcon(type)}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="authManager.closeToast('${toastId}')">
                <i class="fas fa-times"></i>
            </button>
        `;

        toastContainer.appendChild(toast);
        
        // Show toast
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Auto remove after 5 seconds
        setTimeout(() => this.closeToast(toastId), 5000);
    }

    /**
     * Close toast
     */
    closeToast(toastId) {
        const toast = document.getElementById(toastId);
        if (toast) {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }
    }

    /**
     * Get toast icon
     */
    getToastIcon(type) {
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    /**
     * Show loading overlay
     */
    showLoading() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.classList.add('active');
        }
    }

    /**
     * Hide loading overlay
     */
    hideLoading() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.classList.remove('active');
        }
    }

    /**
     * Make API call
     */
    async apiCall(method, endpoint, data = null) {
        const url = this.apiBase + endpoint;
        const config = {
            method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        // Add auth token if available
        if (this.authToken) {
            config.headers['Authorization'] = `Bearer ${this.authToken}`;
        }

        if (data && method !== 'GET') {
            config.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const responseText = await response.text();
            
            // Check if response is valid JSON
            if (!responseText.trim()) {
                throw new Error('Empty response from server');
            }
            
            // Check if response starts with HTML (error page)
            if (responseText.trim().startsWith('<')) {
                console.error('Server returned HTML instead of JSON:', responseText);
                throw new Error('Server error - received HTML instead of JSON response');
            }
            
            try {
                return JSON.parse(responseText);
            } catch (jsonError) {
                console.error('JSON parse error:', jsonError);
                console.error('Response text:', responseText);
                throw new Error('Invalid JSON response from server');
            }
        } catch (error) {
            console.error('API call error:', error);
            throw error;
        }
    }
}

// Initialize auth manager when DOM is loaded
let authManager;
document.addEventListener('DOMContentLoaded', () => {
    authManager = new AuthManager();
});

// Export for global access
window.authManager = authManager;

// Utility functions for global access
window.closeToast = (toastId) => {
    if (authManager) {
        authManager.closeToast(toastId);
    }
};