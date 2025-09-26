<template>
  <div class="enquiries-page">
    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
      <p>Loading enquiries...</p>
    </div>

    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="fas fa-envelope"></i>
            Enquiries Management
          </h1>
          <p class="page-subtitle">Manage and respond to general enquiries</p>
        </div>
        <div class="header-actions">
          <button @click="refreshData" class="btn btn-secondary" :disabled="loading">
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
            Refresh
          </button>
          <button @click="exportEnquiries" class="btn btn-primary">
            <i class="fas fa-download"></i>
            Export
          </button>
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon total">
          <i class="fas fa-envelope"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.total || 0 }}</h3>
          <p>Total Enquiries</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon new">
          <i class="fas fa-envelope-open"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.new || 0 }}</h3>
          <p>New</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon in-progress">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.in_progress || 0 }}</h3>
          <p>In Progress</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon resolved">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.resolved || 0 }}</h3>
          <p>Resolved</p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
      <div class="filters-row">
        <div class="filter-group">
          <label>Status:</label>
          <select v-model="filters.status" @change="fetchEnquiries">
            <option value="">All Status</option>
            <option value="new">New</option>
            <option value="in_progress">In Progress</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Priority:</label>
          <select v-model="filters.priority" @change="fetchEnquiries">
            <option value="">All Priorities</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Sort by:</label>
          <select v-model="filters.sortBy" @change="fetchEnquiries">
            <option value="created_at">Date Created</option>
            <option value="updated_at">Last Updated</option>
            <option value="name">Name</option>
            <option value="subject">Subject</option>
            <option value="priority">Priority</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Order:</label>
          <select v-model="filters.order" @change="fetchEnquiries">
            <option value="desc">Descending</option>
            <option value="asc">Ascending</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Search -->
    <div class="search-section">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input
          type="text"
          v-model="searchQuery"
          @input="debounceSearch"
          placeholder="Search by name, email, subject, or message..."
          class="search-input"
        />
        <button v-if="searchQuery" @click="clearSearch" class="clear-search">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>

    <!-- Bulk Actions -->
    <div v-if="selectedEnquiries.length > 0" class="bulk-actions">
      <div class="bulk-info">
        <span>{{ selectedEnquiries.length }} enquiry(ies) selected</span>
      </div>
      <div class="bulk-buttons">
        <button @click="bulkUpdateStatus('in_progress')" class="btn btn-warning">
          <i class="fas fa-clock"></i>
          Mark In Progress
        </button>
        <button @click="bulkUpdateStatus('resolved')" class="btn btn-success">
          <i class="fas fa-check"></i>
          Mark Resolved
        </button>
        <button @click="bulkDelete" class="btn btn-danger">
          <i class="fas fa-trash"></i>
          Delete
        </button>
      </div>
    </div>

    <!-- Enquiries List -->
    <div class="enquiries-list">
      <div v-if="enquiries.length === 0 && !loading" class="empty-state">
        <i class="fas fa-envelope-open-text"></i>
        <h3>No enquiries found</h3>
        <p>{{ searchQuery ? 'Try adjusting your search criteria' : 'No enquiries have been submitted yet' }}</p>
      </div>

      <div v-else class="enquiries-grid">
        <div
          v-for="enquiry in enquiries"
          :key="enquiry.id"
          class="enquiry-card"
          :class="{ 'selected': selectedEnquiries.includes(enquiry.id) }"
        >
          <div class="card-header">
            <div class="card-select">
              <input
                type="checkbox"
                :value="enquiry.id"
                v-model="selectedEnquiries"
                class="enquiry-checkbox"
              />
            </div>
            <div class="enquiry-status">
              <span class="status-badge" :class="enquiry.status">
                {{ formatStatus(enquiry.status) }}
              </span>
              <span class="priority-badge" :class="enquiry.priority">
                {{ formatPriority(enquiry.priority) }}
              </span>
            </div>
            <div class="card-actions">
              <button @click="viewEnquiry(enquiry)" class="btn-icon" title="View Details">
                <i class="fas fa-eye"></i>
              </button>
              <button @click="editEnquiry(enquiry)" class="btn-icon" title="Edit">
                <i class="fas fa-edit"></i>
              </button>
              <div class="dropdown">
                <button class="btn-icon dropdown-toggle" title="More Actions">
                  <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="dropdown-menu">
                  <button @click="updateStatus(enquiry, 'in_progress')" v-if="enquiry.status === 'new'">
                    <i class="fas fa-clock"></i> Mark In Progress
                  </button>
                  <button @click="updateStatus(enquiry, 'resolved')" v-if="enquiry.status !== 'resolved'">
                    <i class="fas fa-check"></i> Mark Resolved
                  </button>
                  <button @click="updateStatus(enquiry, 'closed')" v-if="enquiry.status === 'resolved'">
                    <i class="fas fa-archive"></i> Close
                  </button>
                  <button @click="replyToEnquiry(enquiry)">
                    <i class="fas fa-reply"></i> Reply
                  </button>
                  <button @click="deleteEnquiry(enquiry.id)" class="danger">
                    <i class="fas fa-trash"></i> Delete
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="card-content">
            <div class="enquiry-info">
              <h4 class="enquiry-subject">{{ enquiry.subject }}</h4>
              <div class="enquiry-meta">
                <span class="enquiry-name">
                  <i class="fas fa-user"></i>
                  {{ enquiry.name }}
                </span>
                <span class="enquiry-email">
                  <i class="fas fa-envelope"></i>
                  {{ enquiry.email }}
                </span>
                <span class="enquiry-phone" v-if="enquiry.phone">
                  <i class="fas fa-phone"></i>
                  {{ enquiry.phone }}
                </span>
              </div>
              <p class="enquiry-message">{{ truncateText(enquiry.message, 150) }}</p>
            </div>
          </div>

          <div class="card-footer">
            <div class="enquiry-dates">
              <span class="created-date">
                <i class="fas fa-calendar-plus"></i>
                Created: {{ formatDate(enquiry.created_at) }}
              </span>
              <span class="updated-date" v-if="enquiry.updated_at !== enquiry.created_at">
                <i class="fas fa-calendar-edit"></i>
                Updated: {{ formatDate(enquiry.updated_at) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="pagination">
      <button
        @click="changePage(currentPage - 1)"
        :disabled="currentPage === 1"
        class="btn btn-secondary"
      >
        <i class="fas fa-chevron-left"></i>
        Previous
      </button>
      
      <div class="page-numbers">
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="changePage(page)"
          :class="['btn', page === currentPage ? 'btn-primary' : 'btn-secondary']"
        >
          {{ page }}
        </button>
      </div>
      
      <button
        @click="changePage(currentPage + 1)"
        :disabled="currentPage === totalPages"
        class="btn btn-secondary"
      >
        Next
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>

    <!-- View Enquiry Modal -->
    <div v-if="showViewModal" class="modal-overlay" @click="closeViewModal">
      <div class="modal-content large" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-envelope-open"></i>
            Enquiry Details
          </h3>
          <button @click="closeViewModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body" v-if="selectedEnquiry">
          <div class="enquiry-details">
            <div class="detail-section">
              <h4>Contact Information</h4>
              <div class="detail-grid">
                <div class="detail-item">
                  <label>Name:</label>
                  <span>{{ selectedEnquiry.name }}</span>
                </div>
                <div class="detail-item">
                  <label>Email:</label>
                  <span>{{ selectedEnquiry.email }}</span>
                </div>
                <div class="detail-item" v-if="selectedEnquiry.phone">
                  <label>Phone:</label>
                  <span>{{ selectedEnquiry.phone }}</span>
                </div>
                <div class="detail-item">
                  <label>Subject:</label>
                  <span>{{ selectedEnquiry.subject }}</span>
                </div>
              </div>
            </div>

            <div class="detail-section">
              <h4>Status & Priority</h4>
              <div class="detail-grid">
                <div class="detail-item">
                  <label>Status:</label>
                  <span class="status-badge" :class="selectedEnquiry.status">
                    {{ formatStatus(selectedEnquiry.status) }}
                  </span>
                </div>
                <div class="detail-item">
                  <label>Priority:</label>
                  <span class="priority-badge" :class="selectedEnquiry.priority">
                    {{ formatPriority(selectedEnquiry.priority) }}
                  </span>
                </div>
              </div>
            </div>

            <div class="detail-section">
              <h4>Message</h4>
              <div class="message-content">
                {{ selectedEnquiry.message }}
              </div>
            </div>

            <div class="detail-section">
              <h4>Timestamps</h4>
              <div class="detail-grid">
                <div class="detail-item">
                  <label>Created:</label>
                  <span>{{ formatDate(selectedEnquiry.created_at) }}</span>
                </div>
                <div class="detail-item">
                  <label>Last Updated:</label>
                  <span>{{ formatDate(selectedEnquiry.updated_at) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="replyToEnquiry(selectedEnquiry)" class="btn btn-primary">
            <i class="fas fa-reply"></i>
            Reply
          </button>
          <button @click="editEnquiry(selectedEnquiry)" class="btn btn-secondary">
            <i class="fas fa-edit"></i>
            Edit
          </button>
          <button @click="closeViewModal" class="btn btn-secondary">
            Close
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Enquiry Modal -->
    <div v-if="showEditModal" class="modal-overlay" @click="closeEditModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>
            <i class="fas fa-edit"></i>
            Edit Enquiry
          </h3>
          <button @click="closeEditModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveEnquiry">
            <div class="form-row">
              <div class="form-group">
                <label for="edit-status">Status *</label>
                <select id="edit-status" v-model="editForm.status" required>
                  <option value="new">New</option>
                  <option value="in_progress">In Progress</option>
                  <option value="resolved">Resolved</option>
                  <option value="closed">Closed</option>
                </select>
              </div>
              <div class="form-group">
                <label for="edit-priority">Priority *</label>
                <select id="edit-priority" v-model="editForm.priority" required>
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label for="edit-notes">Internal Notes</label>
              <textarea
                id="edit-notes"
                v-model="editForm.notes"
                rows="4"
                placeholder="Add internal notes about this enquiry..."
              ></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button @click="saveEnquiry" class="btn btn-primary" :disabled="saving">
            <i class="fas fa-save"></i>
            {{ saving ? 'Saving...' : 'Save Changes' }}
          </button>
          <button @click="closeEditModal" class="btn btn-secondary">
            Cancel
          </button>
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
import { ref, reactive, onMounted, computed } from 'vue'
import { api } from '@/utils/api'

export default {
  name: 'Enquiries',
  setup() {
    // Reactive data
    const loading = ref(false)
    const saving = ref(false)
    const enquiries = ref([])
    const stats = reactive({
      total: 0,
      new: 0,
      in_progress: 0,
      resolved: 0
    })
    
    const filters = reactive({
      status: '',
      priority: '',
      sortBy: 'created_at',
      order: 'desc'
    })
    
    const searchQuery = ref('')
    const selectedEnquiries = ref([])
    const currentPage = ref(1)
    const totalPages = ref(1)
    const totalEnquiries = ref(0)
    const perPage = ref(12)
    
    // Modals
    const showViewModal = ref(false)
    const showEditModal = ref(false)
    const selectedEnquiry = ref(null)
    
    // Edit form
    const editForm = reactive({
      id: null,
      status: '',
      priority: '',
      notes: ''
    })
    
    // Toast
    const showToast = ref(false)
    const toastMessage = ref('')
    
    // Search debounce
    let searchTimeout = null
    
    // Computed properties
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
        const response = await api.get('admin/enquiries/stats')
        Object.assign(stats, response.data.stats)
      } catch (error) {
        console.error('Error fetching stats:', error)
      }
    }
    
    const fetchEnquiries = async () => {
      loading.value = true
      try {
        const params = {
          page: currentPage.value,
          per_page: perPage.value,
          search: searchQuery.value,
          status: filters.status,
          priority: filters.priority,
          sort_by: filters.sortBy,
          order: filters.order
        }
        
        const response = await api.get('admin/enquiries/enquiries', { params })
        enquiries.value = response.data.enquiries || []
        totalEnquiries.value = response.data.total || 0
        totalPages.value = Math.ceil(totalEnquiries.value / perPage.value)
      } catch (error) {
        console.error('Error fetching enquiries:', error)
        enquiries.value = []
      } finally {
        loading.value = false
      }
    }
    
    const refreshData = async () => {
      await Promise.all([fetchStats(), fetchEnquiries()])
      showToastMessage('Data refreshed successfully')
    }
    
    const debounceSearch = () => {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        currentPage.value = 1
        fetchEnquiries()
      }, 500)
    }
    
    const clearSearch = () => {
      searchQuery.value = ''
      currentPage.value = 1
      fetchEnquiries()
    }
    
    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
        fetchEnquiries()
      }
    }
    
    const viewEnquiry = (enquiry) => {
      selectedEnquiry.value = enquiry
      showViewModal.value = true
    }
    
    const editEnquiry = (enquiry) => {
      selectedEnquiry.value = enquiry
      editForm.id = enquiry.id
      editForm.status = enquiry.status
      editForm.priority = enquiry.priority
      editForm.notes = enquiry.notes || ''
      showEditModal.value = true
    }
    
    const saveEnquiry = async () => {
      saving.value = true
      try {
        const response = await api.put('admin/enquiries/enquiries', editForm)
        
        await fetchEnquiries()
        await fetchStats()
        closeEditModal()
        showToastMessage('Enquiry updated successfully')
      } catch (error) {
        console.error('Error saving enquiry:', error)
      } finally {
        saving.value = false
      }
    }
    
    const updateStatus = async (enquiry, status) => {
      try {
        const response = await api.put('admin/enquiries/enquiries', {
          id: enquiry.id,
          status: status
        })
        
        await fetchEnquiries()
        await fetchStats()
        showToastMessage(`Enquiry marked as ${formatStatus(status)}`)
      } catch (error) {
        console.error('Error updating status:', error)
      }
    }
    
    const bulkUpdateStatus = async (status) => {
      if (selectedEnquiries.value.length === 0) return
      
      try {
        const response = await api.put('admin/enquiries/enquiries', {
          ids: selectedEnquiries.value,
          status: status
        })
        
        selectedEnquiries.value = []
        await fetchEnquiries()
        await fetchStats()
        showToastMessage(`${response.data.updated} enquiry(ies) updated successfully`)
      } catch (error) {
        console.error('Error bulk updating:', error)
      }
    }
    
    const deleteEnquiry = async (id) => {
      if (!confirm('Are you sure you want to delete this enquiry?')) return
      
      try {
        const response = await api.delete('admin/enquiries/enquiries', { data: { id } })
        
        await fetchEnquiries()
        await fetchStats()
        showToastMessage('Enquiry deleted successfully')
      } catch (error) {
        console.error('Error deleting enquiry:', error)
      }
    }
    
    const bulkDelete = async () => {
      if (selectedEnquiries.value.length === 0) return
      if (!confirm(`Are you sure you want to delete ${selectedEnquiries.value.length} enquiry(ies)?`)) return
      
      try {
        const response = await api.delete('admin/enquiries/enquiries', { data: { ids: selectedEnquiries.value } })
        
        selectedEnquiries.value = []
        await fetchEnquiries()
        await fetchStats()
        showToastMessage(`${response.data.deleted} enquiry(ies) deleted successfully`)
      } catch (error) {
        console.error('Error bulk deleting:', error)
      }
    }
    
    const replyToEnquiry = (enquiry) => {
      // Open email client with pre-filled data
      const subject = `Re: ${enquiry.subject}`
      const body = `Dear ${enquiry.name},\n\nThank you for your enquiry.\n\n\n\nBest regards,\nBHRC Team`
      const mailtoLink = `mailto:${enquiry.email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`
      window.open(mailtoLink)
    }
    
    const exportEnquiries = async () => {
      try {
        const params = {
          export: 'csv',
          status: filters.status,
          priority: filters.priority,
          search: searchQuery.value
        }
        
        const response = await api.get('admin/enquiries/export', { 
          params,
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(response.data)
        const a = document.createElement('a')
        a.href = url
        a.download = `enquiries-${new Date().toISOString().split('T')[0]}.csv`
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        window.URL.revokeObjectURL(url)
        
        showToastMessage('Enquiries exported successfully')
      } catch (error) {
        console.error('Error exporting enquiries:', error)
      }
    }
    
    const closeViewModal = () => {
      showViewModal.value = false
      selectedEnquiry.value = null
    }
    
    const closeEditModal = () => {
      showEditModal.value = false
      selectedEnquiry.value = null
      Object.assign(editForm, {
        id: null,
        status: '',
        priority: '',
        notes: ''
      })
    }
    
    const showToastMessage = (message) => {
      toastMessage.value = message
      showToast.value = true
      setTimeout(() => {
        showToast.value = false
      }, 3000)
    }
    
    // Utility functions
    const formatStatus = (status) => {
      const statusMap = {
        new: 'New',
        in_progress: 'In Progress',
        resolved: 'Resolved',
        closed: 'Closed'
      }
      return statusMap[status] || status
    }
    
    const formatPriority = (priority) => {
      const priorityMap = {
        low: 'Low',
        medium: 'Medium',
        high: 'High',
        urgent: 'Urgent'
      }
      return priorityMap[priority] || priority
    }
    
    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }
    
    const truncateText = (text, length) => {
      if (!text) return ''
      return text.length > length ? text.substring(0, length) + '...' : text
    }
    
    // Lifecycle
    onMounted(() => {
      fetchStats()
      fetchEnquiries()
    })
    
    return {
      // Data
      loading,
      saving,
      enquiries,
      stats,
      filters,
      searchQuery,
      selectedEnquiries,
      currentPage,
      totalPages,
      totalEnquiries,
      perPage,
      showViewModal,
      showEditModal,
      selectedEnquiry,
      editForm,
      showToast,
      toastMessage,
      
      // Computed
      visiblePages,
      
      // Methods
      fetchStats,
      fetchEnquiries,
      refreshData,
      debounceSearch,
      clearSearch,
      changePage,
      viewEnquiry,
      editEnquiry,
      saveEnquiry,
      updateStatus,
      bulkUpdateStatus,
      deleteEnquiry,
      bulkDelete,
      replyToEnquiry,
      exportEnquiries,
      closeViewModal,
      closeEditModal,
      showToastMessage,
      formatStatus,
      formatPriority,
      formatDate,
      truncateText
    }
  }
}
</script>

