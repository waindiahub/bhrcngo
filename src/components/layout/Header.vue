<template>
  <header class="header">
    <!-- Top Bar -->
    <div class="top-bar bg-primary text-white py-2 d-none d-md-block">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="d-flex align-items-center">
              <i class="fas fa-phone me-2"></i>
              <span class="me-3">+91-11-2345-6789</span>
              <i class="fas fa-envelope me-2"></i>
              <span>info@bhrcindia.org</span>
            </div>
          </div>
          <div class="col-md-6 text-end">
            <div class="social-links">
              <a href="#" class="text-white me-2" title="Facebook">
                <i class="fab fa-facebook-f"></i>
              </a>
              <a href="#" class="text-white me-2" title="Twitter">
                <i class="fab fa-twitter"></i>
              </a>
              <a href="#" class="text-white me-2" title="LinkedIn">
                <i class="fab fa-linkedin-in"></i>
              </a>
              <a href="#" class="text-white" title="YouTube">
                <i class="fab fa-youtube"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
      <div class="container">
        <!-- Brand -->
        <router-link to="/" class="navbar-brand d-flex align-items-center">
          <img src="/logo.png" alt="BHRC" class="logo me-2" height="40">
          <div class="brand-text">
            <div class="brand-name">BHRC</div>
            <div class="brand-subtitle">Human Rights Council</div>
          </div>
        </router-link>

        <!-- Mobile Toggle -->
        <button 
          class="navbar-toggler" 
          type="button" 
          @click="toggleMobileMenu"
          :class="{ 'collapsed': !mobileMenuOpen }"
        >
          <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Menu -->
        <div class="navbar-collapse" :class="{ 'show': mobileMenuOpen }">
          <ul class="navbar-nav me-auto">
            <li class="nav-item">
              <router-link to="/" class="nav-link" @click="closeMobileMenu">
                Home
              </router-link>
            </li>
            <li class="nav-item dropdown" @mouseenter="showDropdown" @mouseleave="hideDropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button">
                About
              </a>
              <ul class="dropdown-menu" :class="{ 'show': dropdownOpen === 'about' }">
                <li><router-link to="/about" class="dropdown-item" @click="closeMobileMenu">About Us</router-link></li>
                <li><router-link to="/mission" class="dropdown-item" @click="closeMobileMenu">Our Mission</router-link></li>
                <li><router-link to="/team" class="dropdown-item" @click="closeMobileMenu">Our Team</router-link></li>
                <li><router-link to="/history" class="dropdown-item" @click="closeMobileMenu">Our History</router-link></li>
              </ul>
            </li>
            <li class="nav-item dropdown" @mouseenter="showDropdown" @mouseleave="hideDropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button">
                Services
              </a>
              <ul class="dropdown-menu" :class="{ 'show': dropdownOpen === 'services' }">
                <li><router-link to="/services/legal-aid" class="dropdown-item" @click="closeMobileMenu">Legal Aid</router-link></li>
                <li><router-link to="/services/complaint-filing" class="dropdown-item" @click="closeMobileMenu">Complaint Filing</router-link></li>
                <li><router-link to="/services/awareness" class="dropdown-item" @click="closeMobileMenu">Awareness Programs</router-link></li>
                <li><router-link to="/services/support" class="dropdown-item" @click="closeMobileMenu">Community Support</router-link></li>
              </ul>
            </li>
            <li class="nav-item">
              <router-link to="/events" class="nav-link" @click="closeMobileMenu">
                Events
              </router-link>
            </li>
            <li class="nav-item">
              <router-link to="/gallery" class="nav-link" @click="closeMobileMenu">
                Gallery
              </router-link>
            </li>
            <li class="nav-item">
              <router-link to="/complaints" class="nav-link" @click="closeMobileMenu">
                File Complaint
              </router-link>
            </li>
            <li class="nav-item">
              <router-link to="/contact" class="nav-link" @click="closeMobileMenu">
                Contact
              </router-link>
            </li>
          </ul>

          <!-- Auth Section -->
          <div class="navbar-nav ms-auto">
            <template v-if="!isAuthenticated">
              <li class="nav-item">
                <router-link to="/login" class="nav-link" @click="closeMobileMenu">
                  <i class="fas fa-sign-in-alt me-1"></i>
                  Login
                </router-link>
              </li>
              <li class="nav-item">
                <router-link to="/register" class="btn btn-primary btn-sm ms-2" @click="closeMobileMenu">
                  Register
                </router-link>
              </li>
            </template>
            <template v-else>
              <!-- Notifications -->
              <li class="nav-item dropdown position-relative">
                <a 
                  class="nav-link" 
                  href="#" 
                  @click.prevent="toggleNotifications"
                  :class="{ 'active': notificationsOpen }"
                >
                  <i class="fas fa-bell"></i>
                  <span 
                    v-if="unreadNotificationCount > 0" 
                    class="badge bg-danger notification-badge"
                  >
                    {{ unreadNotificationCount > 99 ? '99+' : unreadNotificationCount }}
                  </span>
                </a>
                <div 
                  class="dropdown-menu dropdown-menu-end notification-dropdown" 
                  :class="{ 'show': notificationsOpen }"
                >
                  <div class="dropdown-header d-flex justify-content-between align-items-center">
                    <span>Notifications</span>
                    <button 
                      v-if="unreadNotificationCount > 0"
                      class="btn btn-sm btn-link p-0"
                      @click="markAllAsRead"
                    >
                      Mark all as read
                    </button>
                  </div>
                  <div class="notification-list">
                    <template v-if="notifications.length > 0">
                      <div 
                        v-for="notification in notifications.slice(0, 5)" 
                        :key="notification.id"
                        class="dropdown-item notification-item"
                        :class="{ 'unread': !notification.read_at }"
                        @click="markAsRead(notification.id)"
                      >
                        <div class="notification-content">
                          <div class="notification-title">{{ notification.title }}</div>
                          <div class="notification-message">{{ notification.message }}</div>
                          <div class="notification-time">{{ formatRelativeTime(notification.created_at) }}</div>
                        </div>
                      </div>
                      <div class="dropdown-divider"></div>
                      <router-link to="/member/notifications" class="dropdown-item text-center">
                        View All Notifications
                      </router-link>
                    </template>
                    <div v-else class="dropdown-item text-center text-muted">
                      No notifications
                    </div>
                  </div>
                </div>
              </li>

              <!-- User Menu -->
              <li class="nav-item dropdown">
                <a 
                  class="nav-link dropdown-toggle d-flex align-items-center" 
                  href="#" 
                  @click.prevent="toggleUserMenu"
                  :class="{ 'active': userMenuOpen }"
                >
                  <img 
                    :src="userAvatar" 
                    :alt="userName" 
                    class="user-avatar me-2"
                  >
                  <span class="d-none d-lg-inline">{{ userName }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" :class="{ 'show': userMenuOpen }">
                  <li class="dropdown-header">
                    <div class="user-info">
                      <div class="user-name">{{ userName }}</div>
                      <div class="user-email">{{ userEmail }}</div>
                    </div>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                  <li v-if="hasRole('member')">
                    <router-link to="/member/dashboard" class="dropdown-item" @click="closeUserMenu">
                      <i class="fas fa-tachometer-alt me-2"></i>
                      Member Dashboard
                    </router-link>
                  </li>
                  <li>
                    <router-link to="/member/profile" class="dropdown-item" @click="closeUserMenu">
                      <i class="fas fa-user me-2"></i>
                      Profile
                    </router-link>
                  </li>
                  <li>
                    <router-link to="/member/complaints" class="dropdown-item" @click="closeUserMenu">
                      <i class="fas fa-file-alt me-2"></i>
                      My Complaints
                    </router-link>
                  </li>
                  <li>
                    <router-link to="/member/donations" class="dropdown-item" @click="closeUserMenu">
                      <i class="fas fa-heart me-2"></i>
                      My Donations
                    </router-link>
                  </li>
                  <li v-if="hasRole('admin')">
                    <router-link to="/admin/dashboard" class="dropdown-item" @click="closeUserMenu">
                      <i class="fas fa-cog me-2"></i>
                      Admin Panel
                    </router-link>
                  </li>
                  <li v-if="!hasRole('admin') && !hasRole('member')">
                    <router-link to="/dashboard" class="dropdown-item" @click="closeUserMenu">
                      <i class="fas fa-tachometer-alt me-2"></i>
                      Dashboard
                    </router-link>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                  <li>
                    <a href="#" class="dropdown-item" @click.prevent="handleLogout">
                      <i class="fas fa-sign-out-alt me-2"></i>
                      Logout
                    </a>
                  </li>
                </ul>
              </li>
            </template>
          </div>
        </div>
      </div>
    </nav>
  </header>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useAppStore } from '@/stores/app'
