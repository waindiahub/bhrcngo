<template>
  <div class="albums-management">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">
          <i class="fas fa-photo-video"></i>
          Gallery Albums
        </h1>
        <p class="page-description">Manage photo and video albums for the gallery</p>
      </div>
      <div class="header-actions">
        <button @click="showCreateModal = true" class="btn btn-primary">
          <i class="fas fa-plus"></i>
          Create Album
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-images"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.totalAlbums }}</h3>
          <p>Total Albums</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-photo-video"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.totalMedia }}</h3>
          <p>Total Media</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-eye"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.totalViews }}</h3>
          <p>Total Views</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-calendar"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.recentAlbums }}</h3>
          <p>This Month</p>
        </div>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-section">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Search albums..."
          @input="filterAlbums"
        >
      </div>
      <div class="filter-controls">
        <select v-model="selectedType" @change="filterAlbums" class="filter-select">
          <option value="">All Types</option>
          <option value="photos">Photos</option>
          <option value="videos">Videos</option>
          <option value="mixed">Mixed</option>
        </select>
        <select v-model="selectedStatus" @change="filterAlbums" class="filter-select">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
        <select v-model="sortBy" @change="sortAlbums" class="filter-select">
          <option value="created_at">Latest</option>
          <option value="name">Name</option>
          <option value="media_count">Media Count</option>
          <option value="views">Views</option>
        </select>
      </div>
    </div>

    <!-- Albums Grid -->
    <div class="albums-grid" v-if="!loading">
      <div 
        v-for="album in filteredAlbums" 
        :key="album.id" 
        class="album-card"
        :class="{ 'inactive': album.status === 'inactive' }"
      >
        <div class="album-thumbnail">
          <img 
            :src="album.cover_image || '/assets/images/placeholder-album.svg'" 
            :alt="album.name"
            @error="handleImageError"
          >
          <div class="album-overlay">
            <div class="media-count">
              <i class="fas fa-images"></i>
              {{ album.media_count }} items
            </div>
            <div class="album-actions">
              <button @click="viewAlbum(album)" class="action-btn" title="View Album">
                <i class="fas fa-eye"></i>
              </button>
              <button @click="editAlbum(album)" class="action-btn" title="Edit Album">
                <i class="fas fa-edit"></i>
              </button>
              <button @click="deleteAlbum(album)" class="action-btn danger" title="Delete Album">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="album-info">
          <h3 class="album-name">{{ album.name }}</h3>
          <p class="album-description">{{ album.description || 'No description' }}</p>
          <div class="album-meta">
            <span class="album-type">
              <i :class="getTypeIcon(album.type)"></i>
              {{ album.type }}
            </span>
            <span class="album-date">{{ formatDate(album.created_at) }}</span>
            <span class="album-views">
              <i class="fas fa-eye"></i>
              {{ album.views }}
            </span>
          </div>
          <div class="album-status">
            <span :class="['status-badge', album.status]">
              {{ album.status }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading albums...</p>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && filteredAlbums.length === 0" class="empty-state">
      <i class="fas fa-photo-video"></i>
      <h3>No Albums Found</h3>
      <p>{{ searchQuery ? 'No albums match your search criteria.' : 'Create your first album to get started.' }}</p>
      <button @click="showCreateModal = true" class="btn btn-primary">
        <i class="fas fa-plus"></i>
        Create Album
      </button>
    </div>

    <!-- Create/Edit Album Modal -->
    <div v-if="showCreateModal || showEditModal" class="modal-overlay" @click="closeModals">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>{{ showEditModal ? 'Edit Album' : 'Create New Album' }}</h2>
          <button @click="closeModals" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <form @submit.prevent="saveAlbum" class="album-form">
          <div class="form-group">
            <label for="albumName">Album Name *</label>
            <input 
              id="albumName"
              v-model="albumForm.name" 
              type="text" 
              required
              placeholder="Enter album name"
            >
          </div>
          <div class="form-group">
            <label for="albumDescription">Description</label>
            <textarea 
              id="albumDescription"
              v-model="albumForm.description" 
              rows="3"
              placeholder="Enter album description"
            ></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="albumType">Type *</label>
              <select id="albumType" v-model="albumForm.type" required>
                <option value="">Select Type</option>
                <option value="photos">Photos</option>
                <option value="videos">Videos</option>
                <option value="mixed">Mixed</option>
              </select>
            </div>
            <div class="form-group">
              <label for="albumStatus">Status</label>
              <select id="albumStatus" v-model="albumForm.status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label for="coverImage">Cover Image</label>
            <div class="file-upload-area">
              <input 
                id="coverImage"
                type="file" 
                @change="handleCoverImageUpload"
                accept="image/*"
                class="file-input"
              >
              <div class="upload-placeholder">
                <i class="fas fa-cloud-upload-alt"></i>
                <p>Click to upload cover image</p>
              </div>
            </div>
            <div v-if="albumForm.cover_image_preview" class="image-preview">
              <img :src="albumForm.cover_image_preview" alt="Cover preview">
              <button type="button" @click="removeCoverImage" class="remove-image">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
          <div class="form-group">
            <label for="albumTags">Tags</label>
            <input 
              id="albumTags"
              v-model="albumForm.tags" 
              type="text" 
              placeholder="Enter tags separated by commas"
            >
          </div>
          <div class="form-actions">
            <button type="button" @click="closeModals" class="btn btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <i class="fas fa-save"></i>
              {{ saving ? 'Saving...' : (showEditModal ? 'Update Album' : 'Create Album') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click="showDeleteModal = false">
      <div class="modal-content small" @click.stop>
        <div class="modal-header">
          <h2>Delete Album</h2>
          <button @click="showDeleteModal = false" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete the album "{{ albumToDelete?.name }}"?</p>
          <p class="warning-text">This action cannot be undone and will remove all media in this album.</p>
        </div>
        <div class="modal-actions">
          <button @click="showDeleteModal = false" class="btn btn-secondary">
            Cancel
          </button>
          <button @click="confirmDelete" class="btn btn-danger" :disabled="deleting">
            <i class="fas fa-trash"></i>
            {{ deleting ? 'Deleting...' : 'Delete Album' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Albums',
  data() {
    return {
      loading: true,
      saving: false,
      deleting: false,
      albums: [],
      filteredAlbums: [],
      searchQuery: '',
      selectedType: '',
      selectedStatus: '',
      sortBy: 'created_at',
      showCreateModal: false,
      showEditModal: false,
      showDeleteModal: false,
      albumToDelete: null,
      editingAlbum: null,
      stats: {
        totalAlbums: 0,
        totalMedia: 0,
        totalViews: 0,
        recentAlbums: 0
      },
      albumForm: {
        name: '',
        description: '',
        type: '',
        status: 'active',
        cover_image: null,
        cover_image_preview: null,
        tags: ''
      }
    }
  },
  mounted() {
    this.loadAlbums()
    this.loadStats()
  },
  methods: {
    async loadAlbums() {
      try {
        this.loading = true
        // Mock data for demonstration
        this.albums = [
          {
            id: 1,
            name: 'Community Events 2024',
            description: 'Photos from various community events throughout 2024',
            type: 'photos',
            status: 'active',
            media_count: 45,
            views: 1250,
            cover_image: '/assets/images/event-placeholder.svg',
            created_at: '2024-01-15T10:00:00Z',
            tags: 'events, community, 2024'
          },
          {
            id: 2,
            name: 'Legal Awareness Videos',
            description: 'Educational videos about legal rights and procedures',
            type: 'videos',
            status: 'active',
            media_count: 12,
            views: 890,
            cover_image: null,
            created_at: '2024-01-10T14:30:00Z',
            tags: 'legal, education, awareness'
          },
          {
            id: 3,
            name: 'Annual Conference',
            description: 'Mixed media from our annual conference',
            type: 'mixed',
            status: 'active',
            media_count: 78,
            views: 2100,
            cover_image: '/assets/images/placeholder-album.svg',
            created_at: '2024-01-05T09:15:00Z',
            tags: 'conference, annual, mixed'
          }
        ]
        this.filteredAlbums = [...this.albums]
      } catch (error) {
        console.error('Error loading albums:', error)
        this.$toast?.error('Failed to load albums')
      } finally {
        this.loading = false
      }
    },

    async loadStats() {
      try {
        // Mock stats data
        this.stats = {
          totalAlbums: 15,
          totalMedia: 342,
          totalViews: 8750,
          recentAlbums: 3
        }
      } catch (error) {
        console.error('Error loading stats:', error)
      }
    },

    filterAlbums() {
      let filtered = [...this.albums]

      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(album => 
          album.name.toLowerCase().includes(query) ||
          album.description?.toLowerCase().includes(query) ||
          album.tags?.toLowerCase().includes(query)
        )
      }

      if (this.selectedType) {
        filtered = filtered.filter(album => album.type === this.selectedType)
      }

      if (this.selectedStatus) {
        filtered = filtered.filter(album => album.status === this.selectedStatus)
      }

      this.filteredAlbums = filtered
      this.sortAlbums()
    },

    sortAlbums() {
      this.filteredAlbums.sort((a, b) => {
        switch (this.sortBy) {
          case 'name':
            return a.name.localeCompare(b.name)
          case 'media_count':
            return b.media_count - a.media_count
          case 'views':
            return b.views - a.views
          case 'created_at':
          default:
            return new Date(b.created_at) - new Date(a.created_at)
        }
      })
    },

    viewAlbum(album) {
      // Navigate to album detail view
      this.$router.push(`/admin/albums/${album.id}`)
    },

    editAlbum(album) {
      this.editingAlbum = album
      this.albumForm = {
        name: album.name,
        description: album.description || '',
        type: album.type,
        status: album.status,
        cover_image: null,
        cover_image_preview: album.cover_image,
        tags: album.tags || ''
      }
      this.showEditModal = true
    },

    deleteAlbum(album) {
      this.albumToDelete = album
      this.showDeleteModal = true
    },

    async confirmDelete() {
      try {
        this.deleting = true
        // API call would go here
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        this.albums = this.albums.filter(a => a.id !== this.albumToDelete.id)
        this.filterAlbums()
        this.$toast?.success('Album deleted successfully')
        this.showDeleteModal = false
        this.albumToDelete = null
      } catch (error) {
        console.error('Error deleting album:', error)
        this.$toast?.error('Failed to delete album')
      } finally {
        this.deleting = false
      }
    },

    async saveAlbum() {
      try {
        this.saving = true
        
        // API call would go here
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        if (this.showEditModal) {
          // Update existing album
          const index = this.albums.findIndex(a => a.id === this.editingAlbum.id)
          if (index !== -1) {
            this.albums[index] = {
              ...this.albums[index],
              ...this.albumForm,
              cover_image: this.albumForm.cover_image_preview
            }
          }
          this.$toast?.success('Album updated successfully')
        } else {
          // Create new album
          const newAlbum = {
            id: Date.now(),
            ...this.albumForm,
            media_count: 0,
            views: 0,
            cover_image: this.albumForm.cover_image_preview,
            created_at: new Date().toISOString()
          }
          this.albums.unshift(newAlbum)
          this.$toast?.success('Album created successfully')
        }
        
        this.filterAlbums()
        this.closeModals()
      } catch (error) {
        console.error('Error saving album:', error)
        this.$toast?.error('Failed to save album')
      } finally {
        this.saving = false
      }
    },

    handleCoverImageUpload(event) {
      const file = event.target.files[0]
      if (file) {
        this.albumForm.cover_image = file
        const reader = new FileReader()
        reader.onload = (e) => {
          this.albumForm.cover_image_preview = e.target.result
        }
        reader.readAsDataURL(file)
      }
    },

    removeCoverImage() {
      this.albumForm.cover_image = null
      this.albumForm.cover_image_preview = null
    },

    closeModals() {
      this.showCreateModal = false
      this.showEditModal = false
      this.editingAlbum = null
      this.albumForm = {
        name: '',
        description: '',
        type: '',
        status: 'active',
        cover_image: null,
        cover_image_preview: null,
        tags: ''
      }
    },

    getTypeIcon(type) {
      switch (type) {
        case 'photos': return 'fas fa-images'
        case 'videos': return 'fas fa-video'
        case 'mixed': return 'fas fa-photo-video'
        default: return 'fas fa-folder'
      }
    },

    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },

    handleImageError(event) {
      event.target.src = '/assets/images/placeholder-album.svg'
    }
  }
}
</script>

<style scoped>
.albums-management {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.header-content h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
}

.header-content h1 i {
  margin-right: 0.5rem;
  color: #3b82f6;
}

.page-description {
  color: #6b7280;
  margin: 0;
}

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
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
}

.stat-content h3 {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.stat-content p {
  color: #6b7280;
  margin: 0;
  font-size: 0.875rem;
}

.filters-section {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 300px;
}

.search-box i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #6b7280;
}

.search-box input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
}

