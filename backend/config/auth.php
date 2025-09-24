<?php
/**
 * Authentication Configuration
 * BHRC - Bharatiya Human Rights Council
 */

// JWT Configuration
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'bhrc_jwt_secret_key_change_in_production_' . hash('sha256', __DIR__));
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRY', 86400); // 24 hours
define('JWT_REFRESH_EXPIRY', 604800); // 7 days

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_REGENERATE_INTERVAL', 300); // 5 minutes
define('REMEMBER_ME_DURATION', 2592000); // 30 days

// Password Configuration
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SYMBOLS', true);
define('PASSWORD_HASH_ALGO', PASSWORD_ARGON2ID);

// Account Security
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes
define('PASSWORD_RESET_EXPIRY', 3600); // 1 hour
define('EMAIL_VERIFICATION_EXPIRY', 86400); // 24 hours

// Two-Factor Authentication
define('ENABLE_2FA', true);
define('OTP_LENGTH', 6);
define('OTP_EXPIRY', 300); // 5 minutes
define('BACKUP_CODES_COUNT', 10);

// API Rate Limiting
define('API_RATE_LIMIT', 100); // requests per hour
define('AUTH_RATE_LIMIT', 10); // login attempts per hour

// Security Headers
$SECURITY_HEADERS = [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com;"
];

// Public Routes (no authentication required)
$PUBLIC_ROUTES = [
    '/api/auth/login',
    '/api/auth/register',
    '/api/auth/forgot-password',
    '/api/auth/reset-password',
    '/api/auth/verify-email',
    '/api/contact/submit',
    '/api/newsletter/subscribe',
    '/api/public/gallery',
    '/api/public/events',
    '/api/public/activities',
    '/api/public/news',
    '/api/public/about'
];

// Role-based permissions
$ROLE_PERMISSIONS = [
    'super_admin' => [
        'users.create', 'users.read', 'users.update', 'users.delete',
        'admin.create', 'admin.read', 'admin.update', 'admin.delete',
        'system.backup', 'system.restore', 'system.logs',
        'settings.update', 'roles.manage'
    ],
    'admin' => [
        'users.read', 'users.update',
        'content.create', 'content.read', 'content.update', 'content.delete',
        'events.create', 'events.read', 'events.update', 'events.delete',
        'gallery.create', 'gallery.read', 'gallery.update', 'gallery.delete',
        'newsletter.create', 'newsletter.read', 'newsletter.update', 'newsletter.send',
        'complaints.read', 'complaints.update', 'complaints.respond',
        'donations.read', 'reports.generate'
    ],
    'moderator' => [
        'content.read', 'content.update',
        'events.read', 'events.update',
        'gallery.read', 'gallery.update',
        'complaints.read', 'complaints.respond',
        'users.read'
    ],
    'member' => [
        'profile.read', 'profile.update',
        'complaints.create', 'complaints.read',
        'events.read', 'events.register',
        'donations.create', 'donations.read',
        'gallery.read', 'newsletter.subscribe'
    ],
    'user' => [
        'profile.read', 'profile.update',
        'events.read', 'gallery.read',
        'newsletter.subscribe'
    ]
];

/**
 * Check if user has permission
 */
function hasPermission($userRole, $permission) {
    global $ROLE_PERMISSIONS;
    return in_array($permission, $ROLE_PERMISSIONS[$userRole] ?? []);
}

/**
 * Get user role hierarchy
 */
function getRoleHierarchy() {
    return [
        'super_admin' => 5,
        'admin' => 4,
        'moderator' => 3,
        'member' => 2,
        'user' => 1
    ];
}

/**
 * Check if role has higher or equal privilege
 */
function hasRolePrivilege($userRole, $requiredRole) {
    $hierarchy = getRoleHierarchy();
    $userLevel = $hierarchy[$userRole] ?? 0;
    $requiredLevel = $hierarchy[$requiredRole] ?? 0;
    return $userLevel >= $requiredLevel;
}