/**
 * Complaint Routes - Node.js/Express
 * BHRC India - Human Rights Commission Web Application
 * Routes for complaint filing, management, and status tracking
 */

const express = require('express');
const { body, query, param } = require('express-validator');
const router = express.Router();
const complaintController = require('../controllers/complaintController');
const { authenticateToken, requireRole } = require('../middleware/auth');

// Validation rules for filing a complaint
const fileComplaintValidation = [
    body('complainant_name')
        .trim()
        .isLength({ min: 2, max: 100 })
        .withMessage('Complainant name must be between 2 and 100 characters'),
    
    body('complainant_email')
        .isEmail()
        .normalizeEmail()
        .withMessage('Valid email address is required'),
    
    body('complainant_phone')
        .matches(/^[0-9+\-\s()]{10,15}$/)
        .withMessage('Valid phone number is required'),
    
    body('complainant_address')
        .optional()
        .trim()
        .isLength({ max: 500 })
        .withMessage('Address must not exceed 500 characters'),
    
    body('complaint_type')
        .isIn(['human_rights_violation', 'police_brutality', 'discrimination', 'corruption', 'other'])
        .withMessage('Valid complaint type is required'),
    
    body('subject')
        .trim()
        .isLength({ min: 5, max: 200 })
        .withMessage('Subject must be between 5 and 200 characters'),
    
    body('description')
        .trim()
        .isLength({ min: 20, max: 5000 })
        .withMessage('Description must be between 20 and 5000 characters'),
    
    body('incident_date')
        .optional()
        .isISO8601()
        .withMessage('Valid incident date is required'),
    
    body('incident_location')
        .optional()
        .trim()
        .isLength({ max: 200 })
        .withMessage('Incident location must not exceed 200 characters')
];

// Validation rules for updating complaint status
const updateComplaintValidation = [
    body('status')
        .isIn(['submitted', 'under_review', 'investigating', 'resolved', 'closed', 'rejected'])
        .withMessage('Valid status is required'),
    
    body('priority')
        .optional()
        .isIn(['low', 'medium', 'high', 'urgent'])
        .withMessage('Valid priority is required'),
    
    body('admin_notes')
        .optional()
        .trim()
        .isLength({ max: 1000 })
        .withMessage('Admin notes must not exceed 1000 characters'),
    
    body('assigned_to')
        .optional()
        .isInt({ min: 1 })
        .withMessage('Valid user ID is required for assignment')
];

// Validation rules for bulk update
const bulkUpdateValidation = [
    body('complaint_ids')
        .isArray({ min: 1 })
        .withMessage('At least one complaint ID is required'),
    
    body('complaint_ids.*')
        .isInt({ min: 1 })
        .withMessage('Valid complaint IDs are required'),
    
    body('status')
        .isIn(['submitted', 'under_review', 'investigating', 'resolved', 'closed', 'rejected'])
        .withMessage('Valid status is required')
];

// Validation rules for adding notes
const addNoteValidation = [
    body('content')
        .trim()
        .isLength({ min: 1, max: 1000 })
        .withMessage('Note content must be between 1 and 1000 characters')
];

// Validation rules for assignment
const assignComplaintValidation = [
    body('assigned_to')
        .optional()
        .isInt({ min: 1 })
        .withMessage('Valid user ID is required'),
    
    body('note')
        .optional()
        .trim()
        .isLength({ max: 500 })
        .withMessage('Note must not exceed 500 characters')
];

// Query validation for getting complaints
const getComplaintsValidation = [
    query('page')
        .optional()
        .isInt({ min: 1 })
        .withMessage('Page must be a positive integer'),
    
    query('limit')
        .optional()
        .isInt({ min: 1, max: 100 })
        .withMessage('Limit must be between 1 and 100'),
    
    query('status')
        .optional()
        .isIn(['submitted', 'under_review', 'investigating', 'resolved', 'closed', 'rejected'])
        .withMessage('Valid status is required'),
    
    query('priority')
        .optional()
        .isIn(['low', 'medium', 'high', 'urgent'])
        .withMessage('Valid priority is required'),
    
    query('type')
        .optional()
        .isIn(['human_rights_violation', 'police_brutality', 'discrimination', 'corruption', 'other'])
        .withMessage('Valid complaint type is required'),
    
    query('sort_by')
        .optional()
        .isIn(['created_at', 'updated_at', 'priority', 'status', 'complaint_number'])
        .withMessage('Valid sort field is required'),
    
    query('sort_order')
        .optional()
        .isIn(['ASC', 'DESC'])
        .withMessage('Sort order must be ASC or DESC')
];

