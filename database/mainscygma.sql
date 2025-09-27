-- BHRC India Complete Database Schema
-- Database: tzdmiohj_bhmc
-- Combined from: schema.sql, donations_schema.sql, events_schema.sql, settings_schema.sql, complaints.sql

-- Create database if not exists

-- =====================================================
-- USERS TABLE
-- =====================================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(15),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    pincode VARCHAR(10),
    role ENUM('admin', 'member', 'volunteer', 'moderator', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive', 'suspended', 'pending') DEFAULT 'pending',
    email_verified BOOLEAN DEFAULT FALSE,
    profile_image VARCHAR(255),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- =====================================================
-- COMPLAINTS TABLE
-- =====================================================
CREATE TABLE complaints (
    id INT PRIMARY KEY AUTO_INCREMENT,
    complaint_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT,
    complainant_name VARCHAR(100) NOT NULL,
    complainant_email VARCHAR(100) NOT NULL,
    complainant_phone VARCHAR(15),
    complainant_address TEXT,
    complainant_city VARCHAR(50),
    complainant_state VARCHAR(50),
    complainant_pincode VARCHAR(10),
    subject VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    incident_date DATE,
    incident_time TIME,
    incident_location VARCHAR(200),
    incident_address TEXT,
    category ENUM('discrimination', 'violence', 'harassment', 'civil_rights', 'police_brutality', 'corruption', 'other') NOT NULL,
    subcategory VARCHAR(100),
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('submitted', 'under_review', 'investigating', 'resolved', 'closed', 'rejected') DEFAULT 'submitted',
    assigned_to INT,
    resolution TEXT,
    resolution_date DATE,
    attachments JSON,
    evidence_files JSON,
    witness_details JSON,
    legal_action_required BOOLEAN DEFAULT FALSE,
    compensation_sought DECIMAL(10,2),
    follow_up_required BOOLEAN DEFAULT TRUE,
    public_visibility ENUM('public', 'private', 'anonymous') DEFAULT 'private',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_complaint_number (complaint_number),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at),
    INDEX idx_incident_date (incident_date)
);

-- Complaint Updates Table for tracking changes
CREATE TABLE complaint_updates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    complaint_id INT NOT NULL,
    updated_by INT,
    update_type ENUM('status_change', 'assignment', 'comment', 'resolution', 'evidence_added') NOT NULL,
    old_value TEXT,
    new_value TEXT,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_complaint_id (complaint_id),
    INDEX idx_updated_by (updated_by),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- EVENTS TABLE
-- =====================================================
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME,
    end_date DATE,
    end_time TIME,
    location VARCHAR(200),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    pincode VARCHAR(10),
    event_type ENUM('workshop', 'seminar', 'awareness', 'training', 'meeting', 'conference', 'webinar', 'other') NOT NULL,
    capacity INT DEFAULT 0,
    current_registrations INT DEFAULT 0,
    registration_required BOOLEAN DEFAULT FALSE,
    registration_deadline DATE,
    registration_fee DECIMAL(8,2) DEFAULT 0.00,
    status ENUM('upcoming', 'ongoing', 'completed', 'cancelled', 'postponed') DEFAULT 'upcoming',
    featured_image VARCHAR(255),
    gallery_images JSON,
    organizer_id INT,
    created_by INT NOT NULL,
    is_featured BOOLEAN DEFAULT FALSE,
    is_public BOOLEAN DEFAULT TRUE,
    tags JSON,
    requirements TEXT,
    agenda TEXT,
    speaker_details JSON,
    contact_person VARCHAR(100),
    contact_phone VARCHAR(15),
    contact_email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_event_date (event_date),
    INDEX idx_status (status),
    INDEX idx_event_type (event_type),
    INDEX idx_is_featured (is_featured),
    INDEX idx_registration_deadline (registration_deadline)
);

-- Event Registrations Table
CREATE TABLE event_registrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    user_id INT,
    participant_name VARCHAR(100) NOT NULL,
    participant_email VARCHAR(100) NOT NULL,
    participant_phone VARCHAR(15),
    participant_organization VARCHAR(100),
    participant_designation VARCHAR(100),
    special_requirements TEXT,
    payment_status ENUM('pending', 'paid', 'waived', 'refunded') DEFAULT 'pending',
    payment_method ENUM('online', 'bank_transfer', 'cash', 'free') DEFAULT 'free',
    transaction_id VARCHAR(100),
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attendance_status ENUM('registered', 'attended', 'absent', 'cancelled') DEFAULT 'registered',
    feedback_rating INT CHECK (feedback_rating >= 1 AND feedback_rating <= 5),
    feedback_comments TEXT,
    certificate_issued BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_registration (event_id, participant_email),
    INDEX idx_event_id (event_id),
    INDEX idx_user_id (user_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_attendance_status (attendance_status)
);

