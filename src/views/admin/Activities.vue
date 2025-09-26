<template>
  <div class="admin-activities">
    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
      <p>Loading activities...</p>
    </div>

    <!-- Activities Header -->
    <div class="activities-header">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h1>Activity Management</h1>
            <p>Monitor and manage all system activities and user interactions</p>
          </div>
          <div class="col-md-4">
            <div class="header-actions">
              <button 
                class="btn btn-outline-light" 
                @click="refreshData"
                :disabled="loading"
              >
                <i class="fas fa-sync-alt"></i>
                Refresh
              </button>
              <button 
                class="btn btn-outline-light" 
                @click="exportActivities"
                :disabled="loading"
              >
                <i class="fas fa-download"></i>
                Export
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="container">
      <!-- Statistics Section -->
      <div class="stats-section">
        <div class="row">
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card total">
              <div class="card-body">
                <h6 class="card-title">Total Activities</h6>
                <div class="stats-number">{{ stats.total || 0 }}</div>
                <div class="stats-change positive">
                  <i class="fas fa-arrow-up"></i>
                  +{{ stats.totalChange || 0 }}% from last month
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card today">
              <div class="card-body">
                <h6 class="card-title">Today's Activities</h6>
                <div class="stats-number">{{ stats.today || 0 }}</div>
                <div class="stats-change positive">
                  <i class="fas fa-arrow-up"></i>
                  +{{ stats.todayChange || 0 }}% from yesterday
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card users">
              <div class="card-body">
                <h6 class="card-title">Active Users</h6>
                <div class="stats-number">{{ stats.activeUsers || 0 }}</div>
                <div class="stats-change positive">
                  <i class="fas fa-arrow-up"></i>
                  +{{ stats.usersChange || 0 }}% this week
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card errors">
              <div class="card-body">
                <h6 class="card-title">Error Activities</h6>
                <div class="stats-number">{{ stats.errors || 0 }}</div>
                <div class="stats-change negative">
                  <i class="fas fa-arrow-down"></i>
                  -{{ stats.errorsChange || 0 }}% from last week
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters Section -->
      <div class="filters-section">
        <div class="filters-header">
          <h5 class="filters-title">Filter Activities</h5>
          <div class="view-toggle">
            <button 
              class="view-btn" 
              :class="{ active: viewMode === 'list' }"
              @click="viewMode = 'list'"
            >
              <i class="fas fa-list"></i>
              List
            </button>
            <button 
              class="view-btn" 
              :class="{ active: viewMode === 'timeline' }"
              @click="viewMode = 'timeline'"
            >
              <i class="fas fa-stream"></i>
              Timeline
            </button>
            <button 
              class="view-btn" 
              :class="{ active: viewMode === 'chart' }"
              @click="viewMode = 'chart'"
            >
              <i class="fas fa-chart-line"></i>
              Chart
            </button>
          </div>
        </div>

        <div class="row filters-row">
          <div class="col-md-3">
            <div class="filter-group">
              <label class="filter-label">Activity Type</label>
              <select v-model="filters.type" class="form-control form-select">
                <option value="">All Types</option>
                <option value="login">Login</option>
                <option value="logout">Logout</option>
                <option value="registration">Registration</option>
                <option value="profile_update">Profile Update</option>
                <option value="complaint_submit">Complaint Submit</option>
                <option value="donation">Donation</option>
                <option value="event_registration">Event Registration</option>
                <option value="admin_action">Admin Action</option>
                <option value="system_error">System Error</option>
              </select>
            </div>
          </div>
          <div class="col-md-3">
            <div class="filter-group">
              <label class="filter-label">User Role</label>
              <select v-model="filters.role" class="form-control form-select">
                <option value="">All Roles</option>
                <option value="admin">Admin</option>
                <option value="member">Member</option>
                <option value="guest">Guest</option>
              </select>
            </div>
          </div>
          <div class="col-md-3">
            <div class="filter-group">
              <label class="filter-label">Date Range</label>
              <select v-model="filters.dateRange" class="form-control form-select">
                <option value="today">Today</option>
                <option value="yesterday">Yesterday</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="custom">Custom Range</option>
              </select>
            </div>
          </div>
          <div class="col-md-3">
            <div class="filter-group">
              <label class="filter-label">Search</label>
              <input 
                type="text" 
                v-model="filters.search" 
                class="form-control" 
                placeholder="Search activities..."
              >
            </div>
          </div>
        </div>

        <div v-if="filters.dateRange === 'custom'" class="row filters-row">
          <div class="col-md-6">
            <div class="filter-group">
              <label class="filter-label">Start Date</label>
              <input 
                type="date" 
                v-model="filters.startDate" 
                class="form-control"
              >
            </div>
          </div>
          <div class="col-md-6">
            <div class="filter-group">
              <label class="filter-label">End Date</label>
              <input 
                type="date" 
                v-model="filters.endDate" 
                class="form-control"
              >
            </div>
          </div>
        </div>

        <div class="filters-actions">
          <button class="btn btn-outline-secondary" @click="clearFilters">
            <i class="fas fa-times"></i>
            Clear Filters
          </button>
          <button class="btn btn-primary" @click="applyFilters">
            <i class="fas fa-search"></i>
            Apply Filters
          </button>
        </div>
      </div>

      <!-- Activities Section -->
      <div class="activities-section">
        <div class="section-header">
          <div>
            <h5 class="section-title">Activities</h5>
            <p class="section-subtitle">{{ filteredActivities.length }} activities found</p>
          </div>
          <div v-if="selectedActivities.length > 0" class="bulk-actions">
            <span class="bulk-info">{{ selectedActivities.length }} selected</span>
            <button class="btn btn-outline-danger btn-sm" @click="deleteSelected">
              <i class="fas fa-trash"></i>
              Delete
            </button>
            <button class="btn btn-outline-info btn-sm" @click="exportSelected">
              <i class="fas fa-download"></i>
              Export
            </button>
          </div>
        </div>

        <!-- List View -->
        <div v-if="viewMode === 'list'" class="activities-list">
          <div v-if="filteredActivities.length === 0" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-history"></i>
            </div>
            <h6 class="empty-title">No Activities Found</h6>
            <p class="empty-text">No activities match your current filters. Try adjusting your search criteria.</p>
          </div>

          <div 
            v-for="activity in paginatedActivities" 
            :key="activity.id"
            class="activity-item"
            @click="viewActivityDetails(activity)"
          >
            <div class="activity-header">
              <div class="activity-info">
                <div class="activity-checkbox">
                  <input 
                    type="checkbox" 
                    :value="activity.id"
                    v-model="selectedActivities"
                    @click.stop
                  >
                </div>
                <div class="activity-icon" :class="getActivityIconClass(activity.type)">
                  <i :class="getActivityIcon(activity.type)"></i>
                </div>
                <div class="activity-details">
                  <h6 class="activity-title">{{ activity.title }}</h6>
                  <p class="activity-description">{{ activity.description }}</p>
                </div>
              </div>
              <div class="activity-badges">
                <span class="badge" :class="getActivityBadgeClass(activity.type)">
                  {{ formatActivityType(activity.type) }}
                </span>
                <span v-if="activity.severity" class="badge" :class="getSeverityBadgeClass(activity.severity)">
                  {{ activity.severity }}
                </span>
              </div>
            </div>

            <div class="activity-meta">
              <div class="meta-item">
                <span class="meta-label">User</span>
                <span class="meta-value">{{ activity.user_name || 'System' }}</span>
              </div>
              <div class="meta-item">
                <span class="meta-label">IP Address</span>
                <span class="meta-value">{{ activity.ip_address || 'N/A' }}</span>
              </div>
              <div class="meta-item">
                <span class="meta-label">User Agent</span>
                <span class="meta-value">{{ activity.user_agent || 'N/A' }}</span>
              </div>
              <div class="meta-item">
                <span class="meta-label">Timestamp</span>
                <span class="meta-value">{{ formatDateTime(activity.created_at) }}</span>
              </div>
            </div>

            <div v-if="activity.metadata" class="activity-metadata">
              <h6>Additional Information</h6>
              <pre>{{ JSON.stringify(activity.metadata, null, 2) }}</pre>
            </div>

            <div class="activity-actions">
              <button class="btn btn-outline-info btn-sm" @click.stop="viewActivityDetails(activity)">
                <i class="fas fa-eye"></i>
                View Details
              </button>
              <button v-if="activity.type === 'system_error'" class="btn btn-outline-warning btn-sm" @click.stop="markAsResolved(activity)">
                <i class="fas fa-check"></i>
                Mark Resolved
              </button>
              <button class="btn btn-outline-danger btn-sm" @click.stop="deleteActivity(activity)">
                <i class="fas fa-trash"></i>
                Delete
              </button>
            </div>
          </div>
        </div>

        <!-- Timeline View -->
        <div v-if="viewMode === 'timeline'" class="activities-timeline">
          <div v-if="filteredActivities.length === 0" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-history"></i>
            </div>
            <h6 class="empty-title">No Activities Found</h6>
            <p class="empty-text">No activities match your current filters. Try adjusting your search criteria.</p>
          </div>

          <div class="timeline">
            <div 
              v-for="(activity, index) in paginatedActivities" 
              :key="activity.id"
              class="timeline-item"
              :class="{ 'timeline-item-error': activity.type === 'system_error' }"
            >
              <div class="timeline-marker" :class="getActivityIconClass(activity.type)">
                <i :class="getActivityIcon(activity.type)"></i>
              </div>
              <div class="timeline-content">
                <div class="timeline-header">
                  <h6 class="timeline-title">{{ activity.title }}</h6>
                  <span class="timeline-time">{{ formatDateTime(activity.created_at) }}</span>
                </div>
                <p class="timeline-description">{{ activity.description }}</p>
                <div class="timeline-meta">
                  <span class="timeline-user">{{ activity.user_name || 'System' }}</span>
                  <span class="timeline-ip">{{ activity.ip_address }}</span>
                  <span class="badge" :class="getActivityBadgeClass(activity.type)">
                    {{ formatActivityType(activity.type) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Chart View -->
        <div v-if="viewMode === 'chart'" class="activities-chart">
          <div class="chart-container">
            <canvas ref="activityChart"></canvas>
          </div>
          <div class="chart-legend">
            <div class="legend-item" v-for="type in activityTypes" :key="type">
              <div class="legend-color" :style="{ backgroundColor: getChartColor(type) }"></div>
              <span class="legend-label">{{ formatActivityType(type) }}</span>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="filteredActivities.length > 0" class="pagination-section">
          <div class="pagination-info">
            <span class="pagination-text">
              Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to 
              {{ Math.min(currentPage * itemsPerPage, filteredActivities.length) }} 
              of {{ filteredActivities.length }} activities
            </span>
            <div class="pagination">
              <button 
                class="page-btn" 
                @click="currentPage = 1" 
                :disabled="currentPage === 1"
              >
                First
              </button>
              <button 
                class="page-btn" 
                @click="currentPage--" 
                :disabled="currentPage === 1"
              >
                Previous
              </button>
              <button 
                v-for="page in visiblePages" 
                :key="page"
                class="page-btn" 
                :class="{ active: page === currentPage }"
                @click="currentPage = page"
              >
                {{ page }}
              </button>
              <button 
                class="page-btn" 
                @click="currentPage++" 
                :disabled="currentPage === totalPages"
              >
                Next
              </button>
              <button 
                class="page-btn" 
                @click="currentPage = totalPages" 
                :disabled="currentPage === totalPages"
              >
                Last
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bulk Actions Bar -->
    <div v-if="selectedActivities.length > 0" class="bulk-actions-bar">
      <div class="bulk-info">
        <strong>{{ selectedActivities.length }}</strong> activities selected
      </div>
      <div class="bulk-actions">
        <button class="btn btn-outline-info" @click="exportSelected">
          <i class="fas fa-download"></i>
          Export Selected
        </button>
        <button class="btn btn-outline-danger" @click="deleteSelected">
          <i class="fas fa-trash"></i>
          Delete Selected
        </button>
      </div>
    </div>

    <!-- Activity Details Modal -->
    <div v-if="showDetailsModal" class="modal" @click="closeDetailsModal">
      <div class="modal-dialog modal-lg" @click.stop>
        <div class="modal-header">
          <h5 class="modal-title">Activity Details</h5>
          <button class="btn-close" @click="closeDetailsModal">&times;</button>
        </div>
        <div class="modal-body">
          <div v-if="selectedActivity" class="activity-detail">
            <div class="activity-detail-header">
              <h4>{{ selectedActivity.title }}</h4>
              <div class="activity-detail-badges">
                <span class="badge" :class="getActivityBadgeClass(selectedActivity.type)">
                  {{ formatActivityType(selectedActivity.type) }}
                </span>
                <span v-if="selectedActivity.severity" class="badge" :class="getSeverityBadgeClass(selectedActivity.severity)">
                  {{ selectedActivity.severity }}
                </span>
              </div>
            </div>

            <div class="activity-detail-info">
              <div class="info-item">
                <strong>Description:</strong>
                <span>{{ selectedActivity.description }}</span>
              </div>
              <div class="info-item">
                <strong>User:</strong>
                <span>{{ selectedActivity.user_name || 'System' }}</span>
              </div>
              <div class="info-item">
                <strong>User Role:</strong>
                <span>{{ selectedActivity.user_role || 'N/A' }}</span>
              </div>
              <div class="info-item">
                <strong>IP Address:</strong>
                <span>{{ selectedActivity.ip_address || 'N/A' }}</span>
              </div>
              <div class="info-item">
                <strong>User Agent:</strong>
                <span>{{ selectedActivity.user_agent || 'N/A' }}</span>
              </div>
              <div class="info-item">
                <strong>Timestamp:</strong>
                <span>{{ formatDateTime(selectedActivity.created_at) }}</span>
              </div>
            </div>

            <div v-if="selectedActivity.metadata" class="activity-detail-metadata">
              <h6>Additional Metadata</h6>
              <pre>{{ JSON.stringify(selectedActivity.metadata, null, 2) }}</pre>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" @click="closeDetailsModal">Close</button>
          <button v-if="selectedActivity && selectedActivity.type === 'system_error'" class="btn btn-outline-warning" @click="markAsResolved(selectedActivity)">
            <i class="fas fa-check"></i>
            Mark as Resolved
          </button>
          <button class="btn btn-outline-danger" @click="deleteActivity(selectedActivity)">
            <i class="fas fa-trash"></i>
            Delete Activity
          </button>
        </div>
      </div>
    </div>

    <!-- Success Toast -->
    <div v-if="showSuccessToast" class="success-toast">
      {{ successMessage }}
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue'
import { useAuthStore } from '@/stores/auth'
import Chart from 'chart.js/auto'

export default {
  name: 'AdminActivities',
  setup() {
    const authStore = useAuthStore()
    
    // Reactive data
    const loading = ref(false)
    const activities = ref([])
    const stats = reactive({
      total: 0,
      today: 0,
      activeUsers: 0,
      errors: 0,
      totalChange: 0,
      todayChange: 0,
      usersChange: 0,
      errorsChange: 0
    })
    
    const filters = reactive({
      type: '',
      role: '',
      dateRange: 'today',
      search: '',
      startDate: '',
      endDate: ''
    })
    
    const viewMode = ref('list')
    const currentPage = ref(1)
    const itemsPerPage = ref(20)
    const selectedActivities = ref([])
    const showDetailsModal = ref(false)
    const selectedActivity = ref(null)
    const showSuccessToast = ref(false)
    const successMessage = ref('')
    const activityChart = ref(null)
    const chartInstance = ref(null)

    // Computed properties
    const filteredActivities = computed(() => {
      let filtered = activities.value

      if (filters.type) {
        filtered = filtered.filter(activity => activity.type === filters.type)
      }

      if (filters.role) {
        filtered = filtered.filter(activity => activity.user_role === filters.role)
      }

      if (filters.search) {
        const search = filters.search.toLowerCase()
        filtered = filtered.filter(activity => 
          activity.title.toLowerCase().includes(search) ||
          activity.description.toLowerCase().includes(search) ||
          (activity.user_name && activity.user_name.toLowerCase().includes(search))
        )
      }

      // Date filtering
      if (filters.dateRange !== 'custom') {
        const now = new Date()
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
        
        filtered = filtered.filter(activity => {
          const activityDate = new Date(activity.created_at)
          
          switch (filters.dateRange) {
            case 'today':
              return activityDate >= today
            case 'yesterday':
              const yesterday = new Date(today)
              yesterday.setDate(yesterday.getDate() - 1)
              return activityDate >= yesterday && activityDate < today
            case 'week':
              const weekStart = new Date(today)
              weekStart.setDate(weekStart.getDate() - weekStart.getDay())
              return activityDate >= weekStart
            case 'month':
              const monthStart = new Date(today.getFullYear(), today.getMonth(), 1)
              return activityDate >= monthStart
            default:
              return true
          }
        })
      } else if (filters.startDate && filters.endDate) {
        const startDate = new Date(filters.startDate)
        const endDate = new Date(filters.endDate)
        endDate.setHours(23, 59, 59, 999)
        
        filtered = filtered.filter(activity => {
          const activityDate = new Date(activity.created_at)
          return activityDate >= startDate && activityDate <= endDate
        })
      }

      return filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
    })

    const totalPages = computed(() => Math.ceil(filteredActivities.value.length / itemsPerPage.value))

    const paginatedActivities = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage.value
      const end = start + itemsPerPage.value
      return filteredActivities.value.slice(start, end)
    })

    const visiblePages = computed(() => {
      const pages = []
      const total = totalPages.value
      const current = currentPage.value
      
      if (total <= 7) {
        for (let i = 1; i <= total; i++) {
          pages.push(i)
        }
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) {
            pages.push(i)
          }
          pages.push('...')
          pages.push(total)
        } else if (current >= total - 3) {
          pages.push(1)
          pages.push('...')
          for (let i = total - 4; i <= total; i++) {
            pages.push(i)
          }
        } else {
          pages.push(1)
          pages.push('...')
          for (let i = current - 1; i <= current + 1; i++) {
            pages.push(i)
          }
          pages.push('...')
          pages.push(total)
        }
      }
      
      return pages
    })

    const activityTypes = computed(() => {
      const types = new Set()
      activities.value.forEach(activity => types.add(activity.type))
      return Array.from(types)
    })

    // Methods
    const loadActivities = async () => {
      loading.value = true
      try {
        const response = await fetch('/api/admin/activities', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          activities.value = data.activities || []
        }
      } catch (error) {
        console.error('Error loading activities:', error)
        showToast('Error loading activities')
      } finally {
        loading.value = false
      }
    }

    const loadStats = async () => {
      try {
        const response = await fetch('/api/admin/activities/stats', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          Object.assign(stats, data)
        }
      } catch (error) {
        console.error('Error loading stats:', error)
      }
    }

    const refreshData = async () => {
      await Promise.all([loadActivities(), loadStats()])
      showToast('Data refreshed successfully')
    }

    const applyFilters = () => {
      currentPage.value = 1
      if (viewMode.value === 'chart') {
        nextTick(() => {
          updateChart()
        })
      }
    }

    const clearFilters = () => {
      Object.assign(filters, {
        type: '',
        role: '',
        dateRange: 'today',
        search: '',
        startDate: '',
        endDate: ''
      })
      currentPage.value = 1
      applyFilters()
    }

    const viewActivityDetails = (activity) => {
      selectedActivity.value = activity
      showDetailsModal.value = true
    }

    const closeDetailsModal = () => {
      showDetailsModal.value = false
      selectedActivity.value = null
    }

    const deleteActivity = async (activity) => {
      if (!confirm('Are you sure you want to delete this activity?')) return

      try {
        const response = await fetch(`/api/admin/activities/${activity.id}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          activities.value = activities.value.filter(a => a.id !== activity.id)
          selectedActivities.value = selectedActivities.value.filter(id => id !== activity.id)
          showToast('Activity deleted successfully')
          closeDetailsModal()
        }
      } catch (error) {
        console.error('Error deleting activity:', error)
        showToast('Error deleting activity')
      }
    }

    const deleteSelected = async () => {
      if (!confirm(`Are you sure you want to delete ${selectedActivities.value.length} activities?`)) return

      try {
        const response = await fetch('/api/admin/activities/bulk-delete', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ ids: selectedActivities.value })
        })

        if (response.ok) {
          activities.value = activities.value.filter(a => !selectedActivities.value.includes(a.id))
          const count = selectedActivities.value.length
          selectedActivities.value = []
          showToast(`${count} activities deleted successfully`)
        }
      } catch (error) {
        console.error('Error deleting activities:', error)
        showToast('Error deleting activities')
      }
    }

    const markAsResolved = async (activity) => {
      try {
        const response = await fetch(`/api/admin/activities/${activity.id}/resolve`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          const index = activities.value.findIndex(a => a.id === activity.id)
          if (index !== -1) {
            activities.value[index].resolved = true
          }
          showToast('Activity marked as resolved')
          closeDetailsModal()
        }
      } catch (error) {
        console.error('Error marking activity as resolved:', error)
        showToast('Error marking activity as resolved')
      }
    }

    const exportActivities = async () => {
      try {
        const response = await fetch('/api/admin/activities/export', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ filters })
        })

        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = `activities-${new Date().toISOString().split('T')[0]}.csv`
          document.body.appendChild(a)
          a.click()
          window.URL.revokeObjectURL(url)
          document.body.removeChild(a)
          showToast('Activities exported successfully')
        }
      } catch (error) {
        console.error('Error exporting activities:', error)
        showToast('Error exporting activities')
      }
    }

    const exportSelected = async () => {
      try {
        const response = await fetch('/api/admin/activities/export-selected', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ ids: selectedActivities.value })
        })

        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = `selected-activities-${new Date().toISOString().split('T')[0]}.csv`
          document.body.appendChild(a)
          a.click()
          window.URL.revokeObjectURL(url)
          document.body.removeChild(a)
          showToast('Selected activities exported successfully')
        }
      } catch (error) {
        console.error('Error exporting selected activities:', error)
        showToast('Error exporting selected activities')
      }
    }

    const showToast = (message) => {
      successMessage.value = message
      showSuccessToast.value = true
      setTimeout(() => {
        showSuccessToast.value = false
      }, 3000)
    }

    // Utility methods
    const formatDateTime = (dateString) => {
      return new Date(dateString).toLocaleString()
    }

    const formatActivityType = (type) => {
      return type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
    }

    const getActivityIcon = (type) => {
      const icons = {
        login: 'fas fa-sign-in-alt',
        logout: 'fas fa-sign-out-alt',
        registration: 'fas fa-user-plus',
        profile_update: 'fas fa-user-edit',
        complaint_submit: 'fas fa-exclamation-triangle',
        donation: 'fas fa-heart',
        event_registration: 'fas fa-calendar-plus',
        admin_action: 'fas fa-cog',
        system_error: 'fas fa-bug'
      }
      return icons[type] || 'fas fa-info-circle'
    }

    const getActivityIconClass = (type) => {
      const classes = {
        login: 'icon-success',
        logout: 'icon-info',
        registration: 'icon-primary',
        profile_update: 'icon-warning',
        complaint_submit: 'icon-danger',
        donation: 'icon-success',
        event_registration: 'icon-info',
        admin_action: 'icon-secondary',
        system_error: 'icon-danger'
      }
      return classes[type] || 'icon-info'
    }

    const getActivityBadgeClass = (type) => {
      const classes = {
        login: 'badge-success',
        logout: 'badge-info',
        registration: 'badge-primary',
        profile_update: 'badge-warning',
        complaint_submit: 'badge-danger',
        donation: 'badge-success',
        event_registration: 'badge-info',
        admin_action: 'badge-secondary',
        system_error: 'badge-danger'
      }
      return classes[type] || 'badge-info'
    }

    const getSeverityBadgeClass = (severity) => {
      const classes = {
        low: 'badge-info',
        medium: 'badge-warning',
        high: 'badge-danger',
        critical: 'badge-danger'
      }
      return classes[severity] || 'badge-info'
    }

    const getChartColor = (type) => {
      const colors = {
        login: '#4caf50',
        logout: '#2196f3',
        registration: '#1976d2',
        profile_update: '#ff9800',
        complaint_submit: '#f44336',
        donation: '#4caf50',
        event_registration: '#2196f3',
        admin_action: '#9e9e9e',
        system_error: '#f44336'
      }
      return colors[type] || '#2196f3'
    }

    const initChart = () => {
      if (!activityChart.value) return

      const ctx = activityChart.value.getContext('2d')
      
      // Destroy existing chart
      if (chartInstance.value) {
        chartInstance.value.destroy()
      }

      const chartData = getChartData()
      
      chartInstance.value = new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            title: {
              display: true,
              text: 'Activity Trends Over Time'
            },
            legend: {
              display: true,
              position: 'bottom'
            }
          },
          scales: {
            x: {
              display: true,
              title: {
                display: true,
                text: 'Date'
              }
            },
            y: {
              display: true,
              title: {
                display: true,
                text: 'Number of Activities'
              },
              beginAtZero: true
            }
          }
        }
      })
    }

    const getChartData = () => {
      // Group activities by date and type
      const groupedData = {}
      const dates = []
      
      filteredActivities.value.forEach(activity => {
        const date = new Date(activity.created_at).toDateString()
        if (!groupedData[date]) {
          groupedData[date] = {}
          dates.push(date)
        }
        if (!groupedData[date][activity.type]) {
          groupedData[date][activity.type] = 0
        }
        groupedData[date][activity.type]++
      })

      const sortedDates = dates.sort((a, b) => new Date(a) - new Date(b))
      
      const datasets = activityTypes.value.map(type => ({
        label: formatActivityType(type),
        data: sortedDates.map(date => groupedData[date][type] || 0),
        borderColor: getChartColor(type),
        backgroundColor: getChartColor(type) + '20',
        tension: 0.1
      }))

      return {
        labels: sortedDates,
        datasets
      }
    }

    const updateChart = () => {
      if (chartInstance.value) {
        const chartData = getChartData()
        chartInstance.value.data = chartData
        chartInstance.value.update()
      }
    }

    // Watchers
    watch(viewMode, (newMode) => {
      if (newMode === 'chart') {
        nextTick(() => {
          initChart()
        })
      }
    })

    watch(filteredActivities, () => {
      if (viewMode.value === 'chart') {
        nextTick(() => {
          updateChart()
        })
      }
    })

    // Lifecycle
    onMounted(async () => {
      await Promise.all([loadActivities(), loadStats()])
    })

    return {
      // Reactive data
      loading,
      activities,
      stats,
      filters,
      viewMode,
      currentPage,
      itemsPerPage,
      selectedActivities,
      showDetailsModal,
      selectedActivity,
      showSuccessToast,
      successMessage,
      activityChart,
      
      // Computed
      filteredActivities,
      totalPages,
      paginatedActivities,
      visiblePages,
      activityTypes,
      
      // Methods
      loadActivities,
      loadStats,
      refreshData,
      applyFilters,
      clearFilters,
      viewActivityDetails,
      closeDetailsModal,
      deleteActivity,
      deleteSelected,
      markAsResolved,
      exportActivities,
      exportSelected,
      showToast,
      formatDateTime,
      formatActivityType,
      getActivityIcon,
      getActivityIconClass,
      getActivityBadgeClass,
      getSeverityBadgeClass,
      getChartColor,
      initChart,
      updateChart
    }
  }
}
</script>

<style scoped>
/* Admin Activities Page Styles */
.admin-activities {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  position: relative;
}

/* Loading Overlay */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  backdrop-filter: blur(5px);
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Header */
.activities-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.header-content {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.header-title {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-title h1 {
  font-size: 2rem;
  font-weight: 700;
  margin: 0;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.header-title .icon {
  font-size: 2.5rem;
  opacity: 0.9;
}

.header-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  font-size: 0.9rem;
}

.btn-primary {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.btn-primary:hover {
  background: rgba(255, 255, 255, 0.3);
  border-color: rgba(255, 255, 255, 0.5);
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.2);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.4);
  transform: translateY(-2px);
}

/* Main Content */
.activities-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

/* Statistics Cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  border-left: 4px solid #667eea;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.stat-card.warning {
  border-left-color: #ffc107;
}

.stat-card.success {
  border-left-color: #28a745;
}

.stat-card.danger {
  border-left-color: #dc3545;
}

.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.stat-title {
  font-size: 0.9rem;
  color: #6c757d;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  color: white;
}

.stat-icon.primary {
  background: linear-gradient(135deg, #667eea, #764ba2);
}

.stat-icon.warning {
  background: linear-gradient(135deg, #ffc107, #ff8c00);
}

.stat-icon.success {
  background: linear-gradient(135deg, #28a745, #20c997);
}

.stat-icon.danger {
  background: linear-gradient(135deg, #dc3545, #e83e8c);
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.stat-change {
  font-size: 0.8rem;
  font-weight: 600;
}

.stat-change.positive {
  color: #28a745;
}

.stat-change.negative {
  color: #dc3545;
}

/* Filters Section */
.filters-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.filters-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.filters-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #2c3e50;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.view-toggles {
  display: flex;
  gap: 0.5rem;
  background: #f8f9fa;
  padding: 0.25rem;
  border-radius: 8px;
}

.view-toggle {
  padding: 0.5rem 1rem;
  border: none;
  background: transparent;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 500;
  color: #6c757d;
}

.view-toggle.active {
  background: #667eea;
  color: white;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-weight: 600;
  color: #495057;
  font-size: 0.9rem;
}

.form-control {
  padding: 0.75rem;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: all 0.3s ease;
  background: white;
}

.form-control:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-box {
  position: relative;
}

.search-input {
  padding-left: 2.5rem;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #6c757d;
}

/* Activities Section */
.activities-section {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.section-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.section-title {
  font-size: 1.3rem;
  font-weight: 600;
  color: #2c3e50;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* List View */
.activities-list {
  max-height: 600px;
  overflow-y: auto;
}

.activity-item {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #f8f9fa;
  transition: all 0.3s ease;
  cursor: pointer;
}

.activity-item:hover {
  background: #f8f9fa;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.activity-user {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea, #764ba2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 0.9rem;
}

.user-info h4 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 600;
  color: #2c3e50;
}

.user-info p {
  margin: 0;
  font-size: 0.8rem;
  color: #6c757d;
}

.activity-time {
  font-size: 0.8rem;
  color: #6c757d;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.activity-content {
  margin-left: 3.25rem;
}

.activity-action {
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.25rem;
}

.activity-details {
  font-size: 0.9rem;
  color: #6c757d;
  line-height: 1.4;
}

.activity-type {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 0.5rem;
}

.activity-type.login {
  background: #e3f2fd;
  color: #1976d2;
}

.activity-type.create {
  background: #e8f5e8;
  color: #2e7d32;
}

.activity-type.update {
  background: #fff3e0;
  color: #f57c00;
}

.activity-type.delete {
  background: #ffebee;
  color: #c62828;
}

.activity-type.error {
  background: #fce4ec;
  color: #ad1457;
}

/* Timeline View */
.timeline-container {
  padding: 2rem;
  position: relative;
}

.timeline {
  position: relative;
  padding-left: 2rem;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 1rem;
  top: 0;
  bottom: 0;
  width: 2px;
  background: linear-gradient(to bottom, #667eea, #764ba2);
}

.timeline-item {
  position: relative;
  margin-bottom: 2rem;
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  margin-left: 1rem;
}

.timeline-item::before {
  content: '';
  position: absolute;
  left: -1.75rem;
  top: 1.5rem;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #667eea;
  border: 3px solid white;
  box-shadow: 0 0 0 3px #667eea;
}

.timeline-date {
  position: absolute;
  left: -8rem;
  top: 1.25rem;
  font-size: 0.8rem;
  font-weight: 600;
  color: #6c757d;
  text-align: right;
  width: 6rem;
}

/* Chart View */
.chart-container {
  padding: 2rem;
  height: 500px;
}

.chart-wrapper {
  position: relative;
  height: 100%;
  background: white;
  border-radius: 8px;
  padding: 1rem;
}

/* Bulk Actions */
.bulk-actions {
  padding: 1rem 1.5rem;
  background: #f8f9fa;
  border-top: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.bulk-info {
  font-size: 0.9rem;
  color: #6c757d;
  font-weight: 500;
}

.bulk-buttons {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.8rem;
}

.btn-danger {
  background: #dc3545;
  color: white;
  border: 2px solid #dc3545;
}

.btn-danger:hover {
  background: #c82333;
  border-color: #c82333;
  transform: translateY(-1px);
}

.btn-info {
  background: #17a2b8;
  color: white;
  border: 2px solid #17a2b8;
}

.btn-info:hover {
  background: #138496;
  border-color: #138496;
  transform: translateY(-1px);
}

/* Pagination */
.pagination-container {
  padding: 1.5rem;
  border-top: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.pagination-info {
  font-size: 0.9rem;
  color: #6c757d;
}

.pagination {
  display: flex;
  gap: 0.25rem;
  align-items: center;
}

.page-btn {
  padding: 0.5rem 0.75rem;
  border: 1px solid #dee2e6;
  background: white;
  color: #6c757d;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.9rem;
}

.page-btn:hover {
  background: #e9ecef;
  border-color: #adb5bd;
}

.page-btn.active {
  background: #667eea;
  border-color: #667eea;
  color: white;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal {
  background: white;
  border-radius: 12px;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-50px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  font-size: 1.3rem;
  font-weight: 600;
  color: #2c3e50;
  margin: 0;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #6c757d;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.3s ease;
}

.modal-close:hover {
  background: #f8f9fa;
  color: #495057;
}

.modal-body {
  padding: 1.5rem;
}

.detail-group {
  margin-bottom: 1.5rem;
}

.detail-label {
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.5rem;
  display: block;
}

.detail-value {
  color: #6c757d;
  line-height: 1.5;
}

.detail-value.code {
  background: #f8f9fa;
  padding: 0.75rem;
  border-radius: 6px;
  font-family: 'Courier New', monospace;
  font-size: 0.9rem;
  border-left: 4px solid #667eea;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #6c757d;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #495057;
}

.empty-message {
  font-size: 1rem;
  line-height: 1.5;
}

/* Success Toast */
.success-toast {
  position: fixed;
  top: 2rem;
  right: 2rem;
  background: #28a745;
  color: white;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(40, 167, 69, 0.3);
  z-index: 1001;
  animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .activities-header {
    padding: 1.5rem 1rem;
  }
  
  .header-content {
    flex-direction: column;
    align-items: stretch;
  }
  
  .header-actions {
    justify-content: center;
  }
  
  .activities-content {
    padding: 1rem;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .filters-grid {
    grid-template-columns: 1fr;
  }
  
  .filters-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .view-toggles {
    justify-content: center;
  }
  
  .section-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .activity-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .activity-content {
    margin-left: 0;
    margin-top: 1rem;
  }
  
  .bulk-actions {
    flex-direction: column;
    align-items: stretch;
  }
  
  .bulk-buttons {
    justify-content: center;
  }
  
  .pagination-container {
    flex-direction: column;
    text-align: center;
  }
  
  .timeline-date {
    position: static;
    text-align: left;
    margin-bottom: 0.5rem;
    width: auto;
  }
  
  .timeline-item {
    margin-left: 0;
  }
  
  .timeline::before {
    left: 0.5rem;
  }
  
  .timeline-item::before {
    left: -0.25rem;
  }
}

@media (max-width: 480px) {
  .header-title h1 {
    font-size: 1.5rem;
  }
  
  .btn {
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
  }
  
  .stat-value {
    font-size: 1.5rem;
  }
  
  .modal {
    margin: 0.5rem;
    max-height: 95vh;
  }
  
  .modal-header,
  .modal-body {
    padding: 1rem;
  }
}

/* Print Styles */
@media print {
  .activities-header,
  .filters-section,
  .bulk-actions,
  .pagination-container,
  .modal-overlay {
    display: none !important;
  }
  
  .activities-content {
    padding: 0;
  }
  
  .activities-section {
    box-shadow: none;
    border: 1px solid #ddd;
  }
  
  .activity-item {
    break-inside: avoid;
    border-bottom: 1px solid #ddd;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .admin-activities {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
  }
  
  .stat-card,
  .filters-section,
  .activities-section,
  .modal {
    background: #34495e;
    color: #ecf0f1;
  }
  
  .stat-title,
  .form-label,
  .section-title,
  .modal-title {
    color: #ecf0f1;
  }
  
  .stat-value {
    color: #ecf0f1;
  }
  
  .form-control {
    background: #2c3e50;
    border-color: #4a5f7a;
    color: #ecf0f1;
  }
  
  .form-control:focus {
    border-color: #667eea;
  }
  
  .activity-item:hover {
    background: #2c3e50;
  }
  
  .timeline-item {
    background: #2c3e50;
  }
  
  .bulk-actions {
    background: #2c3e50;
    border-color: #4a5f7a;
  }
  
  .page-btn {
    background: #2c3e50;
    border-color: #4a5f7a;
    color: #ecf0f1;
  }
  
  .page-btn:hover {
    background: #34495e;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Focus Styles for Keyboard Navigation */
.btn:focus,
.form-control:focus,
.page-btn:focus,
.view-toggle:focus {
  outline: 2px solid #667eea;
  outline-offset: 2px;
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .btn-primary {
    background: #000;
    border-color: #000;
    color: #fff;
  }
  
  .btn-secondary {
    background: #fff;
    border-color: #000;
    color: #000;
  }
  
  .stat-card {
    border: 2px solid #000;
  }
}

/* Custom Scrollbar */
.activities-list::-webkit-scrollbar,
.modal::-webkit-scrollbar {
  width: 8px;
}

.activities-list::-webkit-scrollbar-track,
.modal::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.activities-list::-webkit-scrollbar-thumb,
.modal::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

.activities-list::-webkit-scrollbar-thumb:hover,
.modal::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

.btn-primary:hover {
  background: rgba(255, 255, 255, 0.3);
  border-color: rgba(255, 255, 255, 0.5);
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.2);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.4);
  transform: translateY(-2px);
}

/* Main Content */
.activities-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

/* Statistics Cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  border-left: 4px solid transparent;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.stat-card.total { border-left-color: #667eea; }
.stat-card.today { border-left-color: #28a745; }
.stat-card.users { border-left-color: #ffc107; }
.stat-card.errors { border-left-color: #dc3545; }

.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.stat-title {
  font-size: 0.9rem;
  color: #6c757d;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  color: white;
}

.stat-card.total .stat-icon { background: #667eea; }
.stat-card.today .stat-icon { background: #28a745; }
.stat-card.users .stat-icon { background: #ffc107; }
.stat-card.errors .stat-icon { background: #dc3545; }

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.stat-trend {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  font-weight: 600;
}

.trend-up { color: #28a745; }
.trend-down { color: #dc3545; }

/* Filters Section */
.filters-section {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
}

.filters-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.filters-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #2c3e50;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: #495057;
}

.form-control {
  padding: 0.75rem;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: all 0.3s ease;
  background: white;
}

.form-control:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-box {
  position: relative;
}

.search-input {
  padding-left: 2.5rem;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #6c757d;
}

.filters-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  flex-wrap: wrap;
}

.btn-outline {
  background: transparent;
  color: #667eea;
  border: 2px solid #667eea;
}

.btn-outline:hover {
  background: #667eea;
  color: white;
}

/* View Toggle */
.view-toggle {
  display: flex;
  background: #f8f9fa;
  border-radius: 8px;
  padding: 0.25rem;
  gap: 0.25rem;
}

.view-btn {
  padding: 0.5rem 1rem;
  border: none;
  background: transparent;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 600;
  color: #6c757d;
}

.view-btn.active {
  background: white;
  color: #667eea;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Activities Section */
.activities-section {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.section-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.section-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #2c3e50;
}

.section-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

/* List View */
.activities-list {
  max-height: 600px;
  overflow-y: auto;
}

.activity-item {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #f8f9fa;
  transition: all 0.3s ease;
  cursor: pointer;
}

.activity-item:hover {
  background: #f8f9fa;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.activity-info {
  flex: 1;
}

.activity-type {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.activity-user {
  font-size: 0.9rem;
  color: #6c757d;
  margin-bottom: 0.25rem;
}

.activity-description {
  font-size: 0.85rem;
  color: #495057;
  line-height: 1.4;
}

.activity-meta {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-shrink: 0;
}

.activity-time {
  font-size: 0.8rem;
  color: #6c757d;
  white-space: nowrap;
}

.activity-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge-login { background: #d4edda; color: #155724; }
.badge-logout { background: #f8d7da; color: #721c24; }
.badge-registration { background: #d1ecf1; color: #0c5460; }
.badge-profile { background: #fff3cd; color: #856404; }
.badge-complaint { background: #f1c0c7; color: #6f2232; }
.badge-donation { background: #d4edda; color: #155724; }
.badge-event { background: #e2e3e5; color: #383d41; }
.badge-admin { background: #cce5ff; color: #004085; }
.badge-error { background: #f8d7da; color: #721c24; }

/* Timeline View */
.timeline-container {
  padding: 2rem;
  max-height: 600px;
  overflow-y: auto;
}

.timeline {
  position: relative;
  padding-left: 2rem;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 1rem;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #e9ecef;
}

.timeline-item {
  position: relative;
  margin-bottom: 2rem;
  background: white;
  border-radius: 8px;
  padding: 1rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  margin-left: 1rem;
}

.timeline-item::before {
  content: '';
  position: absolute;
  left: -1.5rem;
  top: 1rem;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #667eea;
  border: 3px solid white;
  box-shadow: 0 0 0 3px #e9ecef;
}

.timeline-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.timeline-info {
  flex: 1;
}

.timeline-type {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.timeline-user {
  font-size: 0.9rem;
  color: #6c757d;
  margin-bottom: 0.5rem;
}

.timeline-description {
  font-size: 0.9rem;
  color: #495057;
  line-height: 1.4;
}

.timeline-meta {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.5rem;
}

.timeline-time {
  font-size: 0.8rem;
  color: #6c757d;
  white-space: nowrap;
}

/* Chart View */
.chart-container {
  padding: 2rem;
  height: 500px;
}

.chart-wrapper {
  position: relative;
  height: 100%;
  width: 100%;
}

/* Bulk Actions */
.bulk-actions {
  position: fixed;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%);
  background: white;
  padding: 1rem 2rem;
  border-radius: 50px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  gap: 1rem;
  z-index: 1000;
  transition: all 0.3s ease;
}

.bulk-actions.show {
  opacity: 1;
  visibility: visible;
}

.bulk-actions.hide {
  opacity: 0;
  visibility: hidden;
}

.bulk-info {
  font-size: 0.9rem;
  color: #495057;
  font-weight: 600;
}

.bulk-actions .btn {
  padding: 0.5rem 1rem;
  font-size: 0.85rem;
}

.btn-danger {
  background: #dc3545;
  color: white;
  border: 2px solid #dc3545;
}

.btn-danger:hover {
  background: #c82333;
  border-color: #c82333;
  transform: translateY(-2px);
}

.btn-success {
  background: #28a745;
  color: white;
  border: 2px solid #28a745;
}

.btn-success:hover {
  background: #218838;
  border-color: #218838;
  transform: translateY(-2px);
}

/* Pagination */
.pagination-container {
  padding: 1.5rem;
  border-top: 1px solid #e9ecef;
  display: flex;
  justify-content: between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.pagination-info {
  font-size: 0.9rem;
  color: #6c757d;
}

.pagination {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.page-btn {
  padding: 0.5rem 0.75rem;
  border: 2px solid #e9ecef;
  background: white;
  color: #495057;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 600;
  min-width: 40px;
  text-align: center;
}

.page-btn:hover {
  border-color: #667eea;
  color: #667eea;
}

.page-btn.active {
  background: #667eea;
  border-color: #667eea;
  color: white;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  backdrop-filter: blur(5px);
}

.modal {
  background: white;
  border-radius: 12px;
  max-width: 600px;
  width: 90%;
  max-height: 80vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-50px) scale(0.9);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #2c3e50;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #6c757d;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 50%;
  transition: all 0.3s ease;
}

.modal-close:hover {
  background: #f8f9fa;
  color: #495057;
}

.modal-body {
  padding: 1.5rem;
}

.detail-group {
  margin-bottom: 1.5rem;
}

.detail-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-value {
  font-size: 1rem;
  color: #2c3e50;
  line-height: 1.5;
}

.json-display {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 6px;
  padding: 1rem;
  font-family: 'Courier New', monospace;
  font-size: 0.85rem;
  white-space: pre-wrap;
  max-height: 200px;
  overflow-y: auto;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  color: #6c757d;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-title {
  font-size: 1.2rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #495057;
}

.empty-message {
  font-size: 0.9rem;
  line-height: 1.5;
}

/* Success Toast */
.success-toast {
  position: fixed;
  top: 2rem;
  right: 2rem;
  background: #28a745;
  color: white;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
  z-index: 10001;
  animation: slideInRight 0.3s ease;
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .activities-header {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    align-items: stretch;
  }
  
  .header-title h1 {
    font-size: 1.5rem;
  }
  
  .activities-content {
    padding: 1rem;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .filters-grid {
    grid-template-columns: 1fr;
  }
  
  .filters-actions {
    justify-content: stretch;
  }
  
  .filters-actions .btn {
    flex: 1;
    justify-content: center;
  }
  
  .section-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .activity-header {
    flex-direction: column;
    align-items: stretch;
    gap: 0.5rem;
  }
  
  .activity-meta {
    justify-content: space-between;
  }
  
  .timeline-content {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .timeline-meta {
    align-items: flex-start;
    flex-direction: row;
    justify-content: space-between;
  }
  
  .bulk-actions {
    left: 1rem;
    right: 1rem;
    transform: none;
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .pagination-container {
    flex-direction: column;
    text-align: center;
  }
  
  .modal {
    width: 95%;
    margin: 1rem;
  }
}

@media (max-width: 480px) {
  .header-actions {
    width: 100%;
  }
  
  .header-actions .btn {
    flex: 1;
    justify-content: center;
  }
  
  .view-toggle {
    width: 100%;
  }
  
  .view-btn {
    flex: 1;
    text-align: center;
  }
  
  .activity-item {
    padding: 1rem;
  }
  
  .timeline-container {
    padding: 1rem;
  }
  
  .chart-container {
    padding: 1rem;
    height: 300px;
  }
}

/* Print Styles */
@media print {
  .admin-activities {
    background: white !important;
  }
  
  .activities-header {
    background: white !important;
    color: black !important;
    box-shadow: none !important;
  }
  
  .header-actions,
  .bulk-actions,
  .filters-section,
  .pagination-container {
    display: none !important;
  }
  
  .activities-section {
    box-shadow: none !important;
    border: 1px solid #ddd !important;
  }
  
  .activity-item {
    break-inside: avoid;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .admin-activities {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
  }
  
  .stat-card,
  .filters-section,
  .activities-section,
  .modal {
    background: #34495e;
    color: #ecf0f1;
  }
  
  .form-control {
    background: #2c3e50;
    border-color: #4a5568;
    color: #ecf0f1;
  }
  
  .activity-item:hover {
    background: #2c3e50;
  }
  
  .timeline-item {
    background: #2c3e50;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Focus Styles */
.btn:focus,
.form-control:focus,
.page-btn:focus {
  outline: 2px solid #667eea;
  outline-offset: 2px;
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .btn {
    border-width: 3px;
  }
  
  .stat-card {
    border-width: 2px;
    border-style: solid;
  }
  
  .activity-badge {
    border: 2px solid currentColor;
  }
}

/* Custom Scrollbar */
.activities-list::-webkit-scrollbar,
.timeline-container::-webkit-scrollbar,
.json-display::-webkit-scrollbar {
  width: 8px;
}

.activities-list::-webkit-scrollbar-track,
.timeline-container::-webkit-scrollbar-track,
.json-display::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.activities-list::-webkit-scrollbar-thumb,
.timeline-container::-webkit-scrollbar-thumb,
.json-display::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

.activities-list::-webkit-scrollbar-thumb:hover,
.timeline-container::-webkit-scrollbar-thumb:hover,
.json-display::-webkit-scrollbar-thumb:hover {
  background: #a0a0a0;
}
</style>