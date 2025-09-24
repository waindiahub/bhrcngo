<?php
/**
 * Input Validator
 * BHRC - Bharatiya Human Rights Council
 */

require_once __DIR__ . '/../middleware/SecurityMiddleware.php';

class InputValidator {
    
    private $errors = [];
    private $data = [];
    
    /**
     * Validate user registration data
     */
    public function validateUserRegistration($data) {
        $this->errors = [];
        $this->data = $data;
        
        // Name validation
        $this->validateRequired('name', 'Name is required');
        $this->validateString('name', 2, 100, 'Name must be between 2 and 100 characters');
        $this->validateAlpha('name', 'Name can only contain letters and spaces');
        
        // Email validation
        $this->validateRequired('email', 'Email is required');
        $this->validateEmail('email', 'Please enter a valid email address');
        
        // Phone validation
        $this->validateRequired('phone', 'Phone number is required');
        $this->validatePhone('phone', 'Please enter a valid phone number');
        
        // Password validation
        $this->validateRequired('password', 'Password is required');
        $this->validatePassword('password');
        
        // Confirm password
        $this->validateRequired('confirm_password', 'Please confirm your password');
        $this->validatePasswordMatch('password', 'confirm_password', 'Passwords do not match');
        
        // Date of birth
        if (isset($data['date_of_birth']) && !empty($data['date_of_birth'])) {
            $this->validateDate('date_of_birth', 'Please enter a valid date of birth');
            $this->validateAge('date_of_birth', 18, 'You must be at least 18 years old');
        }
        
        // Gender
        if (isset($data['gender']) && !empty($data['gender'])) {
            $this->validateInArray('gender', ['male', 'female', 'other'], 'Please select a valid gender');
        }
        
        return $this->getValidationResult();
    }
    
    /**
     * Validate member application data
     */
    public function validateMemberApplication($data) {
        $this->errors = [];
        $this->data = $data;
        
        // Basic user validation
        $userValidation = $this->validateUserRegistration($data);
        if (!$userValidation['valid']) {
            return $userValidation;
        }
        
        // Additional member fields
        $this->validateRequired('address', 'Address is required');
        $this->validateString('address', 10, 500, 'Address must be between 10 and 500 characters');
        
        $this->validateRequired('city', 'City is required');
        $this->validateString('city', 2, 100, 'City must be between 2 and 100 characters');
        
        $this->validateRequired('state', 'State is required');
        $this->validateString('state', 2, 100, 'State must be between 2 and 100 characters');
        
        $this->validateRequired('pincode', 'Pincode is required');
        $this->validatePincode('pincode', 'Please enter a valid pincode');
        
        $this->validateRequired('occupation', 'Occupation is required');
        $this->validateString('occupation', 2, 100, 'Occupation must be between 2 and 100 characters');
        
        // Aadhar number (optional but if provided, must be valid)
        if (isset($data['aadhar_number']) && !empty($data['aadhar_number'])) {
            $this->validateAadhar('aadhar_number', 'Please enter a valid Aadhar number');
        }
        
        // PAN number (optional but if provided, must be valid)
        if (isset($data['pan_number']) && !empty($data['pan_number'])) {
            $this->validatePAN('pan_number', 'Please enter a valid PAN number');
        }
        
        return $this->getValidationResult();
    }
    
    /**
     * Validate complaint data
     */
    public function validateComplaint($data) {
        $this->errors = [];
        $this->data = $data;
        
        $this->validateRequired('title', 'Complaint title is required');
        $this->validateString('title', 5, 200, 'Title must be between 5 and 200 characters');
        
        $this->validateRequired('description', 'Complaint description is required');
        $this->validateString('description', 20, 5000, 'Description must be between 20 and 5000 characters');
        
        $this->validateRequired('category', 'Complaint category is required');
        $this->validateInArray('category', [
            'human_rights_violation',
            'discrimination',
            'police_brutality',
            'corruption',
            'women_rights',
            'child_rights',
            'other'
        ], 'Please select a valid category');
        
        if (isset($data['incident_date']) && !empty($data['incident_date'])) {
            $this->validateDate('incident_date', 'Please enter a valid incident date');
            $this->validatePastDate('incident_date', 'Incident date cannot be in the future');
        }
        
        if (isset($data['location']) && !empty($data['location'])) {
            $this->validateString('location', 2, 200, 'Location must be between 2 and 200 characters');
        }
        
        return $this->getValidationResult();
    }
    
