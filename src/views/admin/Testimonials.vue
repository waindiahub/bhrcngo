<template>
  <div class="testimonials-page">
    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
      <p>Loading testimonials...</p>
    </div>

    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="fas fa-quote-right"></i>
            Testimonials Management
          </h1>
          <p class="page-subtitle">Manage member testimonials and reviews</p>
        </div>
        <div class="header-actions">
          <button @click="refreshTestimonials" class="btn btn-secondary" :disabled="loading">
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
            Refresh
          </button>
          <button @click="showAddModal = true" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Add Testimonial
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <!-- Statistics Cards -->
      <div class="stats-grid">
        <div class="stat-card total">
          <div class="stat-icon">
            <i class="fas fa-quote-right"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number">{{ stats.total_testimonials || 0 }}</div>
            <div class="stat-label">Total Testimonials</div>
            <div class="stat-change positive" v-if="stats.testimonials_growth > 0">
              +{{ stats.testimonials_growth }}% this month
            </div>
          </div>
        </div>

        <div class="stat-card published">
          <div class="stat-icon">
            <i class="fas fa-eye"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number">{{ stats.published_testimonials || 0 }}</div>
            <div class="stat-label">Published</div>
            <div class="stat-percentage">{{ stats.published_percentage || 0 }}% of total</div>
          </div>
        </div>

        <div class="stat-card pending">
          <div class="stat-icon">
            <i class="fas fa-clock"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number">{{ stats.pending_testimonials || 0 }}</div>
            <div class="stat-label">Pending Review</div>
            <div class="stat-percentage">{{ stats.pending_percentage || 0 }}% of total</div>
          </div>
        </div>

        <div class="stat-card rating">
          <div class="stat-icon">
            <i class="fas fa-star"></i>
          </div>
          <div class="stat-content">
            <div class="stat-number">{{ stats.average_rating || 0 }}</div>
            <div class="stat-label">Average Rating</div>
            <div class="stat-stars">
              <i v-for="n in 5" :key="n" 
                 :class="['fas fa-star', n <= Math.round(stats.average_rating || 0) ? 'filled' : '']">
              </i>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters and Controls -->
      <div class="controls-section">
        <div class="filters">
          <div class="filter-group">
            <label>Status:</label>
            <select v-model="filterStatus" @change="fetchTestimonials">
              <option value="">All Status</option>
              <option value="published">Published</option>
              <option value="pending">Pending</option>
              <option value="draft">Draft</option>
            </select>
          </div>

          <div class="filter-group">
            <label>Rating:</label>
            <select v-model="filterRating" @change="fetchTestimonials">
              <option value="">All Ratings</option>
              <option value="5">5 Stars</option>
              <option value="4">4+ Stars</option>
              <option value="3">3+ Stars</option>
              <option value="2">2+ Stars</option>
              <option value="1">1+ Stars</option>
            </select>
          </div>

          <div class="filter-group">
            <label>Sort by:</label>
            <select v-model="sortBy" @change="fetchTestimonials">
              <option value="created_at">Date Created</option>
              <option value="rating">Rating</option>
              <option value="member_name">Member Name</option>
              <option value="status">Status</option>
            </select>
          </div>

          <div class="filter-group">
            <label>Order:</label>
            <select v-model="sortOrder" @change="fetchTestimonials">
              <option value="desc">Descending</option>
              <option value="asc">Ascending</option>
            </select>
          </div>
        </div>

        <div class="search-section">
          <div class="search-box">
            <i class="fas fa-search"></i>
            <input 
              type="text" 
              v-model="searchQuery" 
              @input="debounceSearch"
              placeholder="Search testimonials, members..."
            >
          </div>
        </div>
      </div>

      <!-- Bulk Actions -->
      <div v-if="selectedTestimonials.length > 0" class="bulk-actions">
        <div class="bulk-info">
          <span>{{ selectedTestimonials.length }} testimonial(s) selected</span>
        </div>
        <div class="bulk-buttons">
          <button @click="bulkPublish" class="btn btn-success btn-sm">
            <i class="fas fa-eye"></i>
            Publish Selected
          </button>
          <button @click="bulkUnpublish" class="btn btn-warning btn-sm">
            <i class="fas fa-eye-slash"></i>
            Unpublish Selected
          </button>
          <button @click="bulkDelete" class="btn btn-danger btn-sm">
            <i class="fas fa-trash"></i>
            Delete Selected
          </button>
        </div>
      </div>

      <!-- Testimonials List -->
      <div class="testimonials-section">
        <div class="section-header">
          <h2>Testimonials ({{ totalItems }})</h2>
        </div>

        <div v-if="testimonials.length === 0 && !loading" class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-quote-right"></i>
          </div>
          <h3>No testimonials found</h3>
          <p>Start by adding your first testimonial or adjust your search filters.</p>
          <button @click="showAddModal = true" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Add First Testimonial
          </button>
        </div>

        <div v-else class="testimonials-grid">
          <div v-for="testimonial in testimonials" :key="testimonial.id" class="testimonial-card">
            <div class="card-header">
              <div class="member-info">
                <div class="member-avatar">
                  <img v-if="testimonial.member_photo" 
                       :src="testimonial.member_photo" 
                       :alt="testimonial.member_name">
                  <i v-else class="fas fa-user"></i>
                </div>
                <div class="member-details">
                  <h4>{{ testimonial.member_name }}</h4>
                  <p>{{ testimonial.member_designation || 'Member' }}</p>
                </div>
              </div>
              <div class="card-actions">
                <input 
                  type="checkbox" 
                  :value="testimonial.id" 
                  v-model="selectedTestimonials"
                  class="testimonial-checkbox"
                >
                <div class="dropdown">
                  <button class="btn-icon" @click="toggleDropdown(testimonial.id)">
                    <i class="fas fa-ellipsis-v"></i>
                  </button>
                  <div v-if="activeDropdown === testimonial.id" class="dropdown-menu">
                    <button @click="editTestimonial(testimonial)">
                      <i class="fas fa-edit"></i>
                      Edit
                    </button>
                    <button @click="viewTestimonial(testimonial)">
                      <i class="fas fa-eye"></i>
                      View
                    </button>
                    <button v-if="testimonial.status !== 'published'" @click="publishTestimonial(testimonial.id)">
                      <i class="fas fa-check"></i>
                      Publish
                    </button>
                    <button v-else @click="unpublishTestimonial(testimonial.id)">
                      <i class="fas fa-eye-slash"></i>
                      Unpublish
                    </button>
                    <button @click="deleteTestimonial(testimonial.id)" class="danger">
                      <i class="fas fa-trash"></i>
                      Delete
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="card-content">
              <div class="rating">
                <i v-for="n in 5" :key="n" 
                   :class="['fas fa-star', n <= testimonial.rating ? 'filled' : '']">
                </i>
                <span class="rating-text">({{ testimonial.rating }}/5)</span>
              </div>
              
              <div class="testimonial-text">
                <p>{{ testimonial.content }}</p>
              </div>

              <div class="testimonial-meta">
                <div class="status-badge" :class="testimonial.status">
                  {{ testimonial.status }}
                </div>
                <div class="date">
                  {{ formatDate(testimonial.created_at) }}
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
      </div>
    </div>

    <!-- Add/Edit Testimonial Modal -->
    <div v-if="showAddModal || showEditModal" class="modal-overlay" @click="closeModals">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>{{ showEditModal ? 'Edit Testimonial' : 'Add New Testimonial' }}</h3>
          <button @click="closeModals" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="saveTestimonial">
            <div class="form-group">
              <label for="member_name">Member Name *</label>
              <input 
                type="text" 
                id="member_name"
                v-model="testimonialForm.member_name" 
                required
                placeholder="Enter member name"
              >
            </div>

            <div class="form-group">
              <label for="member_designation">Member Designation</label>
              <input 
                type="text" 
                id="member_designation"
                v-model="testimonialForm.member_designation" 
                placeholder="e.g., Senior Advocate, Member"
              >
            </div>

            <div class="form-group">
              <label for="rating">Rating *</label>
              <div class="rating-input">
                <button 
                  v-for="n in 5" 
                  :key="n"
                  type="button"
                  @click="testimonialForm.rating = n"
                  :class="['star-btn', n <= testimonialForm.rating ? 'active' : '']"
                >
                  <i class="fas fa-star"></i>
                </button>
                <span class="rating-value">{{ testimonialForm.rating }}/5</span>
              </div>
            </div>

            <div class="form-group">
              <label for="content">Testimonial Content *</label>
              <textarea 
                id="content"
                v-model="testimonialForm.content" 
                required
                rows="4"
                placeholder="Enter the testimonial content..."
              ></textarea>
            </div>

            <div class="form-group">
              <label for="status">Status</label>
              <select id="status" v-model="testimonialForm.status">
                <option value="draft">Draft</option>
                <option value="pending">Pending Review</option>
                <option value="published">Published</option>
              </select>
            </div>

            <div class="form-actions">
              <button type="button" @click="closeModals" class="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary" :disabled="saving">
                <i v-if="saving" class="fas fa-spinner fa-spin"></i>
                {{ saving ? 'Saving...' : (showEditModal ? 'Update' : 'Create') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- View Testimonial Modal -->
    <div v-if="showViewModal" class="modal-overlay" @click="closeModals">
      <div class="modal view-modal" @click.stop>
        <div class="modal-header">
          <h3>Testimonial Details</h3>
          <button @click="closeModals" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        
        <div class="modal-body">
          <div v-if="selectedTestimonial" class="testimonial-details">
            <div class="member-section">
              <div class="member-avatar large">
                <img v-if="selectedTestimonial.member_photo" 
                     :src="selectedTestimonial.member_photo" 
                     :alt="selectedTestimonial.member_name">
                <i v-else class="fas fa-user"></i>
              </div>
              <div class="member-info">
                <h4>{{ selectedTestimonial.member_name }}</h4>
                <p>{{ selectedTestimonial.member_designation || 'Member' }}</p>
              </div>
            </div>

            <div class="rating-section">
              <div class="rating large">
                <i v-for="n in 5" :key="n" 
                   :class="['fas fa-star', n <= selectedTestimonial.rating ? 'filled' : '']">
                </i>
                <span class="rating-text">({{ selectedTestimonial.rating }}/5)</span>
              </div>
            </div>

            <div class="content-section">
              <h5>Testimonial Content</h5>
              <div class="testimonial-content">
                {{ selectedTestimonial.content }}
              </div>
            </div>

            <div class="meta-section">
              <div class="meta-item">
                <strong>Status:</strong>
                <span class="status-badge" :class="selectedTestimonial.status">
                  {{ selectedTestimonial.status }}
                </span>
              </div>
              <div class="meta-item">
                <strong>Created:</strong>
                {{ formatDate(selectedTestimonial.created_at) }}
              </div>
              <div class="meta-item">
                <strong>Last Updated:</strong>
                {{ formatDate(selectedTestimonial.updated_at) }}
              </div>
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
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { api } from '@/utils/api'

export default {
  name: 'TestimonialsPage',
  setup() {
    const authStore = useAuthStore()
    
    // Reactive data
    const loading = ref(false)
    const saving = ref(false)
    const testimonials = ref([])
    const stats = ref({})
    const selectedTestimonials = ref([])
    const activeDropdown = ref(null)
    
    // Pagination
    const currentPage = ref(1)
    const itemsPerPage = ref(12)
    const totalItems = ref(0)
    
    // Filters and search
    const searchQuery = ref('')
    const filterStatus = ref('')
    const filterRating = ref('')
    const sortBy = ref('created_at')
    const sortOrder = ref('desc')
    
    // Modals
    const showAddModal = ref(false)
    const showEditModal = ref(false)
    const showViewModal = ref(false)
    const selectedTestimonial = ref(null)
    
    // Form data
    const testimonialForm = reactive({
      id: null,
      member_name: '',
      member_designation: '',
      rating: 5,
      content: '',
      status: 'draft'
    })
    
    // Toast
    const showToast = ref(false)
    const toastMessage = ref('')
    
    // Computed properties
    const totalPages = computed(() => {
      return Math.ceil(totalItems.value / itemsPerPage.value)
    })
    
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
        const response = await api.get('/admin/testimonials/stats')
        
        if (response.data) {
          stats.value = response.data
        }
      } catch (error) {
        console.error('Error fetching stats:', error)
      }
    }
    
    const fetchTestimonials = async () => {
      try {
        loading.value = true
        const params = {
          page: currentPage.value,
          limit: itemsPerPage.value,
          search: searchQuery.value,
          status: filterStatus.value,
          rating: filterRating.value,
          sort: sortBy.value,
          order: sortOrder.value
        }
        
        const response = await api.get('/admin/testimonials/testimonials', { params })
        
        if (response.data) {
          testimonials.value = response.data.testimonials || []
          totalItems.value = response.data.total || 0
        }
      } catch (error) {
        console.error('Error fetching testimonials:', error)
      } finally {
        loading.value = false
      }
    }
    
    const refreshTestimonials = async () => {
      await Promise.all([
        fetchStats(),
        fetchTestimonials()
      ])
    }
    
    const debounceSearch = (() => {
      let timeout
      return () => {
        clearTimeout(timeout)
        timeout = setTimeout(() => {
          currentPage.value = 1
          fetchTestimonials()
        }, 500)
      }
    })()
    
    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
        fetchTestimonials()
      }
    }
    
    const toggleDropdown = (id) => {
      activeDropdown.value = activeDropdown.value === id ? null : id
    }
    
    const editTestimonial = (testimonial) => {
      Object.assign(testimonialForm, testimonial)
      showEditModal.value = true
      activeDropdown.value = null
    }
    
    const viewTestimonial = (testimonial) => {
      selectedTestimonial.value = testimonial
      showViewModal.value = true
      activeDropdown.value = null
    }
    
    const saveTestimonial = async () => {
      try {
        saving.value = true
        
        const response = showEditModal.value 
          ? await api.put('/admin/testimonials/testimonials', testimonialForm)
          : await api.post('/admin/testimonials/testimonials', testimonialForm)
        
        if (response.data) {
          showToast.value = true
          toastMessage.value = response.data.message || 'Testimonial saved successfully'
          closeModals()
          await fetchTestimonials()
          
          setTimeout(() => {
            showToast.value = false
          }, 3000)
        }
      } catch (error) {
        console.error('Error saving testimonial:', error)
      } finally {
        saving.value = false
      }
    }
    
    const publishTestimonial = async (id) => {
      try {
        const response = await api.put('/admin/testimonials/testimonials', { 
          id, 
          status: 'published' 
        })
        
        if (response.data) {
          showToast.value = true
          toastMessage.value = 'Testimonial published successfully'
          await fetchTestimonials()
          
          setTimeout(() => {
            showToast.value = false
          }, 3000)
        }
      } catch (error) {
        console.error('Error publishing testimonial:', error)
      }
      activeDropdown.value = null
    }
    
    const unpublishTestimonial = async (id) => {
      try {
        const response = await api.put('/admin/testimonials/testimonials', { 
          id, 
          status: 'draft' 
        })
        
        if (response.data) {
          showToast.value = true
          toastMessage.value = 'Testimonial unpublished successfully'
          await fetchTestimonials()
          
          setTimeout(() => {
            showToast.value = false
          }, 3000)
        }
      } catch (error) {
        console.error('Error unpublishing testimonial:', error)
      }
      activeDropdown.value = null
    }
    
    const deleteTestimonial = async (id) => {
      if (confirm('Are you sure you want to delete this testimonial?')) {
        try {
          const response = await api.delete('/admin/testimonials/testimonials', {
            data: { id }
          })
          
          if (response.data) {
            showToast.value = true
            toastMessage.value = 'Testimonial deleted successfully'
            await fetchTestimonials()
            
            setTimeout(() => {
              showToast.value = false
            }, 3000)
          }
        } catch (error) {
          console.error('Error deleting testimonial:', error)
        }
      }
      activeDropdown.value = null
    }
    
    const bulkPublish = async () => {
      try {
        const response = await api.put('/admin/testimonials/testimonials', { 
          ids: selectedTestimonials.value, 
          status: 'published' 
        })
        
        if (response.data) {
          showToast.value = true
          toastMessage.value = `${selectedTestimonials.value.length} testimonial(s) published`
          selectedTestimonials.value = []
          await fetchTestimonials()
          
          setTimeout(() => {
            showToast.value = false
          }, 3000)
        }
      } catch (error) {
        console.error('Error bulk publishing:', error)
      }
    }
    
    const bulkUnpublish = async () => {
      try {
        const response = await api.put('/admin/testimonials/testimonials', { 
          ids: selectedTestimonials.value, 
          status: 'draft' 
        })
        
        if (response.data) {
          showToast.value = true
          toastMessage.value = `${selectedTestimonials.value.length} testimonial(s) unpublished`
          selectedTestimonials.value = []
          await fetchTestimonials()
          
          setTimeout(() => {
            showToast.value = false
          }, 3000)
        }
      } catch (error) {
        console.error('Error bulk unpublishing:', error)
      }
    }
    
    const bulkDelete = async () => {
      if (confirm(`Are you sure you want to delete ${selectedTestimonials.value.length} testimonial(s)?`)) {
        try {
          const response = await api.delete('/admin/testimonials/testimonials', {
            data: { ids: selectedTestimonials.value }
          })
          
          if (response.data) {
            showToast.value = true
            toastMessage.value = `${selectedTestimonials.value.length} testimonial(s) deleted`
            selectedTestimonials.value = []
            await fetchTestimonials()
            
            setTimeout(() => {
              showToast.value = false
            }, 3000)
          }
        } catch (error) {
          console.error('Error bulk deleting:', error)
        }
      }
    }
    
    const closeModals = () => {
      showAddModal.value = false
      showEditModal.value = false
      showViewModal.value = false
      selectedTestimonial.value = null
      
      // Reset form
      Object.assign(testimonialForm, {
        id: null,
        member_name: '',
        member_designation: '',
        rating: 5,
        content: '',
        status: 'draft'
      })
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
    
    // Watchers
    watch([filterStatus, filterRating, sortBy, sortOrder], () => {
      currentPage.value = 1
      fetchTestimonials()
    })
    
    // Lifecycle
    onMounted(() => {
      refreshTestimonials()
    })
    
    return {
      // Data
      loading,
      saving,
      testimonials,
      stats,
      selectedTestimonials,
      activeDropdown,
      currentPage,
      itemsPerPage,
      totalItems,
      searchQuery,
      filterStatus,
      filterRating,
      sortBy,
      sortOrder,
      showAddModal,
      showEditModal,
      showViewModal,
      selectedTestimonial,
      testimonialForm,
      showToast,
      toastMessage,
      
      // Computed
      totalPages,
      visiblePages,
      
      // Methods
      fetchStats,
      fetchTestimonials,
      refreshTestimonials,
      debounceSearch,
      changePage,
      toggleDropdown,
      editTestimonial,
      viewTestimonial,
      saveTestimonial,
      publishTestimonial,
      unpublishTestimonial,
      deleteTestimonial,
      bulkPublish,
      bulkUnpublish,
      bulkDelete,
      closeModals,
      formatDate
    }
  }
}
</script>

