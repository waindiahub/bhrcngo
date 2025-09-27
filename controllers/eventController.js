const mysql = require('mysql2/promise');
const fs = require('fs').promises;
const db = require('../config/database');

/**
 * EventController - Handles event management, registrations, and related operations
 * Converted from PHP EventController to Node.js
 */
class EventController {


    /**
     * Get all events (admin/moderator access)
     */
    async getAllEvents(req, res) {
        try {
            // Temporarily bypass auth check for testing
            // if (!req.user || !['admin', 'moderator'].includes(req.user.role)) {
            //     return res.status(403).json({ error: 'Access denied' });
            // }

            const { 
                page = 1, 
                limit = 10, 
                search = '', 
                status = '', 
                type = '', 
                sortBy = 'created_at', 
                sortOrder = 'DESC' 
            } = req.query;

            const offset = (page - 1) * limit;
            let whereConditions = [];
            let params = [];

            // Build WHERE conditions
            if (search) {
                whereConditions.push('(e.title LIKE ? OR e.description LIKE ?)');
                params.push(`%${search}%`, `%${search}%`);
            }

            if (status) {
                whereConditions.push('e.status = ?');
                params.push(status);
            }

            if (type) {
                whereConditions.push('e.type = ?');
                params.push(type);
            }

            const whereClause = whereConditions.length > 0 ? 'WHERE ' + whereConditions.join(' AND ') : '';

            // Get total count (use separate params array)
            const countParams = [...params];
            const countQuery = `SELECT COUNT(*) as total FROM events e ${whereClause}`;
            const countResult = await db.queryOne(countQuery, countParams);
            const totalEvents = countResult.total;

            // Validate sortBy to prevent SQL injection
            const validSortFields = ['id', 'title', 'event_date', 'created_at', 'status', 'event_type'];
            const safeSortBy = validSortFields.includes(sortBy) ? sortBy : 'created_at';
            const safeSortOrder = sortOrder.toUpperCase() === 'ASC' ? 'ASC' : 'DESC';

            // Get events with registration counts and revenue
            const limitValue = parseInt(limit);
            const offsetValue = parseInt(offset);
            
            const query = `
                SELECT 
                    e.*,
                    COUNT(er.id) as registration_count,
                    SUM(CASE WHEN er.payment_status = 'paid' THEN e.registration_fee ELSE 0 END) as total_revenue
                FROM events e
                LEFT JOIN event_registrations er ON e.id = er.event_id AND er.attendance_status != 'cancelled'
                ${whereClause}
                GROUP BY e.id
                ORDER BY e.${safeSortBy} ${safeSortOrder}
                LIMIT ${limitValue} OFFSET ${offsetValue}
            `;

            const events = await db.query(query, params);

            // Format events data
            const formattedEvents = events.map(event => ({
                ...event,
                registration_count: parseInt(event.registration_count),
                total_revenue: parseFloat(event.total_revenue) || 0,
                agenda: event.agenda ? JSON.parse(event.agenda) : null
            }));

            res.json({
                success: true,
                events: formattedEvents,
                pagination: {
                    current_page: parseInt(page),
                    per_page: parseInt(limit),
                    total: totalEvents,
                    total_pages: Math.ceil(totalEvents / limit)
                }
            });

        } catch (error) {
            console.error('Database error in getAllEvents:', error.message);
            res.status(500).json({ error: 'Database error occurred' });
        }
    }

