/**
 * Analytics Routes
 * Defines API endpoints for analytics and reporting functionality
 * BHRC India - Human Rights Commission Web Application
 */

const express = require('express');
const { query } = require('express-validator');
const router = express.Router();
const analyticsController = require('../controllers/analyticsController');
const { authenticateToken, authorizeRoles } = require('../middleware/auth');

/**
 * Validation rules for analytics queries
 */
const analyticsValidation = {
    // Period validation
    period: query('period')
        .optional()
        .isIn(['7d', '30d', '90d', '1y', 'custom'])
        .withMessage('Period must be one of: 7d, 30d, 90d, 1y, custom'),
    
    // Date validation for custom period
    startDate: query('start_date')
        .optional()
        .isISO8601()
        .withMessage('Start date must be a valid ISO 8601 date'),
    
    endDate: query('end_date')
        .optional()
        .isISO8601()
        .withMessage('End date must be a valid ISO 8601 date'),
    
    // Limit validation
    limit: query('limit')
        .optional()
        .isInt({ min: 1, max: 50 })
        .withMessage('Limit must be between 1 and 50'),
    
    // Format validation for exports
    format: query('format')
        .optional()
        .isIn(['csv', 'pdf'])
        .withMessage('Format must be csv or pdf')
};

/**
 * GET /api/analytics
 * Get dashboard overview statistics
 * Access: Admin, Moderator
 */
router.get('/', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    [
        analyticsValidation.startDate,
        analyticsValidation.endDate
    ],
    analyticsController.getDashboardOverview
);

/**
 * GET /api/analytics/metrics
 * Get key metrics with period comparison
 * Access: Admin, Moderator
 */
router.get('/metrics',
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    [
        analyticsValidation.period,
        analyticsValidation.startDate,
        analyticsValidation.endDate
    ],
    analyticsController.getMetrics
);

/**
 * GET /api/analytics/charts
 * Get chart data for various analytics
 * Access: Admin, Moderator
 */
router.get('/charts',
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    [
        analyticsValidation.period,
        analyticsValidation.startDate,
        analyticsValidation.endDate
    ],
    analyticsController.getChartData
);

/**
 * GET /api/analytics/top-donors
 * Get top donors for the period
 * Access: Admin, Moderator
 */
router.get('/top-donors',
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    [
        analyticsValidation.period,
        analyticsValidation.startDate,
        analyticsValidation.endDate,
        analyticsValidation.limit
    ],
    analyticsController.getTopDonors
);

/**
 * GET /api/analytics/popular-events
 * Get popular events for the period
 * Access: Admin, Moderator
 */
router.get('/popular-events',
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    [
        analyticsValidation.period,
        analyticsValidation.startDate,
        analyticsValidation.endDate,
        analyticsValidation.limit
    ],
    analyticsController.getPopularEvents
);

/**
 * GET /api/analytics/system-metrics
 * Get system performance metrics
 * Access: Admin only
 */
router.get('/system-metrics',
    authenticateToken,
    authorizeRoles(['admin']),
    analyticsController.getSystemMetrics
);

/**
 * GET /api/analytics/export
 * Export analytics report
 * Access: Admin only
 */
router.get('/export',
    authenticateToken,
    authorizeRoles(['admin']),
    [
        analyticsValidation.period,
        analyticsValidation.startDate,
        analyticsValidation.endDate,
        analyticsValidation.format
    ],
    analyticsController.exportReport
);

module.exports = router;