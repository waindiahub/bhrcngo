<template>
  <div class="gallery-page">
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <div class="hero-content">
              <h1 class="hero-title">Gallery</h1>
              <p class="hero-subtitle">
                Explore our visual journey through photos and videos documenting our human rights advocacy work and community impact.
              </p>
              <div class="hero-stats">
                <div class="row">
                  <div class="col-4">
                    <div class="stat-item">
                      <div class="stat-number">{{ stats.totalPhotos }}+</div>
                      <div class="stat-label">Photos</div>
                    </div>
                  </div>
                  <div class="col-4">
                    <div class="stat-item">
                      <div class="stat-number">{{ stats.totalVideos }}+</div>
                      <div class="stat-label">Videos</div>
                    </div>
                  </div>
                  <div class="col-4">
                    <div class="stat-item">
                      <div class="stat-number">{{ stats.totalAlbums }}</div>
                      <div class="stat-label">Albums</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="hero-image">
              <img src="/images/gallery-hero.jpg" alt="BHRC Gallery" class="img-fluid rounded-lg">
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Filters Section -->
    <section class="filters-section py-4 bg-light">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-4">
            <div class="search-box">
              <div class="input-group">
                <span class="input-group-text">
                  <i class="fas fa-search"></i>
                </span>
                <input 
                  type="text" 
                  class="form-control" 
                  placeholder="Search gallery..."
                  v-model="searchQuery"
                  @input="handleSearch"
                >
              </div>
            </div>
          </div>
          <div class="col-lg-8">
            <div class="filter-controls">
              <div class="row">
                <div class="col-md-3">
                  <div class="btn-group w-100" role="group">
                    <button 
                      type="button" 
                      class="btn btn-outline-primary"
                      :class="{ active: activeTab === 'all' }"
                      @click="setActiveTab('all')"
                    >
                      <i class="fas fa-th me-1"></i>
                      All
                    </button>
                    <button 
                      type="button" 
                      class="btn btn-outline-primary"
                      :class="{ active: activeTab === 'photos' }"
                      @click="setActiveTab('photos')"
                    >
                      <i class="fas fa-image me-1"></i>
                      Photos
                    </button>
                    <button 
                      type="button" 
                      class="btn btn-outline-primary"
                      :class="{ active: activeTab === 'videos' }"
                      @click="setActiveTab('videos')"
                    >
                      <i class="fas fa-video me-1"></i>
                      Videos
                    </button>
                  </div>
                </div>
                <div class="col-md-3">
                  <select v-model="filters.category" @change="applyFilters" class="form-select">
                    <option value="">All Categories</option>
                    <option value="events">Events</option>
                    <option value="workshops">Workshops</option>
                    <option value="meetings">Meetings</option>
                    <option value="campaigns">Campaigns</option>
                    <option value="awards">Awards</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <select v-model="filters.year" @change="applyFilters" class="form-select">
                    <option value="">All Years</option>
                    <option value="2024">2024</option>
                    <option value="2023">2023</option>
                    <option value="2022">2022</option>
                    <option value="2021">2021</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <select v-model="sortBy" @change="applySorting" class="form-select">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="title">Title A-Z</option>
                    <option value="popular">Most Popular</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Gallery Section -->
    <section class="gallery-section py-5">
      <div class="container">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-3">Loading gallery...</p>
        </div>

        <!-- No Items Found -->
        <div v-else-if="filteredItems.length === 0" class="text-center py-5">
          <i class="fas fa-images fa-3x text-muted mb-3"></i>
          <h4>No Items Found</h4>
          <p class="text-muted">Try adjusting your search or filter criteria.</p>
          <button class="btn btn-primary" @click="clearFilters">
            <i class="fas fa-refresh me-2"></i>
            Clear Filters
          </button>
        </div>

        <!-- Gallery Grid -->
        <div v-else class="gallery-grid">
          <div class="row g-4">
            <div 
              class="col-lg-4 col-md-6" 
              v-for="item in paginatedItems" 
              :key="item.id"
            >
              <div class="gallery-item" @click="openLightbox(item)">
                <div class="item-image">
                  <img 
                    :src="item.thumbnail || item.image" 
                    :alt="item.title" 
                    class="img-fluid"
                    loading="lazy"
                  >
                  <div class="item-overlay">
                    <div class="overlay-content">
                      <i v-if="item.type === 'photo'" class="fas fa-search-plus"></i>
                      <i v-else class="fas fa-play"></i>
                    </div>
                  </div>
                  <div class="item-type">
                    <i v-if="item.type === 'photo'" class="fas fa-image"></i>
                    <i v-else class="fas fa-video"></i>
                  </div>
                </div>
                <div class="item-content">
                  <h6>{{ item.title }}</h6>
                  <p class="item-description">{{ truncateText(item.description, 80) }}</p>
                  <div class="item-meta">
                    <span class="meta-item">
                      <i class="fas fa-calendar me-1"></i>
                      {{ formatDate(item.date) }}
                    </span>
                    <span class="meta-item">
                      <i class="fas fa-tag me-1"></i>
                      {{ item.category }}
                    </span>
                  </div>
                  <div class="item-stats">
                    <span class="stat-item">
                      <i class="fas fa-eye me-1"></i>
                      {{ item.views }}
                    </span>
                    <span class="stat-item">
                      <i class="fas fa-heart me-1"></i>
                      {{ item.likes }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="pagination-wrapper mt-5">
            <nav aria-label="Gallery pagination">
              <ul class="pagination justify-content-center">
                <li class="page-item" :class="{ disabled: currentPage === 1 }">
                  <button class="page-link" @click="changePage(currentPage - 1)" :disabled="currentPage === 1">
                    <i class="fas fa-chevron-left"></i>
                  </button>
                </li>
                <li 
                  class="page-item" 
                  :class="{ active: page === currentPage }"
                  v-for="page in visiblePages" 
                  :key="page"
                >
                  <button class="page-link" @click="changePage(page)">{{ page }}</button>
                </li>
                <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                  <button class="page-link" @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages">
                    <i class="fas fa-chevron-right"></i>
                  </button>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      </div>
    </section>

    <!-- Lightbox Modal -->
    <div class="modal fade" id="lightboxModal" tabindex="-1" aria-labelledby="lightboxModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark">
          <div class="modal-header border-0">
            <h5 class="modal-title text-white" id="lightboxModalLabel">
              {{ selectedItem?.title }}
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0">
            <div v-if="selectedItem" class="lightbox-content">
              <!-- Photo Display -->
              <div v-if="selectedItem.type === 'photo'" class="photo-display">
                <img 
                  :src="selectedItem.image" 
                  :alt="selectedItem.title" 
                  class="img-fluid w-100"
                  style="max-height: 70vh; object-fit: contain;"
                >
              </div>
              
              <!-- Video Display -->
              <div v-else class="video-display">
                <video 
                  v-if="selectedItem.video_url"
                  controls 
                  class="w-100"
                  style="max-height: 70vh;"
                  :poster="selectedItem.thumbnail"
                >
                  <source :src="selectedItem.video_url" type="video/mp4">
                  Your browser does not support the video tag.
                </video>
                <div v-else class="video-placeholder">
                  <i class="fas fa-video fa-3x text-muted"></i>
                  <p class="text-muted mt-2">Video not available</p>
                </div>
              </div>
              
              <!-- Item Details -->
              <div class="item-details p-4">
                <div class="row">
                  <div class="col-md-8">
                    <h5 class="text-white mb-3">{{ selectedItem.title }}</h5>
                    <p class="text-light mb-3">{{ selectedItem.description }}</p>
                    <div class="item-meta-detailed">
                      <span class="meta-badge">
                        <i class="fas fa-calendar me-1"></i>
                        {{ formatDate(selectedItem.date) }}
                      </span>
                      <span class="meta-badge">
                        <i class="fas fa-tag me-1"></i>
                        {{ selectedItem.category }}
                      </span>
                      <span class="meta-badge">
                        <i class="fas fa-eye me-1"></i>
                        {{ selectedItem.views }} views
                      </span>
                      <span class="meta-badge">
                        <i class="fas fa-heart me-1"></i>
                        {{ selectedItem.likes }} likes
                      </span>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="item-actions">
                      <button class="btn btn-outline-light btn-sm me-2" @click="likeItem(selectedItem)">
                        <i class="fas fa-heart me-1"></i>
                        Like
                      </button>
                      <button class="btn btn-outline-light btn-sm me-2" @click="shareItem(selectedItem)">
                        <i class="fas fa-share me-1"></i>
                        Share
                      </button>
                      <button class="btn btn-outline-light btn-sm" @click="downloadItem(selectedItem)">
                        <i class="fas fa-download me-1"></i>
                        Download
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 justify-content-between">
            <button 
              class="btn btn-outline-light"
              @click="navigateLightbox('prev')"
              :disabled="!canNavigatePrev"
            >
              <i class="fas fa-chevron-left me-2"></i>
              Previous
            </button>
            <span class="text-light">
              {{ currentItemIndex + 1 }} of {{ filteredItems.length }}
            </span>
            <button 
              class="btn btn-outline-light"
              @click="navigateLightbox('next')"
              :disabled="!canNavigateNext"
            >
              Next
              <i class="fas fa-chevron-right ms-2"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { api } from '@/utils/api'
import { useToast } from 'vue-toastification'
import { dateUtils, stringUtils } from '@/utils/helpers'

export default {
  name: 'Gallery',
  setup() {
    const toast = useToast()

    // Reactive state
    const loading = ref(false)
    const galleryItems = ref([])
    const selectedItem = ref(null)
    const currentItemIndex = ref(0)
    const activeTab = ref('all')
    const currentPage = ref(1)
    const itemsPerPage = ref(12)
    const searchQuery = ref('')
    const sortBy = ref('newest')

    const stats = ref({
      totalPhotos: 250,
      totalVideos: 45,
      totalAlbums: 15
    })

    const filters = reactive({
      category: '',
      year: ''
    })

    // Computed properties
    const filteredItems = computed(() => {
      let filtered = galleryItems.value

      // Tab filter
      if (activeTab.value !== 'all') {
        filtered = filtered.filter(item => {
          if (activeTab.value === 'photos') return item.type === 'photo'
          if (activeTab.value === 'videos') return item.type === 'video'
          return true
        })
      }

      // Search filter
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(item => 
          item.title.toLowerCase().includes(query) ||
          item.description.toLowerCase().includes(query) ||
          item.category.toLowerCase().includes(query)
        )
      }

      // Category filter
      if (filters.category) {
        filtered = filtered.filter(item => item.category === filters.category)
      }

      // Year filter
      if (filters.year) {
        filtered = filtered.filter(item => {
          const itemYear = new Date(item.date).getFullYear().toString()
          return itemYear === filters.year
        })
      }

      // Sorting
      filtered.sort((a, b) => {
        switch (sortBy.value) {
          case 'newest':
            return new Date(b.date) - new Date(a.date)
          case 'oldest':
            return new Date(a.date) - new Date(b.date)
          case 'title':
            return a.title.localeCompare(b.title)
          case 'popular':
            return (b.views + b.likes) - (a.views + a.likes)
          default:
            return 0
        }
      })

      return filtered
    })

    const totalPages = computed(() => {
      return Math.ceil(filteredItems.value.length / itemsPerPage.value)
    })

    const paginatedItems = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage.value
      const end = start + itemsPerPage.value
      return filteredItems.value.slice(start, end)
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

    const canNavigatePrev = computed(() => {
      return currentItemIndex.value > 0
    })

    const canNavigateNext = computed(() => {
      return currentItemIndex.value < filteredItems.value.length - 1
    })

    // Methods
    const loadGallery = async () => {
      loading.value = true
      try {
        const response = await api.get('/gallery')
        if (response.data.success) {
          galleryItems.value = response.data.items
          
          // Update stats
          stats.value = {
            totalPhotos: response.data.stats.total_photos || 250,
            totalVideos: response.data.stats.total_videos || 45,
            totalAlbums: response.data.stats.total_albums || 15
          }
        }
      } catch (error) {
        console.error('Error loading gallery:', error)
        // Load dummy data for demo
        loadDummyGallery()
      } finally {
        loading.value = false
      }
    }

    const loadDummyGallery = () => {
      galleryItems.value = [
        {
          id: 1,
          title: 'Human Rights Workshop 2024',
          description: 'Community workshop on understanding fundamental human rights',
          type: 'photo',
          category: 'workshops',
          date: '2024-01-15',
          image: '/images/gallery/workshop1.jpg',
          thumbnail: '/images/gallery/workshop1-thumb.jpg',
          views: 245,
          likes: 32
        },
        {
          id: 2,
          title: 'Women Empowerment Seminar',
          description: 'Seminar focusing on women rights and empowerment strategies',
          type: 'video',
          category: 'events',
          date: '2024-01-10',
          image: '/images/gallery/seminar1.jpg',
          thumbnail: '/images/gallery/seminar1-thumb.jpg',
          video_url: '/videos/seminar1.mp4',
          views: 189,
          likes: 28
        },
        {
          id: 3,
          title: 'Legal Aid Camp',
          description: 'Free legal aid camp for underprivileged communities',
          type: 'photo',
          category: 'campaigns',
          date: '2024-01-05',
          image: '/images/gallery/legal-camp1.jpg',
          thumbnail: '/images/gallery/legal-camp1-thumb.jpg',
          views: 156,
          likes: 21
        },
        {
          id: 4,
          title: 'Award Ceremony 2023',
          description: 'Annual award ceremony recognizing human rights advocates',
          type: 'photo',
          category: 'awards',
          date: '2023-12-20',
          image: '/images/gallery/awards1.jpg',
          thumbnail: '/images/gallery/awards1-thumb.jpg',
          views: 312,
          likes: 45
        },
        {
          id: 5,
          title: 'Community Meeting',
          description: 'Monthly community meeting discussing local issues',
          type: 'video',
          category: 'meetings',
          date: '2023-12-15',
          image: '/images/gallery/meeting1.jpg',
          thumbnail: '/images/gallery/meeting1-thumb.jpg',
          video_url: '/videos/meeting1.mp4',
          views: 98,
          likes: 15
        },
        {
          id: 6,
          title: 'Child Rights Campaign',
          description: 'Campaign raising awareness about child rights and protection',
          type: 'photo',
          category: 'campaigns',
          date: '2023-12-10',
          image: '/images/gallery/child-rights1.jpg',
          thumbnail: '/images/gallery/child-rights1-thumb.jpg',
          views: 203,
          likes: 34
        }
      ]
    }

    const setActiveTab = (tab) => {
      activeTab.value = tab
      currentPage.value = 1
    }

    const handleSearch = () => {
      currentPage.value = 1
    }

    const applyFilters = () => {
      currentPage.value = 1
    }

    const applySorting = () => {
      currentPage.value = 1
    }

    const clearFilters = () => {
      searchQuery.value = ''
      filters.category = ''
      filters.year = ''
      activeTab.value = 'all'
      sortBy.value = 'newest'
      currentPage.value = 1
    }

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
        window.scrollTo({ top: 0, behavior: 'smooth' })
      }
    }

    const openLightbox = (item) => {
      selectedItem.value = item
      currentItemIndex.value = filteredItems.value.findIndex(i => i.id === item.id)
      
      // Increment view count
      item.views++
      
      // Show modal
      const modal = new bootstrap.Modal(document.getElementById('lightboxModal'))
      modal.show()
    }

    const navigateLightbox = (direction) => {
      if (direction === 'prev' && canNavigatePrev.value) {
        currentItemIndex.value--
      } else if (direction === 'next' && canNavigateNext.value) {
        currentItemIndex.value++
      }
      
      selectedItem.value = filteredItems.value[currentItemIndex.value]
    }

    const likeItem = async (item) => {
      try {
        const response = await api.post(`/gallery/${item.id}/like`)
        if (response.data.success) {
          item.likes++
          toast.success('Item liked!')
        }
      } catch (error) {
        console.error('Error liking item:', error)
        // Fallback for demo
        item.likes++
        toast.success('Item liked!')
      }
    }

    const shareItem = (item) => {
      if (navigator.share) {
        navigator.share({
          title: item.title,
          text: item.description,
          url: window.location.href
        })
      } else {
        // Fallback: copy to clipboard
        const url = `${window.location.origin}/gallery/${item.id}`
        navigator.clipboard.writeText(url).then(() => {
          toast.success('Link copied to clipboard!')
        })
      }
    }

    const downloadItem = (item) => {
      if (item.type === 'photo') {
        const link = document.createElement('a')
        link.href = item.image
        link.download = `${item.title}.jpg`
        link.click()
      } else {
        toast.info('Video download not available')
      }
    }

    const formatDate = (date) => {
      return dateUtils.formatDate(date, 'MMM DD, YYYY')
    }

    const truncateText = (text, length) => {
      return stringUtils.truncate(text, length)
    }

    // Watch for filter changes
    watch([() => filters.category, () => filters.year, activeTab], () => {
      currentPage.value = 1
    })

    // Lifecycle
    onMounted(() => {
      loadGallery()
    })

    return {
      // State
      loading,
      galleryItems,
      selectedItem,
      currentItemIndex,
      activeTab,
      currentPage,
      searchQuery,
      sortBy,
      stats,
      filters,
      
      // Computed
      filteredItems,
      totalPages,
      paginatedItems,
      visiblePages,
      canNavigatePrev,
      canNavigateNext,
      
      // Methods
      setActiveTab,
      handleSearch,
      applyFilters,
      applySorting,
      clearFilters,
      changePage,
      openLightbox,
      navigateLightbox,
      likeItem,
      shareItem,
      downloadItem,
      formatDate,
      truncateText
    }
  }
}
</script>

