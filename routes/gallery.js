/**
 * Gallery Routes - Node.js/Express
 * BHRC India - Human Rights Commission Web Application
 * Defines gallery management API endpoints
 */

const express = require('express');
const { body, param, query } = require('express-validator');
const router = express.Router();
const galleryController = require('../controllers/galleryController');
const { authenticateToken, authorizeRoles } = require('../middleware/auth');

// Validation rules
const galleryValidation = {
    create: [
        body('title')
            .notEmpty()
            .withMessage('Title is required')
            .isLength({ min: 3, max: 255 })
            .withMessage('Title must be between 3 and 255 characters'),
        body('description')
            .optional()
            .isLength({ max: 1000 })
            .withMessage('Description must not exceed 1000 characters'),
        body('image_url')
            .notEmpty()
            .withMessage('Image URL is required')
            .isURL()
            .withMessage('Image URL must be a valid URL'),
        body('category')
            .optional()
            .isLength({ min: 2, max: 50 })
            .withMessage('Category must be between 2 and 50 characters')
    ],
    update: [
        param('id')
            .isInt({ min: 1 })
            .withMessage('Valid gallery item ID is required'),
        body('title')
            .notEmpty()
            .withMessage('Title is required')
            .isLength({ min: 3, max: 255 })
            .withMessage('Title must be between 3 and 255 characters'),
        body('description')
            .optional()
            .isLength({ max: 1000 })
            .withMessage('Description must not exceed 1000 characters'),
        body('image_url')
            .notEmpty()
            .withMessage('Image URL is required')
            .isURL()
            .withMessage('Image URL must be a valid URL'),
        body('category')
            .optional()
            .isLength({ min: 2, max: 50 })
            .withMessage('Category must be between 2 and 50 characters')
    ],
    delete: [
        param('id')
            .isInt({ min: 1 })
            .withMessage('Valid gallery item ID is required')
    ],
    bulkDelete: [
        body('item_ids')
            .isArray({ min: 1 })
            .withMessage('Item IDs array is required')
            .custom((value) => {
                if (!value.every(id => Number.isInteger(id) && id > 0)) {
                    throw new Error('All item IDs must be positive integers');
                }
                return true;
            })
    ],
    getItems: [
        query('page')
            .optional()
            .isInt({ min: 1 })
            .withMessage('Page must be a positive integer'),
        query('per_page')
            .optional()
            .isInt({ min: 1, max: 100 })
            .withMessage('Per page must be between 1 and 100'),
        query('category')
            .optional()
            .isLength({ min: 2, max: 50 })
            .withMessage('Category must be between 2 and 50 characters')
    ],
    getItem: [
        param('id')
            .isInt({ min: 1 })
            .withMessage('Valid gallery item ID is required')
    ]
};

// Public routes
router.get('/', galleryValidation.getItems, galleryController.getGalleryItems);
router.get('/categories', galleryController.getGalleryCategories);
router.get('/:id', galleryValidation.getItem, galleryController.getGalleryItem);

// Admin/Moderator/Volunteer routes
router.get('/admin/stats', 
    authenticateToken, 
    authorizeRoles(['admin', 'moderator']), 
    galleryController.getGalleryStats
);

router.post('/', 
    authenticateToken, 
    authorizeRoles(['admin', 'moderator', 'volunteer']), 
    galleryValidation.create, 
    galleryController.createGalleryItem
);

router.put('/:id', 
    authenticateToken, 
    authorizeRoles(['admin', 'moderator', 'volunteer']), 
    galleryValidation.update, 
    galleryController.updateGalleryItem
);

router.delete('/:id', 
    authenticateToken, 
    authorizeRoles(['admin', 'moderator']), 
    galleryValidation.delete, 
    galleryController.deleteGalleryItem
);

router.post('/bulk-delete', 
    authenticateToken, 
    authorizeRoles(['admin', 'moderator']), 
    galleryValidation.bulkDelete, 
    galleryController.bulkDeleteGalleryItems
);

module.exports = router;