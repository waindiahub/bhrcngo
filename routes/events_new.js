const express = require('express');
const { body, param, query } = require('express-validator');
const eventController = require('../controllers/eventController');
const { authenticateToken, authorizeRoles, validateRequest } = require('../middleware/auth');

const router = express.Router();

/**
 * Validation rules for event creation
 */
const createEventValidation = [
    body('title')
        .notEmpty()
        .withMessage('Title is required')
        .isLength({ min: 3, max: 255 })
        .withMessage('Title must be between 3 and 255 characters'),
    
    body('description')
        .notEmpty()
        .withMessage('Description is required')
        .isLength({ min: 10 })
        .withMessage('Description must be at least 10 characters'),
    
    body('type')
        .notEmpty()
        .withMessage('Event type is required')
        .isIn(['workshop', 'seminar', 'conference', 'meeting', 'training', 'other'])
        .withMessage('Invalid event type'),
    
    body('event_date')
        .notEmpty()
        .withMessage('Event date is required')
        .isISO8601()
        .withMessage('Invalid date format')
        .custom((value) => {
            if (new Date(value) < new Date().setHours(0, 0, 0, 0)) {
                throw new Error('Event date cannot be in the past');
            }
            return true;
        }),
    
    body('event_time')
        .optional()
        .matches(/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/)
        .withMessage('Invalid time format (HH:MM)'),
    
    body('location')
        .notEmpty()
        .withMessage('Location is required')
        .isLength({ min: 3, max: 255 })
        .withMessage('Location must be between 3 and 255 characters'),
    
    body('registration_fee')
        .optional()
        .isFloat({ min: 0 })
        .withMessage('Registration fee must be a positive number'),
    
    body('max_participants')
        .optional({ nullable: true })
        .custom((value) => {
            if (value === null || value === undefined || value === '') return true;
            if (!Number.isInteger(Number(value)) || Number(value) < 1) {
                throw new Error('Max participants must be a positive integer');
            }
            return true;
        }),
    
    body('registration_deadline')
        .optional({ nullable: true })
        .custom((value) => {
            if (value === null || value === undefined || value === '') return true;
            if (!Date.parse(value)) {
                throw new Error('Invalid registration deadline format');
            }
            return true;
        }),
    
    body('status')
        .optional()
        .isIn(['upcoming', 'ongoing', 'completed', 'cancelled', 'postponed'])
        .withMessage('Invalid status'),
    
    body('is_public')
        .optional()
        .isBoolean()
        .withMessage('is_public must be a boolean'),
    
    body('registration_open')
        .optional()
        .isBoolean()
        .withMessage('registration_open must be a boolean')
];

/**
 * Validation rules for event updates
 */
const updateEventValidation = [
    param('eventId')
        .isInt({ min: 1 })
        .withMessage('Invalid event ID'),
    
    body('title')
        .optional()
        .isLength({ min: 3, max: 255 })
        .withMessage('Title must be between 3 and 255 characters'),
    
    body('description')
        .optional()
        .isLength({ min: 10 })
        .withMessage('Description must be at least 10 characters'),
    
    body('type')
        .optional()
        .isIn(['workshop', 'seminar', 'conference', 'meeting', 'training', 'other'])
        .withMessage('Invalid event type'),
    
    body('event_date')
        .optional()
        .isISO8601()
        .withMessage('Invalid date format'),
    
    body('event_time')
        .optional()
        .matches(/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/)
        .withMessage('Invalid time format (HH:MM)'),
    
    body('location')
        .optional()
        .isLength({ min: 3, max: 255 })
        .withMessage('Location must be between 3 and 255 characters'),
    
    body('registration_fee')
        .optional()
        .isFloat({ min: 0 })
        .withMessage('Registration fee must be a positive number'),
    
    body('max_participants')
        .optional({ nullable: true })
        .custom((value) => {
            if (value === null || value === undefined || value === '') return true;
            if (!Number.isInteger(Number(value)) || Number(value) < 1) {
                throw new Error('Max participants must be a positive integer');
            }
            return true;
        }),
    
    body('registration_deadline')
        .optional({ nullable: true })
        .custom((value) => {
            if (value === null || value === undefined || value === '') return true;
            if (!Date.parse(value)) {
                throw new Error('Invalid registration deadline format');
            }
            return true;
        }),
    
    body('status')
        .optional()
        .isIn(['upcoming', 'ongoing', 'completed', 'cancelled', 'postponed'])
        .withMessage('Invalid status'),
    
    body('is_public')
        .optional()
        .isBoolean()
        .withMessage('is_public must be a boolean'),
    
    body('registration_open')
        .optional()
        .isBoolean()
        .withMessage('registration_open must be a boolean')
];