<style scoped>
.gallery-page {
  padding-top: 0;
}

/* Hero Section */
.hero-section {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 4rem 0;
}

.hero-title {
  font-size: 3.5rem;
  font-weight: 700;
  color: #333;
  margin-bottom: 1rem;
}

.hero-subtitle {
  font-size: 1.25rem;
  color: #6c757d;
  margin-bottom: 2rem;
}

.hero-stats {
  margin-bottom: 2rem;
}

.stat-item {
  text-align: center;
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: #007bff;
  display: block;
}

.stat-label {
  font-size: 0.9rem;
  color: #6c757d;
  text-transform: uppercase;
}

/* Filters Section */
.filters-section {
  border-bottom: 1px solid #dee2e6;
}

.search-box .input-group-text {
  background: white;
  border-right: none;
}

.search-box .form-control {
  border-left: none;
}

.filter-controls .btn-group .btn {
  font-size: 0.9rem;
}

.filter-controls .btn.active {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.filter-controls .form-select {
  font-size: 0.9rem;
}

/* Gallery Grid */
.gallery-grid {
  margin-top: 2rem;
}

.gallery-item {
  background: white;
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  cursor: pointer;
  height: 100%;
}

.gallery-item:hover {
  transform: translateY(-5px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.item-image {
  position: relative;
  height: 250px;
  overflow: hidden;
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.gallery-item:hover .item-image img {
  transform: scale(1.05);
}

.item-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.gallery-item:hover .item-overlay {
  opacity: 1;
}

.overlay-content i {
  font-size: 2rem;
  color: white;
}

.item-type {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  padding: 0.5rem;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.item-content {
  padding: 1.5rem;
}

.item-content h6 {
  color: #333;
  font-weight: 600;
  margin-bottom: 0.75rem;
}

.item-description {
  color: #6c757d;
  font-size: 0.9rem;
  margin-bottom: 1rem;
}

.item-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1rem;
}

.meta-item {
  font-size: 0.8rem;
  color: #6c757d;
}

.meta-item i {
  color: #007bff;
}

.item-stats {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
  color: #6c757d;
}

.stat-item i {
  color: #007bff;
}

/* Lightbox Modal */
.modal-xl {
  max-width: 90vw;
}

.lightbox-content {
  position: relative;
}

.photo-display,
.video-display {
  text-align: center;
  background: #000;
}

.video-placeholder {
  padding: 4rem;
  text-align: center;
}

.item-details {
  background: rgba(0, 0, 0, 0.8);
}

.item-meta-detailed {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.meta-badge {
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.8rem;
}

.item-actions {
  text-align: right;
}

.item-actions .btn {
  margin-bottom: 0.5rem;
}

/* Pagination */
.pagination-wrapper {
  display: flex;
  justify-content: center;
}

.pagination .page-link {
  color: #007bff;
  border-color: #dee2e6;
}

.pagination .page-item.active .page-link {
  background: #007bff;
  border-color: #007bff;
}

.pagination .page-link:hover {
  background: #f8f9fa;
}

/* Loading State */
.spinner-border {
  width: 3rem;
  height: 3rem;
}

/* Responsive Design */
@media (max-width: 991.98px) {
  .hero-title {
    font-size: 2.5rem;
  }
  
  .filter-controls .row > div {
    margin-bottom: 0.5rem;
  }
  
  .item-image {
    height: 200px;
  }
}

@media (max-width: 767.98px) {
  .hero-title {
    font-size: 2rem;
  }
  
  .filter-controls .btn-group {
    flex-direction: column;
  }
  
  .filter-controls .btn-group .btn {
    border-radius: 0.375rem !important;
    margin-bottom: 0.25rem;
  }
  
  .item-image {
    height: 180px;
  }
  
  .item-content {
    padding: 1rem;
  }
  
  .modal-xl {
    max-width: 95vw;
  }
  
  .item-details {
    padding: 2rem 1rem !important;
  }
  
  .item-actions {
    text-align: center;
    margin-top: 1rem;
  }
  
  .item-actions .btn {
    display: block;
    width: 100%;
  }
}

/* Animations */
.gallery-page {
  animation: fadeIn 0.6s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

/* Utility Classes */
.rounded-lg {
  border-radius: 1rem !important;
}

/* Button Hover Effects */
.btn {
  transition: all 0.3s ease;
}

.btn:hover {
  transform: translateY(-1px);
}

.btn-primary:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 123, 255, 0.3);
}

/* Form Styles */
.form-control:focus,
.form-select:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Custom Scrollbar for Modal */
.modal-body::-webkit-scrollbar {
  width: 8px;
}

.modal-body::-webkit-scrollbar-track {
  background: #333;
}

.modal-body::-webkit-scrollbar-thumb {
  background: #666;
  border-radius: 4px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
  background: #888;
}
</style>