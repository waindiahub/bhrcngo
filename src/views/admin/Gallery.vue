<template>
  <div class="admin-gallery">
    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
    </div>

    <!-- Gallery Header -->
    <div class="gallery-header">
      <div class="header-content">
        <div class="header-title">
          <i class="fas fa-images icon"></i>
          <h1>Gallery Management</h1>
        </div>
        <div class="header-actions">
          <button @click="refreshGallery" class="btn btn-secondary">
            <i class="fas fa-sync-alt"></i>
            Refresh
          </button>
          <button @click="showUploadModal = true" class="btn btn-primary">
            <i class="fas fa-upload"></i>
            Upload Photos
          </button>
          <button @click="showAlbumModal = true" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            Create Album
          </button>
        </div>
      </div>
    </div>

    <div class="gallery-content">
      <!-- Quick Stats -->
      <div class="stats-grid">
        <div class="stat-card primary">
          <div class="stat-header">
            <span class="stat-title">Total Photos</span>
            <div class="stat-icon primary">
              <i class="fas fa-image"></i>
            </div>
          </div>
          <div class="stat-value">{{ stats.totalPhotos }}</div>
          <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i>
            +{{ stats.photosGrowth }}% this month
          </div>
        </div>

        <div class="stat-card success">
          <div class="stat-header">
            <span class="stat-title">Albums</span>
            <div class="stat-icon success">
              <i class="fas fa-folder"></i>
            </div>
          </div>
          <div class="stat-value">{{ stats.totalAlbums }}</div>
          <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i>
            +{{ stats.albumsGrowth }}% this month
          </div>
        </div>

        <div class="stat-card warning">
          <div class="stat-header">
            <span class="stat-title">Storage Used</span>
            <div class="stat-icon warning">
              <i class="fas fa-hdd"></i>
            </div>
          </div>
          <div class="stat-value">{{ formatFileSize(stats.storageUsed) }}</div>
          <div class="stat-change">
            <i class="fas fa-chart-line"></i>
            {{ stats.storagePercentage }}% of limit
          </div>
        </div>

        <div class="stat-card info">
          <div class="stat-header">
            <span class="stat-title">Recent Uploads</span>
            <div class="stat-icon info">
              <i class="fas fa-clock"></i>
            </div>
          </div>
          <div class="stat-value">{{ stats.recentUploads }}</div>
          <div class="stat-change">
            <i class="fas fa-calendar-day"></i>
            Last 24 hours
          </div>
        </div>
      </div>

      <!-- Gallery Controls -->
      <div class="gallery-controls">
        <div class="controls-left">
          <div class="view-toggle">
            <button 
              @click="viewMode = 'grid'" 
              :class="['view-btn', { active: viewMode === 'grid' }]"
              title="Grid View"
            >
              <i class="fas fa-th"></i>
            </button>
            <button 
              @click="viewMode = 'list'" 
              :class="['view-btn', { active: viewMode === 'list' }]"
              title="List View"
            >
              <i class="fas fa-list"></i>
            </button>
            <button 
              @click="viewMode = 'albums'" 
              :class="['view-btn', { active: viewMode === 'albums' }]"
              title="Albums View"
            >
              <i class="fas fa-folder-open"></i>
            </button>
          </div>
          
          <div class="filter-controls">
            <select v-model="filterAlbum" class="form-control">
              <option value="">All Albums</option>
              <option v-for="album in albums" :key="album.id" :value="album.id">
                {{ album.name }}
              </option>
            </select>
            
            <select v-model="filterType" class="form-control">
              <option value="">All Types</option>
              <option value="image">Images</option>
              <option value="video">Videos</option>
            </select>
            
            <select v-model="sortBy" class="form-control">
              <option value="created_at">Date Added</option>
              <option value="name">Name</option>
              <option value="size">File Size</option>
              <option value="views">Views</option>
            </select>
          </div>
        </div>
        
        <div class="controls-right">
          <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input 
              v-model="searchQuery" 
              type="text" 
              placeholder="Search photos..." 
              class="search-input form-control"
            >
          </div>
        </div>
      </div>

      <!-- Albums View -->
      <div v-if="viewMode === 'albums'" class="albums-section">
        <div class="section-header">
          <h2 class="section-title">
            <i class="fas fa-folder-open"></i>
            Photo Albums
          </h2>
        </div>

        <div class="albums-grid">
          <div 
            v-for="album in filteredAlbums" 
            :key="album.id"
            class="album-card"
            @click="selectAlbum(album)"
          >
            <div class="album-cover">
              <img 
                v-if="album.cover_photo" 
                :src="album.cover_photo" 
                :alt="album.name"
                class="cover-image"
              >
              <div v-else class="no-cover">
                <i class="fas fa-folder"></i>
              </div>
              <div class="album-overlay">
                <div class="album-actions">
                  <button @click.stop="editAlbum(album)" class="action-btn">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button @click.stop="deleteAlbum(album)" class="action-btn">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
            <div class="album-info">
              <h3 class="album-name">{{ album.name }}</h3>
              <p class="album-description">{{ album.description }}</p>
              <div class="album-stats">
                <span class="photo-count">
                  <i class="fas fa-image"></i>
                  {{ album.photo_count }} photos
                </span>
                <span class="created-date">
                  <i class="fas fa-calendar"></i>
                  {{ formatDate(album.created_at) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Photos Grid View -->
      <div v-if="viewMode === 'grid'" class="photos-grid-section">
        <div class="section-header">
          <h2 class="section-title">
            <i class="fas fa-images"></i>
            Photo Gallery
            <span v-if="selectedAlbum" class="album-breadcrumb">
              / {{ selectedAlbum.name }}
            </span>
          </h2>
          <div class="section-actions">
            <button 
              v-if="selectedAlbum" 
              @click="selectedAlbum = null" 
              class="btn btn-sm btn-secondary"
            >
              <i class="fas fa-arrow-left"></i>
              Back to Albums
            </button>
          </div>
        </div>

        <div class="photos-grid">
          <div 
            v-for="photo in filteredPhotos" 
            :key="photo.id"
            class="photo-card"
            @click="viewPhoto(photo)"
          >
            <div class="photo-container">
              <img :src="photo.thumbnail" :alt="photo.name" class="photo-image">
              <div class="photo-overlay">
                <div class="photo-actions">
                  <button @click.stop="editPhoto(photo)" class="action-btn">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button @click.stop="downloadPhoto(photo)" class="action-btn">
                    <i class="fas fa-download"></i>
                  </button>
                  <button @click.stop="deletePhoto(photo)" class="action-btn">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
              <div class="photo-selection">
                <input 
                  type="checkbox" 
                  v-model="selectedPhotos" 
                  :value="photo.id"
                  @click.stop
                >
              </div>
            </div>
            <div class="photo-info">
              <h4 class="photo-name">{{ photo.name }}</h4>
              <div class="photo-meta">
                <span class="file-size">{{ formatFileSize(photo.size) }}</span>
                <span class="upload-date">{{ formatDate(photo.created_at) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Photos List View -->
      <div v-if="viewMode === 'list'" class="photos-list-section">
        <div class="section-header">
          <h2 class="section-title">
            <i class="fas fa-list"></i>
            Photo List
          </h2>
        </div>

        <div class="photos-table-container">
          <table class="photos-table">
            <thead>
              <tr>
                <th>
                  <input 
                    type="checkbox" 
                    v-model="selectAll" 
                    @change="toggleSelectAll"
                  >
                </th>
                <th @click="sortBy = 'name'">
                  Photo
                  <i class="fas fa-sort sort-icon"></i>
                </th>
                <th @click="sortBy = 'album'">
                  Album
                  <i class="fas fa-sort sort-icon"></i>
                </th>
                <th @click="sortBy = 'size'">
                  Size
                  <i class="fas fa-sort sort-icon"></i>
                </th>
                <th @click="sortBy = 'created_at'">
                  Upload Date
                  <i class="fas fa-sort sort-icon"></i>
                </th>
                <th @click="sortBy = 'views'">
                  Views
                  <i class="fas fa-sort sort-icon"></i>
                </th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="photo in filteredPhotos" 
                :key="photo.id"
                class="photo-row"
              >
                <td>
                  <input 
                    type="checkbox" 
                    v-model="selectedPhotos" 
                    :value="photo.id"
                  >
                </td>
                <td>
                  <div class="photo-preview">
                    <img :src="photo.thumbnail" :alt="photo.name" class="preview-image">
                    <div class="photo-details">
                      <strong>{{ photo.name }}</strong>
                      <small>{{ photo.description }}</small>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="album-badge">{{ photo.album_name || 'Uncategorized' }}</span>
                </td>
                <td>
                  <span class="file-size">{{ formatFileSize(photo.size) }}</span>
                </td>
                <td>
                  <div class="date-info">
                    <strong>{{ formatDate(photo.created_at) }}</strong>
                    <small>{{ formatTime(photo.created_at) }}</small>
                  </div>
                </td>
                <td>
                  <span class="view-count">{{ photo.views || 0 }}</span>
                </td>
                <td>
                  <div class="action-buttons">
                    <button 
                      @click="viewPhoto(photo)" 
                      class="btn btn-sm btn-info"
                      title="View Photo"
                    >
                      <i class="fas fa-eye"></i>
                    </button>
                    <button 
                      @click="editPhoto(photo)" 
                      class="btn btn-sm btn-primary"
                      title="Edit"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button 
                      @click="downloadPhoto(photo)" 
                      class="btn btn-sm btn-success"
                      title="Download"
                    >
                      <i class="fas fa-download"></i>
                    </button>
                    <button 
                      @click="deletePhoto(photo)" 
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
          <div v-if="filteredPhotos.length === 0" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-images"></i>
            </div>
            <h3 class="empty-title">No Photos Found</h3>
            <p class="empty-message">
              {{ searchQuery ? 'No photos match your search criteria.' : 'Start by uploading your first photos to the gallery.' }}
            </p>
            <button @click="showUploadModal = true" class="btn btn-primary">
              <i class="fas fa-upload"></i>
              Upload Photos
            </button>
          </div>
        </div>
      </div>

      <!-- Bulk Actions -->
      <div v-if="selectedPhotos.length > 0" class="bulk-actions">
        <div class="bulk-info">
          {{ selectedPhotos.length }} photo(s) selected
        </div>
        <div class="bulk-buttons">
          <button @click="bulkMoveToAlbum" class="btn btn-sm btn-primary">
            <i class="fas fa-folder"></i>
            Move to Album
          </button>
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
          Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, totalPhotos) }} of {{ totalPhotos }} photos
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

    <!-- Upload Photos Modal -->
    <div v-if="showUploadModal" class="modal-overlay" @click="showUploadModal = false">
      <div class="modal large" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">Upload Photos</h3>
          <button @click="showUploadModal = false" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="submitUpload">
            <div class="form-group">
              <label class="form-label">Select Album</label>
              <select v-model="uploadForm.album_id" class="form-control">
                <option value="">Select Album</option>
                <option v-for="album in albums" :key="album.id" :value="album.id">
                  {{ album.name }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Photos</label>
              <div class="upload-area" @drop="handleDrop" @dragover.prevent @dragenter.prevent>
                <input 
                  ref="fileInput"
                  type="file" 
                  multiple 
                  accept="image/*,video/*"
                  @change="handleFileSelect"
                  class="file-input"
                >
                <div class="upload-content">
                  <i class="fas fa-cloud-upload-alt upload-icon"></i>
                  <h4>Drag & Drop Photos Here</h4>
                  <p>or <button type="button" @click="$refs.fileInput.click()" class="upload-link">browse files</button></p>
                  <small>Supports: JPG, PNG, GIF, MP4 (Max: 10MB each)</small>
                </div>
              </div>
            </div>

            <div v-if="selectedFiles.length > 0" class="selected-files">
              <h4>Selected Files ({{ selectedFiles.length }})</h4>
              <div class="files-preview">
                <div 
                  v-for="(file, index) in selectedFiles" 
                  :key="index"
                  class="file-preview"
                >
                  <img 
                    v-if="file.type.startsWith('image/')"
                    :src="getFilePreview(file)" 
                    :alt="file.name"
                    class="preview-thumb"
                  >
                  <div v-else class="file-icon">
                    <i class="fas fa-file"></i>
                  </div>
                  <div class="file-info">
                    <strong>{{ file.name }}</strong>
                    <small>{{ formatFileSize(file.size) }}</small>
                  </div>
                  <button 
                    type="button" 
                    @click="removeFile(index)" 
                    class="remove-file"
                  >
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">
                <input type="checkbox" v-model="uploadForm.makePublic">
                Make photos public
              </label>
            </div>

            <div class="modal-actions">
              <button type="button" @click="showUploadModal = false" class="btn btn-secondary">
                Cancel
              </button>
              <button 
                type="submit" 
                class="btn btn-primary"
                :disabled="selectedFiles.length === 0 || uploading"
              >
                <i class="fas fa-upload"></i>
                {{ uploading ? 'Uploading...' : `Upload ${selectedFiles.length} Photo(s)` }}
              </button>
            </div>
          </form>

          <!-- Upload Progress -->
          <div v-if="uploading" class="upload-progress">
            <div class="progress-bar">
              <div class="progress-fill" :style="{ width: uploadProgress + '%' }"></div>
            </div>
            <div class="progress-text">{{ uploadProgress }}% Complete</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Album Modal -->
    <div v-if="showAlbumModal" class="modal-overlay" @click="showAlbumModal = false">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">{{ editingAlbum ? 'Edit Album' : 'Create Album' }}</h3>
          <button @click="showAlbumModal = false" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="submitAlbum">
            <div class="form-group">
              <label class="form-label">Album Name</label>
              <input 
                v-model="albumForm.name" 
                type="text" 
                class="form-control" 
                required
                placeholder="Enter album name..."
              >
            </div>

            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea 
                v-model="albumForm.description" 
                class="form-control" 
                rows="3"
                placeholder="Brief description of the album..."
              ></textarea>
            </div>

            <div class="form-group">
              <label class="form-label">
                <input type="checkbox" v-model="albumForm.is_public">
                Make album public
              </label>
            </div>

            <div class="modal-actions">
              <button type="button" @click="showAlbumModal = false" class="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                {{ editingAlbum ? 'Update Album' : 'Create Album' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Photo View Modal -->
    <div v-if="showPhotoModal" class="modal-overlay" @click="showPhotoModal = false">
      <div class="modal large photo-modal" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">{{ selectedPhoto?.name }}</h3>
          <button @click="showPhotoModal = false" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="selectedPhoto" class="photo-viewer">
            <div class="photo-display">
              <img 
                v-if="selectedPhoto.type === 'image'"
                :src="selectedPhoto.url" 
                :alt="selectedPhoto.name"
                class="full-photo"
              >
              <video 
                v-else-if="selectedPhoto.type === 'video'"
                :src="selectedPhoto.url"
                controls
                class="full-video"
              >
              </video>
            </div>
            
            <div class="photo-details">
              <div class="detail-group">
                <strong>File Name:</strong> {{ selectedPhoto.name }}
              </div>
              <div class="detail-group">
                <strong>Album:</strong> {{ selectedPhoto.album_name || 'Uncategorized' }}
              </div>
              <div class="detail-group">
                <strong>Size:</strong> {{ formatFileSize(selectedPhoto.size) }}
              </div>
              <div class="detail-group">
                <strong>Uploaded:</strong> {{ formatDateTime(selectedPhoto.created_at) }}
              </div>
              <div class="detail-group">
                <strong>Views:</strong> {{ selectedPhoto.views || 0 }}
              </div>
              <div v-if="selectedPhoto.description" class="detail-group">
                <strong>Description:</strong> {{ selectedPhoto.description }}
              </div>
            </div>

            <div class="photo-actions">
              <button @click="editPhoto(selectedPhoto)" class="btn btn-primary">
                <i class="fas fa-edit"></i>
                Edit Details
              </button>
              <button @click="downloadPhoto(selectedPhoto)" class="btn btn-success">
                <i class="fas fa-download"></i>
                Download
              </button>
              <button @click="sharePhoto(selectedPhoto)" class="btn btn-info">
                <i class="fas fa-share"></i>
                Share
              </button>
              <button @click="deletePhoto(selectedPhoto)" class="btn btn-danger">
                <i class="fas fa-trash"></i>
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Photo Modal -->
    <div v-if="showEditPhotoModal" class="modal-overlay" @click="showEditPhotoModal = false">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3 class="modal-title">Edit Photo Details</h3>
          <button @click="showEditPhotoModal = false" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="submitPhotoEdit">
            <div class="form-group">
              <label class="form-label">Photo Name</label>
              <input 
                v-model="photoForm.name" 
                type="text" 
                class="form-control" 
                required
              >
            </div>

            <div class="form-group">
              <label class="form-label">Album</label>
              <select v-model="photoForm.album_id" class="form-control">
                <option value="">Uncategorized</option>
                <option v-for="album in albums" :key="album.id" :value="album.id">
                  {{ album.name }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea 
                v-model="photoForm.description" 
                class="form-control" 
                rows="3"
                placeholder="Photo description..."
              ></textarea>
            </div>

            <div class="form-group">
              <label class="form-label">
                <input type="checkbox" v-model="photoForm.is_public">
                Make photo public
              </label>
            </div>

            <div class="modal-actions">
              <button type="button" @click="showEditPhotoModal = false" class="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                Update Photo
              </button>
            </div>
          </form>
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
import { api } from '@/utils/api'

export default {
  name: 'AdminGallery',
  setup() {
    const authStore = useAuthStore()
    
    // Reactive data
    const loading = ref(false)
    const uploading = ref(false)
    const uploadProgress = ref(0)
    const showUploadModal = ref(false)
    const showAlbumModal = ref(false)
    const showPhotoModal = ref(false)
    const showEditPhotoModal = ref(false)
    const showSuccessToast = ref(false)
    const successMessage = ref('')
    const selectedPhoto = ref(null)
    const selectedAlbum = ref(null)
    const editingAlbum = ref(null)
    
    // View and filters
    const viewMode = ref('grid')
    const searchQuery = ref('')
    const filterAlbum = ref('')
    const filterType = ref('')
    const sortBy = ref('created_at')
    const sortDirection = ref('desc')
    
    // Selection
    const selectAll = ref(false)
    const selectedPhotos = ref([])
    const selectedFiles = ref([])
    
    // Pagination
    const currentPage = ref(1)
    const itemsPerPage = ref(20)
    const totalPhotos = ref(0)
    
    // Forms
    const uploadForm = reactive({
      album_id: '',
      makePublic: true
    })
    
    const albumForm = reactive({
      name: '',
      description: '',
      is_public: true
    })
    
    const photoForm = reactive({
      name: '',
      album_id: '',
      description: '',
      is_public: true
    })
    
    // Data
    const stats = ref({
      totalPhotos: 0,
      totalAlbums: 0,
      storageUsed: 0,
      recentUploads: 0,
      photosGrowth: 0,
      albumsGrowth: 0,
      storagePercentage: 0
    })
    
    const albums = ref([])
    const photos = ref([])
    
    // Computed properties
    const filteredAlbums = computed(() => {
      let filtered = albums.value
      
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(album => 
          album.name.toLowerCase().includes(query) ||
          album.description.toLowerCase().includes(query)
        )
      }
      
      return filtered
    })
    
    const filteredPhotos = computed(() => {
      let filtered = photos.value
      
      if (selectedAlbum.value) {
        filtered = filtered.filter(photo => photo.album_id === selectedAlbum.value.id)
      }
      
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(photo => 
          photo.name.toLowerCase().includes(query) ||
          photo.description?.toLowerCase().includes(query)
        )
      }
      
      if (filterAlbum.value) {
        filtered = filtered.filter(photo => photo.album_id === filterAlbum.value)
      }
      
      if (filterType.value) {
        filtered = filtered.filter(photo => photo.type === filterType.value)
      }
      
      // Sort
      filtered.sort((a, b) => {
        const aVal = a[sortBy.value]
        const bVal = b[sortBy.value]
        const modifier = sortDirection.value === 'asc' ? 1 : -1
        
        if (aVal < bVal) return -1 * modifier
        if (aVal > bVal) return 1 * modifier
        return 0
      })
      
      return filtered
    })
    
    const totalPages = computed(() => Math.ceil(totalPhotos.value / itemsPerPage.value))
    
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
        const response = await api.get('/admin/gallery/stats')
        
        if (response.data) {
          stats.value = response.data
        }
      } catch (error) {
        console.error('Error fetching stats:', error)
      }
    }
    
    const fetchAlbums = async () => {
      try {
        const response = await api.get('/admin/gallery/albums')
        
        if (response.data) {
          albums.value = response.data.albums || []
        }
      } catch (error) {
        console.error('Error fetching albums:', error)
      }
    }
    
    const fetchPhotos = async () => {
      try {
        loading.value = true
        const params = new URLSearchParams({
          page: currentPage.value,
          limit: itemsPerPage.value,
          search: searchQuery.value,
          album_id: filterAlbum.value,
          sort: sortBy.value,
          order: sortDirection.value
        })
        
        const response = await api.get(`/admin/gallery/photos?${params}`)
        
        if (response) {
          photos.value = response.photos || []
          totalPhotos.value = response.total || 0
        }
      } catch (error) {
        console.error('Error fetching photos:', error)
      } finally {
        loading.value = false
      }
    }
    
    const refreshGallery = async () => {
      await Promise.all([
        fetchStats(),
        fetchAlbums(),
        fetchPhotos()
      ])
      showToast('Gallery refreshed successfully!')
    }
    
    const selectAlbum = (album) => {
      selectedAlbum.value = album
      viewMode.value = 'grid'
    }
    
    const handleFileSelect = (event) => {
      const files = Array.from(event.target.files)
      selectedFiles.value = [...selectedFiles.value, ...files]
    }
    
    const handleDrop = (event) => {
      event.preventDefault()
      const files = Array.from(event.dataTransfer.files)
      selectedFiles.value = [...selectedFiles.value, ...files]
    }
    
    const removeFile = (index) => {
      selectedFiles.value.splice(index, 1)
    }
    
    const getFilePreview = (file) => {
      return URL.createObjectURL(file)
    }
    
    const submitUpload = async () => {
      if (selectedFiles.value.length === 0) return
      
      try {
        uploading.value = true
        uploadProgress.value = 0
        
        const formData = new FormData()
        formData.append('album_id', uploadForm.album_id)
        formData.append('make_public', uploadForm.makePublic)
        
        selectedFiles.value.forEach((file, index) => {
          formData.append(`photos[${index}]`, file)
        })
        
        const response = await fetch('/api/admin/gallery/upload', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          },
          body: formData
        })
        
        if (response.ok) {
          showUploadModal.value = false
          showToast('Photos uploaded successfully!')
          await refreshGallery()
          resetUploadForm()
        }
      } catch (error) {
        console.error('Error uploading photos:', error)
      } finally {
        uploading.value = false
        uploadProgress.value = 0
      }
    }
    
    const submitAlbum = async () => {
      try {
        const url = editingAlbum.value 
          ? `/api/admin/gallery/albums/${editingAlbum.value.id}`
          : '/api/admin/gallery/albums'
        
        const method = editingAlbum.value ? 'PUT' : 'POST'
        
        const response = await fetch(url, {
          method,
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(albumForm)
        })
        
        if (response.ok) {
          showAlbumModal.value = false
          showToast(editingAlbum.value ? 'Album updated successfully!' : 'Album created successfully!')
          await fetchAlbums()
          resetAlbumForm()
        }
      } catch (error) {
        console.error('Error saving album:', error)
      }
    }
    
    const editAlbum = (album) => {
      editingAlbum.value = album
      Object.assign(albumForm, {
        name: album.name,
        description: album.description,
        is_public: album.is_public
      })
      showAlbumModal.value = true
    }
    
    const deleteAlbum = async (album) => {
      if (confirm(`Are you sure you want to delete "${album.name}"? This will also delete all photos in this album.`)) {
        try {
          const response = await fetch(`/api/admin/gallery/albums/${album.id}`, {
            method: 'DELETE',
            headers: {
              'Authorization': `Bearer ${authStore.token}`
            }
          })
          
          if (response.ok) {
            showToast('Album deleted successfully!')
            await refreshGallery()
          }
        } catch (error) {
          console.error('Error deleting album:', error)
        }
      }
    }
    
    const viewPhoto = (photo) => {
      selectedPhoto.value = photo
      showPhotoModal.value = true
    }
    
    const editPhoto = (photo) => {
      selectedPhoto.value = photo
      Object.assign(photoForm, {
        name: photo.name,
        album_id: photo.album_id,
        description: photo.description,
        is_public: photo.is_public
      })
      showEditPhotoModal.value = true
    }
    
    const submitPhotoEdit = async () => {
      try {
        const response = await fetch(`/api/admin/gallery/photos/${selectedPhoto.value.id}`, {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(photoForm)
        })
        
        if (response.ok) {
          showEditPhotoModal.value = false
          showToast('Photo updated successfully!')
          await fetchPhotos()
          resetPhotoForm()
        }
      } catch (error) {
        console.error('Error updating photo:', error)
      }
    }
    
    const downloadPhoto = async (photo) => {
      try {
        const response = await fetch(`/api/admin/gallery/photos/${photo.id}/download`, {
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          }
        })
        
        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = photo.name
          a.click()
          window.URL.revokeObjectURL(url)
        }
      } catch (error) {
        console.error('Error downloading photo:', error)
      }
    }
    
    const sharePhoto = async (photo) => {
      try {
        const response = await fetch(`/api/admin/gallery/photos/${photo.id}/share`, {
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
        console.error('Error sharing photo:', error)
      }
    }
    
    const deletePhoto = async (photo) => {
      if (confirm(`Are you sure you want to delete "${photo.name}"?`)) {
        try {
          const response = await fetch(`/api/admin/gallery/photos/${photo.id}`, {
            method: 'DELETE',
            headers: {
              'Authorization': `Bearer ${authStore.token}`
            }
          })
          
          if (response.ok) {
            showToast('Photo deleted successfully!')
            await fetchPhotos()
            if (showPhotoModal.value) {
              showPhotoModal.value = false
            }
          }
        } catch (error) {
          console.error('Error deleting photo:', error)
        }
      }
    }
    
    const bulkMoveToAlbum = async () => {
      const albumId = prompt('Enter album ID to move photos to:')
      if (albumId) {
        try {
          const response = await fetch('/api/admin/gallery/photos/bulk-move', {
            method: 'POST',
            headers: {
              'Authorization': `Bearer ${authStore.token}`,
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              photo_ids: selectedPhotos.value,
              album_id: albumId
            })
          })
          
          if (response.ok) {
            showToast('Photos moved successfully!')
            selectedPhotos.value = []
            await fetchPhotos()
          }
        } catch (error) {
          console.error('Error moving photos:', error)
        }
      }
    }
    
    const bulkDownload = async () => {
      try {
        const response = await fetch('/api/admin/gallery/photos/bulk-download', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ photo_ids: selectedPhotos.value })
        })
        
        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = 'photos.zip'
          a.click()
          window.URL.revokeObjectURL(url)
          selectedPhotos.value = []
        }
      } catch (error) {
        console.error('Error bulk downloading:', error)
      }
    }
    
    const bulkDelete = async () => {
      if (confirm(`Are you sure you want to delete ${selectedPhotos.value.length} photos?`)) {
        try {
          const response = await fetch('/api/admin/gallery/photos/bulk-delete', {
            method: 'DELETE',
            headers: {
              'Authorization': `Bearer ${authStore.token}`,
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({ photo_ids: selectedPhotos.value })
          })
          
          if (response.ok) {
            showToast('Photos deleted successfully!')
            selectedPhotos.value = []
            await fetchPhotos()
          }
        } catch (error) {
          console.error('Error bulk deleting:', error)
        }
      }
    }
    
    const toggleSelectAll = () => {
      if (selectAll.value) {
        selectedPhotos.value = filteredPhotos.value.map(photo => photo.id)
      } else {
        selectedPhotos.value = []
      }
    }
    
    const resetUploadForm = () => {
      Object.assign(uploadForm, {
        album_id: '',
        makePublic: true
      })
      selectedFiles.value = []
    }
    
    const resetAlbumForm = () => {
      Object.assign(albumForm, {
        name: '',
        description: '',
        is_public: true
      })
      editingAlbum.value = null
    }
    
    const resetPhotoForm = () => {
      Object.assign(photoForm, {
        name: '',
        album_id: '',
        description: '',
        is_public: true
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
    
    // Watchers
    watch([searchQuery, filterAlbum, filterType, currentPage], () => {
      fetchPhotos()
    })
    
    watch(selectedPhotos, (newVal) => {
      selectAll.value = newVal.length === filteredPhotos.value.length && newVal.length > 0
    })
    
    // Lifecycle
    onMounted(async () => {
      await refreshGallery()
    })
    
    return {
      // Reactive data
      loading,
      uploading,
      uploadProgress,
      showUploadModal,
      showAlbumModal,
      showPhotoModal,
      showEditPhotoModal,
      showSuccessToast,
      successMessage,
      selectedPhoto,
      selectedAlbum,
      editingAlbum,
      
      // View and filters
      viewMode,
      searchQuery,
      filterAlbum,
      filterType,
      sortBy,
      sortDirection,
      
      // Selection
      selectAll,
      selectedPhotos,
      selectedFiles,
      
      // Pagination
      currentPage,
      itemsPerPage,
      totalPhotos,
      totalPages,
      visiblePages,
      
      // Forms
      uploadForm,
      albumForm,
      photoForm,
      
      // Data
      stats,
      albums,
      photos,
      filteredAlbums,
      filteredPhotos,
      
      // Methods
      refreshGallery,
      selectAlbum,
      handleFileSelect,
      handleDrop,
      removeFile,
      getFilePreview,
      submitUpload,
      submitAlbum,
      editAlbum,
      deleteAlbum,
      viewPhoto,
      editPhoto,
      submitPhotoEdit,
      downloadPhoto,
      sharePhoto,
      deletePhoto,
      bulkMoveToAlbum,
      bulkDownload,
      bulkDelete,
      toggleSelectAll,
      resetUploadForm,
      resetAlbumForm,
      resetPhotoForm,
      showToast,
      
      // Utility functions
      formatDate,
      formatTime,
      formatDateTime,
      formatFileSize
    }
  }
}
</script>

<style>
.admin-gallery {
  background: white;
}

.gallery-header {
  background: white;
  box-shadow: none;
}

.header-title h1 {
  color: #f7fafc;
}

.stat-card,
.gallery-controls,
.albums-section,
.photos-grid-section,
.photos-list-section {
  box-shadow: none;
  border: 1px solid #e2e8f0;
}

/* Quick Stats */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

/* Albums View */
.albums-section {
  display: flex;
  gap: 0.75rem;
}

/* Photos Grid View */
.photos-grid-section {
  display: flex;
  gap: 0.75rem;
}

/* Photos List View */
.photos-list-section {
  display: grid;
  gap: 0.75rem;
}

/* Pagination */
.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 2rem;
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.pagination-info {
  color: #718096;
  font-size: 0.9rem;
}

.pagination {
  display: flex;
  gap: 0.5rem;
}

.page-btn {
  padding: 0.5rem 1rem;
  border: 2px solid #e2e8f0;
  background: white;
  color: #4a5568;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 500;
}

.page-btn:hover:not(:disabled) {
  border-color: #667eea;
  color: #667eea;
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

/* Modals */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  animation: slideUp 0.3s ease;
}

.modal.large {
  max-width: 800px;
}

.modal.photo-modal {
  max-width: 1000px;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0;
}

.modal-close {
  width: 40px;
  height: 40px;
  border: none;
  background: #f7fafc;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #718096;
}

.modal-close:hover {
  background: #e2e8f0;
  color: #4a5568;
}

.modal-body {
  padding: 2rem;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
}

/* Form Elements */
.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #4a5568;
}

.form-label input[type="checkbox"] {
  margin-right: 0.5rem;
  accent-color: #667eea;
}

/* Upload Area */
.upload-area {
  border: 2px dashed #cbd5e0;
  border-radius: 12px;
  padding: 3rem 2rem;
  text-align: center;
  transition: all 0.3s ease;
  cursor: pointer;
  background: #f7fafc;
}

.upload-area:hover {
  border-color: #667eea;
  background: #edf2f7;
}

.file-input {
  display: none;
}

.upload-content {
  pointer-events: none;
}

.upload-icon {
  font-size: 3rem;
  color: #a0aec0;
  margin-bottom: 1rem;
}

.upload-content h4 {
  font-size: 1.2rem;
  color: #4a5568;
  margin: 0 0 0.5rem 0;
}

.upload-content p {
  color: #718096;
  margin: 0 0 0.5rem 0;
}

.upload-link {
  color: #667eea;
  text-decoration: underline;
  background: none;
  border: none;
  cursor: pointer;
  font-size: inherit;
}

.upload-content small {
  color: #a0aec0;
  font-size: 0.8rem;
}

/* Selected Files */
.selected-files {
  margin-top: 1.5rem;
}

.selected-files h4 {
  color: #4a5568;
  margin-bottom: 1rem;
}

.files-preview {
  display: grid;
  gap: 1rem;
  max-height: 300px;
  overflow-y: auto;
}

.file-preview {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f7fafc;
  border-radius: 8px;
}

.preview-thumb {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 6px;
}

.file-icon {
  width: 60px;
  height: 60px;
  background: #e2e8f0;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: #a0aec0;
}

.file-info {
  flex: 1;
}

.file-info strong {
  display: block;
  color: #2d3748;
  margin-bottom: 0.25rem;
}

.file-info small {
  color: #718096;
}

.remove-file {
  width: 32px;
  height: 32px;
  border: none;
  background: #fed7d7;
  color: #e53e3e;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.remove-file:hover {
  background: #feb2b2;
}

/* Upload Progress */
.upload-progress {
  margin-top: 1.5rem;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: #e2e8f0;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  transition: width 0.3s ease;
}

.progress-text {
  text-align: center;
  color: #4a5568;
  font-weight: 600;
}

/* Photo Viewer */
.photo-viewer {
  text-align: center;
}

.photo-display {
  margin-bottom: 2rem;
}

.full-photo,
.full-video {
  max-width: 100%;
  max-height: 60vh;
  border-radius: 12px;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
}

.photo-details {
  background: #f7fafc;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  text-align: left;
}

.detail-group {
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.detail-group:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}

.detail-group strong {
  color: #4a5568;
  margin-right: 0.5rem;
}

.photo-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #718096;
}

