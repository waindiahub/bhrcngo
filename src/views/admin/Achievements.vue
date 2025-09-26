<template>
  <div class="achievements-page">
    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading achievements...</p>
      </div>
    </div>

    <!-- Page Header -->
    <div class="page-header">
      <div class="header-left">
        <h1><i class="fas fa-trophy"></i> Achievements Management</h1>
        <p>Manage member achievements, awards, and recognitions</p>
      </div>
      <div class="header-actions">
        <button @click="refreshData" class="btn btn-outline-primary" :disabled="loading">
          <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
          Refresh
        </button>
        <button @click="showAddModal = true" class="btn btn-primary">
          <i class="fas fa-plus"></i>
          Add Achievement
        </button>
      </div>
    </div>

    <!-- Statistics Overview -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-trophy"></i>
        </div>
        <div class="stat-content">
          <h3>{{ statistics.total || 0 }}</h3>
          <p>Total Achievements</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon award">
          <i class="fas fa-award"></i>
        </div>
        <div class="stat-content">
          <h3>{{ statistics.awards || 0 }}</h3>
          <p>Awards</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon certificate">
          <i class="fas fa-certificate"></i>
        </div>
        <div class="stat-content">
          <h3>{{ statistics.certificates || 0 }}</h3>
          <p>Certificates</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon recognition">
          <i class="fas fa-medal"></i>
        </div>
        <div class="stat-content">
          <h3>{{ statistics.recognitions || 0 }}</h3>
          <p>Recognitions</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon milestone">
          <i class="fas fa-flag-checkered"></i>
        </div>
        <div class="stat-content">
          <h3>{{ statistics.milestones || 0 }}</h3>
          <p>Milestones</p>
        </div>
      </div>
    </div>

    <!-- View Toggle and Filters -->
    <div class="filters-section">
      <div class="view-toggle">
        <button 
          @click="currentView = 'grid'" 
          :class="['view-btn', { active: currentView === 'grid' }]"
        >
          <i class="fas fa-th-large"></i> Grid View
        </button>
        <button 
          @click="currentView = 'timeline'" 
          :class="['view-btn', { active: currentView === 'timeline' }]"
        >
          <i class="fas fa-stream"></i> Timeline View
        </button>
      </div>

      <div class="filters-row">
        <div class="filter-group">
          <label>Category</label>
          <select v-model="filters.category" @change="applyFilters" class="form-select">
            <option value="">All Categories</option>
            <option value="award">Awards</option>
            <option value="certificate">Certificates</option>
            <option value="recognition">Recognitions</option>
            <option value="milestone">Milestones</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Year</label>
          <select v-model="filters.year" @change="applyFilters" class="form-select">
            <option value="">All Years</option>
            <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Search</label>
          <input 
            v-model="filters.search" 
            @input="debouncedSearch"
            type="text" 
            class="form-control" 
            placeholder="Search achievements..."
          >
        </div>
        <div class="filter-group">
          <label>&nbsp;</label>
          <button @click="clearFilters" class="btn btn-outline-secondary">
            <i class="fas fa-times"></i> Clear
          </button>
        </div>
      </div>
    </div>

    <!-- Grid View -->
    <div v-if="currentView === 'grid'" class="achievements-grid">
      <div v-for="achievement in paginatedAchievements" :key="achievement.id" class="achievement-card">
        <div class="achievement-header">
          <div :class="['achievement-icon', achievement.category]">
            <i :class="getCategoryIcon(achievement.category)"></i>
          </div>
          <div class="achievement-meta">
            <span class="achievement-date">
              <i class="fas fa-calendar"></i>
              {{ formatDate(achievement.date_achieved) }}
            </span>
            <span :class="['achievement-category', `category-${achievement.category}`]">
              {{ achievement.category }}
            </span>
          </div>
        </div>
        
        <div class="achievement-content">
          <h4>{{ achievement.title }}</h4>
          <p>{{ truncateText(achievement.description, 120) }}</p>
          
          <div v-if="achievement.awarded_by" class="awarded-by">
            <i class="fas fa-building"></i>
            <span>{{ achievement.awarded_by }}</span>
          </div>
          
          <div v-if="achievement.location" class="location">
            <i class="fas fa-map-marker-alt"></i>
            <span>{{ achievement.location }}</span>
          </div>
          
          <div v-if="achievement.impact_score" class="impact-score">
            <span>Impact Score:</span>
            <div class="stars">
              <span v-html="renderStars(achievement.impact_score)"></span>
              <span class="score-text">({{ achievement.impact_score }}/10)</span>
            </div>
          </div>
        </div>
        
        <div class="achievement-actions">
          <button @click="viewAchievement(achievement)" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-eye"></i> View
          </button>
          <button @click="editAchievement(achievement)" class="btn btn-sm btn-outline-warning">
            <i class="fas fa-edit"></i> Edit
          </button>
          <button @click="deleteAchievement(achievement.id)" class="btn btn-sm btn-outline-danger">
            <i class="fas fa-trash"></i> Delete
          </button>
          <span v-if="achievement.is_featured" class="badge bg-warning ms-2">Featured</span>
        </div>
      </div>
    </div>

    <!-- Timeline View -->
    <div v-if="currentView === 'timeline'" class="timeline-view">
      <div v-for="achievement in filteredAchievements" :key="achievement.id" class="timeline-item">
        <div class="timeline-content">
          <div class="timeline-header">
            <h4>{{ achievement.title }}</h4>
            <span class="timeline-date">{{ formatDate(achievement.date_achieved) }}</span>
          </div>
          <p>{{ achievement.description }}</p>
          <div class="timeline-meta">
            <span :class="['category-badge', `category-${achievement.category}`]">
              {{ achievement.category }}
            </span>
            <span v-if="achievement.awarded_by" class="awarded-by">
              by {{ achievement.awarded_by }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="currentView === 'grid' && totalPages > 1" class="pagination-wrapper">
      <nav>
        <ul class="pagination">
          <li :class="['page-item', { disabled: currentPage === 1 }]">
            <button @click="changePage(currentPage - 1)" class="page-link">Previous</button>
          </li>
          <li 
            v-for="page in visiblePages" 
            :key="page" 
            :class="['page-item', { active: page === currentPage }]"
          >
            <button @click="changePage(page)" class="page-link">{{ page }}</button>
          </li>
          <li :class="['page-item', { disabled: currentPage === totalPages }]">
            <button @click="changePage(currentPage + 1)" class="page-link">Next</button>
          </li>
        </ul>
      </nav>
    </div>

    <!-- Add Achievement Modal -->
    <div v-if="showAddModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3><i class="fas fa-plus"></i> Add New Achievement</h3>
          <button @click="showAddModal = false" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveAchievement">
            <div class="form-row">
              <div class="form-group">
                <label>Title *</label>
                <input v-model="newAchievement.title" type="text" class="form-control" required>
              </div>
              <div class="form-group">
                <label>Category *</label>
                <select v-model="newAchievement.category" class="form-select" required>
                  <option value="">Select Category</option>
                  <option value="award">Award</option>
                  <option value="certificate">Certificate</option>
                  <option value="recognition">Recognition</option>
                  <option value="milestone">Milestone</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label>Description *</label>
              <textarea v-model="newAchievement.description" class="form-control" rows="4" required></textarea>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Date Achieved *</label>
                <input v-model="newAchievement.date_achieved" type="date" class="form-control" required>
              </div>
              <div class="form-group">
                <label>Awarded By</label>
                <input v-model="newAchievement.awarded_by" type="text" class="form-control">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Location</label>
                <input v-model="newAchievement.location" type="text" class="form-control">
              </div>
              <div class="form-group">
                <label>Impact Score (1-10)</label>
                <input v-model="newAchievement.impact_score" type="number" min="1" max="10" step="0.1" class="form-control">
              </div>
            </div>
            <div class="form-group">
              <label>Related URL</label>
              <input v-model="newAchievement.related_url" type="url" class="form-control">
            </div>
            <div class="form-group">
              <label>Tags (comma separated)</label>
              <input v-model="newAchievement.tags" type="text" class="form-control" placeholder="tag1, tag2, tag3">
            </div>
            <div class="form-group">
              <label class="checkbox-label">
                <input v-model="newAchievement.is_featured" type="checkbox">
                <span class="checkmark"></span>
                Featured Achievement
              </label>
            </div>
            <div class="modal-actions">
              <button type="button" @click="showAddModal = false" class="btn btn-secondary">Cancel</button>
              <button type="submit" class="btn btn-primary" :disabled="saving">
                <i class="fas fa-save"></i>
                {{ saving ? 'Saving...' : 'Save Achievement' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Edit Achievement Modal -->
    <div v-if="showEditModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3><i class="fas fa-edit"></i> Edit Achievement</h3>
          <button @click="showEditModal = false" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="updateAchievement">
            <div class="form-row">
              <div class="form-group">
                <label>Title *</label>
                <input v-model="editingAchievement.title" type="text" class="form-control" required>
              </div>
              <div class="form-group">
                <label>Category *</label>
                <select v-model="editingAchievement.category" class="form-select" required>
                  <option value="">Select Category</option>
                  <option value="award">Award</option>
                  <option value="certificate">Certificate</option>
                  <option value="recognition">Recognition</option>
                  <option value="milestone">Milestone</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label>Description *</label>
              <textarea v-model="editingAchievement.description" class="form-control" rows="4" required></textarea>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Date Achieved *</label>
                <input v-model="editingAchievement.date_achieved" type="date" class="form-control" required>
              </div>
              <div class="form-group">
                <label>Awarded By</label>
                <input v-model="editingAchievement.awarded_by" type="text" class="form-control">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Location</label>
                <input v-model="editingAchievement.location" type="text" class="form-control">
              </div>
              <div class="form-group">
                <label>Impact Score (1-10)</label>
                <input v-model="editingAchievement.impact_score" type="number" min="1" max="10" step="0.1" class="form-control">
              </div>
            </div>
            <div class="form-group">
              <label>Related URL</label>
              <input v-model="editingAchievement.related_url" type="url" class="form-control">
            </div>
            <div class="form-group">
              <label>Tags (comma separated)</label>
              <input v-model="editingAchievement.tags" type="text" class="form-control" placeholder="tag1, tag2, tag3">
            </div>
            <div class="form-group">
              <label class="checkbox-label">
                <input v-model="editingAchievement.is_featured" type="checkbox">
                <span class="checkmark"></span>
                Featured Achievement
              </label>
            </div>
            <div class="modal-actions">
              <button type="button" @click="showEditModal = false" class="btn btn-secondary">Cancel</button>
              <button type="submit" class="btn btn-primary" :disabled="saving">
                <i class="fas fa-save"></i>
                {{ saving ? 'Updating...' : 'Update Achievement' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- View Achievement Modal -->
    <div v-if="showViewModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content large" @click.stop>
        <div class="modal-header">
          <h3><i class="fas fa-trophy"></i> Achievement Details</h3>
          <button @click="showViewModal = false" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="viewingAchievement" class="achievement-details">
            <div class="detail-header">
              <div :class="['detail-icon', viewingAchievement.category]">
                <i :class="getCategoryIcon(viewingAchievement.category)"></i>
              </div>
              <div class="detail-info">
                <h2>{{ viewingAchievement.title }}</h2>
                <span :class="['category-badge', `category-${viewingAchievement.category}`]">
                  {{ viewingAchievement.category }}
                </span>
                <span v-if="viewingAchievement.is_featured" class="badge bg-warning ms-2">Featured</span>
              </div>
            </div>
            
            <div class="detail-content">
              <div class="detail-section">
                <h4>Description</h4>
                <p>{{ viewingAchievement.description }}</p>
              </div>
              
              <div class="detail-grid">
                <div class="detail-item">
                  <label>Date Achieved</label>
                  <span>{{ formatDate(viewingAchievement.date_achieved) }}</span>
                </div>
                <div v-if="viewingAchievement.awarded_by" class="detail-item">
                  <label>Awarded By</label>
                  <span>{{ viewingAchievement.awarded_by }}</span>
                </div>
                <div v-if="viewingAchievement.location" class="detail-item">
                  <label>Location</label>
                  <span>{{ viewingAchievement.location }}</span>
                </div>
                <div v-if="viewingAchievement.impact_score" class="detail-item">
                  <label>Impact Score</label>
                  <div class="stars">
                    <span v-html="renderStars(viewingAchievement.impact_score)"></span>
                    <span class="score-text">({{ viewingAchievement.impact_score }}/10)</span>
                  </div>
                </div>
              </div>
              
              <div v-if="viewingAchievement.tags" class="detail-section">
                <h4>Tags</h4>
                <div class="tags">
                  <span v-for="tag in viewingAchievement.tags.split(',')" :key="tag.trim()" class="tag">
                    {{ tag.trim() }}
                  </span>
                </div>
              </div>
              
              <div v-if="viewingAchievement.related_url" class="detail-section">
                <h4>Related Link</h4>
                <a :href="viewingAchievement.related_url" target="_blank" class="btn btn-outline-primary">
                  <i class="fas fa-external-link-alt"></i> View Link
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Toast -->
    <div v-if="showToast" class="toast-notification" :class="toastType">
      <i :class="toastIcon"></i>
      <span>{{ toastMessage }}</span>
      <button @click="showToast = false" class="toast-close">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue'

export default {
  name: 'Achievements',
  setup() {
    // Reactive data
    const loading = ref(false)
    const saving = ref(false)
    const achievements = ref([])
    const filteredAchievements = ref([])
    const statistics = ref({})
    const currentView = ref('grid')
    const currentPage = ref(1)
    const itemsPerPage = ref(12)
    
    // Modals
    const showAddModal = ref(false)
    const showEditModal = ref(false)
    const showViewModal = ref(false)
    const viewingAchievement = ref(null)
    const editingAchievement = ref({})
    
    // Toast
    const showToast = ref(false)
    const toastMessage = ref('')
    const toastType = ref('success')
    
    // Filters
    const filters = reactive({
      category: '',
      year: '',
      search: ''
    })
    
    // New achievement form
    const newAchievement = reactive({
      title: '',
      category: '',
      description: '',
      date_achieved: '',
      awarded_by: '',
      location: '',
      impact_score: '',
      related_url: '',
      tags: '',
      is_featured: false
    })

    // Database configuration
    const DB_CONFIG = {
      host: '37.27.60.109',
      database: 'tzdmiohj_bhmc',
      username: 'tzdmiohj_bhmc',
      password: 'tzdmiohj_bhmc'
    }

    // API base URL
    const API_BASE_URL = 'https://bhrcdata.online/backend'

    // Computed properties
    const availableYears = computed(() => {
      const years = [...new Set(achievements.value.map(a => new Date(a.date_achieved).getFullYear()))]
      return years.sort((a, b) => b - a)
    })

    const totalPages = computed(() => {
      return Math.ceil(filteredAchievements.value.length / itemsPerPage.value)
    })

    const paginatedAchievements = computed(() => {
      if (currentView.value === 'timeline') return filteredAchievements.value
      
      const start = (currentPage.value - 1) * itemsPerPage.value
      const end = start + itemsPerPage.value
      return filteredAchievements.value.slice(start, end)
    })

    const visiblePages = computed(() => {
      const pages = []
      const total = totalPages.value
      const current = currentPage.value
      
      for (let i = 1; i <= total; i++) {
        if (i === 1 || i === total || (i >= current - 2 && i <= current + 2)) {
          pages.push(i)
        }
      }
      return pages
    })

    const toastIcon = computed(() => {
      const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
      }
      return icons[toastType.value] || icons.info
    })

    // Methods
    const loadAchievements = async () => {
      loading.value = true
      try {
        const response = await fetch(`${API_BASE_URL}/achievements.php`)
        const data = await response.json()
        
        if (data.success) {
          achievements.value = data.data || []
          filteredAchievements.value = [...achievements.value]
        } else {
          throw new Error(data.message || 'Failed to load achievements')
        }
      } catch (error) {
        console.error('Error loading achievements:', error)
        showToastMessage('Error loading achievements: ' + error.message, 'error')
        achievements.value = []
        filteredAchievements.value = []
      } finally {
        loading.value = false
      }
    }

    const loadStatistics = async () => {
      try {
        const response = await fetch(`${API_BASE_URL}/achievements-stats.php`)
        const data = await response.json()
        
        if (data.success) {
          statistics.value = data.data || {}
        }
      } catch (error) {
        console.error('Error loading statistics:', error)
      }
    }

    const refreshData = async () => {
      await Promise.all([loadAchievements(), loadStatistics()])
    }

    const applyFilters = () => {
      let filtered = [...achievements.value]

      // Category filter
      if (filters.category) {
        filtered = filtered.filter(a => a.category === filters.category)
      }

      // Year filter
      if (filters.year) {
        filtered = filtered.filter(a => new Date(a.date_achieved).getFullYear() == filters.year)
      }

      // Search filter
      if (filters.search) {
        const searchTerm = filters.search.toLowerCase()
        filtered = filtered.filter(a => {
          const searchableText = `${a.title} ${a.description} ${a.awarded_by || ''} ${a.location || ''}`.toLowerCase()
          return searchableText.includes(searchTerm)
        })
      }

      filteredAchievements.value = filtered
      currentPage.value = 1
    }

    const clearFilters = () => {
      filters.category = ''
      filters.year = ''
      filters.search = ''
      applyFilters()
    }

    const debouncedSearch = debounce(() => {
      applyFilters()
    }, 300)

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
      }
    }

    const saveAchievement = async () => {
      saving.value = true
      try {
        const formData = new FormData()
        Object.keys(newAchievement).forEach(key => {
          formData.append(key, newAchievement[key])
        })

        const response = await fetch(`${API_BASE_URL}/achievements.php`, {
          method: 'POST',
          body: formData
        })
        
        const data = await response.json()
        
        if (data.success) {
          showToastMessage('Achievement created successfully!', 'success')
          showAddModal.value = false
          resetNewAchievement()
          await refreshData()
        } else {
          throw new Error(data.message || 'Failed to create achievement')
        }
      } catch (error) {
        console.error('Error saving achievement:', error)
        showToastMessage('Error creating achievement: ' + error.message, 'error')
      } finally {
        saving.value = false
      }
    }

    const editAchievement = (achievement) => {
      editingAchievement.value = { ...achievement }
      showEditModal.value = true
    }

    const updateAchievement = async () => {
      saving.value = true
      try {
        const formData = new FormData()
        Object.keys(editingAchievement.value).forEach(key => {
          formData.append(key, editingAchievement.value[key])
        })

        const response = await fetch(`${API_BASE_URL}/achievements.php?id=${editingAchievement.value.id}`, {
          method: 'POST',
          body: formData
        })
        
        const data = await response.json()
        
        if (data.success) {
          showToastMessage('Achievement updated successfully!', 'success')
          showEditModal.value = false
          await refreshData()
        } else {
          throw new Error(data.message || 'Failed to update achievement')
        }
      } catch (error) {
        console.error('Error updating achievement:', error)
        showToastMessage('Error updating achievement: ' + error.message, 'error')
      } finally {
        saving.value = false
      }
    }

    const deleteAchievement = async (id) => {
      if (!confirm('Are you sure you want to delete this achievement? This action cannot be undone.')) {
        return
      }

      try {
        const response = await fetch(`${API_BASE_URL}/achievements.php?id=${id}&action=delete`, {
          method: 'DELETE'
        })
        
        const data = await response.json()
        
        if (data.success) {
          showToastMessage('Achievement deleted successfully!', 'success')
          await refreshData()
        } else {
          throw new Error(data.message || 'Failed to delete achievement')
        }
      } catch (error) {
        console.error('Error deleting achievement:', error)
        showToastMessage('Error deleting achievement: ' + error.message, 'error')
      }
    }

    const viewAchievement = (achievement) => {
      viewingAchievement.value = achievement
      showViewModal.value = true
    }

    const closeModal = () => {
      showAddModal.value = false
      showEditModal.value = false
      showViewModal.value = false
    }

    const resetNewAchievement = () => {
      Object.keys(newAchievement).forEach(key => {
        if (typeof newAchievement[key] === 'boolean') {
          newAchievement[key] = false
        } else {
          newAchievement[key] = ''
        }
      })
    }

    const showToastMessage = (message, type = 'info') => {
      toastMessage.value = message
      toastType.value = type
      showToast.value = true
      
      setTimeout(() => {
        showToast.value = false
      }, 5000)
    }

    // Utility functions
    const getCategoryIcon = (category) => {
      const icons = {
        award: 'fas fa-trophy',
        certificate: 'fas fa-certificate',
        recognition: 'fas fa-medal',
        milestone: 'fas fa-flag-checkered'
      }
      return icons[category] || 'fas fa-star'
    }

    const renderStars = (score) => {
      const fullStars = Math.floor(score)
      const halfStar = score % 1 >= 0.5
      let starsHTML = ''
      
      for (let i = 0; i < fullStars; i++) {
        starsHTML += '<i class="fas fa-star text-warning"></i>'
      }
      
      if (halfStar) {
        starsHTML += '<i class="fas fa-star-half-alt text-warning"></i>'
      }
      
      const emptyStars = 10 - Math.ceil(score)
      for (let i = 0; i < emptyStars; i++) {
        starsHTML += '<i class="far fa-star text-muted"></i>'
      }
      
      return starsHTML
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }

    const truncateText = (text, maxLength) => {
      return text && text.length > maxLength ? text.substring(0, maxLength) + '...' : text
    }

    function debounce(func, wait) {
      let timeout
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout)
          func(...args)
        }
        clearTimeout(timeout)
        timeout = setTimeout(later, wait)
      }
    }

    // Watchers
    watch(() => filters.search, debouncedSearch)

    // Lifecycle
    onMounted(() => {
      refreshData()
    })

    return {
      // Data
      loading,
      saving,
      achievements,
      filteredAchievements,
      statistics,
      currentView,
      currentPage,
      itemsPerPage,
      showAddModal,
      showEditModal,
      showViewModal,
      viewingAchievement,
      editingAchievement,
      showToast,
      toastMessage,
      toastType,
      filters,
      newAchievement,
      
      // Computed
      availableYears,
      totalPages,
      paginatedAchievements,
      visiblePages,
      toastIcon,
      
      // Methods
      refreshData,
      applyFilters,
      clearFilters,
      debouncedSearch,
      changePage,
      saveAchievement,
      editAchievement,
      updateAchievement,
      deleteAchievement,
      viewAchievement,
      closeModal,
      showToastMessage,
      getCategoryIcon,
      renderStars,
      formatDate,
      truncateText
    }
  }
}
</script>