.filter-controls {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.filter-select {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  min-width: 120px;
}

.albums-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
}

.album-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}

.album-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.album-card.inactive {
  opacity: 0.6;
}

.album-thumbnail {
  position: relative;
  height: 200px;
  overflow: hidden;
}

.album-thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.album-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.7));
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1rem;
  opacity: 0;
  transition: opacity 0.2s;
}

.album-card:hover .album-overlay {
  opacity: 1;
}

.media-count {
  color: white;
  font-size: 0.875rem;
  font-weight: 500;
}

.album-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

.action-btn {
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.2s;
}

.action-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

.action-btn.danger:hover {
  background: #ef4444;
}

.album-info {
  padding: 1.5rem;
}

.album-name {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
}

.album-description {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0 0 1rem 0;
  line-height: 1.4;
}

.album-meta {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
  font-size: 0.75rem;
  color: #6b7280;
}

.album-type i {
  margin-right: 0.25rem;
}

.album-views i {
  margin-right: 0.25rem;
}

.album-status {
  display: flex;
  justify-content: flex-end;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
}

.status-badge.active {
  background: #dcfce7;
  color: #166534;
}

.status-badge.inactive {
  background: #fef3c7;
  color: #92400e;
}

.loading-state, .empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #6b7280;
}

.loading-state .spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-state i {
  font-size: 4rem;
  color: #d1d5db;
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.5rem;
  color: #374151;
  margin-bottom: 0.5rem;
}

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
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-content.small {
  max-width: 400px;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  margin: 0;
  color: #1f2937;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #6b7280;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 4px;
}

