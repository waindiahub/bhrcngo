/**
 * File Controller
 * Handles file upload and management API endpoints
 * BHRC India - Human Rights Commission Web Application
 */

const mysql = require('mysql2/promise');
const multer = require('multer');
const path = require('path');
const fs = require('fs').promises;
const crypto = require('crypto');
const { validationResult } = require('express-validator');
const { logActivity, logError } = require('../middleware/logger');
const ResponseHelper = require('../utils/responseHelper');
const QueryBuilder = require('../utils/queryBuilder');

/**
 * Database connection configuration
 */
const dbConfig = {
    host: '37.27.60.109',
    user: 'tzdmiohj_bhmc',
    password: 'tzdmiohj_bhmc',
    database: 'tzdmiohj_bhmc',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
};

// Create connection pool
const pool = mysql.createPool(dbConfig);

// File upload configuration
const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
const ALLOWED_FILE_TYPES = {
    image: ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    document: ['pdf', 'doc', 'docx', 'txt', 'rtf']
};

const UPLOADS_PATH = path.join(__dirname, '../../uploads');
const APP_URL = 'https://bhrcdata.online';

/**
 * Multer configuration for file uploads
 */
const storage = multer.diskStorage({
    destination: async (req, file, cb) => {
        try {
            const fileType = file.fieldname === 'image' ? 'images' : 'documents';
            const uploadDir = path.join(UPLOADS_PATH, fileType);
            
            // Create directory if it doesn't exist
            await fs.mkdir(uploadDir, { recursive: true });
            cb(null, uploadDir);
        } catch (error) {
            cb(error);
        }
    },
    filename: (req, file, cb) => {
        const uniqueSuffix = crypto.randomBytes(16).toString('hex');
        const timestamp = Date.now();
        const extension = path.extname(file.originalname).toLowerCase();
        const filename = `${uniqueSuffix}_${timestamp}${extension}`;
        cb(null, filename);
    }
});

const fileFilter = (req, file, cb) => {
    const extension = path.extname(file.originalname).toLowerCase().substring(1);
    const fileType = file.fieldname === 'image' ? 'image' : 'document';
    
    if (ALLOWED_FILE_TYPES[fileType].includes(extension)) {
        cb(null, true);
    } else {
        cb(new Error(`Invalid file type. Allowed: ${ALLOWED_FILE_TYPES[fileType].join(', ')}`), false);
    }
};

const upload = multer({
    storage: storage,
    limits: {
        fileSize: MAX_FILE_SIZE
    },
    fileFilter: fileFilter
});

