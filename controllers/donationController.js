/**
 * Donation Controller - Node.js/Express
 * Handles all donation-related operations including member donations, admin management, and public donations
 */

const { validationResult } = require('express-validator');
const db = require('../config/database');
const { logActivity } = require('../middleware/auth');
const ResponseHelper = require('../utils/responseHelper');
const QueryBuilder = require('../utils/queryBuilder');

class DonationController {
    constructor() {
        this.db = db;
    }

    /**
     * Get member's own donations
     */
    async getMemberDonations(req, res) {
        try {
            const userId = req.user.id;
            const { status, year, page = 1, limit = 10 } = req.query;

            // Get member's email
            const user = await QueryBuilder.findById('users', userId);
            if (!user) {
                return ResponseHelper.notFound(res, 'User not found');
            }

            const memberEmail = user.email;

            // Build query with filters
            let query = QueryBuilder.select('donations', '*')
                .where('donor_email', memberEmail);

            if (status) {
                query = query.where('status', status);
            }

            if (year) {
                query = query.whereRaw('YEAR(created_at) = ?', [year]);
            }

            // Apply pagination and ordering
            query = query.orderBy('created_at', 'DESC');
            const result = await query.paginate(parseInt(page), parseInt(limit));

            return ResponseHelper.paginated(res, result.data, result.pagination);

        } catch (error) {
            console.error('Error fetching member donations:', error);
            return ResponseHelper.serverError(res, 'Failed to fetch donations');
        }
    }

    /**
     * Get member's donation summary
     */
    async getMemberDonationSummary(req, res) {
        try {
            const userId = req.user.id;

            // Get member's email
            const userQuery = 'SELECT email FROM users WHERE id = ?';
            const [userRows] = await this.db.execute(userQuery, [userId]);
            
            if (userRows.length === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'User not found'
                });
            }

            const memberEmail = userRows[0].email;
            const summary = {};

            // Total donations
            const totalQuery = `
                SELECT COUNT(*) as total_donations, SUM(amount) as total_amount 
                FROM donations 
                WHERE donor_email = ? AND status = 'completed'
            `;
            const [totalRows] = await this.db.execute(totalQuery, [memberEmail]);
            summary.total_donations = totalRows[0].total_donations || 0;
            summary.total_amount = parseFloat(totalRows[0].total_amount) || 0;

            // This year's donations
            const yearlyQuery = `
                SELECT COUNT(*) as yearly_donations, SUM(amount) as yearly_amount 
                FROM donations 
                WHERE donor_email = ? AND status = 'completed' AND YEAR(created_at) = YEAR(CURDATE())
            `;
            const [yearlyRows] = await this.db.execute(yearlyQuery, [memberEmail]);
            summary.yearly_donations = yearlyRows[0].yearly_donations || 0;
            summary.yearly_amount = parseFloat(yearlyRows[0].yearly_amount) || 0;

            // Donations by category
            const categoryQuery = `
                SELECT category, COUNT(*) as count, SUM(amount) as amount 
                FROM donations 
                WHERE donor_email = ? AND status = 'completed'
                GROUP BY category
            `;
            const [categoryRows] = await this.db.execute(categoryQuery, [memberEmail]);
            summary.by_category = categoryRows;

            // Recent donations
            const recentQuery = `
                SELECT * FROM donations 
                WHERE donor_email = ? AND status = 'completed'
                ORDER BY created_at DESC 
                LIMIT 5
            `;
            const [recentRows] = await this.db.execute(recentQuery, [memberEmail]);
            summary.recent_donations = recentRows;