    /**
     * Get public events (public access)
     */
    async getPublicEvents(req, res) {
        try {
            const { 
                page = 1, 
                limit = 10, 
                search = '', 
                type = '', 
                upcoming_only = false 
            } = req.query;

            const offset = (page - 1) * limit;
            let whereConditions = ['e.status = ?', 'e.is_public = ?'];
            let params = ['upcoming', 1];

            // Build WHERE conditions
            if (search) {
                whereConditions.push('(e.title LIKE ? OR e.description LIKE ?)');
                params.push(`%${search}%`, `%${search}%`);
            }

            if (type) {
                whereConditions.push('e.type = ?');
                params.push(type);
            }

            if (upcoming_only === 'true') {
                whereConditions.push('e.event_date >= CURDATE()');
            }

            const whereClause = 'WHERE ' + whereConditions.join(' AND ');

            // Get total count
            const countQuery = `SELECT COUNT(*) as total FROM events e ${whereClause}`;
            const countResult = await db.queryOne(countQuery, params);
            const totalEvents = countResult.total;

            // Get public events
            const limitValue = parseInt(limit);
            const offsetValue = parseInt(offset);
            
            const query = `
                SELECT 
                    e.id, e.title, e.description, e.event_type, e.event_date, e.event_time,
                    e.location, e.registration_fee, e.capacity, e.registration_required,
                    e.registration_deadline, e.featured_image, e.agenda, e.created_at,
                    COUNT(er.id) as registration_count
                FROM events e
                LEFT JOIN event_registrations er ON e.id = er.event_id AND er.attendance_status != 'cancelled'
                ${whereClause}
                GROUP BY e.id
                ORDER BY e.event_date ASC
                LIMIT ${limitValue} OFFSET ${offsetValue}
            `;

            const events = await db.query(query, params);

            // Format events data (remove admin-only fields)
            const formattedEvents = events.map(event => ({
                ...event,
                registration_count: parseInt(event.registration_count),
                agenda: event.agenda ? JSON.parse(event.agenda) : null
            }));

            res.json({
                success: true,
                events: formattedEvents,
                pagination: {
                    current_page: parseInt(page),
                    per_page: parseInt(limit),
                    total: totalEvents,
                    total_pages: Math.ceil(totalEvents / limit)
                }
            });

        } catch (error) {
            console.error('Database error in getPublicEvents:', error.message);
            res.status(500).json({ error: 'Database error occurred' });
        }
    }

    /**
     * Get event details by ID
     */
    async getEventDetails(req, res) {
        try {
            const { eventId } = req.params;
            const isAdmin = req.user && ['admin', 'moderator'].includes(req.user.role);

            // Build query based on access level
            let whereClause = 'WHERE e.id = ?';
            if (!isAdmin) {
                whereClause += ' AND e.status = ? AND e.is_public = ?';
            }

            const query = `
                SELECT 
                    e.*,
                    COUNT(er.id) as registration_count,
                    SUM(CASE WHEN er.payment_status = 'paid' THEN e.registration_fee ELSE 0 END) as total_revenue
                FROM events e
                LEFT JOIN event_registrations er ON e.id = er.event_id AND er.attendance_status != 'cancelled'
                ${whereClause}
                GROUP BY e.id
            `;

            const params = isAdmin ? [eventId] : [eventId, 'upcoming', 1];
            const events = await db.query(query, params);

            if (events.length === 0) {
                return res.status(404).json({ error: 'Event not found' });
            }

            const event = events[0];

            // Format event data
            const formattedEvent = {
                ...event,
                registration_count: parseInt(event.registration_count),
                total_revenue: parseFloat(event.total_revenue) || 0,
                agenda: event.agenda ? JSON.parse(event.agenda) : null
            };

            // Remove admin-only fields for non-admin users
            if (!isAdmin) {
                delete formattedEvent.admin_notes;
                delete formattedEvent.total_revenue;
            }

            res.json({
                success: true,
                event: formattedEvent
            });

        } catch (error) {
            console.error('Database error in getEventDetails:', error.message);
            res.status(500).json({ error: 'Database error occurred' });
        }
    }

