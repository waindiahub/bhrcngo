/**
 * Contact Routes for BHRC India Application
 * 
 * Defines contact form and related API endpoints.
 */

const express = require('express');
const router = express.Router();
const { body } = require('express-validator');
const { validateRequest, optionalAuth } = require('../middleware/auth');

// Validation rules for contact form
const validateContactForm = [
    body('name')
        .isLength({ min: 2, max: 100 })
        .matches(/^[a-zA-Z\s]+$/)
        .withMessage('Name must be 2-100 characters and contain only letters'),
    body('email')
        .isEmail()
        .normalizeEmail()
        .withMessage('Valid email is required'),
    body('phone')
        .optional()
        .isMobilePhone()
        .withMessage('Invalid phone number'),
    body('subject')
        .isLength({ min: 5, max: 200 })
        .withMessage('Subject must be 5-200 characters'),
    body('message')
        .isLength({ min: 10, max: 2000 })
        .withMessage('Message must be 10-2000 characters'),
    body('category')
        .optional()
        .isIn(['general', 'complaint', 'suggestion', 'support', 'donation', 'volunteer'])
        .withMessage('Invalid category'),
    body('priority')
        .optional()
        .isIn(['low', 'medium', 'high', 'urgent'])
        .withMessage('Invalid priority')
];

/**
 * Submit contact form
 * POST /api/contact
 */
router.post('/', 
    optionalAuth, // Allow both authenticated and anonymous submissions
    validateContactForm,
    validateRequest,
    async (req, res) => {
        try {
            const {
                name,
                email,
                phone,
                subject,
                message,
                category = 'general',
                priority = 'medium'
            } = req.body;

            // Get user ID if authenticated
            const userId = req.user ? req.user.id : null;

            // Create contact submission record
            const contactData = {
                user_id: userId,
                name: name,
                email: email,
                phone: phone || null,
                subject: subject,
                message: message,
                category: category,
                priority: priority,
                status: 'new',
                ip_address: req.ip,
                user_agent: req.get('User-Agent'),
                created_at: new Date(),
                updated_at: new Date()
            };

            // In a real implementation, this would save to database
            // const db = require('../config/database');
            // const contactId = await db.insert('contact_submissions', contactData);

            // Send notification email to admin
            // await sendContactNotificationEmail(contactData);

            // Send confirmation email to user
            // await sendContactConfirmationEmail(contactData);

            return res.status(201).json({
                success: true,
                message: 'Contact form submitted successfully. We will get back to you soon.',
                data: {
                    id: Math.floor(Math.random() * 10000), // Mock ID
                    reference_number: `BHRC-${Date.now()}`,
                    status: 'submitted',
                    estimated_response_time: '24-48 hours'
                }
            });

        } catch (error) {
            console.error('Contact form submission error:', error);
            return res.status(500).json({
                success: false,
                message: 'Error submitting contact form. Please try again later.'
            });
        }
    }
);

/**
 * Get contact form status (for authenticated users)
 * GET /api/contact/:referenceNumber/status
 */
router.get('/:referenceNumber/status', 
    optionalAuth,
    async (req, res) => {
        try {
            const { referenceNumber } = req.params;

            // Validate reference number format
            if (!referenceNumber.match(/^BHRC-\d+$/)) {
                return res.status(400).json({
                    success: false,
                    message: 'Invalid reference number format'
                });
            }

            // In a real implementation, this would query the database
            // const contact = await db.queryOne(
            //     'SELECT * FROM contact_submissions WHERE reference_number = ?',
            //     [referenceNumber]
            // );

            // Mock response
            const mockContact = {
                id: 1,
                reference_number: referenceNumber,
                status: 'in_progress',
                subject: 'General Inquiry',
                created_at: new Date().toISOString(),
                updated_at: new Date().toISOString(),
                estimated_response_time: '24-48 hours',
                last_update: 'Your inquiry has been assigned to our support team.'
            };

            return res.json({
                success: true,
                data: mockContact
            });

        } catch (error) {
            console.error('Contact status check error:', error);
            return res.status(500).json({
                success: false,
                message: 'Error checking contact status'
            });
        }
    }
);

/**
 * Get contact categories
 * GET /api/contact/categories
 */
router.get('/categories', async (req, res) => {
    try {
        const categories = [
            {
                value: 'general',
                label: 'General Inquiry',
                description: 'General questions about BHRC India'
            },
            {
                value: 'complaint',
                label: 'Complaint',
                description: 'File a complaint or report an issue'
            },
            {
                value: 'suggestion',
                label: 'Suggestion',
                description: 'Provide suggestions for improvement'
            },
            {
                value: 'support',
                label: 'Technical Support',
                description: 'Get help with technical issues'
            },
            {
                value: 'donation',
                label: 'Donation Inquiry',
                description: 'Questions about donations and contributions'
            },
            {
                value: 'volunteer',
                label: 'Volunteer Opportunity',
                description: 'Inquire about volunteer opportunities'
            }
        ];

        return res.json({
            success: true,
            data: categories
        });

    } catch (error) {
        console.error('Contact categories error:', error);
        return res.status(500).json({
            success: false,
            message: 'Error fetching contact categories'
        });
    }
});

/**
 * Get contact information
 * GET /api/contact/info
 */
router.get('/info', async (req, res) => {
    try {
        const contactInfo = {
            organization: 'BHRC India',
            email: 'contact@bhrcindia.org',
            phone: '+91-XXX-XXX-XXXX',
            address: {
                street: 'BHRC India Office',
                city: 'New Delhi',
                state: 'Delhi',
                postal_code: '110001',
                country: 'India'
            },
            office_hours: {
                monday_friday: '9:00 AM - 6:00 PM',
                saturday: '9:00 AM - 2:00 PM',
                sunday: 'Closed'
            },
            social_media: {
                facebook: 'https://facebook.com/bhrcindia',
                twitter: 'https://twitter.com/bhrcindia',
                linkedin: 'https://linkedin.com/company/bhrcindia',
                instagram: 'https://instagram.com/bhrcindia'
            },
            emergency_contact: {
                phone: '+91-XXX-XXX-XXXX',
                email: 'emergency@bhrcindia.org',
                available: '24/7'
            }
        };

        return res.json({
            success: true,
            data: contactInfo
        });

    } catch (error) {
        console.error('Contact info error:', error);
        return res.status(500).json({
            success: false,
            message: 'Error fetching contact information'
        });
    }
});

module.exports = router;