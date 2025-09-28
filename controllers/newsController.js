/**
 * News Controller - Node.js/Express
 * BHRC India - Human Rights Commission Web Application
 * Handles news/articles management API endpoints
 */

const { validationResult } = require('express-validator');
const db = require('../config/database');
const { logActivity, logError } = require('../middleware/auth');

class NewsController {
    constructor() {
        this.db = db;
    }

    /**
     * Get all news articles with filtering and pagination
     * Public access - returns published articles by default
     */
    async getNews(req, res) {
        try {
            const {
                page = 1,
                per_page = 10,
                category = '',
                status = 'published'
            } = req.query;

            const pageNum = parseInt(page);
            const perPage = parseInt(per_page);
            const offset = (pageNum - 1) * perPage;

            // Build WHERE clause
            let whereConditions = ['status = ?'];
            let params = [status];

            if (category) {
                whereConditions.push('category = ?');
                params.push(category);
            }

            const whereClause = 'WHERE ' + whereConditions.join(' AND ');

            // Get total count
            const countQuery = `SELECT COUNT(*) as total FROM news_articles ${whereClause}`;
            const countResult = await this.db.query(countQuery, params);
            const total = countResult[0]?.total || 0;

            // Get news articles
            const articlesQuery = `
                SELECT id, title, slug, excerpt, content, featured_image, category, status, 
                       created_at, updated_at, author_id, published_at, views_count
                FROM news_articles 
                ${whereClause} 
                ORDER BY created_at DESC 
                LIMIT ${perPage} OFFSET ${offset}
            `;

            const articles = await this.db.query(articlesQuery, params);

            const response = {
                data: articles,
                meta: {
                    current_page: pageNum,
                    per_page: perPage,
                    total: total,
                    last_page: Math.ceil(total / perPage)
                }
            };

            res.json({
                success: true,
                message: 'News articles retrieved successfully',
                data: response
            });

        } catch (error) {
            logError('Get news error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch news articles'
            });
        }
    }

    /**
     * Get single news article by ID
     * Public access
     */
    async getNewsItem(req, res) {
        try {
            const { id } = req.params;

            if (!id || isNaN(id)) {
                return res.status(400).json({
                    success: false,
                    message: 'Valid news article ID is required'
                });
            }

            const query = 'SELECT * FROM news_articles WHERE id = ?';
            const articleResult = await this.db.query(query, [id]);
            const article = articleResult[0];

            if (!article) {
                return res.status(404).json({
                    success: false,
                    message: 'News article not found'
                });
            }

            res.json({
                success: true,
                message: 'News article retrieved successfully',
                data: article
            });

        } catch (error) {
            logError('Get news item error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch news article'
            });
        }
    }

    /**
     * Create new news article
     * Admin/Moderator access required
     */
    async createNews(req, res) {
        try {
            // Check validation errors
            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    message: 'Validation failed',
                    errors: errors.array()
                });
            }

            const {
                title,
                excerpt,
                content,
                featured_image = '',
                category = 'general',
                status = 'draft'
            } = req.body;

            // Generate slug from title
            const slug = title.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');

            const query = `
                INSERT INTO news_articles (title, slug, excerpt, content, featured_image, category, status, author_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            `;

            const result = await this.db.query(query, [
                title,
                slug,
                excerpt,
                content,
                featured_image,
                category,
                status,
                req.user.id
            ]);

            // Log activity
            await logActivity(req.user.id, 'news_created', `Created news article: ${title}`, {
                news_id: result.insertId,
                title: title,
                category: category,
                status: status
            });

            res.status(201).json({
                success: true,
                message: 'News article created successfully',
                data: { id: result.insertId }
            });

        } catch (error) {
            logError('Create news error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to create news article'
            });
        }
    }

    /**
     * Update existing news article
     * Admin/Moderator access required
     */
    async updateNews(req, res) {
        try {
            // Check validation errors
            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return res.status(400).json({
                    success: false,
                    message: 'Validation failed',
                    errors: errors.array()
                });
            }

            const { id } = req.params;
            const {
                title,
                excerpt,
                content,
                featured_image = '',
                category = 'general',
                status = 'draft'
            } = req.body;

            if (!id || isNaN(id)) {
                return res.status(400).json({
                    success: false,
                    message: 'Valid news article ID is required'
                });
            }

            // Check if article exists
            const checkQuery = 'SELECT id, title FROM news_articles WHERE id = ?';
            const existingResult = await this.db.query(checkQuery, [id]);
            const existingArticle = existingResult[0];

            if (!existingArticle) {
                return res.status(404).json({
                    success: false,
                    message: 'News article not found'
                });
            }

            // Generate slug from title if title is being updated
            let slug = null;
            if (title) {
                slug = title.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .trim('-');
            }

            const updateQuery = `
                UPDATE news_articles 
                SET title = ?, slug = COALESCE(?, slug), excerpt = ?, content = ?, featured_image = ?, 
                    category = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            `;

            const result = await this.db.query(updateQuery, [
                title,
                slug,
                excerpt,
                content,
                featured_image,
                category,
                status,
                id
            ]);

            if (result.affectedRows === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'News article not found'
                });
            }

            // Log activity
            await logActivity(req.user.id, 'news_updated', `Updated news article: ${title}`, {
                news_id: id,
                title: title,
                category: category,
                status: status,
                previous_title: existingArticle.title
            });

            res.json({
                success: true,
                message: 'News article updated successfully'
            });

        } catch (error) {
            logError('Update news error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to update news article'
            });
        }
    }

    /**
     * Delete news article
     * Admin/Moderator access required
     */
    async deleteNews(req, res) {
        try {
            const { id } = req.params;

            if (!id || isNaN(id)) {
                return res.status(400).json({
                    success: false,
                    message: 'Valid news article ID is required'
                });
            }

            // Check if article exists and get details for logging
            const checkQuery = 'SELECT id, title FROM news_articles WHERE id = ?';
            const existingResult = await this.db.query(checkQuery, [id]);
            const existingArticle = existingResult[0];

            if (!existingArticle) {
                return res.status(404).json({
                    success: false,
                    message: 'News article not found'
                });
            }

            const deleteQuery = 'DELETE FROM news_articles WHERE id = ?';
            const result = await this.db.query(deleteQuery, [id]);

            if (result.affectedRows === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'News article not found'
                });
            }

            // Log activity
            await logActivity(req.user.id, 'news_deleted', `Deleted news article: ${existingArticle.title}`, {
                news_id: id,
                title: existingArticle.title
            });

            res.json({
                success: true,
                message: 'News article deleted successfully'
            });

        } catch (error) {
            logError('Delete news error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to delete news article'
            });
        }
    }

    /**
     * Get news categories
     * Public access
     */
    async getNewsCategories(req, res) {
        try {
            const query = `
                SELECT category, COUNT(*) as count 
                FROM news_articles 
                WHERE status = 'published' 
                GROUP BY category 
                ORDER BY count DESC
            `;

            const categories = await this.db.query(query);

            res.json({
                success: true,
                message: 'News categories retrieved successfully',
                data: categories
            });

        } catch (error) {
            logError('Get news categories error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch news categories'
            });
        }
    }

    /**
     * Get all news articles for admin (including drafts)
     * Admin/Moderator access required
     */
    async getAdminNews(req, res) {
        try {
            const {
                page = 1,
                per_page = 20,
                category = '',
                status = '',
                search = '',
                sortBy = 'created_at',
                sortOrder = 'DESC'
            } = req.query;

            const pageNum = parseInt(page);
            const perPage = parseInt(per_page);
            const offset = (pageNum - 1) * perPage;

            // Build WHERE clause
            let whereConditions = [];
            let params = [];

            if (status) {
                whereConditions.push('na.status = ?');
                params.push(status);
            }

            if (category) {
                whereConditions.push('na.category = ?');
                params.push(category);
            }

            if (search) {
                whereConditions.push('(na.title LIKE ? OR na.excerpt LIKE ? OR na.content LIKE ?)');
                const searchTerm = `%${search}%`;
                params.push(searchTerm, searchTerm, searchTerm);
            }

            const whereClause = whereConditions.length > 0 ? 'WHERE ' + whereConditions.join(' AND ') : '';

            // Get total count
            const countQuery = `SELECT COUNT(*) as total FROM news_articles na ${whereClause}`;
            const countResult = await this.db.query(countQuery, params);
            const total = countResult[0]?.total || 0;

            // Get news articles with author information
            const articlesQuery = `
                SELECT na.id, na.title, na.slug, na.excerpt, na.content, na.featured_image, 
                       na.category, na.status, na.created_at, na.updated_at, na.author_id, 
                       na.published_at, na.views_count,
                       u.first_name, u.last_name, u.username
                FROM news_articles na
                LEFT JOIN users u ON na.author_id = u.id
                ${whereClause} 
                ORDER BY na.${sortBy} ${sortOrder} 
                LIMIT ${perPage} OFFSET ${offset}
            `;

            const articles = await this.db.query(articlesQuery, params);

            // Format articles with author name
            const formattedArticles = articles.map(article => ({
                ...article,
                author_name: article.first_name && article.last_name 
                    ? `${article.first_name} ${article.last_name}` 
                    : article.username || 'Unknown'
            }));

            const response = {
                data: formattedArticles,
                meta: {
                    current_page: pageNum,
                    per_page: perPage,
                    total: total,
                    last_page: Math.ceil(total / perPage)
                }
            };

            res.json({
                success: true,
                message: 'Articles retrieved successfully',
                data: response
            });

        } catch (error) {
            logError('Get admin news error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch articles'
            });
        }
    }

    /**
     * Get news statistics
     * Admin/Moderator access required
     */
    async getNewsStats(req, res) {
        try {
            const statsQuery = `
                SELECT 
                    COUNT(*) as total_articles,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_articles,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_articles,
                    SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_articles,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_articles,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week_articles,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as month_articles
                FROM news_articles
            `;

            const statsResult = await this.db.query(statsQuery);
            const stats = statsResult[0] || {};

            // Get category breakdown
            const categoryQuery = `
                SELECT category, COUNT(*) as count 
                FROM news_articles 
                GROUP BY category 
                ORDER BY count DESC
            `;

            const categories = await this.db.query(categoryQuery);

            // Get recent articles
            const recentQuery = `
                SELECT id, title, slug, category, status, created_at, published_at, views_count
                FROM news_articles 
                ORDER BY created_at DESC 
                LIMIT 10
            `;

            const recentArticles = await this.db.query(recentQuery);

            res.json({
                success: true,
                message: 'News statistics retrieved successfully',
                data: {
                    overview: stats,
                    categories: categories,
                    recent_articles: recentArticles
                }
            });

        } catch (error) {
            logError('Get news stats error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch news statistics'
            });
        }
    }
}

const newsController = new NewsController();

// Bind methods to ensure 'this' context is preserved
const boundMethods = {};
Object.getOwnPropertyNames(NewsController.prototype).forEach(method => {
    if (method !== 'constructor' && typeof newsController[method] === 'function') {
        boundMethods[method] = newsController[method].bind(newsController);
    }
});

module.exports = boundMethods;