<template>
  <div class="admin-videos">
    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
    </div>

    <!-- Videos Header -->
    <div class="videos-header">
      <div class="header-content">
        <div class="header-title">
          <i class="fas fa-video icon"></i>
          <h1>Videos Management</h1>
        </div>
        <div class="header-actions">
          <button @click="refreshVideos" class="btn btn-secondary">
            <i class="fas fa-sync-alt"></i>
            Refresh
          </button>
          <router-link to="/admin/add-video" class="btn btn-primary">
            <i class="fas fa-upload"></i>
            Upload Videos
          </router-link>
        </div>
      </div>
    </div>

    <div class="videos-content">
      <!-- Quick Stats -->
      <div class="stats-grid">
        <div class="stat-card primary">
          <div class="stat-header">
            <span class="stat-title">Total Videos</span>
            <div class="stat-icon primary">
              <i class="fas fa-video"></i>
            </div>
          </div>
          <div class="stat-value">{{ stats.totalVideos }}</div>
          <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i>
            +{{ stats.videosGrowth }}% this month
          </div>
        </div>

        <div class="stat-card success">
          <div class="stat-header">
            <span class="stat-title">Published</span>
            <div class="stat-icon success">
              <i class="fas fa-check-circle"></i>
            </div>
          </div>
          <div class="stat-value">{{ stats.publishedVideos }}</div>
          <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i>
            +{{ stats.publishedGrowth }}%
          </div>
        </div>

        <div class="stat-card warning">
          <div class="stat-header">
            <span class="stat-title">Pending</span>
            <div class="stat-icon warning">
              <i class="fas fa-clock"></i>
            </div>
          </div>
          <div class="stat-value">{{ stats.pendingVideos }}</div>
        </div>

        <div class="stat-card info">
          <div class="stat-header">
            <span class="stat-title">Storage Used</span>
            <div class="stat-icon info">
              <i class="fas fa-hdd"></i>
            </div>
          </div>
          <div class="stat-value">{{ stats.storageUsed }}MB</div>
        </div>
      </div>

      <!-- Filters and Search -->
      <div class="filters-section">
        <div class="search-box">
          <i class="fas fa-search"></i>
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Search videos..."
            @input="filterVideos"
          >
        </div>
        <div class="filter-controls">
          <select v-model="statusFilter" @change="filterVideos" class="form-select">
            <option value="">All Status</option>
            <option value="published">Published</option>
            <option value="pending">Pending</option>
            <option value="draft">Draft</option>
          </select>
          <select v-model="categoryFilter" @change="filterVideos" class="form-select">
            <option value="">All Categories</option>
            <option value="events">Events</option>
            <option value="activities">Activities</option>
            <option value="testimonials">Testimonials</option>
            <option value="training">Training</option>
            <option value="awareness">Awareness</option>
          </select>
          <select v-model="sortBy" @change="filterVideos" class="form-select">
            <option value="created_at">Latest First</option>
            <option value="title">Title A-Z</option>
            <option value="views">Most Viewed</option>
            <option value="duration">Duration</option>
          </select>
        </div>
      </div>

      <!-- Videos Grid -->
      <div class="videos-grid">
        <div v-if="filteredVideos.length === 0" class="empty-state">
          <i class="fas fa-video fa-4x text-muted mb-3"></i>
          <h3>No Videos Found</h3>
          <p class="text-muted">{{ searchQuery ? 'No videos match your search criteria.' : 'Start by uploading your first video.' }}</p>
          <router-link to="/admin/add-video" class="btn btn-primary">
            <i class="fas fa-upload me-2"></i>
            Upload Video
          </router-link>
        </div>

        <div v-else class="video-cards">
          <div v-for="video in filteredVideos" :key="video.id" class="video-card">
            <div class="video-thumbnail">
              <img :src="video.thumbnail || '/assets/images/video-placeholder.svg'" :alt="video.title">
              <div class="video-overlay">
                <button @click="playVideo(video)" class="play-btn">
                  <i class="fas fa-play"></i>
                </button>
                <div class="video-duration">{{ formatDuration(video.duration) }}</div>
              </div>
              <div class="video-status" :class="video.status">
                {{ video.status }}
              </div>
            </div>
            <div class="video-info">
              <h4 class="video-title">{{ video.title }}</h4>
              <p class="video-description">{{ truncateText(video.description, 100) }}</p>
              <div class="video-meta">
                <span class="video-category">
                  <i class="fas fa-tag"></i>
                  {{ video.category }}
                </span>
                <span class="video-date">
                  <i class="fas fa-calendar"></i>
                  {{ formatDate(video.created_at) }}
                </span>
                <span class="video-views">
                  <i class="fas fa-eye"></i>
                  {{ video.views || 0 }} views
                </span>
              </div>
              <div class="video-actions">
                <button @click="editVideo(video)" class="btn btn-sm btn-outline-primary">
                  <i class="fas fa-edit"></i>
                  Edit
                </button>
                <button @click="toggleVideoStatus(video)" class="btn btn-sm" :class="video.status === 'published' ? 'btn-outline-warning' : 'btn-outline-success'">
                  <i :class="video.status === 'published' ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                  {{ video.status === 'published' ? 'Unpublish' : 'Publish' }}
                </button>
                <button @click="deleteVideo(video)" class="btn btn-sm btn-outline-danger">
                  <i class="fas fa-trash"></i>
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="pagination-wrapper">
        <nav aria-label="Videos pagination">
          <ul class="pagination justify-content-center">
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <button class="page-link" @click="changePage(currentPage - 1)" :disabled="currentPage === 1">
                <i class="fas fa-chevron-left"></i>
              </button>
            </li>
            <li v-for="page in visiblePages" :key="page" class="page-item" :class="{ active: page === currentPage }">
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

    <!-- Video Player Modal -->
    <div v-if="selectedVideo" class="modal fade show d-block" tabindex="-1" @click="closeVideoModal">
      <div class="modal-dialog modal-lg modal-dialog-centered" @click.stop>
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ selectedVideo.title }}</h5>
            <button type="button" class="btn-close" @click="closeVideoModal"></button>
          </div>
          <div class="modal-body p-0">
            <div class="video-player">
              <video v-if="selectedVideo.type === 'upload'" controls class="w-100">
                <source :src="selectedVideo.url" type="video/mp4">
                Your browser does not support the video tag.
              </video>
              <iframe v-else-if="selectedVideo.type === 'youtube'" 
                :src="`https://www.youtube.com/embed/${selectedVideo.video_id}`"
                class="w-100"
                style="height: 400px;"
                frameborder="0"
                allowfullscreen>
              </iframe>
              <iframe v-else-if="selectedVideo.type === 'vimeo'"
                :src="`https://player.vimeo.com/video/${selectedVideo.video_id}`"
                class="w-100"
                style="height: 400px;"
                frameborder="0"
                allowfullscreen>
              </iframe>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

