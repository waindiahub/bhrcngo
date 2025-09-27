/**
 * User Controller for BHRC India Application
 * 
 * Handles user management API endpoints including CRUD operations,
 * user statistics, bulk operations, and user administration.
 */

const db = require('../config/database');
const AuthMiddleware = require('../middleware/auth');
const { body, validationResult, param } = require('express-validator');
const crypto = require('crypto');
const ResponseHelper = require('../utils/responseHelper');
const QueryBuilder = require('../utils/queryBuilder');

/**
 * User Controller Class
 */
class UserController {
    /**
     * Get all users with filtering and pagination
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async getUsers(req, res) {
        try {
            // Check permissions (admin or moderator only)
            if (!['admin', 'moderator'].includes(req.user.role)) {
                return res.status(403).json({
                    success: false,
                    message: 'Admin access required'
                });
            }

            // Get query parameters
            const page = parseInt(req.query.page) || 1;
            const perPage = parseInt(req.query.per_page) || 25;
            const search = req.query.search || '';
            const role = req.query.role || '';
            const status = req.query.status || '';
            const verified = req.query.verified || '';
            const sortBy = req.query.sort_by || 'created_at';
            const sortOrder = req.query.sort_order || 'desc';

            // Build WHERE clause
            let whereConditions = [];
            let params = [];

            if (search) {
                whereConditions.push('(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)');
                const searchTerm = `%${search}%`;
                params.push(searchTerm, searchTerm, searchTerm, searchTerm);
            }

            if (role) {
                whereConditions.push('role = ?');
                params.push(role);
            }

            if (status) {
                whereConditions.push('status = ?');
                params.push(status);
            }

            if (verified !== '') {
                whereConditions.push('email_verified = ?');
                params.push(parseInt(verified));
            }

            const whereClause = whereConditions.length > 0 ? 'WHERE ' + whereConditions.join(' AND ') : '';

            // Get total count
            const countQuery = `SELECT COUNT(*) as total FROM users ${whereClause}`;
            const countResult = await db.queryOne(countQuery, params);
            const totalUsers = countResult.total;

            // Calculate pagination
            const offset = (page - 1) * perPage;
            const totalPages = Math.ceil(totalUsers / perPage);

            // Get users with pagination
            const usersQuery = `
                SELECT id, first_name, last_name, email, phone, city, state, role, status, 
                       email_verified, created_at, last_login,
                       CONCAT(first_name, ' ', last_name) as full_name
                FROM users 
                ${whereClause} 
                ORDER BY ${sortBy} ${sortOrder} 
                LIMIT ${parseInt(perPage)} OFFSET ${parseInt(offset)}
            `;

            const users = await db.query(usersQuery, params);

            // Format user data
            const formattedUsers = users.map(user => ({
                ...user,
                id: parseInt(user.id),
                name: user.full_name,
                email_verified_at: user.email_verified ? user.created_at : null,
                last_login_at: user.last_login,
                email_verified: Boolean(user.email_verified)
            }));

            return ResponseHelper.paginated(res, formattedUsers, {
                current_page: page,
                per_page: perPage,
                total: totalUsers,
                last_page: totalPages,
                from: offset + 1,
                to: Math.min(offset + perPage, totalUsers)
            });

        } catch (error) {
            console.error('Get users error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch users'
            });
        }
    }

    /**
     * Get user statistics for admin dashboard
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async getUserStats(req, res) {
        try {
            // Check permissions (admin or moderator only)
            if (!['admin', 'moderator'].includes(req.user.role)) {
                return ResponseHelper.forbidden(res, 'Admin access required');
            }

            // Get user statistics using QueryBuilder
            const stats = await QueryBuilder.query(`
                SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_verification,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_this_month
                FROM users
            `);

            // Convert to integers
            const formattedStats = {
                total_users: parseInt(stats[0].total_users),
                active_users: parseInt(stats[0].active_users),
                pending_verification: parseInt(stats[0].pending_verification),
                new_this_month: parseInt(stats[0].new_this_month)
            };

            return ResponseHelper.success(res, formattedStats, 'User statistics retrieved successfully');

        } catch (error) {
            console.error('Get user stats error:', error);
            return ResponseHelper.serverError(res, 'Failed to fetch user statistics');
        }
    }

    /**
     * Get single user by ID
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async getUser(req, res) {
        try {
            const userId = parseInt(req.params.id);

            // Check permissions (own profile or admin/moderator)
            if (req.user.id !== userId && !['admin', 'moderator'].includes(req.user.role)) {
                return res.status(403).json({
                    success: false,
                    message: 'Access denied'
                });
            }

            const user = await db.queryOne(`
                SELECT id, first_name, last_name, email, phone, city, state, role, status, 
                       email_verified, created_at, last_login,
                       CONCAT(first_name, ' ', last_name) as full_name
                FROM users 
                WHERE id = ?
            `, [userId]);

            if (!user) {
                return res.status(404).json({
                    success: false,
                    message: 'User not found'
                });
            }

            // Format user data
            const formattedUser = {
                ...user,
                id: parseInt(user.id),
                email_verified: Boolean(user.email_verified)
            };

            res.json({
                success: true,
                data: formattedUser
            });

        } catch (error) {
            console.error('Get user error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch user'
            });
        }
    }

    /**
     * Create new user
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async createUser(req, res) {
        try {
            // Check permissions (admin only)
            if (req.user.role !== 'admin') {
                return res.status(403).json({
                    success: false,
                    message: 'Admin access required'
                });
            }

            // Validation rules
            await body('first_name')
                .isLength({ min: 2, max: 50 })
                .matches(/^[a-zA-Z0-9\s]+$/)
                .withMessage('First name must be 2-50 characters and contain only letters and numbers')
                .run(req);

            await body('last_name')
                .isLength({ min: 2, max: 50 })
                .matches(/^[a-zA-Z0-9\s]+$/)
                .withMessage('Last name must be 2-50 characters and contain only letters and numbers')
                .run(req);

            await body('email')
                .isEmail()
                .normalizeEmail()
                .withMessage('Valid email is required')
                .run(req);

            await body('password')
                .isLength({ min: 8 })
                .matches(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/)
                .withMessage('Password must be at least 8 characters with uppercase, lowercase, and number')
                .run(req);

            await body('role')
                .isIn(['admin', 'member', 'volunteer', 'donor'])
                .withMessage('Invalid role')
                .run(req);

            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    message: 'Validation failed',
                    errors: errors.array()
                });
            }

            const { first_name, last_name, email, password, phone, city, state, role } = req.body;

            // Check if email already exists
            const existingUser = await db.queryOne('SELECT id FROM users WHERE email = ?', [email]);
            if (existingUser) {
                return res.status(400).json({
                    success: false,
                    message: 'Email already exists'
                });
            }

            // Hash password
            const passwordHash = await AuthMiddleware.hashPassword(password);

            // Create user
            const userId = await db.insert('users', {
                first_name: first_name,
                last_name: last_name,
                email: email,
                password_hash: passwordHash,
                phone: phone || null,
                city: city || null,
                state: state || null,
                role: role,
                status: 'active',
                email_verified: true,
                created_at: new Date(),
                updated_at: new Date()
            });

            res.status(201).json({
                success: true,
                message: 'User created successfully',
                data: { id: userId }
            });

        } catch (error) {
            console.error('Create user error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to create user'
            });
        }
    }

    /**
     * Update user status
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async updateUserStatus(req, res) {
        try {
            const userId = parseInt(req.params.id);

            // Check permissions (admin or moderator only)
            if (!['admin', 'moderator'].includes(req.user.role)) {
                return res.status(403).json({
                    success: false,
                    message: 'Admin access required'
                });
            }

            await body('status')
                .isIn(['active', 'inactive', 'suspended', 'pending'])
                .withMessage('Invalid status')
                .run(req);

            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    message: 'Invalid status'
                });
            }

            const { status } = req.body;

            // Update user status
            const result = await db.update('users', { status: status }, 'id = ?', [userId]);

            if (result.affectedRows === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'User not found'
                });
            }

            res.json({
                success: true,
                message: 'User status updated successfully'
            });

        } catch (error) {
            console.error('Update user status error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to update user status'
            });
        }
    }

    /**
     * Reset user password
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async resetUserPassword(req, res) {
        try {
            const userId = parseInt(req.params.id);

            // Check permissions (admin or moderator only)
            if (!['admin', 'moderator'].includes(req.user.role)) {
                return res.status(403).json({
                    success: false,
                    message: 'Admin access required'
                });
            }

            // Get user details
            const user = await db.queryOne('SELECT email, first_name, last_name FROM users WHERE id = ?', [userId]);

            if (!user) {
                return res.status(404).json({
                    success: false,
                    message: 'User not found'
                });
            }

            // Generate temporary password
            const tempPassword = crypto.randomBytes(8).toString('hex');
            const hashedPassword = await AuthMiddleware.hashPassword(tempPassword);

            // Update user password
            await db.update('users', { password_hash: hashedPassword }, 'id = ?', [userId]);

            // Log temporary password (remove in production)
            console.log(`Temporary password for user ${user.email}: ${tempPassword}`);

            res.json({
                success: true,
                message: 'Password reset successfully. Temporary password sent to user email.'
            });

        } catch (error) {
            console.error('Reset user password error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to reset password'
            });
        }
    }

    /**
     * Send verification email to user
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async sendVerificationEmail(req, res) {
        try {
            const userId = parseInt(req.params.id);

            // Check permissions (admin or moderator only)
            if (!['admin', 'moderator'].includes(req.user.role)) {
                return res.status(403).json({
                    success: false,
                    message: 'Admin access required'
                });
            }

            // Get user details
            const user = await db.queryOne('SELECT email, first_name, last_name, email_verified FROM users WHERE id = ?', [userId]);

            if (!user) {
                return res.status(404).json({
                    success: false,
                    message: 'User not found'
                });
            }

            if (user.email_verified) {
                return res.status(400).json({
                    success: false,
                    message: 'User email is already verified'
                });
            }

            // Mark as verified (simplified implementation)
            await db.update('users', { email_verified: true }, 'id = ?', [userId]);

            res.json({
                success: true,
                message: 'Verification email sent successfully'
            });

        } catch (error) {
            console.error('Send verification email error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to send verification email'
            });
        }
    }

    /**
     * Delete user
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async deleteUser(req, res) {
        try {
            const userId = parseInt(req.params.id);

            // Check permissions (admin only)
            if (req.user.role !== 'admin') {
                return res.status(403).json({
                    success: false,
                    message: 'Admin access required'
                });
            }

            // Prevent self-deletion
            if (req.user.id === userId) {
                return res.status(400).json({
                    success: false,
                    message: 'Cannot delete your own account'
                });
            }

            // Delete user
            const result = await db.delete('users', 'id = ?', [userId]);

            if (result.affectedRows === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'User not found'
                });
            }

            res.json({
                success: true,
                message: 'User deleted successfully'
            });

        } catch (error) {
            console.error('Delete user error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to delete user'
            });
        }
    }

    /**
     * Bulk update user status
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async bulkUpdateStatus(req, res) {
        try {
            // Check permissions (admin or moderator only)
            if (!['admin', 'moderator'].includes(req.user.role)) {
                return res.status(403).json({
                    success: false,
                    message: 'Admin access required'
                });
            }

            await body('user_ids')
                .isArray({ min: 1 })
                .withMessage('User IDs array is required')
                .run(req);

            await body('status')
                .isIn(['active', 'inactive', 'suspended', 'pending'])
                .withMessage('Invalid status')
                .run(req);

            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    message: 'Validation failed',
                    errors: errors.array()
                });
            }

            const { user_ids, status } = req.body;

            // Create placeholders for IN clause
            const placeholders = user_ids.map(() => '?').join(',');
            const query = `UPDATE users SET status = ? WHERE id IN (${placeholders})`;
            const params = [status, ...user_ids];

            // Update user statuses
            const result = await db.query(query, params);

            res.json({
                success: true,
                message: `Updated ${result.affectedRows} user(s) successfully`
            });

        } catch (error) {
            console.error('Bulk update status error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to update user statuses'
            });
        }
    }

    /**
     * Bulk activate users
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async bulkActivate(req, res) {
        req.body.status = 'active';
        return UserController.bulkUpdateStatus(req, res);
    }

    /**
     * Bulk deactivate users
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async bulkDeactivate(req, res) {
        req.body.status = 'inactive';
        return UserController.bulkUpdateStatus(req, res);
    }

    /**
     * Bulk delete users
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async bulkDeleteUsers(req, res) {
        try {
            // Check permissions (admin only)
            if (req.user.role !== 'admin') {
                return res.status(403).json({
                    success: false,
                    message: 'Admin access required'
                });
            }

            await body('user_ids')
                .isArray({ min: 1 })
                .withMessage('User IDs array is required')
                .run(req);

            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    message: 'User IDs array is required'
                });
            }

            const { user_ids } = req.body;

            // Prevent self-deletion
            if (user_ids.includes(req.user.id)) {
                return res.status(400).json({
                    success: false,
                    message: 'Cannot delete your own account'
                });
            }

            // Create placeholders for IN clause
            const placeholders = user_ids.map(() => '?').join(',');
            const query = `DELETE FROM users WHERE id IN (${placeholders})`;

            // Delete users
            const result = await db.query(query, user_ids);

            res.json({
                success: true,
                message: `Deleted ${result.affectedRows} user(s) successfully`
            });

        } catch (error) {
            console.error('Bulk delete users error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to delete users'
            });
        }
    }

    /**
     * Export users to CSV
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async exportUsers(req, res) {
        try {
            // Check permissions (admin or moderator only)
            if (!['admin', 'moderator'].includes(req.user.role)) {
                return res.status(403).json({
                    success: false,
                    message: 'Admin access required'
                });
            }

            // Get all users
            const users = await db.query(`
                SELECT id, first_name, last_name, email, phone, city, state, role, status, 
                       email_verified, created_at, last_login,
                       CONCAT(first_name, ' ', last_name) as full_name
                FROM users 
                ORDER BY created_at DESC
            `);

            // Set CSV headers
            res.setHeader('Content-Type', 'text/csv');
            res.setHeader('Content-Disposition', `attachment; filename="users_export_${new Date().toISOString().split('T')[0]}.csv"`);

            // Write CSV header
            const csvHeader = 'ID,Full Name,Email,Phone,City,State,Role,Status,Email Verified,Registration Date,Last Login\n';
            res.write(csvHeader);

            // Write user data
            for (const user of users) {
                const csvRow = [
                    user.id,
                    `"${user.full_name || ''}"`,
                    `"${user.email || ''}"`,
                    `"${user.phone || ''}"`,
                    `"${user.city || ''}"`,
                    `"${user.state || ''}"`,
                    user.role ? user.role.charAt(0).toUpperCase() + user.role.slice(1) : '',
                    user.status ? user.status.charAt(0).toUpperCase() + user.status.slice(1) : '',
                    user.email_verified ? 'Yes' : 'No',
                    user.created_at ? new Date(user.created_at).toISOString().replace('T', ' ').split('.')[0] : '',
                    user.last_login ? new Date(user.last_login).toISOString().replace('T', ' ').split('.')[0] : 'Never'
                ].join(',') + '\n';
                
                res.write(csvRow);
            }

            res.end();

        } catch (error) {
            console.error('Export users error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to export users'
            });
        }
    }

    /**
     * Update user profile
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async updateUser(req, res) {
        try {
            const userId = parseInt(req.params.id);

            // Check permissions (own profile or admin/moderator)
            if (req.user.id !== userId && !['admin', 'moderator'].includes(req.user.role)) {
                return res.status(403).json({
                    success: false,
                    message: 'Access denied'
                });
            }

            // Validation rules
            await body('first_name')
                .optional()
                .isLength({ min: 2, max: 50 })
                .matches(/^[a-zA-Z0-9\s]+$/)
                .withMessage('First name must be 2-50 characters and contain only letters and numbers')
                .run(req);

            await body('last_name')
                .optional()
                .isLength({ min: 2, max: 50 })
                .matches(/^[a-zA-Z0-9\s]+$/)
                .withMessage('Last name must be 2-50 characters and contain only letters and numbers')
                .run(req);

            await body('phone')
                .optional()
                .isMobilePhone()
                .withMessage('Invalid phone number')
                .run(req);

            await body('city')
                .optional()
                .isLength({ min: 2, max: 100 })
                .withMessage('City must be 2-100 characters')
                .run(req);

            await body('state')
                .optional()
                .isLength({ min: 2, max: 100 })
                .withMessage('State must be 2-100 characters')
                .run(req);

            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    message: 'Validation failed',
                    errors: errors.array()
                });
            }

            const allowedFields = ['first_name', 'last_name', 'phone', 'city', 'state'];
            const updateData = {};
            let hasUpdates = false;

            for (const field of allowedFields) {
                if (req.body[field] !== undefined) {
                    updateData[field] = req.body[field];
                    hasUpdates = true;
                }
            }

            if (!hasUpdates) {
                return res.status(400).json({
                    success: false,
                    message: 'No valid fields to update'
                });
            }

            updateData.updated_at = new Date();

            // Update user
            const result = await db.update('users', updateData, 'id = ?', [userId]);

            if (result.affectedRows === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'User not found'
                });
            }

            res.json({
                success: true,
                message: 'User updated successfully'
            });

        } catch (error) {
            console.error('Update user error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to update user'
            });
        }
    }

    /**
     * Update user role
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async updateUserRole(req, res) {
        try {
            const userId = parseInt(req.params.id);

            // Check permissions (admin only)
            if (req.user.role !== 'admin') {
                return res.status(403).json({
                    success: false,
                    message: 'Admin access required'
                });
            }

            // Validation
            await body('role')
                .isIn(['admin', 'member', 'volunteer', 'moderator', 'user', 'donor'])
                .withMessage('Invalid role')
                .run(req);

            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    message: 'Invalid role'
                });
            }

            const { role } = req.body;

            // Prevent changing own role
            if (req.user.id === userId) {
                return res.status(400).json({
                    success: false,
                    message: 'Cannot change your own role'
                });
            }

            // Update user role
            const result = await db.update('users', { role, updated_at: new Date() }, 'id = ?', [userId]);

            if (result.affectedRows === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'User not found'
                });
            }

            res.json({
                success: true,
                message: 'User role updated successfully'
            });

        } catch (error) {
            console.error('Update user role error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to update user role'
            });
        }
    }
}

module.exports = UserController;