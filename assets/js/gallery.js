/**
 * Gallery Page JavaScript
 * Handles gallery filtering, modal functionality, and backend integration
 */

class GalleryManager {
    constructor() {
        this.currentFilter = 'all';
        this.currentPage = 1;
        this.itemsPerPage = 12;
        this.galleryItems = [];
        this.filteredItems = [];
        this.currentModalIndex = 0;
        this.searchTimeout = null;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadGalleryItems();
        this.updateStatistics();
    }

    bindEvents() {
        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleFilterChange(e.target.dataset.filter);
            });
        });

        // Search functionality
        const searchInput = document.getElementById('gallerySearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    this.handleSearch(e.target.value);
                }, 300);
            });
        }

        // Load more button
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => {
                this.loadMoreItems();
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

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardNavigation(e);
        });
    }

    bindModalEvents() {
        const modal = document.getElementById('galleryModal');
        const modalClose = document.getElementById('modalClose');
        const modalOverlay = modal?.querySelector('.modal-overlay');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        if (modalClose) {
            modalClose.addEventListener('click', () => this.closeModal());
        }

        if (modalOverlay) {
            modalOverlay.addEventListener('click', () => this.closeModal());
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.showPreviousItem());
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.showNextItem());
        }
    }

    async loadGalleryItems() {
        try {
            this.showLoading(true);
            
            // Simulate API call - replace with actual backend endpoint
            const response = await this.fetchGalleryData();
            this.galleryItems = response.items || this.generateSampleData();
            
            this.applyCurrentFilter();
            this.renderGalleryItems();
            this.showLoading(false);
            
        } catch (error) {
            console.error('Error loading gallery items:', error);
            this.showError('Failed to load gallery items. Please try again.');
            this.showLoading(false);
        }
    }

    async fetchGalleryData() {
        // Replace with actual API endpoint
        try {
            const response = await fetch(window.BHRC_CONFIG ? window.BHRC_CONFIG.API.BASE_URL + ''.replace('/api', '') : 'https://bhrcdata.online/backend/api'.replace('/api', '')), {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to fetch gallery data');
            }
            
            return await response.json();
        } catch (error) {
            console.warn('Using sample data due to API error:', error);
            return { items: this.generateSampleData() };
        }
    }

    generateSampleData() {
        const categories = ['photos', 'videos', 'events', 'activities'];
        const sampleItems = [];
        
        for (let i = 1; i <= 24; i++) {
            const category = categories[Math.floor(Math.random() * categories.length)];
            const isVideo = category === 'videos' || Math.random() < 0.3;
            
            sampleItems.push({
                id: i,
                title: `${category.charAt(0).toUpperCase() + category.slice(1)} Item ${i}`,
                description: `Description for ${category} item ${i}. This showcases BHRC's commitment to human rights advocacy.`,
                type: isVideo ? 'video' : 'photo',
                category: category,
                url: isVideo ? 
                    `../assets/videos/sample-video-${i}.mp4` : 
                    `../assets/images/gallery/sample-${i}.jpg`,
                thumbnail: `../assets/images/gallery/thumb-${i}.jpg`,
                date: new Date(2024, Math.floor(Math.random() * 12), Math.floor(Math.random() * 28) + 1).toISOString(),
                views: Math.floor(Math.random() * 1000) + 100
            });
        }
        
        return sampleItems;
    }

    handleFilterChange(filter) {
        // Update active filter button
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        document.querySelector(`[data-filter="${filter}"]`).classList.add('active');
        
        this.currentFilter = filter;
        this.currentPage = 1;
        this.applyCurrentFilter();
        this.renderGalleryItems();
    }

    handleSearch(query) {
        this.currentPage = 1;
        this.applyCurrentFilter(query);
        this.renderGalleryItems();
    }

    applyCurrentFilter(searchQuery = '') {
        let filtered = [...this.galleryItems];
        
        // Apply category filter
        if (this.currentFilter !== 'all') {
            filtered = filtered.filter(item => 
                item.category === this.currentFilter || item.type === this.currentFilter
            );
        }
        
        // Apply search filter
        if (searchQuery.trim()) {
            const query = searchQuery.toLowerCase();
            filtered = filtered.filter(item =>
                item.title.toLowerCase().includes(query) ||
                item.description.toLowerCase().includes(query) ||
                item.category.toLowerCase().includes(query)
            );
        }
        
        this.filteredItems = filtered;
    }

    renderGalleryItems() {
        const galleryGrid = document.getElementById('galleryGrid');
        if (!galleryGrid) return;
        
        const startIndex = 0;
        const endIndex = this.currentPage * this.itemsPerPage;
        const itemsToShow = this.filteredItems.slice(startIndex, endIndex);
        
        if (itemsToShow.length === 0) {
            galleryGrid.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>No items found</h3>
                    <p>Try adjusting your filters or search terms.</p>
                </div>
            `;
            return;
        }
        
        galleryGrid.innerHTML = itemsToShow.map((item, index) => `
            <div class="gallery-item fade-in" data-index="${index}" tabindex="0" role="button" aria-label="View ${item.title}">
                <div class="gallery-item-media">
                    ${item.type === 'video' ? `
                        <video poster="${item.thumbnail}">
                            <source src="${item.url}" type="video/mp4">
                        </video>
                        <div class="media-overlay">
                            <i class="fas fa-play play-icon"></i>
                        </div>
                    ` : `
                        <img src="${item.thumbnail}" alt="${item.title}" loading="lazy">
                        <div class="media-overlay">
                            <i class="fas fa-expand-alt"></i>
                        </div>
                    `}
                    <span class="media-type">${item.type}</span>
                </div>
                
                <div class="gallery-item-content">
                    <h3>${item.title}</h3>
                    <p>${item.description}</p>
                    
                    <div class="gallery-item-meta">
                        <span>${this.formatDate(item.date)}</span>
                        <span class="gallery-category">${item.category}</span>
                    </div>
                </div>
            </div>
        `).join('');
        
        // Bind click events to gallery items
        galleryGrid.querySelectorAll('.gallery-item').forEach((item, index) => {
            item.addEventListener('click', () => {
                this.openModal(index);
            });
            
            item.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.openModal(index);
                }
            });
        });
        
        // Update load more button
        this.updateLoadMoreButton();
    }

    updateLoadMoreButton() {
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        if (!loadMoreBtn) return;
        
        const totalShown = this.currentPage * this.itemsPerPage;
        const hasMore = totalShown < this.filteredItems.length;
        
        loadMoreBtn.style.display = hasMore ? 'inline-flex' : 'none';
        
        if (hasMore) {
            const remaining = this.filteredItems.length - totalShown;
            loadMoreBtn.innerHTML = `
                <i class="fas fa-plus"></i>
                Load More (${remaining} remaining)
            `;
        }
    }

    loadMoreItems() {
        this.currentPage++;
        this.renderGalleryItems();
    }

    openModal(index) {
        const modal = document.getElementById('galleryModal');
        if (!modal) return;
        
        this.currentModalIndex = index;
        const item = this.filteredItems[index];
        
        if (!item) return;
        
        // Update modal content
        this.updateModalContent(item);
        
        // Show modal
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Update navigation buttons
        this.updateModalNavigation();
        
        // Focus management
        const closeBtn = document.getElementById('modalClose');
        if (closeBtn) closeBtn.focus();
    }

    updateModalContent(item) {
        const modalImage = document.getElementById('modalImage');
        const modalVideo = document.getElementById('modalVideo');
        const modalTitle = document.getElementById('modalTitle');
        const modalDescription = document.getElementById('modalDescription');
        const modalDate = document.getElementById('modalDate');
        const modalCategory = document.getElementById('modalCategory');
        
        // Hide both media elements first
        if (modalImage) modalImage.style.display = 'none';
        if (modalVideo) modalVideo.style.display = 'none';
        
        // Show appropriate media
        if (item.type === 'video') {
            if (modalVideo) {
                modalVideo.src = item.url;
                modalVideo.style.display = 'block';
            }
        } else {
            if (modalImage) {
                modalImage.src = item.url;
                modalImage.alt = item.title;
                modalImage.style.display = 'block';
            }
        }
        
        // Update text content
        if (modalTitle) modalTitle.textContent = item.title;
        if (modalDescription) modalDescription.textContent = item.description;
        if (modalDate) modalDate.textContent = this.formatDate(item.date);
        if (modalCategory) modalCategory.textContent = item.category;
    }

    updateModalNavigation() {
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        
        if (prevBtn) {
            prevBtn.disabled = this.currentModalIndex === 0;
        }
        
        if (nextBtn) {
            nextBtn.disabled = this.currentModalIndex === this.filteredItems.length - 1;
        }
    }

    showPreviousItem() {
        if (this.currentModalIndex > 0) {
            this.currentModalIndex--;
            this.updateModalContent(this.filteredItems[this.currentModalIndex]);
            this.updateModalNavigation();
        }
    }

    showNextItem() {
        if (this.currentModalIndex < this.filteredItems.length - 1) {
            this.currentModalIndex++;
            this.updateModalContent(this.filteredItems[this.currentModalIndex]);
            this.updateModalNavigation();
        }
    }

    closeModal() {
        const modal = document.getElementById('galleryModal');
        if (!modal) return;
        
        modal.classList.remove('active');
        document.body.style.overflow = '';
        
        // Pause any playing video
        const modalVideo = document.getElementById('modalVideo');
        if (modalVideo) {
            modalVideo.pause();
        }
    }

    handleKeyboardNavigation(e) {
        const modal = document.getElementById('galleryModal');
        if (!modal || !modal.classList.contains('active')) return;
        
        switch (e.key) {
            case 'Escape':
                this.closeModal();
                break;
            case 'ArrowLeft':
                e.preventDefault();
                this.showPreviousItem();
                break;
            case 'ArrowRight':
                e.preventDefault();
                this.showNextItem();
                break;
        }
    }

    async updateStatistics() {
        try {
            const stats = await this.fetchStatistics();
            this.animateCounters(stats);
        } catch (error) {
            console.error('Error updating statistics:', error);
            // Use default values
            this.animateCounters({
                photos: 150,
                videos: 45,
                events: 25,
                views: 5000
            });
        }
    }

    async fetchStatistics() {
        try {
            const response = await fetch(window.BHRC_CONFIG ? window.BHRC_CONFIG.API.BASE_URL + ''.replace('/api', '') : 'https://bhrcdata.online/backend/api'.replace('/api', '')));
            if (!response.ok) throw new Error('Failed to fetch stats');
            return await response.json();
        } catch (error) {
            // Calculate from current data
            const photos = this.galleryItems.filter(item => item.type === 'photo').length;
            const videos = this.galleryItems.filter(item => item.type === 'video').length;
            const events = this.galleryItems.filter(item => item.category === 'events').length;
            const views = this.galleryItems.reduce((sum, item) => sum + (item.views || 0), 0);
            
            return { photos, videos, events, views };
        }
    }

    animateCounters(stats) {
        const counters = [
            { element: document.getElementById('photoCount'), target: stats.photos || 0 },
            { element: document.getElementById('videoCount'), target: stats.videos || 0 },
            { element: document.getElementById('eventCount'), target: stats.events || 0 },
            { element: document.getElementById('viewCount'), target: stats.views || 0 }
        ];
        
        counters.forEach(({ element, target }) => {
            if (!element) return;
            
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current).toLocaleString();
            }, 30);
        });
    }

    async handleNewsletterSubmit(e) {
        e.preventDefault();
        
        const emailInput = document.getElementById('newsletterEmail');
        const submitBtn = e.target.querySelector('button[type="submit"]');
        
        if (!emailInput || !submitBtn) return;
        
        const email = emailInput.value.trim();
        
        if (!this.validateEmail(email)) {
            this.showToast('Please enter a valid email address', 'error');
            return;
        }
        
        try {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subscribing...';
            
            const response = await fetch(window.BHRC_CONFIG ? window.BHRC_CONFIG.API.BASE_URL + ''.replace('/api', '') : 'https://bhrcdata.online/backend/api'.replace('/api', '')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email })
            });
            
            if (response.ok) {
                this.showToast('Successfully subscribed to newsletter!', 'success');
                emailInput.value = '';
            } else {
                throw new Error('Subscription failed');
            }
            
        } catch (error) {
            console.error('Newsletter subscription error:', error);
            this.showToast('Failed to subscribe. Please try again.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Subscribe';
        }
    }

    showLoading(show) {
        const galleryGrid = document.getElementById('galleryGrid');
        if (!galleryGrid) return;
        
        if (show) {
            galleryGrid.innerHTML = `
                <div class="gallery-loading">
                    <div class="loading-spinner"></div>
                    <p>Loading gallery...</p>
                </div>
            `;
        }
    }

    showError(message) {
        const galleryGrid = document.getElementById('galleryGrid');
        if (!galleryGrid) return;
        
        galleryGrid.innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Error</h3>
                <p>${message}</p>
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="fas fa-refresh"></i>
                    Retry
                </button>
            </div>
        `;
    }

    showToast(message, type = 'info') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="toast-close">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        // Add to page
        document.body.appendChild(toast);
        
        // Show toast
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Auto remove
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
        
        // Manual close
        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        });
    }

    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
}