export default {
  name: 'AdminVideos',
  setup() {
    const router = useRouter()
    const loading = ref(false)
    const searchQuery = ref('')
    const statusFilter = ref('')
    const categoryFilter = ref('')
    const sortBy = ref('created_at')
    const currentPage = ref(1)
    const itemsPerPage = 12
    const selectedVideo = ref(null)

    const stats = reactive({
      totalVideos: 0,
      publishedVideos: 0,
      pendingVideos: 0,
      storageUsed: 0,
      videosGrowth: 0,
      publishedGrowth: 0
    })

    const videos = ref([])

    const filteredVideos = computed(() => {
      let filtered = videos.value

      if (searchQuery.value) {
        filtered = filtered.filter(video => 
          video.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
          video.description.toLowerCase().includes(searchQuery.value.toLowerCase())
        )
      }

      if (statusFilter.value) {
        filtered = filtered.filter(video => video.status === statusFilter.value)
      }

      if (categoryFilter.value) {
        filtered = filtered.filter(video => video.category === categoryFilter.value)
      }

      // Sort
      filtered.sort((a, b) => {
        switch (sortBy.value) {
          case 'title':
            return a.title.localeCompare(b.title)
          case 'views':
            return (b.views || 0) - (a.views || 0)
          case 'duration':
            return (b.duration || 0) - (a.duration || 0)
          default:
            return new Date(b.created_at) - new Date(a.created_at)
        }
      })

      // Pagination
      const start = (currentPage.value - 1) * itemsPerPage
      return filtered.slice(start, start + itemsPerPage)
    })

    const totalPages = computed(() => {
      const filtered = videos.value.filter(video => {
        let matches = true
        if (searchQuery.value) {
          matches = matches && (
            video.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            video.description.toLowerCase().includes(searchQuery.value.toLowerCase())
          )
        }
        if (statusFilter.value) {
          matches = matches && video.status === statusFilter.value
        }
        if (categoryFilter.value) {
          matches = matches && video.category === categoryFilter.value
        }
        return matches
      })
      return Math.ceil(filtered.length / itemsPerPage)
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

    const loadVideos = async () => {
      loading.value = true
      try {
        // Mock data for now - replace with actual API call
        videos.value = [
          {
            id: 1,
            title: 'BHRC Annual Conference 2024',
            description: 'Highlights from our annual human rights conference featuring keynote speakers and panel discussions.',
            thumbnail: '/assets/images/video-placeholder.svg',
            url: '/uploads/videos/conference-2024.mp4',
            type: 'upload',
            category: 'events',
            status: 'published',
            duration: 3600,
            views: 1250,
            created_at: '2024-01-15T10:00:00Z'
          },
          {
            id: 2,
            title: 'Human Rights Awareness Campaign',
            description: 'Educational video about fundamental human rights and their importance in society.',
            thumbnail: '/assets/images/video-placeholder.svg',
            url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            type: 'youtube',
            video_id: 'dQw4w9WgXcQ',
            category: 'awareness',
            status: 'published',
            duration: 1800,
            views: 2100,
            created_at: '2024-01-10T14:30:00Z'
          }
        ]

        // Update stats
        stats.totalVideos = videos.value.length
        stats.publishedVideos = videos.value.filter(v => v.status === 'published').length
        stats.pendingVideos = videos.value.filter(v => v.status === 'pending').length
        stats.storageUsed = Math.round(Math.random() * 500 + 100)
        stats.videosGrowth = Math.round(Math.random() * 20 + 5)
        stats.publishedGrowth = Math.round(Math.random() * 15 + 3)
      } catch (error) {
        console.error('Error loading videos:', error)
      } finally {
        loading.value = false
      }
    }

    const refreshVideos = () => {
      loadVideos()
    }

    const filterVideos = () => {
      currentPage.value = 1
    }

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
      }
    }

    const playVideo = (video) => {
      selectedVideo.value = video
    }

    const closeVideoModal = () => {
      selectedVideo.value = null
    }

    const editVideo = (video) => {
      router.push(`/admin/edit-video/${video.id}`)
    }

    const toggleVideoStatus = async (video) => {
      try {
        const newStatus = video.status === 'published' ? 'draft' : 'published'
        // API call would go here
        video.status = newStatus
        
        // Update stats
        stats.publishedVideos = videos.value.filter(v => v.status === 'published').length
        stats.pendingVideos = videos.value.filter(v => v.status === 'pending').length
      } catch (error) {
        console.error('Error updating video status:', error)
      }
    }

    const deleteVideo = async (video) => {
      if (confirm(`Are you sure you want to delete "${video.title}"?`)) {
        try {
          // API call would go here
          const index = videos.value.findIndex(v => v.id === video.id)
          if (index > -1) {
            videos.value.splice(index, 1)
            stats.totalVideos = videos.value.length
            stats.publishedVideos = videos.value.filter(v => v.status === 'published').length
            stats.pendingVideos = videos.value.filter(v => v.status === 'pending').length
          }
        } catch (error) {
          console.error('Error deleting video:', error)
        }
      }
    }

    const formatDuration = (seconds) => {
      if (!seconds) return '0:00'
      const hours = Math.floor(seconds / 3600)
      const minutes = Math.floor((seconds % 3600) / 60)
      const secs = seconds % 60
      
      if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
      }
      return `${minutes}:${secs.toString().padStart(2, '0')}`
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }

    const truncateText = (text, length) => {
      if (!text) return ''
      return text.length > length ? text.substring(0, length) + '...' : text
    }

    onMounted(() => {
      loadVideos()
    })

    return {
      loading,
      searchQuery,
      statusFilter,
      categoryFilter,
      sortBy,
      currentPage,
      selectedVideo,
      stats,
      videos,
      filteredVideos,
      totalPages,
      visiblePages,
      refreshVideos,
      filterVideos,
      changePage,
      playVideo,
      closeVideoModal,
      editVideo,
      toggleVideoStatus,
      deleteVideo,
      formatDuration,
      formatDate,
      truncateText
    }
  }
}
</script>

