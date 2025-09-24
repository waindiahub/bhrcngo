/**
 * Comprehensive Form Validation Library
 * Handles validation for all forms across the BHRC website
 */

class FormValidator {
    constructor() {
        this.rules = {};
        this.messages = {};
        this.customValidators = {};
        this.init();
    }

    /**
     * Initialize form validator
     */
    init() {
        this.setupDefaultRules();
        this.setupDefaultMessages();
        this.setupCustomValidators();
        this.initializeFormValidation();
    }

    /**
     * Setup default validation rules
     */
    setupDefaultRules() {
        this.rules = {
            required: (value) => value !== null && value !== undefined && value.toString().trim() !== '',
            email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
            phone: (value) => /^[6-9]\d{9}$/.test(value),
            aadhar: (value) => /^\d{12}$/.test(value.replace(/\s/g, '')),
            pan: (value) => /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(value.toUpperCase()),
            pincode: (value) => /^\d{6}$/.test(value),
            password: (value) => value.length >= 8 && /(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(value),
            url: (value) => {
                try {
                    new URL(value);
                    return true;
                } catch {
                    return false;
                }
            },
            date: (value) => {
                const date = new Date(value);
                return date instanceof Date && !isNaN(date);
            },
            number: (value) => !isNaN(value) && isFinite(value),
            min: (value, min) => parseFloat(value) >= parseFloat(min),
            max: (value, max) => parseFloat(value) <= parseFloat(max),
            minLength: (value, length) => value.toString().length >= parseInt(length),
            maxLength: (value, length) => value.toString().length <= parseInt(length),
            alpha: (value) => /^[a-zA-Z\s]+$/.test(value),
            alphaNumeric: (value) => /^[a-zA-Z0-9\s]+$/.test(value),
            numeric: (value) => /^\d+$/.test(value)
        };
    }

    /**
     * Setup default error messages
     */
    setupDefaultMessages() {
        this.messages = {
            required: 'This field is required',
            email: 'Please enter a valid email address',
            phone: 'Please enter a valid 10-digit phone number',
            aadhar: 'Please enter a valid 12-digit Aadhar number',
            pan: 'Please enter a valid PAN number (e.g., ABCDE1234F)',
            pincode: 'Please enter a valid 6-digit PIN code',
            password: 'Password must be at least 8 characters with uppercase, lowercase, and number',
            url: 'Please enter a valid URL',
            date: 'Please enter a valid date',
            number: 'Please enter a valid number',
            min: 'Value must be at least {min}',
            max: 'Value must be at most {max}',
            minLength: 'Must be at least {length} characters long',
            maxLength: 'Must be at most {length} characters long',
            alpha: 'Only letters and spaces are allowed',
            alphaNumeric: 'Only letters, numbers, and spaces are allowed',
            numeric: 'Only numbers are allowed'
        };
    }

    /**
     * Setup custom validators
     */
    setupCustomValidators() {
        this.customValidators = {
            // Validate Indian mobile number with country code
            indianMobile: (value) => {
                const cleaned = value.replace(/[\s\-\(\)]/g, '');
                return /^(\+91|91)?[6-9]\d{9}$/.test(cleaned);
            },

            // Validate strong password
            strongPassword: (value) => {
                return value.length >= 8 &&
                       /(?=.*[a-z])/.test(value) &&
                       /(?=.*[A-Z])/.test(value) &&
                       /(?=.*\d)/.test(value) &&
                       /(?=.*[@$!%*?&])/.test(value);
            },

            // Validate age (18-100)
            validAge: (value) => {
                const age = parseInt(value);
                return age >= 18 && age <= 100;
            },

            // Validate date of birth (not future, reasonable age)
            validDOB: (value) => {
                const dob = new Date(value);
                const today = new Date();
                const age = today.getFullYear() - dob.getFullYear();
                return dob <= today && age >= 0 && age <= 120;
            },

            // Validate file size (in MB)
            fileSize: (file, maxSizeMB) => {
                if (!file) return true;
                return file.size <= maxSizeMB * 1024 * 1024;
            },

            // Validate file type
            fileType: (file, allowedTypes) => {
                if (!file) return true;
                return allowedTypes.includes(file.type);
            },

            // Validate complaint category
            complaintCategory: (value) => {
                const validCategories = [
                    'human_rights_violation',
                    'discrimination',
                    'police_brutality',
                    'custodial_violence',
                    'women_rights',
                    'child_rights',
                    'disability_rights',
                    'other'
                ];
                return validCategories.includes(value);
            },

            // Validate event type
            eventType: (value) => {
                const validTypes = ['workshop', 'seminar', 'training', 'awareness', 'legal_aid', 'other'];
                return validTypes.includes(value);
            },

            // Validate donation amount
            donationAmount: (value) => {
                const amount = parseFloat(value);
                return amount >= 1 && amount <= 1000000;
            }
        };
    }

    /**
     * Initialize form validation for all forms
     */
    initializeFormValidation() {
        // Auto-initialize validation on page load
        document.addEventListener('DOMContentLoaded', () => {
            this.initializeAllForms();
        });
    }

    /**
     * Initialize all forms on the page
     */
    initializeAllForms() {
        const forms = document.querySelectorAll('form[data-validate]');
        forms.forEach(form => this.initializeForm(form));
    }

    /**
     * Initialize validation for a specific form
     */
    initializeForm(form) {
        if (!form) return;

        // Add real-time validation
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            // Validate on blur
            input.addEventListener('blur', () => {
                this.validateField(input);
            });

            // Clear errors on input
            input.addEventListener('input', () => {
                this.clearFieldError(input);
                
                // Real-time validation for certain fields
                if (input.dataset.realtime === 'true') {
                    setTimeout(() => this.validateField(input), 500);
                }
            });

            // Format certain fields
            this.setupFieldFormatting(input);
        });

