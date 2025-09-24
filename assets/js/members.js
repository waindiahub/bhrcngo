// Members Page JavaScript

class MembersManager {
    constructor() {
        this.members = [];
        this.filteredMembers = [];
        this.currentPage = 1;
        this.membersPerPage = 12;
        this.currentCategory = 'all';
        this.currentView = 'grid';
        this.searchQuery = '';
        this.isLoading = false;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadMembers();
        this.updateStats();
    }
    
    bindEvents() {
        // Filter tabs
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                this.handleCategoryFilter(e.target.dataset.category);
            });
        });
        
        // Search
        const searchInput = document.getElementById('memberSearch');
        if (searchInput) {
            searchInput.addEventListener('input', debounce((e) => {
                this.handleSearch(e.target.value);
            }, 300));
        }
        
        // View toggle
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleViewToggle(e.target.dataset.view);
            });
        });
        
        // Load more
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => {
                this.loadMoreMembers();
            });
        }
        
        // Modal events
        const modal = document.getElementById('memberModal');
        const closeModal = document.getElementById('closeModal');
        
        if (closeModal) {
            closeModal.addEventListener('click', () => {
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
        
        // Newsletter form
        const newsletterForm = document.getElementById('newsletterForm');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', (e) => {
                this.handleNewsletterSubmit(e);
            });
        }
        
        // Keyboard events
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    }
    
    async loadMembers() {
        this.showLoading();
        
        try {
            // Simulate API call - replace with actual endpoint
            const response = await this.fetchMembers();
            this.members = response.members || this.generateSampleMembers();
            this.filterMembers();
            this.renderMembers();
        } catch (error) {
            console.error('Error loading members:', error);
            this.members = this.generateSampleMembers();
            this.filterMembers();
            this.renderMembers();
        } finally {
            this.hideLoading();
        }
    }
    
    async fetchMembers() {
        // Replace with actual API endpoint
        const response = await fetch(window.BHRC_CONFIG ? window.BHRC_CONFIG.API.BASE_URL + ''.replace('/api', '') : 'https://bhrcdata.online/backend/api'.replace('/api', '')));
        if (!response.ok) {
            throw new Error('Failed to fetch members');
        }
        return await response.json();
    }
    
    generateSampleMembers() {
        return [
            {
                id: 1,
                name: 'Dr. Rajesh Kumar',
                position: 'Chairman',
                category: 'board',
                image: '../assets/images/members/member1.jpg',
                bio: 'Dr. Rajesh Kumar is a renowned human rights advocate with over 20 years of experience in social justice and legal reform.',
                experience: '20+ years',
                specialization: 'Human Rights Law',
                education: 'LLM Human Rights, Delhi University',
                email: 'rajesh.kumar@bhrcindia.in',
                phone: '+91-9876543210',
                social: {
                    linkedin: 'https://linkedin.com/in/rajeshkumar',
                    twitter: 'https://twitter.com/rajeshkumar'
                }
            },
            {
                id: 2,
                name: 'Ms. Priya Sharma',
                position: 'Vice Chairperson',
                category: 'board',
                image: '../assets/images/members/member2.jpg',
                bio: 'Ms. Priya Sharma is a dedicated social worker focusing on women\'s rights and child protection.',
                experience: '15+ years',
                specialization: 'Women & Child Rights',
                education: 'MSW, Patna University',
                email: 'priya.sharma@bhrcindia.in',
                phone: '+91-9876543211',
                social: {
                    linkedin: 'https://linkedin.com/in/priyasharma',
                    facebook: 'https://facebook.com/priyasharma'
                }
            },
            {
                id: 3,
                name: 'Adv. Suresh Yadav',
                position: 'Legal Advisor',
                category: 'advisors',
                image: '../assets/images/members/member3.jpg',
                bio: 'Advocate Suresh Yadav specializes in constitutional law and has represented numerous human rights cases.',
                experience: '18+ years',
                specialization: 'Constitutional Law',
                education: 'LLB, Patna High Court',
                email: 'suresh.yadav@bhrcindia.in',
                phone: '+91-9876543212',
                social: {
                    linkedin: 'https://linkedin.com/in/sureshyadav'
                }
            },
            {
                id: 4,
                name: 'Mr. Amit Singh',
                position: 'Program Manager',
                category: 'staff',
                image: '../assets/images/members/member4.jpg',
                bio: 'Mr. Amit Singh manages various human rights programs and community outreach initiatives.',
                experience: '8+ years',
                specialization: 'Program Management',
                education: 'MBA, IIM Patna',
                email: 'amit.singh@bhrcindia.in',
                phone: '+91-9876543213',
                social: {
                    linkedin: 'https://linkedin.com/in/amitsingh',
                    twitter: 'https://twitter.com/amitsingh'
                }
            },
            {
                id: 5,
                name: 'Ms. Sunita Devi',
                position: 'Community Coordinator',
                category: 'staff',
                image: '../assets/images/members/member5.jpg',
                bio: 'Ms. Sunita Devi works closely with local communities to identify and address human rights issues.',
                experience: '6+ years',
                specialization: 'Community Development',
                education: 'MA Social Work, Magadh University',
                email: 'sunita.devi@bhrcindia.in',
                phone: '+91-9876543214',
                social: {
                    facebook: 'https://facebook.com/sunitadevi'
                }
            },
            {
                id: 6,
                name: 'Mr. Ravi Kumar',
                position: 'Volunteer Coordinator',
                category: 'volunteers',
                image: '../assets/images/members/member6.jpg',
                bio: 'Mr. Ravi Kumar coordinates volunteer activities and manages community engagement programs.',
                experience: '4+ years',
                specialization: 'Volunteer Management',
                education: 'BA Social Science, Patna University',
                email: 'ravi.kumar@bhrcindia.in',
                phone: '+91-9876543215',
                social: {
                    linkedin: 'https://linkedin.com/in/ravikumar'
                }
            }
        ];
    }
    
    handleCategoryFilter(category) {
        this.currentCategory = category;
        this.currentPage = 1;
        
        // Update active tab
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`[data-category="${category}"]`).classList.add('active');
        
        this.filterMembers();
        this.renderMembers();
    }
    
    handleSearch(query) {
        this.searchQuery = query.toLowerCase();
        this.currentPage = 1;
        this.filterMembers();
        this.renderMembers();
    }
    
    handleViewToggle(view) {
        this.currentView = view;
        
        // Update active view button
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-view="${view}"]`).classList.add('active');
        
        // Update grid class
        const grid = document.getElementById('membersGrid');
        if (grid) {
            if (view === 'list') {
                grid.classList.add('list-view');
            } else {
                grid.classList.remove('list-view');
            }
        }
    }
    
    filterMembers() {
        this.filteredMembers = this.members.filter(member => {
            const matchesCategory = this.currentCategory === 'all' || member.category === this.currentCategory;
            const matchesSearch = !this.searchQuery || 
                member.name.toLowerCase().includes(this.searchQuery) ||
                member.position.toLowerCase().includes(this.searchQuery) ||
                member.specialization.toLowerCase().includes(this.searchQuery);
            
            return matchesCategory && matchesSearch;
        });
    }
    
    renderMembers() {
        const grid = document.getElementById('membersGrid');
        const emptyState = document.getElementById('emptyState');
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        
        if (!grid) return;
        
        if (this.filteredMembers.length === 0) {
            grid.innerHTML = '';
            emptyState.style.display = 'block';
            loadMoreBtn.style.display = 'none';
            return;
        }
        
        emptyState.style.display = 'none';
        
        const startIndex = 0;
        const endIndex = this.currentPage * this.membersPerPage;
        const membersToShow = this.filteredMembers.slice(startIndex, endIndex);
        
        grid.innerHTML = membersToShow.map(member => this.createMemberCard(member)).join('');
        
        // Show/hide load more button
        if (endIndex >= this.filteredMembers.length) {
            loadMoreBtn.style.display = 'none';
        } else {
            loadMoreBtn.style.display = 'block';
        }
        
        // Add click events to member cards
        this.bindMemberCardEvents();
        
        // Add animation
        this.animateCards();
    }
    
    createMemberCard(member) {
        return `
            <div class="member-card fade-in" data-member-id="${member.id}">
                <div class="member-image">
                    <img src="${member.image}" alt="${member.name}" onerror="this.src='../assets/images/placeholder-member.jpg'">
                    <div class="member-category-badge">${this.getCategoryLabel(member.category)}</div>
                </div>
                <div class="member-info">
                    <h3 class="member-name">${member.name}</h3>
                    <div class="member-position">${member.position}</div>
                    <div class="member-bio">${member.bio}</div>
                    <div class="member-details">
                        <div class="member-experience">
                            <i class="fas fa-clock"></i>
                            ${member.experience}
                        </div>
                        <div class="member-contact">
                            ${member.email ? `<button class="contact-btn" title="Email"><i class="fas fa-envelope"></i></button>` : ''}
                            ${member.phone ? `<button class="contact-btn" title="Phone"><i class="fas fa-phone"></i></button>` : ''}
                            <button class="contact-btn" title="View Details"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    getCategoryLabel(category) {
        const labels = {
            'board': 'Board Member',
            'staff': 'Staff',
            'volunteers': 'Volunteer',
            'advisors': 'Advisor'
        };
        return labels[category] || 'Member';
    }
    
    bindMemberCardEvents() {
        document.querySelectorAll('.member-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (!e.target.closest('.contact-btn')) {
                    const memberId = parseInt(card.dataset.memberId);
                    this.openMemberModal(memberId);
                }
            });
        });
        
        document.querySelectorAll('.contact-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const card = e.target.closest('.member-card');
                const memberId = parseInt(card.dataset.memberId);
                const member = this.members.find(m => m.id === memberId);
                
                if (btn.title === 'Email' && member.email) {
                    window.location.href = `mailto:${member.email}`;
                } else if (btn.title === 'Phone' && member.phone) {
                    window.location.href = `tel:${member.phone}`;
                } else if (btn.title === 'View Details') {
                    this.openMemberModal(memberId);
                }
            });
        });
    }
    
    openMemberModal(memberId) {
        const member = this.members.find(m => m.id === memberId);
        if (!member) return;
        
        const modal = document.getElementById('memberModal');
        if (!modal) return;
        
        // Populate modal content
        document.getElementById('modalMemberName').textContent = member.name;
        document.getElementById('modalMemberPosition').textContent = member.position;
        document.getElementById('modalMemberCategory').textContent = this.getCategoryLabel(member.category);
        document.getElementById('modalMemberBio').textContent = member.bio;
        document.getElementById('modalMemberExperience').textContent = member.experience;
        document.getElementById('modalMemberSpecialization').textContent = member.specialization;
        document.getElementById('modalMemberEducation').textContent = member.education;
        
        const modalImage = document.getElementById('modalMemberImage');
        modalImage.src = member.image;
        modalImage.alt = member.name;
        modalImage.onerror = function() {
            this.src = '../assets/images/placeholder-member.jpg';
        };
        
        // Contact information
        const emailElement = document.getElementById('modalMemberEmail');
        const phoneElement = document.getElementById('modalMemberPhone');
        
        if (member.email) {
            emailElement.style.display = 'flex';
            emailElement.querySelector('a').href = `mailto:${member.email}`;
            emailElement.querySelector('a').textContent = member.email;
        } else {
            emailElement.style.display = 'none';
        }
        
        if (member.phone) {
            phoneElement.style.display = 'flex';
            phoneElement.querySelector('span').textContent = member.phone;
        } else {
            phoneElement.style.display = 'none';
        }
        
        // Social links
        const socialContainer = document.getElementById('modalMemberSocial');
        socialContainer.innerHTML = '';
        
        if (member.social) {
            Object.entries(member.social).forEach(([platform, url]) => {
                const link = document.createElement('a');
                link.href = url;
                link.target = '_blank';
                link.className = 'social-link';
                link.innerHTML = `<i class="fab fa-${platform}"></i>`;
                socialContainer.appendChild(link);
            });
        }
        
        // Show modal
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Focus management
        const closeBtn = document.getElementById('closeModal');
        if (closeBtn) {
            closeBtn.focus();
        }
    }
    
    closeModal() {
        const modal = document.getElementById('memberModal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
    
    loadMoreMembers() {
        this.currentPage++;
        this.renderMembers();
    }
    
    animateCards() {
        const cards = document.querySelectorAll('.member-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    }
    
    showLoading() {
        this.isLoading = true;
        const loadingState = document.getElementById('loadingState');
        if (loadingState) {
            loadingState.style.display = 'block';
        }
    }
    
    hideLoading() {
        this.isLoading = false;
        const loadingState = document.getElementById('loadingState');
        if (loadingState) {
            loadingState.style.display = 'none';
        }
    }
    
    async updateStats() {
        try {
            // Simulate API call for stats
            const stats = await this.fetchStats();
            this.renderStats(stats);
        } catch (error) {
            console.error('Error loading stats:', error);
            // Use default stats
            this.renderStats({
                totalMembers: 50,
                boardMembers: 12,
                volunteers: 200,
                experience: 15
            });
        }
    }
    
    async fetchStats() {
        // Replace with actual API endpoint
        const response = await fetch(window.BHRC_CONFIG.getEndpointUrl('MEMBERS_STATS'));
        if (!response.ok) {
            throw new Error('Failed to fetch stats');
        }
        return await response.json();
    }
    
    renderStats(stats) {
        const elements = {
            totalMembers: document.getElementById('totalMembers'),
            boardMembers: document.getElementById('boardMembers'),
            volunteers: document.getElementById('volunteers'),
            experience: document.getElementById('experience')
        };
        
        Object.entries(stats).forEach(([key, value]) => {
            if (elements[key]) {
                this.animateCounter(elements[key], value);
            }
        });
    }
    
    animateCounter(element, target) {
        const duration = 2000;
        const start = 0;
        const increment = target / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            
            if (typeof target === 'string' && target.includes('+')) {
                element.textContent = Math.floor(current) + '+';
            } else {
                element.textContent = Math.floor(current);
            }
        }, 16);
    }
    
    async handleNewsletterSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const email = form.querySelector('input[type="email"]').value;
        const button = form.querySelector('button');
        
        if (!email) {
            this.showToast('Please enter your email address', 'error');
            return;
        }
        
        // Disable button and show loading
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subscribing...';
        
        try {
            await this.subscribeNewsletter(email);
            this.showToast('Successfully subscribed to newsletter!', 'success');
            form.reset();
        } catch (error) {
            console.error('Newsletter subscription error:', error);
            this.showToast('Failed to subscribe. Please try again.', 'error');
        } finally {
            button.disabled = false;
            button.innerHTML = 'Subscribe';
        }
    }
    
    async subscribeNewsletter(email) {
        // Replace with actual API endpoint
        const response = await fetch(window.BHRC_CONFIG ? window.BHRC_CONFIG.API.BASE_URL + ''.replace('/api', '') : 'https://bhrcdata.online/backend/api'.replace('/api', '')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email })
        });
        
        if (!response.ok) {
            throw new Error('Failed to subscribe');
        }
        
        return await response.json();
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
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 5000);
        
        // Close button
        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        });
    }
}

