// Events Page JavaScript

class EventsManager {
    constructor() {
        this.events = [];
        this.filteredEvents = [];
        this.currentPage = 1;
        this.eventsPerPage = 9;
        this.currentFilter = 'all';
        this.currentView = 'grid';
        this.searchQuery = '';
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadEvents();
    }
    
    bindEvents() {
        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleFilterChange(e.target.dataset.filter);
            });
        });
        
        // View toggle
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleViewChange(e.target.dataset.view);
            });
        });
        
        // Search functionality
        const searchInput = document.getElementById('eventSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.handleSearch(e.target.value);
            });
        }
        
        // Load more button
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => {
                this.loadMoreEvents();
            });
        }
        
        // Modal events
        this.bindModalEvents();
        
        // Newsletter form
        const newsletterForm = document.getElementById('newsletterForm');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', (e) => {
                this.handleNewsletterSubmit(e);
            });
        }
    }
    
    bindModalEvents() {
        const modal = document.getElementById('registrationModal');
        const modalClose = document.getElementById('modalClose');
        const cancelBtn = document.getElementById('cancelRegistration');
        const registrationForm = document.getElementById('registrationForm');
        
        if (modalClose) {
            modalClose.addEventListener('click', () => {
                this.closeModal();
            });
        }
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                this.closeModal();
            });
        }
        
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal();
                }
            });
        }
        
        if (registrationForm) {
            registrationForm.addEventListener('submit', (e) => {
                this.handleRegistrationSubmit(e);
            });
        }
    }
    
    async loadEvents() {
        try {
            this.showLoading();
            
            const response = await fetch(window.BHRC_CONFIG.getEndpointUrl('EVENTS'), {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load events');
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.events = data.events || [];
                this.applyFilters();
            } else {
                throw new Error(data.message || 'Failed to load events');
            }
        } catch (error) {
            console.error('Error loading events:', error);
            this.showError('Failed to load events. Please try again later.');
        }
    }
    
    showLoading() {
        const eventsGrid = document.getElementById('eventsGrid');
        if (eventsGrid) {
            eventsGrid.innerHTML = `
                <div class="loading-placeholder">
                    <div class="spinner"></div>
                    <p>Loading events...</p>
                </div>
            `;
        }
    }
    
    showError(message) {
        const eventsGrid = document.getElementById('eventsGrid');
        if (eventsGrid) {
            eventsGrid.innerHTML = `
                <div class="loading-placeholder">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #dc3545; margin-bottom: 1rem;"></i>
                    <p>${message}</p>
                    <button class="btn-primary" onclick="location.reload()">Retry</button>
                </div>
            `;
        }
    }
    
    handleFilterChange(filter) {
        // Update active filter button
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-filter="${filter}"]`).classList.add('active');
        
        this.currentFilter = filter;
        this.currentPage = 1;
        this.applyFilters();
    }
    
    handleViewChange(view) {
        // Update active view button
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-view="${view}"]`).classList.add('active');
        
        this.currentView = view;
        this.updateGridView();
    }
    
    handleSearch(query) {
        this.searchQuery = query.toLowerCase();
        this.currentPage = 1;
        this.applyFilters();
    }
    
    applyFilters() {
        let filtered = [...this.events];
        
        // Apply category filter
        if (this.currentFilter !== 'all') {
            filtered = filtered.filter(event => {
                if (this.currentFilter === 'upcoming') {
                    return new Date(event.date) > new Date();
                } else if (this.currentFilter === 'past') {
                    return new Date(event.date) < new Date();
                } else {
                    return event.type === this.currentFilter;
                }
            });
        }
        
        // Apply search filter
        if (this.searchQuery) {
            filtered = filtered.filter(event => 
                event.title.toLowerCase().includes(this.searchQuery) ||
                event.description.toLowerCase().includes(this.searchQuery) ||
                event.location.toLowerCase().includes(this.searchQuery)
            );
        }
        
        this.filteredEvents = filtered;
        this.renderEvents();
    }
    
    renderEvents() {
        const eventsGrid = document.getElementById('eventsGrid');
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        
        if (!eventsGrid) return;
        
        const startIndex = 0;
        const endIndex = this.currentPage * this.eventsPerPage;
        const eventsToShow = this.filteredEvents.slice(startIndex, endIndex);
        
        if (eventsToShow.length === 0) {
            eventsGrid.innerHTML = `
                <div class="loading-placeholder">
                    <i class="fas fa-calendar-times" style="font-size: 2rem; color: #6c757d; margin-bottom: 1rem;"></i>
                    <p>No events found matching your criteria.</p>
                </div>
            `;
            if (loadMoreBtn) loadMoreBtn.style.display = 'none';
            return;
        }
        
        eventsGrid.innerHTML = eventsToShow.map(event => this.createEventCard(event)).join('');
        
        // Show/hide load more button
        if (loadMoreBtn) {
            if (endIndex < this.filteredEvents.length) {
                loadMoreBtn.style.display = 'block';
            } else {
                loadMoreBtn.style.display = 'none';
            }
        }
        
        this.updateGridView();
        this.bindEventCardEvents();
    }
    
    createEventCard(event) {
        const eventDate = new Date(event.date);
        const now = new Date();
        const isUpcoming = eventDate > now;
        const isPast = eventDate < now;
        const isToday = eventDate.toDateString() === now.toDateString();
        
        let status = 'upcoming';
        let statusText = 'Upcoming';
        
        if (isPast) {
            status = 'past';
            statusText = 'Past Event';
        } else if (isToday) {
            status = 'ongoing';
            statusText = 'Today';
        }
        
        const formattedDate = eventDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        const formattedTime = eventDate.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        return `
            <div class="event-card" data-event-id="${event.id}">
                <div class="event-image">
                    <img src="${event.image || '../assets/images/default-event.jpg'}" alt="${event.title}" loading="lazy">
                    <div class="event-status ${status}">${statusText}</div>
                </div>
                <div class="event-content">
                    <div class="event-meta">
                        <div class="event-date">
                            <i class="fas fa-calendar-alt"></i>
                            <span>${formattedDate}</span>
                        </div>
                        <div class="event-type">${this.getTypeLabel(event.type)}</div>
                    </div>
                    <h3 class="event-title">${event.title}</h3>
                    <p class="event-description">${event.description}</p>
                    <div class="event-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>${event.location}</span>
                    </div>
                    <div class="event-actions">
                        ${isUpcoming ? `
                            <button class="btn-register" data-event-id="${event.id}">
                                Register Now
                            </button>
                        ` : ''}
                        <button class="btn-details" data-event-id="${event.id}">
                            View Details
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    getTypeLabel(type) {
        const labels = {
            'workshop': 'Workshop',
            'seminar': 'Seminar',
            'awareness': 'Awareness Program',
            'meeting': 'Meeting',
            'conference': 'Conference',
            'training': 'Training'
        };
        return labels[type] || type;
    }
    
    bindEventCardEvents() {
        // Register buttons
        document.querySelectorAll('.btn-register').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const eventId = e.target.dataset.eventId;
                this.openRegistrationModal(eventId);
            });
        });
        
        // Details buttons
        document.querySelectorAll('.btn-details').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const eventId = e.target.dataset.eventId;
                this.showEventDetails(eventId);
            });
        });
    }
    
    updateGridView() {
        const eventsGrid = document.getElementById('eventsGrid');
        if (eventsGrid) {
            eventsGrid.className = `events-grid ${this.currentView}-view`;
        }
    }
    
    loadMoreEvents() {
        this.currentPage++;
        this.renderEvents();
    }
    
    openRegistrationModal(eventId) {
        const event = this.events.find(e => e.id == eventId);
        if (!event) return;
        
        const modal = document.getElementById('registrationModal');
        const eventIdInput = document.getElementById('eventId');
        
        if (eventIdInput) {
            eventIdInput.value = eventId;
        }
        
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }
    
    closeModal() {
        const modal = document.getElementById('registrationModal');
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
        
        // Reset form
        const form = document.getElementById('registrationForm');
        if (form) {
            form.reset();
        }
    }
    
    async handleRegistrationSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        try {
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Registering...';
            
            const response = await fetch(window.BHRC_CONFIG.getBackendUrl(window.BHRC_CONFIG.API.ENDPOINTS.EVENT_REGISTRATION), {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Registration successful! You will receive a confirmation email shortly.', 'success');
                this.closeModal();
            } else {
                throw new Error(data.message || 'Registration failed');
            }
        } catch (error) {
            console.error('Registration error:', error);
            this.showNotification(error.message || 'Registration failed. Please try again.', 'error');
        } finally {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.textContent = 'Register Now';
        }
    }
    
    showEventDetails(eventId) {
        const event = this.events.find(e => e.id == eventId);
        if (!event) return;
        
        // For now, we'll show an alert with event details
        // In a real implementation, you might open a detailed modal or navigate to a details page
        alert(`Event Details:\n\nTitle: ${event.title}\nDate: ${new Date(event.date).toLocaleDateString()}\nLocation: ${event.location}\nDescription: ${event.description}`);
    }
    
    async handleNewsletterSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const email = form.querySelector('input[type="email"]').value;
        const submitBtn = form.querySelector('button[type="submit"]');
        
        try {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Subscribing...';
            
            const response = await fetch(window.BHRC_CONFIG.getBackendUrl(window.BHRC_CONFIG.API.ENDPOINTS.NEWSLETTER), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Successfully subscribed to newsletter!', 'success');
                form.reset();
            } else {
                throw new Error(data.message || 'Subscription failed');
            }
        } catch (error) {
            console.error('Newsletter subscription error:', error);
            this.showNotification(error.message || 'Subscription failed. Please try again.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Subscribe';
        }
    }
    
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#007bff'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            max-width: 400px;
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Close button
        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            this.removeNotification(notification);
        });
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            this.removeNotification(notification);
        }, 5000);
    }
    
    removeNotification(notification) {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
}

// Form validation utilities
class FormValidator {
    static validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    static validatePhone(phone) {
        const re = /^[\+]?[1-9][\d]{0,15}$/;
        return re.test(phone.replace(/\s/g, ''));
    }
    
    static validateRequired(value) {
        return value && value.trim().length > 0;
    }
    
    static addValidation(form) {
        const inputs = form.querySelectorAll('input[required], textarea[required]');
        
        inputs.forEach(input => {
            input.addEventListener('blur', () => {
                this.validateField(input);
            });
            
            input.addEventListener('input', () => {
                if (input.classList.contains('error')) {
                    this.validateField(input);
                }
            });
        });
        
        form.addEventListener('submit', (e) => {
            let isValid = true;
            
            inputs.forEach(input => {
                if (!this.validateField(input)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
    
    static validateField(input) {
        const value = input.value.trim();
        let isValid = true;
        let message = '';
        
        // Required validation
        if (input.hasAttribute('required') && !this.validateRequired(value)) {
            isValid = false;
            message = 'This field is required';
        }
        
        // Email validation
        if (isValid && input.type === 'email' && value && !this.validateEmail(value)) {
            isValid = false;
            message = 'Please enter a valid email address';
        }
        
        // Phone validation
        if (isValid && input.type === 'tel' && value && !this.validatePhone(value)) {
            isValid = false;
            message = 'Please enter a valid phone number';
        }
        
        // Update UI
        if (isValid) {
            input.classList.remove('error');
            this.removeErrorMessage(input);
        } else {
            input.classList.add('error');
            this.showErrorMessage(input, message);
        }
        
        return isValid;
    }
    
    static showErrorMessage(input, message) {
        this.removeErrorMessage(input);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        errorDiv.style.cssText = `
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        `;
        
        input.parentNode.appendChild(errorDiv);
    }
    
    static removeErrorMessage(input) {
        const existingError = input.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize events manager
    window.eventsManager = new EventsManager();
    
    // Add form validation to registration form
    const registrationForm = document.getElementById('registrationForm');
    if (registrationForm) {
        FormValidator.addValidation(registrationForm);
    }
    
    // Add smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

// Export for use in other scripts
window.EventsManager = EventsManager;
window.FormValidator = FormValidator;