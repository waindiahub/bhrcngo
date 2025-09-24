/**
 * Component Loader - Dynamically loads header and footer components
 * This script should be included in all HTML pages to load common components
 */

class ComponentLoader {
    constructor() {
        // Determine the correct base path based on current location
        this.basePath = this.getBasePath();
        this.init();
    }

    /**
     * Get the correct base path for components
     */
    getBasePath() {
        const currentPath = window.location.pathname;
        
        // If we're in a subdirectory (admin, member, pages), go up one level
        if (currentPath.includes('/admin/') || currentPath.includes('/member/') || currentPath.includes('/pages/')) {
            return '../components/';
        }
        
        // If we're at root level, use absolute path
        return '/components/';
    }

    /**
     * Initialize the component loader
     */
    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.loadComponents());
        } else {
            this.loadComponents();
        }
    }

    /**
     * Load all components
     */
    async loadComponents() {
        try {
            await Promise.all([
                this.loadHeader(),
                this.loadFooter()
            ]);
            
            // Initialize Bootstrap components after loading
            this.initializeBootstrap();
            
            // Set active navigation item
            this.setActiveNavigation();
            
            console.log('Components loaded successfully');
        } catch (error) {
            console.error('Error loading components:', error);
        }
    }

    /**
     * Load header component
     */
    async loadHeader() {
        const headerContainer = document.getElementById('header-container');
        if (!headerContainer) {
            console.warn('Header container not found');
            return;
        }

        try {
            const response = await fetch(this.basePath + 'header.html');
            if (!response.ok) {
                throw new Error(`Failed to load header: ${response.status}`);
            }
            
            const headerHTML = await response.text();
            headerContainer.innerHTML = headerHTML;
        } catch (error) {
            console.error('Error loading header:', error);
            // Fallback: show a basic header
            headerContainer.innerHTML = '<header class="header"><div class="container"><h1>BHRC</h1></div></header>';
        }
    }

    /**
     * Load footer component
     */
    async loadFooter() {
        const footerContainer = document.getElementById('footer-container');
        if (!footerContainer) {
            console.warn('Footer container not found');
            return;
        }

        try {
            const response = await fetch(this.basePath + 'footer.html');
            if (!response.ok) {
                throw new Error(`Failed to load footer: ${response.status}`);
            }
            
            const footerHTML = await response.text();
            footerContainer.innerHTML = footerHTML;
        } catch (error) {
            console.error('Error loading footer:', error);
            // Fallback: show a basic footer
            footerContainer.innerHTML = '<footer class="footer"><div class="container"><p>&copy; 2024 BHRC. All rights reserved.</p></div></footer>';
        }
    }

    /**
     * Initialize Bootstrap components after loading
     */
    initializeBootstrap() {
        // Initialize Bootstrap dropdowns
        if (typeof bootstrap !== 'undefined') {
            const dropdownElementList = document.querySelectorAll('.dropdown-toggle');
            const dropdownList = [...dropdownElementList].map(dropdownToggleEl => new bootstrap.Dropdown(dropdownToggleEl));
            
            // Initialize Bootstrap collapse
            const collapseElementList = document.querySelectorAll('.collapse');
            const collapseList = [...collapseElementList].map(collapseEl => new bootstrap.Collapse(collapseEl, {
                toggle: false
            }));
        }
    }

    /**
     * Set active navigation item based on current page
     */
    setActiveNavigation() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            
            const href = link.getAttribute('href');
            if (href === currentPath || (currentPath === '/' && href === '/')) {
                link.classList.add('active');
            }
        });
    }

    /**
     * Update page title and meta description
     */
    updatePageMeta(title, description) {
        if (title) {
            document.title = `${title} - Bharatiya Human Rights Council`;
        }
        
        if (description) {
            let metaDescription = document.querySelector('meta[name="description"]');
            if (!metaDescription) {
                metaDescription = document.createElement('meta');
                metaDescription.name = 'description';
                document.head.appendChild(metaDescription);
            }
            metaDescription.content = description;
        }
    }
}

// Initialize component loader
const componentLoader = new ComponentLoader();

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ComponentLoader;
}