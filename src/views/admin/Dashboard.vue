<template>
  <div class="admin-dashboard">
    <!-- Loading State -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
      <p>Loading dashboard...</p>
    </div>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-6">
            <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
            <p class="text-muted">Welcome back, {{ authStore.user?.name }}! Here's your organization overview.</p>
          </div>
          <div class="col-md-6 text-end">
            <div class="dashboard-actions">
              <button @click="refreshData" class="btn btn-outline-primary me-2" :disabled="refreshing">
                <i class="fas fa-sync-alt" :class="{ 'fa-spin': refreshing }"></i>
                Refresh
              </button>
              <button @click="exportReport" class="btn btn-primary" :disabled="exporting">
                <i class="fas fa-download"></i>
                <span v-if="exporting">Exporting...</span>
                <span v-else>Export Report</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="stats-section">
      <div class="container-fluid">
        <div class="row">
          <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card members">
              <div class="card-body">
                <div class="stats-icon">
                  <i class="fas fa-users"></i>
                </div>
                <div class="stats-content">
                  <h3>{{ formatNumber(stats.totalMembers) }}</h3>
                  <p>Total Members</p>
                  <div class="stats-change" :class="stats.membersChange >= 0 ? 'positive' : 'negative'">
                    <i :class="stats.membersChange >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                    {{ Math.abs(stats.membersChange) }}% from last month
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card events">
              <div class="card-body">
                <div class="stats-icon">
                  <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stats-content">
                  <h3>{{ formatNumber(stats.totalEvents) }}</h3>
                  <p>Active Events</p>
                  <div class="stats-change" :class="stats.eventsChange >= 0 ? 'positive' : 'negative'">
                    <i :class="stats.eventsChange >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                    {{ Math.abs(stats.eventsChange) }}% from last month
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card complaints">
              <div class="card-body">
                <div class="stats-icon">
                  <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stats-content">
                  <h3>{{ formatNumber(stats.pendingComplaints) }}</h3>
                  <p>Pending Complaints</p>
                  <div class="stats-change" :class="stats.complaintsChange <= 0 ? 'positive' : 'negative'">
                    <i :class="stats.complaintsChange <= 0 ? 'fas fa-arrow-down' : 'fas fa-arrow-up'"></i>
                    {{ Math.abs(stats.complaintsChange) }}% from last month
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card donations">
              <div class="card-body">
                <div class="stats-icon">
                  <i class="fas fa-heart"></i>
                </div>
                <div class="stats-content">
                  <h3>₹{{ formatCurrency(stats.totalDonations) }}</h3>
                  <p>Total Donations</p>
                  <div class="stats-change" :class="stats.donationsChange >= 0 ? 'positive' : 'negative'">
                    <i :class="stats.donationsChange >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                    {{ Math.abs(stats.donationsChange) }}% from last month
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts and Analytics -->
    <div class="analytics-section">
      <div class="container-fluid">
        <div class="row">
          <!-- Member Growth Chart -->
          <div class="col-xl-8 col-lg-7 mb-4">
            <div class="chart-card">
              <div class="card-header">
                <h5><i class="fas fa-chart-line"></i> Member Growth</h5>
                <div class="chart-controls">
                  <select v-model="memberGrowthPeriod" @change="loadMemberGrowthData" class="form-select form-select-sm">
                    <option value="7d">Last 7 Days</option>
                    <option value="30d">Last 30 Days</option>
                    <option value="90d">Last 90 Days</option>
                    <option value="1y">Last Year</option>
                  </select>
                </div>
              </div>
              <div class="card-body">
                <canvas ref="memberGrowthChart" height="300"></canvas>
              </div>
            </div>
          </div>

          <!-- Member Distribution -->
          <div class="col-xl-4 col-lg-5 mb-4">
            <div class="chart-card">
              <div class="card-header">
                <h5><i class="fas fa-chart-pie"></i> Member Distribution</h5>
              </div>
              <div class="card-body">
                <canvas ref="memberDistributionChart" height="300"></canvas>
                <div class="distribution-legend">
                  <div v-for="(item, index) in memberDistribution" :key="index" class="legend-item">
                    <span class="legend-color" :style="{ backgroundColor: item.color }"></span>
                    <span class="legend-label">{{ item.label }}</span>
                    <span class="legend-value">{{ item.value }}%</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <!-- Recent Activities -->
          <div class="col-xl-6 col-lg-6 mb-4">
            <div class="activity-card">
              <div class="card-header">
                <h5><i class="fas fa-clock"></i> Recent Activities</h5>
                <a href="#" @click.prevent="viewAllActivities" class="view-all">View All</a>
              </div>
              <div class="card-body">
                <div v-if="recentActivities.length === 0" class="empty-state">
                  <i class="fas fa-history"></i>
                  <p>No recent activities</p>
                </div>
                <div v-else class="activity-list">
                  <div v-for="activity in recentActivities" :key="activity.id" class="activity-item">
                    <div class="activity-icon" :class="activity.type">
                      <i :class="getActivityIcon(activity.type)"></i>
                    </div>
                    <div class="activity-content">
                      <h6>{{ activity.title }}</h6>
                      <p>{{ activity.description }}</p>
                      <small class="text-muted">{{ formatTime(activity.created_at) }}</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- System Status -->
          <div class="col-xl-6 col-lg-6 mb-4">
            <div class="status-card">
              <div class="card-header">
                <h5><i class="fas fa-server"></i> System Status</h5>
                <span class="status-indicator" :class="systemStatus.overall">{{ systemStatus.overall }}</span>
              </div>
              <div class="card-body">
                <div class="status-list">
                  <div v-for="service in systemStatus.services" :key="service.name" class="status-item">
                    <div class="status-info">
                      <h6>{{ service.name }}</h6>
                      <p>{{ service.description }}</p>
                    </div>
                    <div class="status-badge" :class="service.status">
                      <i :class="getStatusIcon(service.status)"></i>
                      {{ service.status }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions-section">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="quick-actions-card">
              <div class="card-header">
                <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
              </div>
              <div class="card-body">
                <div class="actions-grid">
                  <div class="action-item" @click="navigateTo('/admin/members/new')">
                    <div class="action-icon">
                      <i class="fas fa-user-plus"></i>
                    </div>
                    <h6>Add Member</h6>
                    <p>Register new member</p>
                  </div>

                  <div class="action-item" @click="navigateTo('/admin/events/new')">
                    <div class="action-icon">
                      <i class="fas fa-calendar-plus"></i>
                    </div>
                    <h6>Create Event</h6>
                    <p>Schedule new event</p>
                  </div>

                  <div class="action-item" @click="navigateTo('/admin/complaints')">
                    <div class="action-icon">
                      <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h6>Review Complaints</h6>
                    <p>Handle pending issues</p>
                  </div>

                  <div class="action-item" @click="navigateTo('/admin/reports')">
                    <div class="action-icon">
                      <i class="fas fa-chart-bar"></i>
                    </div>
                    <h6>Generate Report</h6>
                    <p>Create analytics report</p>
                  </div>

                  <div class="action-item" @click="navigateTo('/admin/settings')">
                    <div class="action-icon">
                      <i class="fas fa-cog"></i>
                    </div>
                    <h6>System Settings</h6>
                    <p>Configure system</p>
                  </div>

                  <div class="action-item" @click="navigateTo('/admin/backup')">
                    <div class="action-icon">
                      <i class="fas fa-database"></i>
                    </div>
                    <h6>Backup Data</h6>
                    <p>Create system backup</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Alerts and Notifications -->
    <div v-if="alerts.length > 0" class="alerts-section">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="alerts-card">
              <div class="card-header">
                <h5><i class="fas fa-bell"></i> System Alerts</h5>
                <button @click="dismissAllAlerts" class="btn btn-sm btn-outline-secondary">Dismiss All</button>
              </div>
              <div class="card-body">
                <div class="alerts-list">
                  <div v-for="alert in alerts" :key="alert.id" class="alert-item" :class="alert.type">
                    <div class="alert-icon">
                      <i :class="getAlertIcon(alert.type)"></i>
                    </div>
                    <div class="alert-content">
                      <h6>{{ alert.title }}</h6>
                      <p>{{ alert.message }}</p>
                      <small class="text-muted">{{ formatTime(alert.created_at) }}</small>
                    </div>
                    <button @click="dismissAlert(alert.id)" class="alert-dismiss">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Toast -->
    <div v-if="showSuccessToast" class="success-toast">
      <i class="fas fa-check-circle"></i>
      {{ successMessage }}
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, nextTick } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import Chart from 'chart.js/auto'
import { api } from '@/utils/api'

