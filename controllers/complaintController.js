/**
 * Complaint Controller - Node.js/Express
 * BHRC India - Human Rights Commission Web Application
 * Handles complaint filing, management, and status tracking
 */

const { validationResult } = require('express-validator');
const db = require('../config/database');
const { logActivity, logError } = require('../middleware/auth');
const ResponseHelper = require('../utils/responseHelper');
const QueryBuilder = require('../utils/queryBuilder');

class ComplaintController {

    /**
     * File a new complaint (Public access)
     */
    async fileComplaint(req, res) {
        try {
            // Check validation results
            const errors = validationResult(req);
            if (!errors.isEmpty()) {
                return ResponseHelper.validationError(res, errors.array());
            }

            const {
                complainant_name,
                complainant_email,
                complainant_phone,
                complainant_address = '',
                complaint_type,
                subject,
                description,
                incident_date,
                incident_location = ''
            } = req.body;

            // Generate unique complaint number
            const complaintNumber = await this.generateComplaintNumber();

            // Prepare complaint data
            const complaintData = {
                complaint_number: complaintNumber,
                complainant_name: complainant_name.trim(),
                complainant_email: complainant_email.trim().toLowerCase(),
                complainant_phone: complainant_phone.trim(),
                complainant_address: complainant_address.trim(),
                complaint_type,
                subject: subject.trim(),
                description: description.trim(),
                incident_date: incident_date || null,
                incident_location: incident_location.trim(),
                priority: 'medium', // Default priority
                status: 'submitted',
                created_at: new Date()
            };

            // Insert complaint using QueryBuilder
            const result = await QueryBuilder.insert('complaints', complaintData);

            if (!result.insertId) {
                return ResponseHelper.serverError(res, 'Failed to file complaint');
            }

            // Log activity
            await logActivity('complaint_filed', `Complaint filed: ${complaintNumber}`, null, req.ip);

            // Send confirmation email (implement email service)
            await this.sendComplaintConfirmation(complaintData);

            return ResponseHelper.created(res, {
                message: 'Complaint filed successfully',
                complaint_number: complaintNumber,
                complaint_id: result.insertId
            });

        } catch (error) {
            console.error('Complaint filing error:', error);
            await logError('Complaint filing error: ' + error.message);
            return ResponseHelper.serverError(res, 'Internal server error');
        }
    }

    /**
     * Get complaint status by complaint number (Public access)
     */
    async getComplaintStatus(req, res) {
        try {
            const { complaint_number } = req.query;

            if (!complaint_number) {
                return ResponseHelper.error(res, 'Complaint number is required', 400);
            }

            const complaint = await QueryBuilder
                .table('complaints')
                .select(['complaint_number', 'status', 'priority', 'subject', 'created_at', 'updated_at', 'admin_notes'])
                .where('complaint_number', '=', complaint_number)
                .first();

            if (!complaint) {
                return ResponseHelper.notFound(res, 'Complaint not found');
            }

            // Format response
            const response = {
                complaint_number: complaint.complaint_number,
                status: complaint.status,
                priority: complaint.priority,
                subject: complaint.subject,
                filed_date: complaint.created_at,
                last_updated: complaint.updated_at,
                admin_notes: complaint.admin_notes
            };

            return ResponseHelper.success(res, response);

        } catch (error) {
            console.error('Complaint status check error:', error);
            await logError('Complaint status check error: ' + error.message);
            return ResponseHelper.serverError(res, 'Internal server error');
        }
    }

    /**
     * Get all complaints (Admin/Moderator access)
     */
    async getAllComplaints(req, res) {
        try {
            const page = parseInt(req.query.page) || 1;
            const perPage = parseInt(req.query.per_page) || 20;
            const offset = (page - 1) * perPage;
            
            // Build query using QueryBuilder
            let query = QueryBuilder.table('complaints');
            
            // Apply filters
            if (req.query.status) {
                query = query.where('status', '=', req.query.status);
            }
            
            if (req.query.priority) {
                query = query.where('priority', '=', req.query.priority);
            }
            
            if (req.query.category) {
                query = query.where('category', '=', req.query.category);
            }
            
            if (req.query.search) {
                const searchTerm = req.query.search;
                query = query.where('complaint_number', 'LIKE', `%${searchTerm}%`)
                           .orWhere('complainant_name', 'LIKE', `%${searchTerm}%`)
                           .orWhere('subject', 'LIKE', `%${searchTerm}%`);
            }
            
            // Get total count
            const total = await query.count();
            
            // Get complaints with pagination
            const complaints = await query
                .select(['id', 'complaint_number as case_number', 'complainant_name', 'complainant_email', 'complainant_phone',
                        'subject as title', 'subject', 'description', 'category', 'priority', 'status', 
                        'created_at', 'updated_at', 'assigned_to'])
                .orderBy('created_at', 'DESC')
                .limit(perPage, offset)
                .get();
            
            return res.json({
                success: true,
                complaints: complaints,
                total: total,
                current_page: page,
                per_page: perPage,
                last_page: Math.ceil(total / perPage),
                stats: {
                    total_complaints: total,
                    pending_complaints: 0,
                    in_progress_complaints: 0,
                    resolved_complaints: 0
                }
            });
            
        } catch (error) {
            console.error('Get complaints error:', error);
            return ResponseHelper.serverError(res, 'Internal server error');
        }
    }