            res.json({
                success: true,
                data: summary
            });

        } catch (error) {
            console.error('Error fetching member donation summary:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch donation summary'
            });
        }
    }

    /**
     * Get years when member made donations
     */
    async getMemberDonationYears(req, res) {
        try {
            const userId = req.user.id;

            // Get member's email
            const userQuery = 'SELECT email FROM users WHERE id = ?';
            const [userRows] = await this.db.execute(userQuery, [userId]);
            
            if (userRows.length === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'User not found'
                });
            }

            const memberEmail = userRows[0].email;

            // Get distinct years
            const query = `
                SELECT DISTINCT YEAR(created_at) as year 
                FROM donations 
                WHERE donor_email = ? AND status = 'completed'
                ORDER BY year DESC
            `;
            const [rows] = await this.db.execute(query, [memberEmail]);
            const years = rows.map(row => row.year);

            res.json({
                success: true,
                data: years
            });

        } catch (error) {
            console.error('Error fetching member donation years:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch donation years'
            });
        }
    }

    /**
     * Generate receipt for member's donation
     */
    async getMemberDonationReceipt(req, res) {
        try {
            const userId = req.user.id;
            const { donationId } = req.params;

            // Get member's email
            const userQuery = 'SELECT email FROM users WHERE id = ?';
            const [userRows] = await this.db.execute(userQuery, [userId]);
            
            if (userRows.length === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'User not found'
                });
            }

            const memberEmail = userRows[0].email;

            // Get donation details (ensure it belongs to the member)
            const query = `
                SELECT * FROM donations 
                WHERE id = ? AND donor_email = ? AND status = 'completed'
            `;
            const [rows] = await this.db.execute(query, [donationId, memberEmail]);

            if (rows.length === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'Donation not found or access denied'
                });
            }

            const donation = rows[0];
            const receiptContent = this.generateReceiptContent(donation);

            // Set headers for text receipt (in production, use PDF library)
            res.setHeader('Content-Type', 'text/plain');
            res.setHeader('Content-Disposition', `attachment; filename="donation-receipt-${donation.reference_number}.txt"`);
            res.send(receiptContent);

        } catch (error) {
            console.error('Error generating member donation receipt:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to generate receipt'
            });
        }
    }

    /**
     * Get all donations (Admin/Moderator only)
     */
    async getAllDonations(req, res) {
        try {
            const {
                search,
                status,
                type,
                category,
                date_from,
                date_to,
                amount_min,
                amount_max,
                sort = 'created_at',
                order = 'DESC',
                page = 1,
                limit = 20
            } = req.query;

            // Build query with filters
            let query = QueryBuilder.select('donations', '*');

            if (search) {
                query = query.whereLike('reference_number', search)
                    .orWhereLike('donor_name', search)
                    .orWhereLike('donor_email', search);
            }

            if (status) {
                query = query.where('status', status);
            }

            if (type) {
                query = query.where('type', type);
            }

            if (category) {
                query = query.where('category', category);
            }

            if (date_from) {
                query = query.whereRaw('DATE(created_at) >= ?', [date_from]);
            }

            if (date_to) {
                query = query.whereRaw('DATE(created_at) <= ?', [date_to]);
            }

            if (amount_min) {
                query = query.where('amount', '>=', amount_min);
            }

            if (amount_max) {
                query = query.where('amount', '<=', amount_max);
            }

            // Validate sort field
            const allowedSortFields = ['created_at', 'amount', 'donor_name', 'status', 'type', 'category'];
            const sortField = allowedSortFields.includes(sort) ? sort : 'created_at';
            const sortOrder = order.toUpperCase() === 'ASC' ? 'ASC' : 'DESC';

            // Apply ordering and pagination
            query = query.orderBy(sortField, sortOrder);
            const result = await query.paginate(parseInt(page), parseInt(limit));

            return ResponseHelper.paginated(res, result.data, result.pagination);

        } catch (error) {
            console.error('Error fetching donations:', error);
            return ResponseHelper.serverError(res, 'Failed to fetch donations');
        }
    }

    /**
     * Get donation details (Admin/Moderator only)
     */
    async getDonationDetails(req, res) {
        try {
            const { donationId } = req.params;

            const donation = await QueryBuilder.findById('donations', donationId);

            if (!donation) {
                return ResponseHelper.notFound(res, 'Donation not found');
            }

            return ResponseHelper.success(res, donation);

        } catch (error) {
            console.error('Error fetching donation details:', error);
            return ResponseHelper.serverError(res, 'Failed to fetch donation details');
        }
    }

    /**
     * Create new donation (Admin/Moderator only)
     */
    async createDonation(req, res) {
        try {
            // Check validation errors
            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return ResponseHelper.validationError(res, errors.array());
            }

            const data = req.body;

            // Generate reference number
            const referenceNumber = this.generateReferenceNumber();

            // Prepare data for insertion
            const insertData = {
                reference_number: referenceNumber,
                amount: data.amount,
                type: data.type,
                category: data.category,
                status: data.status,
                fee_amount: data.fee_amount || 0,
                is_anonymous: data.is_anonymous || false,
                donor_name: data.donor_name || null,
                donor_email: data.donor_email || null,
                donor_phone: data.donor_phone || null,
                donor_address: data.donor_address || null,
                payment_method: data.payment_method,
                payment_status: data.payment_status,
                transaction_id: data.transaction_id || null,
                payment_date: data.payment_date || null,
                next_payment_date: data.next_payment_date || null,
                recurring_count: data.recurring_count || 0,
                recurring_active: data.recurring_active || false,
                notes: data.notes || null,
                admin_notes: data.admin_notes || null,
                processed_at: data.status === 'completed' ? new Date() : null,
                created_at: new Date(),
                updated_at: new Date()
            };

            // Insert donation
            const donationId = await QueryBuilder.insert('donations', insertData);

            // Send confirmation email if not anonymous and email provided
            if (!insertData.is_anonymous && insertData.donor_email) {
                await this.sendDonationConfirmation(donationId);
            }

            // Log activity
            await logActivity(req.user.id, 'donation_created', `Created donation ${referenceNumber}`);

            return ResponseHelper.created(res, { id: donationId, reference_number: referenceNumber }, 'Donation recorded successfully');

        } catch (error) {
            console.error('Error creating donation:', error);
            return ResponseHelper.serverError(res, 'Failed to record donation');
        }
    }

    /**
     * Update donation (Admin/Moderator only)
     */
    async updateDonation(req, res) {
        try {
            const { donationId } = req.params;
            const data = req.body;

            // Check if donation exists
            const checkQuery = 'SELECT * FROM donations WHERE id = ?';
            const [existingRows] = await this.db.execute(checkQuery, [donationId]);

            if (existingRows.length === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'Donation not found'
                });
            }

            const existingDonation = existingRows[0];

            // Prepare update data
            const updateFields = [];
            const updateParams = [];

            const allowedFields = [
                'amount', 'type', 'category', 'status', 'fee_amount', 'is_anonymous',
                'donor_name', 'donor_email', 'donor_phone', 'donor_address',
                'payment_method', 'payment_status', 'transaction_id', 'payment_date',
                'next_payment_date', 'recurring_count', 'recurring_active',
                'notes', 'admin_notes'
            ];

            allowedFields.forEach(field => {
                if (data.hasOwnProperty(field)) {
                    updateFields.push(`${field} = ?`);
                    updateParams.push(data[field]);
                }
            });

            if (updateFields.length === 0) {
                return res.status(400).json({
                    success: false,
                    message: 'No valid fields to update'
                });
            }

            // Add processed_at if status changed to completed
            if (data.status === 'completed' && existingDonation.status !== 'completed') {
                updateFields.push('processed_at = ?');
                updateParams.push(new Date());
            }

            // Always update updated_at
            updateFields.push('updated_at = ?');
            updateParams.push(new Date());

            updateParams.push(donationId);

            const query = `UPDATE donations SET ${updateFields.join(', ')} WHERE id = ?`;
            await this.db.execute(query, updateParams);

            // Log activity
            await logActivity(req.user.id, 'donation_updated', `Updated donation ${existingDonation.reference_number}`);

            res.json({
                success: true,
                message: 'Donation updated successfully'
            });

        } catch (error) {
            console.error('Error updating donation:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to update donation'
            });
        }
    }

    /**
     * Delete donation (Admin only)
     */
    async deleteDonation(req, res) {
        try {
            const { donationId } = req.params;

            // Check if donation exists
            const checkQuery = 'SELECT status, reference_number FROM donations WHERE id = ?';
            const [rows] = await this.db.execute(checkQuery, [donationId]);

            if (rows.length === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'Donation not found'
                });
            }

            const donation = rows[0];

            // Prevent deletion of completed donations (for audit purposes)
            if (donation.status === 'completed') {
                return res.status(400).json({
                    success: false,
                    message: 'Cannot delete completed donations'
                });
            }

            const deleteQuery = 'DELETE FROM donations WHERE id = ?';
            await this.db.execute(deleteQuery, [donationId]);

            // Log activity
            await logActivity(req.user.id, 'donation_deleted', `Deleted donation ${donation.reference_number}`);

            res.json({
                success: true,
                message: 'Donation deleted successfully'
            });

        } catch (error) {
            console.error('Error deleting donation:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to delete donation'
            });
        }
    }

    /**
     * Get donation statistics (Admin/Moderator only)
     */
    async getDonationStats(req, res) {
        try {
            const stats = {};

            // Total donations
            const totalQuery = `
                SELECT COUNT(*) as total, SUM(amount) as total_amount 
                FROM donations 
                WHERE status = 'completed'
            `;
            const [totalRows] = await this.db.execute(totalQuery);
            stats.total_donations = totalRows[0].total || 0;
            stats.total_amount = parseFloat(totalRows[0].total_amount) || 0;

            // Monthly totals
            const monthlyQuery = `
                SELECT COUNT(*) as monthly_donations, SUM(amount) as monthly_amount 
                FROM donations 
                WHERE status = 'completed' 
                AND YEAR(created_at) = YEAR(CURDATE()) 
                AND MONTH(created_at) = MONTH(CURDATE())
            `;
            const [monthlyRows] = await this.db.execute(monthlyQuery);
            stats.monthly_donations = monthlyRows[0].monthly_donations || 0;
            stats.monthly_amount = parseFloat(monthlyRows[0].monthly_amount) || 0;

            // Average donation
            stats.average_donation = stats.total_donations > 0 ? 
                stats.total_amount / stats.total_donations : 0;

            // Unique donors
            const uniqueQuery = `
                SELECT COUNT(DISTINCT donor_email) as unique_donors 
                FROM donations 
                WHERE status = 'completed' 
                AND is_anonymous = 0 
                AND donor_email IS NOT NULL
            `;
            const [uniqueRows] = await this.db.execute(uniqueQuery);
            stats.unique_donors = uniqueRows[0].unique_donors || 0;

            // Donations by status
            const statusQuery = `
                SELECT status, COUNT(*) as count, SUM(amount) as amount 
                FROM donations 
                GROUP BY status
            `;
            const [statusRows] = await this.db.execute(statusQuery);
            stats.by_status = statusRows;

            // Donations by type
            const typeQuery = `
                SELECT type, COUNT(*) as count, SUM(amount) as amount 
                FROM donations 
                WHERE status = 'completed'
                GROUP BY type
            `;
            const [typeRows] = await this.db.execute(typeQuery);
            stats.by_type = typeRows;

            // Donations by category
            const categoryQuery = `
                SELECT category, COUNT(*) as count, SUM(amount) as amount 
                FROM donations 
                WHERE status = 'completed'
                GROUP BY category
            `;
            const [categoryRows] = await this.db.execute(categoryQuery);
            stats.by_category = categoryRows;

            // Monthly trend (last 12 months)
            const trendQuery = `
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as count,
                    SUM(amount) as amount
                FROM donations 
                WHERE status = 'completed'
                AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month
            `;
            const [trendRows] = await this.db.execute(trendQuery);
            stats.monthly_trend = trendRows;

            res.json({
                success: true,
                data: stats
            });

        } catch (error) {
            console.error('Error fetching donation stats:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch donation statistics'
            });
        }
    }

    /**
     * Get public donation statistics (for public donation page)
     */
    async getPublicDonationStats(req, res) {
        try {
            const stats = {};

            // Total donations amount and count
            const totalQuery = `
                SELECT COUNT(*) as total, SUM(amount) as total_amount 
                FROM donations 
                WHERE status = 'completed'
            `;
            const [totalRows] = await this.db.execute(totalQuery);
            stats.total_amount = parseFloat(totalRows[0].total_amount) || 0;
            stats.donor_count = totalRows[0].total || 0;

            // Projects supported (simplified - using categories as projects)
            const projectsQuery = `
                SELECT COUNT(DISTINCT category) as projects 
                FROM donations 
                WHERE status = 'completed'
            `;
            const [projectsRows] = await this.db.execute(projectsQuery);
            stats.projects_supported = projectsRows[0].projects || 0;

            res.json({
                success: true,
                data: stats
            });

        } catch (error) {
            console.error('Error fetching public donation stats:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch donation statistics'
            });
        }
    }

    /**
     * Get donation purposes/categories
     */
    async getDonationPurposes(req, res) {
        try {
            const purposes = [
                { id: 'general', name: 'General Fund', description: 'Support our general operations and programs' },
                { id: 'healthcare', name: 'Healthcare', description: 'Medical equipment and healthcare services' },
                { id: 'education', name: 'Education', description: 'Educational programs and scholarships' },
                { id: 'community', name: 'Community Development', description: 'Community center and local programs' },
                { id: 'emergency', name: 'Emergency Relief', description: 'Emergency response and disaster relief' }
            ];

            res.json({
                success: true,
                data: purposes
            });

        } catch (error) {
            console.error('Error fetching donation purposes:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch donation purposes'
            });
        }
    }

    /**
     * Get recent donors (for public display)
     */
    async getRecentDonors(req, res) {
        try {
            const query = `
                SELECT 
                    CASE 
                        WHEN is_anonymous = 1 THEN 'Anonymous'
                        ELSE CONCAT(LEFT(donor_name, 1), '***')
                    END as donor_name,
                    amount,
                    created_at
                FROM donations 
                WHERE status = 'completed'
                ORDER BY created_at DESC 
                LIMIT 10
            `;
            const [donors] = await this.db.execute(query);

            res.json({
                success: true,
                data: donors
            });

        } catch (error) {
            console.error('Error fetching recent donors:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch recent donors'
            });
        }
    }

    /**
     * Create public donation (from donation page)
     */
    async createPublicDonation(req, res) {
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

            const data = req.body;

            // Generate reference number
            const referenceNumber = this.generateReferenceNumber();

            // Prepare donation data
            const donationData = {
                reference_number: referenceNumber,
                amount: data.amount,
                type: data.type || 'one-time',
                category: data.purpose,
                status: 'pending', // Will be updated after payment
                fee_amount: 0,
                is_anonymous: data.anonymous || false,
                donor_name: `${data.firstName} ${data.lastName}`,
                donor_email: data.email,
                donor_phone: data.phone || null,
                donor_address: data.address || null,
                payment_method: 'online', // Default for public donations
                payment_status: 'pending',
                transaction_id: null, // Will be set after payment
                payment_date: null,
                notes: null,
                admin_notes: 'Public donation via website',
                created_at: new Date(),
                updated_at: new Date()
            };

            // Insert donation
            const fields = Object.keys(donationData).join(', ');
            const placeholders = Object.keys(donationData).map(() => '?').join(', ');
            const values = Object.values(donationData);

            const query = `INSERT INTO donations (${fields}) VALUES (${placeholders})`;
            const [result] = await this.db.execute(query, values);

            const donationId = result.insertId;

            // In a real implementation, you would integrate with a payment gateway here
            // For now, we'll simulate a successful payment
            await this.simulatePaymentSuccess(donationId, referenceNumber);

            res.status(201).json({
                success: true,
                message: 'Donation processed successfully',
                data: {
                    id: donationId,
                    reference_number: referenceNumber,
                    amount: data.amount,
                    transaction_id: 'TXN_' + referenceNumber.substring(3, 13).toUpperCase()
                }
            });

        } catch (error) {
            console.error('Error creating public donation:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to process donation'
            });
        }
    }

    /**
     * Export donations to CSV (Admin/Moderator only)
     */
    async exportDonations(req, res) {
        try {
            const {
                search,
                status,
                type,
                category,
                date_from,
                date_to
            } = req.query;

            // Build WHERE conditions (similar to getAllDonations)
            let whereConditions = [];
            let whereParams = [];

            if (search) {
                const searchTerm = `%${search}%`;
                whereConditions.push('(reference_number LIKE ? OR donor_name LIKE ? OR donor_email LIKE ?)');
                whereParams.push(searchTerm, searchTerm, searchTerm);
            }

            if (status) {
                whereConditions.push('status = ?');
                whereParams.push(status);
            }

            if (type) {
                whereConditions.push('type = ?');
                whereParams.push(type);
            }

            if (category) {
                whereConditions.push('category = ?');
                whereParams.push(category);
            }

            if (date_from) {
                whereConditions.push('DATE(created_at) >= ?');
                whereParams.push(date_from);
            }

            if (date_to) {
                whereConditions.push('DATE(created_at) <= ?');
                whereParams.push(date_to);
            }

            const whereClause = whereConditions.length > 0 ? 'WHERE ' + whereConditions.join(' AND ') : '';

            // Get donations for export
            const query = `SELECT * FROM donations ${whereClause} ORDER BY created_at DESC`;
            const [donations] = await this.db.execute(query, whereParams);

            // Generate CSV content
            let csvContent = 'Reference Number,Amount,Type,Category,Status,Donor Name,Donor Email,Payment Method,Transaction ID,Created Date\n';

            donations.forEach(donation => {
                csvContent += [
                    donation.reference_number,
                    donation.amount,
                    donation.type,
                    donation.category,
                    donation.status,
                    donation.is_anonymous ? 'Anonymous' : donation.donor_name,
                    donation.is_anonymous ? '' : donation.donor_email,
                    donation.payment_method,
                    donation.transaction_id || '',
                    new Date(donation.created_at).toISOString().slice(0, 19).replace('T', ' ')
                ].join(',') + '\n';
            });

            // Set headers for CSV download
            res.setHeader('Content-Type', 'text/csv');
            res.setHeader('Content-Disposition', `attachment; filename="donations-export-${new Date().toISOString().slice(0, 10)}.csv"`);
            res.send(csvContent);

        } catch (error) {
            console.error('Error exporting donations:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to export donations'
            });
        }
    }

    /**
     * Generate receipt (Admin/Moderator only)
     */
    async generateReceipt(req, res) {
        try {
            const { donationId } = req.params;

            // Get donation details
            const query = 'SELECT * FROM donations WHERE id = ?';
            const [rows] = await this.db.execute(query, [donationId]);

            if (rows.length === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'Donation not found'
                });
            }

            const donation = rows[0];

            if (donation.status !== 'completed') {
                return res.status(400).json({
                    success: false,
                    message: 'Receipt can only be generated for completed donations'
                });
            }

            const receiptContent = this.generateReceiptContent(donation);

            // Set headers for text receipt (in production, use PDF library)
            res.setHeader('Content-Type', 'text/plain');
            res.setHeader('Content-Disposition', `attachment; filename="donation-receipt-${donation.reference_number}.txt"`);
            res.send(receiptContent);

        } catch (error) {
            console.error('Error generating receipt:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to generate receipt'
            });
        }
    }

    /**
     * Send thank you email (Admin/Moderator only)
     */
    async sendThankYou(req, res) {
        try {
            const { donationId } = req.params;

            // Get donation details
            const query = 'SELECT * FROM donations WHERE id = ?';
            const [rows] = await this.db.execute(query, [donationId]);

            if (rows.length === 0) {
                return res.status(404).json({
                    success: false,
                    message: 'Donation not found'
                });
            }

            const donation = rows[0];

            if (donation.is_anonymous || !donation.donor_email) {
                return res.status(400).json({
                    success: false,
                    message: 'Cannot send email to anonymous donor or missing email'
                });
            }

            // Send thank you email
            const emailSent = await this.sendThankYouEmail(donation);

            if (emailSent) {
                // Log activity
                await logActivity(req.user.id, 'thank_you_sent', `Sent thank you email for donation ${donation.reference_number}`);

                res.json({
                    success: true,
                    message: 'Thank you email sent successfully'
                });
            } else {
                res.status(500).json({
                    success: false,
                    message: 'Failed to send thank you email'
                });
            }

        } catch (error) {
            console.error('Error sending thank you email:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to send thank you email'
            });
        }
    }

    /**
     * Helper Methods
     */

    /**
     * Generate unique reference number
     */
    generateReferenceNumber() {
        const prefix = 'DON';
        const timestamp = new Date().toISOString().slice(0, 10).replace(/-/g, '');
        const random = Math.floor(Math.random() * 9999).toString().padStart(4, '0');
        return prefix + timestamp + random;
    }

    /**
     * Generate receipt content
     */
    generateReceiptContent(donation) {
        let content = 'DONATION RECEIPT\n';
        content += '================\n\n';
        content += `Reference Number: ${donation.reference_number}\n`;
        content += `Date: ${new Date(donation.created_at).toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        })}\n\n`;

        if (!donation.is_anonymous) {
            content += `Donor: ${donation.donor_name}\n`;
            content += `Email: ${donation.donor_email}\n\n`;
        } else {
            content += 'Anonymous Donation\n\n';
        }

        content += `Amount: $${parseFloat(donation.amount).toFixed(2)}\n`;
        content += `Type: ${donation.type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}\n`;
        content += `Category: ${donation.category.charAt(0).toUpperCase() + donation.category.slice(1)}\n`;
        content += `Payment Method: ${donation.payment_method.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}\n\n`;

        content += 'Thank you for your generous donation!\n';
        content += 'Your support makes a difference in our community.\n\n';

        content += 'This receipt serves as proof of your donation for tax purposes.\n';

        return content;
    }

    /**
     * Simulate payment success (for demo purposes)
     */
    async simulatePaymentSuccess(donationId, referenceNumber) {
        try {
            const transactionId = 'TXN_' + referenceNumber.substring(3, 13).toUpperCase();

            const query = `
                UPDATE donations 
                SET status = 'completed', 
                    payment_status = 'paid', 
                    transaction_id = ?, 
                    payment_date = NOW(), 
                    processed_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            `;
            await this.db.execute(query, [transactionId, donationId]);

        } catch (error) {
            console.error('Error simulating payment success:', error);
        }
    }

    /**
     * Send donation confirmation email
     */
    async sendDonationConfirmation(donationId) {
        // Placeholder for email sending functionality
        // In production, integrate with email service (SendGrid, Mailgun, etc.)
        return true;
    }

    /**
     * Send thank you email
     */
    async sendThankYouEmail(donation) {
        // Placeholder for email sending functionality
        // In production, integrate with email service (SendGrid, Mailgun, etc.)
        return true;
    }
}

module.exports = new DonationController();