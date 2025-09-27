/**
 * File Routes
 * Handles file upload and management API endpoints
 * BHRC India - Human Rights Commission Web Application
 */

const express = require('express');
const router = express.Router();
const { param, query } = require('express-validator');
const { FileController, uploadImage, uploadDocument } = require('../controllers/fileController');
const { authenticateToken, authorizeRoles } = require('../middleware/auth');

// Validation for filename parameter
const validateFilename = [
    param('filename')
        .matches(/^[a-zA-Z0-9_\-\.]+$/)
        .withMessage('Invalid filename format')
        .isLength({ min: 1, max: 255 })
        .withMessage('Filename must be 1-255 characters')
];

// Validation for query parameters
const validateQueryParams = [
    query('page')
        .optional()
        .isInt({ min: 1 })
        .withMessage('Page must be a positive integer'),
    query('per_page')
        .optional()
        .isInt({ min: 1, max: 100 })
        .withMessage('Per page must be between 1 and 100'),
    query('file_type')
        .optional()
        .isIn(['image', 'document'])
        .withMessage('File type must be image or document'),
    query('search')
        .optional()
        .isLength({ max: 255 })
        .withMessage('Search term too long')
];

/**
 * @route POST /api/files/upload/image
 * @desc Upload image file
 * @access Authenticated users
 */
router.post('/upload/image', 
    authenticateToken,
    uploadImage,
    FileController.uploadImage
);

/**
 * @route POST /api/files/upload/document
 * @desc Upload document file
 * @access Authenticated users
 */
router.post('/upload/document', 
    authenticateToken,
    uploadDocument,
    FileController.uploadDocument
);

/**
 * @route GET /api/files
 * @desc Get list of uploaded files
 * @access Admin/Moderator only
 */
router.get('/', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateQueryParams,
    FileController.getFiles
);

/**
 * @route GET /api/files/:filename
 * @desc Get file details
 * @access Own files or Admin/Moderator
 */
router.get('/:filename', 
    authenticateToken,
    validateFilename,
    FileController.getFileDetails
);

/**
 * @route DELETE /api/files/:filename
 * @desc Delete file
 * @access Admin/Moderator only
 */
router.delete('/:filename', 
    authenticateToken,
    authorizeRoles(['admin', 'moderator']),
    validateFilename,
    FileController.deleteFile
);

// Error handling middleware for multer errors
router.use((error, req, res, next) => {
    if (error.code === 'LIMIT_FILE_SIZE') {
        return res.status(400).json({
            success: false,
            message: 'File too large. Maximum size is 5MB'
        });
    }
    
    if (error.code === 'LIMIT_UNEXPECTED_FILE') {
        return res.status(400).json({
            success: false,
            message: 'Unexpected file field'
        });
    }
    
    if (error.message.includes('Invalid file type')) {
        return res.status(400).json({
            success: false,
            message: error.message
        });
    }
    
    // Pass other errors to default error handler
    next(error);
});

module.exports = router;