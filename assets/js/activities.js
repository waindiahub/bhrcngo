/**
 * Activities Page JavaScript
 * Handles activity display, filtering, search, and interactions
 */

class ActivitiesManager {
    constructor() {
        this.activities = [];
        this.filteredActivities = [];
        this.currentPage = 1;
        this.activitiesPerPage = 12;
        this.currentCategory = 'all';
        this.currentSort = 'date-desc';
        this.searchTerm = '';
        this.isLoading = false;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadActivities();
        this.loadStats();
    }
    
    bindEvents() {
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.searchTerm = e.target.value.toLowerCase().trim();
                    this.filterAndDisplayActivities();
                }, 300);
            });
        }
        
        // Filter buttons
        const filterButtons = document.querySelectorAll('.filter-btn');
        filterButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                // Remove active class from all buttons
                filterButtons.forEach(b => b.classList.remove('active'));
                // Add active class to clicked button
                e.target.classList.add('active');
                
                this.currentCategory = e.target.dataset.category;
                this.currentPage = 1;
                this.filterAndDisplayActivities();
            });
        });
        
        // Sort functionality
        const sortSelect = document.getElementById('sortSelect');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                this.currentSort = e.target.value;
                this.filterAndDisplayActivities();
            });
        }
        
        // Load more button
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => {
                this.loadMoreActivities();
            });
        }
        
        // Newsletter form
        const newsletterForm = document.getElementById('newsletterForm');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.subscribeNewsletter(e.target);
            });
        }
        
        // Modal close events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeActivityModal();
            }
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeActivityModal();
            }
        });
    }
    
    async loadActivities() {
        try {
            this.showLoading(true);
            
            const response = await fetch('../backend/controllers/ActivityController.php?action=get_activities');
            const data = await response.json();
            
            if (data.success) {
                this.activities = data.activities || [];
                this.filterAndDisplayActivities();
            } else {
                throw new Error(data.message || 'Failed to load activities');
            }
        } catch (error) {
            console.error('Error loading activities:', error);
            this.showError('Failed to load activities. Please try again later.');
            
            // Load sample data for demonstration
            this.loadSampleActivities();
        } finally {
            this.showLoading(false);
        }
    }
    
    loadSampleActivities() {
        this.activities = [
            {
                id: 1,
                title: 'Legal Aid Camp for Marginalized Communities',
                description: 'Providing free legal assistance and consultation to underprivileged communities in rural areas. Our team of experienced lawyers offers guidance on various legal matters including property disputes, family law, and human rights violations.',
                category: 'legal-aid',
                status: 'ongoing',
                date: '2024-01-15',
                image: '../assets/images/activities/legal-aid-camp.jpg',
                objectives: [
                    'Provide free legal consultation to rural communities',
                    'Educate people about their legal rights',
                    'Assist in documentation and legal procedures',
                    'Connect beneficiaries with appropriate legal resources'
                ],
                impact: [
                    'Over 500 people received legal assistance',
                    '150 cases successfully resolved',
                    '25 villages covered across Bharatiya',
                    '80% satisfaction rate from beneficiaries'
                ],
                gallery: [
                    '../assets/images/gallery/legal-aid-1.jpg',
                    '../assets/images/gallery/legal-aid-2.jpg',
                    '../assets/images/gallery/legal-aid-3.jpg'
                ]
            },
            {
                id: 2,
                title: 'Human Rights Awareness Workshop',
                description: 'Comprehensive workshop series designed to educate communities about fundamental human rights, constitutional provisions, and available legal remedies for rights violations.',
                category: 'awareness',
                status: 'completed',
                date: '2024-01-10',
                image: '../assets/images/activities/awareness-workshop.jpg',
                objectives: [
                    'Educate participants about fundamental rights',
                    'Create awareness about constitutional provisions',
                    'Train community leaders as rights advocates',
                    'Establish local human rights committees'
                ],
                impact: [
                    '300+ participants trained',
                    '15 community leaders certified',
                    '10 local committees established',
                    'Significant increase in rights awareness'
                ],
                gallery: [
                    '../assets/images/gallery/workshop-1.jpg',
                    '../assets/images/gallery/workshop-2.jpg'
                ]
            },
            {
                id: 3,
                title: 'Women Empowerment Campaign',
                description: 'Multi-faceted campaign focusing on women\'s rights, gender equality, and empowerment through education, skill development, and legal awareness programs.',
                category: 'campaigns',
                status: 'ongoing',
                date: '2024-01-08',
                image: '../assets/images/activities/women-empowerment.jpg',
                objectives: [
                    'Promote gender equality and women\'s rights',
                    'Provide skill development training',
                    'Create awareness about women-specific laws',
                    'Establish women support groups'
                ],
                impact: [
                    '200+ women trained in various skills',
                    '50 women started their own businesses',
                    '30 support groups formed',
                    'Reduced domestic violence cases by 25%'
                ],
                gallery: [
                    '../assets/images/gallery/women-1.jpg',
                    '../assets/images/gallery/women-2.jpg',
                    '../assets/images/gallery/women-3.jpg',
                    '../assets/images/gallery/women-4.jpg'
                ]
            },
            {
                id: 4,
                title: 'Child Rights Protection Initiative',
                description: 'Comprehensive program aimed at protecting children\'s rights, preventing child labor, and ensuring access to education and healthcare for all children.',
                category: 'advocacy',
                status: 'ongoing',
                date: '2024-01-05',
                image: '../assets/images/activities/child-rights.jpg',
                objectives: [
                    'Prevent child labor and trafficking',
                    'Ensure access to quality education',
                    'Provide healthcare and nutrition support',
                    'Create child-friendly communities'
                ],
                impact: [
                    '150 children rescued from labor',
                    '300+ children enrolled in schools',
                    '20 child-friendly villages created',
                    '95% reduction in child labor cases'
                ],
                gallery: [
                    '../assets/images/gallery/child-1.jpg',
                    '../assets/images/gallery/child-2.jpg'
                ]
            },
            {
                id: 5,
                title: 'Human Rights Research Project',
                description: 'Comprehensive research study on the state of human rights in Bharatiya, documenting violations, analyzing trends, and proposing policy recommendations.',
                category: 'research',
                status: 'completed',
                date: '2023-12-20',
                image: '../assets/images/activities/research-project.jpg',
                objectives: [
                    'Document human rights violations',
                    'Analyze trends and patterns',
                    'Propose policy recommendations',
                    'Create awareness through publications'
                ],
                impact: [
                    'Comprehensive 200-page report published',
                    '15 policy recommendations submitted',
                    'Influenced 3 major policy changes',
                    'Cited in 25+ academic papers'
                ],
                gallery: [
                    '../assets/images/gallery/research-1.jpg',
                    '../assets/images/gallery/research-2.jpg'
                ]
            },
            {
                id: 6,
                title: 'Community Leader Training Program',
                description: 'Intensive training program for community leaders to build their capacity in human rights advocacy, conflict resolution, and community mobilization.',
                category: 'training',
                status: 'upcoming',
                date: '2024-02-01',
                image: '../assets/images/activities/leader-training.jpg',
                objectives: [
                    'Build leadership capacity in communities',
                    'Train in human rights advocacy',
                    'Develop conflict resolution skills',
                    'Create a network of trained leaders'
                ],
                impact: [
                    'Program launching soon',
                    'Expected to train 100+ leaders',
                    'Will cover 50+ villages',
                    'Anticipated 70% improvement in community issues resolution'
                ],
                gallery: []
            }
        ];
        
        this.filterAndDisplayActivities();
    }
    
    filterAndDisplayActivities() {
        // Filter by category
        let filtered = this.activities;
        
        if (this.currentCategory !== 'all') {
            filtered = filtered.filter(activity => 
                activity.category === this.currentCategory
            );
        }
        
        // Filter by search term
        if (this.searchTerm) {
            filtered = filtered.filter(activity =>
                activity.title.toLowerCase().includes(this.searchTerm) ||
                activity.description.toLowerCase().includes(this.searchTerm) ||
                activity.category.toLowerCase().includes(this.searchTerm)
            );
        }
        
        // Sort activities
        filtered = this.sortActivities(filtered);
        
        this.filteredActivities = filtered;
        this.displayActivities();
    }
    
    sortActivities(activities) {
        return activities.sort((a, b) => {
            switch (this.currentSort) {
                case 'date-desc':
                    return new Date(b.date) - new Date(a.date);
                case 'date-asc':
                    return new Date(a.date) - new Date(b.date);
                case 'title-asc':
                    return a.title.localeCompare(b.title);
                case 'title-desc':
                    return b.title.localeCompare(a.title);
                default:
                    return 0;
            }
        });
    }
    
    displayActivities() {
        const activitiesGrid = document.getElementById('activitiesGrid');
        const noResults = document.getElementById('noResults');
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        
        if (!activitiesGrid) return;
        
        if (this.filteredActivities.length === 0) {
            activitiesGrid.innerHTML = '';
            noResults.style.display = 'block';
            loadMoreBtn.style.display = 'none';
            return;
        }
        
        noResults.style.display = 'none';
        
        // Calculate activities to show
        const startIndex = 0;
        const endIndex = this.currentPage * this.activitiesPerPage;
        const activitiesToShow = this.filteredActivities.slice(startIndex, endIndex);
        
        // Clear grid and add activities
        activitiesGrid.innerHTML = '';
        
        activitiesToShow.forEach((activity, index) => {
            const activityCard = this.createActivityCard(activity);
            activityCard.style.animationDelay = `${index * 0.1}s`;
            activitiesGrid.appendChild(activityCard);
        });
        
        // Show/hide load more button
        if (endIndex >= this.filteredActivities.length) {
            loadMoreBtn.style.display = 'none';
        } else {
            loadMoreBtn.style.display = 'block';
        }
    }
    
    createActivityCard(activity) {
        const card = document.createElement('div');
        card.className = 'activity-card';
        card.onclick = () => this.showActivityModal(activity);
        
        const statusClass = `status-${activity.status}`;
        const formattedDate = this.formatDate(activity.date);
        
        card.innerHTML = `
            <div class="activity-image">
                <img src="${activity.image}" alt="${activity.title}" onerror="this.src='../assets/images/placeholder-activity.jpg'">
                <div class="activity-category">${this.formatCategory(activity.category)}</div>
            </div>
            
            <div class="activity-content">
                <h3 class="activity-title">${activity.title}</h3>
                <p class="activity-description">${activity.description}</p>
                
                <div class="activity-meta">
                    <div class="activity-date">
                        <i class="fas fa-calendar-alt"></i>
                        ${formattedDate}
                    </div>
                    <div class="activity-status ${statusClass}">
                        ${activity.status}
                    </div>
                </div>
                
                <div class="activity-actions">
                    <button class="btn-small btn-primary-small" onclick="event.stopPropagation(); window.activitiesManager.showActivityModal(${JSON.stringify(activity).replace(/"/g, '&quot;')})">
                        <i class="fas fa-eye"></i>
                        View Details
                    </button>
                    <button class="btn-small btn-outline-small" onclick="event.stopPropagation(); window.activitiesManager.shareActivity(${activity.id})">
                        <i class="fas fa-share-alt"></i>
                        Share
                    </button>
                </div>
            </div>
        `;
        
        return card;
    }
    
    showActivityModal(activity) {
        const modal = document.getElementById('activityModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalImage = document.getElementById('modalImage');
        const modalCategory = document.getElementById('modalCategory');
        const modalDate = document.getElementById('modalDate');
        const modalDescription = document.getElementById('modalDescription');
        const modalObjectives = document.getElementById('modalObjectives');
        const modalImpact = document.getElementById('modalImpact');
        const modalGallery = document.getElementById('modalGallery');
        
        if (!modal) return;
        
        // Set modal content
        modalTitle.textContent = activity.title;
        modalImage.src = activity.image;
        modalImage.alt = activity.title;
        modalCategory.textContent = this.formatCategory(activity.category);
        modalDate.innerHTML = `<i class="fas fa-calendar-alt"></i> ${this.formatDate(activity.date)}`;
        
        modalDescription.innerHTML = `
            <h3>About This Activity</h3>
            <p>${activity.description}</p>
        `;
        
        if (activity.objectives && activity.objectives.length > 0) {
            modalObjectives.innerHTML = `
                <h3>Objectives</h3>
                <ul>
                    ${activity.objectives.map(obj => `<li>${obj}</li>`).join('')}
                </ul>
            `;
        } else {
            modalObjectives.innerHTML = '';
        }
        
        if (activity.impact && activity.impact.length > 0) {
            modalImpact.innerHTML = `
                <h3>Impact & Results</h3>
                <ul>
                    ${activity.impact.map(impact => `<li>${impact}</li>`).join('')}
                </ul>
            `;
        } else {
            modalImpact.innerHTML = '';
        }
        
        if (activity.gallery && activity.gallery.length > 0) {
            modalGallery.innerHTML = `
                <h3>Gallery</h3>
                <div class="gallery-grid">
                    ${activity.gallery.map(img => `
                        <div class="gallery-item" onclick="window.activitiesManager.showImageModal('${img}')">
                            <img src="${img}" alt="Activity Gallery" onerror="this.parentElement.style.display='none'">
                        </div>
                    `).join('')}
                </div>
            `;
        } else {
            modalGallery.innerHTML = '';
        }
        
        // Show modal
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    
    closeActivityModal() {
        const modal = document.getElementById('activityModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
    
    loadMoreActivities() {
        this.currentPage++;
        this.displayActivities();
    }
    
    async loadStats() {
        try {
            const response = await fetch('../backend/controllers/ActivityController.php?action=get_activity_stats');
            const data = await response.json();
            
            if (data.success && data.stats) {
                this.updateStatsDisplay(data.stats);
            }
        } catch (error) {
            console.error('Error loading stats:', error);
            // Use default stats
            this.updateStatsDisplay({
                legal_cases: 500,
                beneficiaries: 10000,
                training_programs: 200,
                campaigns: 50
            });
        }
    }
    
    updateStatsDisplay(stats) {
        const elements = {
            legalCasesCount: stats.legal_cases || 500,
            beneficiariesCount: stats.beneficiaries || 10000,
            trainingProgramsCount: stats.training_programs || 200,
            campaignsCount: stats.campaigns || 50
        };
        
        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                this.animateCounter(element, value);
            }
        });
    }
    
    animateCounter(element, targetValue) {
        const duration = 2000;
        const startValue = 0;
        const startTime = performance.now();
        
        const updateCounter = (currentTime) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            const currentValue = Math.floor(startValue + (targetValue - startValue) * progress);
            element.textContent = currentValue.toLocaleString() + '+';
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            }
        };
        
        requestAnimationFrame(updateCounter);
    }
    
    async subscribeNewsletter(form) {
        const email = form.querySelector('input[type="email"]').value;
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (!email) {
            this.showToast('Please enter your email address', 'error');
            return;
        }
        
        try {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Subscribing...';
            
            const response = await fetch('../backend/controllers/NewsletterController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=subscribe&email=${encodeURIComponent(email)}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('Successfully subscribed to newsletter!', 'success');
                form.reset();
            } else {
                throw new Error(data.message || 'Subscription failed');
            }
        } catch (error) {
            console.error('Newsletter subscription error:', error);
            this.showToast('Failed to subscribe. Please try again.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Subscribe';
        }
    }
    
    shareActivity(activityId) {
        const activity = this.activities.find(a => a.id === activityId);
        if (!activity) return;
        
        if (navigator.share) {
            navigator.share({
                title: activity.title,
                text: activity.description,
                url: window.location.href + `?activity=${activityId}`
            }).catch(console.error);
        } else {
            // Fallback to copying URL
            const url = window.location.href + `?activity=${activityId}`;
            navigator.clipboard.writeText(url).then(() => {
                this.showToast('Activity link copied to clipboard!', 'success');
            }).catch(() => {
                this.showToast('Unable to copy link', 'error');
            });
        }
    }
    
    getInvolved() {
        window.location.href = 'join-us.html';
    }
    
    showImageModal(imageSrc) {
        // Create and show image modal
        const imageModal = document.createElement('div');
        imageModal.className = 'modal';
        imageModal.innerHTML = `
            <div class="modal-content" style="max-width: 90%; text-align: center;">
                <div class="modal-header">
                    <h2>Gallery Image</h2>
                    <button class="modal-close" onclick="this.closest('.modal').remove(); document.body.style.overflow='auto';">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <img src="${imageSrc}" alt="Gallery Image" style="max-width: 100%; height: auto; border-radius: 8px;">
                </div>
            </div>
        `;
        
        document.body.appendChild(imageModal);
        imageModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        imageModal.addEventListener('click', (e) => {
            if (e.target === imageModal) {
                imageModal.remove();
                document.body.style.overflow = 'auto';
            }
        });
    }
    
    // Utility methods
    formatCategory(category) {
        return category.split('-').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    }
    
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    showLoading(show) {
        const spinner = document.getElementById('loadingSpinner');
        const grid = document.getElementById('activitiesGrid');
        
        if (spinner) {
            spinner.style.display = show ? 'flex' : 'none';
        }
        if (grid && show) {
            grid.innerHTML = '';
        }
    }
    
    showError(message) {
        this.showToast(message, 'error');
    }
    
    showToast(message, type = 'info') {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        // Add toast styles if not already present
        if (!document.querySelector('#toast-styles')) {
            const styles = document.createElement('style');
            styles.id = 'toast-styles';
            styles.textContent = `
                .toast {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 1rem 1.5rem;
                    border-radius: 8px;
                    color: white;
                    font-weight: 500;
                    z-index: 10000;
                    animation: slideInRight 0.3s ease;
                }
                .toast-success { background: #48bb78; }
                .toast-error { background: #f56565; }
                .toast-info { background: #4299e1; }
                .toast-content {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                }
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(styles);
        }
        
        document.body.appendChild(toast);
        
        // Remove toast after 3 seconds
        setTimeout(() => {
            toast.style.animation = 'slideInRight 0.3s ease reverse';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Global functions for modal and interactions
window.closeActivityModal = () => {
    if (window.activitiesManager) {
        window.activitiesManager.closeActivityModal();
    }
};

window.getInvolved = () => {
    if (window.activitiesManager) {
        window.activitiesManager.getInvolved();
    }
};

window.shareActivity = () => {
    // This will be called from modal context
    if (window.activitiesManager) {
        window.activitiesManager.shareActivity(window.currentActivityId);
    }
};

window.clearFilters = () => {
    // Reset all filters
    document.getElementById('searchInput').value = '';
    document.querySelector('.filter-btn[data-category="all"]').click();
    document.getElementById('sortSelect').value = 'date-desc';
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.activitiesManager = new ActivitiesManager();
});

// Handle URL parameters for direct activity links
window.addEventListener('load', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const activityId = urlParams.get('activity');
    
    if (activityId && window.activitiesManager) {
        // Wait for activities to load, then show the specific activity
        setTimeout(() => {
            const activity = window.activitiesManager.activities.find(a => a.id == activityId);
            if (activity) {
                window.activitiesManager.showActivityModal(activity);
            }
        }, 1000);
    }
});