.empty-icon {
  font-size: 4rem;
  color: #cbd5e0;
  margin-bottom: 1rem;
}

.empty-title {
  font-size: 1.5rem;
  color: #4a5568;
  margin-bottom: 1rem;
}

.empty-message {
  font-size: 1rem;
  margin-bottom: 2rem;
  max-width: 400px;
  margin-left: auto;
  margin-right: auto;
}

/* Success Toast */
.success-toast {
  position: fixed;
  top: 2rem;
  right: 2rem;
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
  color: white;
  padding: 1rem 1.5rem;
  border-radius: 12px;
  box-shadow: 0 8px 30px rgba(72, 187, 120, 0.3);
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 600;
  z-index: 1001;
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

/* Responsive Design */
@media (max-width: 1200px) {
  .gallery-content {
    padding: 1.5rem;
  }
  
  .stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  }
}

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
  
  .gallery-content {
    padding: 1rem;
  }
  
  .gallery-controls {
    flex-direction: column;
    align-items: stretch;
  }
  
  .controls-left {
    justify-content: center;
  }
  
  .filter-controls {
    justify-content: center;
  }
  
  .search-input {
    width: 100%;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .albums-grid {
    grid-template-columns: 1fr;
  }
  
  .photos-grid {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  }
  
  .pagination-container {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .bulk-actions {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .modal {
    width: 95%;
    margin: 1rem;
  }
  
  .modal-body {
    padding: 1.5rem;
  }
  
  .photo-actions {
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .header-title h1 {
    font-size: 1.5rem;
  }
  
  .stat-value {
    font-size: 2rem;
  }
  
  .photos-grid {
    grid-template-columns: 1fr;
  }
  
  .action-buttons {
    flex-direction: column;
  }
  
  .modal-actions {
    flex-direction: column;
  }
}

/* Print Styles */
@media print {
  .admin-gallery {
    background: white !important;
  }
  
  .loading-overlay,
  .header-actions,
  .gallery-controls,
  .bulk-actions,
  .pagination-container,
  .modal-overlay,
  .success-toast {
    display: none !important;
  }
  
  .gallery-header {
    background: white !important;
    box-shadow: none !important;
  }
  
  .stat-card,
  .albums-section,
  .photos-grid-section,
  .photos-list-section {
    box-shadow: none !important;
    border: 1px solid #e2e8f0 !important;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .admin-gallery {
    background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
  }
  
  .gallery-header {
    background: rgba(26, 32, 44, 0.95);
    border-bottom-color: rgba(255, 255, 255, 0.1);
  }
  
  .header-title h1 {
    color: #f7fafc;
  }
  
  .stat-card,
  .gallery-controls,
  .albums-section,
  .photos-grid-section,
  .photos-list-section,
  .pagination-container,
  .bulk-actions {
    background: #2d3748;
    color: #f7fafc;
  }
  
  .form-control {
    background: #4a5568;
    border-color: #718096;
    color: #f7fafc;
  }
  
  .modal {
    background: #2d3748;
    color: #f7fafc;
  }
  
  .photos-table th {
    background: #4a5568;
  }
  
  .photo-row:hover {
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
.view-btn:focus,
.page-btn:focus,
.action-btn:focus {
  outline: 2px solid #667eea;
  outline-offset: 2px;
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .btn,
  .form-control,
  .stat-card,
  .album-card,
  .photo-card {
    border: 2px solid #000;
  }
  
  .btn-primary {
    background: #000;
    color: #fff;
  }
  
  .btn-secondary {
    background: #fff;
    color: #000;
  }
}

/* Custom Scrollbar */
.photos-table-container::-webkit-scrollbar,
.files-preview::-webkit-scrollbar,
.modal::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}
</style>