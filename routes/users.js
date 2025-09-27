/**
 * User Routes for BHRC India Application
 * 
 * Defines all user management API endpoints including
 * CRUD operations, bulk operations, and user statistics.
 */

const express = require('express');
const router = express.Router();
const { body, param, query } = require('express-validator');
const UserController = require('../controllers/userController');
const { authenticateToken, authorizeRoles } = require('../middleware/auth');

// Validation rules for user creation
const validateUserCreation = [
    body('first_name')
        .isLength({ min: 2, max: 50 })
        .matches(/^[a-zA-Z\s]+$/)
        .withMessage('First name must be 2-50 characters and contain only letters'),
    body('last_name')
        .isLength({ min: 2, max: 50 })
        .matches(/^[a-zA-Z\s]+$/)
        .withMessage('Last name must be 2-50 characters and contain only letters'),
    body('email')
        .isEmail()
        .normalizeEmail()
        .withMessage('Valid email is required'),
    body('password')
        .isLength({ min: 8 })
        .matches(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/)
        .withMessage('Password must be at least 8 characters with uppercase, lowercase, and number'),
    body('phone')
        .optional()
        .isMobilePhone()
        .withMessage('Invalid phone number'),
    body('city')
        .optional()
        .isLength({ min: 2, max: 100 })
        .withMessage('City must be 2-100 characters'),
    body('state')
        .optional()
        .isLength({ min: 2, max: 100 })
        .withMessage('State must be 2-100 characters'),
    body('role')
        .isIn(['admin', 'member', 'volunteer', 'donor'])
        .withMessage('Invalid role')
];

// Validation rules for user update
const validateUserUpdate = [
    body('first_name')
        .optional()
        .isLength({ min: 2, max: 50 })
        .matches(/^[a-zA-Z\s]+$/)
        .withMessage('First name must be 2-50 characters and contain only letters'),
    body('last_name')
        .optional()
        .isLength({ min: 2, max: 50 })
        .matches(/^[a-zA-Z\s]+$/)
        .withMessage('Last name must be 2-50 characters and contain only letters'),
    body('phone')
        .optional()
        .isMobilePhone()
        .withMessage('Invalid phone number'),
    body('city')
        .optional()
        .isLength({ min: 2, max: 100 })
        .withMessage('City must be 2-100 characters'),
    body('state')
        .optional()
        .isLength({ min: 2, max: 100 })
        .withMessage('State must be 2-100 characters')
];

// Validation rules for status update
const validateStatusUpdate = [
    body('status')
        .isIn(['active', 'inactive', 'suspended', 'pending'])
        .withMessage('Invalid status')
];

// Validation rules for role update
const validateRoleUpdate = [
    body('role')
        .isIn(['admin', 'member', 'volunteer', 'moderator', 'user', 'donor'])
        .withMessage('Invalid role')
];

// Validation rules for bulk operations
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

// Validation for query parameters
const validateQueryParams = [
    query('page')
        .optional()
        .isInt({ min: 1 })
        .withMessage('Page must be a positive integer'),
    query('per_page')
        .optional()
        .isInt({ min: 1, max: 100 })
        .withMessage('Per page must be between 1 and 100'),
    query('search')
        .optional()
        .isLength({ max: 255 })
        .withMessage('Search term too long'),
    query('role')
        .optional()
        .isIn(['admin', 'member', 'volunteer', 'moderator', 'user', 'donor'])
        .withMessage('Invalid role filter'),
    query('status')
        .optional()
        .isIn(['active', 'inactive', 'suspended', 'pending'])
        .withMessage('Invalid status filter'),
    query('verified')
        .optional()
        .isIn(['0', '1', 'true', 'false'])
        .withMessage('Invalid verified filter'),
    query('sort_by')
        .optional()
        .isIn(['created_at', 'first_name', 'last_name', 'email', 'role', 'status'])
        .withMessage('Invalid sort field'),
    query('sort_order')
        .optional()
        .isIn(['asc', 'desc'])
        .withMessage('Invalid sort order')
];

// Parameter validation
const validateUserId = [
    param('id')
        .isInt({ min: 1 })
        .withMessage('User ID must be a positive integer')
];

