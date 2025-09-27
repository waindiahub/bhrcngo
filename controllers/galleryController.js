/**
 * Gallery Controller - Node.js/Express
 * BHRC India - Human Rights Commission Web Application
 * Handles gallery management API endpoints
 */

const { validationResult } = require('express-validator');
const db = require('../config/database');
const { logActivity, logError } = require('../middleware/auth');
const ResponseHelper = require('../utils/responseHelper');
const QueryBuilder = require('../utils/queryBuilder');

class GalleryController {
    constructor() {
        this.db = db;
    }

    /**
     * Get all gallery items with filtering and pagination
     * Public access
     */
    async getGalleryItems(req, res) {
        try {
            const {
                page = 1,
                per_page = 20,
                category = ''
            } = req.query;

            // Build query with QueryBuilder
            let query = QueryBuilder.select('id, title, description, image_url, category, created_at')
                .from('gallery');

            // Apply filters
            if (category) {
                query = query.where('category', '=', category);
            }

            // Apply ordering and pagination
            query = query.orderBy('created_at', 'DESC')
                .paginate(parseInt(page), parseInt(per_page));

            const result = await query.execute();

            return ResponseHelper.paginated(res, result.data, result.meta, 'Gallery items retrieved successfully');

        } catch (error) {
            logError('Get gallery items error', error, req.user?.id);
            return ResponseHelper.serverError(res, 'Failed to fetch gallery items');
        }
    }

    /**
     * Get single gallery item by ID
     * Public access
     */
    async getGalleryItem(req, res) {
        try {
            const { id } = req.params;

            if (!id || isNaN(id)) {
                return ResponseHelper.validationError(res, 'Valid gallery item ID is required');
            }

            const item = await QueryBuilder.findById('gallery', id);

            if (!item) {
                return ResponseHelper.notFound(res, 'Gallery item not found');
            }

            return ResponseHelper.success(res, item, 'Gallery item retrieved successfully');

        } catch (error) {
            logError('Get gallery item error', error, req.user?.id);
            return ResponseHelper.serverError(res, 'Failed to fetch gallery item');
        }
    }

    /**
     * Create new gallery item
     * Admin/Moderator/Volunteer access required
     */
    async createGalleryItem(req, res) {
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
                description,
                image_url,
                category = 'general'
            } = req.body;

            const query = `
                INSERT INTO gallery (title, description, image_url, category, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            `;

            const result = await this.db.query(query, [
                title,
                description,
                image_url,
                category
            ]);

            // Log activity
            await logActivity(req.user.id, 'gallery_created', `Created gallery item: ${title}`, {
                gallery_id: result.insertId,
                title: title,
                category: category
            });