-- =====================================================
-- DONATIONS TABLE
-- =====================================================
CREATE TABLE donations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reference_number VARCHAR(50) UNIQUE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    type ENUM('general', 'education', 'legal_aid', 'awareness', 'emergency', 'infrastructure') DEFAULT 'general',
    category ENUM('individual', 'corporate', 'institutional', 'anonymous') DEFAULT 'individual',
    status ENUM('pending', 'completed', 'failed', 'refunded', 'cancelled') DEFAULT 'pending',
    fee_amount DECIMAL(8,2) DEFAULT 0.00,
    net_amount DECIMAL(12,2) GENERATED ALWAYS AS (amount - fee_amount) STORED,
    
    -- Donor Information
    donor_name VARCHAR(100) NOT NULL,
    donor_email VARCHAR(100) NOT NULL,
    donor_phone VARCHAR(15),
    donor_address TEXT,
    donor_city VARCHAR(50),
    donor_state VARCHAR(50),
    donor_country VARCHAR(50) DEFAULT 'India',
    donor_pincode VARCHAR(10),
    is_anonymous BOOLEAN DEFAULT FALSE,
    
    -- Payment Details
    payment_method ENUM('credit_card', 'debit_card', 'net_banking', 'upi', 'wallet', 'bank_transfer', 'cash', 'cheque') NOT NULL,
    payment_status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100),
    gateway_response JSON,
    payment_date TIMESTAMP NULL,
    
    -- Recurring Donations
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_frequency ENUM('monthly', 'quarterly', 'yearly') NULL,
    recurring_end_date DATE NULL,
    parent_donation_id INT NULL,
    
    -- Additional Information
    purpose TEXT,
    dedication_message TEXT,
    tax_exemption_required BOOLEAN DEFAULT TRUE,
    receipt_sent BOOLEAN DEFAULT FALSE,
    receipt_number VARCHAR(50) UNIQUE,
    notes TEXT,
    
    -- Tracking
    user_id INT,
    campaign_source VARCHAR(100),
    utm_source VARCHAR(100),
    utm_medium VARCHAR(100),
    utm_campaign VARCHAR(100),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_donation_id) REFERENCES donations(id) ON DELETE SET NULL,
    
    INDEX idx_reference_number (reference_number),
    INDEX idx_donor_email (donor_email),
    INDEX idx_payment_status (payment_status),
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_category (category),
    INDEX idx_created_at (created_at),
    INDEX idx_payment_date (payment_date),
    INDEX idx_is_recurring (is_recurring),
    INDEX idx_amount (amount)
);

-- =====================================================
-- SYSTEM SETTINGS TABLE
-- =====================================================
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json', 'text') DEFAULT 'string',
    category ENUM('general', 'email', 'payment', 'security', 'notifications', 'appearance', 'api') DEFAULT 'general',
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    is_encrypted BOOLEAN DEFAULT FALSE,
    validation_rules JSON,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_setting_key (setting_key),
    INDEX idx_category (category),
    INDEX idx_is_public (is_public)
);

-- Settings Backup Table
CREATE TABLE settings_backups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    backup_name VARCHAR(100) NOT NULL,
    settings_data JSON NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_created_by (created_by),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- ADDITIONAL TABLES
-- =====================================================

-- Gallery table for images and media
CREATE TABLE gallery (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    image_url VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(255),
    category ENUM('events', 'activities', 'awards', 'meetings', 'complaints', 'other') DEFAULT 'other',
    alt_text VARCHAR(200),
    uploaded_by INT NOT NULL,
    is_featured BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    file_size INT,
    file_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_category (category),
    INDEX idx_is_featured (is_featured),
    INDEX idx_display_order (display_order)
);

-- News and articles table
CREATE TABLE news_articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    featured_image VARCHAR(255),
    category ENUM('news', 'press_release', 'article', 'announcement', 'blog') NOT NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    author_id INT NOT NULL,
    published_at TIMESTAMP NULL,
    views_count INT DEFAULT 0,
    tags JSON,
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_published_at (published_at)
);

