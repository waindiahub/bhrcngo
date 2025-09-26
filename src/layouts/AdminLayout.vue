<template>
  <div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar" :class="{ 'sidebar-collapsed': sidebarCollapsed }">
      <div class="sidebar-header">
        <div class="logo">
          <img src="@/assets/images/logo.png" alt="BHRC Logo" class="logo-img">
          <span v-if="!sidebarCollapsed" class="logo-text">BHRC Admin</span>
        </div>
        <button class="sidebar-toggle" @click="toggleSidebar">
          <i class="fas fa-bars"></i>
        </button>
      </div>

      <nav class="sidebar-nav">
        <ul class="nav-menu">
          <li class="nav-item">
            <router-link to="/admin/dashboard" class="nav-link">
              <i class="fas fa-tachometer-alt"></i>
              <span v-if="!sidebarCollapsed">Dashboard</span>
            </router-link>
          </li>
          
          <li class="nav-item">
            <router-link to="/admin/members" class="nav-link">
              <i class="fas fa-users"></i>
              <span v-if="!sidebarCollapsed">Members</span>
            </router-link>
          </li>
          
          <li class="nav-item">
            <router-link to="/admin/events" class="nav-link">
              <i class="fas fa-calendar-alt"></i>
              <span v-if="!sidebarCollapsed">Events</span>
            </router-link>
          </li>
          
          <li class="nav-item">
            <router-link to="/admin/activities" class="nav-link">
              <i class="fas fa-tasks"></i>
              <span v-if="!sidebarCollapsed">Activities</span>
            </router-link>
          </li>
          
          <li class="nav-item">
            <router-link to="/admin/gallery" class="nav-link">
              <i class="fas fa-images"></i>
              <span v-if="!sidebarCollapsed">Gallery</span>
            </router-link>
          </li>
          
          <li class="nav-item">
            <router-link to="/admin/applications" class="nav-link">
              <i class="fas fa-file-alt"></i>
              <span v-if="!sidebarCollapsed">Applications</span>
            </router-link>
          </li>
          
          <li class="nav-item">
            <router-link to="/admin/complaints" class="nav-link">
              <i class="fas fa-exclamation-triangle"></i>
              <span v-if="!sidebarCollapsed">Complaints</span>
            </router-link>
          </li>
          
          <li class="nav-item">
            <router-link to="/admin/enquiries" class="nav-link">
              <i class="fas fa-question-circle"></i>
              <span v-if="!sidebarCollapsed">Enquiries</span>
            </router-link>
          </li>
          
          <li class="nav-item">
            <router-link to="/admin/newsletter" class="nav-link">
              <i class="fas fa-newspaper"></i>
              <span v-if="!sidebarCollapsed">Newsletter</span>
            </router-link>
          </li>
          
          <li class="nav-item">
            <router-link to="/admin/testimonials" class="nav-link">
              <i class="fas fa-quote-right"></i>
              <span v-if="!sidebarCollapsed">Testimonials</span>
            </router-link>
          </li>
          
          <li class="nav-item">
            <router-link to="/admin/achievements" class="nav-link">
              <i class="fas fa-trophy"></i>
              <span v-if="!sidebarCollapsed">Achievements</span>
            </router-link>
          </li>
          
          <li class="nav-item">
            <router-link to="/admin/reports" class="nav-link">
              <i class="fas fa-chart-bar"></i>
              <span v-if="!sidebarCollapsed">Reports</span>
            </router-link>
          </li>
          
          <li class="nav-item">
            <router-link to="/admin/analytics" class="nav-link">
              <i class="fas fa-analytics"></i>
              <span v-if="!sidebarCollapsed">Analytics</span>
            </router-link>
          </li>
        </ul>
      </nav>

      <div class="sidebar-footer">
        <div class="user-info" v-if="!sidebarCollapsed">
          <div class="user-avatar">
            <i class="fas fa-user-circle"></i>
          </div>
          <div class="user-details">
            <span class="user-name">{{ currentUser?.name || 'Admin' }}</span>
            <span class="user-role">Administrator</span>
          </div>
        </div>
        <button class="logout-btn" @click="logout" :title="sidebarCollapsed ? 'Logout' : ''">
          <i class="fas fa-sign-out-alt"></i>
          <span v-if="!sidebarCollapsed">Logout</span>
        </button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-main" :class="{ 'main-expanded': sidebarCollapsed }">
      <!-- Top Header -->
      <header class="admin-header">
        <div class="header-left">
          <h1 class="page-title">{{ pageTitle }}</h1>
        </div>
        <div class="header-right">
          <div class="header-actions">
            <!-- Notifications -->
            <div class="notification-dropdown dropdown">
              <button class="btn btn-icon" data-bs-toggle="dropdown">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
              </button>
              <div class="dropdown-menu dropdown-menu-end">
                <h6 class="dropdown-header">Notifications</h6>
                <a class="dropdown-item" href="#">
                  <i class="fas fa-user-plus text-success"></i>
                  New member registration
                </a>
                <a class="dropdown-item" href="#">
                  <i class="fas fa-exclamation-triangle text-warning"></i>
                  New complaint filed
                </a>
                <a class="dropdown-item" href="#">
                  <i class="fas fa-envelope text-info"></i>
                  New enquiry received
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-center" href="#">View all notifications</a>
              </div>
            </div>

            <!-- User Menu -->
            <div class="user-dropdown dropdown">
              <button class="btn btn-user" data-bs-toggle="dropdown">
                <img src="@/assets/images/admin-avatar.jpg" alt="Admin" class="user-avatar">
                <span class="user-name">{{ currentUser?.name || 'Admin' }}</span>
                <i class="fas fa-chevron-down"></i>
              </button>
              <div class="dropdown-menu dropdown-menu-end">
                <a class="dropdown-item" href="#">
                  <i class="fas fa-user"></i>
                  Profile
                </a>
                <a class="dropdown-item" href="#">
                  <i class="fas fa-cog"></i>
                  Settings
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" @click="logout">
                  <i class="fas fa-sign-out-alt"></i>
                  Logout
                </a>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <main class="admin-content">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AdminLayout',
  data() {
    return {
      sidebarCollapsed: false,
      currentUser: null
    }
  },
  computed: {
    pageTitle() {
      const route = this.$route
      const titles = {
        '/admin/dashboard': 'Dashboard',
        '/admin/members': 'Members Management',
        '/admin/events': 'Events Management',
        '/admin/activities': 'Activities Management',
        '/admin/gallery': 'Gallery Management',
        '/admin/applications': 'Applications',
        '/admin/complaints': 'Complaints Management',
        '/admin/enquiries': 'Enquiries',
        '/admin/newsletter': 'Newsletter Management',
        '/admin/testimonials': 'Testimonials',
        '/admin/achievements': 'Achievements',
        '/admin/reports': 'Reports',
        '/admin/analytics': 'Analytics'
      }
      return titles[route.path] || 'Admin Panel'
    }
  },
  methods: {
    toggleSidebar() {
      this.sidebarCollapsed = !this.sidebarCollapsed
      localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed)
    },
    
    logout() {
      if (confirm('Are you sure you want to logout?')) {
        localStorage.removeItem('user')
        localStorage.removeItem('token')
        this.$router.push('/login')
      }
    },
    
    loadUserData() {
      const userData = localStorage.getItem('user')
      if (userData) {
        this.currentUser = JSON.parse(userData)
      }
    }
  },
  
  mounted() {
    // Load sidebar state
    const sidebarState = localStorage.getItem('sidebarCollapsed')
    if (sidebarState !== null) {
      this.sidebarCollapsed = JSON.parse(sidebarState)
    }
    
    // Load user data
    this.loadUserData()
    
    // Initialize Bootstrap dropdowns
    if (typeof bootstrap !== 'undefined') {
      const dropdownElementList = document.querySelectorAll('.dropdown-toggle')
      dropdownElementList.forEach(dropdownToggleEl => {
        new bootstrap.Dropdown(dropdownToggleEl)
      })
    }
  }
}
</script>

