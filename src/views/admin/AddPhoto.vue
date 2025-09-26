<template>
  <div class="add-photo-page">
    <!-- Header Section -->
    <div class="page-header">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-6">
            <h1 class="page-title">
              <i class="fas fa-image me-3"></i>
              Add Photos
            </h1>
            <p class="page-subtitle">Upload and organize photos for gallery albums</p>
          </div>
          <div class="col-md-6 text-md-end">
            <router-link to="/admin/gallery" class="btn btn-outline-secondary me-2">
              <i class="fas fa-arrow-left me-2"></i>
              Back to Gallery
            </router-link>
            <router-link to="/admin/albums" class="btn btn-info">
              <i class="fas fa-folder me-2"></i>
              Manage Albums
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Upload Section -->
    <div class="upload-section">
      <div class="container-fluid">
        <div class="row">
          <!-- Upload Area -->
          <div class="col-lg-8">
            <div class="card mb-4">
              <div class="card-header">
                <h5 class="card-title mb-0">Upload Photos</h5>
              </div>
              <div class="card-body">
                <!-- Drag & Drop Area -->
                <div 
                  class="upload-area"
                  @dragover.prevent
                  @dragenter.prevent
                  @drop.prevent="handleDrop"
                  @click="$refs.fileInput.click()"
                  :class="{ 'drag-over': isDragOver }"
                  @dragenter="isDragOver = true"
                  @dragleave="isDragOver = false"
                >
                  <div class="upload-content">
                    <i class="fas fa-cloud-upload-alt fa-4x text-primary mb-3"></i>
                    <h4>Drag & Drop Photos Here</h4>
                    <p class="text-muted mb-3">or click to browse files</p>
                    <button type="button" class="btn btn-primary">
                      <i class="fas fa-plus me-2"></i>
                      Select Photos
                    </button>
                    <div class="upload-info mt-3">
                      <small class="text-muted">
                        Supported formats: JPG, PNG, GIF, WebP | Max size: 5MB per file
                      </small>
                    </div>
                  </div>
                </div>
                
                <input 
                  type="file" 
                  ref="fileInput" 
                  @change="handleFileSelect"
                  multiple
                  accept="image/*"
                  class="d-none"
                >

                <!-- Upload Progress -->
                <div v-if="uploadProgress.length > 0" class="upload-progress mt-4">
                  <h6>Upload Progress</h6>
                  <div v-for="(progress, index) in uploadProgress" :key="index" class="progress-item mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <small class="text-muted">{{ progress.name }}</small>
                      <small class="text-muted">{{ progress.percent }}%</small>
                    </div>
                    <div class="progress">
                      <div 
                        class="progress-bar" 
                        :style="{ width: progress.percent + '%' }"
                        :class="progress.status === 'error' ? 'bg-danger' : 'bg-success'"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Photo Preview Grid -->
            <div v-if="photos.length > 0" class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Uploaded Photos ({{ photos.length }})</h5>
                <div>
                  <button 
                    class="btn btn-sm btn-outline-danger me-2"
                    @click="clearAllPhotos"
                  >
                    <i class="fas fa-trash me-1"></i>
                    Clear All
                  </button>
                  <button 
                    class="btn btn-sm btn-success"
                    @click="saveAllPhotos"
                    :disabled="loading || photos.length === 0"
                  >
                    <i class="fas fa-save me-1"></i>
                    {{ loading ? 'Saving...' : 'Save All Photos' }}
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div class="photo-grid">
                  <div 
                    v-for="(photo, index) in photos" 
                    :key="index" 
                    class="photo-item"
                  >
                    <div class="photo-preview">
                      <img :src="photo.preview" :alt="photo.title" class="photo-img">
                      <div class="photo-overlay">
                        <button 
                          class="btn btn-sm btn-primary me-1"
                          @click="editPhoto(index)"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <button 
                          class="btn btn-sm btn-danger"
                          @click="removePhoto(index)"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </div>
                    <div class="photo-info">
                      <h6 class="photo-title">{{ photo.title || 'Untitled' }}</h6>
                      <small class="text-muted">{{ formatFileSize(photo.size) }}</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Settings Sidebar -->
          <div class="col-lg-4">
            <!-- Album Selection -->
            <div class="card mb-4">
              <div class="card-header">
                <h6 class="card-title mb-0">Album Settings</h6>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label required">Select Album</label>
                  <select class="form-select" v-model="selectedAlbum" required>
                    <option value="">Choose Album</option>
                    <option v-for="album in albums" :key="album.id" :value="album.id">
                      {{ album.name }}
                    </option>
                  </select>
                  <div class="form-text">
                    <router-link to="/admin/albums" class="text-decoration-none">
                      <i class="fas fa-plus me-1"></i>
                      Create New Album
                    </router-link>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Default Tags</label>
                  <input 
                    type="text" 
                    class="form-control"
                    v-model="defaultTags"
                    placeholder="Enter tags separated by commas"
                  >
                  <div class="form-text">These tags will be applied to all uploaded photos</div>
                </div>

                <div class="mb-3">
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      v-model="autoResize"
                      id="autoResize"
                    >
                    <label class="form-check-label" for="autoResize">
                      Auto-resize large images
                    </label>
                  </div>
                  <div class="form-text">Resize images larger than 1920px width</div>
                </div>

                <div class="mb-3">
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      v-model="generateThumbnails"
                      id="generateThumbnails"
                    >
                    <label class="form-check-label" for="generateThumbnails">
                      Generate thumbnails
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Photo Statistics -->
            <div class="card mb-4">
              <div class="card-header">
                <h6 class="card-title mb-0">Upload Statistics</h6>
              </div>
              <div class="card-body">
                <div class="stat-item d-flex justify-content-between mb-2">
                  <span>Total Photos:</span>
                  <strong>{{ photos.length }}</strong>
                </div>
                <div class="stat-item d-flex justify-content-between mb-2">
                  <span>Total Size:</span>
                  <strong>{{ formatFileSize(totalSize) }}</strong>
                </div>
                <div class="stat-item d-flex justify-content-between mb-2">
                  <span>Average Size:</span>
                  <strong>{{ photos.length > 0 ? formatFileSize(totalSize / photos.length) : '0 B' }}</strong>
                </div>
                <div class="stat-item d-flex justify-content-between">
                  <span>Status:</span>
                  <span class="badge bg-info">Ready to Upload</span>
                </div>
              </div>
            </div>

            <!-- Recent Albums -->
            <div class="card">
              <div class="card-header">
                <h6 class="card-title mb-0">Recent Albums</h6>
              </div>
              <div class="card-body">
                <div v-if="recentAlbums.length === 0" class="text-center text-muted py-3">
                  <i class="fas fa-folder-open fa-2x mb-2"></i>
                  <p class="mb-0">No recent albums</p>
                </div>
                <div v-else>
                  <div 
                    v-for="album in recentAlbums" 
                    :key="album.id"
                    class="recent-album-item d-flex align-items-center mb-2"
                    @click="selectedAlbum = album.id"
                  >
                    <div class="album-thumbnail me-3">
                      <img 
                        v-if="album.cover_image" 
                        :src="album.cover_image" 
                        :alt="album.name"
                        class="album-thumb"
                      >
                      <div v-else class="album-thumb-placeholder">
                        <i class="fas fa-folder"></i>
                      </div>
                    </div>
                    <div class="album-info flex-grow-1">
                      <h6 class="album-name mb-0">{{ album.name }}</h6>
                      <small class="text-muted">{{ album.photo_count }} photos</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Photo Edit Modal -->
    <div v-if="editingPhoto" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit Photo Details</h5>
            <button type="button" class="btn-close" @click="closeEditModal"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6">
                <img :src="editingPhoto.preview" alt="Photo" class="img-fluid rounded">
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Photo Title</label>
                  <input 
                    type="text" 
                    class="form-control"
                    v-model="editingPhoto.title"
                    placeholder="Enter photo title"
                  >
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea 
                    class="form-control"
                    v-model="editingPhoto.description"
                    rows="3"
                    placeholder="Enter photo description"
                  ></textarea>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Tags</label>
                  <input 
                    type="text" 
                    class="form-control"
                    v-model="editingPhoto.tags"
                    placeholder="Enter tags separated by commas"
                  >
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Date Taken</label>
                  <input 
                    type="date" 
                    class="form-control"
                    v-model="editingPhoto.date_taken"
                  >
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Photographer</label>
                  <input 
                    type="text" 
                    class="form-control"
                    v-model="editingPhoto.photographer"
                    placeholder="Enter photographer name"
                  >
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeEditModal">
              Cancel
            </button>
            <button type="button" class="btn btn-primary" @click="savePhotoEdit">
              Save Changes
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-if="editingPhoto" class="modal-backdrop fade show"></div>
  </div>
