<template>
  <div class="analytics-page">
    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
      <p>Loading analytics...</p>
    </div>

    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="fas fa-chart-line"></i>
            Analytics Dashboard
          </h1>
          <p class="page-subtitle">Advanced insights and performance metrics</p>
        </div>
        <div class="header-actions">
          <button @click="refreshData" class="btn btn-secondary" :disabled="loading">
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
            Refresh
          </button>
          <button @click="exportReport" class="btn btn-primary">
            <i class="fas fa-download"></i>
            Export Report
          </button>
        </div>
      </div>
    </div>

    <!-- Date Range Filter -->
    <div class="filters-section">
      <div class="filters-row">
        <div class="filter-group">
          <label>Date Range:</label>
          <select v-model="dateRange" @change="fetchAnalytics">
            <option value="7">Last 7 days</option>
            <option value="30">Last 30 days</option>
            <option value="90">Last 3 months</option>
            <option value="365">Last year</option>
            <option value="custom">Custom Range</option>
          </select>
        </div>
        <div class="filter-group" v-if="dateRange === 'custom'">
          <label>From:</label>
          <input type="date" v-model="customDateFrom" @change="fetchAnalytics" />
        </div>
        <div class="filter-group" v-if="dateRange === 'custom'">
          <label>To:</label>
          <input type="date" v-model="customDateTo" @change="fetchAnalytics" />
        </div>
        <div class="filter-group">
          <label>Compare:</label>
          <select v-model="compareWith" @change="fetchAnalytics">
            <option value="">No Comparison</option>
            <option value="previous">Previous Period</option>
            <option value="year">Same Period Last Year</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Key Metrics Overview -->
    <div class="metrics-grid">
      <div class="metric-card">
        <div class="metric-icon visitors">
          <i class="fas fa-users"></i>
        </div>
        <div class="metric-content">
          <h3>{{ formatNumber(metrics.totalVisitors) }}</h3>
          <p>Total Visitors</p>
          <div class="metric-change" :class="metrics.visitorsChange >= 0 ? 'positive' : 'negative'">
            <i :class="metrics.visitorsChange >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
            {{ Math.abs(metrics.visitorsChange) }}%
          </div>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon members">
          <i class="fas fa-user-plus"></i>
        </div>
        <div class="metric-content">
          <h3>{{ formatNumber(metrics.newMembers) }}</h3>
          <p>New Members</p>
          <div class="metric-change" :class="metrics.membersChange >= 0 ? 'positive' : 'negative'">
            <i :class="metrics.membersChange >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
            {{ Math.abs(metrics.membersChange) }}%
          </div>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon revenue">
          <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="metric-content">
          <h3>${{ formatNumber(metrics.revenue) }}</h3>
          <p>Revenue</p>
          <div class="metric-change" :class="metrics.revenueChange >= 0 ? 'positive' : 'negative'">
            <i :class="metrics.revenueChange >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
            {{ Math.abs(metrics.revenueChange) }}%
          </div>
        </div>
      </div>
      <div class="metric-card">
        <div class="metric-icon engagement">
          <i class="fas fa-heart"></i>
        </div>
        <div class="metric-content">
          <h3>{{ metrics.engagementRate }}%</h3>
          <p>Engagement Rate</p>
          <div class="metric-change" :class="metrics.engagementChange >= 0 ? 'positive' : 'negative'">
            <i :class="metrics.engagementChange >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
            {{ Math.abs(metrics.engagementChange) }}%
          </div>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="charts-section">
      <div class="chart-row">
        <!-- Visitors Chart -->
        <div class="chart-card">
          <div class="chart-header">
            <h3>
              <i class="fas fa-chart-area"></i>
              Visitor Trends
            </h3>
            <div class="chart-controls">
              <button 
                v-for="period in ['daily', 'weekly', 'monthly']" 
                :key="period"
                @click="chartPeriod = period; fetchChartData()"
                :class="['btn', 'btn-sm', chartPeriod === period ? 'btn-primary' : 'btn-secondary']"
              >
                {{ period.charAt(0).toUpperCase() + period.slice(1) }}
              </button>
            </div>
          </div>
          <div class="chart-container">
            <canvas ref="visitorsChart" width="400" height="200"></canvas>
          </div>
        </div>

        <!-- Membership Growth Chart -->
        <div class="chart-card">
          <div class="chart-header">
            <h3>
              <i class="fas fa-chart-line"></i>
              Membership Growth
            </h3>
          </div>
          <div class="chart-container">
            <canvas ref="membershipChart" width="400" height="200"></canvas>
          </div>
        </div>
      </div>

      <div class="chart-row">
        <!-- Revenue Chart -->
        <div class="chart-card">
          <div class="chart-header">
            <h3>
              <i class="fas fa-chart-bar"></i>
              Revenue Analysis
            </h3>
          </div>
          <div class="chart-container">
            <canvas ref="revenueChart" width="400" height="200"></canvas>
          </div>
        </div>

        <!-- Top Content Chart -->
        <div class="chart-card">
          <div class="chart-header">
            <h3>
              <i class="fas fa-chart-pie"></i>
              Popular Content
            </h3>
          </div>
          <div class="chart-container">
            <canvas ref="contentChart" width="400" height="200"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Detailed Analytics Tables -->
    <div class="tables-section">
      <div class="table-row">
        <!-- Top Pages -->
        <div class="table-card">
          <div class="table-header">
            <h3>
              <i class="fas fa-file-alt"></i>
              Top Pages
            </h3>
            <span class="table-subtitle">Most visited pages</span>
          </div>
          <div class="table-content">
            <div class="table-item" v-for="page in topPages" :key="page.path">
              <div class="item-info">
                <span class="item-title">{{ page.title }}</span>
                <span class="item-subtitle">{{ page.path }}</span>
              </div>
              <div class="item-stats">
                <span class="stat-value">{{ formatNumber(page.views) }}</span>
                <span class="stat-label">views</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Traffic Sources -->
        <div class="table-card">
          <div class="table-header">
            <h3>
              <i class="fas fa-external-link-alt"></i>
              Traffic Sources
            </h3>
            <span class="table-subtitle">Where visitors come from</span>
          </div>
          <div class="table-content">
            <div class="table-item" v-for="source in trafficSources" :key="source.name">
              <div class="item-info">
                <span class="item-title">{{ source.name }}</span>
                <span class="item-subtitle">{{ source.type }}</span>
              </div>
              <div class="item-stats">
                <span class="stat-value">{{ source.percentage }}%</span>
                <span class="stat-label">of traffic</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="table-row">
        <!-- Device Analytics -->
        <div class="table-card">
          <div class="table-header">
            <h3>
              <i class="fas fa-mobile-alt"></i>
              Device Breakdown
            </h3>
            <span class="table-subtitle">Visitor device types</span>
          </div>
          <div class="table-content">
            <div class="table-item" v-for="device in deviceStats" :key="device.type">
              <div class="item-info">
                <span class="item-title">{{ device.type }}</span>
                <span class="item-subtitle">{{ device.details }}</span>
              </div>
              <div class="item-stats">
                <span class="stat-value">{{ device.percentage }}%</span>
                <span class="stat-label">of users</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Geographic Data -->
        <div class="table-card">
          <div class="table-header">
            <h3>
              <i class="fas fa-globe"></i>
              Geographic Data
            </h3>
            <span class="table-subtitle">Top visitor locations</span>
          </div>
          <div class="table-content">
            <div class="table-item" v-for="location in geoData" :key="location.country">
              <div class="item-info">
                <span class="item-title">{{ location.country }}</span>
                <span class="item-subtitle">{{ location.city }}</span>
              </div>
              <div class="item-stats">
                <span class="stat-value">{{ formatNumber(location.visitors) }}</span>
                <span class="stat-label">visitors</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Performance Metrics -->
    <div class="performance-section">
      <div class="performance-header">
        <h3>
          <i class="fas fa-tachometer-alt"></i>
          Performance Metrics
        </h3>
        <p>Website speed and performance indicators</p>
      </div>
      <div class="performance-grid">
        <div class="performance-card">
          <div class="performance-icon">
            <i class="fas fa-clock"></i>
          </div>
          <div class="performance-content">
            <h4>{{ performance.loadTime }}s</h4>
            <p>Average Load Time</p>
            <div class="performance-bar">
              <div class="performance-fill" :style="{ width: getPerformanceWidth(performance.loadTime, 3) }"></div>
            </div>
          </div>
        </div>
        <div class="performance-card">
          <div class="performance-icon">
            <i class="fas fa-bolt"></i>
          </div>
          <div class="performance-content">
            <h4>{{ performance.bounceRate }}%</h4>
            <p>Bounce Rate</p>
            <div class="performance-bar">
              <div class="performance-fill" :style="{ width: getPerformanceWidth(performance.bounceRate, 100) }"></div>
            </div>
          </div>
        </div>
        <div class="performance-card">
          <div class="performance-icon">
            <i class="fas fa-eye"></i>
          </div>
          <div class="performance-content">
            <h4>{{ performance.pageViews }}</h4>
            <p>Pages per Session</p>
            <div class="performance-bar">
              <div class="performance-fill" :style="{ width: getPerformanceWidth(performance.pageViews, 10) }"></div>
            </div>
          </div>
        </div>
        <div class="performance-card">
          <div class="performance-icon">
            <i class="fas fa-stopwatch"></i>
          </div>
          <div class="performance-content">
            <h4>{{ performance.sessionDuration }}m</h4>
            <p>Avg Session Duration</p>
            <div class="performance-bar">
              <div class="performance-fill" :style="{ width: getPerformanceWidth(performance.sessionDuration, 15) }"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Toast -->
    <div v-if="showToast" class="toast success">
      <i class="fas fa-check-circle"></i>
      <span>{{ toastMessage }}</span>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, nextTick } from 'vue'