    /**
     * Get complaint details (Admin/Moderator access)
     */
    async getComplaintDetails(req, res) {
        try {
            const { id } = req.params;

            const complaint = await QueryBuilder
                .table('complaints c')
                .select(['c.*', 'u.first_name', 'u.last_name'])
                .leftJoin('users u', 'c.assigned_to = u.id')
                .where('c.id', '=', id)
                .first();

            if (!complaint) {
                return ResponseHelper.notFound(res, 'Complaint not found');
            }

            // Format assigned user name
            if (complaint.assigned_to) {
                complaint.assigned_to_name = `${complaint.first_name} ${complaint.last_name}`;
            } else {
                complaint.assigned_to_name = null;
            }

            // Remove user name fields from main object
            delete complaint.first_name;
            delete complaint.last_name;

            return ResponseHelper.success(res, complaint);

        } catch (error) {
            console.error('Get complaint details error:', error);
            await logError('Get complaint details error: ' + error.message);
            return ResponseHelper.serverError(res, 'Internal server error');
        }
    }

    /**
     * Update complaint status (Admin/Moderator access)
     */
    async updateComplaintStatus(req, res) {
        try {
            const { id } = req.params;
            const { status, priority, admin_notes, assigned_to } = req.body;

            // Check if complaint exists
            const existingSql = "SELECT id, status FROM complaints WHERE id = ?";
            const existing = await db.queryOne(existingSql, [id]);
            
            if (!existing) {
                return res.status(404).json({ error: 'Complaint not found' });
            }

            // Prepare update data
            const updateFields = ['status = ?', 'updated_at = ?'];
            const params = [status, new Date()];

            if (priority !== undefined) {
                updateFields.push('priority = ?');
                params.push(priority);
            }

            if (admin_notes !== undefined) {
                updateFields.push('admin_notes = ?');
                params.push(admin_notes.trim());
            }

            if (assigned_to !== undefined) {
                updateFields.push('assigned_to = ?');
                params.push(assigned_to || null);
            }

            params.push(id); // For WHERE clause

            // Update complaint using pool.execute to get result metadata
            const sql = `UPDATE complaints SET ${updateFields.join(', ')} WHERE id = ?`;
            const [result] = await db.pool.execute(sql, params);

            if (!result.affectedRows) {
                return res.status(500).json({ error: 'Failed to update complaint' });
            }

            // Log activity
            await logActivity('complaint_updated', `Complaint #${id} status updated to ${status}`, req.user.id, req.ip);

            res.json({ message: 'Complaint updated successfully' });

        } catch (error) {
            console.error('Update complaint error:', error);
            await logError('Update complaint error: ' + error.message);
            res.status(500).json({ error: 'Internal server error' });
        }
    }