-- Certificates table for member certificates
CREATE TABLE certificates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    certificate_type ENUM('membership', 'participation', 'appreciation', 'training', 'volunteer') NOT NULL,
    certificate_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    issued_date DATE NOT NULL,
    valid_until DATE,
    certificate_file VARCHAR(255),
    issued_by INT NOT NULL,
    event_id INT,
    verification_code VARCHAR(100) UNIQUE,
    is_verified BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_certificate_number (certificate_number),
    INDEX idx_certificate_type (certificate_type),
    INDEX idx_verification_code (verification_code)
);

-- Activity logs table for audit trail
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    description TEXT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    session_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity_type (entity_type),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Trigger to automatically update complaint updates
DELIMITER $$
CREATE TRIGGER complaint_status_update 
AFTER UPDATE ON complaints
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO complaint_updates (complaint_id, update_type, old_value, new_value, updated_by)
        VALUES (NEW.id, 'status_change', OLD.status, NEW.status, NEW.assigned_to);
    END IF;
END$$
DELIMITER ;

-- Trigger to update event registration count
DELIMITER $$
CREATE TRIGGER update_event_registrations_count
AFTER INSERT ON event_registrations
FOR EACH ROW
BEGIN
    UPDATE events 
    SET current_registrations = (
        SELECT COUNT(*) FROM event_registrations 
        WHERE event_id = NEW.event_id AND attendance_status != 'cancelled'
    )
    WHERE id = NEW.event_id;
END$$
DELIMITER ;

-- =====================================================
-- VIEWS
-- =====================================================

-- Complaint Statistics View
CREATE VIEW complaint_statistics AS
SELECT 
    COUNT(*) as total_complaints,
    COUNT(CASE WHEN status = 'submitted' THEN 1 END) as submitted,
    COUNT(CASE WHEN status = 'under_review' THEN 1 END) as under_review,
    COUNT(CASE WHEN status = 'investigating' THEN 1 END) as investigating,
    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved,
    COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
    COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as urgent_complaints,
    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as monthly_complaints
FROM complaints;

-- Donation Statistics View
CREATE VIEW donation_statistics AS
SELECT 
    COUNT(*) as total_donations,
    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_amount,
    AVG(CASE WHEN status = 'completed' THEN amount ELSE NULL END) as average_donation,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_donations,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_donations,
    COUNT(CASE WHEN is_recurring = TRUE THEN 1 END) as recurring_donations,
    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as monthly_donations,
    SUM(CASE WHEN status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN amount ELSE 0 END) as monthly_amount
FROM donations;

