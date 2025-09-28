/**
 * News Routes - Node.js/Express
 * BHRC India - Human Rights Commission Web Application
 * Defines all news-related API endpoints
 */

const express = require('express');
const { body, query, param } = require('express-validator');
const router = express.Router();
const newsController = require('../controllers/newsController');
const { authenticateToken, authorizeRoles } = require('../middleware/auth');

// Validation rules for creating news
const createNewsValidation = [
    body('title')
        .notEmpty()
        .withMessage('Title is required')
        .isLength({ min: 3, max: 200 })
        .withMessage('Title must be between 3 and 200 characters'),
    
    body('excerpt')
        .notEmpty()
        .withMessage('Excerpt is required')
        .isLength({ min: 10, max: 500 })
        .withMessage('Excerpt must be between 10 and 500 characters'),
    
    body('content')
        .notEmpty()
        .withMessage('Content is required')
        .isLength({ min: 50 })
        .withMessage('Content must be at least 50 characters long'),
    
    body('category')
        .optional()
        .isIn(['news', 'press_release', 'article', 'announcement', 'blog'])
        .withMessage('Invalid category'),
    
    body('status')
        .optional()
        .isIn(['draft', 'published', 'archived'])
        .withMessage('Invalid status'),
    
    body('featured_image')
        .optional()
        .custom((value) => {
            if (value && value.trim() !== '') {
                // Only validate URL if value is provided and not empty
                const urlPattern = /^https?:\/\/.+/;
                if (!urlPattern.test(value)) {
                    throw new Error('Invalid image URL');
                }
            }
            return true;
        })
];

// Validation rules for updating news
const updateNewsValidation = [
    param('id')
        .isInt({ min: 1 })
        .withMessage('Valid news ID is required'),
    
    body('title')
        .optional()
        .isLength({ min: 3, max: 200 })
        .withMessage('Title must be between 3 and 200 characters'),
    
    body('excerpt')
        .optional()
        .isLength({ min: 10, max: 500 })
        .withMessage('Excerpt must be between 10 and 500 characters'),
    
    body('content')
        .optional()
        .isLength({ min: 50 })
        .withMessage('Content must be at least 50 characters long'),
    
    body('category')
        .optional()
        .isIn(['news', 'press_release', 'article', 'announcement', 'blog'])
        .withMessage('Invalid category'),
    
    body('status')
        .optional()
        .isIn(['draft', 'published', 'archived'])
        .withMessage('Invalid status'),
    
    body('featured_image')
        .optional()
        .custom((value) => {
            if (value && value.trim() !== '') {
                // Only validate URL if value is provided and not empty
                const urlPattern = /^https?:\/\/.+/;
                if (!urlPattern.test(value)) {
                    throw new Error('Invalid image URL');
                }
            }
            return true;
        })
];

// Validation rules for query parameters
const queryValidation = [
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
        .isIn(['news', 'press_release', 'article', 'announcement', 'blog'])
        .withMessage('Invalid category'),
    
    query('status')
        .optional()
        .isIn(['draft', 'published', 'archived'])
        .withMessage('Invalid status')
];

// Validation for news ID parameter
const newsIdValidation = [
    param('id')
        .isInt({ min: 1 })
        .withMessage('Valid news ID is required')
];

// PUBLIC ROUTES

/**
 * @route   GET /api/news
 * @desc    Get all published news articles with filtering and pagination
 * @access  Public
 */
router.get('/', queryValidation, newsController.getNews);

/**
 * @route   GET /api/news/categories
 * @desc    Get all news categories with article counts
 * @access  Public
 */
router.get('/categories', newsController.getNewsCategories);

/**
 * @route   GET /api/news/:id
 * @desc    Get single news article by ID
 * @access  Public
 */
router.get('/:id', newsIdValidation, newsController.getNewsItem);

// ADMIN/MODERATOR ROUTES

/**
 * @route   GET /api/news/admin/stats
 * @desc    Get news statistics for admin dashboard
 * @access  Private (Admin/Moderator)
 */
router.get('/admin/stats', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    newsController.getNewsStats
);

/**
 * @route   GET /api/news/admin/all
 * @desc    Get all news articles for admin (including drafts)
 * @access  Private (Admin/Moderator)
 */
router.get('/admin/all', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    queryValidation,
    newsController.getAdminNews
);

/**
 * @route   POST /api/news
 * @desc    Create new news article
 * @access  Private (Admin/Moderator)
 */
router.post('/',
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    createNewsValidation,
    newsController.createNews
);

/**
 * @route   PUT /api/news/:id
 * @desc    Update news article
 * @access  Private (Admin/Moderator)
 */
router.put('/:id',
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    updateNewsValidation,
    newsController.updateNews
);

/**
 * @route   DELETE /api/news/:id
 * @desc    Delete news article
 * @access  Private (Admin/Moderator)
 */
router.delete('/:id',
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    newsIdValidation,
    newsController.deleteNews
);

module.exports = router;