/**
 * Validation rules for event registration
 */
const registerEventValidation = [
    param('eventId')
        .isInt({ min: 1 })
        .withMessage('Invalid event ID'),
    
    body('participant_name')
        .notEmpty()
        .withMessage('Participant name is required')
        .isLength({ min: 2, max: 100 })
        .withMessage('Participant name must be between 2 and 100 characters'),
    
    body('participant_email')
        .notEmpty()
        .withMessage('Email is required')
        .isEmail()
        .withMessage('Invalid email format')
        .normalizeEmail(),
    
    body('participant_phone')
        .notEmpty()
        .withMessage('Phone number is required')
        .matches(/^[0-9]{10}$/)
        .withMessage('Phone number must be 10 digits'),
    
    body('participant_role')
        .optional()
        .isLength({ min: 2, max: 50 })
        .withMessage('Participant role must be between 2 and 50 characters'),
    
    body('notes')
        .optional()
        .isLength({ max: 500 })
        .withMessage('Notes must not exceed 500 characters')
];

/**
 * Validation rules for registration updates
 */
const updateRegistrationValidation = [
    param('eventId')
        .isInt({ min: 1 })
        .withMessage('Invalid event ID'),
    
    param('registrationId')
        .isInt({ min: 1 })
        .withMessage('Invalid registration ID'),
    
    body('status')
        .optional()
        .isIn(['pending', 'confirmed', 'cancelled', 'attended'])
        .withMessage('Invalid status'),
    
    body('payment_status')
        .optional()
        .isIn(['pending', 'paid', 'refunded'])
        .withMessage('Invalid payment status'),
    
    body('amount_paid')
        .optional()
        .isFloat({ min: 0 })
        .withMessage('Amount paid must be a positive number'),
    
    body('notes')
        .optional()
        .isLength({ max: 500 })
        .withMessage('Notes must not exceed 500 characters')
];

/**
 * Validation rules for bulk operations
 */
const bulkUpdateValidation = [
    param('eventId')
        .isInt({ min: 1 })
        .withMessage('Invalid event ID'),
    
    body('registration_ids')
        .isArray({ min: 1 })
        .withMessage('Registration IDs must be a non-empty array'),
    
    body('registration_ids.*')
        .isInt({ min: 1 })
        .withMessage('Each registration ID must be a positive integer'),
    
    body('status')
        .notEmpty()
        .withMessage('Status is required')
        .isIn(['pending', 'confirmed', 'cancelled', 'attended'])
        .withMessage('Invalid status')
];

/**
 * Validation rules for bulk email
 */
const bulkEmailValidation = [
    param('eventId')
        .isInt({ min: 1 })
        .withMessage('Invalid event ID'),
    
    body('registration_ids')
        .isArray({ min: 1 })
        .withMessage('Registration IDs must be a non-empty array'),
    
    body('registration_ids.*')
        .isInt({ min: 1 })
        .withMessage('Each registration ID must be a positive integer'),
    
    body('subject')
        .notEmpty()
        .withMessage('Subject is required')
        .isLength({ min: 3, max: 200 })
        .withMessage('Subject must be between 3 and 200 characters'),
    
    body('message')
        .notEmpty()
        .withMessage('Message is required')
        .isLength({ min: 10, max: 2000 })
        .withMessage('Message must be between 10 and 2000 characters')
];

/**
 * Query validation for listing endpoints
 */
const listQueryValidation = [
    query('page')
        .optional()
        .isInt({ min: 1 })
        .withMessage('Page must be a positive integer'),
    
    query('limit')
        .optional()
        .isInt({ min: 1, max: 100 })
        .withMessage('Limit must be between 1 and 100'),
    
    query('search')
        .optional()
        .isLength({ max: 100 })
        .withMessage('Search term must not exceed 100 characters'),
    
    query('status')
        .optional()
        .isIn(['upcoming', 'ongoing', 'completed', 'cancelled', 'postponed'])
        .withMessage('Invalid status filter'),
    
    query('type')
        .optional()
        .isIn(['workshop', 'seminar', 'conference', 'meeting', 'training', 'other'])
        .withMessage('Invalid type filter'),
    
    query('sort_by')
        .optional()
        .isIn(['created_at', 'event_date', 'title', 'type', 'status'])
        .withMessage('Invalid sort field'),
    
    query('sort_order')
        .optional()
        .isIn(['ASC', 'DESC'])
        .withMessage('Sort order must be ASC or DESC'),
    
    query('upcoming_only')
        .optional()
        .isBoolean()
        .withMessage('upcoming_only must be a boolean')
];