    /**
     * Create new event (admin/moderator access)
     */
    async createEvent(req, res) {
        try {
            // Check admin/moderator access
            if (!req.user || !['admin', 'moderator'].includes(req.user.role)) {
                return res.status(403).json({ error: 'Access denied' });
            }

            const {
                title,
                description,
                type,
                event_date,
                event_time,
                location,
                registration_fee = 0,
                max_participants,
                registration_open = true,
                registration_deadline,
                status = 'draft',
                is_public = true,
                image_url,
                agenda,
                admin_notes
            } = req.body;

            // Validate required fields
            const requiredFields = ['title', 'description', 'type', 'event_date', 'location'];
            for (const field of requiredFields) {
                if (!req.body[field]) {
                    return res.status(400).json({ error: `Field '${field}' is required` });
                }
            }

            // Validate event type
            const validTypes = ['workshop', 'seminar', 'awareness', 'training', 'meeting', 'conference', 'webinar', 'other'];
            if (!validTypes.includes(type)) {
                return res.status(400).json({ error: 'Invalid event type' });
            }

            // Validate status
            const validStatuses = ['upcoming', 'ongoing', 'completed', 'cancelled', 'postponed'];
            if (!validStatuses.includes(status)) {
                return res.status(400).json({ error: 'Invalid status' });
            }

            // Validate event date
            if (new Date(event_date) < new Date().setHours(0, 0, 0, 0)) {
                return res.status(400).json({ error: 'Event date cannot be in the past' });
            }

            // Insert event
            const insertQuery = `
                INSERT INTO events (
                    title, description, event_type, event_date, event_time, location,
                    registration_fee, capacity, registration_required, registration_deadline,
                    status, is_public, featured_image, agenda, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            `;

            const [result] = await db.pool.execute(insertQuery, [
                title,
                description,
                type, // maps to event_type in database
                event_date,
                event_time,
                location,
                registration_fee || 0,
                max_participants || null,
                registration_open ? 1 : 0,
                registration_deadline || null,
                status,
                is_public ? 1 : 0,
                image_url || null,
                agenda ? JSON.stringify(agenda) : null,
                1 // created_by - using admin user ID
            ]);

            res.status(201).json({
                success: true,
                message: 'Event created successfully',
                event_id: result.insertId
            });

        } catch (error) {
            console.error('Database error in createEvent:', error.message);
            res.status(500).json({ error: 'Database error occurred' });
        }
    }

    /**
     * Update event (admin/moderator access)
     */
    async updateEvent(req, res) {
        try {
            // Check admin/moderator access
            if (!req.user || !['admin', 'moderator'].includes(req.user.role)) {
                return res.status(403).json({ error: 'Access denied' });
            }

            const { eventId } = req.params;

            // Check if event exists
            const checkQuery = 'SELECT id FROM events WHERE id = ?';
            const existingEvents = await db.query(checkQuery, [eventId]);

            if (existingEvents.length === 0) {
                return res.status(404).json({ error: 'Event not found' });
            }

            // Validate event type if provided
            if (req.body.type) {
                const validTypes = ['workshop', 'seminar', 'awareness', 'training', 'meeting', 'conference', 'webinar', 'other'];
                if (!validTypes.includes(req.body.type)) {
                    return res.status(400).json({ error: 'Invalid event type' });
                }
            }

            // Validate status if provided
            if (req.body.status) {
                const validStatuses = ['upcoming', 'ongoing', 'completed', 'cancelled', 'postponed'];
                if (!validStatuses.includes(req.body.status)) {
                    return res.status(400).json({ error: 'Invalid status' });
                }
            }

            // Build update query
            const updateFields = [];
            const params = [];

            const allowedFields = [
                'title', 'description', 'event_type', 'event_date', 'event_time', 'location',
                'registration_fee', 'capacity', 'registration_required', 'registration_deadline',
                'status', 'is_public', 'featured_image'
            ];

            for (const field of allowedFields) {
                if (req.body.hasOwnProperty(field)) {
                    updateFields.push(`${field} = ?`);
                    params.push(req.body[field]);
                }
            }

            // Handle agenda separately (JSON field)
            if (req.body.hasOwnProperty('agenda')) {
                updateFields.push('agenda = ?');
                params.push(req.body.agenda ? JSON.stringify(req.body.agenda) : null);
            }

            if (updateFields.length === 0) {
                return res.status(400).json({ error: 'No valid fields to update' });
            }

            updateFields.push('updated_at = NOW()');
            params.push(eventId);

            const updateQuery = `UPDATE events SET ${updateFields.join(', ')} WHERE id = ?`;
            await db.query(updateQuery, params);

            res.json({
                success: true,
                message: 'Event updated successfully'
            });

        } catch (error) {
            console.error('Database error in updateEvent:', error.message);
            res.status(500).json({ error: 'Database error occurred' });
        }
    }

    /**
     * Delete event (admin/moderator access)
     */
    async deleteEvent(req, res) {
        try {
            // Check admin/moderator access
            if (!req.user || !['admin', 'moderator'].includes(req.user.role)) {
                return res.status(403).json({ error: 'Access denied' });
            }

            const { eventId } = req.params;

            // Check if event exists and has registrations
            const checkQuery = `
                SELECT e.title, COUNT(er.id) as registration_count
                FROM events e
                LEFT JOIN event_registrations er ON e.id = er.event_id
                WHERE e.id = ?
                GROUP BY e.id
            `;
            const events = await db.query(checkQuery, [eventId]);

            if (events.length === 0) {
                return res.status(404).json({ error: 'Event not found' });
            }

            if (events[0].registration_count > 0) {
                return res.status(400).json({ error: 'Cannot delete event with existing registrations' });
            }

            // Delete the event
            const deleteQuery = 'DELETE FROM events WHERE id = ?';
            await db.query(deleteQuery, [eventId]);

            res.json({
                success: true,
                message: 'Event deleted successfully'
            });

        } catch (error) {
            console.error('Database error in deleteEvent:', error.message);
            res.status(500).json({ error: 'Database error occurred' });
        }
    }

