/**
 * Dashboard Controller
 * Handles dashboard statistics and analytics API endpoints
 * BHRC India - Human Rights Commission Web Application
 */

const db = require('../config/database');
const { validationResult } = require('express-validator');
const { logActivity, logError } = require('../middleware/logger');
const ResponseHelper = require('../utils/responseHelper');
const QueryBuilder = require('../utils/queryBuilder');

/**
 * Helper method to execute single row queries
 * @param {string} query - SQL query
 * @param {Array} params - Query parameters
 * @returns {Promise<Object|null>}
 */
async function fetchOne(query, params = []) {
    return await db.queryOne(query, params);
}

/**
 * Helper method to execute multi-row queries
 * @param {string} query - SQL query
 * @param {Array} params - Query parameters
 * @returns {Promise<Array>}
 */
async function fetchAll(query, params = []) {
    return await db.query(query, params);
}

/**
 * Get general dashboard statistics
 * Available to all authenticated users
 */
async function getStats(req, res) {
    try {
        const user = req.user;
        if (!user) {
            return ResponseHelper.unauthorized(res, 'Authentication required');
        }

        const stats = {};

        // Total complaints
        const complaintsResult = await fetchOne('SELECT COUNT(*) as count FROM complaints');
        stats.total_complaints = complaintsResult.count;

        // Total events
        const eventsResult = await fetchOne('SELECT COUNT(*) as count FROM events');
        stats.total_events = eventsResult.count;

        // Total donations
        const donationsResult = await fetchOne(`
            SELECT COUNT(*) as total, COALESCE(SUM(amount), 0) as total_amount 
            FROM donations WHERE status = 'completed'
        `);
        stats.total_donations = parseInt(donationsResult.total);
        stats.total_donation_amount = parseFloat(donationsResult.total_amount);

        // Recent activity count (last 30 days)
        const recentResult = await fetchOne(`
            SELECT COUNT(*) as recent FROM complaints 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        `);
        stats.recent_complaints = parseInt(recentResult.recent);

        // Log activity
        await logActivity(user.id, 'dashboard_stats_viewed', 'Dashboard statistics viewed');

        return ResponseHelper.success(res, stats, 'Dashboard statistics retrieved successfully');

    } catch (error) {
        await logError(error, req.user?.id, 'dashboard_stats_error');
        console.error('Dashboard stats error:', error);
        return ResponseHelper.serverError(res, 'Failed to fetch dashboard statistics');
    }
}

/**
 * Get admin dashboard statistics
 * Available to admin and moderator roles only
 */
async function getAdminStats(req, res) {
    try {
        const user = req.user;
        if (!user) {
            return ResponseHelper.unauthorized(res, 'Authentication required');
        }

        // Check if user is admin or moderator
        if (!['admin', 'moderator'].includes(user.role)) {
            return ResponseHelper.forbidden(res, 'Admin access required');
        }

        let stats = {};

        // User statistics
        const userResult = await fetchOne(`
            SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_users,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_users_this_month,
                SUM(CASE WHEN email_verified = 0 THEN 1 ELSE 0 END) as pending_verifications
            FROM users
        `);

        stats = {
            ...stats,
            total_users: parseInt(userResult.total_users),
            active_users: parseInt(userResult.active_users),
            pending_users: parseInt(userResult.pending_users),
            new_users_this_month: parseInt(userResult.new_users_this_month),
            pending_verifications: parseInt(userResult.pending_verifications)
        };

        // Complaint statistics
        const complaintResult = await fetchOne(`
            SELECT 
                COUNT(*) as total_complaints,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_complaints,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_complaints,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_complaints
            FROM complaints
        `);

        stats = {
            ...stats,
            total_complaints: parseInt(complaintResult.total_complaints),
            pending_complaints: parseInt(complaintResult.pending_complaints),
            in_progress_complaints: parseInt(complaintResult.in_progress_complaints),
            resolved_complaints: parseInt(complaintResult.resolved_complaints)
        };

        // Event statistics
        const eventResult = await fetchOne(`
            SELECT 
                COUNT(*) as total_events,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_events,
                SUM(CASE WHEN event_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_events,
                SUM(CASE WHEN event_date < CURDATE() THEN 1 ELSE 0 END) as past_events
            FROM events
        `);

        stats = {
            ...stats,
            total_events: parseInt(eventResult.total_events),
            active_events: parseInt(eventResult.active_events),
            upcoming_events: parseInt(eventResult.upcoming_events),
            past_events: parseInt(eventResult.past_events)
        };

        // Donation statistics
        const donationResult = await fetchOne(`
            SELECT 
                COUNT(*) as total_donations,
                COALESCE(SUM(amount), 0) as total_amount,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as donations_this_month
            FROM donations WHERE status = 'completed'
        `);

        stats = {
            ...stats,
            total_donations: parseFloat(donationResult.total_amount),
            donations_this_month: parseInt(donationResult.donations_this_month)
        };

        // Log activity
        await logActivity(user.id, 'admin_stats_viewed', 'Admin dashboard statistics viewed');

        return ResponseHelper.success(res, stats, 'Admin statistics retrieved successfully');

    } catch (error) {
        await logError(error, req.user?.id, 'admin_dashboard_stats_error');
        console.error('Admin dashboard stats error:', error);
        return ResponseHelper.serverError(res, 'Failed to fetch admin dashboard statistics');
    }
}

