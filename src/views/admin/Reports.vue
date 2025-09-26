<template>
  <div class="admin-reports">
    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
    </div>

    <!-- Reports Header -->
    <div class="reports-header">
      <div class="header-content">
        <div class="header-title">
          <i class="fas fa-chart-bar icon"></i>
          <h1>Reports & Analytics</h1>
        </div>
        <div class="header-actions">
          <button @click="refreshReports" class="btn btn-secondary">
            <i class="fas fa-sync-alt"></i>
            Refresh
          </button>
          <button @click="exportAllReports" class="btn btn-primary">
            <i class="fas fa-download"></i>
            Export All
          </button>
          <button @click="showScheduleModal = true" class="btn btn-primary">
            <i class="fas fa-clock"></i>
            Schedule Report
          </button>
        </div>
      </div>
    </div>

    <div class="reports-content">
      <!-- Quick Stats -->
      <div class="stats-grid">
        <div class="stat-card primary">
          <div class="stat-header">
            <span class="stat-title">Total Reports</span>
            <div class="stat-icon primary">
              <i class="fas fa-file-alt"></i>
            </div>
          </div>
          <div class="stat-value">{{ stats.totalReports }}</div>
          <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i>
            +{{ stats.reportsGrowth }}% this month
          </div>
        </div>

        <div class="stat-card success">
          <div class="stat-header">
            <span class="stat-title">Generated Today</span>
            <div class="stat-icon success">
              <i class="fas fa-calendar-day"></i>
            </div>
          </div>
          <div class="stat-value">{{ stats.todayReports }}</div>
          <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i>
            +{{ stats.todayGrowth }}% vs yesterday
          </div>
        </div>

        <div class="stat-card warning">
          <div class="stat-header">
            <span class="stat-title">Scheduled Reports</span>
            <div class="stat-icon warning">
              <i class="fas fa-clock"></i>
            </div>
          </div>
          <div class="stat-value">{{ stats.scheduledReports }}</div>
          <div class="stat-change">
            <i class="fas fa-calendar"></i>
            {{ stats.nextScheduled }} next
          </div>
        </div>

        <div class="stat-card info">
          <div class="stat-header">
            <span class="stat-title">Data Sources</span>
            <div class="stat-icon info">
              <i class="fas fa-database"></i>
            </div>
          </div>
          <div class="stat-value">{{ stats.dataSources }}</div>
          <div class="stat-change">
            <i class="fas fa-check-circle"></i>
            All connected
          </div>
        </div>
      </div>

      <!-- Report Categories -->
      <div class="report-categories">
        <div class="section-header">
          <h2 class="section-title">
            <i class="fas fa-folder-open"></i>
            Report Categories
          </h2>
        </div>

        <div class="categories-grid">
          <div 
            v-for="category in reportCategories" 
            :key="category.id"
            class="category-card"
            @click="selectCategory(category)"
          >
            <div class="category-icon" :class="category.color">
              <i :class="category.icon"></i>
            </div>
            <div class="category-info">
              <h3>{{ category.name }}</h3>
              <p>{{ category.description }}</p>
              <div class="category-stats">
                <span class="report-count">{{ category.reportCount }} reports</span>
                <span class="last-generated">Last: {{ formatDate(category.lastGenerated) }}</span>
              </div>
            </div>
            <div class="category-actions">
              <button @click.stop="generateReport(category)" class="btn btn-sm btn-primary">
                <i class="fas fa-play"></i>
                Generate
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Reports -->
      <div class="recent-reports">
        <div class="section-header">
          <h2 class="section-title">
            <i class="fas fa-history"></i>
            Recent Reports
          </h2>
          <div class="section-actions">
            <div class="search-box">
              <i class="fas fa-search search-icon"></i>
              <input 
                v-model="searchQuery" 
                type="text" 
                placeholder="Search reports..." 
                class="search-input form-control"
              >
            </div>
            <select v-model="filterStatus" class="form-control">
              <option value="">All Status</option>
              <option value="completed">Completed</option>
              <option value="processing">Processing</option>
              <option value="failed">Failed</option>
              <option value="scheduled">Scheduled</option>
            </select>
          </div>
        </div>

        <div class="reports-table-container">
          <table class="reports-table">
            <thead>
              <tr>
                <th>
                  <input 
                    type="checkbox" 
                    v-model="selectAll" 
                    @change="toggleSelectAll"
                  >
                </th>
                <th @click="sortBy('name')">
                  Report Name
                  <i class="fas fa-sort sort-icon"></i>
                </th>
                <th @click="sortBy('category')">
                  Category
                  <i class="fas fa-sort sort-icon"></i>
                </th>
                <th @click="sortBy('status')">
                  Status
                  <i class="fas fa-sort sort-icon"></i>
                </th>
                <th @click="sortBy('created_at')">
                  Generated
                  <i class="fas fa-sort sort-icon"></i>
                </th>
                <th @click="sortBy('size')">
                  Size
                  <i class="fas fa-sort sort-icon"></i>
                </th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="report in filteredReports" 
                :key="report.id"
                class="report-row"
              >
                <td>
                  <input 
                    type="checkbox" 
                    v-model="selectedReports" 
                    :value="report.id"
                  >
                </td>
                <td>
                  <div class="report-name">
                    <i :class="getReportIcon(report.type)" class="report-icon"></i>
                    <div>
                      <strong>{{ report.name }}</strong>
                      <small>{{ report.description }}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="category-badge" :class="report.category.toLowerCase()">
                    {{ report.category }}
                  </span>
                </td>
                <td>
                  <span class="status-badge" :class="report.status">
                    <i :class="getStatusIcon(report.status)"></i>
                    {{ report.status }}
                  </span>
                </td>
                <td>
                  <div class="date-info">
                    <strong>{{ formatDate(report.created_at) }}</strong>
                    <small>{{ formatTime(report.created_at) }}</small>
                  </div>
                </td>
                <td>
                  <span class="file-size">{{ formatFileSize(report.size) }}</span>
                </td>
                <td>
                  <div class="action-buttons">
                    <button 
                      @click="viewReport(report)" 
                      class="btn btn-sm btn-info"
                      title="View Report"
                    >
                      <i class="fas fa-eye"></i>
                    </button>
                    <button 
                      @click="downloadReport(report)" 
                      class="btn btn-sm btn-success"
                      title="Download"
                      :disabled="report.status !== 'completed'"
                    >
                      <i class="fas fa-download"></i>
                    </button>
                    <button 
                      @click="shareReport(report)" 
                      class="btn btn-sm btn-primary"
                      title="Share"
                      :disabled="report.status !== 'completed'"
                    >
                      <i class="fas fa-share"></i>
                    </button>
                    <button 
                      @click="deleteReport(report)" 
                      class="btn btn-sm btn-danger"
                      title="Delete"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Empty State -->
          <div v-if="filteredReports.length === 0" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-chart-bar"></i>
            </div>
            <h3 class="empty-title">No Reports Found</h3>
            <p class="empty-message">
              {{ searchQuery ? 'No reports match your search criteria.' : 'Start by generating your first report from the categories above.' }}
            </p>
          </div>
        </div>

        <!-- Bulk Actions -->
        <div v-if="selectedReports.length > 0" class="bulk-actions">
          <div class="bulk-info">
            {{ selectedReports.length }} report(s) selected
          </div>
          <div class="bulk-buttons">
            <button @click="bulkDownload" class="btn btn-sm btn-success">
              <i class="fas fa-download"></i>
              Download Selected
            </button>
            <button @click="bulkDelete" class="btn btn-sm btn-danger">
              <i class="fas fa-trash"></i>
              Delete Selected
            </button>
          </div>
        </div>

        <!-- Pagination -->
        <div class="pagination-container">
          <div class="pagination-info">
            Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, totalReports) }} of {{ totalReports }} reports
          </div>
          <div class="pagination">
            <button 
              @click="currentPage--" 
              :disabled="currentPage === 1"
              class="page-btn"
            >
              <i class="fas fa-chevron-left"></i>
            </button>
            <button 
              v-for="page in visiblePages" 
              :key="page"
              @click="currentPage = page"
              :class="['page-btn', { active: page === currentPage }]"
            >
              {{ page }}
            </button>
            <button 
              @click="currentPage++" 
              :disabled="currentPage === totalPages"
              class="page-btn"
            >
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Report Generation Modal -->
    <div v-if="showGenerateModal" class="modal-overlay" @click="showGenerateModal = false">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">Generate Report</h3>
          <button @click="showGenerateModal = false" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="submitReportGeneration">
            <div class="form-group">
              <label class="form-label">Report Name</label>
              <input 
                v-model="reportForm.name" 
                type="text" 
                class="form-control" 
                required
              >
            </div>

            <div class="form-group">
              <label class="form-label">Category</label>
              <select v-model="reportForm.category" class="form-control" required>
                <option value="">Select Category</option>
                <option v-for="category in reportCategories" :key="category.id" :value="category.id">
                  {{ category.name }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Date Range</label>
              <div class="date-range">
                <input 
                  v-model="reportForm.startDate" 
                  type="date" 
                  class="form-control"
                  required
                >
                <span>to</span>
                <input 
                  v-model="reportForm.endDate" 
                  type="date" 
                  class="form-control"
                  required
                >
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Format</label>
              <div class="format-options">
                <label class="format-option">
                  <input type="radio" v-model="reportForm.format" value="pdf">
                  <i class="fas fa-file-pdf"></i>
                  PDF
                </label>
                <label class="format-option">
                  <input type="radio" v-model="reportForm.format" value="excel">
                  <i class="fas fa-file-excel"></i>
                  Excel
                </label>
                <label class="format-option">
                  <input type="radio" v-model="reportForm.format" value="csv">
                  <i class="fas fa-file-csv"></i>
                  CSV
                </label>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">
                <input type="checkbox" v-model="reportForm.includeCharts">
                Include Charts and Graphs
              </label>
            </div>

            <div class="form-group">
              <label class="form-label">Description (Optional)</label>
              <textarea 
                v-model="reportForm.description" 
                class="form-control" 
                rows="3"
                placeholder="Brief description of the report..."
              ></textarea>
            </div>

            <div class="modal-actions">
              <button type="button" @click="showGenerateModal = false" class="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-cog"></i>
                Generate Report
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Schedule Report Modal -->
    <div v-if="showScheduleModal" class="modal-overlay" @click="showScheduleModal = false">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">Schedule Report</h3>
          <button @click="showScheduleModal = false" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="submitScheduleReport">
            <div class="form-group">
              <label class="form-label">Report Template</label>
              <select v-model="scheduleForm.template" class="form-control" required>
                <option value="">Select Template</option>
                <option v-for="category in reportCategories" :key="category.id" :value="category.id">
                  {{ category.name }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Frequency</label>
              <select v-model="scheduleForm.frequency" class="form-control" required>
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="quarterly">Quarterly</option>
                <option value="yearly">Yearly</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Start Date</label>
              <input 
                v-model="scheduleForm.startDate" 
                type="datetime-local" 
                class="form-control"
                required
              >
            </div>

            <div class="form-group">
              <label class="form-label">Recipients</label>
              <textarea 
                v-model="scheduleForm.recipients" 
                class="form-control" 
                rows="3"
                placeholder="Enter email addresses separated by commas..."
                required
              ></textarea>
            </div>

            <div class="modal-actions">
              <button type="button" @click="showScheduleModal = false" class="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-calendar-plus"></i>
                Schedule Report
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Report View Modal -->
    <div v-if="showViewModal" class="modal-overlay" @click="showViewModal = false">
      <div class="modal large" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">{{ selectedReport?.name }}</h3>
          <button @click="showViewModal = false" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="selectedReport" class="report-preview">
            <div class="report-meta">
              <div class="meta-item">
                <strong>Category:</strong> {{ selectedReport.category }}
              </div>
              <div class="meta-item">
                <strong>Generated:</strong> {{ formatDateTime(selectedReport.created_at) }}
              </div>
              <div class="meta-item">
                <strong>Size:</strong> {{ formatFileSize(selectedReport.size) }}
              </div>
              <div class="meta-item">
                <strong>Status:</strong> 
                <span class="status-badge" :class="selectedReport.status">
                  {{ selectedReport.status }}
                </span>
              </div>
            </div>
            
            <div class="report-content">
              <div v-if="selectedReport.status === 'completed'" class="report-iframe">
                <iframe :src="selectedReport.preview_url" frameborder="0"></iframe>
              </div>
              <div v-else-if="selectedReport.status === 'processing'" class="processing-state">
                <div class="processing-spinner"></div>
                <h4>Report is being generated...</h4>
                <p>This may take a few minutes depending on the data size.</p>
              </div>
              <div v-else-if="selectedReport.status === 'failed'" class="error-state">
                <i class="fas fa-exclamation-triangle"></i>
                <h4>Report Generation Failed</h4>
                <p>{{ selectedReport.error_message || 'An error occurred while generating the report.' }}</p>
                <button @click="retryReport(selectedReport)" class="btn btn-primary">
                  <i class="fas fa-redo"></i>
                  Retry Generation
                </button>
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
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'

export default {
  name: 'AdminReports',
  setup() {
    const authStore = useAuthStore()
    
    // Reactive data
    const loading = ref(false)
    const showGenerateModal = ref(false)
    const showScheduleModal = ref(false)
    const showViewModal = ref(false)
    const showSuccessToast = ref(false)
    const successMessage = ref('')
    const selectedReport = ref(null)
    
    // Search and filters
    const searchQuery = ref('')
    const filterStatus = ref('')
    const sortField = ref('created_at')
    const sortDirection = ref('desc')
    
    // Selection
    const selectAll = ref(false)
    const selectedReports = ref([])
    
    // Pagination
    const currentPage = ref(1)
    const itemsPerPage = ref(10)
    const totalReports = ref(0)
    
    // Forms
    const reportForm = reactive({
      name: '',
      category: '',
      startDate: '',
      endDate: '',
      format: 'pdf',
      includeCharts: true,
      description: ''
    })
    
    const scheduleForm = reactive({
      template: '',
      frequency: 'monthly',
      startDate: '',
      recipients: ''
    })
    
    // Data
    const stats = ref({
      totalReports: 0,
      todayReports: 0,
      scheduledReports: 0,
      dataSources: 0,
      reportsGrowth: 0,
      todayGrowth: 0,
      nextScheduled: 0
    })
    
    const reportCategories = ref([])
    const reports = ref([])
    
    // Computed properties
    const filteredReports = computed(() => {
      let filtered = reports.value
      
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(report => 
          report.name.toLowerCase().includes(query) ||
          report.description.toLowerCase().includes(query) ||
          report.category.toLowerCase().includes(query)
        )
      }
      
      if (filterStatus.value) {
        filtered = filtered.filter(report => report.status === filterStatus.value)
      }
      
      // Sort
      filtered.sort((a, b) => {
        const aVal = a[sortField.value]
        const bVal = b[sortField.value]
        const modifier = sortDirection.value === 'asc' ? 1 : -1
        
        if (aVal < bVal) return -1 * modifier
        if (aVal > bVal) return 1 * modifier
        return 0
      })
      
      return filtered
    })
    
    const totalPages = computed(() => Math.ceil(totalReports.value / itemsPerPage.value))
    
    const visiblePages = computed(() => {
      const pages = []
      const start = Math.max(1, currentPage.value - 2)
      const end = Math.min(totalPages.value, currentPage.value + 2)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      return pages
    })
    
    // Methods
    const fetchStats = async () => {
      try {
        const response = await fetch('/api/admin/reports/stats', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          stats.value = data
        }
      } catch (error) {
        console.error('Error fetching stats:', error)
      }
    }
    
    const fetchReportCategories = async () => {
      try {
        const response = await fetch('/api/admin/reports/categories', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          reportCategories.value = data
        }
      } catch (error) {
        console.error('Error fetching categories:', error)
      }
    }
    
    const fetchReports = async () => {
      try {
        loading.value = true
        const params = new URLSearchParams({
          page: currentPage.value,
          limit: itemsPerPage.value,
          search: searchQuery.value,
          status: filterStatus.value,
          sort: sortField.value,
          direction: sortDirection.value
        })
        
        const response = await fetch(`/api/admin/reports?${params}`, {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          reports.value = data.reports
          totalReports.value = data.total
        }
      } catch (error) {
        console.error('Error fetching reports:', error)
      } finally {
        loading.value = false
      }
    }
    
    const refreshReports = async () => {
      await Promise.all([
        fetchStats(),
        fetchReportCategories(),
        fetchReports()
      ])
      showToast('Reports refreshed successfully!')
    }
    
    const selectCategory = (category) => {
      reportForm.category = category.id
      showGenerateModal.value = true
    }
    
    const generateReport = async (category) => {
      try {
        const response = await fetch('/api/admin/reports/generate', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            category_id: category.id,
            name: `${category.name} Report - ${new Date().toLocaleDateString()}`,
            format: 'pdf',
            include_charts: true
          })
        })
        
        if (response.ok) {
          showToast('Report generation started!')
          await fetchReports()
        }
      } catch (error) {
        console.error('Error generating report:', error)
      }
    }
    
    const submitReportGeneration = async () => {
      try {
        const response = await fetch('/api/admin/reports/generate', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(reportForm)
        })
        
        if (response.ok) {
          showGenerateModal.value = false
          showToast('Report generation started!')
          await fetchReports()
          resetReportForm()
        }
      } catch (error) {
        console.error('Error generating report:', error)
      }
    }
    
    const submitScheduleReport = async () => {
      try {
        const response = await fetch('/api/admin/reports/schedule', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(scheduleForm)
        })
        
        if (response.ok) {
          showScheduleModal.value = false
          showToast('Report scheduled successfully!')
          await fetchStats()
          resetScheduleForm()
        }
      } catch (error) {
        console.error('Error scheduling report:', error)
      }
    }
    
    const viewReport = (report) => {
      selectedReport.value = report
      showViewModal.value = true
    }
    
    const downloadReport = async (report) => {
      try {
        const response = await fetch(`/api/admin/reports/${report.id}/download`, {
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          }
        })
        
        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = `${report.name}.${report.format}`
          a.click()
          window.URL.revokeObjectURL(url)
        }
      } catch (error) {
        console.error('Error downloading report:', error)
      }
    }
    
    const shareReport = async (report) => {
      try {
        const response = await fetch(`/api/admin/reports/${report.id}/share`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          navigator.clipboard.writeText(data.share_url)
          showToast('Share link copied to clipboard!')
        }
      } catch (error) {
        console.error('Error sharing report:', error)
      }
    }
    
    const deleteReport = async (report) => {
      if (confirm(`Are you sure you want to delete "${report.name}"?`)) {
        try {
          const response = await fetch(`/api/admin/reports/${report.id}`, {
            method: 'DELETE',
            headers: {
              'Authorization': `Bearer ${authStore.token}`
            }
          })
          
          if (response.ok) {
            showToast('Report deleted successfully!')
            await fetchReports()
          }
        } catch (error) {
          console.error('Error deleting report:', error)
        }
      }
    }
    
    const retryReport = async (report) => {
      try {
        const response = await fetch(`/api/admin/reports/${report.id}/retry`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          }
        })
        
        if (response.ok) {
          showToast('Report generation restarted!')
          await fetchReports()
        }
      } catch (error) {
        console.error('Error retrying report:', error)
      }
    }
    
    const exportAllReports = async () => {
      try {
        const response = await fetch('/api/admin/reports/export-all', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          }
        })
        
        if (response.ok) {
          showToast('Export started! You will receive an email when ready.')
        }
      } catch (error) {
        console.error('Error exporting reports:', error)
      }
    }
    
    const bulkDownload = async () => {
      try {
        const response = await fetch('/api/admin/reports/bulk-download', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ report_ids: selectedReports.value })
        })
        
        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = 'reports.zip'
          a.click()
          window.URL.revokeObjectURL(url)
          selectedReports.value = []
        }
      } catch (error) {
        console.error('Error bulk downloading:', error)
      }
    }
    
    const bulkDelete = async () => {
      if (confirm(`Are you sure you want to delete ${selectedReports.value.length} reports?`)) {
        try {
          const response = await fetch('/api/admin/reports/bulk-delete', {
            method: 'DELETE',
            headers: {
              'Authorization': `Bearer ${authStore.token}`,
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({ report_ids: selectedReports.value })
          })
          
          if (response.ok) {
            showToast('Reports deleted successfully!')
            selectedReports.value = []
            await fetchReports()
          }
        } catch (error) {
          console.error('Error bulk deleting:', error)
        }
      }
    }
    
    const sortBy = (field) => {
      if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
      } else {
        sortField.value = field
        sortDirection.value = 'asc'
      }
    }
    
    const toggleSelectAll = () => {
      if (selectAll.value) {
        selectedReports.value = filteredReports.value.map(report => report.id)
      } else {
        selectedReports.value = []
      }
    }
    
    const resetReportForm = () => {
      Object.assign(reportForm, {
        name: '',
        category: '',
        startDate: '',
        endDate: '',
        format: 'pdf',
        includeCharts: true,
        description: ''
      })
    }
    
    const resetScheduleForm = () => {
      Object.assign(scheduleForm, {
        template: '',
        frequency: 'monthly',
        startDate: '',
        recipients: ''
      })
    }
    
    const showToast = (message) => {
      successMessage.value = message
      showSuccessToast.value = true
      setTimeout(() => {
        showSuccessToast.value = false
      }, 3000)
    }
    
    // Utility functions
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString()
    }
    
    const formatTime = (date) => {
      return new Date(date).toLocaleTimeString()
    }
    
    const formatDateTime = (date) => {
      return new Date(date).toLocaleString()
    }
    
    const formatFileSize = (bytes) => {
      if (bytes === 0) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    }
    
    const getReportIcon = (type) => {
      const icons = {
        'members': 'fas fa-users',
        'financial': 'fas fa-chart-line',
        'events': 'fas fa-calendar',
        'donations': 'fas fa-heart',
        'complaints': 'fas fa-exclamation-triangle',
        'activities': 'fas fa-list'
      }
      return icons[type] || 'fas fa-file-alt'
    }
    
    const getStatusIcon = (status) => {
      const icons = {
        'completed': 'fas fa-check-circle',
        'processing': 'fas fa-spinner fa-spin',
        'failed': 'fas fa-exclamation-circle',
        'scheduled': 'fas fa-clock'
      }
      return icons[status] || 'fas fa-question-circle'
    }
    
    // Watchers
    watch([searchQuery, filterStatus, currentPage], () => {
      fetchReports()
    })
    
    watch(selectedReports, (newVal) => {
      selectAll.value = newVal.length === filteredReports.value.length && newVal.length > 0
    })
    
    // Lifecycle
    onMounted(async () => {
      await refreshReports()
    })
    
    return {
      // Reactive data
      loading,
      showGenerateModal,
      showScheduleModal,
      showViewModal,
      showSuccessToast,
      successMessage,
      selectedReport,
      
      // Search and filters
      searchQuery,
      filterStatus,
      sortField,
      sortDirection,
      
      // Selection
      selectAll,
      selectedReports,
      
      // Pagination
      currentPage,
      itemsPerPage,
      totalReports,
      totalPages,
      visiblePages,
      
      // Forms
      reportForm,
      scheduleForm,
      
      // Data
      stats,
      reportCategories,
      reports,
      filteredReports,
      
      // Methods
      refreshReports,
      selectCategory,
      generateReport,
      submitReportGeneration,
      submitScheduleReport,
      viewReport,
      downloadReport,
      shareReport,
      deleteReport,
      retryReport,
      exportAllReports,
      bulkDownload,
      bulkDelete,
      sortBy,
      toggleSelectAll,
      resetReportForm,
      resetScheduleForm,
      showToast,
      
      // Utility functions
      formatDate,
      formatTime,
      formatDateTime,
      formatFileSize,
      getReportIcon,
      getStatusIcon
    }
  }
}
</script>

