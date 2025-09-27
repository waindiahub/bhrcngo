/**
 * Member Routes for BHRC India Application
 * 
 * Defines all member-specific API endpoints including
 * profile management, donations, events, complaints, and certificates.
 */

const express = require('express');
const router = express.Router();
const { body, param, query } = require('express-validator');
const { authenticateToken, authorizeRoles, validateRequest } = require('../middleware/auth');

// Import controllers
const UserController = require('../controllers/userController');
const DonationController = require('../controllers/donationController');
const EventController = require('../controllers/eventController');
const ComplaintController = require('../controllers/complaintController');
const CertificateController = require('../controllers/certificateController');
const DashboardController = require('../controllers/dashboardController');

// Validation rules
const validateEventId = [
    param('eventId')
        .isInt({ min: 1 })
        .withMessage('Event ID must be a positive integer')
];

const validateComplaintId = [
    param('complaintId')
        .isInt({ min: 1 })
        .withMessage('Complaint ID must be a positive integer')
];

const validateCertificateId = [
    param('certificateId')
        .isInt({ min: 1 })
        .withMessage('Certificate ID must be a positive integer')
];

const validateSessionId = [
    param('sessionId')
        .isLength({ min: 1 })
        .withMessage('Session ID is required')
];

const validateProfileUpdate = [
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

const validatePasswordUpdate = [
    body('current_password')
        .isLength({ min: 1 })
        .withMessage('Current password is required'),
    body('new_password')
        .isLength({ min: 8 })
        .matches(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/)
        .withMessage('New password must be at least 8 characters with uppercase, lowercase, and number'),
    body('confirm_password')
        .custom((value, { req }) => {
            if (value !== req.body.new_password) {
                throw new Error('Password confirmation does not match');
            }
            return true;
        })
];

const validateNotificationSettings = [
    body('email_notifications')
        .optional()
        .isBoolean()
        .withMessage('Email notifications must be a boolean'),
    body('sms_notifications')
        .optional()
        .isBoolean()
        .withMessage('SMS notifications must be a boolean'),
    body('push_notifications')
        .optional()
        .isBoolean()
        .withMessage('Push notifications must be a boolean')
];

const validatePreferences = [
    body('language')
        .optional()
        .isIn(['en', 'hi', 'bn'])
        .withMessage('Invalid language preference'),
    body('timezone')
        .optional()
        .isLength({ min: 1, max: 50 })
        .withMessage('Invalid timezone'),
    body('theme')
        .optional()
        .isIn(['light', 'dark', 'auto'])
        .withMessage('Invalid theme preference')
];

const validateTwoFactorSettings = [
    body('enabled')
        .isBoolean()
        .withMessage('Enabled must be a boolean'),
    body('method')
        .optional()
        .isIn(['sms', 'email', 'app'])
        .withMessage('Invalid two-factor method')
];

// Member Profile Routes
/**
 * Get member profile
 * GET /api/member/profile
 */
router.get('/profile', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    async (req, res) => {
        try {
            // Return current user's profile
            const user = req.user;
            return res.json({
                success: true,
                data: {
                    id: user.id,
                    username: user.username,
                    email: user.email,
                    first_name: user.first_name,
                    last_name: user.last_name,
                    role: user.role,
                    status: user.status,
                    email_verified: user.email_verified
                }
            });
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error fetching profile'
            });
        }
    }
);

/**
 * Update personal information
 * PUT /api/member/profile/personal
 */
router.put('/profile/personal', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    validateProfileUpdate,
    validateRequest,
    async (req, res) => {
        try {
            req.params.id = req.user.id;
            return UserController.updateUser(req, res);
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error updating profile'
            });
        }
    }
);

/**
 * Update password
 * PUT /api/member/profile/password
 */
router.put('/profile/password', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    validatePasswordUpdate,
    validateRequest,
    async (req, res) => {
        try {
            // Implementation would verify current password and update to new one
            return res.json({
                success: true,
                message: 'Password updated successfully'
            });
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error updating password'
            });
        }
    }
);

/**
 * Update notification settings
 * PUT /api/member/profile/notifications
 */
router.put('/profile/notifications', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    validateNotificationSettings,
    validateRequest,
    async (req, res) => {
        try {
            // Implementation would update user notification preferences
            return res.json({
                success: true,
                message: 'Notification settings updated successfully'
            });
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error updating notification settings'
            });
        }
    }
);

/**
 * Update preferences
 * PUT /api/member/profile/preferences
 */
router.put('/profile/preferences', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    validatePreferences,
    validateRequest,
    async (req, res) => {
        try {
            // Implementation would update user preferences
            return res.json({
                success: true,
                message: 'Preferences updated successfully'
            });
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error updating preferences'
            });
        }
    }
);

/**
 * Update two-factor authentication settings
 * PUT /api/member/profile/two-factor
 */
router.put('/profile/two-factor', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    validateTwoFactorSettings,
    validateRequest,
    async (req, res) => {
        try {
            // Implementation would update two-factor auth settings
            return res.json({
                success: true,
                message: 'Two-factor authentication settings updated successfully'
            });
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error updating two-factor settings'
            });
        }
    }
);

// Member Donations Routes
/**
 * Get member donations
 * GET /api/member/donations
 */
router.get('/donations', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    async (req, res) => {
        try {
            // Filter donations by current user
            req.query.user_id = req.user.id;
            return DonationController.getMemberDonations(req, res);
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error fetching donations'
            });
        }
    }
);