import api from '@/utils/api'

export default {
  name: 'Analytics',
  setup() {
    // Reactive data
    const loading = ref(false)
    const dateRange = ref('30')
    const customDateFrom = ref('')
    const customDateTo = ref('')
    const compareWith = ref('')
    const chartPeriod = ref('daily')
    
    const metrics = reactive({
      totalVisitors: 12543,
      visitorsChange: 15.3,
      newMembers: 234,
      membersChange: 8.7,
      revenue: 45678,
      revenueChange: 12.4,
      engagementRate: 68.5,
      engagementChange: -2.1
    })
    
    const topPages = ref([
      { title: 'Home Page', path: '/', views: 5432 },
      { title: 'About Us', path: '/about', views: 3210 },
      { title: 'Services', path: '/services', views: 2876 },
      { title: 'Contact', path: '/contact', views: 1987 },
      { title: 'Gallery', path: '/gallery', views: 1654 }
    ])
    
    const trafficSources = ref([
      { name: 'Google Search', type: 'Organic', percentage: 45.2 },
      { name: 'Direct Traffic', type: 'Direct', percentage: 28.7 },
      { name: 'Social Media', type: 'Social', percentage: 15.3 },
      { name: 'Email Campaign', type: 'Email', percentage: 7.8 },
      { name: 'Referrals', type: 'Referral', percentage: 3.0 }
    ])
    
    const deviceStats = ref([
      { type: 'Desktop', details: 'Windows, Mac, Linux', percentage: 52.3 },
      { type: 'Mobile', details: 'iOS, Android', percentage: 38.7 },
      { type: 'Tablet', details: 'iPad, Android tablets', percentage: 9.0 }
    ])
    
    const geoData = ref([
      { country: 'United States', city: 'New York', visitors: 3456 },
      { country: 'Canada', city: 'Toronto', visitors: 2134 },
      { country: 'United Kingdom', city: 'London', visitors: 1876 },
      { country: 'Australia', city: 'Sydney', visitors: 1543 },
      { country: 'Germany', city: 'Berlin', visitors: 1234 }
    ])
    
    const performance = reactive({
      loadTime: 2.3,
      bounceRate: 32.5,
      pageViews: 4.2,
      sessionDuration: 8.7
    })
    
    // Chart references
    const visitorsChart = ref(null)
    const membershipChart = ref(null)
    const revenueChart = ref(null)
    const contentChart = ref(null)
    
    // Toast
    const showToast = ref(false)
    const toastMessage = ref('')
    
    // Methods
    const fetchAnalytics = async () => {
       loading.value = true
       try {
         const params = {
           range: dateRange.value,
           from: customDateFrom.value,
           to: customDateTo.value,
           compare: compareWith.value
         }
         
         const data = await api.get('/admin/analytics/overview', { params })
         
         if (data.success) {
           Object.assign(metrics, data.metrics)
           topPages.value = data.topPages || topPages.value
           trafficSources.value = data.trafficSources || trafficSources.value
           deviceStats.value = data.deviceStats || deviceStats.value
           geoData.value = data.geoData || geoData.value
           Object.assign(performance, data.performance || performance)
         }
       } catch (error) {
         console.error('Error fetching analytics:', error)
       } finally {
         loading.value = false
       }
     }
    
    const fetchChartData = async () => {
       try {
         const params = {
           period: chartPeriod.value,
           range: dateRange.value
         }
         
         const data = await api.get('/admin/analytics/charts', { params })
         
         if (data.success) {
           await nextTick()
           renderCharts(data.charts)
         }
       } catch (error) {
         console.error('Error fetching chart data:', error)
       }
     }
    
    const renderCharts = (chartData) => {
      // Visitors Chart
      if (visitorsChart.value) {
        const ctx = visitorsChart.value.getContext('2d')
        new Chart(ctx, {
          type: 'line',
          data: {
            labels: chartData.visitors?.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
              label: 'Visitors',
              data: chartData.visitors?.data || [120, 190, 300, 500, 200, 300, 450],
              borderColor: '#3b82f6',
              backgroundColor: 'rgba(59, 130, 246, 0.1)',
              tension: 0.4,
              fill: true
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false }
            },
            scales: {
              y: { beginAtZero: true }
            }
          }
        })
      }
      
      // Membership Chart
      if (membershipChart.value) {
        const ctx = membershipChart.value.getContext('2d')
        new Chart(ctx, {
          type: 'line',
          data: {
            labels: chartData.membership?.labels || ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
              label: 'New Members',
              data: chartData.membership?.data || [12, 19, 25, 32, 28, 34],
              borderColor: '#10b981',
              backgroundColor: 'rgba(16, 185, 129, 0.1)',
              tension: 0.4,
              fill: true
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false }
            },
            scales: {
              y: { beginAtZero: true }
            }
          }
        })
      }
      
      // Revenue Chart
      if (revenueChart.value) {
        const ctx = revenueChart.value.getContext('2d')
        new Chart(ctx, {
          type: 'bar',
          data: {
            labels: chartData.revenue?.labels || ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [{
              label: 'Revenue',
              data: chartData.revenue?.data || [1200, 1900, 3000, 2500],
              backgroundColor: '#f59e0b',
              borderRadius: 4
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false }
            },
            scales: {
              y: { beginAtZero: true }
            }
          }
        })
      }
      
      // Content Chart
      if (contentChart.value) {
        const ctx = contentChart.value.getContext('2d')
        new Chart(ctx, {
          type: 'doughnut',
          data: {
            labels: chartData.content?.labels || ['Articles', 'Gallery', 'Events', 'Services'],
            datasets: [{
              data: chartData.content?.data || [35, 25, 20, 20],
              backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
              borderWidth: 0
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'bottom',
                labels: { usePointStyle: true }
              }
            }
          }
        })
      }
    }
    
    const refreshData = async () => {
      await Promise.all([fetchAnalytics(), fetchChartData()])
      showToastMessage('Analytics data refreshed successfully')
    }
    
    const exportReport = async () => {
       try {
         const params = {
           range: dateRange.value,
           from: customDateFrom.value,
           to: customDateTo.value,
           format: 'pdf'
         }
         
         const response = await api.post('/admin/analytics/export', params, {
           responseType: 'blob'
         })
         
         const url = window.URL.createObjectURL(response)
         const a = document.createElement('a')
         a.href = url
         a.download = `analytics-report-${new Date().toISOString().split('T')[0]}.pdf`
         document.body.appendChild(a)
         a.click()
         document.body.removeChild(a)
         window.URL.revokeObjectURL(url)
         
         showToastMessage('Analytics report exported successfully')
       } catch (error) {
         console.error('Error exporting report:', error)
       }
     }
    
    const showToastMessage = (message) => {
      toastMessage.value = message
      showToast.value = true
      setTimeout(() => {
        showToast.value = false
      }, 3000)
    }
    
    // Utility functions
    const formatNumber = (num) => {
      if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M'
      } else if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K'
      }
      return num.toString()
    }
    
    const getPerformanceWidth = (value, max) => {
      return Math.min((value / max) * 100, 100) + '%'
    }
    
    // Lifecycle
    onMounted(async () => {
      // Load Chart.js
      if (!window.Chart) {
        const script = document.createElement('script')
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js'
        script.onload = () => {
          fetchAnalytics()
          fetchChartData()
        }
        document.head.appendChild(script)
      } else {
        fetchAnalytics()
        fetchChartData()
      }
    })
    
    return {
      // Data
      loading,
      dateRange,
      customDateFrom,
      customDateTo,
      compareWith,
      chartPeriod,
      metrics,
      topPages,
      trafficSources,
      deviceStats,
      geoData,
      performance,
      showToast,
      toastMessage,
      
      // Refs
      visitorsChart,
      membershipChart,
      revenueChart,
      contentChart,
      
      // Methods
      fetchAnalytics,
      fetchChartData,
      refreshData,
      exportReport,
      showToastMessage,
      formatNumber,
      getPerformanceWidth
    }
  }
}
</script>