</template>

<script>
import { api } from '@/utils/api'

export default {
  name: 'AddPhoto',
  data() {
    return {
      loading: false,
      isDragOver: false,
      photos: [],
      uploadProgress: [],
      selectedAlbum: '',
      defaultTags: '',
      autoResize: true,
      generateThumbnails: true,
      editingPhoto: null,
      editingIndex: -1,
      albums: [
        { id: 1, name: 'Events 2024', photo_count: 45, cover_image: null },
        { id: 2, name: 'Community Activities', photo_count: 32, cover_image: null },
        { id: 3, name: 'Festivals', photo_count: 67, cover_image: null },
        { id: 4, name: 'Meetings', photo_count: 23, cover_image: null },
        { id: 5, name: 'Awards & Recognition', photo_count: 18, cover_image: null }
      ],
      recentAlbums: []
    }
  },

  computed: {
    totalSize() {
      return this.photos.reduce((total, photo) => total + photo.size, 0)
    }
  },

  methods: {
    handleFileSelect(event) {
      const files = Array.from(event.target.files)
      this.processFiles(files)
    },

    handleDrop(event) {
      this.isDragOver = false
      const files = Array.from(event.dataTransfer.files)
      this.processFiles(files)
    },

    processFiles(files) {
      const imageFiles = files.filter(file => file.type.startsWith('image/'))
      
      if (imageFiles.length !== files.length) {
        this.$toast.warning('Some files were skipped (only image files are allowed)')
      }

      imageFiles.forEach(file => {
        if (file.size > 5 * 1024 * 1024) { // 5MB limit
          this.$toast.error(`File ${file.name} is too large (max 5MB)`)
          return
        }

        const reader = new FileReader()
        reader.onload = (e) => {
          const photo = {
            file: file,
            preview: e.target.result,
            title: file.name.replace(/\.[^/.]+$/, ""), // Remove extension
            description: '',
            tags: this.defaultTags,
            date_taken: new Date().toISOString().split('T')[0],
            photographer: '',
            size: file.size,
            type: file.type
          }
          this.photos.push(photo)
        }
        reader.readAsDataURL(file)
      })
    },

    removePhoto(index) {
      this.photos.splice(index, 1)
    },

    clearAllPhotos() {
      if (confirm('Are you sure you want to remove all photos?')) {
        this.photos = []
        this.uploadProgress = []
      }
    },

    editPhoto(index) {
      this.editingIndex = index
      this.editingPhoto = { ...this.photos[index] }
    },

    closeEditModal() {
      this.editingPhoto = null
      this.editingIndex = -1
    },

    savePhotoEdit() {
      if (this.editingIndex >= 0) {
        this.photos[this.editingIndex] = { ...this.editingPhoto }
        this.closeEditModal()
        this.$toast.success('Photo details updated')
      }
    },

    async saveAllPhotos() {
      if (!this.selectedAlbum) {
        this.$toast.error('Please select an album')
        return
      }

      if (this.photos.length === 0) {
        this.$toast.error('No photos to upload')
        return
      }

      try {
        this.loading = true
        this.uploadProgress = []

        // Simulate upload progress for each photo
        for (let i = 0; i < this.photos.length; i++) {
          const photo = this.photos[i]
          
          // Add to progress tracking
          this.uploadProgress.push({
            name: photo.title,
            percent: 0,
            status: 'uploading'
          })

          // Simulate upload progress
          for (let progress = 0; progress <= 100; progress += 20) {
            this.uploadProgress[i].percent = progress
            await new Promise(resolve => setTimeout(resolve, 100))
          }

          this.uploadProgress[i].status = 'completed'
        }

        // Mock API call - replace with actual API
        console.log('Uploading photos to album:', this.selectedAlbum)
        console.log('Photos:', this.photos)

        this.$toast.success(`${this.photos.length} photos uploaded successfully!`)
        
        // Reset form
        this.photos = []
        this.uploadProgress = []
        this.$refs.fileInput.value = ''
        
      } catch (error) {
        console.error('Error uploading photos:', error)
        this.$toast.error('Failed to upload photos')
        
        // Mark failed uploads
        this.uploadProgress.forEach(progress => {
          if (progress.status === 'uploading') {
            progress.status = 'error'
          }
        })
      } finally {
        this.loading = false
      }
    },

    formatFileSize(bytes) {
      if (bytes === 0) return '0 B'
      const k = 1024
      const sizes = ['B', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    },

    loadRecentAlbums() {
      // Mock recent albums - replace with actual API
      this.recentAlbums = this.albums.slice(0, 3)
    }
  },

  mounted() {
    document.title = 'Add Photos - Admin - BHRC'
    this.loadRecentAlbums()
  }
}
</script>

<style scoped>
.add-photo-page {
  background-color: #f8f9fa;
  min-height: 100vh;
}

.page-header {
  background: white;
  border-bottom: 1px solid #e9ecef;
  padding: 2rem 0;
  margin-bottom: 2rem;
}

.page-title {
  font-size: 2rem;
  font-weight: 600;
  color: #333;
  margin-bottom: 0.5rem;
}

.page-subtitle {
  color: #666;
  margin-bottom: 0;
}

.upload-section {
  padding-bottom: 3rem;
}

.card {
  border: none;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
  padding: 1rem 1.5rem;
}

.card-title {
  font-weight: 600;
  color: #333;
}

.upload-area {
  border: 3px dashed #dee2e6;
  border-radius: 12px;
  padding: 3rem 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  background: #fafafa;
}

.upload-area:hover,
.upload-area.drag-over {
  border-color: #007bff;
  background-color: #f0f8ff;
}

.upload-content h4 {
  color: #333;
  margin-bottom: 1rem;
}

.upload-info {
  padding-top: 1rem;
  border-top: 1px solid #e9ecef;
}

.photo-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
}