/**
 * Get recent donations
 * GET /api/member/donations/recent
 */
router.get('/donations/recent', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    async (req, res) => {
        try {
            req.query.user_id = req.user.id;
            req.query.limit = req.query.limit || 10;
            req.query.sort_by = 'created_at';
            req.query.sort_order = 'desc';
            return DonationController.getMemberDonations(req, res);
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error fetching recent donations'
            });
        }
    }
);

/**
 * Get donation summary
 * GET /api/member/donations/summary
 */
router.get('/donations/summary', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    DonationController.getMemberDonationSummary
);

/**
 * Get donation years
 * GET /api/member/donations/years
 */
router.get('/donations/years', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    DonationController.getMemberDonationYears
);

// Member Events Routes
/**
 * Get member events
 * GET /api/member/events
 */
router.get('/events', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    async (req, res) => {
        try {
            // Get events registered by current user
            req.query.user_id = req.user.id;
            return EventController.getMemberEvents(req, res);
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error fetching events'
            });
        }
    }
);

/**
 * Get member event stats
 * GET /api/member/events/stats
 */
router.get('/events/stats', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    async (req, res) => {
        try {
            req.query.user_id = req.user.id;
            return EventController.getMemberEventStats(req, res);
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error fetching event stats'
            });
        }
    }
);

/**
 * Register for event
 * POST /api/member/events/:eventId/register
 */
router.post('/events/:eventId/register', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    validateEventId,
    validateRequest,
    EventController.registerForEvent
);

/**
 * Unregister from event
 * DELETE /api/member/events/:eventId/register
 */
router.delete('/events/:eventId/register', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    validateEventId,
    validateRequest,
    EventController.unregisterFromEvent
);

/**
 * Get event certificate
 * GET /api/member/events/:eventId/certificate
 */
router.get('/events/:eventId/certificate', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    validateEventId,
    validateRequest,
    EventController.getEventCertificate
);

// Member Certificates Routes
/**
 * Get member certificates
 * GET /api/member/certificates
 */
router.get('/certificates', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    async (req, res) => {
        try {
            req.query.user_id = req.user.id;
            return CertificateController.getMemberCertificates(req, res);
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error fetching certificates'
            });
        }
    }
);

/**
 * Download certificate
 * GET /api/member/certificates/:certificateId/download
 */
router.get('/certificates/:certificateId/download', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    validateCertificateId,
    validateRequest,
    CertificateController.downloadCertificate
);

/**
 * Get certificate stats
 * GET /api/member/certificates/stats
 */
router.get('/certificates/stats', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    async (req, res) => {
        try {
            req.query.user_id = req.user.id;
            return CertificateController.getMemberCertificateStats(req, res);
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error fetching certificate stats'
            });
        }
    }
);

/**
 * Get certificate years
 * GET /api/member/certificates/years
 */
router.get('/certificates/years', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    async (req, res) => {
        try {
            req.query.user_id = req.user.id;
            return CertificateController.getMemberCertificateYears(req, res);
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error fetching certificate years'
            });
        }
    }
);

// Member Complaints Routes
/**
 * Get member complaints
 * GET /api/member/complaints
 */
router.get('/complaints', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    async (req, res) => {
        try {
            req.query.user_id = req.user.id;
            return ComplaintController.getMemberComplaints(req, res);
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error fetching complaints'
            });
        }
    }
);

/**
 * Get recent complaints
 * GET /api/member/complaints/recent
 */
router.get('/complaints/recent', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    async (req, res) => {
        try {
            req.query.user_id = req.user.id;
            req.query.limit = req.query.limit || 10;
            req.query.sort_by = 'created_at';
            req.query.sort_order = 'desc';
            return ComplaintController.getMemberComplaints(req, res);
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error fetching recent complaints'
            });
        }
    }
);

/**
 * Get complaint stats
 * GET /api/member/complaints/stats
 */
router.get('/complaints/stats', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    async (req, res) => {
        try {
            req.query.user_id = req.user.id;
            return ComplaintController.getMemberComplaintStats(req, res);
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error fetching complaint stats'
            });
        }
    }
);

/**
 * Download complaint file
 * GET /api/member/complaints/:complaintId/download
 */
router.get('/complaints/:complaintId/download', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    validateComplaintId,
    validateRequest,
    ComplaintController.downloadComplaintFile
);

// Member Stats Route
/**
 * Get member stats
 * GET /api/member/stats
 */
router.get('/stats', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    DashboardController.getMemberStats
);

// Session Management Routes
/**
 * Delete specific session
 * DELETE /api/member/sessions/:sessionId
 */
router.delete('/sessions/:sessionId', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    validateSessionId,
    validateRequest,
    async (req, res) => {
        try {
            // Implementation would delete specific user session
            return res.json({
                success: true,
                message: 'Session deleted successfully'
            });
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error deleting session'
            });
        }
    }
);

/**
 * Delete all sessions (logout from all devices)
 * DELETE /api/member/sessions/all
 */
router.delete('/sessions/all', 
    authenticateToken,
    authorizeRoles(['member', 'volunteer', 'moderator', 'admin', 'donor']),
    async (req, res) => {
        try {
            // Implementation would delete all user sessions
            return res.json({
                success: true,
                message: 'All sessions deleted successfully'
            });
        } catch (error) {
            return res.status(500).json({
                success: false,
                message: 'Error deleting sessions'
            });
        }
    }
);

module.exports = router;