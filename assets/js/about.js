/**
 * About Page JavaScript
 * Handles counter animations, timeline interactions, and other about page specific functionality
 */

class AboutPage {
    constructor() {
        this.counters = document.querySelectorAll('.stat-number[data-count]');
        this.timelineItems = document.querySelectorAll('.timeline-item');
        this.teamMembers = document.querySelectorAll('.team-member');
        this.observerOptions = {
            threshold: 0.5,
            rootMargin: '0px 0px -50px 0px'
        };
        
        this.init();
    }
    
    init() {
        this.setupIntersectionObserver();
        this.setupCounterAnimations();
        this.setupTimelineAnimations();
        this.setupTeamInteractions();
        this.setupAccessibility();
    }
    
    /**
     * Setup Intersection Observer for scroll-triggered animations
     */
    setupIntersectionObserver() {
        if ('IntersectionObserver' in window) {
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                        
                        // Trigger counter animation if it's a stat number
                        if (entry.target.classList.contains('stat-number')) {
                            this.animateCounter(entry.target);
                        }
                    }
                });
            }, this.observerOptions);
            
            // Observe elements
            this.counters.forEach(counter => this.observer.observe(counter));
            this.timelineItems.forEach(item => this.observer.observe(item));
            this.teamMembers.forEach(member => this.observer.observe(member));
        }
    }
    
    /**
     * Setup counter animations
     */
    setupCounterAnimations() {
        this.counters.forEach(counter => {
            counter.textContent = '0';
        });
    }
    
    /**
     * Animate counter from 0 to target value
     */
    animateCounter(element) {
        if (element.classList.contains('counted')) return;
        
        const target = parseInt(element.dataset.count);
        const duration = 2000; // 2 seconds
        const increment = target / (duration / 16); // 60fps
        let current = 0;
        
        element.classList.add('counting', 'counted');
        
        const timer = setInterval(() => {
            current += increment;
            
            if (current >= target) {
                current = target;
                clearInterval(timer);
                element.classList.remove('counting');
            }
            
            // Format number with commas for large numbers
            element.textContent = this.formatNumber(Math.floor(current));
        }, 16);
    }
    
    /**
     * Format number with commas
     */
    formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    /**
     * Setup timeline animations
     */
    setupTimelineAnimations() {
        this.timelineItems.forEach((item, index) => {
            // Add staggered animation delay
            item.style.animationDelay = `${index * 0.2}s`;
            
            // Add hover effects
            item.addEventListener('mouseenter', () => {
                this.highlightTimelineItem(item);
            });
            
            item.addEventListener('mouseleave', () => {
                this.unhighlightTimelineItem(item);
            });
        });
    }
    
    /**
     * Highlight timeline item on hover
     */
    highlightTimelineItem(item) {
        const marker = item.querySelector('.timeline-marker');
        const content = item.querySelector('.timeline-content');
        
        if (marker) {
            marker.style.transform = 'translateX(-50%) scale(1.2)';
            marker.style.backgroundColor = '#10B981';
        }
        
        if (content) {
            content.style.transform = 'scale(1.02)';
            content.style.boxShadow = '0 8px 30px rgba(0, 0, 0, 0.15)';
        }
    }
    
    /**
     * Remove highlight from timeline item
     */
    unhighlightTimelineItem(item) {
        const marker = item.querySelector('.timeline-marker');
        const content = item.querySelector('.timeline-content');
        
        if (marker) {
            marker.style.transform = 'translateX(-50%) scale(1)';
            marker.style.backgroundColor = '#2563EB';
        }
        
        if (content) {
            content.style.transform = 'scale(1)';
            content.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.1)';
        }
    }
    
    /**
     * Setup team member interactions
     */
    setupTeamInteractions() {
        this.teamMembers.forEach(member => {
            const photo = member.querySelector('.member-photo');
            const name = member.querySelector('.member-name');
            
            member.addEventListener('mouseenter', () => {
                if (photo) {
                    photo.style.transform = 'scale(1.05)';
                }
                if (name) {
                    name.style.color = '#2563EB';
                }
            });
            
            member.addEventListener('mouseleave', () => {
                if (photo) {
                    photo.style.transform = 'scale(1)';
                }
                if (name) {
                    name.style.color = '#1F2937';
                }
            });
            
            // Add click handler for potential modal or detail view
            member.addEventListener('click', () => {
                this.showMemberDetails(member);
            });
        });
    }
    
    /**
     * Show member details (placeholder for future implementation)
     */
    showMemberDetails(member) {
        const memberName = member.querySelector('.member-name')?.textContent;
        const memberRole = member.querySelector('.member-role')?.textContent;
        
        // For now, just show an alert. In a real implementation, 
        // this could open a modal with detailed information
        console.log(`Clicked on ${memberName} - ${memberRole}`);
        
        // Could implement modal here:
        // this.openMemberModal(memberData);
    }
    
    /**
     * Setup accessibility features
     */
    setupAccessibility() {
        // Add keyboard navigation for timeline items
        this.timelineItems.forEach((item, index) => {
            item.setAttribute('tabindex', '0');
            item.setAttribute('role', 'article');
            item.setAttribute('aria-label', `Timeline item ${index + 1}`);
            
            item.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.focusTimelineItem(item);
                }
            });
        });
        
        // Add keyboard navigation for team members
        this.teamMembers.forEach((member, index) => {
            member.setAttribute('tabindex', '0');
            member.setAttribute('role', 'button');
            member.setAttribute('aria-label', `Team member ${index + 1}`);
            
            member.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.showMemberDetails(member);
                }
            });
        });
        
        // Add ARIA labels to statistics
        this.counters.forEach((counter, index) => {
            const label = counter.nextElementSibling?.textContent || `Statistic ${index + 1}`;
            counter.setAttribute('aria-label', `${counter.textContent} ${label}`);
        });
    }
    
    /**
     * Focus timeline item for accessibility
     */
    focusTimelineItem(item) {
        // Scroll item into view
        item.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
        
        // Temporarily highlight the item
        this.highlightTimelineItem(item);
        setTimeout(() => {
            this.unhighlightTimelineItem(item);
        }, 2000);
    }
    
    /**
     * Smooth scroll to sections
     */
    setupSmoothScrolling() {
        const links = document.querySelectorAll('a[href^="#"]');
        
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = link.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }
    
    /**
     * Handle window resize
     */
    handleResize() {
        // Recalculate timeline positions on resize
        this.timelineItems.forEach(item => {
            const content = item.querySelector('.timeline-content');
            if (content) {
                // Reset any inline styles that might interfere with responsive design
                content.style.transform = '';
            }
        });
    }
    
    /**
     * Cleanup method
     */
    destroy() {
        if (this.observer) {
            this.observer.disconnect();
        }
        
        // Remove event listeners
        this.timelineItems.forEach(item => {
            item.removeEventListener('mouseenter', this.highlightTimelineItem);
            item.removeEventListener('mouseleave', this.unhighlightTimelineItem);
        });
        
        this.teamMembers.forEach(member => {
            member.removeEventListener('click', this.showMemberDetails);
        });
    }
}

