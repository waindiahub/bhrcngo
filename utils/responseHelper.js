/**
 * Response Helper Utility for BHRC India Application
 * 
 * Provides standardized response formats for consistent API responses
 * across all endpoints, including success, error, and pagination responses.
 */

/**
 * Standard API Response Structure
 * {
 *   success: boolean,
 *   message: string,
 *   data: any,
 *   meta?: object,
 *   errors?: array,
 *   timestamp: string
 * }
 */

class ResponseHelper {
    /**
     * Send success response
     * @param {Object} res - Express response object
     * @param {any} data - Response data
     * @param {string} message - Success message
     * @param {number} statusCode - HTTP status code (default: 200)
     * @param {Object} meta - Additional metadata
     */
    static success(res, data = null, message = 'Success', statusCode = 200, meta = null) {
        const response = {
            success: true,
            message,
            data,
            timestamp: new Date().toISOString()
        };

        if (meta) {
            response.meta = meta;
        }

        return res.status(statusCode).json(response);
    }

    /**
     * Send error response
     * @param {Object} res - Express response object
     * @param {string} message - Error message
     * @param {number} statusCode - HTTP status code (default: 400)
     * @param {array} errors - Validation errors array
     * @param {any} data - Additional error data
     */
    static error(res, message = 'An error occurred', statusCode = 400, errors = null, data = null) {
        const response = {
            success: false,
            message,
            timestamp: new Date().toISOString()
        };

        if (errors) {
            response.errors = errors;
        }

        if (data) {
            response.data = data;
        }

        return res.status(statusCode).json(response);
    }

    /**
     * Send validation error response
     * @param {Object} res - Express response object
     * @param {array} errors - Validation errors array
     * @param {string} message - Error message
     */
    static validationError(res, errors, message = 'Validation failed') {
        return ResponseHelper.error(res, message, 422, errors);
    }

    /**
     * Send unauthorized response
     * @param {Object} res - Express response object
     * @param {string} message - Error message
     */
    static unauthorized(res, message = 'Unauthorized access') {
        return ResponseHelper.error(res, message, 401);
    }

    /**
     * Send forbidden response
     * @param {Object} res - Express response object
     * @param {string} message - Error message
     */
    static forbidden(res, message = 'Access forbidden') {
        return ResponseHelper.error(res, message, 403);
    }

    /**
     * Send not found response
     * @param {Object} res - Express response object
     * @param {string} message - Error message
     */
    static notFound(res, message = 'Resource not found') {
        return ResponseHelper.error(res, message, 404);
    }

    /**
     * Send server error response
     * @param {Object} res - Express response object
     * @param {string} message - Error message
     * @param {any} error - Error details (for development)
     */
    static serverError(res, message = 'Internal server error', error = null) {
        const response = {
            success: false,
            message,
            timestamp: new Date().toISOString()
        };

        // Include error details in development environment
        if (process.env.NODE_ENV === 'development' && error) {
            response.error = {
                message: error.message,
                stack: error.stack
            };
        }

        return res.status(500).json(response);
    }

    /**
     * Send paginated response
     * @param {Object} res - Express response object
     * @param {array} data - Response data array
     * @param {Object} pagination - Pagination metadata
     * @param {string} message - Success message
     */
    static paginated(res, data, pagination, message = 'Data retrieved successfully') {
        const meta = {
            pagination: {
                current_page: pagination.page,
                per_page: pagination.perPage,
                total: pagination.total,
                total_pages: pagination.totalPages,
                has_next_page: pagination.page < pagination.totalPages,
                has_prev_page: pagination.page > 1
            }
        };

        return ResponseHelper.success(res, data, message, 200, meta);
    }

    /**
     * Send created response
     * @param {Object} res - Express response object
     * @param {any} data - Created resource data
     * @param {string} message - Success message
     */
    static created(res, data, message = 'Resource created successfully') {
        return ResponseHelper.success(res, data, message, 201);
    }

    /**
     * Send updated response
     * @param {Object} res - Express response object
     * @param {any} data - Updated resource data
     * @param {string} message - Success message
     */
    static updated(res, data, message = 'Resource updated successfully') {
        return ResponseHelper.success(res, data, message, 200);
    }

    /**
     * Send deleted response
     * @param {Object} res - Express response object
     * @param {string} message - Success message
     */
    static deleted(res, message = 'Resource deleted successfully') {
        return ResponseHelper.success(res, null, message, 200);
    }

    /**
     * Send no content response
     * @param {Object} res - Express response object
     */
    static noContent(res) {
        return res.status(204).send();
    }

    /**
     * Handle async controller errors
     * @param {Function} fn - Async controller function
     * @returns {Function} Express middleware function
     */
    static asyncHandler(fn) {
        return (req, res, next) => {
            Promise.resolve(fn(req, res, next)).catch(next);
        };
    }

    /**
     * Create pagination metadata
     * @param {number} page - Current page
     * @param {number} perPage - Items per page
     * @param {number} total - Total items count
     * @returns {Object} Pagination metadata
     */
    static createPagination(page, perPage, total) {
        const totalPages = Math.ceil(total / perPage);
        
        return {
            page: parseInt(page),
            perPage: parseInt(perPage),
            total: parseInt(total),
            totalPages,
            offset: (page - 1) * perPage
        };
    }

    /**
     * Format validation errors from express-validator
     * @param {Object} errors - Validation errors from express-validator
     * @returns {array} Formatted errors array
     */
    static formatValidationErrors(errors) {
        return errors.array().map(error => ({
            field: error.path || error.param,
            message: error.msg,
            value: error.value
        }));
    }

    /**
     * Send file download response
     * @param {Object} res - Express response object
     * @param {string} filePath - File path
     * @param {string} fileName - Download file name
     */
    static download(res, filePath, fileName) {
        return res.download(filePath, fileName, (err) => {
            if (err) {
                ResponseHelper.serverError(res, 'File download failed', err);
            }
        });
    }

    /**
     * Send CSV export response
     * @param {Object} res - Express response object
     * @param {string} csvData - CSV data string
     * @param {string} fileName - CSV file name
     */
    static csv(res, csvData, fileName = 'export.csv') {
        res.setHeader('Content-Type', 'text/csv');
        res.setHeader('Content-Disposition', `attachment; filename="${fileName}"`);
        return res.send(csvData);
    }

    /**
     * Send Excel export response
     * @param {Object} res - Express response object
     * @param {Buffer} excelBuffer - Excel file buffer
     * @param {string} fileName - Excel file name
     */
    static excel(res, excelBuffer, fileName = 'export.xlsx') {
        res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        res.setHeader('Content-Disposition', `attachment; filename="${fileName}"`);
        return res.send(excelBuffer);
    }
}

module.exports = ResponseHelper;