        // Validate on form submission
        form.addEventListener('submit', (e) => {
            if (!this.validateForm(form)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    }

    /**
     * Setup field formatting
     */
    setupFieldFormatting(input) {
        const type = input.dataset.format;
        
        switch (type) {
            case 'phone':
                input.addEventListener('input', (e) => {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 10) value = value.slice(0, 10);
                    e.target.value = value;
                });
                break;

            case 'aadhar':
                input.addEventListener('input', (e) => {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 12) value = value.slice(0, 12);
                    // Format as XXXX XXXX XXXX
                    value = value.replace(/(\d{4})(\d{4})(\d{4})/, '$1 $2 $3');
                    e.target.value = value;
                });
                break;

            case 'pan':
                input.addEventListener('input', (e) => {
                    let value = e.target.value.toUpperCase();
                    value = value.replace(/[^A-Z0-9]/g, '');
                    if (value.length > 10) value = value.slice(0, 10);
                    e.target.value = value;
                });
                break;

            case 'pincode':
                input.addEventListener('input', (e) => {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 6) value = value.slice(0, 6);
                    e.target.value = value;
                });
                break;

            case 'currency':
                input.addEventListener('input', (e) => {
                    let value = e.target.value.replace(/[^\d.]/g, '');
                    // Ensure only one decimal point
                    const parts = value.split('.');
                    if (parts.length > 2) {
                        value = parts[0] + '.' + parts.slice(1).join('');
                    }
                    e.target.value = value;
                });
                break;
        }
    }

    /**
     * Validate entire form
     */
    validateForm(form) {
        let isValid = true;
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        // Scroll to first error
        if (!isValid) {
            const firstError = form.querySelector('.form-error.show');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        return isValid;
    }

    /**
     * Validate individual field
     */
    validateField(input) {
        if (!input.dataset.rules) return true;

        const rules = input.dataset.rules.split('|');
        const value = input.value;
        let isValid = true;

        for (const rule of rules) {
            const [ruleName, ...params] = rule.split(':');
            const param = params.join(':');

            if (!this.applyRule(value, ruleName, param, input)) {
                this.showFieldError(input, ruleName, param);
                isValid = false;
                break;
            }
        }

        if (isValid) {
            this.clearFieldError(input);
        }

        return isValid;
    }

    /**
     * Apply validation rule
     */
    applyRule(value, ruleName, param, input) {
        // Skip validation for empty non-required fields
        if (!value && ruleName !== 'required') {
            return true;
        }

        // Check custom validators first
        if (this.customValidators[ruleName]) {
            return param ? 
                this.customValidators[ruleName](value, param, input) :
                this.customValidators[ruleName](value, input);
        }

        // Check built-in rules
        if (this.rules[ruleName]) {
            return param ? 
                this.rules[ruleName](value, param) :
                this.rules[ruleName](value);
        }

        // Special cases
        switch (ruleName) {
            case 'confirmed':
                const confirmField = document.querySelector(`[name="${param}"]`);
                return confirmField && value === confirmField.value;

            case 'unique':
                // This would typically check against a database
                // For now, return true (implement server-side validation)
                return true;

            case 'exists':
                // This would typically check if value exists in database
                // For now, return true (implement server-side validation)
                return true;

            default:
                console.warn(`Unknown validation rule: ${ruleName}`);
                return true;
        }
    }

    /**
     * Show field error
     */
    showFieldError(input, ruleName, param) {
        const errorContainer = this.getErrorContainer(input);
        if (!errorContainer) return;

        let message = this.messages[ruleName] || 'Invalid value';
        
        // Replace placeholders in message
        if (param) {
            message = message.replace(`{${ruleName}}`, param);
            message = message.replace('{min}', param);
            message = message.replace('{max}', param);
            message = message.replace('{length}', param);
        }

        // Custom messages from data attribute
        if (input.dataset.message) {
            const customMessages = JSON.parse(input.dataset.message);
            if (customMessages[ruleName]) {
                message = customMessages[ruleName];
            }
        }

        errorContainer.textContent = message;
        errorContainer.classList.add('show');
        input.classList.add('error');
    }

    /**
     * Clear field error
     */
    clearFieldError(input) {
        const errorContainer = this.getErrorContainer(input);
        if (errorContainer) {
            errorContainer.classList.remove('show');
        }
        input.classList.remove('error');
    }

    /**
     * Get error container for input
     */
    getErrorContainer(input) {
        // Look for error container by ID
        let errorContainer = document.getElementById(input.id + 'Error');
        
        // Look for error container by class
        if (!errorContainer) {
            const parent = input.closest('.form-group') || input.closest('.input-group');
            if (parent) {
                errorContainer = parent.querySelector('.form-error');
            }
        }

        // Create error container if not found
        if (!errorContainer) {
            errorContainer = document.createElement('div');
            errorContainer.className = 'form-error';
            errorContainer.id = input.id + 'Error';
            
            const parent = input.closest('.form-group') || input.closest('.input-group') || input.parentNode;
            parent.appendChild(errorContainer);
        }

        return errorContainer;
    }

    /**
     * Add custom validator
     */
    addValidator(name, validator, message) {
        this.customValidators[name] = validator;
        if (message) {
            this.messages[name] = message;
        }
    }

    /**
     * Validate specific value against rules
     */
    validate(value, rules) {
        const ruleArray = rules.split('|');
        
        for (const rule of ruleArray) {
            const [ruleName, param] = rule.split(':');
            
            if (!this.applyRule(value, ruleName, param)) {
                return {
                    valid: false,
                    message: this.messages[ruleName] || 'Invalid value'
                };
            }
        }

        return { valid: true };
    }

    /**
     * Sanitize input value
     */
    sanitize(value, type = 'text') {
        if (typeof value !== 'string') return value;

        switch (type) {
            case 'html':
                return value
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#x27;');

            case 'sql':
                return value.replace(/['";\\]/g, '\\$&');

            case 'email':
                return value.toLowerCase().trim();

            case 'phone':
                return value.replace(/\D/g, '');

            case 'name':
                return value.trim().replace(/\s+/g, ' ');

            case 'url':
                return value.trim().toLowerCase();

            default:
                return value.trim();
        }
    }

    /**
     * Get form data with validation
     */
    getFormData(form, validate = true) {
        const formData = new FormData(form);
        const data = {};
        const errors = {};

        for (const [key, value] of formData.entries()) {
            const input = form.querySelector(`[name="${key}"]`);
            
            if (validate && input && !this.validateField(input)) {
                const errorContainer = this.getErrorContainer(input);
                if (errorContainer && errorContainer.textContent) {
                    errors[key] = errorContainer.textContent;
                }
            }

            // Sanitize value
            const sanitizeType = input?.dataset.sanitize || 'text';
            data[key] = this.sanitize(value, sanitizeType);
        }

        return {
            data,
            errors,
            isValid: Object.keys(errors).length === 0
        };
    }

    /**
     * Show form success message
     */
    showFormSuccess(form, message) {
        const successContainer = form.querySelector('.form-success') || this.createSuccessContainer(form);
        successContainer.textContent = message;
        successContainer.classList.add('show');
        
        // Hide after 5 seconds
        setTimeout(() => {
            successContainer.classList.remove('show');
        }, 5000);
    }

    /**
     * Create success container
     */
    createSuccessContainer(form) {
        const container = document.createElement('div');
        container.className = 'form-success';
        form.insertBefore(container, form.firstChild);
        return container;
    }

    /**
     * Reset form validation
     */
    resetForm(form) {
        // Clear all errors
        form.querySelectorAll('.form-error').forEach(error => {
            error.classList.remove('show');
        });

        // Remove error classes
        form.querySelectorAll('.error').forEach(input => {
            input.classList.remove('error');
        });

        // Hide success message
        const successContainer = form.querySelector('.form-success');
        if (successContainer) {
            successContainer.classList.remove('show');
        }
    }
}

// Initialize form validator
const formValidator = new FormValidator();

// Export for global access
window.FormValidator = FormValidator;
window.formValidator = formValidator;

// Utility functions
window.validateField = (input) => formValidator.validateField(input);
window.validateForm = (form) => formValidator.validateForm(form);
window.sanitizeInput = (value, type) => formValidator.sanitize(value, type);

// Auto-initialize forms with data-validate attribute
document.addEventListener('DOMContentLoaded', () => {
    formValidator.initializeAllForms();
});