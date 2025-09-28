/**
 * Public Routes
 * Defines public API endpoints that don't require authentication
 * BHRC India - Human Rights Commission Web Application
 */

const express = require('express');
const router = express.Router();
const db = require('../config/database');

/**
 * @route GET /api/public/stats
 * @desc Get public statistics (no authentication required)
 * @access Public
 */
router.get('/stats', async (req, res) => {
    try {
        // Get basic public statistics
        const userStats = await db.query(
            'SELECT COUNT(*) as total_users FROM users WHERE status = "active"'
        );
        
        const eventStats = await db.query(
            'SELECT COUNT(*) as total_events FROM events WHERE status = "published"'
        );
        
        const newsStats = await db.query(
            'SELECT COUNT(*) as total_news FROM news_articles WHERE status = "published"'
        );
        
        // Try to get complaints stats, but don't fail if table doesn't exist
        let complaintStats = { total_complaints: 0 };
        try {
            const complaintResult = await db.query(
                'SELECT COUNT(*) as total_complaints FROM complaints WHERE status != "draft"'
            );
            complaintStats = complaintResult[0];
        } catch (error) {
            console.log('Complaints table not found, skipping complaints stats');
        }
        
        res.json({
            success: true,
            data: {
                total_users: userStats[0].total_users,
                total_events: eventStats[0].total_events,
                total_news: newsStats[0].total_news,
                total_complaints: complaintStats.total_complaints
            }
        });
        
    } catch (error) {
        console.error('Error fetching public stats:', error);
        res.status(500).json({
            success: false,
            message: 'Error fetching public statistics'
        });
    }
});

/**
 * @route GET /api/public/events
 * @desc Get published events (no authentication required)
 * @access Public
 */
router.get('/events', async (req, res) => {
    try {
        const limit = parseInt(req.query.limit) || 10;
        const offset = parseInt(req.query.offset) || 0;
        
        const events = await db.query(
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
    }
});

/**
 * @route GET /api/public/news
 * @desc Get published news (no authentication required)
 * @access Public
 */
router.get('/news', async (req, res) => {
    try {
        const limit = parseInt(req.query.limit) || 10;
        const offset = parseInt(req.query.offset) || 0;
        
        const news = await db.query(
            `SELECT id, title, slug, excerpt, content, featured_image, category, created_at, updated_at
             FROM news_articles 
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
    }
});

/**
 * @route GET /api/public/news/:id
 * @desc Get single published article by ID (no authentication required)
 * @access Public
 */
router.get('/news/:id', async (req, res) => {
    try {
        const { id } = req.params;
        
        if (!id || isNaN(id)) {
            return res.status(400).json({
                success: false,
                message: 'Valid article ID is required'
            });
        }
        
        const articles = await db.query(
            `SELECT id, title, slug, excerpt, content, featured_image, category, 
                    created_at, updated_at, views_count
             FROM news_articles 
             WHERE id = ? AND status = 'published'`,
            [id]
        );
        
        if (articles.length === 0) {
            return res.status(404).json({
                success: false,
                message: 'Article not found'
            });
        }
        
        // Increment view count
        await db.query(
            'UPDATE news_articles SET views_count = views_count + 1 WHERE id = ?',
            [id]
        );
        
        res.json({
            success: true,
            data: articles[0]
        });
        
    } catch (error) {
        console.error('Error fetching article:', error);
        res.status(500).json({
            success: false,
            message: 'Error fetching article'
        });
    }
});

/**
 * @route GET /api/public/contact-info
 * @desc Get contact information (no authentication required)
 * @access Public
 */
router.get('/contact-info', async (req, res) => {
    try {
        const settings = await db.query(
            `SELECT setting_key, setting_value 
             FROM system_settings 
             WHERE setting_key IN ('contact_email', 'contact_phone', 'address', 'office_hours')`
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
    }
});

module.exports = router;