/**
 * Certificate Controller - Node.js/Express
 * BHRC India - Human Rights Commission Web Application
 * Handles certificate management API endpoints
 */

const { validationResult } = require('express-validator');
const db = require('../config/database');
const { logActivity, logError } = require('../middleware/auth');
const ResponseHelper = require('../utils/responseHelper');
const QueryBuilder = require('../utils/queryBuilder');

class CertificateController {
    constructor() {
        this.db = db;
    }

    /**
     * Get user certificates with pagination
     * Authenticated access - Admin/Moderator can see all, users see their own
     */
    async getCertificates(req, res) {
        try {
            const {
                page = 1,
                per_page = 10,
                user_id = null
            } = req.query;

            // Build query with role-based filtering
            let query = QueryBuilder.select('certificates c', 'c.*, u.first_name, u.last_name, u.email')
                .join('users u', 'c.user_id = u.id', 'LEFT');

            // Admin/Moderator can see all certificates, others see their own
            if (!['admin', 'moderator'].includes(req.user.role)) {
                query = query.where('c.user_id', req.user.id);
            } else if (user_id) {
                // Admin/Moderator filtering by specific user
                query = query.where('c.user_id', user_id);
            }

            // Apply ordering and pagination
            query = query.orderBy('c.created_at', 'DESC');
            const result = await query.paginate(parseInt(page), parseInt(per_page));

            return ResponseHelper.paginated(res, result.data, result.pagination, 'Certificates retrieved successfully');

        } catch (error) {
            logError('Get certificates error', error, req.user?.id);
            return ResponseHelper.serverError(res, 'Failed to fetch certificates');
        }
    }

    /**
     * Get single certificate by ID
     * Authenticated access - Admin/Moderator can see all, users see their own
     */
    async getCertificate(req, res) {
        try {
            const { id } = req.params;

            if (!id || isNaN(id)) {
                return ResponseHelper.validationError(res, [{ msg: 'Valid certificate ID is required' }]);
            }

            // Build query with role-based filtering
            let query = QueryBuilder.select('certificates c', 'c.*, u.first_name, u.last_name, u.email')
                .join('users u', 'c.user_id = u.id', 'LEFT')
                .where('c.id', id);

            // Non-admin users can only see their own certificates
            if (!['admin', 'moderator'].includes(req.user.role)) {
                query = query.where('c.user_id', req.user.id);
            }

            const certificate = await query.first();

            if (!certificate) {
                return ResponseHelper.notFound(res, 'Certificate not found');
            }

            return ResponseHelper.success(res, certificate, 'Certificate retrieved successfully');

        } catch (error) {
            logError('Get certificate error', error, req.user?.id);
            return ResponseHelper.serverError(res, 'Failed to fetch certificate');
        }
    }