<style scoped>
/* Analytics Page Styles */
.analytics-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  position: relative;
}

/* Loading Overlay */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.95);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #e2e8f0;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Page Header */
.page-header {
  background: white;
  border-bottom: 1px solid #e2e8f0;
  padding: 24px 32px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 1400px;
  margin: 0 auto;
}

.header-left {
  flex: 1;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 8px 0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.page-title i {
  color: #3b82f6;
  font-size: 24px;
}

.page-subtitle {
  color: #64748b;
  font-size: 16px;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 12px;
}

/* Buttons */
.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(59, 130, 246, 0.4);
}

.btn-secondary {
  background: white;
  color: #64748b;
  border: 1px solid #e2e8f0;
}

.btn-secondary:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 12px;
}

/* Filters Section */
.filters-section {
  background: white;
  border-bottom: 1px solid #e2e8f0;
  padding: 20px 32px;
}

.filters-row {
  display: flex;
  gap: 24px;
  align-items: center;
  max-width: 1400px;
  margin: 0 auto;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.filter-group label {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.filter-group select,
.filter-group input {
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 14px;
  background: white;
  min-width: 140px;
}

.filter-group select:focus,
.filter-group input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Metrics Grid */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  padding: 32px;
  max-width: 1400px;
  margin: 0 auto;
}

.metric-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 20px;
  transition: all 0.3s ease;
  border: 1px solid #f1f5f9;
}

.metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.metric-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
}

.metric-icon.visitors {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.metric-icon.members {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.metric-icon.revenue {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.metric-icon.engagement {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.metric-content {
  flex: 1;
}

.metric-content h3 {
  font-size: 32px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 4px 0;
}

.metric-content p {
  color: #64748b;
  font-size: 14px;
  margin: 0 0 8px 0;
  font-weight: 500;
}

.metric-change {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  font-weight: 600;
}

.metric-change.positive {
  color: #10b981;
}

.metric-change.negative {
  color: #ef4444;
}

/* Charts Section */
.charts-section {
  padding: 32px;
  max-width: 1400px;
  margin: 0 auto;
}

.chart-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
  gap: 24px;
  margin-bottom: 24px;
}

.chart-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border: 1px solid #f1f5f9;
  overflow: hidden;
}

.chart-header {
  padding: 20px 24px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.chart-header h3 {
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.chart-header i {
  color: #3b82f6;
}

.chart-controls {
  display: flex;
  gap: 8px;
}

.chart-container {
  padding: 24px;
  height: 300px;
  position: relative;
}

.chart-container canvas {
  max-width: 100%;
  height: 100% !important;
}

/* Tables Section */
.tables-section {
  padding: 0 32px 32px;
  max-width: 1400px;
  margin: 0 auto;
}

.table-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 24px;
  margin-bottom: 24px;
}

.table-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border: 1px solid #f1f5f9;
  overflow: hidden;
}

.table-header {
  padding: 20px 24px;
  border-bottom: 1px solid #f1f5f9;
}

.table-header h3 {
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 4px 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.table-header i {
  color: #3b82f6;
}

.table-subtitle {
  color: #64748b;
  font-size: 14px;
}

.table-content {
  max-height: 300px;
  overflow-y: auto;
}

.table-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  border-bottom: 1px solid #f8fafc;
  transition: background-color 0.2s ease;
}

.table-item:hover {
  background: #f8fafc;
}

.table-item:last-child {
  border-bottom: none;
}

.item-info {
  flex: 1;
}

.item-title {
  display: block;
  font-weight: 600;
  color: #1e293b;
  font-size: 14px;
  margin-bottom: 2px;
}

.item-subtitle {
  display: block;
  color: #64748b;
  font-size: 12px;
}

.item-stats {
  text-align: right;
}

.stat-value {
  display: block;
  font-weight: 700;
  color: #1e293b;
  font-size: 16px;
}

.stat-label {
  display: block;
  color: #64748b;
  font-size: 12px;
}

/* Performance Section */
.performance-section {
  padding: 32px;
  max-width: 1400px;
  margin: 0 auto;
}

.performance-header {
  text-align: center;
  margin-bottom: 32px;
}

.performance-header h3 {
  font-size: 24px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 8px 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}

.performance-header i {
  color: #3b82f6;
}

.performance-header p {
  color: #64748b;
  font-size: 16px;
  margin: 0;
}

.performance-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 24px;
}

.performance-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border: 1px solid #f1f5f9;
  text-align: center;
  transition: all 0.3s ease;
}