// Utility Functions
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialize Gallery Manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new GalleryManager();
    
    // Add scroll to top functionality
    const scrollBtn = document.createElement('button');
    scrollBtn.className = 'scroll-to-top';
    scrollBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    scrollBtn.setAttribute('aria-label', 'Scroll to top');
    document.body.appendChild(scrollBtn);
    
    scrollBtn.addEventListener('click', scrollToTop);
    
    // Show/hide scroll button based on scroll position
    window.addEventListener('scroll', debounce(() => {
        if (window.pageYOffset > 300) {
            scrollBtn.classList.add('visible');
        } else {
            scrollBtn.classList.remove('visible');
        }
    }, 100));
});

// Add CSS for toast notifications and scroll button
const additionalStyles = `
<style>
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    z-index: 10000;
    transform: translateX(400px);
    transition: transform 0.3s ease;
    max-width: 400px;
}

.toast.show {
    transform: translateX(0);
}

.toast-success {
    border-left: 4px solid #10b981;
}

.toast-error {
    border-left: 4px solid #ef4444;
}

.toast-info {
    border-left: 4px solid #3b82f6;
}

.toast-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
}

.toast-close {
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    padding: 0.25rem;
}

.scroll-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 50px;
    height: 50px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(59, 130, 246, 0.3);
    transition: all 0.3s ease;
    opacity: 0;
    visibility: hidden;
    z-index: 1000;
}

.scroll-to-top.visible {
    opacity: 1;
    visibility: visible;
}

.scroll-to-top:hover {
    background: #2563eb;
    transform: translateY(-2px);
}

.no-results,
.error-message {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem 2rem;
    color: #6b7280;
}

.no-results i,
.error-message i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.no-results h3,
.error-message h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: #374151;
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', additionalStyles);
