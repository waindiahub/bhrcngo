/**
 * Logger Middleware
 * Provides logging functionality for activities and errors
 * BHRC India - Human Rights Commission Web Application
 */

const fs = require('fs');
const path = require('path');
const mysql = require('mysql2/promise');

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

// Create logs directory if it doesn't exist
const logsDir = path.join(__dirname, '../logs');
if (!fs.existsSync(logsDir)) {
    fs.mkdirSync(logsDir, { recursive: true });
}

/**
 * Log user activity to database
 * @param {number} userId - User ID performing the action
 * @param {string} action - Action performed
 * @param {string} description - Additional description about the action
 * @param {string} ipAddress - IP address of the user
 */
async function logActivity(userId, action, description = null, ipAddress = null) {
    let connection;
    try {
        connection = await mysql.createConnection(dbConfig);
        
        const query = `
            INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
            VALUES (?, ?, ?, ?, NOW())
        `;
        
        await connection.execute(query, [userId, action, description, ipAddress]);
        
    } catch (error) {
        console.error('Error logging activity:', error);
    } finally {
        if (connection) {
            await connection.end();
        }
    }
}

/**
 * Log error to file and console
 * @param {Error} error - Error object
 * @param {string} context - Context where error occurred
 * @param {number} userId - User ID (optional)
 */
async function logError(error, context = 'Unknown', userId = null) {
    try {
        // Log to console
        console.error(`[${context}] Error:`, error);
        
        // Create error log entry
        const timestamp = new Date().toISOString();
        const logEntry = {
            timestamp,
            userId,
            context,
            message: error.message || 'Unknown error',
            stack: error.stack || null
        };
        
        // Write to file
        const logFile = path.join(logsDir, `error-${new Date().toISOString().split('T')[0]}.log`);
        const logLine = `${timestamp} [${context}] ${userId ? `User:${userId}` : 'System'} - ${error.message}\n${error.stack || ''}\n\n`;
        
        fs.appendFileSync(logFile, logLine);
        
    } catch (logError) {
        console.error('Error writing to log file:', logError);
    }
}

/**
 * Express middleware for request logging
 */
function requestLogger(req, res, next) {
    const start = Date.now();
    
    // Log request
    console.log(`${new Date().toISOString()} - ${req.method} ${req.originalUrl} - IP: ${req.ip}`);
    
    // Override res.end to log response
    const originalEnd = res.end;
    res.end = function(...args) {
        const duration = Date.now() - start;
        console.log(`${new Date().toISOString()} - ${req.method} ${req.originalUrl} - ${res.statusCode} - ${duration}ms`);
        originalEnd.apply(this, args);
    };
    
    next();
}

module.exports = {
    logActivity,
    logError,
    requestLogger
};