    /**
     * Create new certificate
     * Admin/Moderator access required
     */
    async createCertificate(req, res) {
        try {
            // Check validation errors
            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return ResponseHelper.validationError(res, errors.array());
            }

            const {
                user_id,
                title,
                description,
                issue_date = new Date().toISOString().split('T')[0]
            } = req.body;

            // Validate user exists
            const user = await QueryBuilder.findById('users', user_id, 'id, first_name, last_name');
            if (!user) {
                return ResponseHelper.validationError(res, [{ msg: 'User not found' }]);
            }

            // Generate unique certificate number
            const certificateNumber = await this.generateCertificateNumber();

            // Insert certificate
            const certificateId = await QueryBuilder.insert('certificates', {
                user_id,
                certificate_number: certificateNumber,
                title,
                description,
                issue_date,
                created_at: new Date()
            });

            // Log activity
            await logActivity(req.user.id, 'certificate_created', `Created certificate: ${title} for ${user.first_name} ${user.last_name}`, {
                certificate_id: certificateId,
                certificate_number: certificateNumber,
                user_id: user_id,
                title: title
            });

            return ResponseHelper.created(res, {
                id: certificateId,
                certificate_number: certificateNumber
            }, 'Certificate created successfully');

        } catch (error) {
            logError('Create certificate error', error, req.user?.id);
            return ResponseHelper.serverError(res, 'Failed to create certificate');
        }
    }

    /**
     * Update existing certificate
     * Admin/Moderator access required
     */
    async updateCertificate(req, res) {
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
                issue_date
            } = req.body;

            if (!id || isNaN(id)) {
                return res.status(400).json({
                    success: false,
                    message: 'Valid certificate ID is required'
                });
            }

            // Check if certificate exists
            const [existingCertificate] = await this.db.query(
                'SELECT id, title, user_id FROM certificates WHERE id = ?', 
                [id]
            );

            if (!existingCertificate) {
                return res.status(404).json({
                    success: false,
                    message: 'Certificate not found'
                });
            }

            const updateQuery = `
                UPDATE certificates 
                SET title = ?, description = ?, issue_date = ?
                WHERE id = ?
            `;

            const result = await this.db.query(updateQuery, [
                title,
                description,
                issue_date,
                id
            ]);

            if (result.affectedRows === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'Certificate not found'
                });
            }

            // Log activity
            await logActivity(req.user.id, 'certificate_updated', `Updated certificate: ${title}`, {
                certificate_id: id,
                title: title,
                previous_title: existingCertificate.title,
                user_id: existingCertificate.user_id
            });

            res.json({
                success: true,
                message: 'Certificate updated successfully'
            });

        } catch (error) {
            logError('Update certificate error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to update certificate'
            });
        }
    }

    /**
     * Delete certificate
     * Admin access required
     */
    async deleteCertificate(req, res) {
        try {
            const { id } = req.params;

            if (!id || isNaN(id)) {
                return res.status(400).json({
                    success: false,
                    message: 'Valid certificate ID is required'
                });
            }

            // Check if certificate exists and get details for logging
            const [existingCertificate] = await this.db.query(
                'SELECT id, title, certificate_number, user_id FROM certificates WHERE id = ?', 
                [id]
            );

            if (!existingCertificate) {
                return res.status(404).json({
                    success: false,
                    message: 'Certificate not found'
                });
            }

            const deleteQuery = 'DELETE FROM certificates WHERE id = ?';
            const result = await this.db.query(deleteQuery, [id]);

            if (result.affectedRows === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'Certificate not found'
                });
            }

            // Log activity
            await logActivity(req.user.id, 'certificate_deleted', `Deleted certificate: ${existingCertificate.title}`, {
                certificate_id: id,
                certificate_number: existingCertificate.certificate_number,
                title: existingCertificate.title,
                user_id: existingCertificate.user_id
            });

            res.json({
                success: true,
                message: 'Certificate deleted successfully'
            });

        } catch (error) {
            logError('Delete certificate error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to delete certificate'
            });
        }
    }

    /**
     * Get certificate statistics
     * Admin/Moderator access required
     */
    async getCertificateStats(req, res) {
        try {
            const statsQuery = `
                SELECT 
                    COUNT(*) as total_certificates,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_certificates,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week_certificates,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as month_certificates
                FROM certificates
            `;

            const [stats] = await this.db.query(statsQuery);

            // Get monthly breakdown
            const monthlyQuery = `
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as count
                FROM certificates
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month DESC
            `;

            const monthlyStats = await this.db.query(monthlyQuery);

            // Get recent certificates
            const recentQuery = `
                SELECT c.id, c.title, c.certificate_number, c.created_at,
                       u.first_name, u.last_name
                FROM certificates c
                LEFT JOIN users u ON c.user_id = u.id
                ORDER BY c.created_at DESC
                LIMIT 10
            `;

            const recentCertificates = await this.db.query(recentQuery);

            res.json({
                success: true,
                message: 'Certificate statistics retrieved successfully',
                data: {
                    overview: stats,
                    monthly_breakdown: monthlyStats,
                    recent_certificates: recentCertificates
                }
            });

        } catch (error) {
            logError('Get certificate stats error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch certificate statistics'
            });
        }
    }

    /**
     * Generate unique certificate number
     * Private helper method
     */
    async generateCertificateNumber() {
        const year = new Date().getFullYear();
        let isUnique = false;
        let certificateNumber;

        while (!isUnique) {
            const randomNum = Math.floor(Math.random() * 9999) + 1;
            certificateNumber = `BHRC-${year}-${randomNum.toString().padStart(4, '0')}`;

            // Check if this number already exists
            const [existing] = await this.db.query(
                'SELECT id FROM certificates WHERE certificate_number = ?',
                [certificateNumber]
            );

            if (!existing) {
                isUnique = true;
            }
        }

        return certificateNumber;
    }

    /**
     * Get member certificates
     */
    async getMemberCertificates(req, res) {
        try {
            const userId = req.user.id;
            const { page = 1, limit = 10 } = req.query;

            const limitValue = parseInt(limit);
            const offsetValue = (page - 1) * limit;
            
            const query = `
                SELECT c.*, u.first_name, u.last_name
                FROM certificates c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.user_id = ?
                ORDER BY c.created_at DESC
                LIMIT ${limitValue} OFFSET ${offsetValue}
            `;

            const certificates = await this.db.query(query, [userId]);

            // Get total count
            const countQuery = 'SELECT COUNT(*) as total FROM certificates WHERE user_id = ?';
            const [countResult] = await this.db.query(countQuery, [userId]);
            const total = countResult.total;

            res.json({
                success: true,
                data: certificates,
                pagination: {
                    current_page: parseInt(page),
                    per_page: parseInt(limit),
                    total: total,
                    total_pages: Math.ceil(total / limit)
                }
            });

        } catch (error) {
            logError('Get member certificates error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch certificates'
            });
        }
    }

    /**
     * Download certificate for member
     */
    async downloadCertificate(req, res) {
        try {
            const { certificateId } = req.params;
            const userId = req.user.id;

            // Check if certificate belongs to user
            const query = `
                SELECT c.*, u.first_name, u.last_name
                FROM certificates c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.id = ? AND c.user_id = ?
            `;

            const [certificate] = await this.db.query(query, [certificateId, userId]);

            if (!certificate) {
                return res.status(404).json({
                    success: false,
                    message: 'Certificate not found'
                });
            }

            // Generate certificate content
            const certificateContent = `
CERTIFICATE

Certificate Number: ${certificate.certificate_number}
Issued To: ${certificate.first_name} ${certificate.last_name}
Title: ${certificate.title}
Description: ${certificate.description}
Issue Date: ${certificate.issue_date}

Bharatiya Human Rights Commission India
            `;

            res.setHeader('Content-Type', 'text/plain');
            res.setHeader('Content-Disposition', `attachment; filename="certificate-${certificate.certificate_number}.txt"`);
            res.send(certificateContent);

        } catch (error) {
            logError('Download certificate error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to download certificate'
            });
        }
    }

    /**
     * Get member certificate statistics
     */
    async getMemberCertificateStats(req, res) {
        try {
            const userId = req.user.id;

            const statsQuery = `
                SELECT 
                    COUNT(*) as total_certificates,
                    SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as this_year_certificates
                FROM certificates
                WHERE user_id = ?
            `;

            const [stats] = await this.db.query(statsQuery, [userId]);

            res.json({
                success: true,
                data: stats
            });

        } catch (error) {
            logError('Get member certificate stats error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch certificate statistics'
            });
        }
    }

    /**
     * Get member certificate years
     */
    async getMemberCertificateYears(req, res) {
        try {
            const userId = req.user.id;

            const query = `
                SELECT DISTINCT YEAR(created_at) as year
                FROM certificates
                WHERE user_id = ?
                ORDER BY year DESC
            `;

            const years = await this.db.query(query, [userId]);

            res.json({
                success: true,
                data: years.map(row => row.year)
            });

        } catch (error) {
            logError('Get member certificate years error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch certificate years'
            });
        }
    }

    /**
     * Bulk delete certificates
     * Admin access required
     */
    async bulkDeleteCertificates(req, res) {
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

            const { certificate_ids } = req.body;

            if (!Array.isArray(certificate_ids) || certificate_ids.length === 0) {
                return res.status(400).json({
                    success: false,
                    message: 'Certificate IDs array is required'
                });
            }

            // Get certificates details for logging
            const placeholders = certificate_ids.map(() => '?').join(',');
            const checkQuery = `SELECT id, title, certificate_number FROM certificates WHERE id IN (${placeholders})`;
            const existingCertificates = await this.db.query(checkQuery, certificate_ids);

            if (existingCertificates.length === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'No certificates found'
                });
            }

            // Delete certificates
            const deleteQuery = `DELETE FROM certificates WHERE id IN (${placeholders})`;
            const result = await this.db.query(deleteQuery, certificate_ids);

            // Log activity
            await logActivity(req.user.id, 'certificates_bulk_deleted', `Bulk deleted ${result.affectedRows} certificates`, {
                deleted_count: result.affectedRows,
                certificate_ids: certificate_ids,
                certificates: existingCertificates.map(cert => ({ 
                    id: cert.id, 
                    title: cert.title, 
                    certificate_number: cert.certificate_number 
                }))
            });

            res.json({
                success: true,
                message: `Successfully deleted ${result.affectedRows} certificates`,
                data: {
                    deleted_count: result.affectedRows
                }
            });

        } catch (error) {
            logError('Bulk delete certificates error', error, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to delete certificates'
            });
        }
    }
}

module.exports = new CertificateController();