<style scoped>
.admin-layout {
  display: flex;
  min-height: 100vh;
  background-color: #f8f9fa;
}

/* Sidebar Styles */
.admin-sidebar {
  width: 280px;
  background: #2c3e50;
  color: white;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
  position: fixed;
  height: 100vh;
  z-index: 1000;
}

.admin-sidebar.sidebar-collapsed {
  width: 80px;
}

.sidebar-header {
  padding: 1.5rem;
  border-bottom: 1px solid #34495e;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.logo {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.logo-img {
  width: 40px;
  height: 40px;
  object-fit: contain;
}

.logo-text {
  font-size: 1.2rem;
  font-weight: 600;
  color: #667eea;
}

.sidebar-toggle {
  background: none;
  border: none;
  color: white;
  font-size: 1.2rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 0.25rem;
  transition: background-color 0.3s ease;
}

.sidebar-toggle:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.sidebar-nav {
  flex: 1;
  padding: 1rem 0;
  overflow-y: auto;
}

.nav-menu {
  list-style: none;
  padding: 0;
  margin: 0;
}

.nav-item {
  margin-bottom: 0.25rem;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.5rem;
  color: #bdc3c7;
  text-decoration: none;
  transition: all 0.3s ease;
  border-radius: 0;
}

.nav-link:hover {
  background-color: rgba(102, 126, 234, 0.2);
  color: white;
}