-- Event Statistics View
CREATE VIEW event_statistics AS
SELECT 
    COUNT(*) as total_events,
    COUNT(CASE WHEN status = 'upcoming' THEN 1 END) as upcoming_events,
    COUNT(CASE WHEN status = 'ongoing' THEN 1 END) as ongoing_events,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_events,
    COUNT(CASE WHEN registration_required = TRUE THEN 1 END) as registration_required_events,
    SUM(current_registrations) as total_registrations,
    COUNT(CASE WHEN event_date >= CURDATE() AND event_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as upcoming_month_events
FROM events;

-- Settings by Category View
CREATE VIEW settings_by_category AS
SELECT 
    category,
    COUNT(*) as setting_count,
    COUNT(CASE WHEN is_public = TRUE THEN 1 END) as public_settings
FROM system_settings
GROUP BY category;

-- Recent Activity View
CREATE VIEW recent_activity AS
SELECT 
    al.*,
    u.username,
    u.first_name,
    u.last_name
FROM activity_logs al
LEFT JOIN users u ON al.user_id = u.id
ORDER BY al.created_at DESC
LIMIT 100;

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Insert default admin user
INSERT INTO users (username, email, password_hash, first_name, last_name, role, status, email_verified, phone, city, state) VALUES
('admin', 'admin@bhrcdata.online', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', 'active', TRUE, '+91-9876543210', 'New Delhi', 'Delhi'),
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', 'member', 'active', TRUE, '+91-9876543211', 'Mumbai', 'Maharashtra'),
('jane_smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith', 'volunteer', 'active', TRUE, '+91-9876543212', 'Bangalore', 'Karnataka'),
('moderator', 'mod@bhrcdata.online', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Moderator', 'User', 'moderator', 'active', TRUE, '+91-9876543213', 'Chennai', 'Tamil Nadu');

-- Insert sample complaints
INSERT INTO complaints (complaint_number, user_id, complainant_name, complainant_email, complainant_phone, subject, description, incident_date, incident_location, category, priority, status, assigned_to) VALUES
('BHRC2024001', 2, 'John Doe', 'john@example.com', '+91-9876543211', 'Workplace Discrimination', 'Facing discrimination at workplace based on caste', '2024-01-15', 'Mumbai Office Complex', 'discrimination', 'high', 'under_review', 1),
('BHRC2024002', 3, 'Jane Smith', 'jane@example.com', '+91-9876543212', 'Police Harassment', 'Unnecessary harassment by local police', '2024-01-20', 'Bangalore Police Station', 'harassment', 'urgent', 'investigating', 1),
('BHRC2024003', NULL, 'Anonymous User', 'anonymous@example.com', '+91-9876543213', 'Civil Rights Violation', 'Denial of basic civil rights in local area', '2024-01-25', 'Chennai District', 'civil_rights', 'medium', 'submitted', NULL);

-- Insert sample events
INSERT INTO events (title, description, event_date, event_time, location, event_type, capacity, registration_required, status, created_by, organizer_id) VALUES
('Human Rights Awareness Workshop', 'A comprehensive workshop on human rights awareness for the community', '2024-03-15', '10:00:00', 'BHRC Community Center, Delhi', 'workshop', 50, TRUE, 'upcoming', 1, 1),
('Legal Aid Seminar', 'Free legal aid seminar for underprivileged communities', '2024-03-20', '14:00:00', 'Mumbai Legal Center', 'seminar', 100, TRUE, 'upcoming', 1, 1),
('Monthly Team Meeting', 'Regular monthly meeting for BHRC team members', '2024-02-28', '11:00:00', 'BHRC Office, Delhi', 'meeting', 20, FALSE, 'completed', 1, 1);

-- Insert sample event registrations
INSERT INTO event_registrations (event_id, user_id, participant_name, participant_email, participant_phone, attendance_status) VALUES
(1, 2, 'John Doe', 'john@example.com', '+91-9876543211', 'registered'),
(1, 3, 'Jane Smith', 'jane@example.com', '+91-9876543212', 'registered'),
(2, 2, 'John Doe', 'john@example.com', '+91-9876543211', 'registered');

-- Insert sample donations
INSERT INTO donations (reference_number, amount, type, category, status, donor_name, donor_email, donor_phone, payment_method, payment_status, transaction_id, receipt_number, user_id) VALUES
('DON2024001', 5000.00, 'general', 'individual', 'completed', 'John Doe', 'john@example.com', '+91-9876543211', 'upi', 'completed', 'TXN123456789', 'RCP2024001', 2),
('DON2024002', 10000.00, 'education', 'corporate', 'completed', 'ABC Corporation', 'corporate@abc.com', '+91-9876543220', 'bank_transfer', 'completed', 'TXN123456790', 'RCP2024002', NULL),
('DON2024003', 2500.00, 'legal_aid', 'individual', 'pending', 'Jane Smith', 'jane@example.com', '+91-9876543212', 'credit_card', 'pending', NULL, NULL, 3),
('DON2024004', 50000.00, 'general', 'anonymous', 'completed', 'Anonymous Donor', 'anonymous@donor.com', NULL, 'bank_transfer', 'completed', 'TXN123456791', 'RCP2024003', NULL),
('DON2024005', 1000.00, 'awareness', 'individual', 'failed', 'Failed Donor', 'failed@example.com', '+91-9876543215', 'credit_card', 'failed', 'TXN123456792', NULL, NULL);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, category, description, is_public) VALUES
-- General Settings
('site_name', 'BHRC India', 'string', 'general', 'Website name', TRUE),
('site_description', 'Bharatiya Human Rights Commission India', 'string', 'general', 'Website description', TRUE),
('site_logo', '/assets/images/logo.png', 'string', 'general', 'Website logo path', TRUE),
('contact_email', 'info@bhrcdata.online', 'string', 'general', 'Contact email address', TRUE),
('contact_phone', '+91-11-12345678', 'string', 'general', 'Contact phone number', TRUE),
('office_address', 'BHRC India, New Delhi, India', 'string', 'general', 'Office address', TRUE),
('working_hours', 'Monday to Friday, 9:00 AM to 6:00 PM', 'string', 'general', 'Office working hours', TRUE),

-- Email Settings
('smtp_host', 'smtp.gmail.com', 'string', 'email', 'SMTP server host', FALSE),
('smtp_port', '587', 'number', 'email', 'SMTP server port', FALSE),
('smtp_username', 'noreply@bhrcdata.online', 'string', 'email', 'SMTP username', FALSE),
('smtp_password', 'email_password_here', 'string', 'email', 'SMTP password', FALSE),
('email_from_name', 'BHRC India', 'string', 'email', 'Default sender name', FALSE),
('email_from_address', 'noreply@bhrcdata.online', 'string', 'email', 'Default sender email', FALSE),

-- Payment Settings
('payment_gateway', 'razorpay', 'string', 'payment', 'Default payment gateway', FALSE),
('razorpay_key_id', 'rzp_test_key_here', 'string', 'payment', 'Razorpay Key ID', FALSE),
('razorpay_key_secret', 'rzp_test_secret_here', 'string', 'payment', 'Razorpay Key Secret', FALSE),
('min_donation_amount', '100', 'number', 'payment', 'Minimum donation amount', TRUE),
('max_donation_amount', '1000000', 'number', 'payment', 'Maximum donation amount', TRUE),

-- Security Settings
('jwt_secret', 'your-jwt-secret-key-here-change-in-production', 'string', 'security', 'JWT secret key', FALSE),
('jwt_expiry', '24', 'number', 'security', 'JWT token expiry in hours', FALSE),
('session_timeout', '30', 'number', 'security', 'Session timeout in minutes', FALSE),
('max_login_attempts', '5', 'number', 'security', 'Maximum login attempts', FALSE),
('password_min_length', '8', 'number', 'security', 'Minimum password length', FALSE),

-- File Upload Settings
('max_file_size', '5242880', 'number', 'general', 'Maximum file upload size in bytes (5MB)', FALSE),
('allowed_file_types', '["jpg","jpeg","png","pdf","doc","docx","txt"]', 'json', 'general', 'Allowed file types for uploads', FALSE),
('upload_path', '/uploads/', 'string', 'general', 'File upload directory path', FALSE),

-- Notification Settings
('enable_email_notifications', 'true', 'boolean', 'notifications', 'Enable email notifications', FALSE),
('enable_sms_notifications', 'false', 'boolean', 'notifications', 'Enable SMS notifications', FALSE),
('notification_email', 'notifications@bhrcdata.online', 'string', 'notifications', 'Notification email address', FALSE);

-- Insert sample gallery items
INSERT INTO gallery (title, description, image_url, category, uploaded_by, is_featured) VALUES
('Human Rights Workshop 2024', 'Photos from our recent human rights awareness workshop', '/uploads/gallery/workshop2024.jpg', 'events', 1, TRUE),
('Legal Aid Camp', 'Images from our legal aid camp in rural areas', '/uploads/gallery/legalaid.jpg', 'activities', 1, FALSE),
('BHRC Award Ceremony', 'Annual award ceremony recognizing human rights defenders', '/uploads/gallery/awards2024.jpg', 'awards', 1, TRUE);

-- Insert sample news articles
INSERT INTO news_articles (title, slug, content, excerpt, category, status, author_id, published_at) VALUES
('BHRC India Launches New Human Rights Initiative', 'bhrc-new-initiative-2024', 'BHRC India has launched a comprehensive new initiative to promote human rights awareness across the country...', 'New initiative to promote human rights awareness nationwide', 'news', 'published', 1, NOW()),
('Legal Aid Program Reaches 1000 Beneficiaries', 'legal-aid-1000-beneficiaries', 'Our legal aid program has successfully reached over 1000 beneficiaries across various states...', 'Legal aid program milestone achievement', 'press_release', 'published', 1, DATE_SUB(NOW(), INTERVAL 7 DAY)),
('Upcoming Workshop on Civil Rights', 'upcoming-civil-rights-workshop', 'Join us for an informative workshop on civil rights and legal remedies...', 'Workshop announcement for civil rights education', 'announcement', 'published', 1, DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Insert sample activity logs
INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address) VALUES
(1, 'login', 'user', 1, 'Admin user logged in', '192.168.1.1'),
(1, 'create', 'complaint', 1, 'New complaint created: BHRC2024001', '192.168.1.1'),
(1, 'update', 'complaint', 1, 'Complaint status updated to under_review', '192.168.1.1'),
(2, 'register', 'event', 1, 'Registered for Human Rights Awareness Workshop', '192.168.1.2'),
(2, 'donate', 'donation', 1, 'Made donation of ₹5000', '192.168.1.2');

-- =====================================================
-- ERROR LOGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS error_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    error_message TEXT NOT NULL,
    stack_trace TEXT NULL,
    context JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert sample certificates
INSERT INTO certificates (user_id, certificate_type, certificate_number, title, description, issued_date, issued_by) VALUES
(2, 'membership', 'CERT2024001', 'BHRC Membership Certificate', 'Certificate of membership for BHRC India', '2024-01-01', 1),
(3, 'participation', 'CERT2024002', 'Workshop Participation Certificate', 'Certificate for participating in Human Rights Workshop', '2024-02-15', 1);