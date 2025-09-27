/**
 * Analytics Controller
 * Handles analytics and reporting API endpoints including metrics, charts, and system performance
 * BHRC India - Human Rights Commission Web Application
 */

const { validationResult } = require('express-validator');
const db = require('../config/database');
const { logActivity, logError } = require('../middleware/logger');

class AnalyticsController {
    /**
     * Get key metrics with period comparison
     * Admin/Moderator access required
     */
    async getMetrics(req, res) {
        try {
            const { period = '30d', start_date, end_date } = req.query;
            
            // Get date ranges
            const dateRange = this.getDateRange(period, { start_date, end_date });
            const previousRange = this.getPreviousDateRange(period, dateRange);
            
            // Get current period metrics
            const currentMetrics = await this.calculatePeriodMetrics(dateRange.start, dateRange.end);
            
            // Get previous period metrics for comparison
            const previousMetrics = await this.calculatePeriodMetrics(previousRange.start, previousRange.end);
            
            // Calculate percentage changes
            const metrics = {
                totalDonations: currentMetrics.donations.total,
                donationChange: this.calculatePercentageChange(
                    previousMetrics.donations.total, 
                    currentMetrics.donations.total
                ),
                totalEvents: currentMetrics.events.total,
                eventChange: this.calculatePercentageChange(
                    previousMetrics.events.total, 
                    currentMetrics.events.total
                ),
                totalUsers: currentMetrics.users.total,
                userChange: this.calculatePercentageChange(
                    previousMetrics.users.total, 
                    currentMetrics.users.total
                ),
                totalRegistrations: currentMetrics.registrations.total,
                registrationChange: this.calculatePercentageChange(
                    previousMetrics.registrations.total, 
                    currentMetrics.registrations.total
                )
            };
            
            await logActivity(req.user.id, 'analytics_metrics_viewed', 'Viewed analytics metrics');
            
            res.json({
                success: true,
                data: metrics
            });
            
        } catch (error) {
            await logError('getMetrics', error.message, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch metrics'
            });
        }
    }
    
    /**
     * Get chart data for various analytics
     * Admin/Moderator access required
     */
    async getChartData(req, res) {
        try {
            const { period = '30d', start_date, end_date } = req.query;
            const dateRange = this.getDateRange(period, { start_date, end_date });
            
            const chartData = {
                donations: await this.getDonationTrendData(dateRange.start, dateRange.end),
                events: await this.getEventAnalyticsData(dateRange.start, dateRange.end),
                users: await this.getUserGrowthData(dateRange.start, dateRange.end),
                categories: await this.getDonationCategoryData(dateRange.start, dateRange.end)
            };
            
            await logActivity(req.user.id, 'analytics_charts_viewed', 'Viewed analytics charts');
            
            res.json({
                success: true,
                data: chartData
            });
            
        } catch (error) {
            await logError('getChartData', error.message, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch chart data'
            });
        }
    }
    
    /**
     * Get top donors for the period
     * Admin/Moderator access required
     */
    async getTopDonors(req, res) {
        try {
            const { period = '30d', limit = 10, start_date, end_date } = req.query;
            const limitValue = Math.min(parseInt(limit), 50);
            const dateRange = this.getDateRange(period, { start_date, end_date });
            
            const [rows] = await db.execute(`
                SELECT 
                    d.donor_name as name,
                    d.donor_email as email,
                    SUM(d.amount) as totalAmount,
                    COUNT(*) as donationCount,
                    MAX(d.created_at) as lastDonation
                FROM donations d
                WHERE d.created_at BETWEEN ? AND ?
                    AND d.status = 'completed'
                    AND d.is_anonymous = 0
                GROUP BY d.donor_email, d.donor_name
                HAVING d.donor_name IS NOT NULL AND d.donor_name != ''
                ORDER BY totalAmount DESC
                LIMIT ?
            `, [dateRange.start, dateRange.end, limitValue]);
            
            // Format the data
            const topDonors = rows.map(donor => ({
                ...donor,
                totalAmount: parseFloat(donor.totalAmount),
                donationCount: parseInt(donor.donationCount),
                id: require('crypto').createHash('md5').update(donor.email).digest('hex')
            }));
            
            await logActivity(req.user.id, 'analytics_top_donors_viewed', 'Viewed top donors analytics');
            
            res.json({
                success: true,
                data: topDonors
            });
            
        } catch (error) {
            await logError('getTopDonors', error.message, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch top donors'
            });
        }
    }
    
    /**
     * Get popular events for the period
     * Admin/Moderator access required
     */
    async getPopularEvents(req, res) {
        try {
            const { period = '30d', limit = 10, start_date, end_date } = req.query;
            const limitValue = Math.min(parseInt(limit), 50);
            const dateRange = this.getDateRange(period, { start_date, end_date });
            
            const [rows] = await db.execute(`
                SELECT 
                    e.id,
                    e.title,
                    e.type,
                    e.date,
                    COUNT(er.id) as registrations,
                    COALESCE(
                        ROUND(
                            (SUM(CASE WHEN er.status = 'attended' THEN 1 ELSE 0 END) * 100.0) / 
                            NULLIF(COUNT(er.id), 0), 
                            1
                        ), 
                        0
                    ) as attendanceRate
                FROM events e
                LEFT JOIN event_registrations er ON e.id = er.event_id
                WHERE e.created_at BETWEEN ? AND ?
                GROUP BY e.id, e.title, e.type, e.date
                ORDER BY registrations DESC, attendanceRate DESC
                LIMIT ?
            `, [dateRange.start, dateRange.end, limitValue]);
            
            // Format the data
            const popularEvents = rows.map(event => ({
                ...event,
                registrations: parseInt(event.registrations),
                attendanceRate: parseFloat(event.attendanceRate)
            }));
            
            await logActivity(req.user.id, 'analytics_popular_events_viewed', 'Viewed popular events analytics');
            
            res.json({
                success: true,
                data: popularEvents
            });
            
        } catch (error) {
            await logError('getPopularEvents', error.message, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch popular events'
            });
        }
    }
    
    /**
     * Get system performance metrics
     * Admin access required
     */
    async getSystemMetrics(req, res) {
        try {
            // Database performance metrics
            const dbMetrics = await this.getDatabaseMetrics();
            
            // API performance metrics (simulated for demo)
            const apiMetrics = {
                avgResponseTime: Math.floor(Math.random() * 150) + 50,
                requestsPerHour: Math.floor(Math.random() * 900) + 100,
                errorRate: Math.round((Math.random() * 5) * 10) / 10
            };
            
            // Storage metrics (simulated for demo)
            const storageMetrics = {
                usedStorage: Math.round((Math.random() * 70 + 10) * 10) / 10,
                availableStorage: Math.round((Math.random() * 30 + 20) * 10) / 10,
                storageUsagePercent: Math.floor(Math.random() * 60) + 20
            };
            
            const systemMetrics = { ...dbMetrics, ...apiMetrics, ...storageMetrics };
            
            await logActivity(req.user.id, 'analytics_system_metrics_viewed', 'Viewed system metrics');
            
            res.json({
                success: true,
                data: systemMetrics
            });
            
        } catch (error) {
            await logError('getSystemMetrics', error.message, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch system metrics'
            });
        }
    }
    
    /**
     * Get dashboard overview statistics
     */
    async getDashboardOverview(req, res) {
        try {
            const { start_date, end_date } = req.query;
            
            // Get date range (default to last 30 days)
            const endDate = end_date || new Date().toISOString().split('T')[0];
            const startDate = start_date || new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
            
            // Get total counts
            const [totalComplaints] = await db.execute("SELECT COUNT(*) as count FROM complaints");
            const [totalDonations] = await db.execute("SELECT COUNT(*) as count FROM donations");
            const [totalEvents] = await db.execute("SELECT COUNT(*) as count FROM events");
            const [totalUsers] = await db.execute("SELECT COUNT(*) as count FROM users");
            
            // Get recent activity counts
            const [recentComplaints] = await db.execute(
                "SELECT COUNT(*) as count FROM complaints WHERE created_at >= ?",
                [startDate]
            );
            const [recentDonations] = await db.execute(
                "SELECT COUNT(*) as count FROM donations WHERE created_at >= ?",
                [startDate]
            );
            const [recentEvents] = await db.execute(
                "SELECT COUNT(*) as count FROM events WHERE created_at >= ?",
                [startDate]
            );
            
            // Get donation amount
            const [totalDonationAmount] = await db.execute(
                "SELECT COALESCE(SUM(amount), 0) as total FROM donations WHERE status = 'completed'"
            );
            
            const overview = {
                totals: {
                    complaints: parseInt(totalComplaints[0].count),
                    donations: parseInt(totalDonations[0].count),
                    events: parseInt(totalEvents[0].count),
                    users: parseInt(totalUsers[0].count),
                    donation_amount: parseFloat(totalDonationAmount[0].total)
                },
                recent: {
                    complaints: parseInt(recentComplaints[0].count),
                    donations: parseInt(recentDonations[0].count),
                    events: parseInt(recentEvents[0].count),
                    period: `${startDate} to ${endDate}`
                }
            };
            
            await logActivity(req.user.id, 'dashboard_overview_viewed', 'Viewed dashboard overview');
            
            res.json({
                success: true,
                data: overview
            });
            
        } catch (error) {
            await logError('getDashboardOverview', error.message, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to fetch dashboard overview'
            });
        }
    }
    
    /**
     * Export analytics report
     * Admin access required
     */
    async exportReport(req, res) {
        try {
            const { period = '30d', format = 'csv', start_date, end_date } = req.query;
            const dateRange = this.getDateRange(period, { start_date, end_date });
            
            // Generate report data
            const reportData = await this.generateReportData(dateRange.start, dateRange.end);
            
            if (format === 'csv') {
                return this.generateCSVReport(res, reportData, dateRange);
            } else {
                return res.status(400).json({
                    success: false,
                    message: 'Only CSV format is currently supported'
                });
            }
            
        } catch (error) {
            await logError('exportReport', error.message, req.user?.id);
            res.status(500).json({
                success: false,
                message: 'Failed to export report'
            });
        }
    }
    
    /**
     * Calculate metrics for a specific period
     */
    async calculatePeriodMetrics(startDate, endDate) {
        // Donations metrics
        const [donations] = await db.execute(`
            SELECT 
                COUNT(*) as count,
                COALESCE(SUM(amount), 0) as total
            FROM donations 
            WHERE created_at BETWEEN ? AND ? AND status = 'completed'
        `, [startDate, endDate]);
        
        // Events metrics
        const [events] = await db.execute(`
            SELECT COUNT(*) as total
            FROM events 
            WHERE created_at BETWEEN ? AND ?
        `, [startDate, endDate]);
        
        // Users metrics
        const [users] = await db.execute(`
            SELECT COUNT(*) as total
            FROM users 
            WHERE created_at BETWEEN ? AND ?
        `, [startDate, endDate]);
        
        // Registrations metrics
        const [registrations] = await db.execute(`
            SELECT COUNT(*) as total
            FROM event_registrations 
            WHERE created_at BETWEEN ? AND ?
        `, [startDate, endDate]);
        
        return {
            donations: {
                count: parseInt(donations[0].count),
                total: parseFloat(donations[0].total)
            },
            events: {
                total: parseInt(events[0].total)
            },
            users: {
                total: parseInt(users[0].total)
            },
            registrations: {
                total: parseInt(registrations[0].total)
            }
        };
    }
    
    /**
     * Get donation trend data for charts
     */
    async getDonationTrendData(startDate, endDate) {
        const [rows] = await db.execute(`
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                COALESCE(SUM(amount), 0) as amount
            FROM donations 
            WHERE created_at BETWEEN ? AND ? 
                AND status = 'completed'
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        `, [startDate, endDate]);
        
        return rows.map(row => ({
            ...row,
            count: parseInt(row.count),
            amount: parseFloat(row.amount)
        }));
    }
    
    /**
     * Get event analytics data
     */
    async getEventAnalyticsData(startDate, endDate) {
        const [rows] = await db.execute(`
            SELECT 
                e.id,
                e.title,
                e.type,
                e.date,
                COUNT(er.id) as registrations,
                SUM(CASE WHEN er.status = 'attended' THEN 1 ELSE 0 END) as attendance
            FROM events e
            LEFT JOIN event_registrations er ON e.id = er.event_id
            WHERE e.created_at BETWEEN ? AND ?
            GROUP BY e.id, e.title, e.type, e.date
            ORDER BY e.date DESC
            LIMIT 10
        `, [startDate, endDate]);
        
        return rows.map(row => ({
            ...row,
            registrations: parseInt(row.registrations),
            attendance: parseInt(row.attendance)
        }));
    }
    
    /**
     * Get user growth data
     */
    async getUserGrowthData(startDate, endDate) {
        const [rows] = await db.execute(`
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
            FROM users 
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        `, [startDate, endDate]);
        
        return rows.map(row => ({
            ...row,
            count: parseInt(row.count)
        }));
    }
    
    /**
     * Get donation category data
     */
    async getDonationCategoryData(startDate, endDate) {
        const [rows] = await db.execute(`
            SELECT 
                category as name,
                COUNT(*) as count,
                COALESCE(SUM(amount), 0) as amount
            FROM donations 
            WHERE created_at BETWEEN ? AND ? 
                AND status = 'completed'
                AND category IS NOT NULL
            GROUP BY category
            ORDER BY amount DESC
        `, [startDate, endDate]);
        
        return rows.map(row => ({
            ...row,
            count: parseInt(row.count),
            amount: parseFloat(row.amount)
        }));
    }
    
    /**
     * Get database performance metrics
     */
    async getDatabaseMetrics() {
        try {
            // Get database size
            const [sizeResult] = await db.execute(`
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            `);
            
            // Get connection count (simulated)
            const activeConnections = Math.floor(Math.random() * 15) + 5;
            
            // Get average query time (simulated)
            const avgQueryTime = Math.floor(Math.random() * 90) + 10;
            
            return {
                databaseSize: parseFloat(sizeResult[0].size_mb || 0),
                activeConnections,
                avgQueryTime
            };
            
        } catch (error) {
            // Return default values if unable to get real metrics
            return {
                databaseSize: 0,
                activeConnections: 0,
                avgQueryTime: 0
            };
        }
    }
    
    /**
     * Generate CSV report
     */
    generateCSVReport(res, data, dateRange) {
        res.setHeader('Content-Type', 'text/csv');
        res.setHeader('Content-Disposition', 'attachment; filename="analytics-report.csv"');
        
        let csvContent = 'Metric,Value\n';
        csvContent += `Report Period,"${dateRange.start} to ${dateRange.end}"\n`;
        csvContent += `Total Donations,"$${data.totalDonations.toLocaleString()}"\n`;
        csvContent += `Total Events,${data.totalEvents}\n`;
        csvContent += `Total Users,${data.totalUsers}\n`;
        csvContent += `Total Registrations,${data.totalRegistrations}\n`;
        
        res.send(csvContent);
    }
    
    /**
     * Generate comprehensive report data
     */
    async generateReportData(startDate, endDate) {
        const metrics = await this.calculatePeriodMetrics(startDate, endDate);
        
        return {
            totalDonations: metrics.donations.total,
            totalEvents: metrics.events.total,
            totalUsers: metrics.users.total,
            totalRegistrations: metrics.registrations.total
        };
    }
    
    /**
     * Get date range based on period
     */
    getDateRange(period, params = {}) {
        const end = new Date();
        const start = new Date();
        
        if (period === 'custom' && params.start_date && params.end_date) {
            return {
                start: new Date(params.start_date).toISOString().split('T')[0] + ' 00:00:00',
                end: new Date(params.end_date).toISOString().split('T')[0] + ' 23:59:59'
            };
        }
        
        switch (period) {
            case '7d':
                start.setDate(start.getDate() - 7);
                break;
            case '30d':
                start.setDate(start.getDate() - 30);
                break;
            case '90d':
                start.setDate(start.getDate() - 90);
                break;
            case '1y':
                start.setFullYear(start.getFullYear() - 1);
                break;
            default:
                start.setDate(start.getDate() - 30);
        }
        
        return {
            start: start.toISOString().split('T')[0] + ' 00:00:00',
            end: end.toISOString().split('T')[0] + ' 23:59:59'
        };
    }
    
    /**
     * Get previous date range for comparison
     */
    getPreviousDateRange(period, currentRange) {
        const currentStart = new Date(currentRange.start);
        const currentEnd = new Date(currentRange.end);
        const duration = currentEnd.getTime() - currentStart.getTime();
        
        const previousEnd = new Date(currentStart.getTime() - 24 * 60 * 60 * 1000);
        const previousStart = new Date(previousEnd.getTime() - duration);
        
        return {
            start: previousStart.toISOString().split('T')[0] + ' 00:00:00',
            end: previousEnd.toISOString().split('T')[0] + ' 23:59:59'
        };
    }
    
    /**
     * Calculate percentage change
     */
    calculatePercentageChange(previous, current) {
        if (previous === 0) {
            return current > 0 ? 100 : 0;
        }
        
        return Math.round(((current - previous) / previous) * 100 * 10) / 10;
    }
}

module.exports = new AnalyticsController();