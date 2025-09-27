/**
 * Dashboard Routes
 * Handles dashboard statistics and analytics API endpoints
 * BHRC India - Human Rights Commission Web Application
 */

const express = require('express');
const router = express.Router();
const { body, query } = require('express-validator');
const dashboardController = require('../controllers/dashboardController');
const { authenticateToken, authorizeRoles } = require('../middleware/auth');

// Validation rules
const validatePeriod = [
    query('period')
        .optional()
        .isInt({ min: 1, max: 365 })
        .withMessage('Period must be between 1 and 365 days')
];

const validateLimit = [
    query('limit')
        .optional()
        .isInt({ min: 1, max: 100 })
        .withMessage('Limit must be between 1 and 100')
];

const validateExportFormat = [
    query('format')
        .optional()
        .isIn(['pdf', 'csv'])
        .withMessage('Format must be either pdf or csv')
];

/**
 * @route GET /api/dashboard/stats
 * @desc Get general dashboard statistics
 * @access Authenticated users
 */
router.get('/stats', 
    authenticateToken,
    dashboardController.getStats
);

/**
 * @route GET /api/dashboard/admin/stats
 * @desc Get admin dashboard statistics
 * @access Admin and Moderator only
 */
router.get('/admin/stats', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    dashboardController.getAdminStats
);

/**
 * @route GET /api/dashboard/member/stats
 * @desc Get member dashboard statistics
 * @access Authenticated members
 */
router.get('/member/stats', 
    authenticateToken,
    dashboardController.getMemberStats
);

/**
 * @route GET /api/dashboard/activities
 * @desc Get recent activities for admin dashboard
 * @access Admin and Moderator only
 */
router.get('/activities', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateLimit,
    dashboardController.getActivities
);

/**
 * @route GET /api/dashboard/chart-data
 * @desc Get chart data for admin dashboard
 * @access Admin and Moderator only
 */
router.get('/chart-data', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validatePeriod,
    dashboardController.getChartData
);

/**
 * @route GET /api/dashboard/export
 * @desc Export dashboard report
 * @access Admin and Moderator only
 */
router.get('/export', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateExportFormat,
    dashboardController.exportReport
);

module.exports = router;