<style scoped>
.enquiries-page {
  padding: 2rem;
  background-color: #f8fafc;
  min-height: 100vh;
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
  z-index: 1000;
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #e2e8f0;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Page Header */
.page-header {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left h1 {
  margin: 0;
  color: #1e293b;
  font-size: 2rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.header-left h1 i {
  color: #3b82f6;
}

.page-subtitle {
  margin: 0.5rem 0 0 0;
  color: #64748b;
  font-size: 1.1rem;
}

.header-actions {
  display: flex;
  gap: 1rem;
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
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
}

.stat-icon.total { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.stat-icon.new { background: linear-gradient(135deg, #10b981, #059669); }
.stat-icon.in-progress { background: linear-gradient(135deg, #f59e0b, #d97706); }
.stat-icon.resolved { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

.stat-content h3 {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
}

.stat-content p {
  margin: 0.25rem 0 0 0;
  color: #64748b;
  font-weight: 500;
}

/* Filters */
.filters-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.filters-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group label {
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
}

.filter-group select {
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.875rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.filter-group select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Search */
.search-section {
  margin-bottom: 1.5rem;
}

.search-box {
  position: relative;
  max-width: 500px;
}

.search-box i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.clear-search {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: color 0.2s;
}

.clear-search:hover {
  color: #6b7280;
}

/* Bulk Actions */
.bulk-actions {
  background: #fef3c7;
  border: 1px solid #fbbf24;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.bulk-info {
  font-weight: 600;
  color: #92400e;
}

.bulk-buttons {
  display: flex;
  gap: 0.5rem;
}

/* Buttons */
.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  font-size: 0.875rem;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
  transform: translateY(-1px);
}

.btn-secondary {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
}

.btn-secondary:hover:not(:disabled) {
  background: #e2e8f0;
}

.btn-success {
  background: #10b981;
  color: white;
}

.btn-success:hover:not(:disabled) {
  background: #059669;
}

.btn-warning {
  background: #f59e0b;
  color: white;
}

.btn-warning:hover:not(:disabled) {
  background: #d97706;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626;
}

.btn-icon {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 6px;
  background: #f8fafc;
  color: #64748b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #e2e8f0;
  color: #475569;
}

/* Enquiries List */
.enquiries-list {
  margin-bottom: 2rem;
}

.empty-state {
  background: white;
  border-radius: 12px;
  padding: 4rem 2rem;
  text-align: center;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.empty-state i {
  font-size: 4rem;
  color: #cbd5e1;
  margin-bottom: 1rem;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  color: #475569;
  font-size: 1.5rem;
}

.empty-state p {
  margin: 0;
  color: #64748b;
}

.enquiries-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
}

.enquiry-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.2s;
  border: 2px solid transparent;
}

.enquiry-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.enquiry-card.selected {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.card-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-select input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: #3b82f6;
}

.enquiry-status {
  display: flex;
  gap: 0.5rem;
}

.status-badge, .priority-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.new { background: #dbeafe; color: #1e40af; }
.status-badge.in_progress { background: #fef3c7; color: #92400e; }
.status-badge.resolved { background: #d1fae5; color: #065f46; }
.status-badge.closed { background: #f3f4f6; color: #374151; }

.priority-badge.low { background: #f0f9ff; color: #0369a1; }
.priority-badge.medium { background: #fef3c7; color: #92400e; }
.priority-badge.high { background: #fef2f2; color: #991b1b; }
.priority-badge.urgent { background: #fdf2f8; color: #be185d; }

.card-actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.dropdown {
  position: relative;
}

.dropdown-toggle {
  background: none !important;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  min-width: 180px;
  z-index: 100;
  display: none;
}

.dropdown:hover .dropdown-menu {
  display: block;
}

.dropdown-menu button {
  width: 100%;
  padding: 0.75rem 1rem;
  border: none;
  background: none;
  text-align: left;
  cursor: pointer;
  transition: background-color 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.dropdown-menu button:hover {
  background: #f8fafc;
}

.dropdown-menu button.danger {
  color: #ef4444;
}

.dropdown-menu button.danger:hover {
  background: #fef2f2;
}

.card-content {
  padding: 1.5rem;
}

.enquiry-subject {
  margin: 0 0 1rem 0;
  color: #1e293b;
  font-size: 1.125rem;
  font-weight: 600;
}

.enquiry-meta {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.enquiry-meta span {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
  font-size: 0.875rem;
}

.enquiry-meta i {
  width: 16px;
  color: #94a3b8;
}

.enquiry-message {
  color: #475569;
  line-height: 1.6;
  margin: 0;
}

.card-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #f1f5f9;
  background: #f8fafc;
  border-radius: 0 0 12px 12px;
}

.enquiry-dates {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.enquiry-dates span {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #64748b;
  font-size: 0.75rem;
}

.enquiry-dates i {
  width: 12px;
  color: #94a3b8;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  margin-top: 2rem;
}

.page-numbers {
  display: flex;
  gap: 0.25rem;
}

/* Modals */
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

.modal-content {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-content.large {
  max-width: 800px;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  color: #1e293b;
  font-size: 1.25rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #9ca3af;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: color 0.2s;
}

.modal-close:hover {
  color: #6b7280;
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

/* Enquiry Details */
.enquiry-details {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.detail-section h4 {
  margin: 0 0 1rem 0;
  color: #1e293b;
  font-size: 1.125rem;
  font-weight: 600;
  border-bottom: 2px solid #e5e7eb;
  padding-bottom: 0.5rem;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-item label {
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
}

.detail-item span {
  color: #1e293b;
}

.message-content {
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1rem;
  white-space: pre-wrap;
  line-height: 1.6;
  color: #374151;
}

/* Forms */
.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.875rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-group textarea {
  resize: vertical;
  min-height: 100px;
}

/* Toast */
.toast {
  position: fixed;
  top: 2rem;
  right: 2rem;
  background: #10b981;
  color: white;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  z-index: 1100;
  animation: slideIn 0.3s ease-out;
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
@media (max-width: 768px) {
  .enquiries-page {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .header-actions {
    justify-content: center;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .filters-row {
    grid-template-columns: 1fr;
  }
  
  .enquiries-grid {
    grid-template-columns: 1fr;
  }
  
  .bulk-actions {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .bulk-buttons {
    justify-content: center;
  }
  
  .pagination {
    flex-wrap: wrap;
  }
  
  .modal-content {
    margin: 1rem;
    max-width: calc(100vw - 2rem);
  }
  
  .detail-grid {
    grid-template-columns: 1fr;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
}

/* Print Styles */
@media print {
  .enquiries-page {
    background: white;
    padding: 0;
  }
  
  .page-header,
  .filters-section,
  .search-section,
  .bulk-actions,
  .pagination,
  .card-actions,
  .modal-overlay {
    display: none !important;
  }
  
  .enquiries-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .enquiry-card {
    break-inside: avoid;
    box-shadow: none;
    border: 1px solid #e5e7eb;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .enquiries-page {
    background-color: #0f172a;
    color: #e2e8f0;
  }
  
  .page-header,
  .stat-card,
  .filters-section,
  .enquiry-card,
  .modal-content {
    background: #1e293b;
    border-color: #334155;
  }
  
  .page-title,
  .enquiry-subject {
    color: #f1f5f9;
  }
  
  .page-subtitle,
  .enquiry-meta span,
  .enquiry-message {
    color: #94a3b8;
  }
  
  .search-input,
  .form-group input,
  .form-group select,
  .form-group textarea {
    background: #334155;
    border-color: #475569;
    color: #e2e8f0;
  }
  
  .btn-secondary {
    background: #334155;
    color: #e2e8f0;
    border-color: #475569;
  }
  
  .message-content {
    background: #334155;
    border-color: #475569;
    color: #e2e8f0;
  }
}
</style>