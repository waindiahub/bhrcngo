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
const ComplaintController = require('../controllers/complaintController');
const DashboardController = require('../controllers/dashboardController');

// Validation rules
const validateUserId = [
    param('userId')
        .isInt({ min: 1 })
        .withMessage('User ID must be a positive integer')
];

const validateComplaintId = [
    param('complaintId')
        .isInt({ min: 1 })
        .withMessage('Complaint ID must be a positive integer')
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

const validateComplaintAssignment = [
    body('assigned_to')
        .isInt({ min: 1 })
        .withMessage('Assigned user ID must be a positive integer'),
    body('notes')
        .optional()
        .isLength({ max: 1000 })
        .withMessage('Notes must not exceed 1000 characters')
];

const validateComplaintComment = [
    body('comment')
        .isLength({ min: 1, max: 1000 })
        .withMessage('Comment must be between 1 and 1000 characters'),
    body('is_internal')
        .optional()
        .isBoolean()
        .withMessage('is_internal must be a boolean')
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
 * Get staff members
 * GET /api/admin/staff
 */
router.get('/staff', 
    authenticateToken,
    authorizeRoles(['admin', 'super_admin']),
    ComplaintController.getStaffMembers
);

// Admin Complaint Management Routes
/**
 * Get all complaints (admin-specific route)
 * GET /api/admin/complaints
 */
router.get('/complaints', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator', 'super_admin']),
    ComplaintController.getAllComplaints
);

/**
 * Get complaint details
 * GET /api/admin/complaints/:id
 */
router.get('/complaints/:id', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator', 'super_admin']),
    param('id').isInt({ min: 1 }).withMessage('Complaint ID must be a positive integer'),
    validateRequest,
    ComplaintController.getComplaintDetails
);

/**
 * Update complaint
 * PUT /api/admin/complaints/:id
 */
router.put('/complaints/:id', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator', 'super_admin']),
    param('id').isInt({ min: 1 }).withMessage('Complaint ID must be a positive integer'),
    validateRequest,
    ComplaintController.updateComplaintStatus
);

/**
 * Delete complaint
 * DELETE /api/admin/complaints/:complaintId
 */
router.delete('/complaints/:complaintId', 
    authenticateToken,
    authorizeRoles(['admin', 'super_admin']),
    validateComplaintId,
    validateRequest,
    ComplaintController.deleteComplaint
);

/**
 * Download complaint file
 * GET /api/admin/complaints/:complaintId/download
 */
router.get('/complaints/:complaintId/download', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator', 'super_admin']),
    validateComplaintId,
    validateRequest,
    ComplaintController.downloadComplaintFile
);

/**
 * Download complaint attachment
 * GET /api/admin/complaints/attachments/:attachmentId/download
 */
router.get('/complaints/attachments/:attachmentId/download', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator', 'super_admin']),
    param('attachmentId').isInt({ min: 1 }).withMessage('Attachment ID must be a positive integer'),
    validateRequest,
    ComplaintController.downloadAttachment
);

/**
 * Assign complaint
 * POST /api/admin/complaints/:complaintId/assign
 */
router.post('/complaints/:complaintId/assign', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator', 'super_admin']),
    validateComplaintId,
    validateComplaintAssignment,
    validateRequest,
    ComplaintController.assignComplaint
);

/**
 * Add note to complaint
 * POST /api/admin/complaints/:id/notes
 */
router.post('/complaints/:id/notes', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator', 'super_admin']),
    param('id').isInt({ min: 1 }).withMessage('Complaint ID must be a positive integer'),
    body('content').isLength({ min: 1, max: 1000 }).withMessage('Note content is required and must not exceed 1000 characters'),
    validateRequest,
    ComplaintController.addNote
);

/**
 * Add comment to complaint
 * POST /api/admin/complaints/:complaintId/comments
 */
router.post('/complaints/:complaintId/comments', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator', 'super_admin']),
    validateComplaintId,
    validateComplaintComment,
    validateRequest,
    ComplaintController.addComment
);

/**
 * Bulk update complaint status
 * POST /api/admin/complaints/bulk-update-status
 */
router.post('/complaints/bulk-update-status', 
    authenticateToken,
    authorizeRoles(['admin', 'super_admin']),
    body('complaint_ids').isArray({ min: 1 }).withMessage('Complaint IDs array is required'),
    body('status').isIn(['pending', 'in_progress', 'resolved', 'closed']).withMessage('Invalid status'),
    validateRequest,
    ComplaintController.bulkUpdateStatus
);

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