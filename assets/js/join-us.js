// Join Us Page JavaScript

class MembershipManager {
    constructor() {
        this.form = document.getElementById('membershipForm');
        this.membershipCards = document.querySelectorAll('.membership-card');
        this.membershipTypeInput = document.getElementById('membershipType');
        this.paymentSection = document.getElementById('paymentSection');
        this.membershipFee = document.getElementById('membershipFee');
        this.resetButton = document.getElementById('resetForm');
        
        this.membershipTypes = {
            general: { name: 'General Member', fee: 0 },
            active: { name: 'Active Member', fee: 500 },
            life: { name: 'Life Member', fee: 5000 }
        };
        
        this.validator = new FormValidator();
        this.fileUploader = new FileUploader();
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.setupFormValidation();
        this.setupFileUpload();
        this.initializeMembershipSelection();
    }
    
    setupEventListeners() {
        // Membership card selection
        this.membershipCards.forEach(card => {
            const selectBtn = card.querySelector('.btn-select');
            selectBtn.addEventListener('click', (e) => {
                const type = card.dataset.type;
                this.selectMembershipType(type);
            });
        });
        
        // Form submission
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleFormSubmission();
        });
        
        // Reset form
        this.resetButton.addEventListener('click', () => {
            this.resetForm();
        });
        
        // Real-time validation
        const inputs = this.form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => {
                this.validator.validateField(input);
            });
            
            input.addEventListener('input', () => {
                this.clearFieldError(input);
            });
        });
        
        // Phone number formatting
        const phoneInput = document.getElementById('phone');
        phoneInput.addEventListener('input', (e) => {
            this.formatPhoneNumber(e.target);
        });
        
        // PIN code validation
        const pincodeInput = document.getElementById('pincode');
        pincodeInput.addEventListener('input', (e) => {
            this.validatePincode(e.target);
        });
        
        // Age calculation
        const dobInput = document.getElementById('dateOfBirth');
        dobInput.addEventListener('change', (e) => {
            this.validateAge(e.target);
        });
    }
    
    setupFormValidation() {
        // Custom validation rules
        this.validator.addRule('phone', (value) => {
            const phoneRegex = /^[6-9]\d{9}$/;
            return phoneRegex.test(value.replace(/\D/g, ''));
        }, 'Please enter a valid 10-digit mobile number');
        
        this.validator.addRule('pincode', (value) => {
            const pincodeRegex = /^[1-9][0-9]{5}$/;
            return pincodeRegex.test(value);
        }, 'Please enter a valid 6-digit PIN code');
        
        this.validator.addRule('age', (value) => {
            const today = new Date();
            const birthDate = new Date(value);
            const age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            return age >= 18;
        }, 'You must be at least 18 years old to apply');
    }
    
    setupFileUpload() {
        const fileInputs = this.form.querySelectorAll('input[type="file"]');
        
        fileInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                this.handleFileUpload(e.target);
            });
        });
    }
    
    initializeMembershipSelection() {
        // Select general membership by default
        this.selectMembershipType('general');
    }
    
    selectMembershipType(type) {
        // Update UI
        this.membershipCards.forEach(card => {
            const btn = card.querySelector('.btn-select');
            if (card.dataset.type === type) {
                btn.classList.add('selected');
                btn.textContent = 'Selected';
            } else {
                btn.classList.remove('selected');
                btn.textContent = 'Select Plan';
            }
        });
        
        // Update form
        this.membershipTypeInput.value = type;
        
        // Update payment section
        const membershipInfo = this.membershipTypes[type];
        this.membershipFee.textContent = `₹${membershipInfo.fee}`;
        
        if (membershipInfo.fee > 0) {
            this.paymentSection.style.display = 'block';
        } else {
            this.paymentSection.style.display = 'none';
        }
    }
    
    formatPhoneNumber(input) {
        let value = input.value.replace(/\D/g, '');
        if (value.length > 10) {
            value = value.slice(0, 10);
        }
        input.value = value;
    }
    
    validatePincode(input) {
        const value = input.value.replace(/\D/g, '');
        if (value.length > 6) {
            input.value = value.slice(0, 6);
        } else {
            input.value = value;
        }
    }
    
    validateAge(input) {
        const today = new Date();
        const birthDate = new Date(input.value);
        const age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        let calculatedAge = age;
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            calculatedAge--;
        }
        
        if (calculatedAge < 18) {
            this.showFieldError(input, 'You must be at least 18 years old to apply');
        } else if (calculatedAge > 100) {
            this.showFieldError(input, 'Please enter a valid date of birth');
        } else {
            this.clearFieldError(input);
        }
    }
    
    handleFileUpload(input) {
        const file = input.files[0];
        if (!file) return;
        
        const maxSizes = {
            photo: 2 * 1024 * 1024, // 2MB
            id_proof: 5 * 1024 * 1024, // 5MB
            resume: 5 * 1024 * 1024 // 5MB
        };
        
        const allowedTypes = {
            photo: ['image/jpeg', 'image/png', 'image/jpg'],
            id_proof: ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'],
            resume: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        };
        
        const fieldName = input.name;
        const maxSize = maxSizes[fieldName];
        const allowedFileTypes = allowedTypes[fieldName];
        
        // Check file size
        if (file.size > maxSize) {
            this.showFieldError(input, `File size should not exceed ${this.formatFileSize(maxSize)}`);
            input.value = '';
            return;
        }
        
        // Check file type
        if (!allowedFileTypes.includes(file.type)) {
            this.showFieldError(input, 'Please select a valid file type');
            input.value = '';
            return;
        }
        
        this.clearFieldError(input);
        
        // Show file preview for images
        if (fieldName === 'photo' && file.type.startsWith('image/')) {
            this.showImagePreview(input, file);
        }
    }
    
    showImagePreview(input, file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            let preview = input.parentNode.querySelector('.image-preview');
            if (!preview) {
                preview = document.createElement('div');
                preview.className = 'image-preview';
                input.parentNode.appendChild(preview);
            }
            
            preview.innerHTML = `
                <img src="${e.target.result}" alt="Preview" style="max-width: 100px; max-height: 100px; border-radius: 8px; margin-top: 10px;">
                <button type="button" class="remove-preview" onclick="this.parentNode.remove(); document.getElementById('${input.id}').value = '';">×</button>
            `;
        };
        reader.readAsDataURL(file);
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    async handleFormSubmission() {
        // Show loading state
        const submitBtn = this.form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Submitting...';
        submitBtn.disabled = true;
        submitBtn.classList.add('loading');
        
        try {
            // Validate form
            if (!this.validateForm()) {
                throw new Error('Please fix the errors in the form');
            }
            
            // Prepare form data
            const formData = new FormData(this.form);
            
            // Add additional data
            formData.append('action', 'submit_membership');
            formData.append('timestamp', new Date().toISOString());
            
            // Submit form
            const response = await fetch('/backend/controllers/MembershipController.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccessMessage(result.message || 'Membership application submitted successfully!');
                this.resetForm();
                
                // Redirect to success page or show next steps
                if (result.redirect) {
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 2000);
                }
            } else {
                throw new Error(result.message || 'Failed to submit application');
            }
            
        } catch (error) {
            console.error('Form submission error:', error);
            this.showErrorMessage(error.message || 'An error occurred while submitting the form');
        } finally {
            // Reset button state
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            submitBtn.classList.remove('loading');
        }
    }
    
    validateForm() {
        let isValid = true;
        const requiredFields = this.form.querySelectorAll('[required]');
        
        // Clear previous alerts
        this.clearAlerts();
        
        // Validate required fields
        requiredFields.forEach(field => {
            if (!this.validator.validateField(field)) {
                isValid = false;
            }
        });
        
        // Validate interests (at least one should be selected)
        const interests = this.form.querySelectorAll('input[name="interests[]"]:checked');
        if (interests.length === 0) {
            this.showErrorMessage('Please select at least one area of interest');
            isValid = false;
        }
        
        // Validate terms acceptance
        const termsCheckbox = document.getElementById('terms');
        const declarationCheckbox = document.getElementById('declaration');
        
        if (!termsCheckbox.checked) {
            this.showFieldError(termsCheckbox, 'You must accept the terms and conditions');
            isValid = false;
        }
        
        if (!declarationCheckbox.checked) {
            this.showFieldError(declarationCheckbox, 'You must confirm the declaration');
            isValid = false;
        }
        
        return isValid;
    }
    
    showFieldError(field, message) {
        this.clearFieldError(field);
        
        field.classList.add('error');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
    }
    
    clearFieldError(field) {
        field.classList.remove('error');
        field.classList.add('success');
        
        const existingError = field.parentNode.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
    }
    
    showSuccessMessage(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-success';
        alert.textContent = message;
        
        this.form.insertBefore(alert, this.form.firstChild);
        
        // Scroll to top of form
        this.form.scrollIntoView({ behavior: 'smooth' });
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
    
    showErrorMessage(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-error';
        alert.textContent = message;
        
        this.form.insertBefore(alert, this.form.firstChild);
        
        // Scroll to top of form
        this.form.scrollIntoView({ behavior: 'smooth' });
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
    
    clearAlerts() {
        const alerts = this.form.querySelectorAll('.alert');
        alerts.forEach(alert => alert.remove());
    }
    
    resetForm() {
        // Reset form fields
        this.form.reset();
        
        // Clear validation states
        const fields = this.form.querySelectorAll('input, select, textarea');
        fields.forEach(field => {
            field.classList.remove('error', 'success');
        });
        
        // Clear error messages
        const errorMessages = this.form.querySelectorAll('.error-message');
        errorMessages.forEach(msg => msg.remove());
        
        // Clear image previews
        const previews = this.form.querySelectorAll('.image-preview');
        previews.forEach(preview => preview.remove());
        
        // Clear alerts
        this.clearAlerts();
        
        // Reset membership selection
        this.initializeMembershipSelection();
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// Form Validator Class
class FormValidator {
    constructor() {
        this.rules = {};
    }
    
    addRule(name, validator, message) {
        this.rules[name] = { validator, message };
    }
    
    validateField(field) {
        const value = field.value.trim();
        const fieldName = field.name;
        const fieldType = field.type;
        
        // Required field validation
        if (field.hasAttribute('required') && !value) {
            this.showError(field, 'This field is required');
            return false;
        }
        
        // Skip further validation if field is empty and not required
        if (!value && !field.hasAttribute('required')) {
            this.clearError(field);
            return true;
        }
        
        // Email validation
        if (fieldType === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                this.showError(field, 'Please enter a valid email address');
                return false;
            }
        }
        
        // Custom rule validation
        if (this.rules[fieldName]) {
            const rule = this.rules[fieldName];
            if (!rule.validator(value)) {
                this.showError(field, rule.message);
                return false;
            }
        }
        
        // Age validation for date of birth
        if (fieldName === 'date_of_birth') {
            const rule = this.rules['age'];
            if (rule && !rule.validator(value)) {
                this.showError(field, rule.message);
                return false;
            }
        }
        
        this.clearError(field);
        return true;
    }
    
    showError(field, message) {
        field.classList.add('error');
        field.classList.remove('success');
        
        // Remove existing error message
        const existingError = field.parentNode.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
        
        // Add new error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }
    
    clearError(field) {
        field.classList.remove('error');
        field.classList.add('success');
        
        const existingError = field.parentNode.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
    }
}

// File Uploader Class
class FileUploader {
    constructor() {
        this.maxSizes = {
            photo: 2 * 1024 * 1024, // 2MB
            id_proof: 5 * 1024 * 1024, // 5MB
            resume: 5 * 1024 * 1024 // 5MB
        };
        
        this.allowedTypes = {
            photo: ['image/jpeg', 'image/png', 'image/jpg'],
            id_proof: ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'],
            resume: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        };
    }
    
    validateFile(file, fieldName) {
        const maxSize = this.maxSizes[fieldName];
        const allowedTypes = this.allowedTypes[fieldName];
        
        if (!file) {
            return { valid: false, message: 'No file selected' };
        }
        
        if (file.size > maxSize) {
            return { 
                valid: false, 
                message: `File size should not exceed ${this.formatFileSize(maxSize)}` 
            };
        }
        
        if (!allowedTypes.includes(file.type)) {
            return { 
                valid: false, 
                message: 'Please select a valid file type' 
            };
        }
        
        return { valid: true };
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
}

// Utility Functions
function scrollToSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (section) {
        section.scrollIntoView({ behavior: 'smooth' });
    }
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    // Style the toast
    Object.assign(toast.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '12px 20px',
        borderRadius: '8px',
        color: 'white',
        fontWeight: '500',
        zIndex: '10000',
        transform: 'translateX(100%)',
        transition: 'transform 0.3s ease'
    });
    
    // Set background color based on type
    const colors = {
        success: '#28a745',
        error: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };
    toast.style.backgroundColor = colors[type] || colors.info;
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto remove
    setTimeout(() => {
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize membership manager
    const membershipManager = new MembershipManager();
    
    // Initialize smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            scrollToSection(targetId);
        });
    });
    
    // Initialize back to top button
    const backToTopBtn = document.getElementById('backToTop');
    if (backToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.style.display = 'block';
            } else {
                backToTopBtn.style.display = 'none';
            }
        });
        
        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
    
    // Initialize navigation dropdown
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const dropdown = this.parentNode;
            dropdown.classList.toggle('active');
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.nav-dropdown')) {
            const activeDropdowns = document.querySelectorAll('.nav-dropdown.active');
            activeDropdowns.forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });
    
    // Mobile navigation toggle
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            navToggle.classList.toggle('active');
        });
    }
    
    console.log('Join Us page initialized successfully');
});