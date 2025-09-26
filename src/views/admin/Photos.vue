<template>
  <div class="admin-photos">
    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
    </div>

    <!-- Photos Header -->
    <div class="photos-header">
      <div class="header-content">
        <div class="header-title">
          <i class="fas fa-camera icon"></i>
          <h1>Photos Management</h1>
        </div>
        <div class="header-actions">
          <button @click="refreshPhotos" class="btn btn-secondary">
            <i class="fas fa-sync-alt"></i>
            Refresh
          </button>
          <button @click="showUploadModal = true" class="btn btn-primary">
            <i class="fas fa-upload"></i>
            Upload Photos
          </button>
        </div>
      </div>
    </div>

    <div class="photos-content">
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
            <span class="stat-title">Published</span>
            <div class="stat-icon success">
              <i class="fas fa-check-circle"></i>
            </div>
          </div>
          <div class="stat-value">{{ stats.publishedPhotos }}</div>
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
          <div class="stat-value">{{ stats.pendingPhotos }}</div>
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
            placeholder="Search photos..."
            @input="filterPhotos"
          >
        </div>
        <div class="filter-controls">
          <select v-model="statusFilter" @change="filterPhotos" class="form-select">
            <option value="">All Status</option>
            <option value="published">Published</option>
            <option value="pending">Pending</option>
            <option value="draft">Draft</option>
          </select>
          <select v-model="categoryFilter" @change="filterPhotos" class="form-select">
            <option value="">All Categories</option>
            <option value="events">Events</option>
            <option value="activities">Activities</option>
            <option value="gallery">Gallery</option>
            <option value="news">News</option>
          </select>
        </div>
      </div>

      <!-- Photos Grid -->
      <div class="photos-grid">
        <div 
          v-for="photo in filteredPhotos" 
          :key="photo.id" 
          class="photo-card"
          :class="{ 'selected': selectedPhotos.includes(photo.id) }"
        >
          <div class="photo-image">
            <img :src="photo.thumbnail" :alt="photo.title" @error="handleImageError">
            <div class="photo-overlay">
              <div class="photo-actions">
                <button @click="viewPhoto(photo)" class="btn btn-sm btn-light">
                  <i class="fas fa-eye"></i>
                </button>
                <button @click="editPhoto(photo)" class="btn btn-sm btn-primary">
                  <i class="fas fa-edit"></i>
                </button>
                <button @click="deletePhoto(photo)" class="btn btn-sm btn-danger">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
              <div class="photo-select">
                <input 
                  type="checkbox" 
                  :value="photo.id" 
                  v-model="selectedPhotos"
                  class="form-check-input"
                >
              </div>
            </div>
          </div>
          <div class="photo-info">
            <h5 class="photo-title">{{ photo.title }}</h5>
            <p class="photo-description">{{ photo.description }}</p>
            <div class="photo-meta">
              <span class="photo-category">{{ photo.category }}</span>
              <span class="photo-status" :class="photo.status">{{ photo.status }}</span>
            </div>
            <div class="photo-date">
              <i class="fas fa-calendar"></i>
              {{ formatDate(photo.created_at) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div class="pagination-wrapper" v-if="totalPages > 1">
        <nav aria-label="Photos pagination">
          <ul class="pagination">
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <button class="page-link" @click="changePage(currentPage - 1)" :disabled="currentPage === 1">
                <i class="fas fa-chevron-left"></i>
              </button>
            </li>
            <li 
              v-for="page in visiblePages" 
              :key="page" 
              class="page-item" 
              :class="{ active: page === currentPage }"
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

    <!-- Upload Modal -->
    <div v-if="showUploadModal" class="modal-overlay" @click="closeUploadModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Upload Photos</h3>
          <button @click="closeUploadModal" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="upload-area" @drop="handleDrop" @dragover.prevent @dragenter.prevent>
            <input 
              ref="fileInput" 
              type="file" 
              multiple 
              accept="image/*" 
              @change="handleFileSelect"
              style="display: none;"
            >
            <div class="upload-content">
              <i class="fas fa-cloud-upload-alt"></i>
              <p>Drag and drop photos here or <button @click="$refs.fileInput.click()" class="btn-link">browse files</button></p>
            </div>
          </div>
          <div v-if="uploadFiles.length > 0" class="upload-preview">
            <h4>Selected Files:</h4>
            <div class="file-list">
              <div v-for="(file, index) in uploadFiles" :key="index" class="file-item">
                <img :src="file.preview" :alt="file.name" class="file-thumbnail">
                <div class="file-info">
                  <span class="file-name">{{ file.name }}</span>
                  <span class="file-size">{{ formatFileSize(file.size) }}</span>
                </div>
                <button @click="removeFile(index)" class="btn btn-sm btn-danger">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeUploadModal" class="btn btn-secondary">Cancel</button>
          <button @click="uploadPhotos" class="btn btn-primary" :disabled="uploadFiles.length === 0 || uploading">
            <i class="fas fa-upload"></i>
            {{ uploading ? 'Uploading...' : 'Upload Photos' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import api from '@/utils/api'

const router = useRouter()
const toast = useToast()

// Reactive data
const loading = ref(false)
const photos = ref([])
const stats = ref({
  totalPhotos: 0,
  publishedPhotos: 0,
  pendingPhotos: 0,
  photosGrowth: 0,
  publishedGrowth: 0,
  storageUsed: 0
})

// Filters and search
const searchQuery = ref('')
const statusFilter = ref('')
const categoryFilter = ref('')
const selectedPhotos = ref([])

// Pagination
const currentPage = ref(1)
const itemsPerPage = ref(12)
const totalItems = ref(0)

// Upload modal
const showUploadModal = ref(false)
const uploadFiles = ref([])
const uploading = ref(false)

// Computed properties
const filteredPhotos = computed(() => {
  let filtered = photos.value

  if (searchQuery.value) {
    filtered = filtered.filter(photo => 
      photo.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      photo.description.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  }

  if (statusFilter.value) {
    filtered = filtered.filter(photo => photo.status === statusFilter.value)
  }

  if (categoryFilter.value) {
    filtered = filtered.filter(photo => photo.category === categoryFilter.value)
  }

  return filtered
})

const totalPages = computed(() => Math.ceil(totalItems.value / itemsPerPage.value))

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
const loadPhotos = async () => {
  try {
    loading.value = true
    const response = await api.get('/admin/photos', {
      params: {
        page: currentPage.value,
        limit: itemsPerPage.value,
        search: searchQuery.value,
        status: statusFilter.value,
        category: categoryFilter.value
      }
    })
    
    photos.value = response.data.photos || []
    totalItems.value = response.data.total || 0
    
    // Load stats
    const statsResponse = await api.get('/admin/photos/stats')
    stats.value = statsResponse.data || stats.value
    
  } catch (error) {
    console.error('Error loading photos:', error)
    toast.error('Failed to load photos')
  } finally {
    loading.value = false
  }
}

const refreshPhotos = () => {
  loadPhotos()
}

const filterPhotos = () => {
  currentPage.value = 1
  loadPhotos()
}

const changePage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    loadPhotos()
  }
}

const viewPhoto = (photo) => {
  // Open photo in modal or new tab
  window.open(photo.url, '_blank')
}

const editPhoto = (photo) => {
  router.push(`/admin/photos/${photo.id}/edit`)
}

const deletePhoto = async (photo) => {
  if (confirm(`Are you sure you want to delete "${photo.title}"?`)) {
    try {
      await api.delete(`/admin/photos/${photo.id}`)
      toast.success('Photo deleted successfully')
      loadPhotos()
    } catch (error) {
      console.error('Error deleting photo:', error)
      toast.error('Failed to delete photo')
    }
  }
}

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files)
  processFiles(files)
}

const handleDrop = (event) => {
  event.preventDefault()
  const files = Array.from(event.dataTransfer.files)
  processFiles(files)
}

const processFiles = (files) => {
  files.forEach(file => {
    if (file.type.startsWith('image/')) {
      const reader = new FileReader()
      reader.onload = (e) => {
        uploadFiles.value.push({
          file,
          name: file.name,
          size: file.size,
          preview: e.target.result
        })
      }
      reader.readAsDataURL(file)
    }
  })
}

const removeFile = (index) => {
  uploadFiles.value.splice(index, 1)
}

const uploadPhotos = async () => {
  if (uploadFiles.value.length === 0) return

  try {
    uploading.value = true
    const formData = new FormData()
    
    uploadFiles.value.forEach((fileObj, index) => {
      formData.append(`photos[${index}]`, fileObj.file)
    })

    await api.post('/admin/photos/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    toast.success('Photos uploaded successfully')
    closeUploadModal()
    loadPhotos()
  } catch (error) {
    console.error('Error uploading photos:', error)
    toast.error('Failed to upload photos')
  } finally {
    uploading.value = false
  }
}

const closeUploadModal = () => {
  showUploadModal.value = false
  uploadFiles.value = []
}

const handleImageError = (event) => {
  event.target.src = '/assets/images/placeholder-image.svg'
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// Lifecycle
onMounted(() => {
  loadPhotos()
})
</script>

<style scoped>
.admin-photos {
  padding: 20px;
  background-color: #f8f9fa;
  min-height: 100vh;
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
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

.photos-header {
  background: white;
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-title {
  display: flex;
  align-items: center;
  gap: 15px;
}

.header-title .icon {
  font-size: 2rem;
  color: #007bff;
}

.header-title h1 {
  margin: 0;
  color: #333;
  font-weight: 600;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
  margin-bottom: 15px;
}

.stat-title {
  font-size: 0.9rem;
  color: #666;
  font-weight: 500;
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
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
  color: #333;
  margin-bottom: 10px;
}

.stat-change {
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  gap: 5px;
}

.stat-change.positive { color: #28a745; }

.filters-section {
  background: white;
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  display: flex;
  gap: 20px;
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
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #666;
}

.search-box input {
  width: 100%;
  padding: 12px 15px 12px 45px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
}

.filter-controls {
  display: flex;
  gap: 15px;
}

.form-select {
  padding: 10px 15px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: white;
  min-width: 150px;
}

.photos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.photo-card {
  background: white;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
}

.photo-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.photo-card.selected {
  border: 2px solid #007bff;
}

.photo-image {
  position: relative;
  height: 200px;
  overflow: hidden;
}

.photo-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.photo-overlay {
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
  transition: opacity 0.2s;
}

.photo-card:hover .photo-overlay {
  opacity: 1;
}

.photo-actions {
  display: flex;
  gap: 10px;
}

.photo-select {
  position: absolute;
  top: 10px;
  right: 10px;
}

.photo-info {
  padding: 15px;
}

.photo-title {
  margin: 0 0 8px 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #333;
}

.photo-description {
  margin: 0 0 10px 0;
  color: #666;
  font-size: 0.9rem;
  line-height: 1.4;
}

.photo-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.photo-category {
  background: #e9ecef;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.8rem;
  color: #495057;
}

.photo-status {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 500;
  text-transform: capitalize;
}

.photo-status.published {
  background: #d4edda;
  color: #155724;
}

.photo-status.pending {
  background: #fff3cd;
  color: #856404;
}

.photo-status.draft {
  background: #f8d7da;
  color: #721c24;
}

.photo-date {
  display: flex;
  align-items: center;
  gap: 5px;
  color: #666;
  font-size: 0.85rem;
}

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
  border: 1px solid #ddd;
  background: white;
  color: #007bff;
  text-decoration: none;
  border-radius: 5px;
  cursor: pointer;
  transition: all 0.2s;
}

.page-link:hover {
  background: #007bff;
  color: white;
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
  border-radius: 10px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.modal-header h3 {
  margin: 0;
  color: #333;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.2rem;
  color: #666;
  cursor: pointer;
  padding: 5px;
}

.modal-body {
  padding: 20px;
}

.upload-area {
  border: 2px dashed #ddd;
  border-radius: 10px;
  padding: 40px;
  text-align: center;
  margin-bottom: 20px;
  transition: border-color 0.2s;
}

.upload-area:hover {
  border-color: #007bff;
}

.upload-content i {
  font-size: 3rem;
  color: #007bff;
  margin-bottom: 15px;
}

.btn-link {
  background: none;
  border: none;
  color: #007bff;
  text-decoration: underline;
  cursor: pointer;
}

.upload-preview h4 {
  margin-bottom: 15px;
  color: #333;
}

.file-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.file-item {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 10px;
  border: 1px solid #eee;
  border-radius: 8px;
}

.file-thumbnail {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: 5px;
}

.file-info {
  flex: 1;
}

.file-name {
  display: block;
  font-weight: 500;
  color: #333;
}

.file-size {
  display: block;
  font-size: 0.85rem;
  color: #666;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 20px;
  border-top: 1px solid #eee;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-primary:hover {
  background: #0056b3;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #545b62;
}

.btn-danger {
  background: #dc3545;
  color: white;
}

.btn-danger:hover {
  background: #c82333;
}

.btn-light {
  background: #f8f9fa;
  color: #333;
}

.btn-light:hover {
  background: #e2e6ea;
}

.btn-sm {
  padding: 5px 10px;
  font-size: 0.875rem;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .admin-photos {
    padding: 10px;
  }
  
  .header-content {
    flex-direction: column;
    gap: 15px;
    align-items: stretch;
  }
  
  .header-actions {
    justify-content: center;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .filters-section {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-box {
    min-width: auto;
  }
  
  .filter-controls {
    flex-wrap: wrap;
  }
  
  .photos-grid {
    grid-template-columns: 1fr;
  }
}
</style>