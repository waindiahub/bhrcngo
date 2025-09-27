/**
 * Public Routes
 * Defines public API endpoints that don't require authentication
 * BHRC India - Human Rights Commission Web Application
 */

const express = require('express');
const router = express.Router();
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

/**
 * @route GET /api/public/stats
 * @desc Get public statistics (no authentication required)
 * @access Public
 */
router.get('/stats', async (req, res) => {
    let connection;
    try {
        connection = await mysql.createConnection(dbConfig);
        
        // Get basic public statistics
        const [userStats] = await connection.execute(
            'SELECT COUNT(*) as total_users FROM users WHERE status = "active"'
        );
        
        const [eventStats] = await connection.execute(
            'SELECT COUNT(*) as total_events FROM events WHERE status = "published"'
        );
        
        const [newsStats] = await connection.execute(
            'SELECT COUNT(*) as total_news FROM news WHERE status = "published"'
        );
        
        const [complaintStats] = await connection.execute(
            'SELECT COUNT(*) as total_complaints FROM complaints WHERE status != "draft"'
        );
        
        res.json({
            success: true,
            data: {
                total_users: userStats[0].total_users,
                total_events: eventStats[0].total_events,
                total_news: newsStats[0].total_news,
                total_complaints: complaintStats[0].total_complaints
            }
        });
        
    } catch (error) {
        console.error('Error fetching public stats:', error);
        res.status(500).json({
            success: false,
            message: 'Error fetching public statistics'
        });
    } finally {
        if (connection) {
            await connection.end();
        }
    }
});

/**
 * @route GET /api/public/events
 * @desc Get published events (no authentication required)
 * @access Public
 */
router.get('/events', async (req, res) => {
    let connection;
    try {
        const limit = parseInt(req.query.limit) || 10;
        const offset = parseInt(req.query.offset) || 0;
        
        connection = await mysql.createConnection(dbConfig);
        
        const [events] = await connection.execute(
            `SELECT id, title, description, event_date, location, image_url, created_at 
             FROM events 
             WHERE status = 'published' AND event_date >= CURDATE()
             ORDER BY event_date ASC 
             LIMIT ? OFFSET ?`,
            [limit, offset]
        );
        
        res.json({
            success: true,
            data: events
        });
        
    } catch (error) {
        console.error('Error fetching public events:', error);
        res.status(500).json({
            success: false,
            message: 'Error fetching events'
        });
    } finally {
        if (connection) {
            await connection.end();
        }
    }
});

/**
 * @route GET /api/public/news
 * @desc Get published news (no authentication required)
 * @access Public
 */
router.get('/news', async (req, res) => {
    let connection;
    try {
        const limit = parseInt(req.query.limit) || 10;
        const offset = parseInt(req.query.offset) || 0;
        
        connection = await mysql.createConnection(dbConfig);
        
        const [news] = await connection.execute(
            `SELECT id, title, content, image_url, created_at, updated_at
             FROM news 
             WHERE status = 'published'
             ORDER BY created_at DESC 
             LIMIT ? OFFSET ?`,
            [limit, offset]
        );
        
        res.json({
            success: true,
            data: news
        });
        
    } catch (error) {
        console.error('Error fetching public news:', error);
        res.status(500).json({
            success: false,
            message: 'Error fetching news'
        });
    } finally {
        if (connection) {
            await connection.end();
        }
    }
});

/**
 * @route GET /api/public/contact-info
 * @desc Get contact information (no authentication required)
 * @access Public
 */
router.get('/contact-info', async (req, res) => {
    let connection;
    try {
        connection = await mysql.createConnection(dbConfig);
        
        const [settings] = await connection.execute(
            `SELECT setting_key, setting_value 
             FROM settings 
             WHERE setting_key IN ('contact_email', 'contact_phone', 'contact_address', 'office_hours')`
        );
        
        const contactInfo = {};
        settings.forEach(setting => {
            contactInfo[setting.setting_key] = setting.setting_value;
        });
        
        res.json({
            success: true,
            data: contactInfo
        });
        
    } catch (error) {
        console.error('Error fetching contact info:', error);
        res.status(500).json({
            success: false,
            message: 'Error fetching contact information'
        });
    } finally {
        if (connection) {
            await connection.end();
        }
    }
});

module.exports = router;