<style scoped>
/* Testimonials Page Styles */
.testimonials-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
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
  z-index: 9999;
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #e3e3e3;
  border-top: 4px solid #3498db;
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
  border-bottom: 1px solid #e1e5e9;
  padding: 2rem 0;
  margin-bottom: 2rem;
}

.header-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left h1 {
  color: #2c3e50;
  font-size: 2.5rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-left h1 i {
  color: #3498db;
}

.page-subtitle {
  color: #7f8c8d;
  font-size: 1.1rem;
  margin: 0;
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
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.95rem;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: linear-gradient(135deg, #3498db, #2980b9);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #2980b9, #1f5f8b);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}

.btn-secondary {
  background: #ecf0f1;
  color: #2c3e50;
}

.btn-secondary:hover:not(:disabled) {
  background: #d5dbdb;
  transform: translateY(-2px);
}

.btn-success {
  background: linear-gradient(135deg, #27ae60, #229954);
  color: white;
}

.btn-success:hover:not(:disabled) {
  background: linear-gradient(135deg, #229954, #1e8449);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
}

.btn-warning {
  background: linear-gradient(135deg, #f39c12, #e67e22);
  color: white;
}

.btn-warning:hover:not(:disabled) {
  background: linear-gradient(135deg, #e67e22, #d35400);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
}

.btn-danger {
  background: linear-gradient(135deg, #e74c3c, #c0392b);
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: linear-gradient(135deg, #c0392b, #a93226);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.85rem;
}

.btn-icon {
  background: none;
  border: none;
  padding: 0.5rem;
  border-radius: 50%;
  cursor: pointer;
  color: #7f8c8d;
  transition: all 0.3s ease;
}

.btn-icon:hover {
  background: #ecf0f1;
  color: #2c3e50;
}

/* Main Content */
.main-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem 2rem;
}

/* Statistics Cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  border-left: 4px solid transparent;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.stat-card.total {
  border-left-color: #3498db;
}

.stat-card.published {
  border-left-color: #27ae60;
}

.stat-card.pending {
  border-left-color: #f39c12;
}

.stat-card.rating {
  border-left-color: #e74c3c;
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

.stat-card.total .stat-icon {
  background: linear-gradient(135deg, #3498db, #2980b9);
}

.stat-card.published .stat-icon {
  background: linear-gradient(135deg, #27ae60, #229954);
}

.stat-card.pending .stat-icon {
  background: linear-gradient(135deg, #f39c12, #e67e22);
}

.stat-card.rating .stat-icon {
  background: linear-gradient(135deg, #e74c3c, #c0392b);
}

.stat-content {
  flex: 1;
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.stat-label {
  color: #7f8c8d;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.stat-change {
  font-size: 0.85rem;
  font-weight: 600;
}

.stat-change.positive {
  color: #27ae60;
}

.stat-percentage {
  font-size: 0.9rem;
  color: #7f8c8d;
}

.stat-stars {
  display: flex;
  gap: 0.25rem;
  margin-top: 0.5rem;
}

.stat-stars .fa-star {
  color: #f39c12;
  font-size: 0.9rem;
}

.stat-stars .fa-star.filled {
  color: #f39c12;
}

/* Controls Section */
.controls-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.filters {
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

.filter-group label {
  font-weight: 600;
  color: #2c3e50;
  font-size: 0.9rem;
}

.search-section {
  display: flex;
  justify-content: flex-end;
}

.search-box {
  position: relative;
  width: 300px;
}

.search-box i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #7f8c8d;
}

.search-box input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 2px solid #ecf0f1;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: all 0.3s ease;
}

.search-box input:focus {
  outline: none;
  border-color: #3498db;
  box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

/* Form Elements */
select, input[type="text"], input[type="email"], textarea {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #ecf0f1;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  background: white;
}

select:focus, input:focus, textarea:focus {
  outline: none;
  border-color: #3498db;
  box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

/* Bulk Actions */
.bulk-actions {
  background: #3498db;
  color: white;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.bulk-info {
  font-weight: 600;
}

.bulk-buttons {
  display: flex;
  gap: 0.5rem;
}

/* Testimonials Section */
.testimonials-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #ecf0f1;
}

.section-header h2 {
  color: #2c3e50;
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
}

/* Testimonials Grid */
.testimonials-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
}

.testimonial-card {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s ease;
}

.testimonial-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.card-header {
  background: white;
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e9ecef;
}

.member-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.member-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: #ecf0f1;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.member-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.member-avatar i {
  color: #7f8c8d;
  font-size: 1.5rem;
}

.member-details h4 {
  margin: 0 0 0.25rem 0;
  color: #2c3e50;
  font-weight: 600;
}

.member-details p {
  margin: 0;
  color: #7f8c8d;
  font-size: 0.9rem;
}

.card-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.testimonial-checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.dropdown {
  position: relative;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  min-width: 150px;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.dropdown-menu button {
  width: 100%;
  padding: 0.75rem 1rem;
  border: none;
  background: none;
  text-align: left;
  cursor: pointer;
  transition: background 0.2s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #2c3e50;
}

.dropdown-menu button:hover {
  background: #f8f9fa;
}

.dropdown-menu button.danger {
  color: #e74c3c;
}

.dropdown-menu button.danger:hover {
  background: #fdf2f2;
}

.card-content {
  padding: 1rem;
}

.rating {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.rating .fa-star {
  color: #ddd;
  font-size: 1rem;
}

.rating .fa-star.filled {
  color: #f39c12;
}

.rating-text {
  color: #7f8c8d;
  font-size: 0.9rem;
  font-weight: 600;
}

.testimonial-text {
  margin-bottom: 1rem;
}

.testimonial-text p {
  color: #2c3e50;
  line-height: 1.6;
  margin: 0;
}

.testimonial-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-badge.published {
  background: #d4edda;
  color: #155724;
}

.status-badge.pending {
  background: #fff3cd;
  color: #856404;
}

.status-badge.draft {
  background: #f8d7da;
  color: #721c24;
}

.date {
  color: #7f8c8d;
  font-size: 0.85rem;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid #ecf0f1;
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
  z-index: 10000;
  animation: fadeIn 0.3s ease;
}

.modal {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  animation: slideUp 0.3s ease;
}

.modal.view-modal {
  max-width: 700px;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(50px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #ecf0f1;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  color: #2c3e50;
  font-weight: 700;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #7f8c8d;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 50%;
  transition: all 0.3s ease;
}

.btn-close:hover {
  background: #ecf0f1;
  color: #2c3e50;
}

.modal-body {
  padding: 1.5rem;
}

/* Form Elements in Modal */
.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #2c3e50;
}

.rating-input {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.star-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #ddd;
  cursor: pointer;
  transition: color 0.2s ease;
  padding: 0.25rem;
}

.star-btn.active,
.star-btn:hover {
  color: #f39c12;
}

.rating-value {
  margin-left: 0.5rem;
  font-weight: 600;
  color: #2c3e50;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 2rem;
  padding-top: 1rem;
  border-top: 1px solid #ecf0f1;
}

/* Testimonial Details */
.testimonial-details {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.member-section {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.member-avatar.large {
  width: 80px;
  height: 80px;
}

.member-avatar.large i {
  font-size: 2rem;
}

.rating-section {
  text-align: center;
}

.rating.large {
  justify-content: center;
  font-size: 1.2rem;
}

.rating.large .fa-star {
  font-size: 1.5rem;
}

.content-section h5 {
  color: #2c3e50;
  margin-bottom: 1rem;
  font-weight: 600;
}

.testimonial-content {
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 8px;
  border-left: 4px solid #3498db;
  font-style: italic;
  line-height: 1.6;
  color: #2c3e50;
}

.meta-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.meta-item strong {
  color: #2c3e50;
  font-size: 0.9rem;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: #7f8c8d;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-state h3 {
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.empty-state p {
  margin-bottom: 2rem;
}

/* Success Toast */
.toast {
  position: fixed;
  top: 2rem;
  right: 2rem;
  background: #27ae60;
  color: white;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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

.toast.success {
  background: #27ae60;
}

/* Responsive Design */
@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .header-actions {
    flex-wrap: wrap;
    justify-content: center;
  }

  .main-content {
    padding: 0 1rem 2rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .filters {
    grid-template-columns: 1fr;
  }

  .search-section {
    justify-content: stretch;
  }

  .search-box {
    width: 100%;
  }

  .testimonials-grid {
    grid-template-columns: 1fr;
  }

  .bulk-actions {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .pagination {
    flex-wrap: wrap;
  }

  .modal {
    width: 95%;
    margin: 1rem;
  }

  .form-actions {
    flex-direction: column;
  }

  .meta-section {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .header-left h1 {
    font-size: 2rem;
  }

  .stat-card {
    flex-direction: column;
    text-align: center;
  }

  .card-header {
    flex-direction: column;
    gap: 1rem;
  }

  .member-info {
    flex-direction: column;
    text-align: center;
  }

  .testimonial-meta {
    flex-direction: column;
    gap: 0.5rem;
    align-items: flex-start;
  }
}

/* Print Styles */
@media print {
  .testimonials-page {
    background: white !important;
  }

  .page-header,
  .controls-section,
  .bulk-actions,
  .pagination,
  .header-actions,
  .card-actions,
  .btn {
    display: none !important;
  }

  .testimonials-grid {
    grid-template-columns: 1fr !important;
  }

  .testimonial-card {
    break-inside: avoid;
    box-shadow: none !important;
    border: 1px solid #ddd !important;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .testimonials-page {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: #ecf0f1;
  }

  .page-header,
  .controls-section,
  .testimonials-section,
  .stat-card,
  .testimonial-card,
  .modal {
    background: #34495e !important;
    color: #ecf0f1 !important;
    border-color: #4a5f7a !important;
  }

  .card-header {
    background: #2c3e50 !important;
    border-color: #4a5f7a !important;
  }

  input, select, textarea {
    background: #2c3e50 !important;
    color: #ecf0f1 !important;
    border-color: #4a5f7a !important;
  }

  .dropdown-menu {
    background: #34495e !important;
    border-color: #4a5f7a !important;
  }

  .dropdown-menu button {
    color: #ecf0f1 !important;
  }

  .dropdown-menu button:hover {
    background: #2c3e50 !important;
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
button:focus,
input:focus,
select:focus,
textarea:focus {
  outline: 2px solid #3498db;
  outline-offset: 2px;
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .testimonial-card,
  .stat-card,
  .modal {
    border: 2px solid #000 !important;
  }

  .btn {
    border: 2px solid currentColor !important;
  }
}

/* Custom Scrollbar */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>