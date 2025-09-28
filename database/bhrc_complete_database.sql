-- BHRC India Complete Database with Updated Data
-- Import this file into phpMyAdmin to get the complete database with all tables and sample data

-- =====================================================
-- USERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
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
-- EVENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS events (
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
CREATE TABLE IF NOT EXISTS event_registrations (
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
CREATE TABLE IF NOT EXISTS donations (
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
CREATE TABLE IF NOT EXISTS system_settings (
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
CREATE TABLE IF NOT EXISTS settings_backups (
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
-- GALLERY TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS gallery (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    image_url VARCHAR(255) NOT NULL,
    thumbnail_url VARCHAR(255),
    category ENUM('events', 'activities', 'awards', 'meetings', 'other') DEFAULT 'other',
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

-- =====================================================
-- NEWS ARTICLES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS news_articles (
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

-- =====================================================
-- CERTIFICATES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS certificates (
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

-- =====================================================
-- ACTIVITY LOGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS activity_logs (
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

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Clear existing data (optional - remove if you want to keep existing data)
DELETE FROM error_logs;
DELETE FROM activity_logs;
DELETE FROM certificates;
DELETE FROM news_articles;
DELETE FROM gallery;
DELETE FROM settings_backups;
DELETE FROM system_settings;
DELETE FROM donations;
DELETE FROM event_registrations;
DELETE FROM events;
DELETE FROM users;

-- Insert default admin user
INSERT INTO users (username, email, password_hash, first_name, last_name, role, status, email_verified, phone, city, state) VALUES
('admin', 'admin@bhrcdata.online', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', 'active', TRUE, '+91-9876543210', 'New Delhi', 'Delhi'),
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', 'member', 'active', TRUE, '+91-9876543211', 'Mumbai', 'Maharashtra'),
('jane_smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith', 'volunteer', 'active', TRUE, '+91-9876543212', 'Bangalore', 'Karnataka'),
('moderator', 'mod@bhrcdata.online', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Moderator', 'User', 'moderator', 'active', TRUE, '+91-9876543213', 'Chennai', 'Tamil Nadu');

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
INSERT INTO news_articles (title, slug, content, excerpt, featured_image, category, status, author_id, published_at, views_count, created_at, updated_at) VALUES
(
    'BHRC India Launches New Human Rights Initiative',
    'bhrc-new-initiative-2024',
    'BHRC India has launched a comprehensive new initiative to promote human rights awareness across the country. This initiative focuses on education, legal aid, and community outreach programs that aim to empower citizens with knowledge about their fundamental rights.\n\n## Key Features of the Initiative:\n\n- **Educational Workshops**: Regular workshops in schools and communities\n- **Legal Aid Services**: Free legal assistance for underprivileged communities\n- **Awareness Campaigns**: Digital and traditional media campaigns\n- **Community Outreach**: Direct engagement with rural and urban communities\n\nThis initiative represents a significant step forward in our mission to protect and promote human rights across India.',
    'New initiative to promote human rights awareness nationwide',
    '',
    'news',
    'published',
    1,
    NOW(),
    45,
    NOW(),
    NOW()
),
(
    'Legal Aid Program Reaches 1000 Beneficiaries',
    'legal-aid-1000-beneficiaries',
    'Our legal aid program has successfully reached over 1000 beneficiaries across various states. The program provides free legal assistance to underprivileged communities who cannot afford legal representation.\n\n## Program Impact:\n\n- **1000+ Beneficiaries**: Direct legal assistance provided\n- **15 States Covered**: Pan-India presence\n- **85% Success Rate**: Cases resolved in favor of beneficiaries\n- **₹50 Lakh Saved**: Legal fees saved for beneficiaries\n\n### Success Stories:\n\n1. **Land Rights Case**: Helped 50 families reclaim their ancestral land\n2. **Labor Rights**: Secured fair wages for 200 construction workers\n3. **Women\'s Rights**: Provided legal support to 150 domestic violence survivors\n\nThis milestone demonstrates our commitment to making justice accessible to all.',
    'Legal aid program milestone achievement',
    '',
    'press_release',
    'published',
    1,
    DATE_SUB(NOW(), INTERVAL 7 DAY),
    32,
    DATE_SUB(NOW(), INTERVAL 7 DAY),
    DATE_SUB(NOW(), INTERVAL 7 DAY)
),
(
    'Upcoming Workshop on Civil Rights',
    'upcoming-civil-rights-workshop',
    'Join us for an informative workshop on civil rights and legal remedies. This workshop will cover fundamental rights, legal procedures, and how to seek justice when your rights are violated.\n\n## Workshop Details:\n\n- **Date**: [To be announced]\n- **Time**: 10:00 AM - 4:00 PM\n- **Venue**: BHRC Community Center, New Delhi\n- **Registration**: Free (Limited seats available)\n\n### Topics Covered:\n\n1. **Fundamental Rights**: Understanding your constitutional rights\n2. **Legal Procedures**: How to file complaints and petitions\n3. **Court Processes**: Navigating the legal system\n4. **Documentation**: Required documents for legal cases\n5. **Case Studies**: Real examples of successful cases\n\n### Who Should Attend:\n\n- Students and young professionals\n- Community leaders\n- Social workers\n- Anyone interested in human rights\n\n**Registration**: Contact us at info@bhrcdata.online or call +91-11-12345678',
    'Workshop announcement for civil rights education',
    '',
    'announcement',
    'published',
    1,
    DATE_SUB(NOW(), INTERVAL 3 DAY),
    28,
    DATE_SUB(NOW(), INTERVAL 3 DAY),
    DATE_SUB(NOW(), INTERVAL 3 DAY)
),
(
    'Draft Article: Human Rights in Digital Age',
    'draft-human-rights-digital-age',
    'This is a draft article about human rights in the digital age. It covers topics like digital privacy, online freedom of expression, and cyber rights.\n\n## Introduction\n\nAs we move deeper into the digital age, new challenges and opportunities arise for human rights protection. The internet has become a fundamental part of our daily lives, but it also presents unique challenges to traditional human rights frameworks.\n\n## Key Areas of Concern\n\n### Digital Privacy\n- Data protection and privacy rights\n- Surveillance and monitoring\n- Consent and data ownership\n\n### Online Freedom of Expression\n- Social media and free speech\n- Content moderation policies\n- Digital censorship\n\n### Cyber Rights\n- Access to information\n- Digital divide\n- Online harassment and cyberbullying\n\n## Conclusion\n\nThis article is still being developed and will be published soon with comprehensive analysis and recommendations.',
    'Exploring human rights challenges in the digital era',
    '',
    'article',
    'draft',
    1,
    NULL,
    0,
    NOW(),
    NOW()
),
(
    'Blog Post: Community Outreach Success',
    'blog-community-outreach-success',
    'Our community outreach programs have been making a significant impact in rural areas. This blog post shares some success stories and lessons learned from our field work.\n\n## Community Outreach Impact\n\nOver the past year, our community outreach team has visited 25 villages across 5 states, conducting awareness sessions and providing direct assistance to community members.\n\n### Success Stories\n\n#### Village Empowerment in Rajasthan\nIn a small village in Rajasthan, we helped establish a women\'s self-help group that now provides micro-finance services to 50 families. The group has successfully provided loans totaling ₹5 lakh.\n\n#### Education Initiative in Bihar\nOur education initiative in Bihar has helped 200 children return to school by providing scholarships and school supplies. The dropout rate in the target area has reduced by 60%.\n\n#### Health Awareness in Odisha\nHealth awareness camps in Odisha have reached 1000+ people, providing free health checkups and connecting them with government health schemes.\n\n## Lessons Learned\n\n1. **Community Engagement**: Direct community involvement is crucial for success\n2. **Local Partnerships**: Working with local organizations increases impact\n3. **Sustainability**: Programs must be designed for long-term sustainability\n4. **Cultural Sensitivity**: Understanding local culture and customs is essential\n\n## Future Plans\n\nWe plan to expand our outreach to 50 more villages in the next year, focusing on areas with limited access to legal and social services.',
    'Sharing success stories from our community outreach programs',
    '',
    'blog',
    'published',
    1,
    DATE_SUB(NOW(), INTERVAL 1 DAY),
    19,
    DATE_SUB(NOW(), INTERVAL 1 DAY),
    DATE_SUB(NOW(), INTERVAL 1 DAY)
);

-- Insert sample certificates
INSERT INTO certificates (user_id, certificate_type, certificate_number, title, description, issued_date, issued_by) VALUES
(2, 'membership', 'CERT2024001', 'BHRC Membership Certificate', 'Certificate of membership for BHRC India', '2024-01-01', 1),
(3, 'participation', 'CERT2024002', 'Workshop Participation Certificate', 'Certificate for participating in Human Rights Workshop', '2024-02-15', 1);

-- Insert sample activity logs
INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address) VALUES
(1, 'login', 'user', 1, 'Admin user logged in', '192.168.1.1'),
(2, 'register', 'event', 1, 'Registered for Human Rights Awareness Workshop', '192.168.1.2'),
(2, 'donate', 'donation', 1, 'Made donation of ₹5000', '192.168.1.2');

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Verify the data
SELECT 'Users' as table_name, COUNT(*) as count FROM users
UNION ALL
SELECT 'Events', COUNT(*) FROM events
UNION ALL
SELECT 'Event Registrations', COUNT(*) FROM event_registrations
UNION ALL
SELECT 'Donations', COUNT(*) FROM donations
UNION ALL
SELECT 'System Settings', COUNT(*) FROM system_settings
UNION ALL
SELECT 'Gallery', COUNT(*) FROM gallery
UNION ALL
SELECT 'News Articles', COUNT(*) FROM news_articles
UNION ALL
SELECT 'Certificates', COUNT(*) FROM certificates
UNION ALL
SELECT 'Activity Logs', COUNT(*) FROM activity_logs;

-- Show sample articles
SELECT 
    id, 
    title, 
    category, 
    status, 
    author_id, 
    created_at,
    views_count
FROM news_articles 
ORDER BY created_at DESC;
