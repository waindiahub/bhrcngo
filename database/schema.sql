-- BHRC Database Schema
-- Bharatiya Human Rights Council
-- Optimized and Clean Database Structure

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Users table - Core user management
CREATE TABLE `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `phone` varchar(20) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `role` enum('admin','moderator','member','volunteer','user') NOT NULL DEFAULT 'user',
    `status` enum('active','inactive','suspended','pending') NOT NULL DEFAULT 'pending',
    `email_verified` tinyint(1) NOT NULL DEFAULT 0,
    `phone_verified` tinyint(1) NOT NULL DEFAULT 0,
    `email_verified_at` timestamp NULL DEFAULT NULL,
    `phone_verified_at` timestamp NULL DEFAULT NULL,
    `last_login` timestamp NULL DEFAULT NULL,
    `login_attempts` int(11) NOT NULL DEFAULT 0,
    `locked_until` timestamp NULL DEFAULT NULL,
    `profile_image` varchar(500) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `phone` (`phone`),
    KEY `idx_role` (`role`),
    KEY `idx_status` (`status`),
    KEY `idx_email_verified` (`email_verified`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Members table - Extended member information
CREATE TABLE `members` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `member_id` varchar(50) NOT NULL,
    `gender` enum('Male','Female','Other') NOT NULL,
    `date_of_birth` date NOT NULL,
    `father_name` varchar(255) DEFAULT NULL,
    `mother_name` varchar(255) DEFAULT NULL,
    `blood_group` varchar(10) DEFAULT NULL,
    `whatsapp` varchar(20) DEFAULT NULL,
    `state` varchar(100) NOT NULL,
    `district` varchar(100) NOT NULL,
    `block` varchar(100) DEFAULT NULL,
    `pincode` varchar(10) NOT NULL,
    `address` text NOT NULL,
    `id_proof_type` varchar(50) NOT NULL,
    `id_proof_number` varchar(100) NOT NULL,
    `occupation` varchar(255) DEFAULT NULL,
    `qualification` varchar(255) DEFAULT NULL,
    `languages_known` varchar(500) DEFAULT NULL,
    `designation` varchar(255) DEFAULT NULL,
    `id_proof_file` varchar(500) DEFAULT NULL,
    `photo_file` varchar(500) DEFAULT NULL,
    `signature_file` varchar(500) DEFAULT NULL,
    `member_type` enum('national','state','district','block') NOT NULL DEFAULT 'state',
    `membership_date` date DEFAULT NULL,
    `certificate_path` varchar(500) DEFAULT NULL,
    `status` enum('pending','approved','rejected','suspended') NOT NULL DEFAULT 'pending',
    `approved_by` int(11) DEFAULT NULL,
    `approved_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `member_id` (`member_id`),
    UNIQUE KEY `user_id` (`user_id`),
    KEY `idx_state` (`state`),
    KEY `idx_district` (`district`),
    KEY `idx_member_type` (`member_type`),
    KEY `idx_status` (`status`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Complaints table - Complaint management system
CREATE TABLE `complaints` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `complaint_id` varchar(50) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `phone` varchar(20) NOT NULL,
    `gender` enum('Male','Female','Other') NOT NULL,
    `date_of_birth` date NOT NULL,
    `state` varchar(100) NOT NULL,
    `district` varchar(100) NOT NULL,
    `pincode` varchar(10) NOT NULL,
    `address` text NOT NULL,
    `complaint_type` enum('POLICE','WOMEN','SC/ST','MINORITY','CHILD','DISABILITY','OTHER') NOT NULL,
    `date_of_incident` date NOT NULL,
    `complaint_details` text NOT NULL,
    `attachments` text DEFAULT NULL,
    `status` enum('pending','under_review','investigating','resolved','closed','rejected') NOT NULL DEFAULT 'pending',
    `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    `assigned_to` int(11) DEFAULT NULL,
    `resolution_details` text DEFAULT NULL,
    `resolved_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `complaint_id` (`complaint_id`),
    KEY `idx_status` (`status`),
    KEY `idx_complaint_type` (`complaint_type`),
    KEY `idx_priority` (`priority`),
    KEY `idx_state` (`state`),
    KEY `idx_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Donations table - Donation management
CREATE TABLE `donations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `donation_id` varchar(50) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `donor_name` varchar(255) NOT NULL,
    `donor_email` varchar(255) NOT NULL,
    `donor_phone` varchar(20) NOT NULL,
    `amount` decimal(10,2) NOT NULL,
    `currency` varchar(3) NOT NULL DEFAULT 'INR',
    `payment_method` enum('UPI','Credit Card','Debit Card','Net Banking','Bank Transfer','Cash','Cheque') NOT NULL,
    `transaction_id` varchar(255) DEFAULT NULL,
    `payment_gateway` varchar(50) DEFAULT NULL,
    `purpose` varchar(500) DEFAULT NULL,
    `is_anonymous` tinyint(1) NOT NULL DEFAULT 0,
    `receipt_number` varchar(100) DEFAULT NULL,
    `receipt_path` varchar(500) DEFAULT NULL,
    `status` enum('pending','completed','failed','refunded','cancelled') NOT NULL DEFAULT 'pending',
    `payment_date` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `donation_id` (`donation_id`),
    KEY `idx_status` (`status`),
    KEY `idx_donor_email` (`donor_email`),
    KEY `idx_amount` (`amount`),
    KEY `idx_payment_date` (`payment_date`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events table - Event management
CREATE TABLE `events` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `content` longtext DEFAULT NULL,
    `event_date` date NOT NULL,
    `event_time` time DEFAULT NULL,
    `end_date` date DEFAULT NULL,
    `end_time` time DEFAULT NULL,
    `location` varchar(255) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `state` varchar(100) DEFAULT NULL,
    `district` varchar(100) DEFAULT NULL,
    `organizer_id` int(11) NOT NULL,
    `max_participants` int(11) DEFAULT 0,
    `current_participants` int(11) DEFAULT 0,
    `registration_required` tinyint(1) NOT NULL DEFAULT 0,
    `registration_deadline` date DEFAULT NULL,
    `featured_image` varchar(500) DEFAULT NULL,
    `gallery_images` text DEFAULT NULL,
    `status` enum('draft','published','upcoming','ongoing','completed','cancelled') NOT NULL DEFAULT 'draft',
    `is_featured` tinyint(1) NOT NULL DEFAULT 0,
    `meta_title` varchar(255) DEFAULT NULL,
    `meta_description` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `idx_event_date` (`event_date`),
    KEY `idx_status` (`status`),
    KEY `idx_state` (`state`),
    KEY `idx_is_featured` (`is_featured`),
    FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activities table - Programs and activities
CREATE TABLE `activities` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `content` longtext DEFAULT NULL,
    `activity_type` enum('program','meeting','campaign','blood_donation','case','workshop','seminar','training') NOT NULL,
    `activity_date` date DEFAULT NULL,
    `location` varchar(255) DEFAULT NULL,
    `state` varchar(100) DEFAULT NULL,
    `district` varchar(100) DEFAULT NULL,
    `organizer_id` int(11) NOT NULL,
    `participants_count` int(11) DEFAULT 0,
    `featured_image` varchar(500) DEFAULT NULL,
    `attachments` text DEFAULT NULL,
    `status` enum('planned','ongoing','completed','cancelled') NOT NULL DEFAULT 'planned',
    `is_featured` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `idx_activity_type` (`activity_type`),
    KEY `idx_activity_date` (`activity_date`),
    KEY `idx_status` (`status`),
    KEY `idx_is_featured` (`is_featured`),
    FOREIGN KEY (`organizer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gallery albums table
CREATE TABLE `gallery_albums` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `slug` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `cover_image` varchar(500) DEFAULT NULL,
    `created_by` int(11) NOT NULL,
    `sort_order` int(11) DEFAULT 0,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `idx_status` (`status`),
    KEY `idx_sort_order` (`sort_order`),
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gallery photos table
CREATE TABLE `gallery_photos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `image_path` varchar(500) NOT NULL,
    `thumbnail_path` varchar(500) DEFAULT NULL,
    `album_id` int(11) DEFAULT NULL,
    `uploaded_by` int(11) NOT NULL,
    `file_size` int(11) DEFAULT NULL,
    `dimensions` varchar(20) DEFAULT NULL,
    `sort_order` int(11) DEFAULT 0,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_album_id` (`album_id`),
    KEY `idx_status` (`status`),
    KEY `idx_sort_order` (`sort_order`),
    FOREIGN KEY (`album_id`) REFERENCES `gallery_albums` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gallery videos table
CREATE TABLE `gallery_videos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `video_url` varchar(500) NOT NULL,
    `thumbnail_path` varchar(500) DEFAULT NULL,
    `video_type` enum('youtube','vimeo','upload') NOT NULL DEFAULT 'youtube',
    `duration` int(11) DEFAULT NULL,
    `uploaded_by` int(11) NOT NULL,
    `sort_order` int(11) DEFAULT 0,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_video_type` (`video_type`),
    KEY `idx_status` (`status`),
    KEY `idx_sort_order` (`sort_order`),
    FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter subscriptions
CREATE TABLE `newsletter_subscriptions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `email` varchar(255) NOT NULL,
    `name` varchar(255) DEFAULT NULL,
    `status` enum('active','inactive','unsubscribed') NOT NULL DEFAULT 'active',
    `verification_token` varchar(255) DEFAULT NULL,
    `verified_at` timestamp NULL DEFAULT NULL,
    `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `unsubscribed_at` timestamp NULL DEFAULT NULL,
    `unsubscribe_reason` text DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contact inquiries
CREATE TABLE `contact_inquiries` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `phone` varchar(20) NOT NULL,
    `subject` varchar(255) NOT NULL,
    `message` text NOT NULL,
    `category` enum('general','complaint','membership','donation','volunteer','media') DEFAULT 'general',
    `status` enum('new','read','replied','closed') NOT NULL DEFAULT 'new',
    `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
    `assigned_to` int(11) DEFAULT NULL,
    `replied_by` int(11) DEFAULT NULL,
    `reply_message` text DEFAULT NULL,
    `replied_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_category` (`category`),
    KEY `idx_priority` (`priority`),
    KEY `idx_email` (`email`),
    FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- OTP verifications table
CREATE TABLE `otp_verifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `email` varchar(255) DEFAULT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `otp` varchar(10) NOT NULL,
    `purpose` enum('registration','login','password_reset','email_verification','phone_verification') NOT NULL,
    `status` enum('pending','verified','expired','used') NOT NULL DEFAULT 'pending',
    `attempts` int(11) NOT NULL DEFAULT 0,
    `max_attempts` int(11) NOT NULL DEFAULT 3,
    `expires_at` timestamp NOT NULL,
    `verified_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_email` (`email`),
    KEY `idx_phone` (`phone`),
    KEY `idx_otp` (`otp`),
    KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System settings table
CREATE TABLE `system_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `setting_key` varchar(100) NOT NULL,
    `setting_value` text DEFAULT NULL,
    `setting_type` enum('string','number','boolean','json','text') NOT NULL DEFAULT 'string',
    `description` text DEFAULT NULL,
    `is_public` tinyint(1) NOT NULL DEFAULT 0,
    `updated_by` int(11) DEFAULT NULL,
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`),
    FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter campaigns table
CREATE TABLE `newsletter_campaigns` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `subject` varchar(255) NOT NULL,
    `content` longtext NOT NULL,
    `status` enum('draft','scheduled','sending','sent','cancelled') NOT NULL DEFAULT 'draft',
    `scheduled_at` timestamp NULL DEFAULT NULL,
    `sent_at` timestamp NULL DEFAULT NULL,
    `created_by` int(11) NOT NULL,
    `recipient_count` int(11) DEFAULT 0,
    `open_count` int(11) DEFAULT 0,
    `click_count` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_by` (`created_by`),
    KEY `idx_sent_at` (`sent_at`),
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Files table
CREATE TABLE `files` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `filename` varchar(255) NOT NULL,
    `original_name` varchar(255) NOT NULL,
    `file_path` varchar(500) NOT NULL,
    `file_size` bigint(20) NOT NULL,
    `mime_type` varchar(100) NOT NULL,
    `file_type` enum('image','document','video','audio','other') NOT NULL,
    `uploaded_by` int(11) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `alt_text` varchar(255) DEFAULT NULL,
    `category` varchar(50) DEFAULT 'general',
    `is_public` tinyint(1) NOT NULL DEFAULT 1,
    `download_count` int(11) DEFAULT 0,
    `metadata` json DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_filename` (`filename`),
    KEY `idx_file_type` (`file_type`),
    KEY `idx_category` (`category`),
    KEY `idx_uploaded_by` (`uploaded_by`),
    KEY `idx_is_public` (`is_public`),
    FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table
CREATE TABLE `notifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `message` text NOT NULL,
    `type` enum('info','success','warning','error','announcement') NOT NULL DEFAULT 'info',
    `category` varchar(50) DEFAULT 'general',
    `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    `read_at` timestamp NULL DEFAULT NULL,
    `action_url` varchar(500) DEFAULT NULL,
    `action_text` varchar(100) DEFAULT NULL,
    `data` json DEFAULT NULL,
    `expires_at` timestamp NULL DEFAULT NULL,
    `sent_via` enum('web','email','push','sms') NOT NULL DEFAULT 'web',
    `created_by` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_type` (`type`),
    KEY `idx_read_at` (`read_at`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_expires_at` (`expires_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification preferences table
CREATE TABLE `notification_preferences` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `category` varchar(50) NOT NULL,
    `email_enabled` tinyint(1) NOT NULL DEFAULT 1,
    `push_enabled` tinyint(1) NOT NULL DEFAULT 1,
    `sms_enabled` tinyint(1) NOT NULL DEFAULT 0,
    `in_app_enabled` tinyint(1) NOT NULL DEFAULT 1,
    `frequency` enum('immediate','hourly','daily','weekly') NOT NULL DEFAULT 'immediate',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_category` (`user_id`,`category`),
    KEY `idx_user_id` (`user_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System logs table
CREATE TABLE `system_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `level` enum('debug','info','warning','error','critical') NOT NULL,
    `message` text NOT NULL,
    `context` json DEFAULT NULL,
    `user_id` int(11) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `url` varchar(500) DEFAULT NULL,
    `method` varchar(10) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_level` (`level`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity logs table
CREATE TABLE `activity_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `action` varchar(100) NOT NULL,
    `resource_type` varchar(50) NOT NULL,
    `resource_id` int(11) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_resource` (`resource_type`,`resource_id`),
    KEY `idx_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit log table
CREATE TABLE `audit_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `action` varchar(100) NOT NULL,
    `table_name` varchar(100) DEFAULT NULL,
    `record_id` int(11) DEFAULT NULL,
    `old_values` json DEFAULT NULL,
    `new_values` json DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_table_name` (`table_name`),
    KEY `idx_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create views for common queries

-- Active members view
CREATE OR REPLACE VIEW `active_members` AS
SELECT 
    m.*,
    u.email,
    u.created_at as user_created_at
FROM members m
JOIN users u ON m.user_id = u.id
WHERE m.status = 'approved' AND u.status = 'active';

-- Recent activities view
CREATE OR REPLACE VIEW `recent_activities` AS
SELECT 
    al.*,
    u.name as user_name,
    u.email as user_email
FROM activity_logs al
JOIN users u ON al.user_id = u.id
ORDER BY al.created_at DESC
LIMIT 100;

-- Pending complaints view
CREATE OR REPLACE VIEW `pending_complaints` AS
SELECT 
    c.*,
    u.name as assigned_to_name
FROM complaints c
LEFT JOIN users u ON c.assigned_to = u.id
WHERE c.status IN ('pending', 'under_review', 'investigating')
ORDER BY c.priority DESC, c.created_at ASC;

-- Newsletter statistics view
CREATE OR REPLACE VIEW `newsletter_stats` AS
SELECT 
    COUNT(*) as total_subscribers,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_subscribers,
    SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed,
    SUM(CASE WHEN DATE(subscribed_at) = CURDATE() THEN 1 ELSE 0 END) as today_subscriptions,
    SUM(CASE WHEN DATE(subscribed_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week_subscriptions,
    SUM(CASE WHEN DATE(subscribed_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as month_subscriptions
FROM newsletter_subscriptions;

-- System statistics view
CREATE OR REPLACE VIEW `system_stats` AS
SELECT 
    (SELECT COUNT(*) FROM users WHERE status = 'active') as active_users,
    (SELECT COUNT(*) FROM members WHERE status = 'approved') as active_members,
    (SELECT COUNT(*) FROM events WHERE status = 'published') as published_events,
    (SELECT COUNT(*) FROM complaints WHERE status = 'pending') as pending_complaints,
    (SELECT COUNT(*) FROM contact_inquiries WHERE status = 'new') as new_inquiries,
    (SELECT COUNT(*) FROM notifications WHERE read_at IS NULL) as unread_notifications,
    (SELECT SUM(amount) FROM donations WHERE status = 'completed') as total_donations;

-- Insert default system settings
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `is_public`) VALUES
('site_name', 'Bharatiya Human Rights Council', 'string', 'Website name', 1),
('site_description', 'Protecting and promoting human rights in India', 'text', 'Website description', 1),
('admin_email', 'admin@bhrcindia.in', 'string', 'Administrator email address', 0),
('max_file_size', '10485760', 'number', 'Maximum file upload size in bytes (10MB)', 0),
('allowed_file_types', '["jpg","jpeg","png","gif","pdf","doc","docx"]', 'json', 'Allowed file upload types', 0),
('email_notifications', '1', 'boolean', 'Enable email notifications', 0),
('sms_notifications', '0', 'boolean', 'Enable SMS notifications', 0),
('maintenance_mode', '0', 'boolean', 'Enable maintenance mode', 0),
('registration_enabled', '1', 'boolean', 'Enable user registration', 1),
('auto_approve_members', '0', 'boolean', 'Auto approve member registrations', 0);

-- Insert default notification preferences for existing users (only if users exist)
INSERT IGNORE INTO `notification_preferences` (`user_id`, `category`, `email_enabled`, `push_enabled`, `sms_enabled`, `in_app_enabled`, `frequency`)
SELECT 
    u.id,
    'events',
    1,
    1,
    0,
    1,
    'immediate'
FROM users u
WHERE EXISTS (SELECT 1 FROM users LIMIT 1);

INSERT IGNORE INTO `notification_preferences` (`user_id`, `category`, `email_enabled`, `push_enabled`, `sms_enabled`, `in_app_enabled`, `frequency`)
SELECT 
    u.id,
    'announcements',
    1,
    1,
    0,
    1,
    'immediate'
FROM users u
WHERE EXISTS (SELECT 1 FROM users LIMIT 1);

INSERT IGNORE INTO `notification_preferences` (`user_id`, `category`, `email_enabled`, `push_enabled`, `sms_enabled`, `in_app_enabled`, `frequency`)
SELECT 
    u.id,
    'complaints',
    1,
    0,
    0,
    1,
    'daily'
FROM users u
WHERE EXISTS (SELECT 1 FROM users LIMIT 1);

INSERT IGNORE INTO `notification_preferences` (`user_id`, `category`, `email_enabled`, `push_enabled`, `sms_enabled`, `in_app_enabled`, `frequency`)
SELECT 
    u.id,
    'system',
    0,
    0,
    0,
    1,
    'immediate'
FROM users u
WHERE EXISTS (SELECT 1 FROM users LIMIT 1);

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;