.nav-link.router-link-active {
  background-color: #667eea;
  color: white;
  border-right: 4px solid #5a6fd8;
}

.nav-link i {
  width: 20px;
  text-align: center;
  font-size: 1.1rem;
}

.sidebar-collapsed .nav-link {
  padding: 1rem;
  justify-content: center;
}

.sidebar-footer {
  padding: 1rem;
  border-top: 1px solid #34495e;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
  padding: 0.5rem;
}

.user-avatar {
  font-size: 2rem;
  color: #667eea;
}

.user-details {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-weight: 600;
  font-size: 0.9rem;
}

.user-role {
  font-size: 0.8rem;
  color: #bdc3c7;
}

.logout-btn {
  width: 100%;
  background: #e74c3c;
  border: none;
  color: white;
  padding: 0.75rem;
  border-radius: 0.5rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-weight: 500;
  transition: background-color 0.3s ease;
}

.logout-btn:hover {
  background: #c0392b;
}

.sidebar-collapsed .logout-btn {
  padding: 0.75rem 0.5rem;
}

/* Main Content Area */
.admin-main {
  flex: 1;
  margin-left: 280px;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
}

.admin-main.main-expanded {
  margin-left: 80px;
}

.admin-header {
  background: white;
  padding: 1rem 2rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 100;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #2c3e50;
  margin: 0;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.btn-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: none;
  background: #f8f9fa;
  color: #6c757d;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  transition: all 0.3s ease;
}

.btn-icon:hover {
  background: #667eea;
  color: white;
}

.notification-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background: #e74c3c;
  color: white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  font-size: 0.7rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-user {
  background: none;
  border: none;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 1rem;
  border-radius: 2rem;
  transition: background-color 0.3s ease;
}

.btn-user:hover {
  background: #f8f9fa;
}

.btn-user .user-avatar {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  object-fit: cover;
}

.btn-user .user-name {
  font-weight: 500;
  color: #2c3e50;
}

.admin-content {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
}

/* Dropdown Styles */
.dropdown-menu {
  border: none;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border-radius: 0.5rem;
  padding: 0.5rem 0;
  min-width: 200px;
}

.dropdown-header {
  padding: 0.5rem 1rem;
  font-weight: 600;
  color: #2c3e50;
  border-bottom: 1px solid #e9ecef;
  margin-bottom: 0.5rem;
}

.dropdown-item {
  padding: 0.5rem 1rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: #2c3e50;
  text-decoration: none;
  transition: background-color 0.3s ease;
}

.dropdown-item:hover {
  background-color: #f8f9fa;
  color: #667eea;
}

.dropdown-item i {
  width: 16px;
  text-align: center;
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .admin-sidebar {
    transform: translateX(-100%);
    width: 280px;
  }
  
  .admin-sidebar.sidebar-show {
    transform: translateX(0);
  }
  
  .admin-main {
    margin-left: 0;
  }
  
  .admin-header {
    padding: 1rem;
  }
  
  .page-title {
    font-size: 1.2rem;
  }
  
  .admin-content {
    padding: 1rem;
  }
  
  .btn-user .user-name {
    display: none;
  }
}
</style>