<style scoped>
/* Admin Reports Page Styles */
.admin-reports {
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
.reports-header {
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
.reports-content {
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

.stat-card.success {
  border-left-color: #28a745;
}

.stat-card.warning {
  border-left-color: #ffc107;
}

.stat-card.info {
  border-left-color: #17a2b8;
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

.stat-icon.success {
  background: linear-gradient(135deg, #28a745, #20c997);
}

.stat-icon.warning {
  background: linear-gradient(135deg, #ffc107, #ff8c00);
}

.stat-icon.info {
  background: linear-gradient(135deg, #17a2b8, #20c997);
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
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.stat-change.positive {
  color: #28a745;
}

/* Report Categories */
.report-categories {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
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
  margin: 0;
}

.section-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.category-card {
  border: 2px solid #e9ecef;
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.3s ease;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.category-card:hover {
  border-color: #667eea;
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
}

.category-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
  flex-shrink: 0;
}

.category-icon.primary {
  background: linear-gradient(135deg, #667eea, #764ba2);
}

.category-icon.success {
  background: linear-gradient(135deg, #28a745, #20c997);
}

.category-icon.warning {
  background: linear-gradient(135deg, #ffc107, #ff8c00);
}

.category-icon.info {
  background: linear-gradient(135deg, #17a2b8, #20c997);
}

.category-icon.danger {
  background: linear-gradient(135deg, #dc3545, #e83e8c);
}

.category-info {
  flex: 1;
}

.category-info h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #2c3e50;
}

.category-info p {
  margin: 0 0 0.75rem 0;
  color: #6c757d;
  font-size: 0.9rem;
  line-height: 1.4;
}

.category-stats {
  display: flex;
  gap: 1rem;
  font-size: 0.8rem;
  color: #6c757d;
}

.category-actions {
  flex-shrink: 0;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.8rem;
}

/* Recent Reports */
.recent-reports {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  overflow: hidden;
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

/* Reports Table */
.reports-table-container {
  overflow-x: auto;
}

.reports-table {
  width: 100%;
  border-collapse: collapse;
}

.reports-table th,
.reports-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #e9ecef;
}

.reports-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #495057;
  cursor: pointer;
  user-select: none;
  position: relative;
}

.reports-table th:hover {
  background: #e9ecef;
}

.sort-icon {
  margin-left: 0.5rem;
  opacity: 0.5;
}

.report-row {
  transition: all 0.3s ease;
}

.report-row:hover {
  background: #f8f9fa;
}

.report-name {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.report-icon {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  background: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6c757d;
  flex-shrink: 0;
}

.report-name div {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.report-name strong {
  color: #2c3e50;
  font-weight: 600;
}

.report-name small {
  color: #6c757d;
  font-size: 0.8rem;
}

.category-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.category-badge.members {
  background: #e3f2fd;
  color: #1976d2;
}

.category-badge.financial {
  background: #e8f5e8;
  color: #2e7d32;
}

.category-badge.events {
  background: #fff3e0;
  color: #f57c00;
}

.category-badge.donations {
  background: #fce4ec;
  color: #ad1457;
}

.category-badge.complaints {
  background: #ffebee;
  color: #c62828;
}

.category-badge.activities {
  background: #f3e5f5;
  color: #7b1fa2;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge.completed {
  background: #e8f5e8;
  color: #2e7d32;
}

.status-badge.processing {
  background: #fff3e0;
  color: #f57c00;
}

.status-badge.failed {
  background: #ffebee;
  color: #c62828;
}

.status-badge.scheduled {
  background: #e3f2fd;
  color: #1976d2;
}

.date-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.date-info strong {
  color: #2c3e50;
  font-weight: 600;
}

.date-info small {
  color: #6c757d;
  font-size: 0.8rem;
}

.file-size {
  font-weight: 500;
  color: #6c757d;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
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

.btn-success {
  background: #28a745;
  color: white;
  border: 2px solid #28a745;
}

.btn-success:hover {
  background: #218838;
  border-color: #218838;
  transform: translateY(-1px);
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

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
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

.modal.large {
  max-width: 900px;
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

.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.date-range {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.date-range span {
  color: #6c757d;
  font-weight: 500;
}

.format-options {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.format-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 500;
}

.format-option:hover {
  border-color: #667eea;
}

.format-option input[type="radio"] {
  margin: 0;
}

.format-option input[type="radio"]:checked + i {
  color: #667eea;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
  padding-top: 1rem;
  border-top: 1px solid #e9ecef;
}

/* Report Preview */
.report-preview {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.report-meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.meta-item strong {
  color: #495057;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.report-content {
  min-height: 400px;
}

.report-iframe {
  width: 100%;
  height: 500px;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  overflow: hidden;
}

.report-iframe iframe {
  width: 100%;
  height: 100%;
}

.processing-state,
.error-state {
  text-align: center;
  padding: 3rem 2rem;
}

.processing-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

.processing-state h4,
.error-state h4 {
  color: #495057;
  margin-bottom: 0.5rem;
}

.processing-state p,
.error-state p {
  color: #6c757d;
  margin-bottom: 1.5rem;
}

.error-state i {
  font-size: 3rem;
  color: #dc3545;
  margin-bottom: 1rem;
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
  display: flex;
  align-items: center;
  gap: 0.5rem;
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
  .reports-header {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    align-items: stretch;
  }
  
  .header-actions {
    justify-content: center;
  }
  
  .reports-content {
    padding: 1rem;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .categories-grid {
    grid-template-columns: 1fr;
  }
  
  .category-card {
    flex-direction: column;
    text-align: center;
  }
  
  .section-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .section-actions {
    flex-direction: column;
  }
  
  .reports-table-container {
    font-size: 0.8rem;
  }
  
  .reports-table th,
  .reports-table td {
    padding: 0.5rem;
  }
  
  .action-buttons {
    flex-direction: column;
  }
  
  .bulk-actions {
    flex-direction: column;
    align-items: stretch;
  }
  
  .pagination-container {
    flex-direction: column;
    text-align: center;
  }
  
  .modal {
    margin: 1rem;
    max-width: none;
  }
  
  .date-range {
    flex-direction: column;
    align-items: stretch;
  }
  
  .format-options {
    flex-direction: column;
  }
  
  .modal-actions {
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .header-title h1 {
    font-size: 1.5rem;
  }
  
  .header-title .icon {
    font-size: 2rem;
  }
  
  .btn {
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
  }
  
  .stat-value {
    font-size: 1.5rem;
  }
  
  .section-title {
    font-size: 1.1rem;
  }
  
  .success-toast {
    top: 1rem;
    right: 1rem;
    left: 1rem;
    font-size: 0.9rem;
  }
}

/* Print Styles */
@media print {
  .admin-reports {
    background: white !important;
  }
  
  .reports-header {
    background: white !important;
    color: black !important;
    box-shadow: none !important;
  }
  
  .header-actions,
  .action-buttons,
  .bulk-actions,
  .pagination-container {
    display: none !important;
  }
  
  .reports-table {
    font-size: 0.8rem;
  }
  
  .btn {
    display: none !important;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .admin-reports {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
  }
  
  .stat-card,
  .report-categories,
  .recent-reports,
  .modal {
    background: #34495e;
    color: #ecf0f1;
  }
  
  .form-control {
    background: #2c3e50;
    border-color: #4a5568;
    color: #ecf0f1;
  }
  
  .form-control:focus {
    border-color: #667eea;
  }
  
  .reports-table th {
    background: #2c3e50;
    color: #ecf0f1;
  }
  
  .report-row:hover {
    background: #2c3e50;
  }
  
  .bulk-actions {
    background: #2c3e50;
    border-color: #4a5568;
  }
  
  .pagination-container {
    border-color: #4a5568;
  }
  
  .page-btn {
    background: #2c3e50;
    border-color: #4a5568;
    color: #ecf0f1;
  }
  
  .page-btn:hover {
    background: #4a5568;
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
.page-btn:focus,
.category-card:focus {
  outline: 2px solid #667eea;
  outline-offset: 2px;
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .btn {
    border-width: 3px;
  }
  
  .stat-card,
  .category-card,
  .modal {
    border: 2px solid #000;
  }
  
  .status-badge,
  .category-badge {
    border: 1px solid #000;
  }
}

/* Custom Scrollbar */
.reports-table-container::-webkit-scrollbar {
  height: 8px;
}

.reports-table-container::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.reports-table-container::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

.reports-table-container::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

.modal::-webkit-scrollbar {
  width: 8px;
}

.modal::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.modal::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

.modal::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>