    /**
     * Get complaint statistics (Admin/Moderator access)
     */
    async getComplaintStats(req, res) {
        try {
            // Get overall statistics
            const stats = {};

            // Total complaints
            const totalSql = "SELECT COUNT(*) as total FROM complaints";
            const totalResult = await db.queryOne(totalSql);
            stats.total_complaints = totalResult.total;

            // Status breakdown
            const statusSql = "SELECT status, COUNT(*) as count FROM complaints GROUP BY status";
            const statusResults = await db.query(statusSql);
            stats.by_status = {};
            statusResults.forEach(row => {
                stats.by_status[row.status] = row.count;
            });

            // Priority breakdown
            const prioritySql = "SELECT priority, COUNT(*) as count FROM complaints GROUP BY priority";
            const priorityResults = await db.query(prioritySql);
            stats.by_priority = {};
            priorityResults.forEach(row => {
                stats.by_priority[row.priority] = row.count;
            });

            // Type breakdown
            const typeSql = "SELECT complaint_type, COUNT(*) as count FROM complaints GROUP BY complaint_type";
            const typeResults = await db.query(typeSql);
            stats.by_type = {};
            typeResults.forEach(row => {
                stats.by_type[row.complaint_type] = row.count;
            });

            // Monthly trends (last 12 months)
            const trendSql = `SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                            FROM complaints 
                            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                            ORDER BY month`;
            const trendResults = await db.query(trendSql);
            stats.monthly_trend = trendResults;

            // Recent complaints (last 7 days)
            const recentSql = "SELECT COUNT(*) as count FROM complaints WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            const recentResult = await db.queryOne(recentSql);
            stats.recent_complaints = recentResult.count;

            res.json(stats);

        } catch (error) {
            console.error('Get complaint stats error:', error);
            await logError('Get complaint stats error: ' + error.message);
            res.status(500).json({ error: 'Internal server error' });
        }
    }

    /**
     * Bulk update complaint status (Admin/Moderator access)
     */
    async bulkUpdateStatus(req, res) {
        try {
            const { complaint_ids, status } = req.body;

            // Validate required fields
            if (!Array.isArray(complaint_ids) || complaint_ids.length === 0) {
                return res.status(400).json({ error: 'complaint_ids array is required' });
            }

            if (!status) {
                return res.status(400).json({ error: 'status is required' });
            }

            const complaintIds = complaint_ids.map(id => parseInt(id));
            const placeholders = complaintIds.map(() => '?').join(',');

            // Update complaints
            const sql = `UPDATE complaints SET status = ?, updated_at = ? WHERE id IN (${placeholders})`;
            const params = [status, new Date(), ...complaintIds];
            
            const result = await db.query(sql, params);

            if (!result.affectedRows) {
                return res.status(500).json({ error: 'Failed to update complaints' });
            }

            // Log activity
            const idsString = complaintIds.join(', ');
            await logActivity('bulk_complaint_update', `Bulk updated complaints [${idsString}] to status ${status}`, req.user.id, req.ip);

            res.json({ message: 'Complaints updated successfully' });

        } catch (error) {
            console.error('Bulk update complaints error:', error);
            await logError('Bulk update complaints error: ' + error.message);
            res.status(500).json({ error: 'Internal server error' });
        }
    }

    /**
     * Add note to complaint (Admin/Moderator access)
     */
    async addNote(req, res) {
        try {
            const { id } = req.params;
            const { content } = req.body;

            if (!content || !content.trim()) {
                return res.status(400).json({ error: 'Note content is required' });
            }

            // Check if complaint exists
            const complaintSql = "SELECT id FROM complaints WHERE id = ?";
            const complaint = await db.queryOne(complaintSql, [id]);
            
            if (!complaint) {
                return res.status(404).json({ error: 'Complaint not found' });
            }

            // Insert note into complaint_updates table
            const sql = `INSERT INTO complaint_updates (complaint_id, updated_by, update_type, notes, created_at) 
                        VALUES (?, ?, 'note_added', ?, ?)`;
            
            const params = [
                id,
                req.user.id,
                content.trim(),
                new Date()
            ];

            const result = await db.query(sql, params);

            if (!result.insertId) {
                return res.status(500).json({ error: 'Failed to add note' });
            }

            // Log activity
            await logActivity('complaint_note_added', `Note added to complaint #${id}`, req.user.id, req.ip);

            res.json({ message: 'Note added successfully' });

        } catch (error) {
            console.error('Add complaint note error:', error);
            await logError('Add complaint note error: ' + error.message);
            res.status(500).json({ error: 'Internal server error' });
        }
    }