export default {
  name: 'AdminDashboard',
  setup() {
    const authStore = useAuthStore()
    const router = useRouter()

    // Reactive data
    const loading = ref(true)
    const refreshing = ref(false)
    const exporting = ref(false)
    const showSuccessToast = ref(false)
    const successMessage = ref('')

    // Chart refs
    const memberGrowthChart = ref(null)
    const memberDistributionChart = ref(null)
    const memberGrowthPeriod = ref('30d')

    // Chart instances
    let memberGrowthChartInstance = null
    let memberDistributionChartInstance = null

    // Dashboard data
    const stats = reactive({
      totalMembers: 0,
      membersChange: 0,
      totalEvents: 0,
      eventsChange: 0,
      pendingComplaints: 0,
      complaintsChange: 0,
      totalDonations: 0,
      donationsChange: 0
    })

    const memberDistribution = ref([])
    const recentActivities = ref([])
    const alerts = ref([])

    const systemStatus = reactive({
      overall: 'healthy',
      services: []
    })

    // Methods
    const loadDashboardData = async () => {
      try {
        loading.value = true
        const response = await api.get('/admin/dashboard')

        if (response.data.success) {
          const data = response.data.data
          
          // Update stats with proper mapping
          stats.totalMembers = data.stats?.totalMembers || 0
          stats.membersChange = data.stats?.membersChange || 0
          stats.totalEvents = data.stats?.totalEvents || 0
          stats.eventsChange = data.stats?.eventsChange || 0
          stats.pendingComplaints = data.stats?.pendingComplaints || 0
          stats.complaintsChange = data.stats?.complaintsChange || 0
          stats.totalDonations = data.stats?.totalDonations || 0
          stats.donationsChange = data.stats?.donationsChange || 0
          
          // Update other data
          memberDistribution.value = data.memberDistribution || []
          recentActivities.value = data.recentActivities || []
          alerts.value = data.alerts || []
          
          if (data.systemStatus) {
            systemStatus.overall = data.systemStatus.overall || 'healthy'
            systemStatus.services = data.systemStatus.services || []
          }
          
        } else {
          throw new Error(response.data.message || 'Failed to load dashboard data')
        }
      } catch (err) {
        console.error('Error loading dashboard:', err)
        showError(err.response?.data?.message || err.message || 'Failed to load dashboard data')
      } finally {
        loading.value = false
      }
    }

    const loadMemberGrowthData = async () => {
      try {
        const response = await api.get(`/admin/analytics/member-growth?period=${memberGrowthPeriod.value}`)

        if (response.data.success) {
          updateMemberGrowthChart(response.data.data)
        }
      } catch (err) {
        console.error('Error loading member growth data:', err)
      }
    }

    const updateMemberGrowthChart = (data) => {
      if (memberGrowthChartInstance) {
        memberGrowthChartInstance.destroy()
      }

      const ctx = memberGrowthChart.value.getContext('2d')
      memberGrowthChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
          labels: data.labels,
          datasets: [{
            label: 'New Members',
            data: data.values,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(0, 0, 0, 0.1)'
              }
            },
            x: {
              grid: {
                color: 'rgba(0, 0, 0, 0.1)'
              }
            }
          }
        }
      })
    }

    const updateMemberDistributionChart = () => {
      if (memberDistributionChartInstance) {
        memberDistributionChartInstance.destroy()
      }

      const ctx = memberDistributionChart.value.getContext('2d')
      memberDistributionChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: memberDistribution.value.map(item => item.label),
          datasets: [{
            data: memberDistribution.value.map(item => item.value),
            backgroundColor: memberDistribution.value.map(item => item.color),
            borderWidth: 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            }
          }
        }
      })
    }

    const refreshData = async () => {
      refreshing.value = true
      try {
        await loadDashboardData()
        await loadMemberGrowthData()
        showSuccess('Dashboard data refreshed!')
      } catch (err) {
        console.error('Error refreshing data:', err)
      } finally {
        refreshing.value = false
      }
    }

    const exportReport = async () => {
      exporting.value = true
      
      try {
        const response = await api.post('/admin/reports/export', {
          type: 'dashboard',
          period: memberGrowthPeriod.value
        }, {
          responseType: 'blob'
        })

        const blob = response.data
        const url = window.URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = `dashboard-report-${new Date().toISOString().split('T')[0]}.pdf`
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        window.URL.revokeObjectURL(url)
        
        showSuccess('Report exported successfully!')
      } catch (err) {
        console.error('Error exporting report:', err)
        alert('Failed to export report')
      } finally {
        exporting.value = false
      }
    }

    const navigateTo = (path) => {
      router.push(path)
    }

    const viewAllActivities = () => {
      router.push('/admin/activities')
    }

    const dismissAlert = async (alertId) => {
      try {
        const response = await api.delete(`/admin/alerts/${alertId}`)

        if (response.data.success) {
          alerts.value = alerts.value.filter(alert => alert.id !== alertId)
          showSuccess('Alert dismissed!')
        }
      } catch (err) {
        console.error('Error dismissing alert:', err)
        showError(err.response?.data?.message || 'Failed to dismiss alert')
      }
    }

    const dismissAllAlerts = async () => {
      try {
        const response = await api.post('/admin/alerts/dismiss-all')

        if (response.data.success) {
          alerts.value = []
          showSuccess('All alerts dismissed!')
        }
      } catch (err) {
        console.error('Error dismissing alerts:', err)
      }
    }

    // Utility methods
    const formatNumber = (num) => {
      return new Intl.NumberFormat('en-IN').format(num)
    }

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('en-IN', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(amount)
    }

    const formatTime = (timestamp) => {
      return new Date(timestamp).toLocaleString()
    }

    const getActivityIcon = (type) => {
      const icons = {
        member: 'fas fa-user-plus',
        event: 'fas fa-calendar-plus',
        complaint: 'fas fa-exclamation-circle',
        donation: 'fas fa-heart',
        system: 'fas fa-cog'
      }
      return icons[type] || 'fas fa-info-circle'
    }

    const getStatusIcon = (status) => {
      const icons = {
        healthy: 'fas fa-check-circle',
        warning: 'fas fa-exclamation-triangle',
        error: 'fas fa-times-circle'
      }
      return icons[status] || 'fas fa-question-circle'
    }

    const getAlertIcon = (type) => {
      const icons = {
        info: 'fas fa-info-circle',
        warning: 'fas fa-exclamation-triangle',
        error: 'fas fa-times-circle',
        success: 'fas fa-check-circle'
      }
      return icons[type] || 'fas fa-bell'
    }

    const showSuccess = (message) => {
      successMessage.value = message
      showSuccessToast.value = true
      setTimeout(() => {
        showSuccessToast.value = false
      }, 5000)
    }

    const showError = (message) => {
      alert(message)
    }

    // Lifecycle hooks
    onMounted(async () => {
      try {
        await loadDashboardData()
        await loadMemberGrowthData()
        
        // Initialize charts after data is loaded
        await nextTick()
        updateMemberDistributionChart()
        
      } catch (err) {
        console.error('Error initializing dashboard:', err)
      } finally {
        loading.value = false
      }
    })

    return {
      // Reactive data
      loading,
      refreshing,
      exporting,
      showSuccessToast,
      successMessage,
      memberGrowthChart,
      memberDistributionChart,
      memberGrowthPeriod,
      stats,
      memberDistribution,
      recentActivities,
      alerts,
      systemStatus,
      authStore,
      
      // Methods
      loadDashboardData,
      refreshData,
      exportReport,
      navigateTo,
      viewAllActivities,
      dismissAlert,
      dismissAllAlerts,
      loadMemberGrowthData,
      formatNumber,
      formatCurrency,
      formatTime,
      getActivityIcon,
      getStatusIcon,
      getAlertIcon,
      showSuccess,
      showError
    }
  }
}
</script>