class FileController {
    /**
     * Upload image file
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async uploadImage(req, res) {
        try {
            // Check authentication
            if (!req.user) {
                return ResponseHelper.unauthorized(res, 'Authentication required');
            }

            // Check if file was uploaded
            if (!req.file) {
                return ResponseHelper.validationError(res, [{ msg: 'No image file provided' }]);
            }

            const file = req.file;
            const user = req.user;

            // Save file info to database
            const fileId = await QueryBuilder.insert('uploaded_files', {
                filename: file.filename,
                original_name: file.originalname,
                file_path: file.path,
                file_size: file.size,
                file_type: 'image',
                uploaded_by: user.id,
                created_at: new Date()
            });

            const fileUrl = `${APP_URL}/uploads/images/${file.filename}`;

            // Log activity
            await logActivity(user.id, 'file_upload', `Uploaded image: ${file.originalname}`, req.ip);

            return ResponseHelper.success(res, {
                id: fileId,
                filename: file.filename,
                url: fileUrl,
                size: file.size
            }, 'Image uploaded successfully');

        } catch (error) {
            await logError('FileController.uploadImage', error.message, req.user?.id, req.ip);
            
            // Delete uploaded file if database save failed
            if (req.file && req.file.path) {
                try {
                    await fs.unlink(req.file.path);
                } catch (unlinkError) {
                    console.error('Failed to delete uploaded file:', unlinkError);
                }
            }

            return ResponseHelper.serverError(res, 'Failed to upload image');
        }
    }

    /**
     * Upload document file
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async uploadDocument(req, res) {
        try {
            // Check authentication
            if (!req.user) {
                return ResponseHelper.unauthorized(res, 'Authentication required');
            }

            // Check if file was uploaded
            if (!req.file) {
                return ResponseHelper.validationError(res, [{ msg: 'No document file provided' }]);
            }

            const file = req.file;
            const user = req.user;

            // Save file info to database
            const fileId = await QueryBuilder.insert('uploaded_files', {
                filename: file.filename,
                original_name: file.originalname,
                file_path: file.path,
                file_size: file.size,
                file_type: 'document',
                uploaded_by: user.id,
                created_at: new Date()
            });

            const fileUrl = `${APP_URL}/uploads/documents/${file.filename}`;

            // Log activity
            await logActivity(user.id, 'file_upload', `Uploaded document: ${file.originalname}`, req.ip);

            return ResponseHelper.success(res, {
                id: fileId,
                filename: file.filename,
                url: fileUrl,
                size: file.size
            }, 'Document uploaded successfully');

        } catch (error) {
            await logError('FileController.uploadDocument', error.message, req.user?.id, req.ip);
            
            // Delete uploaded file if database save failed
            if (req.file && req.file.path) {
                try {
                    await fs.unlink(req.file.path);
                } catch (unlinkError) {
                    console.error('Failed to delete uploaded file:', unlinkError);
                }
            }

            return ResponseHelper.serverError(res, 'Failed to upload document');
        }
    }

    /**
     * Delete file
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async deleteFile(req, res) {
        try {
            const { filename } = req.params;
            const user = req.user;

            // Check authentication
            if (!user) {
                return ResponseHelper.unauthorized(res, 'Authentication required');
            }

            // Check admin/moderator access
            if (!['admin', 'moderator'].includes(user.role)) {
                return ResponseHelper.forbidden(res, 'Admin access required');
            }

            // Get file info from database
            const fileInfo = await QueryBuilder.findOne('uploaded_files', { filename });

            if (!fileInfo) {
                return ResponseHelper.notFound(res, 'File not found');
            }

            // Delete physical file
            try {
                await fs.access(fileInfo.file_path);
                await fs.unlink(fileInfo.file_path);
            } catch (fileError) {
                console.warn('Physical file not found or already deleted:', fileInfo.file_path);
            }

            // Delete from database
            await QueryBuilder.delete('uploaded_files', { filename });

            // Log activity
            await logActivity(user.id, 'file_delete', `Deleted file: ${fileInfo.original_name}`, req.ip);

            return ResponseHelper.success(res, null, 'File deleted successfully');

        } catch (error) {
            await logError('FileController.deleteFile', error.message, req.user?.id, req.ip);
            return ResponseHelper.serverError(res, 'Failed to delete file');
        }
    }

    /**
     * Get uploaded files list
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async getFiles(req, res) {
        try {
            const user = req.user;
            const { page = 1, per_page = 20, file_type, search } = req.query;

            // Check authentication
            if (!user) {
                return res.status(401).json({
                    success: false,
                    message: 'Authentication required'
                });
            }

            // Check admin/moderator access
            if (!['admin', 'moderator'].includes(user.role)) {
                return res.status(403).json({
                    success: false,
                    message: 'Admin access required'
                });
            }

            const connection = await pool.getConnection();
            
            try {
                let whereClause = 'WHERE 1=1';
                let queryParams = [];

                if (file_type) {
                    whereClause += ' AND file_type = ?';
                    queryParams.push(file_type);
                }

                if (search) {
                    whereClause += ' AND (original_name LIKE ? OR filename LIKE ?)';
                    queryParams.push(`%${search}%`, `%${search}%`);
                }

                // Get total count
                const [countResult] = await connection.execute(
                    `SELECT COUNT(*) as total FROM uploaded_files ${whereClause}`,
                    queryParams
                );
                const total = countResult[0].total;

                // Calculate pagination
                const limit = Math.min(parseInt(per_page), 100);
                const offset = (parseInt(page) - 1) * limit;

                // Get files
                const [files] = await connection.execute(`
                    SELECT 
                        uf.*,
                        u.first_name,
                        u.last_name,
                        u.email
                    FROM uploaded_files uf
                    LEFT JOIN users u ON uf.uploaded_by = u.id
                    ${whereClause}
                    ORDER BY uf.created_at DESC
                    LIMIT ${limit} OFFSET ${offset}
                `, [...queryParams]);

                // Add file URLs
                const filesWithUrls = files.map(file => ({
                    ...file,
                    url: `${APP_URL}/uploads/${file.file_type}s/${file.filename}`,
                    uploader: file.first_name && file.last_name ? 
                        `${file.first_name} ${file.last_name}` : file.email
                }));

                res.status(200).json({
                    success: true,
                    message: 'Files retrieved successfully',
                    data: {
                        files: filesWithUrls,
                        pagination: {
                            current_page: parseInt(page),
                            per_page: limit,
                            total: total,
                            total_pages: Math.ceil(total / limit)
                        }
                    }
                });

            } finally {
                connection.release();
            }

        } catch (error) {
            await logError('FileController.getFiles', error.message, req.user?.id, req.ip);
            
            res.status(500).json({
                success: false,
                message: 'Failed to retrieve files'
            });
        }
    }

    /**
     * Get file details
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async getFileDetails(req, res) {
        try {
            const { filename } = req.params;
            const user = req.user;

            // Check authentication
            if (!user) {
                return res.status(401).json({
                    success: false,
                    message: 'Authentication required'
                });
            }

            const connection = await pool.getConnection();
            
            try {
                // Get file info from database
                const [rows] = await connection.execute(`
                    SELECT 
                        uf.*,
                        u.first_name,
                        u.last_name,
                        u.email
                    FROM uploaded_files uf
                    LEFT JOIN users u ON uf.uploaded_by = u.id
                    WHERE uf.filename = ?
                `, [filename]);

                if (rows.length === 0) {
                    return res.status(404).json({
                        success: false,
                        message: 'File not found'
                    });
                }

                const fileInfo = rows[0];

                // Check access permissions
                if (!['admin', 'moderator'].includes(user.role) && fileInfo.uploaded_by !== user.id) {
                    return res.status(403).json({
                        success: false,
                        message: 'Access denied'
                    });
                }

                const fileDetails = {
                    ...fileInfo,
                    url: `${APP_URL}/uploads/${fileInfo.file_type}s/${fileInfo.filename}`,
                    uploader: fileInfo.first_name && fileInfo.last_name ? 
                        `${fileInfo.first_name} ${fileInfo.last_name}` : fileInfo.email
                };

                res.status(200).json({
                    success: true,
                    message: 'File details retrieved successfully',
                    data: fileDetails
                });

            } finally {
                connection.release();
            }

        } catch (error) {
            await logError('FileController.getFileDetails', error.message, req.user?.id, req.ip);
            
            res.status(500).json({
                success: false,
                message: 'Failed to retrieve file details'
            });
        }
    }
}

// Export multer upload middleware along with controller
module.exports = {
    FileController,
    uploadImage: upload.single('image'),
    uploadDocument: upload.single('document')
};