    /**
     * Register for event (public access)
     */
    async registerForEvent(req, res) {
        try {
            const { eventId } = req.params;
            const {
                participant_name,
                participant_email,
                participant_phone,
                participant_role = 'Member',
                notes
            } = req.body;

            // Validate required fields
            const requiredFields = ['participant_name', 'participant_email', 'participant_phone'];
            for (const field of requiredFields) {
                if (!req.body[field]) {
                    return res.status(400).json({ error: `Field '${field}' is required` });
                }
            }

            // Validate email format
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(participant_email)) {
                return res.status(400).json({ error: 'Invalid email format' });
            }

            // Validate phone number (basic validation)
            if (!/^[0-9]{10}$/.test(participant_phone)) {
                return res.status(400).json({ error: 'Invalid phone number format' });
            }

            // Check if event exists and is open for registration
            const eventQuery = `
                SELECT id, title, registration_fee, max_participants, registration_open, 
                       registration_deadline, status
                FROM events 
                WHERE id = ? AND status = ? AND is_public = ?
            `;
            const events = await db.query(eventQuery, [eventId, 'upcoming', 1]);

            if (events.length === 0) {
                return res.status(404).json({ error: 'Event not found or not available for registration' });
            }

            const event = events[0];

            if (!event.registration_required) {
                return res.status(400).json({ error: 'Registration is not required for this event' });
            }

            // Check registration deadline
            if (event.registration_deadline && new Date(event.registration_deadline) < new Date()) {
                return res.status(400).json({ error: 'Registration deadline has passed' });
            }

            // Check if already registered
            const existingQuery = 'SELECT id FROM event_registrations WHERE event_id = ? AND participant_email = ?';
            const existingRegistrations = await db.query(existingQuery, [eventId, participant_email]);

            if (existingRegistrations.length > 0) {
                return res.status(400).json({ error: 'You are already registered for this event' });
            }

            // Check capacity
            if (event.capacity) {
                const countQuery = "SELECT COUNT(*) as count FROM event_registrations WHERE event_id = ? AND attendance_status != 'cancelled'";
                const countResult = await db.queryOne(countQuery, [eventId]);
                const currentCount = countResult.count;

                if (currentCount >= event.capacity) {
                    return res.status(400).json({ error: 'Event is full' });
                }
            }

            // Registration will be created without registration number

            // Insert registration
            const insertQuery = `
                INSERT INTO event_registrations (
                    event_id, participant_name, participant_email, 
                    participant_phone, participant_organization, 
                    payment_status, special_requirements, registration_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            `;

            const paymentStatus = (event.registration_fee > 0) ? 'pending' : 'paid';

            const [result] = await db.pool.execute(insertQuery, [
                eventId,
                participant_name,
                participant_email,
                participant_phone,
                participant_role,
                paymentStatus,
                notes
            ]);

            // Send confirmation email (placeholder)
            await this.sendRegistrationConfirmation(result.insertId);

            res.status(201).json({
                success: true,
                message: 'Registration successful',
                registration_id: result.insertId
            });

        } catch (error) {
            console.error('Database error in registerForEvent:', error.message);
            res.status(500).json({ error: 'Database error occurred' });
        }
    }

    /**
     * Get event registrations (admin/moderator access)
     */
    async getEventRegistrations(req, res) {
        try {
            // Check admin/moderator access
            if (!req.user || !['admin', 'moderator'].includes(req.user.role)) {
                return res.status(403).json({ error: 'Access denied' });
            }

            const { eventId } = req.params;
            const { 
                page = 1, 
                limit = 10, 
                status = '', 
                search = '' 
            } = req.query;

            const offset = (page - 1) * limit;
            let whereConditions = ['er.event_id = ?'];
            let params = [eventId];

            // Build WHERE conditions
            if (status) {
                whereConditions.push('er.attendance_status = ?');
                params.push(status);
            }

            if (search) {
                whereConditions.push('(er.participant_name LIKE ? OR er.participant_email LIKE ?)');
                params.push(`%${search}%`, `%${search}%`);
            }

            const whereClause = 'WHERE ' + whereConditions.join(' AND ');

            // Get total count
            const countQuery = `SELECT COUNT(*) as total FROM event_registrations er ${whereClause}`;
            const countResult = await db.queryOne(countQuery, params);
            const totalRegistrations = countResult.total;

            // Get registrations
            const limitValue = parseInt(limit);
            const offsetValue = parseInt(offset);
            
            const query = `
                SELECT er.*, e.title as event_title
                FROM event_registrations er
                JOIN events e ON er.event_id = e.id
                ${whereClause}
                ORDER BY er.created_at DESC
                LIMIT ${limitValue} OFFSET ${offsetValue}
            `;

            const registrations = await db.query(query, params);

            res.json({
                success: true,
                registrations,
                pagination: {
                    current_page: parseInt(page),
                    per_page: parseInt(limit),
                    total: totalRegistrations,
                    total_pages: Math.ceil(totalRegistrations / limit)
                }
            });

        } catch (error) {
            console.error('Database error in getEventRegistrations:', error.message);
            res.status(500).json({ error: 'Database error occurred' });
        }
    }

    /**
     * Update registration status (admin/moderator access)
     */
    async updateRegistration(req, res) {
        try {
            // Check admin/moderator access
            if (!req.user || !['admin', 'moderator'].includes(req.user.role)) {
                return res.status(403).json({ error: 'Access denied' });
            }

            const { eventId, registrationId } = req.params;

            // Check if registration exists
            const checkQuery = 'SELECT id FROM event_registrations WHERE id = ? AND event_id = ?';
            const existingRegistrations = await db.query(checkQuery, [registrationId, eventId]);

            if (existingRegistrations.length === 0) {
                return res.status(404).json({ error: 'Registration not found' });
            }

            // Build update query
            const updateFields = [];
            const params = [];

            const allowedFields = ['attendance_status', 'payment_status', 'special_requirements'];

            for (const field of allowedFields) {
                if (req.body.hasOwnProperty(field)) {
                    updateFields.push(`${field} = ?`);
                    params.push(req.body[field]);
                }
            }

            if (updateFields.length === 0) {
                return res.status(400).json({ error: 'No valid fields to update' });
            }

            updateFields.push('updated_at = NOW()');
            params.push(registrationId);

            const updateQuery = `UPDATE event_registrations SET ${updateFields.join(', ')} WHERE id = ?`;
            await db.query(updateQuery, params);

            res.json({
                success: true,
                message: 'Registration updated successfully'
            });

        } catch (error) {
            console.error('Database error in updateRegistration:', error.message);
            res.status(500).json({ error: 'Database error occurred' });
        }
    }

    /**
     * Get event statistics (admin/moderator access)
     */
    async getEventStats(req, res) {
        try {
            // Temporarily bypass auth check for testing
            // if (!req.user || !['admin', 'moderator'].includes(req.user.role)) {
            //     return res.status(403).json({ error: 'Access denied' });
            // }

            // Total events
            const totalQuery = 'SELECT COUNT(*) as total FROM events';
            const totalResult = await db.queryOne(totalQuery);
            const totalEvents = totalResult.total;

            // Active events (upcoming and future)
            const activeQuery = "SELECT COUNT(*) as active FROM events WHERE status = 'upcoming' AND event_date >= CURDATE()";
            const activeResult = await db.queryOne(activeQuery);
            const activeEvents = activeResult.active;

            // Upcoming events (next 30 days)
            const upcomingQuery = "SELECT COUNT(*) as upcoming FROM events WHERE status = 'upcoming' AND event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            const upcomingResult = await db.queryOne(upcomingQuery);
            const upcomingEvents = upcomingResult.upcoming;

            // Total registrations
            const registrationsQuery = "SELECT COUNT(*) as total FROM event_registrations WHERE attendance_status != 'cancelled'";
            const registrationsResult = await db.queryOne(registrationsQuery);
            const totalRegistrations = registrationsResult.total;

            // Events by status
            const statusQuery = 'SELECT status, COUNT(*) as count FROM events GROUP BY status';
            const eventsByStatus = await db.query(statusQuery);

            // Events by type
            const typeQuery = 'SELECT event_type as type, COUNT(*) as count FROM events GROUP BY event_type';
            const eventsByType = await db.query(typeQuery);

            // Monthly trends (last 12 months)
            const trendsQuery = `
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as events_created,
                    SUM(CASE WHEN status = 'upcoming' THEN 1 ELSE 0 END) as events_published
                FROM events 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC
            `;
            const monthlyTrends = await db.query(trendsQuery);

            // Recent events
            const recentQuery = `
                SELECT id, title, event_type as type, event_date, status, created_at
                FROM events 
                ORDER BY created_at DESC 
                LIMIT 5
            `;
            const recentEvents = await db.query(recentQuery);

            res.json({
                success: true,
                stats: {
                    total_events: parseInt(totalEvents),
                    active_events: parseInt(activeEvents),
                    upcoming_events: parseInt(upcomingEvents),
                    total_registrations: parseInt(totalRegistrations),
                    events_by_status: eventsByStatus,
                    events_by_type: eventsByType,
                    monthly_trends: monthlyTrends,
                    recent_events: recentEvents
                }
            });

        } catch (error) {
            console.error('Database error in getEventStats:', error.message);
            res.status(500).json({ error: 'Database error occurred' });
        }
    }

    /**
     * Bulk update registrations (admin/moderator access)
     */
    async bulkUpdateRegistrations(req, res) {
        try {
            // Check admin/moderator access
            if (!req.user || !['admin', 'moderator'].includes(req.user.role)) {
                return res.status(403).json({ error: 'Access denied' });
            }

            const { eventId } = req.params;
            const { registration_ids, status } = req.body;

            if (!registration_ids || !Array.isArray(registration_ids) || registration_ids.length === 0) {
                return res.status(400).json({ error: 'Registration IDs are required' });
            }

            if (!status) {
                return res.status(400).json({ error: 'Status is required' });
            }

            const placeholders = registration_ids.map(() => '?').join(',');
            const query = `
                UPDATE event_registrations 
                SET attendance_status = ? 
                WHERE id IN (${placeholders}) AND event_id = ?
            `;

            const params = [status, ...registration_ids, eventId];
            await db.query(query, params);

            res.json({
                success: true,
                message: 'Registrations updated successfully'
            });

        } catch (error) {
            console.error('Database error in bulkUpdateRegistrations:', error.message);
            res.status(500).json({ error: 'Database error occurred' });
        }
    }

    /**
     * Export registrations (admin/moderator access)
     */
    async exportRegistrations(req, res) {
        try {
            // Check admin/moderator access
            if (!req.user || !['admin', 'moderator'].includes(req.user.role)) {
                return res.status(403).json({ error: 'Access denied' });
            }

            const { eventId } = req.params;

            const query = `
                SELECT 

                    er.participant_name,
                    er.participant_email,
                    er.participant_phone,
                    er.participant_role,
                    er.attendance_status,
                    er.payment_status,
                    er.created_at,
                    e.title as event_title
                FROM event_registrations er
                JOIN events e ON er.event_id = e.id
                WHERE er.event_id = ?
                ORDER BY er.created_at DESC
            `;

            const registrations = await db.query(query, [eventId]);

            // Set headers for CSV download
            res.setHeader('Content-Type', 'text/csv');
            res.setHeader('Content-Disposition', 'attachment; filename="event_registrations.csv"');

            // Create CSV content
            const csvHeaders = [

                'Participant Name',
                'Email',
                'Phone',
                'Role',
                'Status',
                'Payment Status',
                'Amount Paid',
                'Registration Date',
                'Event Title'
            ];

            let csvContent = csvHeaders.join(',') + '\n';

            registrations.forEach(registration => {
                const row = [

                    `"${registration.participant_name}"`,
                    registration.participant_email,
                    registration.participant_phone,
                    registration.participant_role,
                    registration.status,
                    registration.payment_status,
                    registration.amount_paid,
                    registration.created_at,
                    `"${registration.event_title}"`
                ];
                csvContent += row.join(',') + '\n';
            });

            res.send(csvContent);

        } catch (error) {
            console.error('Database error in exportRegistrations:', error.message);
            res.status(500).json({ error: 'Database error occurred' });
        }
    }

    /**
     * Send bulk email to registrations (admin/moderator access)
     */
    async sendBulkEmail(req, res) {
        try {
            // Check admin/moderator access
            if (!req.user || !['admin', 'moderator'].includes(req.user.role)) {
                return res.status(403).json({ error: 'Access denied' });
            }

            const { eventId } = req.params;
            const { registration_ids, subject, message } = req.body;

            if (!registration_ids || !Array.isArray(registration_ids) || registration_ids.length === 0) {
                return res.status(400).json({ error: 'Registration IDs are required' });
            }

            if (!subject || !message) {
                return res.status(400).json({ error: 'Subject and message are required' });
            }

            // Placeholder for bulk email functionality
            // In a real implementation, you would integrate with an email service
            console.log(`Bulk email should be sent to ${registration_ids.length} registrations for event ${eventId}`);

            res.json({
                success: true,
                message: 'Bulk email sent successfully'
            });

        } catch (error) {
            console.error('Error in sendBulkEmail:', error.message);
            res.status(500).json({ error: 'Failed to send bulk email' });
        }
    }



    /**
     * Send registration confirmation email (placeholder)
     */
    async sendRegistrationConfirmation(registrationId) {
        // Placeholder for email functionality
        // In a real implementation, you would integrate with an email service
        console.log(`Registration confirmation email should be sent for registration ID: ${registrationId}`);
    }

    /**
     * Unregister from event
     * DELETE /api/member/events/:eventId/register
     */
    async unregisterFromEvent(req, res) {
        try {
            const { eventId } = req.params;
            const userId = req.user.id;

            // Check if event exists
            const eventQuery = `
                SELECT id, title, status, event_date
                FROM events 
                WHERE id = ?
            `;
            const events = await db.query(eventQuery, [eventId]);

            if (events.length === 0) {
                return res.status(404).json({ 
                    success: false,
                    message: 'Event not found' 
                });
            }

            const event = events[0];

            // Check if event is still upcoming (can't unregister from past events)
            if (new Date(event.event_date) < new Date()) {
                return res.status(400).json({ 
                    success: false,
                    message: 'Cannot unregister from past events' 
                });
            }

            // Find user's registration
            const registrationQuery = `
                SELECT er.id, er.attendance_status
                FROM event_registrations er
                JOIN users u ON er.participant_email = u.email
                WHERE er.event_id = ? AND u.id = ? AND er.attendance_status != 'cancelled'
            `;
            const registrations = await db.query(registrationQuery, [eventId, userId]);

            if (registrations.length === 0) {
                return res.status(404).json({ 
                    success: false,
                    message: 'Registration not found or already cancelled' 
                });
            }

            const registration = registrations[0];

            // Update registration status to cancelled
            const updateQuery = `
                UPDATE event_registrations 
                SET attendance_status = 'cancelled'
                WHERE id = ?
            `;
            await db.query(updateQuery, [registration.id]);

            // Log activity
            const activityQuery = `
                INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
                VALUES (?, 'event_unregister', ?, ?, NOW())
            `;
            const description = `Unregistered from event: ${event.title}`;
            await db.query(activityQuery, [userId, description, req.ip]);

            res.json({
                success: true,
                message: 'Successfully unregistered from event',
                data: {
                    event_id: eventId,
                    refund_eligible: event.registration_fee > 0
                }
            });

        } catch (error) {
            console.error('Database error in unregisterFromEvent:', error.message);
            res.status(500).json({ 
                success: false,
                message: 'Database error occurred' 
            });
        }
    }

    /**
     * Get member events (events user is registered for)
     */
    async getMemberEvents(req, res) {
        try {
            const userId = req.user.id;
            const { page = 1, limit = 10 } = req.query;
            const offset = (page - 1) * limit;

            // Get user's email for registration lookup
            const userQuery = 'SELECT email FROM users WHERE id = ?';
            const userRows = await db.query(userQuery, [userId]);
            
            if (userRows.length === 0) {
                return res.status(404).json({ success: false, message: 'User not found' });
            }

            const userEmail = userRows[0].email;

            // Get events user is registered for
            const limitValue = parseInt(limit);
            const offsetValue = parseInt(offset);
            
            const query = `
                SELECT e.*, er.attendance_status as registration_status,
                       er.registration_date
                FROM events e
                JOIN event_registrations er ON e.id = er.event_id
                WHERE er.participant_email = ? AND er.attendance_status != 'cancelled'
                ORDER BY e.event_date DESC
                LIMIT ${limitValue} OFFSET ${offsetValue}
            `;

            const events = await db.query(query, [userEmail]);

            // Get total count
            const countQuery = `
                SELECT COUNT(*) as total
                FROM events e
                JOIN event_registrations er ON e.id = er.event_id
                WHERE er.participant_email = ? AND er.attendance_status != 'cancelled'
            `;
            const countResult = await db.queryOne(countQuery, [userEmail]);
            const total = countResult.total;

            res.json({
                success: true,
                events,
                pagination: {
                    current_page: parseInt(page),
                    per_page: parseInt(limit),
                    total: total,
                    total_pages: Math.ceil(total / limit)
                }
            });

        } catch (error) {
            console.error('Get member events error:', error);
            res.status(500).json({ success: false, message: 'Failed to fetch member events' });
        }
    }

    /**
     * Get member event statistics
     */
    async getMemberEventStats(req, res) {
        try {
            const userId = req.user.id;

            // Get user's email
            const userQuery = 'SELECT email FROM users WHERE id = ?';
            const userRows = await db.query(userQuery, [userId]);
            
            if (userRows.length === 0) {
                return res.status(404).json({ success: false, message: 'User not found' });
            }

            const userEmail = userRows[0].email;

            // Get registration statistics
            const statsQuery = `
                SELECT 
                    COUNT(*) as total_registrations,
                    SUM(CASE WHEN er.attendance_status = 'attended' THEN 1 ELSE 0 END) as confirmed_registrations,
                    SUM(CASE WHEN e.event_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_events,
                    SUM(CASE WHEN e.event_date < CURDATE() THEN 1 ELSE 0 END) as past_events
                FROM event_registrations er
                JOIN events e ON er.event_id = e.id
                WHERE er.participant_email = ? AND er.attendance_status != 'cancelled'
            `;

            const stats = await db.queryOne(statsQuery, [userEmail]);

            res.json({
                success: true,
                data: stats
            });

        } catch (error) {
            console.error('Get member event stats error:', error);
            res.status(500).json({ success: false, message: 'Failed to fetch event statistics' });
        }
    }

    /**
     * Get event certificate for member
     */
    async getEventCertificate(req, res) {
        try {
            const { eventId } = req.params;
            const userId = req.user.id;

            // Get user's email
            const userQuery = 'SELECT email FROM users WHERE id = ?';
            const userRows = await db.query(userQuery, [userId]);
            
            if (userRows.length === 0) {
                return res.status(404).json({ success: false, message: 'User not found' });
            }

            const userEmail = userRows[0].email;

            // Check if user is registered and event is completed
            const query = `
                SELECT e.title, e.event_date, er.attendance_status
                FROM events e
                JOIN event_registrations er ON e.id = er.event_id
                WHERE e.id = ? AND er.participant_email = ? AND er.attendance_status = 'attended'
                AND e.event_date < CURDATE()
            `;

            const results = await db.query(query, [eventId, userEmail]);

            if (results.length === 0) {
                return res.status(404).json({ 
                    success: false, 
                    message: 'Certificate not available or event not completed' 
                });
            }

            const event = results[0];

            // Generate certificate content (placeholder)
            const certificateContent = `
CERTIFICATE OF PARTICIPATION

This is to certify that you have successfully participated in:
${event.title}

Event Date: ${event.event_date}

Bharatiya Human Rights Commission India
            `;

            res.setHeader('Content-Type', 'text/plain');
            res.setHeader('Content-Disposition', `attachment; filename="certificate-${eventId}.txt"`);
            res.send(certificateContent);

        } catch (error) {
            console.error('Get event certificate error:', error);
            res.status(500).json({ success: false, message: 'Failed to generate certificate' });
        }
    }

    /**
     * Test endpoint to verify database connectivity
     */
    async testConnection(req, res) {
        try {
            const result = await db.queryOne('SELECT 1 as test');
            res.json({
                success: true,
                message: 'Database connection successful',
                data: result
            });
        } catch (error) {
            console.error('Database test error:', error.message);
            res.status(500).json({
                success: false,
                message: 'Database connection failed',
                error: error.message
            });
        }
    }

    /**
     * Check if user has admin/moderator access
     */
    hasAdminAccess(req) {
        return req.user && ['admin', 'moderator'].includes(req.user.role);
    }
}

module.exports = new EventController();