<style scoped>
.admin-videos {
  min-height: 100vh;
  background: #f8f9fa;
}

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
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.videos-header {
  background: white;
  border-bottom: 1px solid #dee2e6;
  padding: 1.5rem 0;
}

.header-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.header-title .icon {
  color: #007bff;
  font-size: 1.5rem;
}

.header-title h1 {
  margin: 0;
  font-size: 1.75rem;
  font-weight: 600;
  color: #2c3e50;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
}

.videos-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

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
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  border-left: 4px solid;
}

.stat-card.primary { border-left-color: #007bff; }
.stat-card.success { border-left-color: #28a745; }
.stat-card.warning { border-left-color: #ffc107; }
.stat-card.info { border-left-color: #17a2b8; }

.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.stat-title {
  font-size: 0.875rem;
  color: #6c757d;
  font-weight: 500;
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.stat-icon.primary { background: #007bff; }
.stat-icon.success { background: #28a745; }
.stat-icon.warning { background: #ffc107; }
.stat-icon.info { background: #17a2b8; }

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.stat-change {
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.stat-change.positive { color: #28a745; }

.filters-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 250px;
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
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  font-size: 0.875rem;
}

.filter-controls {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.form-select {
  min-width: 150px;
  padding: 0.75rem;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  font-size: 0.875rem;
}

.videos-grid {
  margin-bottom: 2rem;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.video-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.video-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
}

.video-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.video-thumbnail {
  position: relative;
  aspect-ratio: 16/9;
  overflow: hidden;
}

.video-thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.video-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.2s;
}

.video-card:hover .video-overlay {
  opacity: 1;
}

.play-btn {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.9);
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: #007bff;
  cursor: pointer;
  transition: transform 0.2s;
}

.play-btn:hover {
  transform: scale(1.1);
}

.video-duration {
  position: absolute;
  bottom: 0.5rem;
  right: 0.5rem;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
}

.video-status {
  position: absolute;
  top: 0.5rem;
  left: 0.5rem;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: capitalize;
}

.video-status.published {
  background: #28a745;
  color: white;
}

.video-status.pending {
  background: #ffc107;
  color: #212529;
}

.video-status.draft {
  background: #6c757d;
  color: white;
}

.video-info {
  padding: 1rem;
}

.video-title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #2c3e50;
}

.video-description {
  font-size: 0.875rem;
  color: #6c757d;
  margin-bottom: 1rem;
  line-height: 1.4;
}

.video-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1rem;
  font-size: 0.75rem;
  color: #6c757d;
}

.video-meta span {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.video-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.pagination-wrapper {
  display: flex;
  justify-content: center;
  margin-top: 2rem;
}

.modal.show {
  background: rgba(0, 0, 0, 0.5);
}

.video-player {
  background: #000;
}

@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .filters-section {
    flex-direction: column;
    align-items: stretch;
  }

  .search-box {
    min-width: auto;
  }

  .filter-controls {
    justify-content: center;
  }

  .video-cards {
    grid-template-columns: 1fr;
  }
}
</style>