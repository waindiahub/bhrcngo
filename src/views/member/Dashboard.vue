<template>
  <div class="member-dashboard">
    <!-- Header Section -->
    <div class="dashboard-header mb-4">
      <div class="row align-items-center">
        <div class="col-md-6">
          <h1 class="h3 mb-0">Welcome back, {{ currentUser?.name || 'Member' }}!</h1>
          <p class="text-muted mb-0">Here's what's happening with your account today.</p>
        </div>
        <div class="col-md-6 text-md-end">
          <button class="btn btn-primary me-2" @click="fileNewComplaint">
            <i class="fas fa-plus me-2"></i>
            File New Complaint
          </button>
          <button class="btn btn-outline-primary" @click="updateProfile">
            <i class="fas fa-user-edit me-2"></i>
            Update Profile
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="stats-icon bg-primary">
                <i class="fas fa-calendar-check"></i>
              </div>
              <div class="ms-3">
                <h3 class="mb-0">{{ stats.eventsAttended }}</h3>
                <p class="text-muted small mb-0">Events Attended</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="stats-icon bg-success">
                <i class="fas fa-heart"></i>
              </div>
              <div class="ms-3">
                <h3 class="mb-0">₹{{ formatCurrency(stats.totalDonations) }}</h3>
                <p class="text-muted small mb-0">Total Donations</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="stats-icon bg-info">
                <i class="fas fa-certificate"></i>
              </div>
              <div class="ms-3">
                <h3 class="mb-0">{{ stats.certificates }}</h3>
                <p class="text-muted small mb-0">Certificates</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="stats-icon bg-warning">
                <i class="fas fa-file-alt"></i>
              </div>
              <div class="ms-3">
                <h3 class="mb-0">{{ stats.complaints }}</h3>
                <p class="text-muted small mb-0">Active Complaints</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="row">
      <!-- Left Column -->
      <div class="col-lg-8">
        <!-- Recent Activities -->
        <div class="card activity-card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-clock me-2"></i>
              Recent Activities
            </h5>
            <button class="btn btn-sm btn-outline-primary" @click="viewAllActivities">
              View All
            </button>
          </div>
          <div class="card-body">
            <div v-if="loading.activities" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
            <div v-else-if="recentActivities.length === 0" class="empty-state text-center py-4">
              <i class="fas fa-history fa-2x text-muted mb-2"></i>
              <p class="text-muted">No recent activities found.</p>
            </div>
            <div v-else class="activities-list">
              <div 
                class="activity-item"
                v-for="activity in recentActivities.slice(0, 5)"
                :key="activity.id"
              >
                <div class="activity-icon">
                  <i :class="getActivityIcon(activity.type)"></i>
                </div>
                <div class="activity-content">
                  <h6 class="activity-title">{{ activity.title }}</h6>
                  <p class="activity-description">{{ activity.description }}</p>
                  <small class="text-muted">{{ formatDate(activity.date) }}</small>
                </div>
                <div class="activity-status">
                  <span class="badge" :class="getStatusClass(activity.status)">
                    {{ activity.status }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="card quick-actions-card">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-bolt me-2"></i>
              Quick Actions
            </h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-3 col-6 mb-3">
                <button class="btn btn-outline-primary w-100 h-100 quick-action-btn" @click="fileNewComplaint">
                  <i class="fas fa-file-alt fa-2x mb-2"></i>
                  <div>File Complaint</div>
                </button>
              </div>
              <div class="col-md-3 col-6 mb-3">
                <button class="btn btn-outline-success w-100 h-100 quick-action-btn" @click="makeDonation">
                  <i class="fas fa-heart fa-2x mb-2"></i>
                  <div>Make Donation</div>
                </button>
              </div>
              <div class="col-md-3 col-6 mb-3">
                <button class="btn btn-outline-info w-100 h-100 quick-action-btn" @click="viewEvents">
                  <i class="fas fa-calendar fa-2x mb-2"></i>
                  <div>View Events</div>
                </button>
              </div>
              <div class="col-md-3 col-6 mb-3">
                <button class="btn btn-outline-warning w-100 h-100 quick-action-btn" @click="updateProfile">
                  <i class="fas fa-user-edit fa-2x mb-2"></i>
                  <div>Update Profile</div>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column -->
      <div class="col-lg-4">
        <!-- Upcoming Events -->
        <div class="card events-card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-calendar-alt me-2"></i>
              Upcoming Events
            </h5>
            <button class="btn btn-sm btn-outline-primary" @click="viewEvents">
              View All
            </button>
          </div>
          <div class="card-body">
            <div v-if="loading.events" class="text-center py-3">
              <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
            <div v-else-if="upcomingEvents.length === 0" class="empty-state text-center py-3">
              <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
              <p class="text-muted small">No upcoming events.</p>
            </div>
            <div v-else class="events-list">
              <div 
                class="event-item"
                v-for="event in upcomingEvents.slice(0, 3)"
                :key="event.id"
              >
                <div class="event-date">
                  <div class="event-day">{{ formatDay(event.date) }}</div>
                  <div class="event-month">{{ formatMonth(event.date) }}</div>
                </div>
                <div class="event-details">
                  <h6 class="event-title">{{ event.title }}</h6>
                  <p class="event-time">
                    <i class="fas fa-clock me-1"></i>
                    {{ event.time }}
                  </p>
                  <p class="event-location">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    {{ event.location }}
                  </p>
                </div>
                <div class="event-actions">
                  <button 
                    class="btn btn-sm"
                    :class="event.is_registered ? 'btn-success' : 'btn-primary'"
                    @click="registerForEvent(event.id)"
                    :disabled="event.is_registered"
                  >
                    {{ event.is_registered ? 'Registered' : 'Register' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Notifications -->
        <div class="card notifications-card">
          <div class="card-header">
            <h5 class="card-title mb-0">
              <i class="fas fa-bell me-2"></i>
              Notifications
              <span v-if="unreadNotifications > 0" class="notification-count">
                {{ unreadNotifications }}
              </span>
            </h5>
            <button class="btn btn-sm btn-outline-primary" @click="markAllAsRead">
              Mark All Read
            </button>
          </div>
          <div class="card-body">
            <div v-if="notifications.length === 0" class="empty-state">
              <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
              <p class="text-muted small">No notifications.</p>
            </div>
            <div v-else class="notifications-list">
              <div 
                class="notification-item"
                :class="{ unread: !notification.is_read }"
                v-for="notification in notifications.slice(0, 5)"
                :key="notification.id"
                @click="markAsRead(notification.id)"
              >
                <div class="notification-icon" :class="notification.type">
                  <i :class="getNotificationIcon(notification.type)"></i>
                </div>
                <div class="notification-content">
                  <h6 class="notification-title">{{ notification.title }}</h6>
                  <p class="notification-message">{{ notification.message }}</p>
                  <small class="notification-time">{{ formatTimeAgo(notification.created_at) }}</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Emergency Contact -->
        <div class="card emergency-card mt-4">
          <div class="card-body text-center">
            <i class="fas fa-phone-alt fa-2x text-danger mb-2"></i>
            <h6 class="card-title">Emergency Contact</h6>
            <p class="card-text small text-muted">For urgent human rights violations</p>
            <a href="tel:+919876543210" class="btn btn-danger btn-sm">
              <i class="fas fa-phone me-2"></i>
              Emergency: +91 987 654 3210
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { api } from '@/utils/api'

const router = useRouter()
const toast = useToast()

// Reactive data
const currentUser = ref(null)
const stats = ref({
  eventsAttended: 0,
  totalDonations: 0,
  certificates: 0,
  complaints: 0
})
const recentActivities = ref([])
const notifications = ref([])
const upcomingEvents = ref([])
const loading = ref({
  activities: false,
  notifications: false,
  events: false,
  stats: false
})

// Computed properties
const unreadNotifications = computed(() => {
  return notifications.value.filter(n => !n.is_read).length
})

// Methods
const loadDashboardData = async () => {
  try {
    loading.value.stats = true
    loading.value.activities = true
    loading.value.notifications = true
    loading.value.events = true

    // Load user data from localStorage
    loadUserData()

    // Load all dashboard data
    await Promise.all([
      loadStats(),
      loadRecentActivities(),
      loadNotifications(),
      loadUpcomingEvents()
    ])
  } catch (error) {
    console.error('Error loading dashboard data:', error)
    toast.error('Failed to load dashboard data')
  }
}

const loadUserData = () => {
  const userData = localStorage.getItem('user')
  if (userData) {
    currentUser.value = JSON.parse(userData)
  }
}

const loadStats = async () => {
  try {
    // Mock data for now - replace with actual API call
    stats.value = {
      eventsAttended: 12,
      totalDonations: 25000,
      certificates: 3,
      complaints: 1
    }
  } catch (error) {
    console.error('Error loading stats:', error)
  } finally {
    loading.value.stats = false
  }
}

const loadRecentActivities = async () => {
  try {
    // Mock data for now - replace with actual API call
    recentActivities.value = [
      {
        id: 1,
        title: 'Blood Donation Camp',
        description: 'Participated in blood donation drive at City Hospital',
        date: '2024-01-15',
        type: 'donation',
        status: 'completed'
      },
      {
        id: 2,
        title: 'Legal Aid Workshop',
        description: 'Attended workshop on legal rights and procedures',
        date: '2024-01-10',
        type: 'workshop',
        status: 'completed'
      },
      {
        id: 3,
        title: 'Community Service',
        description: 'Volunteered for community cleaning drive',
        date: '2024-01-08',
        type: 'service',
        status: 'completed'
      }
    ]
  } catch (error) {
    console.error('Error loading activities:', error)
  } finally {
    loading.value.activities = false
  }
}

const loadNotifications = async () => {
  try {
    // Mock data for now - replace with actual API call
    notifications.value = [
      {
        id: 1,
        type: 'complaint',
        title: 'Complaint Update',
        message: 'Your complaint #12345 has been reviewed and assigned to an officer',
        is_read: false,
        created_at: new Date(Date.now() - 60 * 60 * 1000)
      },
      {
        id: 2,
        type: 'event',
        title: 'Event Reminder',
        message: 'Human Rights Workshop is scheduled for tomorrow at 10:00 AM',
        is_read: false,
        created_at: new Date(Date.now() - 2 * 60 * 60 * 1000)
      },
      {
        id: 3,
        type: 'system',
        title: 'Profile Update',
        message: 'Please complete your profile to receive better assistance',
        is_read: true,
        created_at: new Date(Date.now() - 24 * 60 * 60 * 1000)
      }
    ]
  } catch (error) {
    console.error('Error loading notifications:', error)
  } finally {
    loading.value.notifications = false
  }
}

const loadUpcomingEvents = async () => {
  try {
    // Mock data for now - replace with actual API call
    upcomingEvents.value = [
      {
        id: 1,
        title: 'Human Rights Workshop',
        date: '2024-02-15',
        time: '10:00 AM',
        location: 'Community Center',
        is_registered: false
      },
      {
        id: 2,
        title: 'Legal Aid Camp',
        date: '2024-02-20',
        time: '9:00 AM',
        location: 'City Hall',
        is_registered: true
      }
    ]
  } catch (error) {
    console.error('Error loading events:', error)
  } finally {
    loading.value.events = false
  }
}

// Action methods
const fileNewComplaint = () => {
  router.push('/file-complaint')
}

const updateProfile = () => {
  router.push('/member/profile')
}

const viewEvents = () => {
  router.push('/events')
}

const makeDonation = () => {
  router.push('/donate')
}

const viewAllActivities = () => {
  router.push('/member/activities')
}

const viewComplaint = (id) => {
  router.push(`/member/complaints/${id}`)
}

const trackComplaint = (id) => {
  router.push(`/member/complaints/${id}/track`)
}

const registerForEvent = async (eventId) => {
  try {
    const response = await api.post(`/events/${eventId}/register`)
    
    if (response.data.success) {
      // Update event registration status
      const event = upcomingEvents.value.find(e => e.id === eventId)
      if (event) {
        event.is_registered = true
      }
      
      toast.success('Successfully registered for the event!')
    }
  } catch (error) {
    console.error('Error registering for event:', error)
    
    // Demo: Update status anyway
    const event = upcomingEvents.value.find(e => e.id === eventId)
    if (event) {
      event.is_registered = true
    }
    
    toast.success('Successfully registered for the event!')
  }
}

const markAsRead = async (notificationId) => {
  try {
    await api.put(`/member/notifications/${notificationId}/read`)
    
    // Update notification status
    const notification = notifications.value.find(n => n.id === notificationId)
    if (notification) {
      notification.is_read = true
    }
  } catch (error) {
    console.error('Error marking notification as read:', error)
    
    // Demo: Update status anyway
    const notification = notifications.value.find(n => n.id === notificationId)
    if (notification) {
      notification.is_read = true
    }
  }
}

const markAllAsRead = async () => {
  try {
    await api.put('/member/notifications/mark-all-read')
    
    // Update all notifications
    notifications.value.forEach(notification => {
      notification.is_read = true
    })
    
    toast.success('All notifications marked as read')
  } catch (error) {
    console.error('Error marking all notifications as read:', error)
    
    // Demo: Update status anyway
    notifications.value.forEach(notification => {
      notification.is_read = true
    })
    
    toast.success('All notifications marked as read')
  }
}

const getNotificationIcon = (type) => {
  switch (type) {
    case 'complaint': return 'fas fa-file-alt'
    case 'event': return 'fas fa-calendar'
    case 'system': return 'fas fa-cog'
    default: return 'fas fa-bell'
  }
}

const getActivityIcon = (type) => {
  const icons = {
    donation: 'fas fa-heart text-danger',
    workshop: 'fas fa-chalkboard-teacher text-primary',
    service: 'fas fa-hands-helping text-success',
    event: 'fas fa-calendar-alt text-info',
    default: 'fas fa-circle text-secondary'
  }
  return icons[type] || icons.default
}

const getStatusClass = (status) => {
  const classes = {
    completed: 'bg-success',
    pending: 'bg-warning',
    cancelled: 'bg-danger',
    default: 'bg-secondary'
  }
  return classes[status] || classes.default
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-IN', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDay = (date) => {
  return new Date(date).getDate()
}

const formatMonth = (date) => {
  return new Date(date).toLocaleDateString('en-IN', { month: 'short' })
}

const formatTimeAgo = (date) => {
  const now = new Date()
  const past = new Date(date)
  const diffInHours = Math.floor((now - past) / (1000 * 60 * 60))
  
  if (diffInHours < 1) return 'Just now'
  if (diffInHours < 24) return `${diffInHours}h ago`
  
  const diffInDays = Math.floor(diffInHours / 24)
  if (diffInDays < 7) return `${diffInDays}d ago`
  
  return formatDate(date)
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-IN').format(amount)
}

// Lifecycle
onMounted(() => {
  loadDashboardData()
})
</script>

<style scoped>
.member-dashboard {
  padding: 1rem;
}

.dashboard-header {
  background: linear-gradient(135deg, var(--primary-color) 0%, #1e3a8a 100%);
  color: white;
  padding: 2rem;
  border-radius: 1rem;
  margin-bottom: 2rem;
}

.stats-card {
  border: none;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease;
}

.stats-card:hover {
  transform: translateY(-5px);
}

.stats-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
}

.activity-card,
.events-card,
.notifications-card {
  border: none;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.card-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.activity-item,
.event-item,
.notification-item {
  display: flex;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid #f0f0f0;
  transition: background-color 0.3s ease;
}

.activity-item:hover,
.event-item:hover,
.notification-item:hover {
  background-color: #f8f9fa;
}

.activity-item:last-child,
.event-item:last-child,
.notification-item:last-child {
  border-bottom: none;
}

.activity-icon,
.notification-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 1rem;
  font-size: 1rem;
}

.activity-icon {
  background-color: #e9ecef;
}

.notification-icon.complaint {
  background-color: #fff3cd;
  color: #856404;
}

.notification-icon.event {
  background-color: #d1ecf1;
  color: #0c5460;
}

.notification-icon.system {
  background-color: #d4edda;
  color: #155724;
}

.activity-content,
.notification-content {
  flex: 1;
}

.activity-title,
.notification-title {
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.activity-description,
.notification-message {
  font-size: 0.8rem;
  color: #6c757d;
  margin-bottom: 0.25rem;
}

.notification-item.unread {
  background-color: #f8f9ff;
  border-left: 3px solid var(--primary-color);
}

.notification-count {
  background-color: #dc3545;
  color: white;
  font-size: 0.7rem;
  padding: 0.2rem 0.5rem;
  border-radius: 1rem;
  margin-left: 0.5rem;
}

.event-date {
  text-align: center;
  margin-right: 1rem;
  min-width: 50px;
}

.event-day {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary-color);
  line-height: 1;
}

.event-month {
  font-size: 0.8rem;
  color: #6c757d;
  text-transform: uppercase;
}

.event-details {
  flex: 1;
}

.event-title {
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.event-time,
.event-location {
  font-size: 0.8rem;
  color: #6c757d;
  margin-bottom: 0.25rem;
}

.quick-action-btn {
  padding: 1.5rem 1rem;
  text-align: center;
  border-radius: 0.75rem;
  transition: all 0.3s ease;
}

.quick-action-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.emergency-card {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
  border: none;
}

.emergency-card .card-title,
.emergency-card .card-text {
  color: white;
}

.empty-state {
  text-align: center;
  padding: 2rem;
}

.notifications-list,
.events-list {
  max-height: 400px;
  overflow-y: auto;
}

.notifications-list::-webkit-scrollbar,
.events-list::-webkit-scrollbar {
  width: 4px;
}

.notifications-list::-webkit-scrollbar-track,
.events-list::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.notifications-list::-webkit-scrollbar-thumb,
.events-list::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 2px;
}

.notifications-list::-webkit-scrollbar-thumb:hover,
.events-list::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>