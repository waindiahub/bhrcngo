<?php

class Database {
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $pdo;
    
    public function __construct($config = null) {
        if ($config) {
            $this->host = $config['host'];
            $this->dbname = $config['dbname'];
            $this->username = $config['username'];
            $this->password = $config['password'];
        } else {
            // Default configuration - should be moved to config file
            $this->host = 'localhost';
            $this->dbname = 'bhrc_india';
            $this->username = 'root';
            $this->password = '';
        }
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        }
        
        return $this->pdo;
    }
    
    /**
     * Create database tables
     */
    public function createTables() {
        $connection = $this->getConnection();
        
        try {
            $connection->beginTransaction();
            
            // Users table
            $connection->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    phone VARCHAR(20),
                    password_hash VARCHAR(255),
                    role ENUM('admin', 'moderator', 'user') DEFAULT 'user',
                    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
                    email_verified BOOLEAN DEFAULT FALSE,
                    phone_verified BOOLEAN DEFAULT FALSE,
                    otp_code VARCHAR(6),
                    otp_expires_at TIMESTAMP NULL,
                    last_login TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_phone (phone),
                    INDEX idx_role (role),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Members table
            $connection->exec("
                CREATE TABLE IF NOT EXISTS members (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    position VARCHAR(255) NOT NULL,
                    category ENUM('board', 'staff', 'volunteers', 'advisors') NOT NULL,
                    bio TEXT,
                    experience VARCHAR(100),
                    specialization VARCHAR(255),
                    education TEXT,
                    email VARCHAR(255),
                    phone VARCHAR(20),
                    social_links JSON,
                    image VARCHAR(255),
                    display_order INT DEFAULT 0,
                    join_date DATE,
                    status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_category (category),
                    INDEX idx_status (status),
                    INDEX idx_display_order (display_order)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Complaints table
            $connection->exec("
                CREATE TABLE IF NOT EXISTS complaints (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    complaint_number VARCHAR(50) UNIQUE NOT NULL,
                    complainant_name VARCHAR(255) NOT NULL,
                    complainant_email VARCHAR(255),
                    complainant_phone VARCHAR(20),
                    complainant_address TEXT,
                    victim_name VARCHAR(255),
                    victim_age INT,
                    victim_gender ENUM('male', 'female', 'other'),
                    incident_date DATE,
                    incident_location TEXT,
                    complaint_type VARCHAR(100) NOT NULL,
                    description TEXT NOT NULL,
                    evidence_files JSON,
                    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
                    status ENUM('pending', 'under_review', 'investigating', 'resolved', 'closed', 'rejected') DEFAULT 'pending',
                    assigned_to INT,
                    resolution TEXT,
                    resolved_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_complaint_number (complaint_number),
                    INDEX idx_status (status),
                    INDEX idx_priority (priority),
                    INDEX idx_complaint_type (complaint_type),
                    INDEX idx_assigned_to (assigned_to),
                    INDEX idx_created_at (created_at),
                    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Events table
            $connection->exec("
                CREATE TABLE IF NOT EXISTS events (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    content TEXT,
                    event_date DATE NOT NULL,
                    event_time TIME,
                    end_date DATE,
                    end_time TIME,
                    location VARCHAR(255),
                    address TEXT,
                    category VARCHAR(100),
                    tags JSON,
                    image VARCHAR(255),
                    gallery_images JSON,
                    max_participants INT,
                    registration_required BOOLEAN DEFAULT FALSE,
                    registration_deadline DATE,
                    contact_person VARCHAR(255),
                    contact_email VARCHAR(255),
                    contact_phone VARCHAR(20),
                    status ENUM('draft', 'published', 'cancelled', 'completed') DEFAULT 'draft',
                    featured BOOLEAN DEFAULT FALSE,
                    created_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_event_date (event_date),
                    INDEX idx_status (status),
                    INDEX idx_category (category),
                    INDEX idx_featured (featured),
                    INDEX idx_created_by (created_by),
                    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Activities table
            $connection->exec("
                CREATE TABLE IF NOT EXISTS activities (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    content TEXT,
                    activity_date DATE,
                    location VARCHAR(255),
                    category VARCHAR(100),
                    tags JSON,
                    image VARCHAR(255),
                    gallery_images JSON,
                    participants_count INT DEFAULT 0,
                    impact_description TEXT,
                    beneficiaries_count INT DEFAULT 0,
                    status ENUM('planned', 'ongoing', 'completed', 'cancelled') DEFAULT 'planned',
                    featured BOOLEAN DEFAULT FALSE,
                    created_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_activity_date (activity_date),
                    INDEX idx_status (status),
                    INDEX idx_category (category),
                    INDEX idx_featured (featured),
                    INDEX idx_created_by (created_by),
                    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Gallery table
            $connection->exec("
                CREATE TABLE IF NOT EXISTS gallery (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    type ENUM('image', 'video') NOT NULL,
                    file_path VARCHAR(500) NOT NULL,
                    thumbnail_path VARCHAR(500),
                    category VARCHAR(100),
                    tags JSON,
                    alt_text VARCHAR(255),
                    caption TEXT,
                    event_id INT,
                    activity_id INT,
                    upload_date DATE NOT NULL,
                    file_size INT,
                    dimensions VARCHAR(20),
                    status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
                    featured BOOLEAN DEFAULT FALSE,
                    uploaded_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_type (type),
                    INDEX idx_category (category),
                    INDEX idx_status (status),
                    INDEX idx_featured (featured),
                    INDEX idx_upload_date (upload_date),
                    INDEX idx_event_id (event_id),
                    INDEX idx_activity_id (activity_id),
                    INDEX idx_uploaded_by (uploaded_by),
                    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
                    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE SET NULL,
                    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Donations table
            $connection->exec("
                CREATE TABLE IF NOT EXISTS donations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    donation_id VARCHAR(50) UNIQUE NOT NULL,
                    donor_name VARCHAR(255) NOT NULL,
                    donor_email VARCHAR(255),
                    donor_phone VARCHAR(20),
                    donor_address TEXT,
                    amount DECIMAL(10,2) NOT NULL,
                    currency VARCHAR(3) DEFAULT 'INR',
                    donation_type ENUM('one_time', 'monthly', 'yearly') DEFAULT 'one_time',
                    purpose VARCHAR(255),
                    message TEXT,
                    payment_method VARCHAR(50),
                    payment_gateway VARCHAR(50),
                    transaction_id VARCHAR(100),
                    gateway_response JSON,
                    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
                    anonymous BOOLEAN DEFAULT FALSE,
                    receipt_sent BOOLEAN DEFAULT FALSE,
                    tax_exemption_claimed BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_donation_id (donation_id),
                    INDEX idx_status (status),
                    INDEX idx_donation_type (donation_type),
                    INDEX idx_amount (amount),
                    INDEX idx_created_at (created_at),
                    INDEX idx_donor_email (donor_email)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Event registrations table
            $connection->exec("
                CREATE TABLE IF NOT EXISTS event_registrations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    event_id INT NOT NULL,
                    participant_name VARCHAR(255) NOT NULL,
                    participant_email VARCHAR(255) NOT NULL,
                    participant_phone VARCHAR(20),
                    organization VARCHAR(255),
                    special_requirements TEXT,
                    status ENUM('registered', 'confirmed', 'attended', 'cancelled') DEFAULT 'registered',
                    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    confirmed_at TIMESTAMP NULL,
                    INDEX idx_event_id (event_id),
                    INDEX idx_status (status),
                    INDEX idx_participant_email (participant_email),
                    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Newsletter subscribers table
            $connection->exec("
                CREATE TABLE IF NOT EXISTS newsletter_subscribers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    name VARCHAR(255),
                    status ENUM('active', 'inactive', 'unsubscribed') DEFAULT 'active',
                    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    unsubscribed_at TIMESTAMP NULL,
                    INDEX idx_email (email),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Activity logs table
            $connection->exec("
                CREATE TABLE IF NOT EXISTS activity_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    action VARCHAR(100) NOT NULL,
                    entity_type VARCHAR(50),
                    entity_id INT,
                    description TEXT,
                    user_id INT,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_action (action),
                    INDEX idx_entity_type (entity_type),
                    INDEX idx_entity_id (entity_id),
                    INDEX idx_user_id (user_id),
                    INDEX idx_created_at (created_at),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Settings table
            $connection->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(100) UNIQUE NOT NULL,
                    setting_value TEXT,
                    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
                    description TEXT,
                    updated_by INT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_setting_key (setting_key),
                    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            $connection->commit();
            return true;
            
        } catch (Exception $e) {
            $connection->rollBack();
            throw new Exception("Failed to create tables: " . $e->getMessage());
        }
    }
    
    /**
     * Insert default data
     */
    public function insertDefaultData() {
        $connection = $this->getConnection();
        
        try {
            $connection->beginTransaction();
            
            // Insert default admin user
            $adminExists = $connection->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
            
            if ($adminExists == 0) {
                $stmt = $connection->prepare("
                    INSERT INTO users (name, email, password_hash, role, status, email_verified) 
                    VALUES (?, ?, ?, 'admin', 'active', TRUE)
                ");
                $stmt->execute([
                    'BHRC Admin',
                    'admin@bhrcindia.in',
                    password_hash('admin123', PASSWORD_DEFAULT)
                ]);
            }
            
            // Insert default settings
            $defaultSettings = [
                ['site_name', 'BHRC India', 'string', 'Website name'],
                ['site_description', 'Bharatiya Human Rights Commission - Protecting and promoting human rights', 'string', 'Website description'],
                ['contact_email', 'info@bhrcindia.in', 'string', 'Primary contact email'],
                ['contact_phone', '+91-612-2234567', 'string', 'Primary contact phone'],
                ['contact_address', 'Patna, Bharatiya, India', 'string', 'Primary contact address'],
                ['max_file_size', '5242880', 'number', 'Maximum file upload size in bytes (5MB)'],
                ['allowed_file_types', '["jpg","jpeg","png","gif","pdf","doc","docx"]', 'json', 'Allowed file types for uploads'],
                ['email_notifications', 'true', 'boolean', 'Enable email notifications'],
                ['maintenance_mode', 'false', 'boolean', 'Enable maintenance mode']
            ];
            
            foreach ($defaultSettings as $setting) {
                $stmt = $connection->prepare("
                    INSERT IGNORE INTO settings (setting_key, setting_value, setting_type, description) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute($setting);
            }
            
            $connection->commit();
            return true;
            
        } catch (Exception $e) {
            $connection->rollBack();
            throw new Exception("Failed to insert default data: " . $e->getMessage());
        }
    }
    
    /**
     * Check if database exists and create if not
     */
    public function initializeDatabase() {
        try {
            // First, connect without specifying database
            $dsn = "mysql:host={$this->host};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // Create database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Now connect to the specific database
            $this->getConnection();
            
            // Create tables
            $this->createTables();
            
            // Insert default data
            $this->insertDefaultData();
            
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Database initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get database statistics
     */
    public function getStats() {
        $connection = $this->getConnection();
        
        $stats = [];
        
        $tables = [
            'users', 'members', 'complaints', 'events', 'activities', 
            'gallery', 'donations', 'event_registrations', 'newsletter_subscribers'
        ];
        
        foreach ($tables as $table) {
            $stmt = $connection->query("SELECT COUNT(*) FROM {$table}");
            $stats[$table] = $stmt->fetchColumn();
        }
        
        return $stats;
    }
    
    /**
     * Backup database
     */
    public function backup($filename = null) {
        if (!$filename) {
            $filename = 'bhrc_backup_' . date('Y-m-d_H-i-s') . '.sql';
        }
        
        $backupPath = '../backups/';
        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0755, true);
        }
        
        $command = "mysqldump --user={$this->username} --password={$this->password} --host={$this->host} {$this->dbname} > {$backupPath}{$filename}";
        
        $result = null;
        $output = null;
        exec($command, $output, $result);
        
        if ($result === 0) {
            return $backupPath . $filename;
        } else {
            throw new Exception("Backup failed");
        }
    }
}

?>