.performance-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.performance-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
  color: white;
  font-size: 24px;
}

.performance-content h4 {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 4px 0;
}

.performance-content p {
  color: #64748b;
  font-size: 14px;
  margin: 0 0 16px 0;
  font-weight: 500;
}

.performance-bar {
  width: 100%;
  height: 6px;
  background: #f1f5f9;
  border-radius: 3px;
  overflow: hidden;
}

.performance-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981 0%, #059669 100%);
  border-radius: 3px;
  transition: width 0.3s ease;
}

/* Toast Notification */
.toast {
  position: fixed;
  top: 20px;
  right: 20px;
  background: white;
  border-radius: 8px;
  padding: 16px 20px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 1000;
  animation: slideIn 0.3s ease;
  border-left: 4px solid #10b981;
}

.toast.success {
  border-left-color: #10b981;
}

.toast i {
  color: #10b981;
  font-size: 18px;
}

.toast span {
  color: #1e293b;
  font-weight: 500;
}

@keyframes slideIn {
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
  .chart-row {
    grid-template-columns: 1fr;
  }
  
  .table-row {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .page-header {
    padding: 16px 20px;
  }
  
  .header-content {
    flex-direction: column;
    gap: 16px;
    align-items: stretch;
  }
  
  .header-actions {
    justify-content: center;
  }
  
  .filters-section {
    padding: 16px 20px;
  }
  
  .filters-row {
    flex-direction: column;
    align-items: stretch;
    gap: 16px;
  }
  
  .metrics-grid {
    grid-template-columns: 1fr;
    padding: 20px;
    gap: 16px;
  }
  
  .charts-section {
    padding: 20px;
  }
  
  .chart-row {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .chart-card {
    min-width: 0;
  }
  
  .tables-section {
    padding: 0 20px 20px;
  }
  
  .table-row {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .performance-section {
    padding: 20px;
  }
  
  .performance-grid {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
  }
}

@media (max-width: 480px) {
  .page-title {
    font-size: 24px;
  }
  
  .metric-card {
    flex-direction: column;
    text-align: center;
    gap: 16px;
  }
  
  .chart-container {
    height: 250px;
    padding: 16px;
  }
  
  .performance-grid {
    grid-template-columns: 1fr;
  }
}

/* Print Styles */
@media print {
  .analytics-page {
    background: white;
  }
  
  .loading-overlay,
  .header-actions,
  .filters-section,
  .toast {
    display: none !important;
  }
  
  .page-header {
    box-shadow: none;
    border-bottom: 2px solid #000;
  }
  
  .metric-card,
  .chart-card,
  .table-card,
  .performance-card {
    box-shadow: none;
    border: 1px solid #000;
    break-inside: avoid;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .analytics-page {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  }
  
  .page-header,
  .filters-section,
  .metric-card,
  .chart-card,
  .table-card,
  .performance-card,
  .toast {
    background: #1e293b;
    border-color: #334155;
  }
  
  .page-title,
  .metric-content h3,
  .chart-header h3,
  .table-header h3,
  .performance-header h3,
  .performance-content h4,
  .item-title,
  .stat-value {
    color: #f1f5f9;
  }
  
  .page-subtitle,
  .metric-content p,
  .table-subtitle,
  .item-subtitle,
  .stat-label,
  .performance-header p,
  .performance-content p {
    color: #94a3b8;
  }
  
  .filter-group select,
  .filter-group input {
    background: #334155;
    border-color: #475569;
    color: #f1f5f9;
  }
  
  .btn-secondary {
    background: #334155;
    color: #94a3b8;
    border-color: #475569;
  }
  
  .table-item:hover {
    background: #334155;
  }
  
  .performance-bar {
    background: #334155;
  }
}

/* Animation for metric changes */
.metric-change {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

/* Smooth transitions */
* {
  transition: color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
}

/* Focus styles for accessibility */
.btn:focus,
.filter-group select:focus,
.filter-group input:focus {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

/* Loading animation for charts */
.chart-container::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top: 3px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  z-index: 1;
}

.chart-container canvas {
  position: relative;
  z-index: 2;
}
</style>