// ==================== TEST ROUTES ====================

/**
 * @route GET /api/events/test
 * @desc Test database connection
 * @access Public
 */
router.get('/test', eventController.testConnection);

// ==================== PUBLIC ROUTES ====================

/**
 * @route GET /api/events/public
 * @desc Get public events
 * @access Public
 */
router.get('/public', 
    listQueryValidation,
    validateRequest,
    eventController.getPublicEvents
);

/**
 * @route GET /api/events/public/:eventId
 * @desc Get public event details
 * @access Public
 */
router.get('/public/:eventId',
    param('eventId').isInt({ min: 1 }).withMessage('Invalid event ID'),
    validateRequest,
    eventController.getEventDetails
);

/**
 * @route POST /api/events/:eventId/register
 * @desc Register for an event
 * @access Public
 */
router.post('/:eventId/register',
    registerEventValidation,
    validateRequest,
    eventController.registerForEvent
);

// ==================== ADMIN/MODERATOR ROUTES ====================

/**
 * @route GET /api/events
 * @desc Get all events (admin/moderator only)
 * @access Admin, Moderator
 */
router.get('/',
    listQueryValidation,
    validateRequest,
    eventController.getAllEvents
);

/**
 * @route GET /api/events/stats
 * @desc Get event statistics (admin/moderator only)
 * @access Admin, Moderator
 */
router.get('/stats',
    eventController.getEventStats
);

/**
 * @route GET /api/events/:eventId
 * @desc Get event details (admin/moderator only)
 * @access Admin, Moderator
 */
router.get('/:eventId',
    param('eventId').isInt({ min: 1 }).withMessage('Invalid event ID'),
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateRequest,
    eventController.getEventDetails
);

/**
 * @route POST /api/events
 * @desc Create new event (admin/moderator only)
 * @access Admin, Moderator
 */
router.post('/',
    createEventValidation,
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateRequest,
    eventController.createEvent
);

/**
 * @route PUT /api/events/:eventId
 * @desc Update event (admin/moderator only)
 * @access Admin, Moderator
 */
router.put('/:eventId',
    updateEventValidation,
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateRequest,
    eventController.updateEvent
);

/**
 * @route DELETE /api/events/:eventId
 * @desc Delete event (admin/moderator only)
 * @access Admin, Moderator
 */
router.delete('/:eventId',
    param('eventId').isInt({ min: 1 }).withMessage('Invalid event ID'),
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateRequest,
    eventController.deleteEvent
);

// ==================== REGISTRATION MANAGEMENT ROUTES ====================

/**
 * @route GET /api/events/:eventId/registrations
 * @desc Get event registrations (admin/moderator only)
 * @access Admin, Moderator
 */
router.get('/:eventId/registrations',
    [
        param('eventId').isInt({ min: 1 }).withMessage('Invalid event ID'),
        ...listQueryValidation
    ],
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateRequest,
    eventController.getEventRegistrations
);

/**
 * @route PUT /api/events/:eventId/registrations/:registrationId
 * @desc Update event registration (admin/moderator only)
 * @access Admin, Moderator
 */
router.put('/:eventId/registrations/:registrationId',
    updateRegistrationValidation,
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateRequest,
    eventController.updateRegistration
);

/**
 * @route POST /api/events/:eventId/registrations/bulk-update
 * @desc Bulk update registrations (admin/moderator only)
 * @access Admin, Moderator
 */
router.post('/:eventId/registrations/bulk-update',
    bulkUpdateValidation,
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateRequest,
    eventController.bulkUpdateRegistrations
);

/**
 * @route POST /api/events/:eventId/registrations/bulk-email
 * @desc Send bulk email to registrants (admin/moderator only)
 * @access Admin, Moderator
 */
router.post('/:eventId/registrations/bulk-email',
    bulkEmailValidation,
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateRequest,
    eventController.sendBulkEmail
);

/**
 * @route GET /api/events/:eventId/registrations/export
 * @desc Export event registrations (admin/moderator only)
 * @access Admin, Moderator
 */
router.get('/:eventId/registrations/export',
    param('eventId').isInt({ min: 1 }).withMessage('Invalid event ID'),
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateRequest,
    eventController.exportRegistrations
);

module.exports = router;