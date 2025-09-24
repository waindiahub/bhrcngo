/**
 * BHRC Configuration File
 * Centralized configuration for API URLs and other settings
 */

// Environment Configuration
const ENVIRONMENT = {
    DEVELOPMENT: 'development',
    PRODUCTION: 'production'
};

// Current Environment - Change this to switch between local and live
const CURRENT_ENV = ENVIRONMENT.PRODUCTION; // Using production backend

// API Configuration
const API_CONFIG = {
    [ENVIRONMENT.DEVELOPMENT]: {
        BASE_URL: 'http://localhost/bhrc-clean/backend/api',
        BACKEND_URL: 'http://localhost/bhrc-clean/backend'
    },
    [ENVIRONMENT.PRODUCTION]: {
        BASE_URL: 'https://bhrcdata.online/backend/api',
        BACKEND_URL: 'https://bhrcdata.online/backend'
    }
};

// Global Configuration Object
window.BHRC_CONFIG = {
    // API URLs
    API: {
        BASE_URL: API_CONFIG[CURRENT_ENV].BASE_URL,
        BACKEND_URL: API_CONFIG[CURRENT_ENV].BACKEND_URL,
        
        // Specific endpoints
        ENDPOINTS: {
            STATISTICS: '/statistics',
            EVENTS: '/events',
            EVENTS_RECENT: '/events/recent',
            MEMBERS: '/members',
            MEMBERS_STATS: '/members/stats',
            GALLERY: '/gallery',
            GALLERY_STATS: '/gallery/stats',
            DONATIONS: '/donations',
            DONATIONS_STATS: '/donations/stats',
            COMPLAINTS: '/complaints',
            NEWSLETTER: '/newsletter.php',
            EVENT_REGISTRATION: '/event-registration.php',
            AJAX: '/ajax.php'
        }
    },
    
    // Other settings
    SETTINGS: {
        ANIMATION_DURATION: 300,
        SCROLL_THRESHOLD: 100,
        DEBOUNCE_DELAY: 300,
        MAX_RETRIES: 3,
        CACHE_DURATION: 5 * 60 * 1000, // 5 minutes
        MAX_FILE_SIZE: 5 * 1024 * 1024, // 5MB
        ALLOWED_FILE_TYPES: ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']
    },
    
    // Helper functions
    getApiUrl: function(endpoint = '') {
        return this.API.BASE_URL + endpoint;
    },
    
    getBackendUrl: function(path = '') {
        return this.API.BACKEND_URL + path;
    },
    
    getEndpointUrl: function(endpointKey) {
        const endpoint = this.API.ENDPOINTS[endpointKey];
        return endpoint ? this.getApiUrl(endpoint) : null;
    }
};

// Backward compatibility - Update the existing CONFIG object
if (typeof CONFIG !== 'undefined') {
    Object.assign(CONFIG, {
        API_BASE_URL: window.BHRC_CONFIG.API.BASE_URL,
        ANIMATION_DURATION: window.BHRC_CONFIG.SETTINGS.ANIMATION_DURATION,
        SCROLL_THRESHOLD: window.BHRC_CONFIG.SETTINGS.SCROLL_THRESHOLD,
        DEBOUNCE_DELAY: window.BHRC_CONFIG.SETTINGS.DEBOUNCE_DELAY,
        MAX_RETRIES: window.BHRC_CONFIG.SETTINGS.MAX_RETRIES,
        CACHE_DURATION: window.BHRC_CONFIG.SETTINGS.CACHE_DURATION
    });
}

// Console log for debugging
console.log('BHRC Config loaded:', {
    environment: CURRENT_ENV,
    apiBaseUrl: window.BHRC_CONFIG.API.BASE_URL,
    backendUrl: window.BHRC_CONFIG.API.BACKEND_URL
});