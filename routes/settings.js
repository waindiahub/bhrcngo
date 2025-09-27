/**
 * Settings Routes
 * Handles system settings API endpoints including configuration management and testing
 * BHRC India - Human Rights Commission Web Application
 */

const express = require('express');
const router = express.Router();
const { body, query } = require('express-validator');
const settingsController = require('../controllers/settingsController');
const { authenticateToken, authorizeRoles } = require('../middleware/auth');

// Validation rules for settings update
const validateSettingsUpdate = [
    body('general.contact_email')
        .optional()
        .isEmail()
        .withMessage('Invalid contact email address'),
    body('general.site_name')
        .optional()
        .isLength({ min: 1, max: 100 })
        .withMessage('Site name must be between 1 and 100 characters'),
    body('email.smtp_port')
        .optional()
        .isInt({ min: 1, max: 65535 })
        .withMessage('SMTP port must be between 1 and 65535'),
    body('email.from_email')
        .optional()
        .isEmail()
        .withMessage('Invalid from email address'),
    body('payment.min_donation_amount')
        .optional()
        .isFloat({ min: 0 })
        .withMessage('Minimum donation amount must be a positive number'),
    body('payment.max_donation_amount')
        .optional()
        .isFloat({ min: 0 })
        .withMessage('Maximum donation amount must be a positive number'),
    body('payment.processing_fee_percentage')
        .optional()
        .isFloat({ min: 0, max: 100 })
        .withMessage('Processing fee percentage must be between 0 and 100'),
    body('security.session_timeout')
        .optional()
        .isInt({ min: 1 })
        .withMessage('Session timeout must be at least 1 minute'),
    body('security.password_min_length')
        .optional()
        .isInt({ min: 4 })
        .withMessage('Password minimum length must be at least 4 characters'),
    body('security.max_login_attempts')
        .optional()
        .isInt({ min: 1 })
        .withMessage('Max login attempts must be at least 1'),
    body('notifications.admin_notification_email')
        .optional()
        .custom((value) => {
            if (value && value !== '') {
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    throw new Error('Invalid admin notification email address');
                }
            }
            return true;
        }),
    body('notifications.reminder_days_before')
        .optional()
        .isInt({ min: 0 })
        .withMessage('Reminder days before must be a positive number')
];

// Validation rules for email test
const validateEmailTest = [
    body('email_settings.smtp_host')
        .notEmpty()
        .withMessage('SMTP host is required'),
    body('email_settings.smtp_port')
        .isInt({ min: 1, max: 65535 })
        .withMessage('SMTP port must be between 1 and 65535'),
    body('email_settings.smtp_username')
        .notEmpty()
        .withMessage('SMTP username is required'),
    body('email_settings.smtp_password')
        .notEmpty()
        .withMessage('SMTP password is required'),
    body('email_settings.from_email')
        .isEmail()
        .withMessage('Valid from email is required'),
    body('email_settings.from_name')
        .notEmpty()
        .withMessage('From name is required')
];

// Validation rules for backup restore
const validateBackupRestore = [
    body('backup_name')
        .notEmpty()
        .withMessage('Backup name is required')
        .matches(/^backup_\d{4}-\d{2}-\d{2}T\d{2}_\d{2}_\d{2}_\d{3}Z$/)
        .withMessage('Invalid backup name format')
];

/**
 * @route GET /api/settings
 * @desc Get all system settings
 * @access Admin only
 */
router.get('/', 
    authenticateToken,
    authorizeRoles(['admin']),
    settingsController.getSettings
);

/**
 * @route PUT /api/settings
 * @desc Update system settings
 * @access Admin only
 */
router.put('/', 
    authenticateToken,
    authorizeRoles(['admin']),
    validateSettingsUpdate,
    settingsController.updateSettings
);

/**
 * @route POST /api/settings/test-email
 * @desc Test email settings
 * @access Admin only
 */
router.post('/test-email', 
    authenticateToken,
    authorizeRoles(['admin']),
    validateEmailTest,
    settingsController.testEmailSettings
);

/**
 * @route POST /api/settings/backup
 * @desc Create settings backup
 * @access Admin only
 */
router.post('/backup', 
    authenticateToken,
    authorizeRoles(['admin']),
    settingsController.backupSettings
);

/**
 * @route GET /api/settings/backups
 * @desc Get list of available backups
 * @access Admin only
 */
router.get('/backups', 
    authenticateToken,
    authorizeRoles(['admin']),
    settingsController.getBackups
);

/**
 * @route POST /api/settings/restore
 * @desc Restore settings from backup
 * @access Admin only
 */
router.post('/restore', 
    authenticateToken,
    authorizeRoles(['admin']),
    validateBackupRestore,
    settingsController.restoreSettings
);

module.exports = router;