    /**
     * Assign complaint to user (Admin/Moderator access)
     */
    async assignComplaint(req, res) {
        try {
            const { id } = req.params;
            const { assigned_to, note = '' } = req.body;

            // Check if complaint exists
            const complaintSql = "SELECT id, assigned_to FROM complaints WHERE id = ?";
            const complaint = await db.queryOne(complaintSql, [id]);
            
            if (!complaint) {
                return res.status(404).json({ error: 'Complaint not found' });
            }

            // Validate assigned user if provided
            if (assigned_to) {
                const userSql = "SELECT id FROM users WHERE id = ? AND (role = 'admin' OR role = 'moderator')";
                const assignedUser = await db.queryOne(userSql, [assigned_to]);
                
                if (!assignedUser) {
                    return res.status(400).json({ error: 'Invalid user for assignment' });
                }
            }

            const oldAssignedTo = complaint.assigned_to;
            const newAssignedTo = assigned_to || null;

            // Update complaint assignment
            const sql = "UPDATE complaints SET assigned_to = ?, updated_at = ? WHERE id = ?";
            const params = [newAssignedTo, new Date(), id];
            
            const result = await db.query(sql, params);

            if (!result.affectedRows) {
                return res.status(500).json({ error: 'Failed to assign complaint' });
            }

            // Add update record
            const updateSql = `INSERT INTO complaint_updates (complaint_id, updated_by, update_type, old_value, new_value, notes, created_at) 
                             VALUES (?, ?, 'assignment', ?, ?, ?, ?)`;
            
            const updateParams = [
                id,
                req.user.id,
                oldAssignedTo,
                newAssignedTo,
                note.trim(),
                new Date()
            ];

            await db.query(updateSql, updateParams);

            // Log activity
            const assignmentText = newAssignedTo ? `assigned to user #${newAssignedTo}` : "unassigned";
            await logActivity('complaint_assigned', `Complaint #${id} ${assignmentText}`, req.user.id, req.ip);

            res.json({ message: 'Complaint assigned successfully' });

        } catch (error) {
            console.error('Assign complaint error:', error);
            await logError('Assign complaint error: ' + error.message);
            res.status(500).json({ error: 'Internal server error' });
        }
    }

    /**
     * Export complaints to CSV (Admin/Moderator access)
     */
    async exportComplaints(req, res) {
        try {
            // Get query parameters for filtering
            const status = req.query.status || '';
            const priority = req.query.priority || '';
            const type = req.query.type || '';
            const search = req.query.search || '';

            // Build WHERE clause
            const whereConditions = [];
            const params = [];

            if (status) {
                whereConditions.push("status = ?");
                params.push(status);
            }

            if (priority) {
                whereConditions.push("priority = ?");
                params.push(priority);
            }

            if (type) {
                whereConditions.push("complaint_type = ?");
                params.push(type);
            }

            if (search) {
                whereConditions.push("(complaint_number LIKE ? OR complainant_name LIKE ? OR subject LIKE ?)");
                const searchTerm = `%${search}%`;
                params.push(searchTerm, searchTerm, searchTerm);
            }

            const whereClause = whereConditions.length > 0 ? 'WHERE ' + whereConditions.join(' AND ') : '';

            // Get complaints for export
            const sql = `SELECT complaint_number, complainant_name, complainant_email, complainant_phone,
                        complaint_type, subject, priority, status, created_at, updated_at
                        FROM complaints ${whereClause} 
                        ORDER BY created_at DESC`;

            const complaints = await db.query(sql, params);

            // Set headers for CSV download
            res.setHeader('Content-Type', 'text/csv');
            res.setHeader('Content-Disposition', `attachment; filename="complaints-export-${new Date().toISOString().split('T')[0]}.csv"`);
            res.setHeader('Pragma', 'no-cache');
            res.setHeader('Expires', '0');

            // Create CSV content
            const csvHeaders = [
                'Complaint Number',
                'Complainant Name',
                'Email',
                'Phone',
                'Type',
                'Subject',
                'Priority',
                'Status',
                'Filed Date',
                'Last Updated'
            ];

            let csvContent = csvHeaders.join(',') + '\n';

            // CSV data
            complaints.forEach(complaint => {
                const row = [
                    complaint.complaint_number,
                    `"${complaint.complainant_name}"`,
                    complaint.complainant_email,
                    complaint.complainant_phone,
                    `"${complaint.complaint_type.replace(/_/g, ' ')}"`,
                    `"${complaint.subject}"`,
                    complaint.priority,
                    complaint.status.replace(/_/g, ' '),
                    complaint.created_at,
                    complaint.updated_at
                ];
                csvContent += row.join(',') + '\n';
            });

            // Log activity
            await logActivity('complaints_exported', `Exported ${complaints.length} complaints`, req.user.id, req.ip);

            res.send(csvContent);

        } catch (error) {
            console.error('Export complaints error:', error);
            await logError('Export complaints error: ' + error.message);
            res.status(500).json({ error: 'Internal server error' });
        }
    }

