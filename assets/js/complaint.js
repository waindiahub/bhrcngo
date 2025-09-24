/**
 * Complaint Form JavaScript
 * Handles form validation, file uploads, and submission
 */

class ComplaintForm {
    constructor() {
        this.form = document.getElementById('complaintForm');
        this.submitBtn = document.getElementById('submitBtn');
        this.fileInput = document.getElementById('documents');
        this.fileList = document.getElementById('file-list');
        this.uploadedFiles = [];
        this.maxFileSize = 5 * 1024 * 1024; // 5MB
        this.allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.populateStates();
        this.setupFormValidation();
    }
    
    setupEventListeners() {
        // Form submission
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // File upload
        this.fileInput.addEventListener('change', (e) => this.handleFileUpload(e));
        
        // State/District dependency
        document.getElementById('state').addEventListener('change', (e) => this.populateDistricts(e.target.value));
        
        // Real-time validation
        const inputs = this.form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearError(input));
        });
        
        // Phone number formatting
        document.getElementById('phone').addEventListener('input', (e) => this.formatPhoneNumber(e));
        
        // PIN code validation
        document.getElementById('pincode').addEventListener('input', (e) => this.validatePincode(e));
    }
    
    populateStates() {
        const stateSelect = document.getElementById('state');
        const states = [
            'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bharatiya', 'Chhattisgarh',
            'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand',
            'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur',
            'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab',
            'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura',
            'Uttar Pradesh', 'Uttarakhand', 'West Bengal', 'Delhi', 'Jammu and Kashmir',
            'Ladakh', 'Puducherry', 'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu',
            'Lakshadweep', 'Andaman and Nicobar Islands'
        ];
        
        states.forEach(state => {
            const option = document.createElement('option');
            option.value = state;
            option.textContent = state;
            stateSelect.appendChild(option);
        });
    }
    
    populateDistricts(state) {
        const districtSelect = document.getElementById('district');
        districtSelect.innerHTML = '<option value="">Select District</option>';
        
        // This would typically fetch from an API
        // For now, using a simplified approach
        const districts = this.getDistrictsByState(state);
        
        districts.forEach(district => {
            const option = document.createElement('option');
            option.value = district;
            option.textContent = district;
            districtSelect.appendChild(option);
        });
    }
    
    getDistrictsByState(state) {
        // Simplified district data - in production, this would come from an API
        const districtData = {
            'Delhi': ['Central Delhi', 'East Delhi', 'New Delhi', 'North Delhi', 'North East Delhi', 'North West Delhi', 'Shahdara', 'South Delhi', 'South East Delhi', 'South West Delhi', 'West Delhi'],
            'Maharashtra': ['Mumbai', 'Pune', 'Nagpur', 'Nashik', 'Aurangabad', 'Solapur', 'Amravati', 'Kolhapur', 'Sangli', 'Satara'],
            'Karnataka': ['Bangalore', 'Mysore', 'Hubli-Dharwad', 'Mangalore', 'Belgaum', 'Gulbarga', 'Davanagere', 'Bellary', 'Bijapur', 'Shimoga'],
            // Add more states and districts as needed
        };
        
        return districtData[state] || ['District 1', 'District 2', 'District 3']; // Fallback
    }
    
    setupFormValidation() {
        // Set minimum date for date of birth (18 years ago)
        const dobInput = document.getElementById('dob');
        const eighteenYearsAgo = new Date();
        eighteenYearsAgo.setFullYear(eighteenYearsAgo.getFullYear() - 18);
        dobInput.max = eighteenYearsAgo.toISOString().split('T')[0];
        
        // Set maximum date for incident date (today)
        const incidentDateInput = document.getElementById('date_of_incident');
        incidentDateInput.max = new Date().toISOString().split('T')[0];
    }
    
    validateField(field) {
        const fieldName = field.name;
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';
        
        // Required field validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        }
        
        // Specific field validations
        switch (fieldName) {
            case 'email':
                if (value && !this.validateEmail(value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid email address';
                }
                break;
                
            case 'phone':
                if (value && !this.validatePhone(value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid 10-digit phone number';
                }
                break;
                
            case 'pincode':
                if (value && !this.validatePincode(value)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid 6-digit PIN code';
                }
                break;
                
            case 'dob':
                if (value && !this.validateAge(value)) {
                    isValid = false;
                    errorMessage = 'You must be at least 18 years old';
                }
                break;
                
            case 'date_of_incident':
                if (value && new Date(value) > new Date()) {
                    isValid = false;
                    errorMessage = 'Incident date cannot be in the future';
                }
                break;
                
            case 'complaint_details':
                if (value && value.length < 50) {
                    isValid = false;
                    errorMessage = 'Please provide at least 50 characters describing the incident';
                }
                break;
        }
        
        this.showFieldError(field, isValid, errorMessage);
        return isValid;
    }
    
    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    validatePhone(phone) {
        const phoneRegex = /^[6-9]\d{9}$/;
        return phoneRegex.test(phone.replace(/\D/g, ''));
    }
    
    validatePincode(pincode) {
        const pincodeRegex = /^[1-9][0-9]{5}$/;
        return pincodeRegex.test(pincode);
    }
    
    validateAge(dob) {
        const birthDate = new Date(dob);
        const today = new Date();
        const age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            return age - 1 >= 18;
        }
        return age >= 18;
    }
    
    showFieldError(field, isValid, errorMessage) {
        const formGroup = field.closest('.form-group');
        const errorElement = formGroup.querySelector('.error-message');
        
        formGroup.classList.remove('error', 'success');
        
        if (!isValid) {
            formGroup.classList.add('error');
            errorElement.textContent = errorMessage;
        } else if (field.value.trim()) {
            formGroup.classList.add('success');
            errorElement.textContent = '';
        } else {
            errorElement.textContent = '';
        }
    }
    
    clearError(field) {
        const formGroup = field.closest('.form-group');
        const errorElement = formGroup.querySelector('.error-message');
        
        formGroup.classList.remove('error');
        if (field.value.trim()) {
            formGroup.classList.add('success');
        } else {
            formGroup.classList.remove('success');
        }
        errorElement.textContent = '';
    }
    
    formatPhoneNumber(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 10) {
            value = value.slice(0, 10);
        }
        e.target.value = value;
    }
    
    handleFileUpload(e) {
        const files = Array.from(e.target.files);
        
        files.forEach(file => {
            if (this.validateFile(file)) {
                this.uploadedFiles.push(file);
                this.displayFile(file);
            }
        });
        
        // Clear the input to allow re-uploading the same file
        e.target.value = '';
    }
    
    validateFile(file) {
        // Check file size
        if (file.size > this.maxFileSize) {
            this.showAlert('File size should not exceed 5MB', 'error');
            return false;
        }
        
        // Check file type
        const fileExtension = file.name.split('.').pop().toLowerCase();
        if (!this.allowedTypes.includes(fileExtension)) {
            this.showAlert('Invalid file type. Allowed types: ' + this.allowedTypes.join(', '), 'error');
            return false;
        }
        
        // Check if file already exists
        if (this.uploadedFiles.some(f => f.name === file.name && f.size === file.size)) {
            this.showAlert('File already uploaded', 'warning');
            return false;
        }
        
        return true;
    }
    
    displayFile(file) {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item';
        fileItem.innerHTML = `
            <div>
                <span class="file-name">${file.name}</span>
                <span class="file-size">(${this.formatFileSize(file.size)})</span>
            </div>
            <button type="button" class="remove-file" onclick="complaintForm.removeFile('${file.name}', this)">×</button>
        `;
        
        this.fileList.appendChild(fileItem);
    }
    
    removeFile(fileName, button) {
        this.uploadedFiles = this.uploadedFiles.filter(file => file.name !== fileName);
        button.closest('.file-item').remove();
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        
        if (!this.validateForm()) {
            this.showAlert('Please correct the errors in the form', 'error');
            return;
        }
        
        this.setLoading(true);
        
        try {
            const formData = new FormData();
            
            // Add form fields
            const formFields = new FormData(this.form);
            for (let [key, value] of formFields.entries()) {
                formData.append(key, value);
            }
            
            // Add uploaded files
            this.uploadedFiles.forEach((file, index) => {
                formData.append(`documents[${index}]`, file);
            });
            
            // Add action for backend routing
            formData.append('action', 'submit_complaint');
            
            const response = await fetch(window.BHRC_CONFIG.getBackendUrl(window.BHRC_CONFIG.API.ENDPOINTS.AJAX), {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccessMessage(result.message, result.complaint_id);
                this.resetForm();
            } else {
                this.showAlert(result.message || 'An error occurred while submitting your complaint', 'error');
            }
            
        } catch (error) {
            console.error('Submission error:', error);
            this.showAlert('Network error. Please check your connection and try again.', 'error');
        } finally {
            this.setLoading(false);
        }
    }
    
    validateForm() {
        const inputs = this.form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        // Check checkboxes
        const termsCheckbox = document.getElementById('terms');
        const consentCheckbox = document.getElementById('consent');
        
        if (!termsCheckbox.checked) {
            this.showFieldError(termsCheckbox, false, 'You must agree to the terms and conditions');
            isValid = false;
        }
        
        if (!consentCheckbox.checked) {
            this.showFieldError(consentCheckbox, false, 'You must provide consent for data processing');
            isValid = false;
        }
        
        return isValid;
    }
    
    setLoading(loading) {
        const btnText = this.submitBtn.querySelector('.btn-text');
        const btnLoader = this.submitBtn.querySelector('.btn-loader');
        
        if (loading) {
            btnText.style.display = 'none';
            btnLoader.style.display = 'flex';
            this.submitBtn.disabled = true;
            this.form.classList.add('form-loading');
        } else {
            btnText.style.display = 'block';
            btnLoader.style.display = 'none';
            this.submitBtn.disabled = false;
            this.form.classList.remove('form-loading');
        }
    }
    
    showAlert(message, type = 'info') {
        // Create alert element
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <span>${message}</span>
            <button type="button" class="alert-close" onclick="this.parentElement.remove()">×</button>
        `;
        
        // Insert at top of form
        this.form.insertBefore(alert, this.form.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alert.parentElement) {
                alert.remove();
            }
        }, 5000);
        
        // Scroll to top
        alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    showSuccessMessage(message, complaintId) {
        const successHtml = `
            <div class="success-message show">
                <h3>Complaint Submitted Successfully!</h3>
                <p>${message}</p>
                <p><strong>Your Complaint ID: ${complaintId}</strong></p>
                <p>Please save this ID for future reference. You will receive a confirmation email shortly.</p>
            </div>
        `;
        
        this.form.innerHTML = successHtml;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    resetForm() {
        this.form.reset();
        this.uploadedFiles = [];
        this.fileList.innerHTML = '';
        
        // Clear all validation states
        const formGroups = this.form.querySelectorAll('.form-group');
        formGroups.forEach(group => {
            group.classList.remove('error', 'success');
            const errorElement = group.querySelector('.error-message');
            if (errorElement) {
                errorElement.textContent = '';
            }
        });
        
        // Reset district dropdown
        document.getElementById('district').innerHTML = '<option value="">Select District</option>';
    }
}

// Utility functions
const ComplaintUtils = {
    // Generate complaint ID (this would typically be done on the backend)
    generateComplaintId() {
        const timestamp = Date.now();
        const random = Math.floor(Math.random() * 1000);
        return `BHRC${timestamp}${random}`;
    },
    
    // Format date for display
    formatDate(date) {
        return new Date(date).toLocaleDateString('en-IN', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    },
    
    // Sanitize input
    sanitizeInput(input) {
        const div = document.createElement('div');
        div.textContent = input;
        return div.innerHTML;
    }
};

// Initialize complaint form when DOM is loaded
let complaintForm;
document.addEventListener('DOMContentLoaded', () => {
    complaintForm = new ComplaintForm();
});

// Add CSS for alerts
const alertStyles = `
    .alert {
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        animation: slideDown 0.3s ease-out;
    }
    
    .alert-info {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    
    .alert-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .alert-warning {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
    
    .alert-close {
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0;
        margin-left: 1rem;
    }
`;

// Inject alert styles
const styleSheet = document.createElement('style');
styleSheet.textContent = alertStyles;
document.head.appendChild(styleSheet);