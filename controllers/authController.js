/**
 * Authentication Controller for BHRC India Application
 * 
 * Handles user authentication, registration, login, logout,
 * JWT token management, and password operations.
 */

const db = require('../config/database');
const AuthMiddleware = require('../middleware/auth');
const { body, validationResult } = require('express-validator');
const crypto = require('crypto');
const nodemailer = require('nodemailer');

/**
 * Authentication Controller Class
 */
class AuthController {
    /**
     * User login
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async login(req, res) {
        try {
            // Validation rules
            await body('email')
                .isEmail()
                .normalizeEmail()
                .withMessage('Valid email is required')
                .run(req);
            
            await body('password')
                .isLength({ min: 6 })
                .withMessage('Password must be at least 6 characters')
                .run(req);

            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    message: 'Validation failed',
                    errors: errors.array()
                });
            }

            const { email, password } = req.body;

            // Find user by email
            const user = await db.queryOne(
                `SELECT id, username, email, password_hash, first_name, last_name, 
                        role, status, email_verified, created_at 
                 FROM users WHERE email = ?`,
                [email]
            );

            if (!user) {
                return res.status(401).json({
                    success: false,
                    message: 'Invalid credentials'
                });
            }

            // Check user status
            if (user.status !== 'active') {
                const statusMessages = {
                    'pending': 'Account is pending approval',
                    'inactive': 'Account is inactive',
                    'suspended': 'Account has been suspended'
                };

                return res.status(403).json({
                    success: false,
                    message: statusMessages[user.status] || 'Account access denied'
                });
            }

            // Verify password
            const isPasswordValid = await AuthMiddleware.verifyPassword(password, user.password_hash);
            if (!isPasswordValid) {
                return res.status(401).json({
                    success: false,
                    message: 'Invalid credentials'
                });
            }

            // Check if email is verified
            if (!user.email_verified) {
                return res.status(403).json({
                    success: false,
                    message: 'Please verify your email address before logging in'
                });
            }

            // Generate tokens
            const token = AuthMiddleware.generateToken(user);
            const refreshToken = AuthMiddleware.generateRefreshToken(user);

            // Update last login
            await db.update('users', 
                { last_login: new Date() }, 
                'id = ?', 
                [user.id]
            );

            // Log login activity
            await AuthMiddleware.logActivity(
                user.id, 
                'login', 
                'User logged in successfully',
                req.ip
            );

            // Remove sensitive data
            delete user.password_hash;

            res.json({
                success: true,
                message: 'Login successful',
                data: {
                    user: user,
                    token: token,
                    refreshToken: refreshToken,
                    expiresIn: '24h'
                }
            });

        } catch (error) {
            console.error('Login error:', error);
            res.status(500).json({
                success: false,
                message: 'Internal server error'
            });
        }
    }

    /**
     * User registration
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async register(req, res) {
        try {
            // Validation rules
            await body('username')
                .isLength({ min: 3, max: 50 })
                .matches(/^[a-zA-Z0-9_]+$/)
                .withMessage('Username must be 3-50 characters and contain only letters, numbers, and underscores')
                .run(req);

            await body('email')
                .isEmail()
                .normalizeEmail()
                .withMessage('Valid email is required')
                .run(req);

            await body('password')
                .isLength({ min: 8 })
                .matches(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/)
                .withMessage('Password must be at least 8 characters with uppercase, lowercase, and number')
                .run(req);

            await body('first_name')
                .isLength({ min: 2, max: 50 })
                .matches(/^[a-zA-Z\s]+$/)
                .withMessage('First name must be 2-50 characters and contain only letters')
                .run(req);

            await body('last_name')
                .isLength({ min: 2, max: 50 })
                .matches(/^[a-zA-Z\s]+$/)
                .withMessage('Last name must be 2-50 characters and contain only letters')
                .run(req);

            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    message: 'Validation failed',
                    errors: errors.array()
                });
            }

            const { username, email, password, first_name, last_name, phone } = req.body;

            // Check if user already exists
            const existingUser = await db.queryOne(
                'SELECT id FROM users WHERE email = ? OR username = ?',
                [email, username]
            );

            if (existingUser) {
                return res.status(409).json({
                    success: false,
                    message: 'User with this email or username already exists'
                });
            }

            // Hash password
            const passwordHash = await AuthMiddleware.hashPassword(password);

            // Generate verification token
            const verificationToken = crypto.randomBytes(32).toString('hex');

            // Insert new user
            const userId = await db.insert('users', {
                username: username,
                email: email,
                password_hash: passwordHash,
                first_name: first_name,
                last_name: last_name,
                phone: phone || null,
                role: 'member',
                status: 'pending',
                email_verified: false,
                email_verification_token: verificationToken,
                created_at: new Date(),
                updated_at: new Date()
            });

            // Send verification email (implement email service)
            // await this.sendVerificationEmail(email, verificationToken);

            // Log registration activity
            await AuthMiddleware.logActivity(
                userId, 
                'register', 
                'User registered successfully',
                req.ip
            );

            res.status(201).json({
                success: true,
                message: 'Registration successful. Please check your email for verification.',
                data: {
                    user_id: userId,
                    email: email,
                    status: 'pending'
                }
            });

        } catch (error) {
            console.error('Registration error:', error);
            res.status(500).json({
                success: false,
                message: 'Internal server error'
            });
        }
    }

    /**
     * User logout
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async logout(req, res) {
        try {
            // Log logout activity
            await AuthMiddleware.logActivity(
                req.user.id, 
                'logout', 
                'User logged out successfully',
                req.ip
            );

            res.json({
                success: true,
                message: 'Logout successful'
            });

        } catch (error) {
            console.error('Logout error:', error);
            res.status(500).json({
                success: false,
                message: 'Internal server error'
            });
        }
    }

    /**
     * Get current user
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async getCurrentUser(req, res) {
        try {
            const user = await db.queryOne(
                `SELECT id, username, email, first_name, last_name, phone, 
                        role, status, email_verified, created_at, last_login 
                 FROM users WHERE id = ?`,
                [req.user.id]
            );

            if (!user) {
                return res.status(404).json({
                    success: false,
                    message: 'User not found'
                });
            }

            res.json({
                success: true,
                data: { user: user }
            });

        } catch (error) {
            console.error('Get current user error:', error);
            res.status(500).json({
                success: false,
                message: 'Internal server error'
            });
        }
    }

    /**
     * Refresh JWT token
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async refreshToken(req, res) {
        try {
            const { refreshToken } = req.body;

            if (!refreshToken) {
                return res.status(400).json({
                    success: false,
                    message: 'Refresh token is required'
                });
            }

            // Verify refresh token
            const decoded = AuthMiddleware.verifyToken(refreshToken);

            if (decoded.type !== 'refresh') {
                return res.status(401).json({
                    success: false,
                    message: 'Invalid refresh token'
                });
            }

            // Get user
            const user = await db.queryOne(
                'SELECT id, username, email, role, status FROM users WHERE id = ?',
                [decoded.id]
            );

            if (!user || user.status !== 'active') {
                return res.status(401).json({
                    success: false,
                    message: 'User not found or inactive'
                });
            }

            // Generate new tokens
            const newToken = AuthMiddleware.generateToken(user);
            const newRefreshToken = AuthMiddleware.generateRefreshToken(user);

            res.json({
                success: true,
                data: {
                    token: newToken,
                    refreshToken: newRefreshToken,
                    expiresIn: '24h'
                }
            });

        } catch (error) {
            console.error('Refresh token error:', error);
            res.status(401).json({
                success: false,
                message: 'Invalid or expired refresh token'
            });
        }
    }

    /**
     * Forgot password
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async forgotPassword(req, res) {
        try {
            await body('email')
                .isEmail()
                .normalizeEmail()
                .withMessage('Valid email is required')
                .run(req);

            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    message: 'Valid email is required'
                });
            }

            const { email } = req.body;

            // Find user
            const user = await db.queryOne(
                'SELECT id, email, first_name FROM users WHERE email = ?',
                [email]
            );

            if (!user) {
                // Don't reveal if email exists or not
                return res.json({
                    success: true,
                    message: 'If the email exists, a password reset link has been sent.'
                });
            }

            // Generate reset token
            const resetToken = AuthMiddleware.generateResetToken(user);

            // Store reset token in database
            await db.update('users', 
                { 
                    password_reset_token: resetToken,
                    password_reset_expires: new Date(Date.now() + 3600000) // 1 hour
                }, 
                'id = ?', 
                [user.id]
            );

            // Send reset email (implement email service)
            // await this.sendPasswordResetEmail(user.email, resetToken);

            res.json({
                success: true,
                message: 'If the email exists, a password reset link has been sent.'
            });

        } catch (error) {
            console.error('Forgot password error:', error);
            res.status(500).json({
                success: false,
                message: 'Internal server error'
            });
        }
    }

    /**
     * Reset password
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async resetPassword(req, res) {
        try {
            await body('token')
                .notEmpty()
                .withMessage('Reset token is required')
                .run(req);

            await body('password')
                .isLength({ min: 8 })
                .matches(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/)
                .withMessage('Password must be at least 8 characters with uppercase, lowercase, and number')
                .run(req);

            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    message: 'Validation failed',
                    errors: errors.array()
                });
            }

            const { token, password } = req.body;

            // Verify reset token
            const decoded = AuthMiddleware.verifyToken(token);

            if (decoded.type !== 'password_reset') {
                return res.status(400).json({
                    success: false,
                    message: 'Invalid reset token'
                });
            }

            // Find user with valid reset token
            const user = await db.queryOne(
                'SELECT id FROM users WHERE id = ? AND password_reset_expires > NOW()',
                [decoded.id]
            );

            if (!user) {
                return res.status(400).json({
                    success: false,
                    message: 'Invalid or expired reset token'
                });
            }

            // Hash new password
            const passwordHash = await AuthMiddleware.hashPassword(password);

            // Update password and clear reset token
            await db.update('users', 
                { 
                    password_hash: passwordHash,
                    password_reset_token: null,
                    password_reset_expires: null,
                    updated_at: new Date()
                }, 
                'id = ?', 
                [user.id]
            );

            // Log password reset activity
            await AuthMiddleware.logActivity(
                user.id, 
                'password_reset', 
                'Password reset successfully',
                req.ip
            );

            res.json({
                success: true,
                message: 'Password reset successful'
            });

        } catch (error) {
            console.error('Reset password error:', error);
            res.status(500).json({
                success: false,
                message: 'Internal server error'
            });
        }
    }

    /**
     * Verify email
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async verifyEmail(req, res) {
        try {
            const { token } = req.params;

            if (!token) {
                return res.status(400).json({
                    success: false,
                    message: 'Verification token is required'
                });
            }

            // Find user with verification token
            const user = await db.queryOne(
                'SELECT id FROM users WHERE email_verification_token = ?',
                [token]
            );

            if (!user) {
                return res.status(400).json({
                    success: false,
                    message: 'Invalid verification token'
                });
            }

            // Update user as verified
            await db.update('users', 
                { 
                    email_verified: true,
                    email_verification_token: null,
                    status: 'active',
                    updated_at: new Date()
                }, 
                'id = ?', 
                [user.id]
            );

            // Log email verification activity
            await AuthMiddleware.logActivity(
                user.id, 
                'email_verified', 
                'Email verified successfully',
                req.ip
            );

            res.json({
                success: true,
                message: 'Email verified successfully'
            });

        } catch (error) {
            console.error('Email verification error:', error);
            res.status(500).json({
                success: false,
                message: 'Internal server error'
            });
        }
    }

    /**
     * Resend verification email
     * @param {Object} req - Express request object
     * @param {Object} res - Express response object
     */
    static async resendVerification(req, res) {
        try {
            await body('email')
                .isEmail()
                .normalizeEmail()
                .withMessage('Valid email is required')
                .run(req);

            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    message: 'Valid email is required'
                });
            }

            const { email } = req.body;

            // Find user
            const user = await db.queryOne(
                'SELECT id, email_verified FROM users WHERE email = ?',
                [email]
            );

            if (!user) {
                return res.json({
                    success: true,
                    message: 'If the email exists and is not verified, a verification email has been sent.'
                });
            }

            if (user.email_verified) {
                return res.status(400).json({
                    success: false,
                    message: 'Email is already verified'
                });
            }

            // Generate new verification token
            const verificationToken = crypto.randomBytes(32).toString('hex');

            // Update verification token
            await db.update('users', 
                { email_verification_token: verificationToken }, 
                'id = ?', 
                [user.id]
            );

            // Send verification email (implement email service)
            // await this.sendVerificationEmail(email, verificationToken);

            res.json({
                success: true,
                message: 'Verification email sent successfully'
            });

        } catch (error) {
            console.error('Resend verification error:', error);
            res.status(500).json({
                success: false,
                message: 'Internal server error'
            });
        }
    }
}

module.exports = AuthController;