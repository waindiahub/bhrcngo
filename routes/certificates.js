/**
 * Certificate Routes - Node.js/Express
 * BHRC India - Human Rights Commission Web Application
 * Defines certificate management API endpoints
 */

const express = require('express');
const { body, param, query } = require('express-validator');
const router = express.Router();
const certificateController = require('../controllers/certificateController');
const { authenticateToken, authorizeRoles } = require('../middleware/auth');

// Validation rules
const certificateValidation = {
    create: [
        body('user_id')
            .notEmpty()
            .withMessage('User ID is required')
            .isInt({ min: 1 })
            .withMessage('User ID must be a positive integer'),
        body('title')
            .notEmpty()
            .withMessage('Title is required')
            .isLength({ min: 3, max: 255 })
            .withMessage('Title must be between 3 and 255 characters'),
        body('description')
            .optional()
            .isLength({ max: 1000 })
            .withMessage('Description must not exceed 1000 characters'),
        body('issue_date')
            .optional()
            .isISO8601()
            .withMessage('Issue date must be a valid date (YYYY-MM-DD)')
    ],
    update: [
        param('id')
            .isInt({ min: 1 })
            .withMessage('Valid certificate ID is required'),
        body('title')
            .notEmpty()
            .withMessage('Title is required')
            .isLength({ min: 3, max: 255 })
            .withMessage('Title must be between 3 and 255 characters'),
        body('description')
            .optional()
            .isLength({ max: 1000 })
            .withMessage('Description must not exceed 1000 characters'),
        body('issue_date')
            .optional()
            .isISO8601()
            .withMessage('Issue date must be a valid date (YYYY-MM-DD)')
    ],
    delete: [
        param('id')
            .isInt({ min: 1 })
            .withMessage('Valid certificate ID is required')
    ],
    bulkDelete: [
        body('certificate_ids')
            .isArray({ min: 1 })
            .withMessage('Certificate IDs array is required')
            .custom((value) => {
                if (!value.every(id => Number.isInteger(id) && id > 0)) {
                    throw new Error('All certificate IDs must be positive integers');
                }
                return true;
            })
    ],
    getCertificates: [
        query('page')
            .optional()
            .isInt({ min: 1 })
            .withMessage('Page must be a positive integer'),
        query('per_page')
            .optional()
            .isInt({ min: 1, max: 100 })
            .withMessage('Per page must be between 1 and 100'),
        query('user_id')
            .optional()
            .isInt({ min: 1 })
            .withMessage('User ID must be a positive integer')
    ],
    getCertificate: [
        param('id')
            .isInt({ min: 1 })
            .withMessage('Valid certificate ID is required')
    ]
};

// Apply authentication to all routes below this point
router.use(authenticateToken);

// ==================== PUBLIC/USER ROUTES ====================

/**
 * @route GET /api/certificates
 * @desc Get certificates (user sees their own, admin/moderator sees all)
 * @access Private
 */
router.get('/', 
    certificateValidation.getCertificates, 
    certificateController.getCertificates
);

/**
 * @route GET /api/certificates/:id
 * @desc Get single certificate
 * @access Private
 */
router.get('/:id', 
    certificateValidation.getCertificate, 
    certificateController.getCertificate
);

// ==================== ADMIN/MODERATOR ROUTES ====================

/**
 * @route GET /api/certificates/admin/stats
 * @desc Get certificate statistics (admin/moderator only)
 * @access Admin, Moderator
 */
router.get('/admin/stats',
    authorizeRoles(['admin', 'moderator']),
    certificateController.getCertificateStats
);

/**
 * @route POST /api/certificates
 * @desc Create a new certificate (admin/moderator only)
 * @access Admin, Moderator
 */
router.post('/',
    authorizeRoles(['admin', 'moderator']),
    certificateValidation.create,
    certificateController.createCertificate
);

/**
 * @route PUT /api/certificates/:id
 * @desc Update certificate (admin/moderator only)
 * @access Admin, Moderator
 */
router.put('/:id',
    authorizeRoles(['admin', 'moderator']),
    certificateValidation.update,
    certificateController.updateCertificate
);

/**
 * @route DELETE /api/certificates/:id
 * @desc Delete certificate (admin only)
 * @access Admin
 */
router.delete('/:id',
    authorizeRoles(['admin']),
    certificateValidation.delete,
    certificateController.deleteCertificate
);

/**
 * @route POST /api/certificates/bulk-delete
 * @desc Bulk delete certificates (admin only)
 * @access Admin
 */
router.post('/bulk-delete',
    authorizeRoles(['admin']),
    certificateValidation.bulkDelete,
    certificateController.bulkDeleteCertificates
);

module.exports = router;