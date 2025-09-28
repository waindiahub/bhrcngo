/**
 * Admin Routes for BHRC India Application
 * 
 * Defines all admin-specific API endpoints including
 * user management, complaints, dashboard, and system operations.
 */

const express = require('express');
const router = express.Router();
const { body, param, query } = require('express-validator');
const { authenticateToken, authorizeRoles, validateRequest } = require('../middleware/auth');

// Import controllers
const UserController = require('../controllers/userController');

const DashboardController = require('../controllers/dashboardController');

// Validation rules
const validateUserId = [
    param('userId')
        .isInt({ min: 1 })
        .withMessage('User ID must be a positive integer')
];



const validateStatusUpdate = [
    body('status')
        .isIn(['active', 'inactive', 'suspended', 'pending'])
        .withMessage('Invalid status')
];

const validateBulkOperation = [
    body('user_ids')
        .isArray({ min: 1 })
        .withMessage('User IDs array is required')
        .custom((value) => {
            if (!value.every(id => Number.isInteger(id) && id > 0)) {
                throw new Error('All user IDs must be positive integers');
            }
            return true;
        })
];





// Admin User Management Routes
/**
 * Get all users (admin-specific route)
 * GET /api/admin/users
 */
router.get('/users', 
    authenticateToken,
    authorizeRoles(['admin', 'super_admin']),
    UserController.getUsers
);

/**
 * Export users (admin-specific route)
 * GET /api/admin/users/export
 */
router.get('/users/export', 
    authenticateToken,
    authorizeRoles(['admin', 'super_admin']),
    UserController.exportUsers
);

/**
 * Get user details (admin-specific route)
 * GET /api/admin/users/:userId/details
 */
router.get('/users/:userId/details', 
    authenticateToken,
    authorizeRoles(['admin', 'super_admin']),
    validateUserId,
    validateRequest,
    UserController.getUser
);

/**
 * Update user status (PATCH method)
 * PATCH /api/admin/users/:userId/status
 */
router.patch('/users/:userId/status', 
    authenticateToken,
    authorizeRoles(['admin', 'super_admin']),
    validateUserId,
    validateStatusUpdate,
    validateRequest,
    UserController.updateUserStatus
);

/**
 * Delete user (admin-specific route)
 * DELETE /api/admin/users/:userId
 */
router.delete('/users/:userId', 
    authenticateToken,
    authorizeRoles(['admin', 'super_admin']),
    validateUserId,
    validateRequest,
    UserController.deleteUser
);

/**
 * Reset user password (admin-specific route)
 * POST /api/admin/users/:userId/reset-password
 */
router.post('/users/:userId/reset-password', 
    authenticateToken,
    authorizeRoles(['admin', 'super_admin']),
    validateUserId,
    validateRequest,
    UserController.resetUserPassword
);

/**
 * Send verification email (admin-specific route)
 * POST /api/admin/users/:userId/send-verification
 */
router.post('/users/:userId/send-verification', 
    authenticateToken,
    authorizeRoles(['admin', 'super_admin']),
    validateUserId,
    validateRequest,
    UserController.sendVerificationEmail
);

/**
 * Bulk update user status
 * PATCH /api/admin/users/bulk-status
 */
router.patch('/users/bulk-status', 
    authenticateToken,
    authorizeRoles(['admin', 'super_admin']),
    validateBulkOperation,
    validateStatusUpdate,
    validateRequest,
    UserController.bulkUpdateStatus
);

/**
 * Bulk delete users
 * DELETE /api/admin/users/bulk-delete
 */
router.delete('/users/bulk-delete', 
    authenticateToken,
    authorizeRoles(['admin', 'super_admin']),
    validateBulkOperation,
    validateRequest,
    UserController.bulkDeleteUsers
);

/**
 * Dynamic bulk action on users
 * POST /api/admin/users/bulk-:action
 */
router.post('/users/bulk-:action', 
    authenticateToken,
    authorizeRoles(['admin', 'super_admin']),
    validateBulkOperation,
    validateRequest,
    async (req, res) => {
        const { action } = req.params;
        
        switch (action) {
            case 'activate':
                return UserController.bulkActivate(req, res);
            case 'deactivate':
                return UserController.bulkDeactivate(req, res);
            case 'delete':
                return UserController.bulkDeleteUsers(req, res);
            default:
                return res.status(400).json({
                    success: false,
                    message: 'Invalid bulk action'
                });
        }
    }
);

/**
 * Get user statistics
 * GET /api/admin/users/stats
 */
router.get('/users/stats', 
    authenticateToken,
    authorizeRoles(['admin', 'super_admin']),
    UserController.getUserStats
);



/**
 * Get staff members
 * GET /api/admin/staff
 */
router.get('/staff', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator', 'super_admin']),
    (req, res) => res.json({ success: true, data: [] })
);

/**
 * Test endpoint
 * GET /api/admin/test
 */
router.get('/test', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator', 'super_admin']),
    (req, res) => {
        res.json({
            success: true,
            message: 'Admin routes are working',
            user: req.user,
            timestamp: new Date().toISOString()
        });
    }
);

// Admin Complaint Management Routes
























// Admin Dashboard Routes
/**
 * Get dashboard activities
 * GET /api/admin/dashboard/activities
 */
router.get('/dashboard/activities', 
    authenticateToken,
    authorizeRoles(['admin', 'super_admin']),
    DashboardController.getActivities
);

/**
 * Get dashboard charts
 * GET /api/admin/dashboard/charts
 */
router.get('/dashboard/charts', 
    authenticateToken,
    authorizeRoles(['admin', 'super_admin']),
    DashboardController.getChartData
);

/**
 * Export dashboard data
 * GET /api/admin/dashboard/export
 */
router.get('/dashboard/export', 
    authenticateToken,
    authorizeRoles(['admin', 'super_admin']),
    DashboardController.exportReport
);

module.exports = router;