    /**
     * Validate donation data
     */
    public function validateDonation($data) {
        $this->errors = [];
        $this->data = $data;
        
        $this->validateRequired('amount', 'Donation amount is required');
        $this->validateNumeric('amount', 'Amount must be a valid number');
        $this->validateMin('amount', 1, 'Minimum donation amount is ₹1');
        $this->validateMax('amount', 1000000, 'Maximum donation amount is ₹10,00,000');
        
        $this->validateRequired('donor_name', 'Donor name is required');
        $this->validateString('donor_name', 2, 100, 'Donor name must be between 2 and 100 characters');
        
        $this->validateRequired('donor_email', 'Donor email is required');
        $this->validateEmail('donor_email', 'Please enter a valid email address');
        
        $this->validateRequired('donor_phone', 'Donor phone is required');
        $this->validatePhone('donor_phone', 'Please enter a valid phone number');
        
        if (isset($data['purpose']) && !empty($data['purpose'])) {
            $this->validateString('purpose', 5, 500, 'Purpose must be between 5 and 500 characters');
        }
        
        if (isset($data['payment_method']) && !empty($data['payment_method'])) {
            $this->validateInArray('payment_method', [
                'online', 'bank_transfer', 'cheque', 'cash'
            ], 'Please select a valid payment method');
        }
        
        return $this->getValidationResult();
    }
    
    /**
     * Validate event data
     */
    public function validateEvent($data) {
        $this->errors = [];
        $this->data = $data;
        
        $this->validateRequired('title', 'Event title is required');
        $this->validateString('title', 5, 200, 'Title must be between 5 and 200 characters');
        
        $this->validateRequired('description', 'Event description is required');
        $this->validateString('description', 20, 2000, 'Description must be between 20 and 2000 characters');
        
        $this->validateRequired('event_date', 'Event date is required');
        $this->validateDate('event_date', 'Please enter a valid event date');
        
        $this->validateRequired('location', 'Event location is required');
        $this->validateString('location', 5, 200, 'Location must be between 5 and 200 characters');
        
        if (isset($data['registration_deadline']) && !empty($data['registration_deadline'])) {
            $this->validateDate('registration_deadline', 'Please enter a valid registration deadline');
            $this->validateDateBefore('registration_deadline', 'event_date', 'Registration deadline must be before event date');
        }
        
        if (isset($data['max_participants']) && !empty($data['max_participants'])) {
            $this->validateNumeric('max_participants', 'Maximum participants must be a number');
            $this->validateMin('max_participants', 1, 'Maximum participants must be at least 1');
        }
        
        if (isset($data['category']) && !empty($data['category'])) {
            $this->validateInArray('category', [
                'workshop', 'seminar', 'awareness_campaign', 'training', 'meeting', 'other'
            ], 'Please select a valid category');
        }
        
        return $this->getValidationResult();
    }
    
    /**
     * Validate contact form data
     */
    public function validateContact($data) {
        $this->errors = [];
        $this->data = $data;
        
        $this->validateRequired('name', 'Name is required');
        $this->validateString('name', 2, 100, 'Name must be between 2 and 100 characters');
        
        $this->validateRequired('email', 'Email is required');
        $this->validateEmail('email', 'Please enter a valid email address');
        
        $this->validateRequired('subject', 'Subject is required');
        $this->validateString('subject', 5, 200, 'Subject must be between 5 and 200 characters');
        
        $this->validateRequired('message', 'Message is required');
        $this->validateString('message', 10, 2000, 'Message must be between 10 and 2000 characters');
        
        if (isset($data['phone']) && !empty($data['phone'])) {
            $this->validatePhone('phone', 'Please enter a valid phone number');
        }
        
        return $this->getValidationResult();
    }
    
    // Individual validation methods
    