    /**
     * Get staff members for assignment (Admin/Moderator access)
     */
    async getStaffMembers(req, res) {
        try {
            const sql = `SELECT id, CONCAT(first_name, ' ', last_name) as name, email, role 
                        FROM users 
                        WHERE role IN ('admin', 'moderator') AND status = 'active'
                        ORDER BY first_name, last_name`;

            const staff = await db.query(sql);

            res.json(staff);

        } catch (error) {
            console.error('Get staff members error:', error);
            await logError('Get staff members error: ' + error.message);
            res.status(500).json({ error: 'Internal server error' });
        }
    }

    /**
     * Generate unique complaint number
     */
    async generateComplaintNumber() {
        const prefix = 'BHRC';
        const year = new Date().getFullYear();
        const month = String(new Date().getMonth() + 1).padStart(2, '0');
        
        // Get the last complaint number for this month
        const sql = `SELECT complaint_number FROM complaints 
                    WHERE complaint_number LIKE ? 
                    ORDER BY id DESC LIMIT 1`;
        
        const pattern = `${prefix}${year}${month}%`;
        const lastComplaint = await db.queryOne(sql, [pattern]);
        
        let sequence;
        if (lastComplaint) {
            // Extract the sequence number and increment
            const lastNumber = lastComplaint.complaint_number.slice(-4);
            sequence = String(parseInt(lastNumber) + 1).padStart(4, '0');
        } else {
            // First complaint of the month
            sequence = '0001';
        }
        
        return `${prefix}${year}${month}${sequence}`;
    }

    /**
     * Send complaint confirmation email
     */
    async sendComplaintConfirmation(complaintData) {
        // TODO: Implement email service
        // This would send a confirmation email to the complainant
        // with their complaint number and tracking information
        
        await logActivity('email_sent', `Complaint confirmation sent to ${complaintData.complainant_email}`, null, 'system');
    }

    /**
     * Delete complaint (Admin access only)
     */
    async deleteComplaint(req, res) {
        try {
            const { complaintId } = req.params;

            // Check if complaint exists
            const complaint = await QueryBuilder
                .table('complaints')
                .where('id', '=', complaintId)
                .first();

            if (!complaint) {
                return ResponseHelper.notFound(res, 'Complaint not found');
            }

            // Delete the complaint
            const result = await QueryBuilder.delete('complaints', 'id = ?', [complaintId]);

            if (!result.affectedRows) {
                return ResponseHelper.serverError(res, 'Failed to delete complaint');
            }

            // Log activity
            await logActivity(req.user.id, 'complaint_deleted', `Complaint #${complaint.complaint_number} deleted`);

            return ResponseHelper.success(res, null, 'Complaint deleted successfully');

        } catch (error) {
            console.error('Delete complaint error:', error);
            await logError(error, req.user?.id, 'delete_complaint_error');
            return ResponseHelper.serverError(res, 'Failed to delete complaint');
        }
    }

    /**
     * Download complaint file (Admin/Moderator access)
     */
    async downloadComplaintFile(req, res) {
        try {
            const { complaintId } = req.params;

            // Check if complaint exists
            const complaint = await QueryBuilder
                .table('complaints')
                .where('id', '=', complaintId)
                .first();

            if (!complaint) {
                return ResponseHelper.notFound(res, 'Complaint not found');
            }

            // Generate PDF or return complaint data
            // For now, return complaint data as JSON
            // TODO: Implement PDF generation
            return ResponseHelper.success(res, complaint, 'Complaint file retrieved successfully');

        } catch (error) {
            console.error('Download complaint file error:', error);
            await logError(error, req.user?.id, 'download_complaint_file_error');
            return ResponseHelper.serverError(res, 'Failed to download complaint file');
        }
    }

    /**
     * Download complaint attachment (Admin/Moderator access)
     */
    async downloadAttachment(req, res) {
        try {
            const { attachmentId } = req.params;

            // Check if attachment exists
            const attachment = await QueryBuilder
                .table('complaint_attachments')
                .where('id', '=', attachmentId)
                .first();

            if (!attachment) {
                return ResponseHelper.notFound(res, 'Attachment not found');
            }

            // TODO: Implement file download from storage
            return ResponseHelper.success(res, attachment, 'Attachment retrieved successfully');

        } catch (error) {
            console.error('Download attachment error:', error);
            await logError(error, req.user?.id, 'download_attachment_error');
            return ResponseHelper.serverError(res, 'Failed to download attachment');
        }
    }