/**
 * Get member dashboard statistics
 * Available to authenticated members
 */
async function getMemberStats(req, res) {
    try {
        const user = req.user;
        if (!user) {
            return res.status(401).json({
                success: false,
                message: 'Authentication required'
            });
        }

        const userId = user.id;
        let stats = {};

        // User's complaint statistics
        const complaintStats = await fetchOne(`
            SELECT 
                COUNT(*) as total_complaints,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_complaints,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_complaints,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_complaints
            FROM complaints WHERE user_id = ?
        `, [userId]);

        stats = {
            ...stats,
            my_complaints: parseInt(complaintStats.total_complaints),
            pending_complaints: parseInt(complaintStats.pending_complaints),
            in_progress_complaints: parseInt(complaintStats.in_progress_complaints),
            resolved_complaints: parseInt(complaintStats.resolved_complaints)
        };

        // User's donation statistics
        const donationStats = await fetchOne(`
            SELECT 
                COUNT(*) as total_donations,
                COALESCE(SUM(amount), 0) as total_amount
            FROM donations WHERE user_id = ? AND status = 'completed'
        `, [userId]);

        stats = {
            ...stats,
            my_donations: parseInt(donationStats.total_donations),
            total_donated: parseFloat(donationStats.total_amount)
        };

        // User's event registrations
        const eventResult = await fetchOne(
            'SELECT COUNT(*) as registered_events FROM event_registrations WHERE user_id = ?',
            [userId]
        );
        stats.registered_events = parseInt(eventResult.registered_events);

        // User's certificates
        const certificateResult = await fetchOne(
            'SELECT COUNT(*) as certificates FROM certificates WHERE user_id = ?',
            [userId]
        );
        stats.certificates = parseInt(certificateResult.certificates);

        // Log activity
        await logActivity(user.id, 'member_stats_viewed', 'Member dashboard statistics viewed');

        res.json({
            success: true,
            message: 'Member statistics retrieved successfully',
            data: stats
        });

    } catch (error) {
        await logError(error, req.user?.id, 'member_dashboard_stats_error');
        console.error('Member dashboard stats error:', error);
        res.status(500).json({
            success: false,
            message: 'Failed to fetch member dashboard statistics'
        });
    }
}

/**
 * Get recent activities for admin dashboard
 * Available to admin and moderator users
 */
async function getActivities(req, res) {
    try {
        const user = req.user;
        if (!user) {
            return res.status(401).json({
                success: false,
                message: 'Authentication required'
            });
        }

        // Check if user has admin or moderator role
        if (!['admin', 'moderator'].includes(user.role)) {
            return res.status(403).json({
                success: false,
                message: 'Admin access required'
            });
        }

        const limit = parseInt(req.query.limit) || 20;
        let activities = [];

        // Get recent complaints
        const complaints = await db.query(`
            SELECT 'complaint_filed' as type, id, 
                   CONCAT('New complaint: ', subject) as title,
                   subject as description, created_at, status, 
                   complainant_name as user_name
            FROM complaints 
            ORDER BY created_at DESC 
            LIMIT 5
        `);

        // Get recent user registrations
        const users = await db.query(`
            SELECT 'user_registered' as type, id,
                   CONCAT('New user registered: ', first_name, ' ', last_name) as title,
                   CONCAT(first_name, ' ', last_name, ' joined as ', role) as description,
                   created_at, status,
                   CONCAT(first_name, ' ', last_name) as user_name
            FROM users 
            ORDER BY created_at DESC 
            LIMIT 5
        `);

        // Get recent events
        const events = await db.query(`
            SELECT 'event_created' as type, id, 
                   CONCAT('Event created: ', title) as title,
                   title as description, created_at, status,
                   'System' as user_name
            FROM events 
            ORDER BY created_at DESC 
            LIMIT 5
        `);

        // Get recent donations
        const donations = await db.query(`
            SELECT 'donation_received' as type, id, 
                   CONCAT('Donation received: ₹', amount) as title,
                   CONCAT('₹', amount, ' donated by ', donor_name) as description,
                   created_at, status, donor_name as user_name
            FROM donations 
            WHERE status = 'completed'
            ORDER BY created_at DESC 
            LIMIT 5
        `);

        // Merge and sort all activities
        activities = [...complaints, ...users, ...events, ...donations];
        activities.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

        // Limit to requested number
        activities = activities.slice(0, limit);

        // Log activity
        await logActivity(user.id, 'activities_viewed', 'Dashboard activities viewed');

        res.json({
            success: true,
            message: 'Activities retrieved successfully',
            data: activities
        });

    } catch (error) {
        await logError(error, req.user?.id, 'dashboard_activities_error');
        console.error('Dashboard activities error:', error);
        res.status(500).json({
            success: false,
            message: 'Failed to retrieve activities'
        });
    }
}

