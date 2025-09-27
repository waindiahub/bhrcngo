/**
 * Authentication Middleware for BHRC India Application
 * 
 * Handles JWT token verification, user authentication,
 * and role-based access control for protected routes.
 */

const jwt = require('jsonwebtoken');
const db = require('../config/database');

// JWT Secret - In production, this should be in environment variables
const JWT_SECRET = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJiaHJjX2luZGlhIiwiYXVkIjoiYmhyY191c2VycyIsImlhdCI6MTc1ODkwMzIwMSwibmJmIjoxNzU4OTAzMjAxLCJleHAiOjE3NTg5MDY4MDEsImRhdGEiOnsidXNlcl9pZCI6MSwidXNlcm5hbWUiOiJhZG1pbiIsInJvbGUiOiJzdXBlcl9hZG1pbiJ9fQ.HFz1eSZsRH3IX_on9D-HQuZu86Prbg05dlh1NTV8xaQ';
const JWT_EXPIRES_IN = '24h';
const JWT_REFRESH_EXPIRES_IN = '7d';

/**
 * Authentication middleware class
 */
class AuthMiddleware {
    /**
     * Generate JWT token for user
     * @param {Object} user - User object
     * @returns {string} JWT token
     */
    static generateToken(user) {
        const payload = {
            id: user.id,
            email: user.email,
            username: user.username,
            role: user.role,
            status: user.status
        };

        return jwt.sign(payload, JWT_SECRET, {
            expiresIn: JWT_EXPIRES_IN,
            issuer: 'bhrc-india',
            audience: 'bhrc-users'
        });
    }

    /**
     * Generate refresh token
     * @param {Object} user - User object
     * @returns {string} Refresh token
     */
    static generateRefreshToken(user) {
        const payload = {
            id: user.id,
            email: user.email,
            type: 'refresh'
        };

        return jwt.sign(payload, JWT_SECRET, {
            expiresIn: JWT_REFRESH_EXPIRES_IN,
            issuer: 'bhrc-india',
            audience: 'bhrc-users'
        });
    }

    /**
     * Verify JWT token
     * @param {string} token - JWT token
     * @returns {Object} Decoded token payload
     */
    static verifyToken(token) {
        try {
            return jwt.verify(token, JWT_SECRET, {
                issuer: 'bhrc-india',
                audience: 'bhrc-users'
            });
        } catch (error) {
            throw new Error('Invalid or expired token');
        }
    }

    /**
     * Middleware to authenticate requests
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     * @param {Function} next - Next middleware function
     */
    static async authenticate(req, res, next) {
        try {
            // Get token from Authorization header
            const authHeader = req.headers.authorization;
            
            if (!authHeader || !authHeader.startsWith('Bearer ')) {
                return res.status(401).json({
                    success: false,
                    message: 'Access token required'
                });
            }

            const token = authHeader.substring(7); // Remove 'Bearer ' prefix

            // Verify token
            const decoded = AuthMiddleware.verifyToken(token);

            // Get user from database to ensure they still exist and are active
            const user = await db.queryOne(
                'SELECT id, username, email, first_name, last_name, role, status, email_verified FROM users WHERE id = ?',
                [decoded.id]
            );

            if (!user) {
                return res.status(401).json({
                    success: false,
                    message: 'User not found'
                });
            }

            if (user.status !== 'active') {
                return res.status(403).json({
                    success: false,
                    message: 'Account is not active'
                });
            }

            // Add user to request object
            req.user = user;
            next();

        } catch (error) {
            console.error('Authentication error:', error.message);
            
            if (error.name === 'TokenExpiredError') {
                return res.status(401).json({
                    success: false,
                    message: 'Token expired'
                });
            }

            return res.status(401).json({
                success: false,
                message: 'Invalid token'
            });
        }
    }

    /**
     * Middleware to check user roles
     * @param {Array} allowedRoles - Array of allowed roles
     * @returns {Function} Middleware function
     */
    static authorize(allowedRoles = []) {
        return (req, res, next) => {
            if (!req.user) {
                return res.status(401).json({
                    success: false,
                    message: 'Authentication required'
                });
            }

            if (allowedRoles.length === 0) {
                return next(); // No role restriction
            }

            if (!allowedRoles.includes(req.user.role)) {
                return res.status(403).json({
                    success: false,
                    message: 'Insufficient permissions'
                });
            }

            next();
        };
    }