<style scoped>
.achievements-page {
  padding: 20px;
  background: #f8f9fa;
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
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.loading-spinner {
  text-align: center;
  color: #007bff;
}

.loading-spinner i {
  font-size: 3rem;
  margin-bottom: 1rem;
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 30px;
  background: white;
  padding: 25px;
  border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.header-left h1 {
  margin: 0 0 5px 0;
  color: #2c3e50;
  font-size: 1.8rem;
  font-weight: 600;
}

.header-left p {
  margin: 0;
  color: #6c757d;
  font-size: 0.95rem;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.btn {
  padding: 10px 20px;
  border-radius: 8px;
  border: none;
  font-weight: 500;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 0.9rem;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-outline-primary {
  background: white;
  color: #007bff;
  border: 2px solid #007bff;
}

.btn-outline-primary:hover {
  background: #007bff;
  color: white;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-outline-secondary {
  background: white;
  color: #6c757d;
  border: 2px solid #6c757d;
}

.btn-outline-warning {
  background: white;
  color: #ffc107;
  border: 2px solid #ffc107;
}

.btn-outline-warning:hover {
  background: #ffc107;
  color: #212529;
}

.btn-outline-danger {
  background: white;
  color: #dc3545;
  border: 2px solid #dc3545;
}

.btn-outline-danger:hover {
  background: #dc3545;
  color: white;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 0.8rem;
}

/* Statistics Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  padding: 25px;
  border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  display: flex;
  align-items: center;
  gap: 20px;
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon.award {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-icon.certificate {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-icon.recognition {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-icon.milestone {
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.stat-content h3 {
  margin: 0 0 5px 0;
  font-size: 2rem;
  font-weight: 700;
  color: #2c3e50;
}

.stat-content p {
  margin: 0;
  color: #6c757d;
  font-size: 0.9rem;
  font-weight: 500;
}

/* Filters Section */
.filters-section {
  background: white;
  padding: 25px;
  border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  margin-bottom: 30px;
}

.view-toggle {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.view-btn {
  padding: 10px 20px;
  border: 2px solid #dee2e6;
  background: white;
  border-radius: 25px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 500;
  color: #6c757d;
}

.view-btn.active {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.view-btn:hover:not(.active) {
  border-color: #007bff;
  color: #007bff;
}

.filters-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  align-items: end;
}

.filter-group {
  display: flex;
  flex-direction: column;
}

.filter-group label {
  margin-bottom: 8px;
  font-weight: 600;
  color: #2c3e50;
  font-size: 0.9rem;
}

.form-control, .form-select {
  padding: 12px 15px;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: all 0.3s ease;
  background: white;
}

.form-control:focus, .form-select:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

/* Achievements Grid */
.achievements-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 25px;
  margin-bottom: 30px;
}

.achievement-card {
  background: white;
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 5px 15px rgba(0,0,0,0.08);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.achievement-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.achievement-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #007bff, #28a745, #ffc107, #dc3545);
}

.achievement-header {
  display: flex;
  align-items: flex-start;
  gap: 15px;
  margin-bottom: 20px;
}

.achievement-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  flex-shrink: 0;
}

.achievement-icon.award {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.achievement-icon.certificate {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.achievement-icon.recognition {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.achievement-icon.milestone {
  background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.achievement-meta {
  flex: 1;
}

.achievement-date {
  display: flex;
  align-items: center;
  gap: 5px;
  color: #6c757d;
  font-size: 0.85rem;
  margin-bottom: 8px;
}

.achievement-category {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.category-award {
  background: #fff3cd;
  color: #856404;
}

.category-certificate {
  background: #d1ecf1;
  color: #0c5460;
}

.category-recognition {
  background: #d4edda;
  color: #155724;
}

.category-milestone {
  background: #f8d7da;
  color: #721c24;
}

.achievement-content h4 {
  margin: 0 0 10px 0;
  color: #2c3e50;
  font-size: 1.2rem;
  font-weight: 600;
}

.achievement-content p {
  margin: 0 0 15px 0;
  color: #6c757d;
  line-height: 1.5;
}

.awarded-by, .location {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 0.85rem;
  color: #6c757d;
}

.impact-score {
  margin-top: 15px;
}

.impact-score span:first-child {
  font-weight: 600;
  color: #2c3e50;
  margin-right: 10px;
}

.stars {
  display: flex;
  align-items: center;
  gap: 5px;
}

.score-text {
  font-size: 0.85rem;
  color: #6c757d;
}

.achievement-actions {
  display: flex;
  gap: 8px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e9ecef;
  align-items: center;
}

.badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 600;
}

.bg-warning {
  background-color: #ffc107 !important;
  color: #212529;
}

/* Timeline View */
.timeline-view {
  position: relative;
  padding-left: 30px;
}

.timeline-view::before {
  content: '';
  position: absolute;
  left: 15px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: linear-gradient(to bottom, #007bff, #28a745);
}

.timeline-item {
  position: relative;
  margin-bottom: 30px;
  background: white;
  padding: 25px;
  border-radius: 15px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.timeline-item::before {
  content: '';
  position: absolute;
  left: -37px;
  top: 25px;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #007bff;
  border: 3px solid white;
  box-shadow: 0 0 0 3px #007bff;
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 15px;
}

.timeline-header h4 {
  margin: 0;
  color: #2c3e50;
  font-size: 1.3rem;
  font-weight: 600;
}

.timeline-date {
  color: #6c757d;
  font-size: 0.9rem;
  font-weight: 500;
}

.timeline-content p {
  margin: 0 0 15px 0;
  color: #6c757d;
  line-height: 1.6;
}

.timeline-meta {
  display: flex;
  gap: 15px;
  align-items: center;
}

.category-badge {
  padding: 6px 15px;
  border-radius: 25px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Pagination */
.pagination-wrapper {
  display: flex;
  justify-content: center;
  margin-top: 30px;
}

.pagination {
  display: flex;
  list-style: none;
  padding: 0;
  margin: 0;
  gap: 5px;
}

.page-item {
  display: flex;
}

.page-link {
  padding: 10px 15px;
  background: white;
  border: 2px solid #dee2e6;
  color: #007bff;
  text-decoration: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 500;
}

.page-link:hover:not(.disabled) {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.page-item.active .page-link {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.page-item.disabled .page-link {
  color: #6c757d;
  cursor: not-allowed;
  opacity: 0.5;
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
  padding: 20px;
}

.modal-content {
  background: white;
  border-radius: 15px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-content.large {
  max-width: 800px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 25px;
  border-bottom: 1px solid #e9ecef;
}

.modal-header h3 {
  margin: 0;
  color: #2c3e50;
  font-size: 1.4rem;
  font-weight: 600;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.2rem;
  color: #6c757d;
  cursor: pointer;
  padding: 5px;
  border-radius: 50%;
  width: 35px;
  height: 35px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.btn-close:hover {
  background: #f8f9fa;
  color: #dc3545;
}

.modal-body {
  padding: 25px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #2c3e50;
  font-size: 0.9rem;
}

.form-control, .form-select {
  width: 100%;
  padding: 12px 15px;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

textarea.form-control {
  resize: vertical;
  min-height: 100px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  font-weight: 500;
}

.checkbox-label input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: #007bff;
}

.modal-actions {
  display: flex;
  gap: 15px;
  justify-content: flex-end;
  margin-top: 30px;
  padding-top: 20px;
  border-top: 1px solid #e9ecef;
}

/* Achievement Details */
.achievement-details {
  max-width: 100%;
}

.detail-header {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e9ecef;
}

.detail-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  color: white;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  flex-shrink: 0;
}

.detail-info h2 {
  margin: 0 0 10px 0;
  color: #2c3e50;
  font-size: 1.6rem;
  font-weight: 600;
}

.detail-section {
  margin-bottom: 25px;
}

.detail-section h4 {
  margin: 0 0 15px 0;
  color: #2c3e50;
  font-size: 1.1rem;
  font-weight: 600;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 25px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.detail-item label {
  font-weight: 600;
  color: #2c3e50;
  font-size: 0.9rem;
}

.detail-item span {
  color: #6c757d;
}

.tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.tag {
  padding: 6px 12px;
  background: #e9ecef;
  color: #495057;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
}

/* Toast Notification */
.toast-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  padding: 15px 20px;
  border-radius: 8px;
  color: white;
  display: flex;
  align-items: center;
  gap: 10px;
  z-index: 1100;
  min-width: 300px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  animation: slideIn 0.3s ease;
}

.toast-notification.success {
  background: #28a745;
}

.toast-notification.error {
  background: #dc3545;
}

.toast-notification.warning {
  background: #ffc107;
  color: #212529;
}

.toast-notification.info {
  background: #17a2b8;
}

.toast-close {
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  padding: 0;
  margin-left: auto;
  opacity: 0.8;
}

.toast-close:hover {
  opacity: 1;
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
  .achievements-page {
    padding: 15px;
  }
  
  .page-header {
    flex-direction: column;
    gap: 20px;
    align-items: stretch;
  }
  
  .header-actions {
    justify-content: stretch;
  }
  
  .header-actions .btn {
    flex: 1;
    justify-content: center;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .achievements-grid {
    grid-template-columns: 1fr;
  }
  
  .filters-row {
    grid-template-columns: 1fr;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .modal-content {
    margin: 10px;
    max-height: calc(100vh - 20px);
  }
  
  .detail-header {
    flex-direction: column;
    text-align: center;
  }
  
  .detail-grid {
    grid-template-columns: 1fr;
  }
  
  .timeline-view {
    padding-left: 20px;
  }
  
  .timeline-item::before {
    left: -27px;
  }
}

@media (max-width: 480px) {
  .achievement-header {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }
  
  .achievement-actions {
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .view-toggle {
    flex-direction: column;
  }
  
  .toast-notification {
    left: 10px;
    right: 10px;
    min-width: auto;
  }
}

/* Print Styles */
@media print {
  .achievements-page {
    background: white;
    padding: 0;
  }
  
  .page-header,
  .filters-section,
  .pagination-wrapper,
  .achievement-actions,
  .header-actions {
    display: none !important;
  }
  
  .achievement-card {
    break-inside: avoid;
    box-shadow: none;
    border: 1px solid #dee2e6;
    margin-bottom: 20px;
  }
  
  .stats-grid {
    display: none;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .achievements-page {
    background: #1a1a1a;
    color: #e9ecef;
  }
  
  .page-header,
  .filters-section,
  .achievement-card,
  .timeline-item,
  .stat-card {
    background: #2d3748;
    color: #e9ecef;
  }
  
  .form-control,
  .form-select {
    background: #4a5568;
    border-color: #718096;
    color: #e9ecef;
  }
  
  .modal-content {
    background: #2d3748;
    color: #e9ecef;
  }
}
</style>