<style scoped>
/* Admin Dashboard Styles */
.admin-dashboard {
  min-height: 100vh;
  background: #f8f9fa;
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
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 1050;
}

.loading-spinner {
  width: 3rem;
  height: 3rem;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Dashboard Header */
.dashboard-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2rem 0;
  margin-bottom: 2rem;
}

.dashboard-header h1 {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.dashboard-header p {
  font-size: 1.1rem;
  opacity: 0.9;
  margin: 0;
}

.dashboard-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.btn {
  border-radius: 10px;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
}

.btn-primary {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  backdrop-filter: blur(10px);
}

.btn-primary:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-2px);
}

.btn-outline-primary {
  border: 2px solid rgba(255, 255, 255, 0.3);
  color: white;
  background: transparent;
}

.btn-outline-primary:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

/* Stats Section */
.stats-section {
  margin-bottom: 2rem;
}

.stats-card {
  background: white;
  border-radius: 15px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  overflow: hidden;
  position: relative;
}

.stats-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.stats-card.members {
  border-left: 5px solid #667eea;
}

.stats-card.events {
  border-left: 5px solid #28a745;
}

.stats-card.complaints {
  border-left: 5px solid #ffc107;
}

.stats-card.donations {
  border-left: 5px solid #dc3545;
}

.stats-card .card-body {
  padding: 2rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.stats-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  color: white;
  flex-shrink: 0;
}

