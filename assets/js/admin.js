/**
 * BHRC Admin Dashboard JavaScript
 * Handles all admin functionality including authentication, data management, and UI interactions
 */

class AdminDashboard {
    constructor() {
        this.apiBase = '/api';
        this.currentUser = null;
        this.currentSection = 'dashboard';
        this.charts = {};
        this.notifications = [];
        this.init();
    }

    /**
     * Initialize the admin dashboard
     */
    async init() {
        try {
            // Check authentication
            await this.checkAuth();
            
            // Initialize UI components
            this.initializeUI();
            
            // Load dashboard data
            await this.loadDashboardData();
            
            // Set up event listeners
            this.setupEventListeners();
            
            // Start periodic updates
            this.startPeriodicUpdates();
            
        } catch (error) {
            console.error('Failed to initialize admin dashboard:', error);
            this.redirectToLogin();
        }
    }

    /**
     * Check user authentication
     */
    async checkAuth() {
        try {
            const response = await this.apiCall('GET', '/auth/me');
            if (response.success) {
                this.currentUser = response.user;
                this.updateUserInfo();
                
                // Check admin permissions
                if (!['admin', 'super_admin', 'moderator'].includes(this.currentUser.role)) {
                    throw new Error('Insufficient permissions');
                }
            } else {
                throw new Error('Not authenticated');
            }
        } catch (error) {
            throw error;
        }
    }

    /**
     * Update user information in UI
     */
    updateUserInfo() {
        const adminName = document.getElementById('adminName');
        const adminRole = document.getElementById('adminRole');
        
        if (adminName) adminName.textContent = this.currentUser.name || 'Admin';
        if (adminRole) adminRole.textContent = this.currentUser.role || 'Administrator';
    }

    /**
     * Initialize UI components
     */
    initializeUI() {
        // Initialize sidebar
        this.initializeSidebar();
        
        // Initialize dropdowns
        this.initializeDropdowns();
        
        // Initialize modals
        this.initializeModals();
        
        // Initialize tooltips
        this.initializeTooltips();
    }

