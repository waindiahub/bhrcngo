/**
 * Donation Routes - Node.js/Express
 * Handles all donation-related API endpoints
 */

const express = require('express');
const { body } = require('express-validator');
const router = express.Router();
const donationController = require('../controllers/donationController');
const { authenticateToken, authorizeRoles, optionalAuth } = require('../middleware/auth');

/**
 * Validation Rules
 */

// Donation creation validation
const donationValidation = [
    body('amount')
        .isFloat({ min: 0.01 })
        .withMessage('Amount must be a positive number'),
    body('type')
        .isIn(['one-time', 'monthly', 'annual'])
        .withMessage('Invalid donation type'),
    body('category')
        .isIn(['general', 'healthcare', 'education', 'community', 'emergency'])
        .withMessage('Invalid donation category'),
    body('status')
        .isIn(['pending', 'completed', 'failed', 'refunded'])
        .withMessage('Invalid donation status'),
    body('payment_method')
        .isIn(['credit_card', 'debit_card', 'bank_transfer', 'paypal', 'cash', 'check'])
        .withMessage('Invalid payment method'),
    body('payment_status')
        .isIn(['pending', 'paid', 'failed', 'refunded'])
        .withMessage('Invalid payment status'),
    body('donor_email')
        .optional()
        .isEmail()
        .withMessage('Invalid email address'),
    body('donor_name')
        .optional()
        .isLength({ min: 1, max: 255 })
        .withMessage('Donor name must be between 1 and 255 characters')
];

// Public donation validation
const publicDonationValidation = [
    body('amount')
        .isFloat({ min: 100 })
        .withMessage('Minimum donation amount is ₹100'),
    body('purpose')
        .isIn(['general', 'healthcare', 'education', 'community', 'emergency'])
        .withMessage('Invalid donation purpose'),
    body('firstName')
        .isLength({ min: 1, max: 100 })
        .withMessage('First name is required and must be less than 100 characters'),
    body('lastName')
        .isLength({ min: 1, max: 100 })
        .withMessage('Last name is required and must be less than 100 characters'),
    body('email')
        .isEmail()
        .withMessage('Valid email address is required'),
    body('phone')
        .optional()
        .isMobilePhone()
        .withMessage('Invalid phone number'),
    body('type')
        .optional()
        .isIn(['one-time', 'monthly', 'annual'])
        .withMessage('Invalid donation type')
];

/**
 * Member Donation Routes (Authenticated users only)
 */

// GET /api/donations/member - Get member's own donations
router.get('/member', 
    authenticateToken, 
    donationController.getMemberDonations
);

// GET /api/donations/member/summary - Get member's donation summary
router.get('/member/summary', 
    authenticateToken, 
    donationController.getMemberDonationSummary
);

// GET /api/donations/member/years - Get years when member made donations
router.get('/member/years', 
    authenticateToken, 
    donationController.getMemberDonationYears
);

// GET /api/donations/member/:donationId/receipt - Generate receipt for member's donation
router.get('/member/:donationId/receipt', 
    authenticateToken, 
    donationController.getMemberDonationReceipt
);

/**
 * Admin/Moderator Donation Routes
 */

// GET /api/donations - Get all donations (Admin/Moderator only)
router.get('/', 
    authenticateToken, 
    authorizeRoles(['admin', 'moderator']), 
    donationController.getAllDonations
);

// GET /api/donations/stats - Get donation statistics (Admin/Moderator only)
router.get('/stats', 
    authenticateToken, 
    authorizeRoles(['admin', 'moderator']), 
    donationController.getDonationStats
);

// GET /api/donations/export - Export donations to CSV (Admin/Moderator only)
router.get('/export', 
    authenticateToken, 
    authorizeRoles(['admin', 'moderator']), 
    donationController.exportDonations
);

// GET /api/donations/:donationId - Get donation details (Admin/Moderator only)
router.get('/:donationId', 
    authenticateToken, 
    authorizeRoles(['admin', 'moderator']), 
    donationController.getDonationDetails
);

// POST /api/donations - Create new donation (Admin/Moderator only)
router.post('/', 
    authenticateToken, 
    authorizeRoles(['admin', 'moderator']), 
    donationValidation,
    donationController.createDonation
);

// PUT /api/donations/:donationId - Update donation (Admin/Moderator only)
router.put('/:donationId', 
    authenticateToken, 
    authorizeRoles(['admin', 'moderator']), 
    donationController.updateDonation
);

// DELETE /api/donations/:donationId - Delete donation (Admin only)
router.delete('/:donationId', 
    authenticateToken, 
    authorizeRoles(['admin']), 
    donationController.deleteDonation
);

// GET /api/donations/:donationId/receipt - Generate receipt (Admin/Moderator only)
router.get('/:donationId/receipt', 
    authenticateToken, 
    authorizeRoles(['admin', 'moderator']), 
    donationController.generateReceipt
);

// POST /api/donations/:donationId/thank-you - Send thank you email (Admin/Moderator only)
router.post('/:donationId/thank-you', 
    authenticateToken, 
    authorizeRoles(['admin', 'moderator']), 
    donationController.sendThankYou
);

/**
 * Public Donation Routes (No authentication required)
 */

// GET /api/donations/public/stats - Get public donation statistics
router.get('/public/stats', donationController.getPublicDonationStats);

// GET /api/donations/public/purposes - Get donation purposes/categories
router.get('/public/purposes', donationController.getDonationPurposes);

// GET /api/donations/public/recent-donors - Get recent donors (for public display)
router.get('/public/recent-donors', donationController.getRecentDonors);

// POST /api/donations/public - Create public donation (from donation page)
router.post('/public', 
    publicDonationValidation,
    donationController.createPublicDonation
);

/**
 * Create donation (alternative path)
 * POST /api/donations/create
 */
router.post('/create', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator', 'member', 'donor']),
    donationValidation,
    donationController.createDonation
);

/**
 * Get donation purposes (alternative path)
 * GET /api/donations/purposes
 */
router.get('/purposes', async (req, res) => {
    try {
        // Redirect to public purposes endpoint
        return donationController.getDonationPurposes(req, res);
    } catch (error) {
        return res.status(500).json({
            success: false,
            message: 'Error fetching donation purposes'
        });
    }
});

/**
 * Get recent donations (alternative path)
 * GET /api/donations/recent
 */
router.get('/recent', async (req, res) => {
    try {
        // Redirect to public recent donors endpoint
        return donationController.getRecentDonors(req, res);
    } catch (error) {
        return res.status(500).json({
            success: false,
            message: 'Error fetching recent donations'
        });
    }
});

module.exports = router;