// Utility Functions
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

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new MembersManager();
    
    // Scroll to top functionality
    const scrollToTopBtn = document.getElementById('scrollToTop');
    if (scrollToTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                scrollToTopBtn.classList.add('show');
            } else {
                scrollToTopBtn.classList.remove('show');
            }
        });
        
        scrollToTopBtn.addEventListener('click', scrollToTop);
    }
    
    // Mobile menu functionality (if needed)
    const hamburger = document.querySelector('.hamburger');
    const navList = document.querySelector('.nav-list');
    
    if (hamburger && navList) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navList.classList.toggle('active');
        });
    }
});

// Toast Styles (add to CSS if not already present)
const toastStyles = `
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    padding: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-width: 300px;
    z-index: 10000;
    transform: translateX(400px);
    transition: transform 0.3s ease;
}

.toast.show {
    transform: translateX(0);
}

.toast-success {
    border-left: 4px solid #28a745;
}

.toast-error {
    border-left: 4px solid #dc3545;
}

.toast-info {
    border-left: 4px solid #17a2b8;
}

.toast-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.toast-content i {
    font-size: 1.2rem;
}

.toast-success .toast-content i {
    color: #28a745;
}

.toast-error .toast-content i {
    color: #dc3545;
}

.toast-info .toast-content i {
    color: #17a2b8;
}

.toast-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.25rem;
    color: #6c757d;
    transition: color 0.3s ease;
}

.toast-close:hover {
    color: #495057;
}

.scroll-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    transition: all 0.3s ease;
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px);
    z-index: 1000;
}

.scroll-to-top.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.scroll-to-top:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}
`;

// Add toast styles to head if not already present
if (!document.querySelector('#toast-styles')) {
    const style = document.createElement('style');
    style.id = 'toast-styles';
    style.textContent = toastStyles;
    document.head.appendChild(style);
}