// Utility functions for about page
const AboutUtils = {
    /**
     * Animate elements on scroll
     */
    animateOnScroll: function(elements, className = 'animate-in') {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add(className);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        elements.forEach(el => observer.observe(el));
        return observer;
    },
    
    /**
     * Create parallax effect for background elements
     */
    createParallax: function() {
        const parallaxElements = document.querySelectorAll('[data-parallax]');
        
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            
            parallaxElements.forEach(element => {
                const rate = scrolled * -0.5;
                element.style.transform = `translateY(${rate}px)`;
            });
        });
    },
    
    /**
     * Add loading states to images
     */
    handleImageLoading: function() {
        const images = document.querySelectorAll('img[loading="lazy"]');
        
        images.forEach(img => {
            img.addEventListener('load', () => {
                img.classList.add('loaded');
            });
            
            img.addEventListener('error', () => {
                img.classList.add('error');
                // Could set a fallback image here
            });
        });
    }
};

// Initialize about page when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const aboutPage = new AboutPage();
    
    // Setup additional utilities
    AboutUtils.handleImageLoading();
    
    // Handle window resize
    window.addEventListener('resize', () => {
        aboutPage.handleResize();
    });
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        aboutPage.destroy();
    });
});

// Export for potential use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { AboutPage, AboutUtils };
}