    private function validateRequired($field, $message) {
        if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
            $this->errors[$field] = $message;
        }
    }
    
    private function validateString($field, $min = null, $max = null, $message = null) {
        if (!isset($this->data[$field])) return;
        
        $value = trim($this->data[$field]);
        $length = mb_strlen($value);
        
        if ($min !== null && $length < $min) {
            $this->errors[$field] = $message ?: "Field must be at least {$min} characters";
        }
        
        if ($max !== null && $length > $max) {
            $this->errors[$field] = $message ?: "Field must not exceed {$max} characters";
        }
    }
    
    private function validateEmail($field, $message) {
        if (!isset($this->data[$field])) return;
        
        $email = trim($this->data[$field]);
        if (!SecurityMiddleware::validateEmail($email)) {
            $this->errors[$field] = $message;
        }
    }
    
    private function validatePhone($field, $message) {
        if (!isset($this->data[$field])) return;
        
        $phone = trim($this->data[$field]);
        if (!SecurityMiddleware::validatePhone($phone)) {
            $this->errors[$field] = $message;
        }
    }
    
    private function validatePassword($field) {
        if (!isset($this->data[$field])) return;
        
        $password = $this->data[$field];
        $errors = SecurityMiddleware::validatePasswordStrength($password);
        
        if (!empty($errors)) {
            $this->errors[$field] = implode(', ', $errors);
        }
    }
    
    private function validatePasswordMatch($field1, $field2, $message) {
        if (!isset($this->data[$field1]) || !isset($this->data[$field2])) return;
        
        if ($this->data[$field1] !== $this->data[$field2]) {
            $this->errors[$field2] = $message;
        }
    }
    
    private function validateNumeric($field, $message) {
        if (!isset($this->data[$field])) return;
        
        if (!is_numeric($this->data[$field])) {
            $this->errors[$field] = $message;
        }
    }
    
    private function validateMin($field, $min, $message) {
        if (!isset($this->data[$field])) return;
        
        if (is_numeric($this->data[$field]) && $this->data[$field] < $min) {
            $this->errors[$field] = $message;
        }
    }
    
    private function validateMax($field, $max, $message) {
        if (!isset($this->data[$field])) return;
        
        if (is_numeric($this->data[$field]) && $this->data[$field] > $max) {
            $this->errors[$field] = $message;
        }
    }
    
    private function validateDate($field, $message) {
        if (!isset($this->data[$field])) return;
        
        $date = $this->data[$field];
        $d = DateTime::createFromFormat('Y-m-d', $date);
        
        if (!$d || $d->format('Y-m-d') !== $date) {
            $this->errors[$field] = $message;
        }
    }
    
    private function validatePastDate($field, $message) {
        if (!isset($this->data[$field])) return;
        
        $date = new DateTime($this->data[$field]);
        $today = new DateTime();
        
        if ($date > $today) {
            $this->errors[$field] = $message;
        }
    }
    
    private function validateDateBefore($field1, $field2, $message) {
        if (!isset($this->data[$field1]) || !isset($this->data[$field2])) return;
        
        $date1 = new DateTime($this->data[$field1]);
        $date2 = new DateTime($this->data[$field2]);
        
        if ($date1 >= $date2) {
            $this->errors[$field1] = $message;
        }
    }
    
    private function validateAge($field, $minAge, $message) {
        if (!isset($this->data[$field])) return;
        
        $birthDate = new DateTime($this->data[$field]);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
        
        if ($age < $minAge) {
            $this->errors[$field] = $message;
        }
    }
    
    private function validateAlpha($field, $message) {
        if (!isset($this->data[$field])) return;
        
        $value = trim($this->data[$field]);
        if (!preg_match('/^[a-zA-Z\s]+$/', $value)) {
            $this->errors[$field] = $message;
        }
    }
    
    private function validateInArray($field, $allowedValues, $message) {
        if (!isset($this->data[$field])) return;
        
        if (!in_array($this->data[$field], $allowedValues)) {
            $this->errors[$field] = $message;
        }
    }
    
    private function validateAadhar($field, $message) {
        if (!isset($this->data[$field])) return;
        
        $aadhar = trim($this->data[$field]);
        if (!SecurityMiddleware::validateAadhar($aadhar)) {
            $this->errors[$field] = $message;
        }
    }
    
    private function validatePAN($field, $message) {
        if (!isset($this->data[$field])) return;
        
        $pan = trim($this->data[$field]);
        if (!SecurityMiddleware::validatePAN($pan)) {
            $this->errors[$field] = $message;
        }
    }
    
    private function validatePincode($field, $message) {
        if (!isset($this->data[$field])) return;
        
        $pincode = trim($this->data[$field]);
        if (!SecurityMiddleware::validatePincode($pincode)) {
            $this->errors[$field] = $message;
        }
    }
    
    /**
     * Get validation result
     */
    private function getValidationResult() {
        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors,
            'data' => $this->sanitizeData()
        ];
    }
    
    /**
     * Sanitize all input data
     */
    private function sanitizeData() {
        $sanitized = [];
        
        foreach ($this->data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = array_map(function($item) {
                    return SecurityMiddleware::sanitizeInput($item);
                }, $value);
            } else {
                // Determine sanitization type based on field name
                $type = $this->getSanitizationType($key);
                $sanitized[$key] = SecurityMiddleware::sanitizeInput($value, $type);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Get sanitization type based on field name
     */
    private function getSanitizationType($fieldName) {
        $typeMap = [
            'email' => 'email',
            'phone' => 'phone',
            'amount' => 'float',
            'age' => 'int',
            'max_participants' => 'int',
            'pincode' => 'int',
            'aadhar_number' => 'alphanumeric',
            'pan_number' => 'alphanumeric'
        ];
        
        return $typeMap[$fieldName] ?? 'string';
    }
    
    /**
     * Validate file upload
     */
    public function validateFileUpload($file, $allowedTypes = [], $maxSize = null) {
        return SecurityMiddleware::validateFileUpload($file, $allowedTypes, $maxSize);
    }
    
    /**
     * Custom validation rule
     */
    public function addCustomRule($field, $callback, $message) {
        if (!isset($this->data[$field])) return;
        
        if (!call_user_func($callback, $this->data[$field])) {
            $this->errors[$field] = $message;
        }
    }
    
    /**
     * Get all errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Check if validation passed
     */
    public function isValid() {
        return empty($this->errors);
    }
    
    /**
     * Get first error message
     */
    public function getFirstError() {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
}
?>