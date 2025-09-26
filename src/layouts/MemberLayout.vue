<template>
  <div class="member-layout">
    <!-- Top Navigation -->
    <header class="member-header">
      <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
          <!-- Logo -->
          <router-link to="/" class="navbar-brand">
            <img src="@/assets/images/logo.png" alt="BHRC Logo" class="logo">
            <span class="brand-text">BHRC Member Portal</span>
          </router-link>

          <!-- Mobile Toggle -->
          <button 
            class="navbar-toggler" 
            type="button" 
            @click="toggleMobileMenu"
          >
            <span class="navbar-toggler-icon"></span>
          </button>

          <!-- User Menu -->
          <div class="navbar-nav ms-auto">
            <div class="nav-item dropdown">
              <button class="btn btn-user dropdown-toggle" data-bs-toggle="dropdown">
                <img :src="currentUser?.avatar || '/assets/images/default-avatar.jpg'" alt="User" class="user-avatar">
                <span class="user-name">{{ currentUser?.name || 'Member' }}</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><router-link to="/member/profile" class="dropdown-item">
                  <i class="fas fa-user"></i> Profile
                </router-link></li>
                <li><router-link to="/member/settings" class="dropdown-item">
                  <i class="fas fa-cog"></i> Settings
                </router-link></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" @click="logout">
                  <i class="fas fa-sign-out-alt"></i> Logout
                </a></li>
              </ul>
            </div>
          </div>
        </div>
      </nav>
    </header>

    <div class="member-container">
      <!-- Sidebar Navigation -->
      <aside class="member-sidebar" :class="{ 'sidebar-show': mobileMenuOpen }">
        <div class="sidebar-content">
          <!-- User Info Card -->
          <div class="user-info-card">
            <div class="user-avatar-large">
              <img :src="currentUser?.avatar || '/assets/images/default-avatar.jpg'" alt="User">
            </div>
            <div class="user-details">
              <h5 class="user-name">{{ currentUser?.name || 'Member Name' }}</h5>
              <p class="user-id">ID: {{ currentUser?.member_id || 'BHRC001' }}</p>
              <span class="user-status" :class="currentUser?.status || 'active'">
                {{ (currentUser?.status || 'active').toUpperCase() }}
              </span>
            </div>
          </div>

          <!-- Navigation Menu -->
          <nav class="sidebar-nav">
            <ul class="nav-menu">
              <li class="nav-item">
                <router-link to="/member/dashboard" class="nav-link">
                  <i class="fas fa-tachometer-alt"></i>
                  <span>Dashboard</span>
                </router-link>
              </li>
              
              <li class="nav-item">
                <router-link to="/member/profile" class="nav-link">
                  <i class="fas fa-user"></i>
                  <span>My Profile</span>
                </router-link>
              </li>
              
              <li class="nav-item">
                <router-link to="/member/certificates" class="nav-link">
                  <i class="fas fa-certificate"></i>
                  <span>Certificates</span>
                </router-link>
              </li>
              
              <li class="nav-item">
                <router-link to="/member/events" class="nav-link">
                  <i class="fas fa-calendar-alt"></i>
                  <span>Events</span>
                </router-link>
              </li>
              
              <li class="nav-item">
                <router-link to="/member/donations" class="nav-link">
                  <i class="fas fa-heart"></i>
                  <span>My Donations</span>
                </router-link>
              </li>
              
              <li class="nav-item">
                <router-link to="/member/complaints" class="nav-link">
                  <i class="fas fa-exclamation-triangle"></i>
                  <span>My Complaints</span>
                </router-link>
              </li>
              
              <li class="nav-item">
                <router-link to="/member/settings" class="nav-link">
                  <i class="fas fa-cog"></i>
                  <span>Settings</span>
                </router-link>
              </li>
            </ul>
          </nav>

          <!-- Quick Actions -->
          <div class="quick-actions">
            <h6 class="section-title">Quick Actions</h6>
            <div class="action-buttons">
              <router-link to="/complaint" class="btn btn-action btn-complaint">
                <i class="fas fa-plus"></i>
                File Complaint
              </router-link>
              <router-link to="/donate" class="btn btn-action btn-donate">
                <i class="fas fa-heart"></i>
                Make Donation
              </router-link>
            </div>
          </div>
        </div>
      </aside>

      <!-- Main Content -->
      <main class="member-main">
        <!-- Breadcrumb -->
        <nav class="breadcrumb-nav">
          <ol class="breadcrumb">
            <li class="breadcrumb-item">
              <router-link to="/member/dashboard">Dashboard</router-link>
            </li>
            <li class="breadcrumb-item active">{{ pageTitle }}</li>
          </ol>
        </nav>

        <!-- Page Content -->
        <div class="member-content">
          <router-view />
        </div>
      </main>
    </div>

    <!-- Mobile Overlay -->
    <div 
      class="mobile-overlay" 
      :class="{ 'overlay-show': mobileMenuOpen }"
      @click="closeMobileMenu"
    ></div>
  </div>
</template>