// Parameter validation
const idValidation = [
    param('id')
        .isInt({ min: 1 })
        .withMessage('Valid complaint ID is required')
];

// PUBLIC ROUTES

/**
 * @route POST /api/complaints/file
 * @desc File a new complaint
 * @access Public
 */
router.post('/file', fileComplaintValidation, complaintController.fileComplaint);

/**
 * @route GET /api/complaints/status
 * @desc Get complaint status by complaint number
 * @access Public
 */
router.get('/status', 
    query('complaint_number')
        .notEmpty()
        .withMessage('Complaint number is required'),
    complaintController.getComplaintStatus
);

// ADMIN/MODERATOR ROUTES

/**
 * @route GET /api/complaints
 * @desc Get all complaints with filtering and pagination
 * @access Admin, Moderator
 */
router.get('/', 
    authenticateToken,
    requireRole(['admin', 'moderator']),
    getComplaintsValidation,
    complaintController.getAllComplaints
);

/**
 * @route GET /api/complaints/stats
 * @desc Get complaint statistics
 * @access Admin, Moderator
 */
router.get('/stats',
    authenticateToken,
    requireRole(['admin', 'moderator']),
    complaintController.getComplaintStats
);

/**
 * @route GET /api/complaints/export
 * @desc Export complaints to CSV
 * @access Admin, Moderator
 */
router.get('/export',
    authenticateToken,
    requireRole(['admin', 'moderator']),
    complaintController.exportComplaints
);

/**
 * @route GET /api/complaints/staff
 * @desc Get staff members for assignment
 * @access Admin, Moderator
 */
router.get('/staff',
    authenticateToken,
    requireRole(['admin', 'moderator']),
    complaintController.getStaffMembers
);

/**
 * @route GET /api/complaints/:id
 * @desc Get complaint details by ID
 * @access Admin, Moderator
 */
router.get('/:id',
    authenticateToken,
    requireRole(['admin', 'moderator']),
    idValidation,
    complaintController.getComplaintDetails
);

/**
 * @route PUT /api/complaints/:id/status
 * @desc Update complaint status
 * @access Admin, Moderator
 */
router.put('/:id/status',
    authenticateToken,
    requireRole(['admin', 'moderator']),
    idValidation,
    updateComplaintValidation,
    complaintController.updateComplaintStatus
);

/**
 * @route PUT /api/complaints/bulk/status
 * @desc Bulk update complaint status
 * @access Admin, Moderator
 */
router.put('/bulk/status',
    authenticateToken,
    requireRole(['admin', 'moderator']),
    bulkUpdateValidation,
    complaintController.bulkUpdateStatus
);

/**
 * @route POST /api/complaints/:id/notes
 * @desc Add note to complaint
 * @access Admin, Moderator
 */
router.post('/:id/notes',
    authenticateToken,
    requireRole(['admin', 'moderator']),
    idValidation,
    addNoteValidation,
    complaintController.addNote
);

/**
 * @route PUT /api/complaints/:id/assign
 * @desc Assign complaint to user
 * @access Admin, Moderator
 */
router.put('/:id/assign',
    authenticateToken,
    requireRole(['admin', 'moderator']),
    idValidation,
    assignComplaintValidation,
    complaintController.assignComplaint
);

/**
 * Get complaint history
 * GET /api/complaints/:complaintId/history
 */
router.get('/:complaintId/history', 
    authenticateToken,
    requireRole(['admin', 'moderator', 'member']),
    param('complaintId').isInt({ min: 1 }).withMessage('Complaint ID must be a positive integer'),
    async (req, res) => {
        try {
            // Implementation would fetch complaint history/audit trail
            return res.json({
                success: true,
                data: {
                    complaint_id: req.params.complaintId,
                    history: [
                        {
                            id: 1,
                            action: 'created',
                            description: 'Complaint was filed',
                            user: 'John Doe',
                            timestamp: new Date().toISOString()
                        },
                        {
                            id: 2,
                            action: 'status_changed',
                            description: 'Status changed from pending to in_progress',
                            user: 'Admin User',
                            timestamp: new Date().toISOString()
                        }
                    ]
                }
            });
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error fetching complaint history'
            });
        }
    }
);

module.exports = router;