.stats-card.members .stats-icon {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stats-card.events .stats-icon {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.stats-card.complaints .stats-icon {
  background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
}

.stats-card.donations .stats-icon {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}

.stats-content h3 {
  font-size: 2.5rem;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.stats-content p {
  font-size: 1.1rem;
  color: #6c757d;
  margin-bottom: 0.75rem;
  font-weight: 600;
}

.stats-change {
  font-size: 0.9rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.stats-change.positive {
  color: #28a745;
}

.stats-change.negative {
  color: #dc3545;
}

/* Analytics Section */
.analytics-section {
  margin-bottom: 2rem;
}

.chart-card,
.activity-card,
.status-card {
  background: white;
  border-radius: 15px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-header {
  background: #f8f9fa;
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.card-header h5 {
  margin: 0;
  font-weight: 600;
  color: #2c3e50;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.card-header i {
  color: #667eea;
}

.chart-controls .form-select {
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 0.5rem 1rem;
  font-size: 0.9rem;
}

.card-body {
  padding: 1.5rem;
}

/* Member Distribution */
.distribution-legend {
  margin-top: 1rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f1f3f4;
}

.legend-item:last-child {
  border-bottom: none;
}

.legend-color {
  width: 16px;
  height: 16px;
  border-radius: 4px;
  flex-shrink: 0;
}

.legend-label {
  flex: 1;
  font-weight: 500;
  color: #2c3e50;
}

.legend-value {
  font-weight: 600;
  color: #667eea;
}

/* Recent Activities */
.activity-list {
  max-height: 400px;
  overflow-y: auto;
}

.activity-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem 0;
  border-bottom: 1px solid #f1f3f4;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.activity-icon.member {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.activity-icon.event {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.activity-icon.complaint {
  background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
}

.activity-icon.donation {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}

.activity-icon.system {
  background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
}

.activity-content h6 {
  margin: 0 0 0.25rem 0;
  font-weight: 600;
  color: #2c3e50;
}

.activity-content p {
  margin: 0 0 0.5rem 0;
  color: #6c757d;
  font-size: 0.9rem;
}

.view-all {
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9rem;
}

.view-all:hover {
  text-decoration: underline;
}

/* System Status */
.status-indicator {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-indicator.healthy {
  background: #d4edda;
  color: #155724;
}

.status-indicator.warning {
  background: #fff3cd;
  color: #856404;
}

.status-indicator.error {
  background: #f8d7da;
  color: #721c24;
}

.status-list {
  space-y: 1rem;
}

.status-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 10px;
  margin-bottom: 1rem;
}

.status-info h6 {
  margin: 0 0 0.25rem 0;
  font-weight: 600;
  color: #2c3e50;
}

.status-info p {
  margin: 0;
  color: #6c757d;
  font-size: 0.9rem;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.healthy {
  background: #d4edda;
  color: #155724;
}

.status-badge.warning {
  background: #fff3cd;
  color: #856404;
}

.status-badge.error {
  background: #f8d7da;
  color: #721c24;
}

/* Quick Actions */
.quick-actions-section {
  margin-bottom: 2rem;
}

.quick-actions-card {
  background: white;
  border-radius: 15px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.action-item {
  text-align: center;
  padding: 2rem 1rem;
  border-radius: 15px;
  background: #f8f9fa;
  transition: all 0.3s ease;
  cursor: pointer;
  border: 2px solid transparent;
}

.action-item:hover {
  background: #667eea;
  color: white;
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.action-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin: 0 auto 1rem;
  transition: all 0.3s ease;
}

.action-item:hover .action-icon {
  background: rgba(255, 255, 255, 0.2);
  transform: scale(1.1);
}

.action-item h6 {
  margin: 0 0 0.5rem 0;
  font-weight: 600;
  color: #2c3e50;
  transition: color 0.3s ease;
}

.action-item:hover h6 {
  color: white;
}

.action-item p {
  margin: 0;
  color: #6c757d;
  font-size: 0.9rem;
  transition: color 0.3s ease;
}

.action-item:hover p {
  color: rgba(255, 255, 255, 0.8);
}

/* Alerts Section */
.alerts-section {
  margin-bottom: 2rem;
}

.alerts-card {
  background: white;
  border-radius: 15px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.alerts-list {
  max-height: 400px;
  overflow-y: auto;
}

.alert-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.5rem;
  border-bottom: 1px solid #f1f3f4;
  position: relative;
}

.alert-item:last-child {
  border-bottom: none;
}

.alert-item.info {
  border-left: 4px solid #17a2b8;
}

.alert-item.warning {
  border-left: 4px solid #ffc107;
}

.alert-item.error {
  border-left: 4px solid #dc3545;
}

.alert-item.success {
  border-left: 4px solid #28a745;
}

.alert-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.alert-item.info .alert-icon {
  background: #17a2b8;
}

.alert-item.warning .alert-icon {
  background: #ffc107;
}

.alert-item.error .alert-icon {
  background: #dc3545;
}

.alert-item.success .alert-icon {
  background: #28a745;
}

.alert-content {
  flex: 1;
}

.alert-content h6 {
  margin: 0 0 0.5rem 0;
  font-weight: 600;
  color: #2c3e50;
}

.alert-content p {
  margin: 0 0 0.5rem 0;
  color: #6c757d;
  font-size: 0.9rem;
}

.alert-dismiss {
  background: none;
  border: none;
  color: #6c757d;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 50%;
  transition: all 0.3s ease;
}

.alert-dismiss:hover {
  background: #f8f9fa;
  color: #dc3545;
}

/* Empty States */
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: #6c757d;
}

.empty-state i {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-state p {
  margin: 0;
  font-size: 1.1rem;
}

/* Success Toast */
.success-toast {
  position: fixed;
  top: 2rem;
  right: 2rem;
  background: #28a745;
  color: white;
  padding: 1rem 1.5rem;
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
  z-index: 1050;
  animation: slideInRight 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

@keyframes slideInRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

/* Responsive Design */
@media (max-width: 1200px) {
  .actions-grid {
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  }
}

@media (max-width: 768px) {
  .dashboard-header {
    padding: 1.5rem 0;
  }

  .dashboard-header h1 {
    font-size: 2rem;
  }

  .dashboard-actions {
    flex-direction: column;
    align-items: stretch;
    gap: 0.5rem;
  }

  .stats-card .card-body {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }

  .stats-icon {
    width: 60px;
    height: 60px;
    font-size: 1.5rem;
  }

  .stats-content h3 {
    font-size: 2rem;
  }

  .actions-grid {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
  }

  .action-item {
    padding: 1.5rem 1rem;
  }

  .action-icon {
    width: 50px;
    height: 50px;
    font-size: 1.25rem;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .success-toast {
    top: 1rem;
    right: 1rem;
    left: 1rem;
  }
}

@media (max-width: 576px) {
  .dashboard-header h1 {
    font-size: 1.75rem;
  }

  .stats-content h3 {
    font-size: 1.75rem;
  }

  .actions-grid {
    grid-template-columns: 1fr;
  }

  .btn {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
  }
}

/* Print Styles */
@media print {
  .admin-dashboard {
    background: white !important;
  }

  .dashboard-actions,
  .success-toast,
  .alert-dismiss {
    display: none !important;
  }

  .stats-card,
  .chart-card,
  .activity-card,
  .status-card,
  .quick-actions-card,
  .alerts-card {
    box-shadow: none !important;
    border: 1px solid #ddd !important;
    break-inside: avoid;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .admin-dashboard {
    background: #1a1a1a;
  }

  .stats-card,
  .chart-card,
  .activity-card,
  .status-card,
  .quick-actions-card,
  .alerts-card {
    background: #2d2d2d;
    color: #f8f9fa;
  }

  .card-header {
    background: #3d3d3d;
    border-color: #4d4d4d;
  }

  .card-header h5,
  .stats-content h3,
  .activity-content h6,
  .status-info h6,
  .alert-content h6,
  .action-item h6 {
    color: #f8f9fa;
  }

  .stats-content p,
  .activity-content p,
  .status-info p,
  .alert-content p,
  .action-item p {
    color: #adb5bd;
  }

  .action-item {
    background: #3d3d3d;
  }

  .action-item:hover {
    background: #667eea;
  }

  .status-item {
    background: #3d3d3d;
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

/* Focus Styles for Accessibility */
.btn:focus,
.action-item:focus,
.alert-dismiss:focus {
  outline: 2px solid #667eea;
  outline-offset: 2px;
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .stats-card,
  .chart-card,
  .activity-card,
  .status-card,
  .quick-actions-card,
  .alerts-card {
    border: 2px solid #000;
  }

  .btn-primary {
    background: #000;
    border: 2px solid #fff;
  }

  .btn-outline-primary {
    border-color: #000;
    color: #000;
  }
}
</style>