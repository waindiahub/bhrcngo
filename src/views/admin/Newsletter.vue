<template>
  <div class="newsletter-management">
    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
      <p>Loading newsletters...</p>
    </div>

    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">Newsletter Management</h1>
        <p class="page-subtitle">Manage newsletters and communications</p>
      </div>
      <div class="header-actions">
        <button @click="fetchNewsletters" class="btn btn-secondary" :disabled="loading">
          <i class="fas fa-sync-alt"></i>
          Refresh
        </button>
        <button @click="showAddModal = true" class="btn btn-primary">
          <i class="fas fa-plus"></i>
          Create Newsletter
        </button>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-newspaper"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.total || 0 }}</h3>
          <p>Total Newsletters</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon published">
          <i class="fas fa-paper-plane"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.sent || 0 }}</h3>
          <p>Sent</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon draft">
          <i class="fas fa-edit"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.draft || 0 }}</h3>
          <p>Drafts</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon scheduled">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.scheduled || 0 }}</h3>
          <p>Scheduled</p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
      <div class="filters-row">
        <div class="filter-group">
          <label>Status:</label>
          <select v-model="filters.status" @change="fetchNewsletters">
            <option value="">All Status</option>
            <option value="draft">Draft</option>
            <option value="scheduled">Scheduled</option>
            <option value="sent">Sent</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Type:</label>
          <select v-model="filters.type" @change="fetchNewsletters">
            <option value="">All Types</option>
            <option value="newsletter">Newsletter</option>
            <option value="announcement">Announcement</option>
            <option value="event">Event</option>
            <option value="promotion">Promotion</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Sort by:</label>
          <select v-model="filters.sortBy" @change="fetchNewsletters">
            <option value="created_at">Created Date</option>
            <option value="scheduled_at">Scheduled Date</option>
            <option value="sent_at">Sent Date</option>
            <option value="subject">Subject</option>
            <option value="recipients_count">Recipients</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Order:</label>
          <select v-model="filters.order" @change="fetchNewsletters">
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
          placeholder="Search newsletters by subject, content, or recipient..."
        >
        <button v-if="searchQuery" @click="clearSearch" class="clear-search">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>

    <!-- Bulk Actions -->
    <div v-if="selectedNewsletters.length > 0" class="bulk-actions">
      <div class="bulk-info">
        <span>{{ selectedNewsletters.length }} newsletter(s) selected</span>
      </div>
      <div class="bulk-buttons">
        <button @click="bulkAction('duplicate')" class="btn btn-secondary">
          <i class="fas fa-copy"></i>
          Duplicate
        </button>
        <button @click="bulkAction('cancel')" class="btn btn-warning">
          <i class="fas fa-ban"></i>
          Cancel
        </button>
        <button @click="bulkAction('delete')" class="btn btn-danger">
          <i class="fas fa-trash"></i>
          Delete
        </button>
      </div>
    </div>

    <!-- Newsletters List -->
    <div class="newsletters-list">
      <div v-if="newsletters.length === 0 && !loading" class="empty-state">
        <i class="fas fa-newspaper"></i>
        <h3>No newsletters found</h3>
        <p>{{ searchQuery ? 'Try adjusting your search criteria' : 'Create your first newsletter to get started' }}</p>
        <button v-if="!searchQuery" @click="showAddModal = true" class="btn btn-primary">
          <i class="fas fa-plus"></i>
          Create Newsletter
        </button>
      </div>

      <div v-else class="newsletters-grid">
        <div 
          v-for="newsletter in newsletters" 
          :key="newsletter.id" 
          class="newsletter-card"
          :class="{ 'selected': selectedNewsletters.includes(newsletter.id) }"
        >
          <div class="newsletter-header">
            <div class="newsletter-select">
              <input 
                type="checkbox" 
                :value="newsletter.id" 
                v-model="selectedNewsletters"
                :id="`newsletter-${newsletter.id}`"
              >
              <label :for="`newsletter-${newsletter.id}`"></label>
            </div>
            <div class="newsletter-status">
              <span :class="`status-badge ${newsletter.status}`">
                {{ newsletter.status }}
              </span>
            </div>
            <div class="newsletter-actions">
              <button @click="editNewsletter(newsletter)" class="btn-icon" title="Edit">
                <i class="fas fa-edit"></i>
              </button>
              <button @click="viewNewsletter(newsletter)" class="btn-icon" title="View">
                <i class="fas fa-eye"></i>
              </button>
              <button @click="duplicateNewsletter(newsletter)" class="btn-icon" title="Duplicate">
                <i class="fas fa-copy"></i>
              </button>
              <div class="dropdown">
                <button class="btn-icon dropdown-toggle" title="More actions">
                  <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="dropdown-menu">
                  <button v-if="newsletter.status === 'draft'" @click="scheduleNewsletter(newsletter)">
                    <i class="fas fa-clock"></i>
                    Schedule
                  </button>
                  <button v-if="newsletter.status === 'draft'" @click="sendNewsletter(newsletter)">
                    <i class="fas fa-paper-plane"></i>
                    Send Now
                  </button>
                  <button v-if="newsletter.status === 'scheduled'" @click="cancelNewsletter(newsletter)">
                    <i class="fas fa-ban"></i>
                    Cancel
                  </button>
                  <button @click="viewStats(newsletter)">
                    <i class="fas fa-chart-bar"></i>
                    View Stats
                  </button>
                  <button @click="deleteNewsletter(newsletter)" class="danger">
                    <i class="fas fa-trash"></i>
                    Delete
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="newsletter-content">
            <h3 class="newsletter-subject">{{ newsletter.subject }}</h3>
            <p class="newsletter-preview">{{ newsletter.preview || 'No preview available' }}</p>
            
            <div class="newsletter-meta">
              <div class="meta-item">
                <i class="fas fa-tag"></i>
                <span>{{ newsletter.type }}</span>
              </div>
              <div class="meta-item">
                <i class="fas fa-users"></i>
                <span>{{ newsletter.recipients_count || 0 }} recipients</span>
              </div>
              <div class="meta-item">
                <i class="fas fa-calendar"></i>
                <span>{{ formatDate(newsletter.created_at) }}</span>
              </div>
            </div>

            <div v-if="newsletter.scheduled_at" class="newsletter-schedule">
              <i class="fas fa-clock"></i>
              <span>Scheduled for {{ formatDate(newsletter.scheduled_at) }}</span>
            </div>

            <div v-if="newsletter.sent_at" class="newsletter-sent">
              <i class="fas fa-check-circle"></i>
              <span>Sent on {{ formatDate(newsletter.sent_at) }}</span>
              <span v-if="newsletter.open_rate" class="open-rate">
                {{ newsletter.open_rate }}% open rate
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

    <!-- Add/Edit Newsletter Modal -->
    <div v-if="showAddModal || showEditModal" class="modal-overlay" @click="closeModals">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h2>{{ showAddModal ? 'Create Newsletter' : 'Edit Newsletter' }}</h2>
          <button @click="closeModals" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="saveNewsletter">
            <div class="form-row">
              <div class="form-group">
                <label for="subject">Subject *</label>
                <input 
                  type="text" 
                  id="subject"
                  v-model="newsletterForm.subject" 
                  required
                  placeholder="Enter newsletter subject"
                >
              </div>
              <div class="form-group">
                <label for="type">Type *</label>
                <select id="type" v-model="newsletterForm.type" required>
                  <option value="newsletter">Newsletter</option>
                  <option value="announcement">Announcement</option>
                  <option value="event">Event</option>
                  <option value="promotion">Promotion</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label for="preview">Preview Text</label>
              <input 
                type="text" 
                id="preview"
                v-model="newsletterForm.preview" 
                placeholder="Brief preview text (optional)"
                maxlength="150"
              >
              <small>This text appears in email previews</small>
            </div>

            <div class="form-group">
              <label for="content">Content *</label>
              <textarea 
                id="content"
                v-model="newsletterForm.content" 
                required
                rows="10"
                placeholder="Enter newsletter content (HTML supported)"
              ></textarea>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="recipients">Recipients *</label>
                <select id="recipients" v-model="newsletterForm.recipients" required>
                  <option value="all">All Members</option>
                  <option value="active">Active Members Only</option>
                  <option value="premium">Premium Members</option>
                  <option value="new">New Members (Last 30 days)</option>
                  <option value="custom">Custom List</option>
                </select>
              </div>
              <div class="form-group">
                <label for="priority">Priority</label>
                <select id="priority" v-model="newsletterForm.priority">
                  <option value="normal">Normal</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
            </div>

            <div v-if="newsletterForm.recipients === 'custom'" class="form-group">
              <label for="custom_recipients">Custom Recipients</label>
              <textarea 
                id="custom_recipients"
                v-model="newsletterForm.custom_recipients" 
                rows="3"
                placeholder="Enter email addresses separated by commas"
              ></textarea>
            </div>

            <div class="form-group">
              <label>
                <input type="checkbox" v-model="newsletterForm.schedule_send">
                Schedule for later
              </label>
            </div>

            <div v-if="newsletterForm.schedule_send" class="form-group">
              <label for="scheduled_at">Schedule Date & Time</label>
              <input 
                type="datetime-local" 
                id="scheduled_at"
                v-model="newsletterForm.scheduled_at"
              >
            </div>

            <div class="modal-actions">
              <button type="button" @click="closeModals" class="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary" :disabled="saving">
                <i v-if="saving" class="fas fa-spinner fa-spin"></i>
                {{ saving ? 'Saving...' : (showAddModal ? 'Create Newsletter' : 'Update Newsletter') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- View Newsletter Modal -->
    <div v-if="showViewModal" class="modal-overlay" @click="closeModals">
      <div class="modal modal-large" @click.stop>
        <div class="modal-header">
          <h2>{{ viewingNewsletter.subject }}</h2>
          <button @click="closeModals" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <div class="newsletter-preview">
            <div class="preview-meta">
              <div class="meta-row">
                <span><strong>Type:</strong> {{ viewingNewsletter.type }}</span>
                <span><strong>Status:</strong> {{ viewingNewsletter.status }}</span>
                <span><strong>Recipients:</strong> {{ viewingNewsletter.recipients_count || 0 }}</span>
              </div>
              <div class="meta-row">
                <span><strong>Created:</strong> {{ formatDate(viewingNewsletter.created_at) }}</span>
                <span v-if="viewingNewsletter.scheduled_at">
                  <strong>Scheduled:</strong> {{ formatDate(viewingNewsletter.scheduled_at) }}
                </span>
                <span v-if="viewingNewsletter.sent_at">
                  <strong>Sent:</strong> {{ formatDate(viewingNewsletter.sent_at) }}
                </span>
              </div>
            </div>
            
            <div class="preview-content" v-html="viewingNewsletter.content"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Toast -->
    <div v-if="showToast" class="toast toast-success">
      <i class="fas fa-check-circle"></i>
      <span>{{ toastMessage }}</span>
      <button @click="showToast = false" class="toast-close">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { api } from '@/utils/api'

export default {
  name: 'Newsletter',
  setup() {
    // Reactive data
    const loading = ref(false)
    const saving = ref(false)
    const newsletters = ref([])
    const stats = ref({})
    const selectedNewsletters = ref([])
    const searchQuery = ref('')
    const currentPage = ref(1)
    const totalPages = ref(1)
    const totalNewsletters = ref(0)
    const itemsPerPage = ref(12)

    // Modals
    const showAddModal = ref(false)
    const showEditModal = ref(false)
    const showViewModal = ref(false)
    const viewingNewsletter = ref({})

    // Toast
    const showToast = ref(false)
    const toastMessage = ref('')

    // Filters
    const filters = reactive({
      status: '',
      type: '',
      sortBy: 'created_at',
      order: 'desc'
    })

    // Newsletter form
    const newsletterForm = reactive({
      id: null,
      subject: '',
      type: 'newsletter',
      preview: '',
      content: '',
      recipients: 'all',
      priority: 'normal',
      schedule_send: false,
      scheduled_at: '',
      custom_recipients: ''
    })

    // Computed properties
    const visiblePages = computed(() => {
      const pages = []
      const start = Math.max(1, currentPage.value - 2)
      const end = Math.min(totalPages.value, start + 4)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      return pages
    })

    // Methods
    const fetchStats = async () => {
      try {
        const response = await api.get('/admin/newsletter/stats')
        if (response.data.success) {
          stats.value = response.data.stats
        }
      } catch (error) {
        console.error('Error fetching newsletter stats:', error)
      }
    }

    const fetchNewsletters = async () => {
      loading.value = true
      try {
        const params = {
          page: currentPage.value,
          limit: itemsPerPage.value,
          search: searchQuery.value,
          status: filters.status,
          type: filters.type,
          sort_by: filters.sortBy,
          order: filters.order
        }

        const response = await api.get('/admin/newsletter/newsletters', { params })
        
        if (response.data.success) {
          newsletters.value = response.data.newsletters || []
          totalNewsletters.value = response.data.total || 0
          totalPages.value = Math.ceil(totalNewsletters.value / itemsPerPage.value)
        }
      } catch (error) {
        console.error('Error fetching newsletters:', error)
        newsletters.value = []
      } finally {
        loading.value = false
      }
    }

    const saveNewsletter = async () => {
      saving.value = true
      try {
        const formData = new FormData()
        
        Object.keys(newsletterForm).forEach(key => {
          if (newsletterForm[key] !== null && newsletterForm[key] !== '') {
            formData.append(key, newsletterForm[key])
          }
        })

        let response
        if (showAddModal.value) {
          response = await api.post('/admin/newsletter/newsletters', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
          })
        } else {
          response = await api.put('/admin/newsletter/newsletters', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
          })
        }
        
        if (response.data.success) {
          showToast.value = true
          toastMessage.value = showAddModal.value ? 'Newsletter created successfully!' : 'Newsletter updated successfully!'
          closeModals()
          fetchNewsletters()
          fetchStats()
        } else {
          alert(response.data.message || 'Error saving newsletter')
        }
      } catch (error) {
        console.error('Error saving newsletter:', error)
        alert('Error saving newsletter')
      } finally {
        saving.value = false
      }
    }

    const editNewsletter = (newsletter) => {
      Object.keys(newsletterForm).forEach(key => {
        newsletterForm[key] = newsletter[key] || ''
      })
      showEditModal.value = true
    }

    const viewNewsletter = (newsletter) => {
      viewingNewsletter.value = newsletter
      showViewModal.value = true
    }

    const duplicateNewsletter = async (newsletter) => {
      try {
        const response = await api.post('/admin/newsletter/newsletters', {
          action: 'duplicate',
          id: newsletter.id
        })

        if (response.data.success) {
          showToast.value = true
          toastMessage.value = 'Newsletter duplicated successfully!'
          fetchNewsletters()
          fetchStats()
        } else {
          alert(response.data.message || 'Error duplicating newsletter')
        }
      } catch (error) {
        console.error('Error duplicating newsletter:', error)
        alert('Error duplicating newsletter')
      }
    }

    const scheduleNewsletter = (newsletter) => {
      // Implementation for scheduling
      console.log('Schedule newsletter:', newsletter)
    }

    const sendNewsletter = async (newsletter) => {
      if (confirm('Are you sure you want to send this newsletter now?')) {
        try {
          const response = await api.post('/admin/newsletter/newsletters', {
            action: 'send',
            id: newsletter.id
          })
          
          if (response.data.success) {
            showToast.value = true
            toastMessage.value = 'Newsletter sent successfully!'
            fetchNewsletters()
            fetchStats()
          } else {
            alert(response.data.message || 'Error sending newsletter')
          }
        } catch (error) {
          console.error('Error sending newsletter:', error)
          alert('Error sending newsletter')
        }
      }
    }

    const cancelNewsletter = async (newsletter) => {
      if (confirm('Are you sure you want to cancel this scheduled newsletter?')) {
        try {
          const response = await api.put('/admin/newsletter/newsletters', {
            id: newsletter.id,
            status: 'cancelled'
          })
          
          if (response.data.success) {
            showToast.value = true
            toastMessage.value = 'Newsletter cancelled successfully!'
            fetchNewsletters()
            fetchStats()
          } else {
            alert(response.data.message || 'Error cancelling newsletter')
          }
        } catch (error) {
          console.error('Error cancelling newsletter:', error)
          alert('Error cancelling newsletter')
        }
      }
    }

    const deleteNewsletter = async (newsletter) => {
      if (confirm('Are you sure you want to delete this newsletter? This action cannot be undone.')) {
        try {
          const response = await api.delete(`/admin/newsletter/newsletters/${newsletter.id}`)
          
          if (response.success) {
            showToast.value = true
            toastMessage.value = 'Newsletter deleted successfully!'
            fetchNewsletters()
            fetchStats()
          } else {
            alert(response.message || 'Error deleting newsletter')
          }
        } catch (error) {
          console.error('Error deleting newsletter:', error)
          alert('Error deleting newsletter')
        }
      }
    }

    const viewStats = (newsletter) => {
      // Implementation for viewing newsletter statistics
      console.log('View stats for newsletter:', newsletter)
    }

    const bulkAction = async (action) => {
      if (selectedNewsletters.value.length === 0) return

      const confirmMessage = {
        duplicate: 'Are you sure you want to duplicate the selected newsletters?',
        cancel: 'Are you sure you want to cancel the selected newsletters?',
        delete: 'Are you sure you want to delete the selected newsletters? This action cannot be undone.'
      }

      if (confirm(confirmMessage[action])) {
        try {
          const response = await api.post('/admin/newsletter/newsletters', {
            action: `bulk_${action}`,
            ids: selectedNewsletters.value
          })
          
          if (response.data.success) {
            showToast.value = true
            toastMessage.value = `Newsletters ${action}d successfully!`
            selectedNewsletters.value = []
            fetchNewsletters()
            fetchStats()
          } else {
            alert(response.data.message || `Error ${action}ing newsletters`)
          }
        } catch (error) {
          console.error(`Error ${action}ing newsletters:`, error)
          alert(`Error ${action}ing newsletters`)
        }
      }
    }

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
        fetchNewsletters()
      }
    }

    const debounceSearch = (() => {
      let timeout
      return () => {
        clearTimeout(timeout)
        timeout = setTimeout(() => {
          currentPage.value = 1
          fetchNewsletters()
        }, 500)
      }
    })()

    const clearSearch = () => {
      searchQuery.value = ''
      currentPage.value = 1
      fetchNewsletters()
    }

    const closeModals = () => {
      showAddModal.value = false
      showEditModal.value = false
      showViewModal.value = false
      
      // Reset form
      Object.keys(newsletterForm).forEach(key => {
        if (key === 'type') {
          newsletterForm[key] = 'newsletter'
        } else if (key === 'recipients') {
          newsletterForm[key] = 'all'
        } else if (key === 'priority') {
          newsletterForm[key] = 'normal'
        } else if (key === 'schedule_send') {
          newsletterForm[key] = false
        } else {
          newsletterForm[key] = key === 'id' ? null : ''
        }
      })
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      const date = new Date(dateString)
      return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    }

    // Lifecycle
    onMounted(() => {
      fetchStats()
      fetchNewsletters()
    })

    return {
      // Data
      loading,
      saving,
      newsletters,
      stats,
      selectedNewsletters,
      searchQuery,
      currentPage,
      totalPages,
      totalNewsletters,
      itemsPerPage,
      filters,
      newsletterForm,
      
      // Modals
      showAddModal,
      showEditModal,
      showViewModal,
      viewingNewsletter,
      
      // Toast
      showToast,
      toastMessage,
      
      // Computed
      visiblePages,
      
      // Methods
      fetchStats,
      fetchNewsletters,
      saveNewsletter,
      editNewsletter,
      viewNewsletter,
      duplicateNewsletter,
      scheduleNewsletter,
      sendNewsletter,
      cancelNewsletter,
      deleteNewsletter,
      viewStats,
      bulkAction,
      changePage,
      debounceSearch,
      clearSearch,
      closeModals,
      formatDate
    }
  }
}
</script>

