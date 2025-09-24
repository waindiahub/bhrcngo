<?php
/**
 * Configuration File
 * BHRC - Bharatiya Human Rights Council
 */

// Environment Configuration
define('ENVIRONMENT', $_ENV['APP_ENV'] ?? 'development');
define('DEBUG_MODE', ENVIRONMENT === 'development');

// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? '37.27.60.109');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'tzdmiohj_bhmc');
define('DB_USER', $_ENV['DB_USER'] ?? 'tzdmiohj_bhmc');
define('DB_PASS', $_ENV['DB_PASS'] ?? 'tzdmiohj_bhmc');
define('DB_CHARSET', 'utf8mb4');
define('DB_PORT', $_ENV['DB_PORT'] ?? 3306);

// Configuration array for Database class
return [
    'database' => [
        'host' => DB_HOST,
        'database' => DB_NAME,
        'username' => DB_USER,
        'password' => DB_PASS,
        'charset' => DB_CHARSET,
        'port' => DB_PORT
    ]
];

// API Configuration
define('API_BASE_URL', $_ENV['API_BASE_URL'] ?? 'http://localhost/bhrc-clean/backend/api');
define('API_VERSION', 'v1');

// Security Configuration
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'your-jwt-secret-key-change-in-production');
define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? 'your-encryption-key-change-in-production');
define('SESSION_SECURE', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');

// Upload Configuration
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);
define('UPLOAD_DIR', __DIR__ . '/../../uploads/');

// Path Configuration
define('UPLOAD_PATH', __DIR__ . '/../../uploads');
define('LOG_PATH', __DIR__ . '/../../logs');

// Email Configuration
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'localhost');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
define('SMTP_ENCRYPTION', $_ENV['SMTP_ENCRYPTION'] ?? 'tls');
define('FROM_EMAIL', $_ENV['FROM_EMAIL'] ?? 'noreply@bhrcindia.in');
define('FROM_NAME', $_ENV['FROM_NAME'] ?? 'BHRC India');

// Application Settings
define('APP_NAME', 'BHRC India');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/bhrc-clean');
define('TIMEZONE', 'Asia/Kolkata');

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'BHRC_SESSION');

// Rate Limiting
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600); // 1 hour

// Pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600);

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting based on environment
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session configuration
if (!headers_sent()) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', SESSION_SECURE ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.name', SESSION_NAME);
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
}

// Create necessary directories
$directories = [UPLOAD_PATH, LOG_PATH];
foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

return [
    'environment' => ENVIRONMENT,
    'debug' => DEBUG_MODE,
    'database' => [
        'host' => DB_HOST,
        'database' => DB_NAME,
        'username' => DB_USER,
        'password' => DB_PASS,
        'charset' => DB_CHARSET,
        'port' => DB_PORT
    ],
    'api' => [
        'base_url' => API_BASE_URL,
        'version' => API_VERSION
    ],
    'security' => [
        'jwt_secret' => JWT_SECRET,
        'encryption_key' => ENCRYPTION_KEY,
        'session_secure' => SESSION_SECURE
    ],
    'upload' => [
        'max_size' => MAX_UPLOAD_SIZE,
        'allowed_types' => ALLOWED_FILE_TYPES,
        'path' => UPLOAD_PATH
    ],
    'email' => [
        'smtp_host' => SMTP_HOST,
        'smtp_port' => SMTP_PORT,
        'smtp_username' => SMTP_USERNAME,
        'smtp_password' => SMTP_PASSWORD,
        'smtp_encryption' => SMTP_ENCRYPTION,
        'from_email' => FROM_EMAIL,
        'from_name' => FROM_NAME
    ]
];
?>