/**
 * Get chart data for admin dashboard
 * Available to admin and moderator users
 */
async function getChartData(req, res) {
    try {
        const user = req.user;
        if (!user) {
            return res.status(401).json({
                success: false,
                message: 'Authentication required'
            });
        }

        // Check if user has admin or moderator role
        if (!['admin', 'moderator'].includes(user.role)) {
            return res.status(403).json({
                success: false,
                message: 'Admin access required'
            });
        }

        const period = req.query.period || '30';
        const chartData = {};

        // Determine date range based on period
        const days = parseInt(period);
        const dateFrom = new Date();
        dateFrom.setDate(dateFrom.getDate() - days);
        const dateFromStr = dateFrom.toISOString().split('T')[0];

        // Get user registration chart data
        const userRegistrations = await db.query(`
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM users 
            WHERE created_at >= ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        `, [dateFromStr]);

        // Format for chart
        chartData.users = userRegistrations.map(item => ({
            date: new Date(item.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }),
            count: parseInt(item.count),
            value: parseInt(item.count)
        }));

        // Get complaint status distribution
        const complaintStats = await db.query(`
            SELECT status, COUNT(*) as count
            FROM complaints 
            GROUP BY status
            ORDER BY count DESC
        `);

        // Format for chart
        chartData.complaints = complaintStats.map(item => ({
            status: item.status.charAt(0).toUpperCase() + item.status.slice(1),
            count: parseInt(item.count),
            value: parseInt(item.count)
        }));

        // Log activity
        await logActivity(user.id, 'chart_data_viewed', 'Dashboard chart data viewed');

        res.json({
            success: true,
            message: 'Chart data retrieved successfully',
            data: chartData
        });

    } catch (error) {
        await logError(error, req.user?.id, 'dashboard_chart_data_error');
        console.error('Dashboard chart data error:', error);
        res.status(500).json({
            success: false,
            message: 'Failed to retrieve chart data'
        });
    }
}

/**
 * Export dashboard report
 * Available to admin and moderator users
 */
async function exportReport(req, res) {
    try {
        const user = req.user;
        if (!user) {
            return res.status(401).json({
                success: false,
                message: 'Authentication required'
            });
        }

        // Check if user has admin or moderator role
        if (!['admin', 'moderator'].includes(user.role)) {
            return res.status(403).json({
                success: false,
                message: 'Admin access required'
            });
        }

        const format = req.query.format || 'csv';
        const currentDate = new Date().toISOString().split('T')[0];

        // Log activity
        await logActivity(user.id, 'report_exported', `Dashboard report exported in ${format} format`);

        if (format === 'pdf') {
            res.setHeader('Content-Type', 'application/pdf');
            res.setHeader('Content-Disposition', `attachment; filename="dashboard_report_${currentDate}.pdf"`);
            res.send(`PDF Report Content - Dashboard Statistics for ${currentDate}`);
        } else {
            res.setHeader('Content-Type', 'text/csv');
            res.setHeader('Content-Disposition', `attachment; filename="dashboard_report_${currentDate}.csv"`);
            
            const csvContent = [
                'Metric,Value',
                `Report Date,${currentDate}`,
                `Generated By,${user.first_name} ${user.last_name}`,
                'Format,CSV'
            ].join('\n');
            
            res.send(csvContent);
        }

    } catch (error) {
        await logError(error, req.user?.id, 'dashboard_export_error');
        console.error('Dashboard export error:', error);
        res.status(500).json({
            success: false,
            message: 'Failed to export report'
        });
    }
}

module.exports = {
    getStats,
    getAdminStats,
    getMemberStats,
    getActivities,
    getChartData,
    exportReport
};