<style scoped>
.newsletter-management {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
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
  z-index: 1000;
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #007bff;
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
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #e9ecef;
}

.header-content h1 {
  margin: 0 0 0.5rem 0;
  color: #2c3e50;
  font-size: 2.5rem;
  font-weight: 700;
}

.header-content p {
  margin: 0;
  color: #6c757d;
  font-size: 1.1rem;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

/* Buttons */
.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.95rem;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: linear-gradient(135deg, #007bff, #0056b3);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #0056b3, #004085);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover:not(:disabled) {
  background: #545b62;
  transform: translateY(-2px);
}

.btn-warning {
  background: #ffc107;
  color: #212529;
}

.btn-warning:hover:not(:disabled) {
  background: #e0a800;
  transform: translateY(-2px);
}

.btn-danger {
  background: #dc3545;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #c82333;
  transform: translateY(-2px);
}

.btn-icon {
  padding: 0.5rem;
  background: none;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
  color: #6c757d;
}

.btn-icon:hover {
  background: #f8f9fa;
  color: #007bff;
}

/* Statistics Grid */
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
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
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
  background: linear-gradient(135deg, #007bff, #0056b3);
}

.stat-icon.published {
  background: linear-gradient(135deg, #28a745, #1e7e34);
}

.stat-icon.draft {
  background: linear-gradient(135deg, #ffc107, #e0a800);
}

.stat-icon.scheduled {
  background: linear-gradient(135deg, #17a2b8, #138496);
}

.stat-content h3 {
  margin: 0 0 0.25rem 0;
  font-size: 2rem;
  font-weight: 700;
  color: #2c3e50;
}

.stat-content p {
  margin: 0;
  color: #6c757d;
  font-weight: 500;
}

/* Filters Section */
.filters-section {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  margin-bottom: 1.5rem;
}

.filters-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  align-items: end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group label {
  font-weight: 600;
  color: #495057;
  font-size: 0.9rem;
}

.filter-group select {
  padding: 0.75rem;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: border-color 0.3s ease;
}

.filter-group select:focus {
  outline: none;
  border-color: #007bff;
}

/* Search Section */
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
  color: #6c757d;
}

.search-box input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 3rem;
  border: 2px solid #e9ecef;
  border-radius: 25px;
  font-size: 0.95rem;
  transition: border-color 0.3s ease;
}

.search-box input:focus {
  outline: none;
  border-color: #007bff;
}

.clear-search {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #6c757d;
  cursor: pointer;
  padding: 0.25rem;
}

/* Bulk Actions */
.bulk-actions {
  background: #f8f9fa;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  border-left: 4px solid #007bff;
}

.bulk-info {
  font-weight: 600;
  color: #495057;
}

.bulk-buttons {
  display: flex;
  gap: 0.5rem;
}

/* Newsletters List */
.newsletters-list {
  margin-bottom: 2rem;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.empty-state i {
  font-size: 4rem;
  color: #dee2e6;
  margin-bottom: 1rem;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  color: #6c757d;
  font-size: 1.5rem;
}

.empty-state p {
  margin: 0 0 2rem 0;
  color: #6c757d;
}

/* Newsletters Grid */
.newsletters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
}

.newsletter-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  overflow: hidden;
  border: 2px solid transparent;
}

.newsletter-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.newsletter-card.selected {
  border-color: #007bff;
  box-shadow: 0 4px 20px rgba(0, 123, 255, 0.2);
}

.newsletter-header {
  padding: 1rem 1.5rem;
  background: #f8f9fa;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e9ecef;
}

.newsletter-select input[type="checkbox"] {
  display: none;
}

.newsletter-select label {
  width: 20px;
  height: 20px;
  border: 2px solid #dee2e6;
  border-radius: 4px;
  display: block;
  cursor: pointer;
  position: relative;
  transition: all 0.3s ease;
}

.newsletter-select input[type="checkbox"]:checked + label {
  background: #007bff;
  border-color: #007bff;
}

.newsletter-select input[type="checkbox"]:checked + label::after {
  content: '✓';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: white;
  font-size: 12px;
  font-weight: bold;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.draft {
  background: #fff3cd;
  color: #856404;
}

.status-badge.scheduled {
  background: #d1ecf1;
  color: #0c5460;
}

.status-badge.sent {
  background: #d4edda;
  color: #155724;
}

.status-badge.cancelled {
  background: #f8d7da;
  color: #721c24;
}

.newsletter-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.dropdown {
  position: relative;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  min-width: 150px;
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
  transition: background-color 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #495057;
}

.dropdown-menu button:hover {
  background: #f8f9fa;
}

.dropdown-menu button.danger {
  color: #dc3545;
}

.dropdown-menu button.danger:hover {
  background: #f8d7da;
}

.newsletter-content {
  padding: 1.5rem;
}

.newsletter-subject {
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #2c3e50;
  line-height: 1.3;
}

.newsletter-preview {
  margin: 0 0 1rem 0;
  color: #6c757d;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.newsletter-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1rem;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #6c757d;
  font-size: 0.9rem;
}

.meta-item i {
  color: #007bff;
}

.newsletter-schedule,
.newsletter-sent {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.9rem;
  margin-top: 1rem;
}

.newsletter-schedule {
  background: #fff3cd;
  color: #856404;
}

.newsletter-sent {
  background: #d4edda;
  color: #155724;
}

.open-rate {
  margin-left: auto;
  font-weight: 600;
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
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-large {
  max-width: 900px;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8f9fa;
}

.modal-header h2 {
  margin: 0;
  color: #2c3e50;
  font-size: 1.5rem;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6c757d;
  padding: 0.25rem;
}

.modal-close:hover {
  color: #dc3545;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #495057;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #007bff;
}

.form-group small {
  display: block;
  margin-top: 0.25rem;
  color: #6c757d;
  font-size: 0.85rem;
}

.form-group input[type="checkbox"] {
  width: auto;
  margin-right: 0.5rem;
}

.modal-actions {
  padding: 1.5rem;
  border-top: 1px solid #e9ecef;
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  background: #f8f9fa;
}

/* Newsletter Preview */
.newsletter-preview {
  max-height: 60vh;
  overflow-y: auto;
}

.preview-meta {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.meta-row {
  display: flex;
  gap: 2rem;
  margin-bottom: 0.5rem;
}

.meta-row:last-child {
  margin-bottom: 0;
}

.meta-row span {
  font-size: 0.9rem;
  color: #495057;
}

.preview-content {
  line-height: 1.6;
  color: #495057;
}

/* Toast */
.toast {
  position: fixed;
  top: 2rem;
  right: 2rem;
  background: #28a745;
  color: white;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  gap: 0.75rem;
  z-index: 1100;
  animation: slideIn 0.3s ease;
}

.toast-success {
  background: #28a745;
}

.toast-close {
  background: none;
  border: none;
  color: white;
  cursor: pointer;
  padding: 0.25rem;
  margin-left: 1rem;
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
  .newsletter-management {
    padding: 1rem;
  }

  .page-header {
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

  .newsletters-grid {
    grid-template-columns: 1fr;
  }

  .form-row {
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

  .newsletter-meta {
    flex-direction: column;
    gap: 0.5rem;
  }

  .meta-row {
    flex-direction: column;
    gap: 0.5rem;
  }
}

@media (max-width: 480px) {
  .newsletter-management {
    padding: 0.5rem;
  }

  .header-content h1 {
    font-size: 2rem;
  }

  .modal {
    margin: 0.5rem;
    max-width: calc(100vw - 1rem);
  }

  .toast {
    top: 1rem;
    right: 1rem;
    left: 1rem;
  }
}

/* Print Styles */
@media print {
  .newsletter-management {
    padding: 0;
  }

  .page-header,
  .filters-section,
  .search-section,
  .bulk-actions,
  .pagination,
  .modal-overlay,
  .toast {
    display: none !important;
  }

  .newsletters-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .newsletter-card {
    box-shadow: none;
    border: 1px solid #dee2e6;
    break-inside: avoid;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .newsletter-management {
    background: #1a1a1a;
    color: #e9ecef;
  }

  .newsletter-card,
  .stat-card,
  .filters-section,
  .modal {
    background: #2d3748;
    color: #e9ecef;
  }

  .newsletter-header {
    background: #4a5568;
  }

  .form-group input,
  .form-group select,
  .form-group textarea {
    background: #4a5568;
    border-color: #718096;
    color: #e9ecef;
  }

  .dropdown-menu {
    background: #2d3748;
    border-color: #4a5568;
  }

  .dropdown-menu button {
    color: #e9ecef;
  }

  .dropdown-menu button:hover {
    background: #4a5568;
  }
}
</style>