<script>
export default {
  name: 'MemberLayout',
  data() {
    return {
      mobileMenuOpen: false,
      currentUser: null
    }
  },
  computed: {
    pageTitle() {
      const route = this.$route
      const titles = {
        '/member/dashboard': 'Dashboard',
        '/member/profile': 'My Profile',
        '/member/certificates': 'Certificates',
        '/member/events': 'Events',
        '/member/donations': 'My Donations',
        '/member/complaints': 'My Complaints',
        '/member/settings': 'Settings'
      }
      return titles[route.path] || 'Member Portal'
    }
  },
  methods: {
    toggleMobileMenu() {
      this.mobileMenuOpen = !this.mobileMenuOpen
    },
    
    closeMobileMenu() {
      this.mobileMenuOpen = false
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
.member-layout {
  min-height: 100vh;
  background-color: #f8f9fa;
}

/* Header Styles */
.member-header {
  background: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 1000;
}

.navbar {
  padding: 1rem 0;
}

.navbar-brand {
  display: flex;
  align-items: center;
  gap: 1rem;
  text-decoration: none;
  font-weight: 600;
  color: #2c3e50;
}

.logo {
  width: 40px;
  height: 40px;
  object-fit: contain;
}

.brand-text {
  color: #667eea;
  font-size: 1.2rem;
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

.user-avatar {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  object-fit: cover;
}

.user-name {
  font-weight: 500;
  color: #2c3e50;
}

/* Container */
.member-container {
  display: flex;
  min-height: calc(100vh - 80px);
}

/* Sidebar Styles */
.member-sidebar {
  width: 300px;
  background: white;
  box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
  position: fixed;
  height: calc(100vh - 80px);
  overflow-y: auto;
  z-index: 100;
  transition: transform 0.3s ease;
}

.sidebar-content {
  padding: 2rem 1.5rem;
}

.user-info-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2rem;
  border-radius: 1rem;
  text-align: center;
  margin-bottom: 2rem;
}

.user-avatar-large {
  margin-bottom: 1rem;
}

.user-avatar-large img {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid rgba(255, 255, 255, 0.3);
}

.user-details h5 {
  margin-bottom: 0.5rem;
  font-weight: 600;
}

.user-id {
  margin-bottom: 1rem;
  opacity: 0.9;
  font-size: 0.9rem;
}

.user-status {
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.8rem;
  font-weight: 600;
  background: rgba(255, 255, 255, 0.2);
}

.user-status.active {
  background: #28a745;
}

.user-status.inactive {
  background: #6c757d;
}

.sidebar-nav {
  margin-bottom: 2rem;
}

.nav-menu {
  list-style: none;
  padding: 0;
  margin: 0;
}

.nav-item {
  margin-bottom: 0.5rem;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  color: #6c757d;
  text-decoration: none;
  border-radius: 0.5rem;
  transition: all 0.3s ease;
}

.nav-link:hover {
  background-color: rgba(102, 126, 234, 0.1);
  color: #667eea;
}

.nav-link.router-link-active {
  background-color: #667eea;
  color: white;
}

.nav-link i {
  width: 20px;
  text-align: center;
}

.quick-actions {
  border-top: 1px solid #e9ecef;
  padding-top: 1.5rem;
}

.section-title {
  font-size: 0.9rem;
  font-weight: 600;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 1rem;
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.btn-action {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem;
  border-radius: 0.5rem;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.9rem;
  transition: all 0.3s ease;
}

.btn-complaint {
  background: #e74c3c;
  color: white;
}

.btn-complaint:hover {
  background: #c0392b;
  color: white;
}

.btn-donate {
  background: #28a745;
  color: white;
}

.btn-donate:hover {
  background: #218838;
  color: white;
}

/* Main Content */
.member-main {
  flex: 1;
  margin-left: 300px;
  padding: 2rem;
}

.breadcrumb-nav {
  margin-bottom: 2rem;
}

.breadcrumb {
  background: white;
  padding: 1rem;
  border-radius: 0.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  margin-bottom: 0;
}

.breadcrumb-item a {
  color: #667eea;
  text-decoration: none;
}

.breadcrumb-item a:hover {
  text-decoration: underline;
}

.member-content {
  background: white;
  border-radius: 1rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  min-height: 500px;
}

/* Dropdown Styles */
.dropdown-menu {
  border: none;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border-radius: 0.5rem;
  padding: 0.5rem 0;
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

/* Mobile Styles */
.mobile-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  z-index: 99;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.mobile-overlay.overlay-show {
  opacity: 1;
}

@media (max-width: 768px) {
  .member-sidebar {
    transform: translateX(-100%);
    width: 280px;
  }
  
  .member-sidebar.sidebar-show {
    transform: translateX(0);
  }
  
  .member-main {
    margin-left: 0;
    padding: 1rem;
  }
  
  .mobile-overlay {
    display: block;
  }
  
  .navbar-toggler {
    border: none;
    padding: 0.25rem 0.5rem;
  }
  
  .navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2833, 37, 41, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
  }
  
  .brand-text {
    font-size: 1rem;
  }
  
  .user-name {
    display: none;
  }
  
  .breadcrumb {
    padding: 0.75rem;
  }
  
  .sidebar-content {
    padding: 1.5rem 1rem;
  }
  
  .user-info-card {
    padding: 1.5rem;
  }
}

@media (max-width: 576px) {
  .brand-text {
    display: none;
  }
  
  .member-main {
    padding: 0.5rem;
  }
  
  .breadcrumb {
    padding: 0.5rem;
    font-size: 0.9rem;
  }
}
</style>