// ==================== USER MANAGEMENT ROUTES ====================

/**
 * @route GET /api/users
 * @desc Get all users with filtering and pagination (admin/moderator only)
 * @access Admin, Moderator
 */
router.get('/', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateQueryParams,
    UserController.getUsers
);

/**
 * @route GET /api/users/stats
 * @desc Get user statistics (admin/moderator only)
 * @access Admin, Moderator
 */
router.get('/stats', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    UserController.getUserStats
);

/**
 * @route GET /api/users/export
 * @desc Export users to CSV (admin/moderator only)
 * @access Admin, Moderator
 */
router.get('/export', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    UserController.exportUsers
);

/**
 * @route GET /api/users/:id
 * @desc Get single user by ID
 * @access Private
 */
router.get('/:id', 
    authenticateToken,
    validateUserId,
    UserController.getUser
);

/**
 * @route POST /api/users
 * @desc Create new user (admin only)
 * @access Admin
 */
router.post('/', 
    authenticateToken,
    authorizeRoles(['admin']),
    validateUserCreation,
    UserController.createUser
);

/**
 * @route PUT /api/users/:id
 * @desc Update user profile
 * @access Private
 */
router.put('/:id', 
    authenticateToken,
    validateUserId,
    validateUserUpdate,
    UserController.updateUser
);

/**
 * @route PUT /api/users/:id/status
 * @desc Update user status (admin/moderator only)
 * @access Admin, Moderator
 */
router.put('/:id/status', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateUserId,
    validateStatusUpdate,
    UserController.updateUserStatus
);

/**
 * @route PUT /api/users/:id/role
 * @desc Update user role (admin only)
 * @access Admin
 */
router.put('/:id/role', 
    authenticateToken,
    authorizeRoles(['admin']),
    validateUserId,
    validateRoleUpdate,
    UserController.updateUserRole
);

/**
 * @route POST /api/users/:id/reset-password
 * @desc Reset user password (admin/moderator only)
 * @access Admin, Moderator
 */
router.post('/:id/reset-password', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateUserId,
    UserController.resetUserPassword
);

/**
 * @route POST /api/users/:id/send-verification
 * @desc Send verification email (admin/moderator only)
 * @access Admin, Moderator
 */
router.post('/:id/send-verification', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateUserId,
    UserController.sendVerificationEmail
);

/**
 * @route DELETE /api/users/:id
 * @desc Delete user (admin only)
 * @access Admin
 */
router.delete('/:id', 
    authenticateToken,
    authorizeRoles(['admin']),
    validateUserId,
    UserController.deleteUser
);

// ==================== BULK OPERATIONS ====================

/**
 * @route PUT /api/users/bulk/status
 * @desc Bulk update user status (admin/moderator only)
 * @access Admin, Moderator
 */
router.put('/bulk/status', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateBulkOperation,
    validateStatusUpdate,
    UserController.bulkUpdateStatus
);

/**
 * @route PUT /api/users/bulk/activate
 * @desc Bulk activate users (admin/moderator only)
 * @access Admin, Moderator
 */
router.put('/bulk/activate', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateBulkOperation,
    UserController.bulkActivate
);

/**
 * @route PUT /api/users/bulk/deactivate
 * @desc Bulk deactivate users (admin/moderator only)
 * @access Admin, Moderator
 */
router.put('/bulk/deactivate', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateBulkOperation,
    UserController.bulkDeactivate
);

/**
 * @route DELETE /api/users/bulk
 * @desc Bulk delete users (admin only)
 * @access Admin
 */
router.delete('/bulk', 
    authenticateToken,
    authorizeRoles(['admin']),
    validateBulkOperation,
    UserController.bulkDeleteUsers
);

/**
 * Get admin users
 * GET /api/users/admin-users
 */
router.get('/admin-users', 
    authenticateToken,
    authorizeRoles(['admin', 'super_admin']),
    async (req, res) => {
        try {
            // Filter users by admin roles
            req.query.role = ['admin', 'super_admin', 'moderator'];
            return UserController.getUsers(req, res);
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error fetching admin users'
            });
        }
    }
);

module.exports = router;