import { useRouter } from 'vue-router'
import { dateUtils } from '@/utils/helpers'

export default {
  name: 'Header',
  setup() {
    const authStore = useAuthStore()
    const appStore = useAppStore()
    const router = useRouter()

    // Reactive state
    const mobileMenuOpen = ref(false)
    const dropdownOpen = ref(null)
    const notificationsOpen = ref(false)
    const userMenuOpen = ref(false)

    // Computed properties
    const isAuthenticated = computed(() => authStore.isAuthenticated)
    const userName = computed(() => authStore.userName)
    const userEmail = computed(() => authStore.userEmail)
    const notifications = computed(() => appStore.notifications)
    const unreadNotificationCount = computed(() => appStore.unreadNotificationCount)
    
    const userAvatar = computed(() => {
      return authStore.user?.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(userName.value)}&background=007bff&color=fff`
    })

    // Methods
    const toggleMobileMenu = () => {
      mobileMenuOpen.value = !mobileMenuOpen.value
    }

    const closeMobileMenu = () => {
      mobileMenuOpen.value = false
    }

    const showDropdown = (event) => {
      const dropdown = event.target.closest('.dropdown')
      if (dropdown) {
        const dropdownType = dropdown.querySelector('.nav-link').textContent.trim().toLowerCase()
        dropdownOpen.value = dropdownType
      }
    }

    const hideDropdown = () => {
      dropdownOpen.value = null
    }

    const toggleNotifications = () => {
      notificationsOpen.value = !notificationsOpen.value
      userMenuOpen.value = false
    }

    const toggleUserMenu = () => {
      userMenuOpen.value = !userMenuOpen.value
      notificationsOpen.value = false
    }

    const closeUserMenu = () => {
      userMenuOpen.value = false
    }

    const markAsRead = (notificationId) => {
      appStore.markNotificationAsRead(notificationId)
    }

    const markAllAsRead = () => {
      appStore.markAllNotificationsAsRead()
    }

    const hasRole = (role) => {
      return authStore.hasRole(role)
    }

    const handleLogout = async () => {
      await authStore.logout()
      router.push('/')
      closeUserMenu()
    }

    const formatRelativeTime = (date) => {
      return dateUtils.formatRelative(date)
    }

    // Close dropdowns when clicking outside
    const handleClickOutside = (event) => {
      if (!event.target.closest('.dropdown')) {
        dropdownOpen.value = null
        notificationsOpen.value = false
        userMenuOpen.value = false
      }
    }

    // Lifecycle
    onMounted(() => {
      document.addEventListener('click', handleClickOutside)
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    return {
      // State
      mobileMenuOpen,
      dropdownOpen,
      notificationsOpen,
      userMenuOpen,
      
      // Computed
      isAuthenticated,
      userName,
      userEmail,
      userAvatar,
      notifications,
      unreadNotificationCount,
      
      // Methods
      toggleMobileMenu,
      closeMobileMenu,
      showDropdown,
      hideDropdown,
      toggleNotifications,
      toggleUserMenu,
      closeUserMenu,
      markAsRead,
      markAllAsRead,
      hasRole,
      handleLogout,
      formatRelativeTime
    }
  }
}
</script>

<style scoped>
.header {
  position: sticky;
  top: 0;
  z-index: 1000;
}

.top-bar {
  font-size: 0.875rem;
}

.social-links a {
  transition: opacity 0.3s ease;
}

.social-links a:hover {
  opacity: 0.8;
}

.navbar {
  padding: 1rem 0;
}

.logo {
  max-height: 40px;
  width: auto;
}

.brand-text {
  line-height: 1.2;
}

.brand-name {
  font-weight: bold;
  font-size: 1.25rem;
  color: #007bff;
}

.brand-subtitle {
  font-size: 0.75rem;
  color: #6c757d;
}

.nav-link {
  font-weight: 500;
  color: #333 !important;
  transition: color 0.3s ease;
}

.nav-link:hover,
.nav-link.active {
  color: #007bff !important;
}

.dropdown-menu {
  border: none;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  border-radius: 0.5rem;
}

.dropdown-item {
  padding: 0.5rem 1rem;
  transition: background-color 0.3s ease;
}

.dropdown-item:hover {
  background-color: #f8f9fa;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  object-fit: cover;
}

.notification-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  font-size: 0.75rem;
  min-width: 18px;
  height: 18px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.notification-dropdown {
  width: 350px;
  max-height: 400px;
  overflow-y: auto;
}

.notification-list {
  max-height: 300px;
  overflow-y: auto;
}

.notification-item {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #f1f3f4;
  cursor: pointer;
}

.notification-item.unread {
  background-color: #f8f9ff;
}

.notification-item:hover {
  background-color: #f1f3f4;
}

.notification-content {
  font-size: 0.875rem;
}

.notification-title {
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.notification-message {
  color: #6c757d;
  margin-bottom: 0.25rem;
}

.notification-time {
  font-size: 0.75rem;
  color: #9ca3af;
}

.user-info {
  padding: 0.5rem 0;
}

.user-name {
  font-weight: 600;
  font-size: 0.875rem;
}

.user-email {
  font-size: 0.75rem;
  color: #6c757d;
}

/* Mobile Styles */
@media (max-width: 991.98px) {
  .navbar-collapse {
    background-color: white;
    border-top: 1px solid #dee2e6;
    margin-top: 1rem;
    padding-top: 1rem;
  }
  
  .dropdown-menu {
    position: static !important;
    transform: none !important;
    box-shadow: none;
    border: 1px solid #dee2e6;
    margin-top: 0.5rem;
  }
  
  .notification-dropdown {
    width: 100%;
  }
}
</style>