.photo-item {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  transition: transform 0.2s ease;
}

.photo-item:hover {
  transform: translateY(-2px);
}

.photo-preview {
  position: relative;
  aspect-ratio: 1;
  overflow: hidden;
}

.photo-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.photo-overlay {
  position: absolute;
  top: 8px;
  right: 8px;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.photo-item:hover .photo-overlay {
  opacity: 1;
}

.photo-info {
  padding: 0.75rem;
}

.photo-title {
  font-size: 0.9rem;
  font-weight: 500;
  margin-bottom: 0.25rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.progress-item {
  background: #f8f9fa;
  padding: 0.75rem;
  border-radius: 6px;
}

.form-label.required::after {
  content: ' *';
  color: #dc3545;
}

.recent-album-item {
  padding: 0.5rem;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.recent-album-item:hover {
  background-color: #f8f9fa;
}

.album-thumb,
.album-thumb-placeholder {
  width: 40px;
  height: 40px;
  border-radius: 6px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.album-thumb {
  object-fit: cover;
}

.album-thumb-placeholder {
  background: #e9ecef;
  color: #6c757d;
}

.album-name {
  font-size: 0.9rem;
  font-weight: 500;
}

.stat-item {
  padding: 0.25rem 0;
}

/* Modal Styles */
.modal {
  background: rgba(0,0,0,0.5);
}

.modal-content {
  border: none;
  box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-header {
    padding: 1rem 0;
  }
  
  .page-title {
    font-size: 1.5rem;
  }
  
  .upload-area {
    padding: 2rem 1rem;
  }
  
  .photo-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  }
}
</style>