    /**
     * Optional authentication middleware (doesn't fail if no token)
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     * @param {Function} next - Next middleware function
     */
    static async optionalAuth(req, res, next) {
        try {
            const authHeader = req.headers.authorization;
            
            if (!authHeader || !authHeader.startsWith('Bearer ')) {
                req.user = null;
                return next();
            }

            const token = authHeader.substring(7);
            const decoded = AuthMiddleware.verifyToken(token);

            const user = await db.queryOne(
                'SELECT id, username, email, first_name, last_name, role, status FROM users WHERE id = ?',
                [decoded.id]
            );

            req.user = user && user.status === 'active' ? user : null;
            next();

        } catch (error) {
            req.user = null;
            next();
        }
    }

    /**
     * Hash password using bcrypt
     * @param {string} password - Plain text password
     * @returns {Promise<string>} Hashed password
     */
    static async hashPassword(password) {
        const bcrypt = require('bcryptjs');
        const saltRounds = 12;
        return await bcrypt.hash(password, saltRounds);
    }

    /**
     * Verify password against hash
     * @param {string} password - Plain text password
     * @param {string} hash - Hashed password
     * @returns {Promise<boolean>} Password match result
     */
    static async verifyPassword(password, hash) {
        const bcrypt = require('bcryptjs');
        return await bcrypt.compare(password, hash);
    }

    /**
     * Log user activity
     * @param {number} userId - User ID
     * @param {string} action - Action performed
     * @param {string} details - Additional details
     * @param {string} ipAddress - User IP address
     */
    static async logActivity(userId, action, details = null, ipAddress = null) {
        try {
            // Skip logging if userId is null or invalid
            if (!userId || userId === null || userId === undefined) {
                return;
            }

            // Use direct SQL insert to avoid potential issues with db.insert method
            const sql = 'INSERT INTO user_activities (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?)';
            await db.query(sql, [userId, action, details, ipAddress, new Date()]);
        } catch (error) {
            // Silently ignore foreign key constraint errors for activity logging
            if (error.message && error.message.includes('foreign key constraint fails')) {
                console.warn(`Skipping activity log for invalid user ID: ${userId}`);
                return;
            }
            console.error('Error logging user activity:', error.message);
            // Don't throw error as this is not critical
        }
    }

    /**
     * Validate request using express-validator
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     * @param {Function} next - Express next function
     */
    static validateRequest(req, res, next) {
        const { validationResult } = require('express-validator');
        const errors = validationResult(req);
        
        if (!errors.isEmpty()) {
            return res.status(400).json({
                success: false,
                message: 'Validation failed',
                errors: errors.array()
            });
        }
        
        next();
    }

    /**
     * Generate password reset token
     * @param {Object} user - User object
     * @returns {string} JWT token
     */
    static generateResetToken(user) {
        const payload = {
            id: user.id,
            email: user.email,
            type: 'password_reset',
            timestamp: Date.now()
        };

        return jwt.sign(payload, JWT_SECRET, {
            expiresIn: '1h', // Reset tokens expire in 1 hour
            issuer: 'bhrc-india',
            audience: 'bhrc-users'
        });
    }

    /**
     * Generate email verification token
     * @param {Object} user - User object
     * @returns {string} Verification token
     */
    static generateVerificationToken(user) {
        const payload = {
            id: user.id,
            email: user.email,
            type: 'email_verification'
        };

        return jwt.sign(payload, JWT_SECRET, {
            expiresIn: '24h', // Verification tokens expire in 24 hours
            issuer: 'bhrc-india',
            audience: 'bhrc-users'
        });
    }
}

// Export individual functions for convenience
const authenticateToken = AuthMiddleware.authenticate;
const authorizeRoles = AuthMiddleware.authorize;
const requireRole = AuthMiddleware.authorize;
const optionalAuth = AuthMiddleware.optionalAuth;
const validateRequest = AuthMiddleware.validateRequest;
const logActivity = AuthMiddleware.logActivity;
const logError = async (message) => {
    console.error(`[ERROR] ${new Date().toISOString()}: ${message}`);
};

module.exports = AuthMiddleware;
module.exports.authenticateToken = authenticateToken;
module.exports.authorizeRoles = authorizeRoles;
module.exports.requireRole = requireRole;
module.exports.optionalAuth = optionalAuth;
module.exports.validateRequest = validateRequest;
module.exports.logActivity = logActivity;
module.exports.logError = logError;
module.exports.logError = logError;