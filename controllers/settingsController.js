/**
 * Settings Controller
 * Handles system settings API endpoints including configuration management and testing
 * BHRC India - Human Rights Commission Web Application
 */

const mysql = require('mysql2/promise');
const { validationResult } = require('express-validator');
const nodemailer = require('nodemailer');
const { logActivity, logError } = require('../middleware/logger');

class SettingsController {
    constructor() {
        // Database connection configuration
        this.dbConfig = {
            host: '37.27.60.109',
            user: 'tzdmiohj_bhmc',
            password: 'tzdmiohj_bhmc',
            database: 'tzdmiohj_bhmc',
            waitForConnections: true,
            connectionLimit: 10,
            queueLimit: 0
        };
        this.pool = mysql.createPool(this.dbConfig);
    }

    /**
     * Get all system settings
     * Available to admin users only
     */
    async getSettings(req, res) {
        try {
            const user = req.user;
            if (!user || user.role !== 'admin') {
                return res.status(403).json({
                    success: false,
                    message: 'Admin access required'
                });
            }

            const connection = await this.pool.getConnection();

            try {
                const [results] = await connection.execute(
                    'SELECT category, setting_key, setting_value FROM system_settings ORDER BY category, setting_key'
                );

                // Organize settings by category
                const settings = {};
                results.forEach(row => {
                    const { category, setting_key, setting_value } = row;
                    let value = setting_value;

                    // Try to decode JSON values
                    try {
                        const decodedValue = JSON.parse(value);
                        value = decodedValue;
                    } catch (e) {
                        // Handle boolean and numeric values
                        if (value === 'true') {
                            value = true;
                        } else if (value === 'false') {
                            value = false;
                        } else if (!isNaN(value) && value !== '') {
                            value = value.includes('.') ? parseFloat(value) : parseInt(value);
                        }
                    }

                    if (!settings[category]) {
                        settings[category] = {};
                    }
                    settings[category][setting_key] = value;
                });

                // Ensure all categories exist with default values
                const defaultSettings = this.getDefaultSettings();
                Object.keys(defaultSettings).forEach(category => {
                    if (!settings[category]) {
                        settings[category] = {};
                    }
                    Object.keys(defaultSettings[category]).forEach(key => {
                        if (settings[category][key] === undefined) {
                            settings[category][key] = defaultSettings[category][key];
                        }
                    });
                });

                // Log activity
                await logActivity(user.id, 'settings_viewed', 'System settings viewed');

                res.json({
                    success: true,
                    message: 'Settings retrieved successfully',
                    data: settings
                });

            } finally {
                connection.release();
            }

        } catch (error) {
            await logError(error, req.user?.id, 'settings_fetch_error');
            console.error('Error fetching settings:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch settings'
            });
        }
    }

    /**
     * Update system settings
     * Available to admin users only
     */
    async updateSettings(req, res) {
        try {
            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    message: 'Validation failed',
                    errors: errors.array()
                });
            }

            const user = req.user;
            if (!user || user.role !== 'admin') {
                return res.status(403).json({
                    success: false,
                    message: 'Admin access required'
                });
            }

            const data = req.body;
            if (!data) {
                return res.status(400).json({
                    success: false,
                    message: 'Settings data is required'
                });
            }

            const connection = await this.pool.getConnection();

            try {
                await connection.beginTransaction();

                // Validate settings structure
                const allowedCategories = ['general', 'email', 'payment', 'security', 'notifications'];

                for (const [category, settings] of Object.entries(data)) {
                    if (!allowedCategories.includes(category)) {
                        throw new Error(`Invalid settings category: ${category}`);
                    }

                    if (typeof settings !== 'object' || Array.isArray(settings)) {
                        throw new Error(`Settings for category '${category}' must be an object`);
                    }

                    // Validate specific settings
                    this.validateCategorySettings(category, settings);

                    // Update each setting
                    for (const [key, value] of Object.entries(settings)) {
                        // Convert value to string for storage
                        let valueStr;
                        if (typeof value === 'boolean') {
                            valueStr = value ? 'true' : 'false';
                        } else if (typeof value === 'object') {
                            valueStr = JSON.stringify(value);
                        } else {
                            valueStr = String(value);
                        }

                        // Insert or update setting
                        await connection.execute(`
                            INSERT INTO system_settings (category, setting_key, setting_value, updated_at) 
                            VALUES (?, ?, ?, NOW()) 
                            ON DUPLICATE KEY UPDATE 
                            setting_value = VALUES(setting_value), 
                            updated_at = NOW()
                        `, [category, key, valueStr]);
                    }
                }

                await connection.commit();

                // Log the settings update
                await logActivity(user.id, 'settings_updated', 'System settings updated');

                res.json({
                    success: true,
                    message: 'Settings updated successfully'
                });

            } catch (error) {
                await connection.rollback();
                throw error;
            } finally {
                connection.release();
            }

        } catch (error) {
            await logError(error, req.user?.id, 'settings_update_error');
            console.error('Error updating settings:', error);
            res.status(500).json({
                success: false,
                message: error.message || 'Failed to update settings'
            });
        }
    }

    /**
     * Test email settings
     * Available to admin users only
     */
    async testEmailSettings(req, res) {
        try {
            const user = req.user;
            if (!user || user.role !== 'admin') {
                return res.status(403).json({
                    success: false,
                    message: 'Admin access required'
                });
            }

            const { email_settings } = req.body;
            if (!email_settings) {
                return res.status(400).json({
                    success: false,
                    message: 'Email settings are required'
                });
            }

            // Validate required email settings
            const requiredFields = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'from_email', 'from_name'];
            for (const field of requiredFields) {
                if (!email_settings[field]) {
                    return res.status(400).json({
                        success: false,
                        message: `Email setting '${field}' is required`
                    });
                }
            }

            // Test email configuration
            const testResult = await this.sendTestEmail(email_settings);

            if (testResult.success) {
                // Log activity
                await logActivity(user.id, 'email_test_sent', 'Test email sent successfully');

                res.json({
                    success: true,
                    message: 'Test email sent successfully'
                });
            } else {
                res.status(500).json({
                    success: false,
                    message: testResult.message || 'Failed to send test email'
                });
            }

        } catch (error) {
            await logError(error, req.user?.id, 'email_test_error');
            console.error('Error testing email settings:', error);
            res.status(500).json({
                success: false,
                message: 'Email test failed: ' + error.message
            });
        }
    }

    /**
     * Backup current settings
     * Available to admin users only
     */
    async backupSettings(req, res) {
        try {
            const user = req.user;
            if (!user || user.role !== 'admin') {
                return res.status(403).json({
                    success: false,
                    message: 'Admin access required'
                });
            }

            const connection = await this.pool.getConnection();

            try {
                const [settings] = await connection.execute(
                    'SELECT * FROM system_settings ORDER BY category, setting_key'
                );

                const backup = {
                    timestamp: new Date().toISOString(),
                    version: '1.0',
                    settings: settings
                };

                // Store backup in database
                const backupData = JSON.stringify(backup);
                const backupName = `backup_${new Date().toISOString().replace(/[:.]/g, '_')}`;
                
                await connection.execute(`
                    INSERT INTO settings_backups (backup_name, backup_data, created_at) 
                    VALUES (?, ?, NOW())
                `, [backupName, backupData]);

                // Log activity
                await logActivity(user.id, 'settings_backup_created', `Settings backup created: ${backupName}`);

                res.json({
                    success: true,
                    message: 'Settings backup created successfully',
                    data: { backup_name: backupName }
                });

            } finally {
                connection.release();
            }

        } catch (error) {
            await logError(error, req.user?.id, 'settings_backup_error');
            console.error('Error creating settings backup:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to create settings backup'
            });
        }
    }

    /**
     * Restore settings from backup
     * Available to admin users only
     */
    async restoreSettings(req, res) {
        try {
            const user = req.user;
            if (!user || user.role !== 'admin') {
                return res.status(403).json({
                    success: false,
                    message: 'Admin access required'
                });
            }

            const { backup_name } = req.body;
            if (!backup_name) {
                return res.status(400).json({
                    success: false,
                    message: 'Backup name is required'
                });
            }

            const connection = await this.pool.getConnection();

            try {
                // Get backup data
                const [backupRows] = await connection.execute(
                    'SELECT backup_data FROM settings_backups WHERE backup_name = ?',
                    [backup_name]
                );

                if (backupRows.length === 0) {
                    return res.status(404).json({
                        success: false,
                        message: 'Backup not found'
                    });
                }

                const backup = JSON.parse(backupRows[0].backup_data);
                if (!backup || !backup.settings) {
                    return res.status(400).json({
                        success: false,
                        message: 'Invalid backup data'
                    });
                }

                await connection.beginTransaction();

                // Clear current settings
                await connection.execute('DELETE FROM system_settings');

                // Restore settings
                for (const setting of backup.settings) {
                    await connection.execute(`
                        INSERT INTO system_settings (category, setting_key, setting_value, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?)
                    `, [
                        setting.category,
                        setting.setting_key,
                        setting.setting_value,
                        setting.created_at,
                        setting.updated_at
                    ]);
                }

                await connection.commit();

                // Log the restore
                await logActivity(user.id, 'settings_restored', `Settings restored from backup: ${backup_name}`);

                res.json({
                    success: true,
                    message: 'Settings restored successfully'
                });

            } catch (error) {
                await connection.rollback();
                throw error;
            } finally {
                connection.release();
            }

        } catch (error) {
            await logError(error, req.user?.id, 'settings_restore_error');
            console.error('Error restoring settings:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to restore settings'
            });
        }
    }

    /**
     * Get list of available backups
     * Available to admin users only
     */
    async getBackups(req, res) {
        try {
            const user = req.user;
            if (!user || user.role !== 'admin') {
                return res.status(403).json({
                    success: false,
                    message: 'Admin access required'
                });
            }

            const connection = await this.pool.getConnection();

            try {
                const [backups] = await connection.execute(`
                    SELECT backup_name, created_at 
                    FROM settings_backups 
                    ORDER BY created_at DESC
                `);

                res.json({
                    success: true,
                    message: 'Backups retrieved successfully',
                    data: backups
                });

            } finally {
                connection.release();
            }

        } catch (error) {
            await logError(error, req.user?.id, 'settings_backups_list_error');
            console.error('Error fetching backups:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch backups'
            });
        }
    }

    /**
     * Validate category-specific settings
     */
    validateCategorySettings(category, settings) {
        switch (category) {
            case 'general':
                if (settings.contact_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(settings.contact_email)) {
                    throw new Error('Invalid contact email address');
                }
                break;

            case 'email':
                if (settings.smtp_port && (!Number.isInteger(Number(settings.smtp_port)) || settings.smtp_port < 1 || settings.smtp_port > 65535)) {
                    throw new Error('SMTP port must be between 1 and 65535');
                }
                if (settings.from_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(settings.from_email)) {
                    throw new Error('Invalid from email address');
                }
                break;

            case 'payment':
                if (settings.min_donation_amount && (!Number.isFinite(Number(settings.min_donation_amount)) || Number(settings.min_donation_amount) < 0)) {
                    throw new Error('Minimum donation amount must be a positive number');
                }
                if (settings.max_donation_amount && (!Number.isFinite(Number(settings.max_donation_amount)) || Number(settings.max_donation_amount) < 0)) {
                    throw new Error('Maximum donation amount must be a positive number');
                }
                if (settings.processing_fee_percentage && (!Number.isFinite(Number(settings.processing_fee_percentage)) || Number(settings.processing_fee_percentage) < 0 || Number(settings.processing_fee_percentage) > 100)) {
                    throw new Error('Processing fee percentage must be between 0 and 100');
                }
                break;

            case 'security':
                if (settings.session_timeout && (!Number.isInteger(Number(settings.session_timeout)) || Number(settings.session_timeout) < 1)) {
                    throw new Error('Session timeout must be at least 1 minute');
                }
                if (settings.password_min_length && (!Number.isInteger(Number(settings.password_min_length)) || Number(settings.password_min_length) < 4)) {
                    throw new Error('Password minimum length must be at least 4 characters');
                }
                if (settings.max_login_attempts && (!Number.isInteger(Number(settings.max_login_attempts)) || Number(settings.max_login_attempts) < 1)) {
                    throw new Error('Max login attempts must be at least 1');
                }
                break;

            case 'notifications':
                if (settings.admin_notification_email && settings.admin_notification_email !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(settings.admin_notification_email)) {
                    throw new Error('Invalid admin notification email address');
                }
                if (settings.reminder_days_before && (!Number.isInteger(Number(settings.reminder_days_before)) || Number(settings.reminder_days_before) < 0)) {
                    throw new Error('Reminder days before must be a positive number');
                }
                break;
        }
    }

    /**
     * Get default settings structure
     */
    getDefaultSettings() {
        return {
            general: {
                site_name: 'BHMC Organization',
                site_description: 'Building Healthy Muslim Communities',
                contact_email: 'contact@bhmc.org',
                contact_phone: '',
                address: '',
                timezone: 'UTC',
                language: 'en',
                currency: 'USD'
            },
            email: {
                smtp_host: '',
                smtp_port: 587,
                smtp_username: '',
                smtp_password: '',
                from_email: '',
                from_name: '',
                use_ssl: true,
                send_notifications: true
            },
            payment: {
                paypal_client_id: '',
                paypal_client_secret: '',
                stripe_publishable_key: '',
                stripe_secret_key: '',
                min_donation_amount: 5.00,
                max_donation_amount: 10000.00,
                processing_fee_percentage: 2.9,
                processing_fee_fixed: 0.30,
                enable_recurring_donations: true,
                enable_anonymous_donations: true
            },
            security: {
                session_timeout: 30,
                password_min_length: 8,
                max_login_attempts: 5,
                lockout_duration: 15,
                require_password_complexity: true,
                enable_two_factor: false,
                log_user_activities: true,
                enable_ip_whitelist: false,
                allowed_ips: ''
            },
            notifications: {
                new_donation_notification: true,
                event_registration_notification: true,
                new_user_notification: true,
                send_donation_receipts: true,
                send_event_confirmations: true,
                send_event_reminders: true,
                reminder_days_before: 3,
                admin_notification_email: ''
            }
        };
    }

    /**
     * Send test email
     */
    async sendTestEmail(emailSettings) {
        try {
            // Create transporter
            const transporter = nodemailer.createTransporter({
                host: emailSettings.smtp_host,
                port: parseInt(emailSettings.smtp_port),
                secure: emailSettings.use_ssl || false,
                auth: {
                    user: emailSettings.smtp_username,
                    pass: emailSettings.smtp_password
                }
            });

            // Send test email
            const info = await transporter.sendMail({
                from: `"${emailSettings.from_name}" <${emailSettings.from_email}>`,
                to: emailSettings.smtp_username,
                subject: `Test Email from ${emailSettings.from_name || 'System'}`,
                text: `This is a test email to verify your email configuration.\n\nIf you received this email, your email settings are working correctly.\n\nSent at: ${new Date().toISOString()}`
            });

            return { success: true, messageId: info.messageId };

        } catch (error) {
            console.error('Test email error:', error);
            return { success: false, message: error.message };
        }
    }
}

module.exports = new SettingsController();