            res.status(201).json({
                success: true,
                message: 'Gallery item created successfully',
                data: { id: result.insertId }
            });

        } catch (error) {
            logError('Create gallery item error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to create gallery item'
            });
        }
    }

    /**
     * Update existing gallery item
     * Admin/Moderator/Volunteer access required
     */
    async updateGalleryItem(req, res) {
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
                description,
                image_url,
                category = 'general'
            } = req.body;

            if (!id || isNaN(id)) {
                return res.status(400).json({
                    success: false,
                    message: 'Valid gallery item ID is required'
                });
            }

            // Check if item exists
            const checkQuery = 'SELECT id, title FROM gallery WHERE id = ?';
            const [existingItem] = await this.db.query(checkQuery, [id]);

            if (!existingItem) {
                return res.status(404).json({
                    success: false,
                    message: 'Gallery item not found'
                });
            }

            const updateQuery = `
                UPDATE gallery 
                SET title = ?, description = ?, image_url = ?, category = ? 
                WHERE id = ?
            `;

            const result = await this.db.query(updateQuery, [
                title,
                description,
                image_url,
                category,
                id
            ]);

            if (result.affectedRows === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'Gallery item not found'
                });
            }

            // Log activity
            await logActivity(req.user.id, 'gallery_updated', `Updated gallery item: ${title}`, {
                gallery_id: id,
                title: title,
                category: category,
                previous_title: existingItem.title
            });

            res.json({
                success: true,
                message: 'Gallery item updated successfully'
            });

        } catch (error) {
            logError('Update gallery item error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to update gallery item'
            });
        }
    }

    /**
     * Delete gallery item
     * Admin/Moderator access required
     */
    async deleteGalleryItem(req, res) {
        try {
            const { id } = req.params;

            if (!id || isNaN(id)) {
                return res.status(400).json({
                    success: false,
                    message: 'Valid gallery item ID is required'
                });
            }

            // Check if item exists and get details for logging
            const checkQuery = 'SELECT id, title FROM gallery WHERE id = ?';
            const [existingItem] = await this.db.query(checkQuery, [id]);

            if (!existingItem) {
                return res.status(404).json({
                    success: false,
                    message: 'Gallery item not found'
                });
            }

            const deleteQuery = 'DELETE FROM gallery WHERE id = ?';
            const result = await this.db.query(deleteQuery, [id]);

            if (result.affectedRows === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'Gallery item not found'
                });
            }

            // Log activity
            await logActivity(req.user.id, 'gallery_deleted', `Deleted gallery item: ${existingItem.title}`, {
                gallery_id: id,
                title: existingItem.title
            });

            res.json({
                success: true,
                message: 'Gallery item deleted successfully'
            });

        } catch (error) {
            logError('Delete gallery item error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to delete gallery item'
            });
        }
    }

    /**
     * Get gallery categories
     * Public access
     */
    async getGalleryCategories(req, res) {
        try {
            const query = `
                SELECT category, COUNT(*) as count 
                FROM gallery 
                GROUP BY category 
                ORDER BY count DESC
            `;

            const categories = await this.db.query(query);

            res.json({
                success: true,
                message: 'Gallery categories retrieved successfully',
                data: categories
            });

        } catch (error) {
            logError('Get gallery categories error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch gallery categories'
            });
        }
    }

    /**
     * Get gallery statistics
     * Admin/Moderator access required
     */
    async getGalleryStats(req, res) {
        try {
            const statsQuery = `
                SELECT 
                    COUNT(*) as total_items,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_items,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week_items,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as month_items
                FROM gallery
            `;

            const [stats] = await this.db.query(statsQuery);

            // Get category breakdown
            const categoryQuery = `
                SELECT category, COUNT(*) as count 
                FROM gallery 
                GROUP BY category 
                ORDER BY count DESC
            `;

            const categories = await this.db.query(categoryQuery);

            // Get recent items
            const recentQuery = `
                SELECT id, title, category, created_at 
                FROM gallery 
                ORDER BY created_at DESC 
                LIMIT 10
            `;

            const recentItems = await this.db.query(recentQuery);

            res.json({
                success: true,
                message: 'Gallery statistics retrieved successfully',
                data: {
                    overview: stats,
                    categories: categories,
                    recent_items: recentItems
                }
            });

        } catch (error) {
            logError('Get gallery stats error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch gallery statistics'
            });
        }
    }

    /**
     * Bulk delete gallery items
     * Admin/Moderator access required
     */
    async bulkDeleteGalleryItems(req, res) {
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

            const { item_ids } = req.body;

            if (!Array.isArray(item_ids) || item_ids.length === 0) {
                return res.status(400).json({
                    success: false,
                    message: 'Item IDs array is required'
                });
            }

            // Get items details for logging
            const placeholders = item_ids.map(() => '?').join(',');
            const checkQuery = `SELECT id, title FROM gallery WHERE id IN (${placeholders})`;
            const existingItems = await this.db.query(checkQuery, item_ids);

            if (existingItems.length === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'No gallery items found'
                });
            }

            // Delete items
            const deleteQuery = `DELETE FROM gallery WHERE id IN (${placeholders})`;
            const result = await this.db.query(deleteQuery, item_ids);

            // Log activity
            await logActivity(req.user.id, 'gallery_bulk_deleted', `Bulk deleted ${result.affectedRows} gallery items`, {
                deleted_count: result.affectedRows,
                item_ids: item_ids,
                items: existingItems.map(item => ({ id: item.id, title: item.title }))
            });

            res.json({
                success: true,
                message: `Successfully deleted ${result.affectedRows} gallery items`,
                data: {
                    deleted_count: result.affectedRows
                }
            });

        } catch (error) {
            logError('Bulk delete gallery items error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to delete gallery items'
            });
        }
    }
}

module.exports = new GalleryController();