    /**
     * Get member complaints
     */
    async getMemberComplaints(req, res) {
        try {
            const userId = req.user.id;
            const { page = 1, limit = 10, status = '' } = req.query;

            // Get user's email
            const userQuery = 'SELECT email FROM users WHERE id = ?';
            const userResult = await db.queryOne(userQuery, [userId]);
            
            if (!userResult) {
                return res.status(404).json({
                    success: false,
                    message: 'User not found'
                });
            }

            const userEmail = userResult.email;
            const offset = (page - 1) * limit;

            // Build query
            let whereClause = 'WHERE complainant_email = ?';
            let params = [userEmail];

            if (status) {
                whereClause += ' AND status = ?';
                params.push(status);
            }

            // Get complaints
            const limitValue = parseInt(limit);
            const offsetValue = parseInt(offset);
            
            const query = `
                SELECT id, complaint_number, subject, complaint_type, priority, status, created_at, updated_at
                FROM complaints
                ${whereClause}
                ORDER BY created_at DESC
                LIMIT ${limitValue} OFFSET ${offsetValue}
            `;

            const complaints = await db.query(query, params);

            // Get total count
            const countQuery = `SELECT COUNT(*) as total FROM complaints ${whereClause}`;
            const countResult = await db.queryOne(countQuery, params.slice(0, -2));
            const total = countResult.total;

            res.json({
                success: true,
                data: complaints,
                pagination: {
                    current_page: parseInt(page),
                    per_page: parseInt(limit),
                    total: total,
                    total_pages: Math.ceil(total / limit)
                }
            });

        } catch (error) {
            console.error('Get member complaints error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch complaints'
            });
        }
    }

    /**
     * Get member complaint statistics
     */
    async getMemberComplaintStats(req, res) {
        try {
            const userId = req.user.id;

            // Get user's email
            const userQuery = 'SELECT email FROM users WHERE id = ?';
            const userResult = await db.queryOne(userQuery, [userId]);
            
            if (!userResult) {
                return res.status(404).json({
                    success: false,
                    message: 'User not found'
                });
            }

            const userEmail = userResult.email;

            // Get statistics
            const statsQuery = `
                SELECT 
                    COUNT(*) as total_complaints,
                    SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted_complaints,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_complaints,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_complaints,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as recent_complaints
                FROM complaints
                WHERE complainant_email = ?
            `;

            const stats = await db.queryOne(statsQuery, [userEmail]);

            res.json({
                success: true,
                data: stats
            });

        } catch (error) {
            console.error('Get member complaint stats error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch complaint statistics'
            });
        }
    }

    /**
     * Add comment to complaint (Admin/Moderator access)
     */
    async addComment(req, res) {
        try {
            const { complaintId } = req.params;
            const { content, internal = false } = req.body;

            if (!content || !content.trim()) {
                return ResponseHelper.validationError(res, 'Comment content is required');
            }

            // Check if complaint exists
            const complaint = await QueryBuilder
                .table('complaints')
                .where('id', '=', complaintId)
                .first();

            if (!complaint) {
                return ResponseHelper.notFound(res, 'Complaint not found');
            }

            // Insert comment
            const commentData = {
                complaint_id: complaintId,
                user_id: req.user.id,
                content: content.trim(),
                internal: internal ? 1 : 0,
                created_at: new Date()
            };

            const result = await QueryBuilder.insert('complaint_comments', commentData);

            if (!result.insertId) {
                return ResponseHelper.serverError(res, 'Failed to add comment');
            }

            // Get the created comment with user details
            const newComment = await QueryBuilder
                .table('complaint_comments cc')
                .select(['cc.*', 'u.first_name', 'u.last_name', 'u.email'])
                .leftJoin('users u', 'cc.user_id = u.id')
                .where('cc.id', '=', result.insertId)
                .first();

            // Format user name
            if (newComment) {
                newComment.user_name = `${newComment.first_name} ${newComment.last_name}`;
                delete newComment.first_name;
                delete newComment.last_name;
                delete newComment.email;
            }

            // Log activity
            await logActivity(req.user.id, 'complaint_comment_added', `Comment added to complaint #${complaint.complaint_number}`);

            return ResponseHelper.created(res, newComment, 'Comment added successfully');

        } catch (error) {
            console.error('Add comment error:', error);
            await logError(error, req.user?.id, 'add_comment_error');
            return ResponseHelper.serverError(res, 'Failed to add comment');
        }
    }
}

module.exports = new ComplaintController();