.close-btn:hover {
  background: #f3f4f6;
}

.album-form {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #374151;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
}

.form-group textarea {
  resize: vertical;
}

.file-upload-area {
  position: relative;
  border: 2px dashed #d1d5db;
  border-radius: 8px;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: border-color 0.2s;
}

.file-upload-area:hover {
  border-color: #3b82f6;
}

.file-input {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
}

.upload-placeholder i {
  font-size: 2rem;
  color: #9ca3af;
  margin-bottom: 0.5rem;
}

.image-preview {
  position: relative;
  margin-top: 1rem;
  display: inline-block;
}

.image-preview img {
  width: 120px;
  height: 80px;
  object-fit: cover;
  border-radius: 8px;
}

.remove-image {
  position: absolute;
  top: -8px;
  right: -8px;
  width: 24px;
  height: 24px;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
}

.form-actions,
.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
  margin-top: 1rem;
}

.modal-body {
  padding: 1.5rem;
}

.warning-text {
  color: #dc2626;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626;
}

@media (max-width: 768px) {
  .albums-management {
    padding: 1rem;
  }
  
  .page-header {
    flex-direction: column;
    gap: 1rem;
  }
  
  .filters-section {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-box {
    min-width: auto;
  }
  
  .filter-controls {
    justify-content: stretch;
  }
  
  .filter-select {
    flex: 1;
    min-width: auto;
  }
  
  .albums-grid {
    grid-template-columns: 1fr;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .modal-content {
    width: 95%;
    margin: 1rem;
  }
}
</style>