    /**
     * Initialize sidebar navigation
     */
    initializeSidebar() {
        const navLinks = document.querySelectorAll('.nav-link');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('adminSidebar');

        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = link.dataset.section;
                if (section) {
                    this.switchSection(section);
                }
            });
        });

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
            });
        }
    }

    /**
     * Initialize dropdown menus
     */
    initializeDropdowns() {
        // User dropdown
        const userDropdownBtn = document.getElementById('userDropdownBtn');
        const userDropdownMenu = document.getElementById('userDropdownMenu');
        
        if (userDropdownBtn && userDropdownMenu) {
            userDropdownBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdownMenu.classList.toggle('active');
            });
        }

        // Notification dropdown
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationDropdown = document.getElementById('notificationDropdown');
        
        if (notificationBtn && notificationDropdown) {
            notificationBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                notificationDropdown.classList.toggle('active');
                this.loadNotifications();
            });
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', () => {
            document.querySelectorAll('.dropdown-menu, .notification-dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        });

        // Logout functionality
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.logout();
            });
        }
    }

    /**
     * Initialize modal functionality
     */
    initializeModals() {
        const modalOverlay = document.getElementById('modalOverlay');
        
        if (modalOverlay) {
            modalOverlay.addEventListener('click', (e) => {
                if (e.target === modalOverlay) {
                    this.closeModal();
                }
            });
        }
    }

    /**
     * Initialize tooltips
     */
    initializeTooltips() {
        // Add tooltip functionality for elements with data-tooltip attribute
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            element.addEventListener('mouseenter', (e) => {
                this.showTooltip(e.target, e.target.dataset.tooltip);
            });
            
            element.addEventListener('mouseleave', () => {
                this.hideTooltip();
            });
        });
    }

    /**
     * Switch between dashboard sections
     */
    async switchSection(sectionName) {
        // Update navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });
        
        document.querySelector(`[data-section="${sectionName}"]`).closest('.nav-item').classList.add('active');

        // Update sections
        document.querySelectorAll('.admin-section').forEach(section => {
            section.classList.remove('active');
        });
        
        const targetSection = document.getElementById(`${sectionName}Section`);
        if (targetSection) {
            targetSection.classList.add('active');
            this.currentSection = sectionName;
            
            // Load section data
            await this.loadSectionData(sectionName);
        }
    }

    /**
     * Load data for specific section
     */
    async loadSectionData(sectionName) {
        this.showLoading();
        
        try {
            switch (sectionName) {
                case 'dashboard':
                    await this.loadDashboardData();
                    break;
                case 'complaints':
                    await this.loadComplaintsData();
                    break;
                case 'events':
                    await this.loadEventsData();
                    break;
                case 'activities':
                    await this.loadActivitiesData();
                    break;
                case 'members':
                    await this.loadMembersData();
                    break;
                case 'donations':
                    await this.loadDonationsData();
                    break;
                case 'gallery':
                    await this.loadGalleryData();
                    break;
                case 'users':
                    await this.loadUsersData();
                    break;
                case 'reports':
                    await this.loadReportsData();
                    break;
                case 'settings':
                    await this.loadSettingsData();
                    break;
            }
        } catch (error) {
            console.error(`Failed to load ${sectionName} data:`, error);
            this.showToast('Error', `Failed to load ${sectionName} data`, 'error');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Load dashboard data
     */
    async loadDashboardData() {
        try {
            // Load statistics
            const [complaints, events, members, donations] = await Promise.all([
                this.apiCall('GET', '/complaints?stats=true'),
                this.apiCall('GET', '/events?stats=true'),
                this.apiCall('GET', '/members?stats=true'),
                this.apiCall('GET', '/donations?stats=true')
            ]);

            // Update stats cards
            this.updateStatsCards({
                complaints: complaints.total || 0,
                events: events.total || 0,
                members: members.total || 0,
                donations: donations.total_amount || 0
            });

            // Load charts
            await this.loadCharts();
            
            // Load recent activities
            await this.loadRecentActivities();
            
            // Update system status
            this.updateSystemStatus();

        } catch (error) {
            console.error('Failed to load dashboard data:', error);
            throw error;
        }
    }

    /**
     * Update statistics cards
     */
    updateStatsCards(stats) {
        const elements = {
            totalComplaints: document.getElementById('totalComplaints'),
            totalEvents: document.getElementById('totalEvents'),
            totalMembers: document.getElementById('totalMembers'),
            totalDonations: document.getElementById('totalDonations')
        };

        if (elements.totalComplaints) elements.totalComplaints.textContent = stats.complaints;
        if (elements.totalEvents) elements.totalEvents.textContent = stats.events;
        if (elements.totalMembers) elements.totalMembers.textContent = stats.members;
        if (elements.totalDonations) elements.totalDonations.textContent = `â‚¹${this.formatNumber(stats.donations)}`;
    }

    /**
     * Load and render charts
     */
    async loadCharts() {
        try {
            // Load complaints chart data
            const complaintsData = await this.apiCall('GET', '/complaints?chart=true&period=30');
            this.renderComplaintsChart(complaintsData);

            // Load donations chart data
            const donationsData = await this.apiCall('GET', '/donations?chart=true&period=30');
            this.renderDonationsChart(donationsData);

        } catch (error) {
            console.error('Failed to load chart data:', error);
        }
    }

    /**
     * Render complaints chart
     */
    renderComplaintsChart(data) {
        const ctx = document.getElementById('complaintsChart');
        if (!ctx) return;

        if (this.charts.complaints) {
            this.charts.complaints.destroy();
        }

        this.charts.complaints = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels || [],
                datasets: [{
                    label: 'Complaints',
                    data: data.values || [],
                    borderColor: '#ff6b6b',
                    backgroundColor: 'rgba(255, 107, 107, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    /**
     * Render donations chart
     */
    renderDonationsChart(data) {
        const ctx = document.getElementById('donationsChart');
        if (!ctx) return;

        if (this.charts.donations) {
            this.charts.donations.destroy();
        }

        this.charts.donations = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels || [],
                datasets: [{
                    label: 'Donations (â‚¹)',
                    data: data.values || [],
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: '#667eea',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'â‚¹' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Load recent activities
     */
    async loadRecentActivities() {
        try {
            const [recentComplaints, upcomingEvents, recentDonations] = await Promise.all([
                this.apiCall('GET', '/complaints?limit=5&sort=created_at&order=desc'),
                this.apiCall('GET', '/events?limit=5&upcoming=true'),
                this.apiCall('GET', '/donations?limit=5&sort=created_at&order=desc')
            ]);

            this.renderRecentComplaints(recentComplaints.data || []);
            this.renderUpcomingEvents(upcomingEvents.data || []);
            this.renderRecentDonations(recentDonations.data || []);

        } catch (error) {
            console.error('Failed to load recent activities:', error);
        }
    }

    /**
     * Render recent complaints
     */
    renderRecentComplaints(complaints) {
        const container = document.getElementById('recentComplaints');
        if (!container) return;

        if (complaints.length === 0) {
            container.innerHTML = '<p class="text-muted">No recent complaints</p>';
            return;
        }

        container.innerHTML = complaints.map(complaint => `
            <div class="recent-item">
                <div class="recent-icon" style="background: #ff6b6b;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="recent-content">
                    <div class="recent-title">${this.truncateText(complaint.subject || 'No Subject', 30)}</div>
                    <div class="recent-meta">
                        ${complaint.complainant_name || 'Anonymous'} â€¢ ${this.formatDate(complaint.created_at)}
                    </div>
                </div>
                <span class="badge badge-${this.getStatusColor(complaint.status)}">${complaint.status}</span>
            </div>
        `).join('');
    }

    /**
     * Render upcoming events
     */
    renderUpcomingEvents(events) {
        const container = document.getElementById('upcomingEvents');
        if (!container) return;

        if (events.length === 0) {
            container.innerHTML = '<p class="text-muted">No upcoming events</p>';
            return;
        }

        container.innerHTML = events.map(event => `
            <div class="recent-item">
                <div class="recent-icon" style="background: #4ecdc4;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="recent-content">
                    <div class="recent-title">${this.truncateText(event.title, 30)}</div>
                    <div class="recent-meta">
                        ${this.formatDate(event.event_date)} â€¢ ${event.location || 'TBD'}
                    </div>
                </div>
                <span class="badge badge-info">${event.registrations_count || 0} registered</span>
            </div>
        `).join('');
    }

    /**
     * Render recent donations
     */
    renderRecentDonations(donations) {
        const container = document.getElementById('recentDonations');
        if (!container) return;

        if (donations.length === 0) {
            container.innerHTML = '<p class="text-muted">No recent donations</p>';
            return;
        }

        container.innerHTML = donations.map(donation => `
            <div class="recent-item">
                <div class="recent-icon" style="background: #f093fb;">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="recent-content">
                    <div class="recent-title">â‚¹${this.formatNumber(donation.amount)}</div>
                    <div class="recent-meta">
                        ${donation.donor_name || 'Anonymous'} â€¢ ${this.formatDate(donation.created_at)}
                    </div>
                </div>
                <span class="badge badge-${donation.status === 'completed' ? 'success' : 'warning'}">${donation.status}</span>
            </div>
        `).join('');
    }

    /**
     * Update system status
     */
    updateSystemStatus() {
        // This would typically check actual system health
        const statusElements = {
            dbStatus: document.getElementById('dbStatus'),
            emailStatus: document.getElementById('emailStatus'),
            paymentStatus: document.getElementById('paymentStatus'),
            storageStatus: document.getElementById('storageStatus')
        };

        // For demo purposes, showing all as online
        Object.values(statusElements).forEach(element => {
            if (element) {
                element.textContent = 'Online';
                element.className = 'status-indicator online';
            }
        });
    }

    /**
     * Load complaints data
     */
    async loadComplaintsData() {
        const content = document.getElementById('complaintsContent');
        if (!content) return;

        try {
            const response = await this.apiCall('GET', '/complaints?page=1&limit=20');
            const complaints = response.data || [];

            content.innerHTML = this.renderDataTable({
                title: 'Complaints',
                data: complaints,
                columns: [
                    { key: 'complaint_number', label: 'Complaint #' },
                    { key: 'subject', label: 'Subject' },
                    { key: 'complainant_name', label: 'Complainant' },
                    { key: 'status', label: 'Status', render: (value) => `<span class="badge badge-${this.getStatusColor(value)}">${value}</span>` },
                    { key: 'created_at', label: 'Date', render: (value) => this.formatDate(value) },
                    { key: 'actions', label: 'Actions', render: (value, row) => this.renderTableActions(row, 'complaint') }
                ],
                searchable: true,
                filterable: true
            });

        } catch (error) {
            content.innerHTML = '<div class="error-message">Failed to load complaints data</div>';
        }
    }

    /**
     * Load events data
     */
    async loadEventsData() {
        const content = document.getElementById('eventsContent');
        if (!content) return;

        try {
            const response = await this.apiCall('GET', '/events?page=1&limit=20');
            const events = response.data || [];

            content.innerHTML = this.renderDataTable({
                title: 'Events',
                data: events,
                columns: [
                    { key: 'title', label: 'Title' },
                    { key: 'event_date', label: 'Date', render: (value) => this.formatDate(value) },
                    { key: 'location', label: 'Location' },
                    { key: 'registrations_count', label: 'Registrations' },
                    { key: 'status', label: 'Status', render: (value) => `<span class="badge badge-${this.getStatusColor(value)}">${value}</span>` },
                    { key: 'actions', label: 'Actions', render: (value, row) => this.renderTableActions(row, 'event') }
                ],
                searchable: true,
                filterable: true
            });

        } catch (error) {
            content.innerHTML = '<div class="error-message">Failed to load events data</div>';
        }
    }

    /**
     * Load activities data
     */
    async loadActivitiesData() {
        const content = document.getElementById('activitiesContent');
        if (!content) return;

        try {
            const response = await this.apiCall('GET', '/activities?page=1&limit=20');
            const activities = response.data || [];

            content.innerHTML = this.renderDataTable({
                title: 'Activities',
                data: activities,
                columns: [
                    { key: 'title', label: 'Title' },
                    { key: 'category', label: 'Category' },
                    { key: 'date', label: 'Date', render: (value) => this.formatDate(value) },
                    { key: 'participants', label: 'Participants' },
                    { key: 'status', label: 'Status', render: (value) => `<span class="badge badge-${this.getStatusColor(value)}">${value}</span>` },
                    { key: 'actions', label: 'Actions', render: (value, row) => this.renderTableActions(row, 'activity') }
                ],
                searchable: true,
                filterable: true
            });

        } catch (error) {
            content.innerHTML = '<div class="error-message">Failed to load activities data</div>';
        }
    }

    /**
     * Load members data
     */
    async loadMembersData() {
        const content = document.getElementById('membersContent');
        if (!content) return;

        try {
            const response = await this.apiCall('GET', '/members?page=1&limit=20');
            const members = response.data || [];

            content.innerHTML = this.renderDataTable({
                title: 'Members',
                data: members,
                columns: [
                    { key: 'membership_id', label: 'Member ID' },
                    { key: 'name', label: 'Name' },
                    { key: 'email', label: 'Email' },
                    { key: 'phone', label: 'Phone' },
                    { key: 'member_type', label: 'Type' },
                    { key: 'status', label: 'Status', render: (value) => `<span class="badge badge-${this.getStatusColor(value)}">${value}</span>` },
                    { key: 'actions', label: 'Actions', render: (value, row) => this.renderTableActions(row, 'member') }
                ],
                searchable: true,
                filterable: true
            });

        } catch (error) {
            content.innerHTML = '<div class="error-message">Failed to load members data</div>';
        }
    }

    /**
     * Load donations data
     */
    async loadDonationsData() {
        const content = document.getElementById('donationsContent');
        if (!content) return;

        try {
            const response = await this.apiCall('GET', '/donations?page=1&limit=20');
            const donations = response.data || [];

            content.innerHTML = this.renderDataTable({
                title: 'Donations',
                data: donations,
                columns: [
                    { key: 'donation_id', label: 'Donation ID' },
                    { key: 'donor_name', label: 'Donor' },
                    { key: 'amount', label: 'Amount', render: (value) => `â‚¹${this.formatNumber(value)}` },
                    { key: 'payment_method', label: 'Method' },
                    { key: 'status', label: 'Status', render: (value) => `<span class="badge badge-${this.getStatusColor(value)}">${value}</span>` },
                    { key: 'created_at', label: 'Date', render: (value) => this.formatDate(value) },
                    { key: 'actions', label: 'Actions', render: (value, row) => this.renderTableActions(row, 'donation') }
                ],
                searchable: true,
                filterable: true
            });

        } catch (error) {
            content.innerHTML = '<div class="error-message">Failed to load donations data</div>';
        }
    }

    /**
     * Load gallery data
     */
    async loadGalleryData() {
        const content = document.getElementById('galleryContent');
        if (!content) return;

        try {
            const response = await this.apiCall('GET', '/gallery?page=1&limit=20');
            const images = response.data || [];

            content.innerHTML = this.renderGalleryGrid(images);

        } catch (error) {
            content.innerHTML = '<div class="error-message">Failed to load gallery data</div>';
        }
    }

    /**
     * Load users data
     */
    async loadUsersData() {
        const content = document.getElementById('usersContent');
        if (!content) return;

        try {
            const response = await this.apiCall('GET', '/users?page=1&limit=20');
            const users = response.data || [];

            content.innerHTML = this.renderDataTable({
                title: 'Users',
                data: users,
                columns: [
                    { key: 'name', label: 'Name' },
                    { key: 'email', label: 'Email' },
                    { key: 'role', label: 'Role', render: (value) => `<span class="badge badge-info">${value}</span>` },
                    { key: 'status', label: 'Status', render: (value) => `<span class="badge badge-${this.getStatusColor(value)}">${value}</span>` },
                    { key: 'last_login', label: 'Last Login', render: (value) => value ? this.formatDate(value) : 'Never' },
                    { key: 'actions', label: 'Actions', render: (value, row) => this.renderTableActions(row, 'user') }
                ],
                searchable: true,
                filterable: true
            });

        } catch (error) {
            content.innerHTML = '<div class="error-message">Failed to load users data</div>';
        }
    }

    /**
     * Load reports data
     */
    async loadReportsData() {
        const content = document.getElementById('reportsContent');
        if (!content) return;

        content.innerHTML = `
            <div class="reports-dashboard">
                <div class="report-cards">
                    <div class="report-card">
                        <h3>Complaints Report</h3>
                        <p>Generate detailed complaints analysis</p>
                        <button class="btn btn-primary" onclick="adminDashboard.generateReport('complaints')">
                            <i class="fas fa-file-pdf"></i> Generate
                        </button>
                    </div>
                    <div class="report-card">
                        <h3>Events Report</h3>
                        <p>Generate events and registrations report</p>
                        <button class="btn btn-primary" onclick="adminDashboard.generateReport('events')">
                            <i class="fas fa-file-pdf"></i> Generate
                        </button>
                    </div>
                    <div class="report-card">
                        <h3>Donations Report</h3>
                        <p>Generate financial donations report</p>
                        <button class="btn btn-primary" onclick="adminDashboard.generateReport('donations')">
                            <i class="fas fa-file-pdf"></i> Generate
                        </button>
                    </div>
                    <div class="report-card">
                        <h3>Members Report</h3>
                        <p>Generate membership analysis report</p>
                        <button class="btn btn-primary" onclick="adminDashboard.generateReport('members')">
                            <i class="fas fa-file-pdf"></i> Generate
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Load settings data
     */
    async loadSettingsData() {
        const content = document.getElementById('settingsContent');
        if (!content) return;

        content.innerHTML = `
            <div class="settings-dashboard">
                <div class="settings-sections">
                    <div class="settings-section">
                        <h3>General Settings</h3>
                        <form class="settings-form">
                            <div class="form-group">
                                <label class="form-label">Organization Name</label>
                                <input type="text" class="form-control" value="Bharatiya Human Rights Commission" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Contact Email</label>
                                <input type="email" class="form-control" value="info@bhrcindia.in" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Contact Phone</label>
                                <input type="tel" class="form-control" value="+91-612-2235555" />
                            </div>
                        </form>
                    </div>
                    
                    <div class="settings-section">
                        <h3>Email Settings</h3>
                        <form class="settings-form">
                            <div class="form-group">
                                <label class="form-label">SMTP Server</label>
                                <input type="text" class="form-control" value="smtp.gmail.com" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">SMTP Port</label>
                                <input type="number" class="form-control" value="587" />
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Username</label>
                                <input type="email" class="form-control" value="noreply@bhrcindia.in" />
                            </div>
                        </form>
                    </div>
                    
                    <div class="settings-section">
                        <h3>Security Settings</h3>
                        <form class="settings-form">
                            <div class="form-group">
                                <label class="form-label">
                                    <input type="checkbox" checked /> Enable Two-Factor Authentication
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <input type="checkbox" checked /> Enable Login Notifications
                                </label>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Session Timeout (minutes)</label>
                                <input type="number" class="form-control" value="30" />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Render data table
     */
    renderDataTable(config) {
        const { title, data, columns, searchable = false, filterable = false } = config;

        return `
            <div class="data-table">
                <div class="table-header">
                    <h3>${title}</h3>
                    <div class="table-controls">
                        ${searchable ? `
                            <div class="table-search">
                                <input type="text" class="search-input" placeholder="Search ${title.toLowerCase()}..." />
                                <button class="btn btn-secondary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        ` : ''}
                        ${filterable ? `
                            <div class="table-filters">
                                <select class="filter-select">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                        ` : ''}
                    </div>
                </div>
                <div class="table-content">
                    <table class="table">
                        <thead>
                            <tr>
                                ${columns.map(col => `<th>${col.label}</th>`).join('')}
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map(row => `
                                <tr>
                                    ${columns.map(col => `
                                        <td>
                                            ${col.render ? col.render(row[col.key], row) : (row[col.key] || '-')}
                                        </td>
                                    `).join('')}
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                <div class="pagination">
                    <button class="pagination-btn" disabled>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="pagination-btn active">1</button>
                    <button class="pagination-btn">2</button>
                    <button class="pagination-btn">3</button>
                    <button class="pagination-btn">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Render table actions
     */
    renderTableActions(row, type) {
        return `
            <div class="table-actions">
                <button class="action-btn view" onclick="adminDashboard.viewItem('${type}', '${row.id}')" title="View">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="action-btn edit" onclick="adminDashboard.editItem('${type}', '${row.id}')" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="action-btn delete" onclick="adminDashboard.deleteItem('${type}', '${row.id}')" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    }

    /**
     * Render gallery grid
     */
    renderGalleryGrid(images) {
        return `
            <div class="gallery-grid">
                ${images.map(image => `
                    <div class="gallery-item">
                        <img src="${image.thumbnail_url || image.image_url}" alt="${image.title}" />
                        <div class="gallery-overlay">
                            <div class="gallery-actions">
                                <button class="btn btn-sm btn-primary" onclick="adminDashboard.viewImage('${image.id}')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="adminDashboard.deleteImage('${image.id}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Refresh dashboard
        const refreshBtn = document.getElementById('refreshDashboard');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.loadDashboardData();
            });
        }

        // Chart period changes
        const complaintsChartPeriod = document.getElementById('complaintsChartPeriod');
        const donationsChartPeriod = document.getElementById('donationsChartPeriod');

        if (complaintsChartPeriod) {
            complaintsChartPeriod.addEventListener('change', (e) => {
                this.updateChart('complaints', e.target.value);
            });
        }

        if (donationsChartPeriod) {
            donationsChartPeriod.addEventListener('change', (e) => {
                this.updateChart('donations', e.target.value);
            });
        }

        // Mark all notifications as read
        const markAllRead = document.getElementById('markAllRead');
        if (markAllRead) {
            markAllRead.addEventListener('click', () => {
                this.markAllNotificationsRead();
            });
        }
    }

    /**
     * Start periodic updates
     */
    startPeriodicUpdates() {
        // Update notifications every 30 seconds
        setInterval(() => {
            this.updateNotificationCount();
        }, 30000);

        // Update dashboard stats every 5 minutes
        setInterval(() => {
            if (this.currentSection === 'dashboard') {
                this.loadDashboardData();
            }
        }, 300000);
    }

    /**
     * Load notifications
     */
    async loadNotifications() {
        try {
            const response = await this.apiCall('GET', '/notifications?limit=10');
            this.notifications = response.data || [];
            this.renderNotifications();
        } catch (error) {
            console.error('Failed to load notifications:', error);
        }
    }

    /**
     * Render notifications
     */
    renderNotifications() {
        const container = document.getElementById('notificationList');
        if (!container) return;

        if (this.notifications.length === 0) {
            container.innerHTML = '<div class="notification-empty">No new notifications</div>';
            return;
        }

        container.innerHTML = this.notifications.map(notification => `
            <div class="notification-item ${notification.read ? 'read' : 'unread'}">
                <div class="notification-icon">
                    <i class="fas fa-${this.getNotificationIcon(notification.type)}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${notification.title}</div>
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-time">${this.formatDate(notification.created_at)}</div>
                </div>
            </div>
        `).join('');
    }

    /**
     * Update notification count
     */
    async updateNotificationCount() {
        try {
            const response = await this.apiCall('GET', '/notifications/count');
            const count = response.count || 0;
            
            const countElement = document.getElementById('notificationCount');
            if (countElement) {
                countElement.textContent = count;
                countElement.style.display = count > 0 ? 'flex' : 'none';
            }
        } catch (error) {
            console.error('Failed to update notification count:', error);
        }
    }

    /**
     * Mark all notifications as read
     */
    async markAllNotificationsRead() {
        try {
            await this.apiCall('POST', '/notifications/mark-all-read');
            this.notifications.forEach(n => n.read = true);
            this.renderNotifications();
            this.updateNotificationCount();
        } catch (error) {
            console.error('Failed to mark notifications as read:', error);
        }
    }

    /**
     * Update chart data
     */
    async updateChart(type, period) {
        try {
            const endpoint = type === 'complaints' ? '/complaints' : '/donations';
            const data = await this.apiCall('GET', `${endpoint}?chart=true&period=${period}`);
            
            if (type === 'complaints') {
                this.renderComplaintsChart(data);
            } else {
                this.renderDonationsChart(data);
            }
        } catch (error) {
            console.error(`Failed to update ${type} chart:`, error);
        }
    }

    /**
     * View item
     */
    async viewItem(type, id) {
        try {
            const response = await this.apiCall('GET', `/${type}s/${id}`);
            const item = response.data || response;
            
            this.showModal({
                title: `View ${type.charAt(0).toUpperCase() + type.slice(1)}`,
                content: this.renderItemDetails(item, type),
                size: 'large'
            });
        } catch (error) {
            this.showToast('Error', `Failed to load ${type} details`, 'error');
        }
    }

    /**
     * Edit item
     */
    async editItem(type, id) {
        try {
            const response = await this.apiCall('GET', `/${type}s/${id}`);
            const item = response.data || response;
            
            this.showModal({
                title: `Edit ${type.charAt(0).toUpperCase() + type.slice(1)}`,
                content: this.renderEditForm(item, type),
                size: 'large',
                actions: [
                    {
                        text: 'Cancel',
                        class: 'btn-secondary',
                        action: () => this.closeModal()
                    },
                    {
                        text: 'Save Changes',
                        class: 'btn-primary',
                        action: () => this.saveItem(type, id)
                    }
                ]
            });
        } catch (error) {
            this.showToast('Error', `Failed to load ${type} for editing`, 'error');
        }
    }

    /**
     * Delete item
     */
    async deleteItem(type, id) {
        if (!confirm(`Are you sure you want to delete this ${type}?`)) {
            return;
        }

        try {
            await this.apiCall('DELETE', `/${type}s/${id}`);
            this.showToast('Success', `${type.charAt(0).toUpperCase() + type.slice(1)} deleted successfully`, 'success');
            this.loadSectionData(type + 's');
        } catch (error) {
            this.showToast('Error', `Failed to delete ${type}`, 'error');
        }
    }

    /**
     * Generate report
     */
    async generateReport(type) {
        this.showLoading();
        
        try {
            const response = await this.apiCall('GET', `/reports/${type}`, {}, { responseType: 'blob' });
            
            // Create download link
            const url = window.URL.createObjectURL(new Blob([response]));
            const link = document.createElement('a');
            link.href = url;
            link.setAttribute('download', `${type}-report-${new Date().toISOString().split('T')[0]}.pdf`);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
            
            this.showToast('Success', 'Report generated successfully', 'success');
        } catch (error) {
            this.showToast('Error', 'Failed to generate report', 'error');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * Render item details
     */
    renderItemDetails(item, type) {
        // This would render detailed view based on item type
        return `
            <div class="item-details">
                <pre>${JSON.stringify(item, null, 2)}</pre>
            </div>
        `;
    }

    /**
     * Render edit form
     */
    renderEditForm(item, type) {
        // This would render edit form based on item type
        return `
            <div class="edit-form">
                <p>Edit form for ${type} would be rendered here</p>
                <pre>${JSON.stringify(item, null, 2)}</pre>
            </div>
        `;
    }

    /**
     * Save item
     */
    async saveItem(type, id) {
        // This would collect form data and save
        this.showToast('Info', 'Save functionality would be implemented here', 'info');
        this.closeModal();
    }

    /**
     * Show modal
     */
    showModal(config) {
        const { title, content, size = 'medium', actions = [] } = config;
        const modalOverlay = document.getElementById('modalOverlay');
        const modalContainer = document.getElementById('modalContainer');
        
        if (!modalOverlay || !modalContainer) return;

        modalContainer.className = `modal-container modal-${size}`;
        modalContainer.innerHTML = `
            <div class="modal-header">
                <h3 class="modal-title">${title}</h3>
                <button class="modal-close" onclick="adminDashboard.closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                ${content}
            </div>
            ${actions.length > 0 ? `
                <div class="modal-footer">
                    ${actions.map(action => `
                        <button class="btn ${action.class}" onclick="${action.action}">
                            ${action.text}
                        </button>
                    `).join('')}
                </div>
            ` : ''}
        `;

        modalOverlay.classList.add('active');
    }

    /**
     * Close modal
     */
    closeModal() {
        const modalOverlay = document.getElementById('modalOverlay');
        if (modalOverlay) {
            modalOverlay.classList.remove('active');
        }
    }

    /**
     * Show loading overlay
     */
    showLoading() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.classList.add('active');
        }
    }

    /**
     * Hide loading overlay
     */
    hideLoading() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.classList.remove('active');
        }
    }

    /**
     * Show toast notification
     */
    showToast(title, message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer');
        if (!toastContainer) return;

        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas fa-${this.getToastIcon(type)}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="adminDashboard.closeToast('${toastId}')">
                <i class="fas fa-times"></i>
            </button>
        `;

        toastContainer.appendChild(toast);
        
        // Show toast
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Auto remove after 5 seconds
        setTimeout(() => this.closeToast(toastId), 5000);
    }

    /**
     * Close toast
     */
    closeToast(toastId) {
        const toast = document.getElementById(toastId);
        if (toast) {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }
    }

    /**
     * Logout user
     */
    async logout() {
        try {
            await this.apiCall('POST', '/auth/logout');
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            this.redirectToLogin();
        }
    }

    /**
     * Redirect to login page
     */
    redirectToLogin() {
        window.location.href = '/login.html';
    }

    /**
     * Make API call
     */
    async apiCall(method, endpoint, data = null, options = {}) {
        const url = this.apiBase + endpoint;
        const config = {
            method,
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };

        if (data && method !== 'GET') {
            config.body = JSON.stringify(data);
        }

        const response = await fetch(url, config);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    /**
     * Utility functions
     */
    formatDate(dateString) {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('en-IN', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    formatNumber(number) {
        return new Intl.NumberFormat('en-IN').format(number);
    }

    truncateText(text, length) {
        if (!text) return '';
        return text.length > length ? text.substring(0, length) + '...' : text;
    }

    getStatusColor(status) {
        const colors = {
            'active': 'success',
            'completed': 'success',
            'pending': 'warning',
            'processing': 'info',
            'cancelled': 'danger',
            'inactive': 'secondary',
            'draft': 'secondary'
        };
        return colors[status] || 'secondary';
    }

    getNotificationIcon(type) {
        const icons = {
            'complaint': 'exclamation-triangle',
            'event': 'calendar-alt',
            'donation': 'heart',
            'member': 'user',
            'system': 'cog'
        };
        return icons[type] || 'bell';
    }

    getToastIcon(type) {
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
}

// Initialize admin dashboard when DOM is loaded
let adminDashboard;
document.addEventListener('DOMContentLoaded', () => {
    adminDashboard = new AdminDashboard();
});

// Export for global access
window.adminDashboard = adminDashboard;
