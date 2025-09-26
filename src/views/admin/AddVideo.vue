<template>
  <div class="add-video-page">
    <!-- Header Section -->
    <div class="page-header">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-6">
            <h1 class="page-title">
              <i class="fas fa-video me-3"></i>
              Add Videos
            </h1>
            <p class="page-subtitle">Upload and manage videos for gallery</p>
          </div>
          <div class="col-md-6 text-md-end">
            <router-link to="/admin/videos" class="btn btn-outline-secondary me-2">
              <i class="fas fa-arrow-left me-2"></i>
              Back to Videos
            </router-link>
            <router-link to="/admin/gallery" class="btn btn-info">
              <i class="fas fa-images me-2"></i>
              Gallery
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Upload Section -->
    <div class="upload-section">
      <div class="container-fluid">
        <div class="row">
          <!-- Main Upload Area -->
          <div class="col-lg-8">
            <!-- Upload Methods -->
            <div class="card mb-4">
              <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                  <li class="nav-item" role="presentation">
                    <button 
                      class="nav-link active" 
                      data-bs-toggle="tab" 
                      data-bs-target="#upload-tab"
                      @click="activeTab = 'upload'"
                    >
                      <i class="fas fa-upload me-2"></i>
                      Upload Video
                    </button>
                  </li>
                  <li class="nav-item" role="presentation">
                    <button 
                      class="nav-link" 
                      data-bs-toggle="tab" 
                      data-bs-target="#youtube-tab"
                      @click="activeTab = 'youtube'"
                    >
                      <i class="fab fa-youtube me-2"></i>
                      YouTube Link
                    </button>
                  </li>
                  <li class="nav-item" role="presentation">
                    <button 
                      class="nav-link" 
                      data-bs-toggle="tab" 
                      data-bs-target="#vimeo-tab"
                      @click="activeTab = 'vimeo'"
                    >
                      <i class="fab fa-vimeo me-2"></i>
                      Vimeo Link
                    </button>
                  </li>
                </ul>
              </div>
              <div class="card-body">
                <!-- Upload Tab -->
                <div v-if="activeTab === 'upload'" class="tab-content">
                  <div 
                    class="upload-area"
                    @dragover.prevent
                    @dragenter.prevent
                    @drop.prevent="handleDrop"
                    @click="$refs.videoInput.click()"
                    :class="{ 'drag-over': isDragOver }"
                    @dragenter="isDragOver = true"
                    @dragleave="isDragOver = false"
                  >
                    <div class="upload-content">
                      <i class="fas fa-cloud-upload-alt fa-4x text-primary mb-3"></i>
                      <h4>Drag & Drop Video Here</h4>
                      <p class="text-muted mb-3">or click to browse files</p>
                      <button type="button" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        Select Video
                      </button>
                      <div class="upload-info mt-3">
                        <small class="text-muted">
                          Supported formats: MP4, AVI, MOV, WMV, FLV | Max size: 100MB
                        </small>
                      </div>
                    </div>
                  </div>
                  
                  <input 
                    type="file" 
                    ref="videoInput" 
                    @change="handleVideoUpload"
                    accept="video/*"
                    class="d-none"
                  >
                </div>

                <!-- YouTube Tab -->
                <div v-if="activeTab === 'youtube'" class="tab-content">
                  <div class="youtube-form">
                    <div class="mb-3">
                      <label class="form-label">YouTube Video URL</label>
                      <input 
                        type="url" 
                        class="form-control"
                        v-model="youtubeUrl"
                        placeholder="https://www.youtube.com/watch?v=..."
                        @input="extractYouTubeInfo"
                      >
                      <div class="form-text">
                        Paste the YouTube video URL to automatically extract video information
                      </div>
                    </div>
                    
                    <div v-if="youtubePreview" class="youtube-preview">
                      <div class="row">
                        <div class="col-md-4">
                          <img :src="youtubePreview.thumbnail" alt="Thumbnail" class="img-fluid rounded">
                        </div>
                        <div class="col-md-8">
                          <h6>{{ youtubePreview.title }}</h6>
                          <p class="text-muted small">{{ youtubePreview.description }}</p>
                          <div class="d-flex gap-2">
                            <span class="badge bg-info">{{ youtubePreview.duration }}</span>
                            <span class="badge bg-secondary">{{ youtubePreview.views }} views</span>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <button 
                      type="button" 
                      class="btn btn-success mt-3"
                      @click="addYouTubeVideo"
                      :disabled="!youtubeUrl || loading"
                    >
                      <i class="fab fa-youtube me-2"></i>
                      Add YouTube Video
                    </button>
                  </div>
                </div>

                <!-- Vimeo Tab -->
                <div v-if="activeTab === 'vimeo'" class="tab-content">
                  <div class="vimeo-form">
                    <div class="mb-3">
                      <label class="form-label">Vimeo Video URL</label>
                      <input 
                        type="url" 
                        class="form-control"
                        v-model="vimeoUrl"
                        placeholder="https://vimeo.com/..."
                        @input="extractVimeoInfo"
                      >
                      <div class="form-text">
                        Paste the Vimeo video URL to automatically extract video information
                      </div>
                    </div>
                    
                    <div v-if="vimeoPreview" class="vimeo-preview">
                      <div class="row">
                        <div class="col-md-4">
                          <img :src="vimeoPreview.thumbnail" alt="Thumbnail" class="img-fluid rounded">
                        </div>
                        <div class="col-md-8">
                          <h6>{{ vimeoPreview.title }}</h6>
                          <p class="text-muted small">{{ vimeoPreview.description }}</p>
                          <div class="d-flex gap-2">
                            <span class="badge bg-info">{{ vimeoPreview.duration }}</span>
                            <span class="badge bg-secondary">{{ vimeoPreview.views }} views</span>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <button 
                      type="button" 
                      class="btn btn-success mt-3"
                      @click="addVimeoVideo"
                      :disabled="!vimeoUrl || loading"
                    >
                      <i class="fab fa-vimeo me-2"></i>
                      Add Vimeo Video
                    </button>
                  </div>
                </div>

                <!-- Upload Progress -->
                <div v-if="uploadProgress.show" class="upload-progress mt-4">
                  <h6>Upload Progress</h6>
                  <div class="progress-item">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <span>{{ uploadProgress.filename }}</span>
                      <span>{{ uploadProgress.percent }}%</span>
                    </div>
                    <div class="progress">
                      <div 
                        class="progress-bar" 
                        :style="{ width: uploadProgress.percent + '%' }"
                        :class="uploadProgress.status === 'error' ? 'bg-danger' : 'bg-success'"
                      ></div>
                    </div>
                    <div v-if="uploadProgress.status === 'processing'" class="mt-2">
                      <small class="text-info">
                        <i class="fas fa-cog fa-spin me-1"></i>
                        Processing video...
                      </small>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Video List -->
            <div v-if="videos.length > 0" class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Added Videos ({{ videos.length }})</h5>
                <div>
                  <button 
                    class="btn btn-sm btn-outline-danger me-2"
                    @click="clearAllVideos"
                  >
                    <i class="fas fa-trash me-1"></i>
                    Clear All
                  </button>
                  <button 
                    class="btn btn-sm btn-success"
                    @click="saveAllVideos"
                    :disabled="loading || videos.length === 0"
                  >
                    <i class="fas fa-save me-1"></i>
                    {{ loading ? 'Saving...' : 'Save All Videos' }}
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div class="video-list">
                  <div 
                    v-for="(video, index) in videos" 
                    :key="index" 
                    class="video-item"
                  >
                    <div class="row align-items-center">
                      <div class="col-md-3">
                        <div class="video-thumbnail">
                          <img :src="video.thumbnail" :alt="video.title" class="video-thumb">
                          <div class="play-overlay">
                            <i class="fas fa-play"></i>
                          </div>
                          <div class="video-duration">{{ video.duration }}</div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <h6 class="video-title">{{ video.title }}</h6>
                        <p class="video-description text-muted">{{ video.description }}</p>
                        <div class="video-meta">
                          <span class="badge bg-primary me-2">{{ video.type }}</span>
                          <span class="badge bg-info me-2">{{ video.category }}</span>
                          <small class="text-muted">{{ formatFileSize(video.size) }}</small>
                        </div>
                      </div>
                      <div class="col-md-3 text-end">
                        <button 
                          class="btn btn-sm btn-outline-primary me-2"
                          @click="editVideo(index)"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <button 
                          class="btn btn-sm btn-outline-danger"
                          @click="removeVideo(index)"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Settings Sidebar -->
          <div class="col-lg-4">
            <!-- Video Settings -->
            <div class="card mb-4">
              <div class="card-header">
                <h6 class="card-title mb-0">Video Settings</h6>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label">Category</label>
                  <select class="form-select" v-model="defaultCategory">
                    <option value="">Select Category</option>
                    <option value="events">Events</option>
                    <option value="activities">Activities</option>
                    <option value="meetings">Meetings</option>
                    <option value="festivals">Festivals</option>
                    <option value="interviews">Interviews</option>
                    <option value="documentaries">Documentaries</option>
                    <option value="other">Other</option>
                  </select>
                </div>

                <div class="mb-3">
                  <label class="form-label">Default Tags</label>
                  <input 
                    type="text" 
                    class="form-control"
                    v-model="defaultTags"
                    placeholder="Enter tags separated by commas"
                  >
                </div>

                <div class="mb-3">
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      v-model="autoGenerateThumbnail"
                      id="autoThumbnail"
                    >
                    <label class="form-check-label" for="autoThumbnail">
                      Auto-generate thumbnail
                    </label>
                  </div>
                </div>

                <div class="mb-3">
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      v-model="enableComments"
                      id="enableComments"
                    >
                    <label class="form-check-label" for="enableComments">
                      Enable comments
                    </label>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Privacy</label>
                  <select class="form-select" v-model="defaultPrivacy">
                    <option value="public">Public</option>
                    <option value="members">Members Only</option>
                    <option value="private">Private</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Upload Statistics -->
            <div class="card mb-4">
              <div class="card-header">
                <h6 class="card-title mb-0">Upload Statistics</h6>
              </div>
              <div class="card-body">
                <div class="stat-item d-flex justify-content-between mb-2">
                  <span>Total Videos:</span>
                  <strong>{{ videos.length }}</strong>
                </div>
                <div class="stat-item d-flex justify-content-between mb-2">
                  <span>Total Size:</span>
                  <strong>{{ formatFileSize(totalSize) }}</strong>
                </div>
                <div class="stat-item d-flex justify-content-between mb-2">
                  <span>Total Duration:</span>
                  <strong>{{ totalDuration }}</strong>
                </div>
                <div class="stat-item d-flex justify-content-between">
                  <span>Status:</span>
                  <span class="badge bg-info">Ready to Save</span>
                </div>
              </div>
            </div>

            <!-- Recent Videos -->
            <div class="card">
              <div class="card-header">
                <h6 class="card-title mb-0">Recent Videos</h6>
              </div>
              <div class="card-body">
                <div v-if="recentVideos.length === 0" class="text-center text-muted py-3">
                  <i class="fas fa-video fa-2x mb-2"></i>
                  <p class="mb-0">No recent videos</p>
                </div>
                <div v-else>
                  <div 
                    v-for="video in recentVideos" 
                    :key="video.id"
                    class="recent-video-item d-flex align-items-center mb-3"
                  >
                    <div class="video-thumb-small me-3">
                      <img :src="video.thumbnail" :alt="video.title" class="thumb-img">
                      <div class="play-icon">
                        <i class="fas fa-play"></i>
                      </div>
                    </div>
                    <div class="video-info flex-grow-1">
                      <h6 class="video-name mb-1">{{ video.title }}</h6>
                      <small class="text-muted">{{ video.views }} views • {{ video.date }}</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Video Edit Modal -->
    <div v-if="editingVideo" class="modal fade show d-block" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit Video Details</h5>
            <button type="button" class="btn-close" @click="closeEditModal"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6">
                <div class="video-preview">
                  <img :src="editingVideo.thumbnail" :alt="editingVideo.title" class="img-fluid rounded">
                  <div class="play-overlay-large">
                    <i class="fas fa-play fa-2x"></i>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label">Video Title</label>
                  <input 
                    type="text" 
                    class="form-control"
                    v-model="editingVideo.title"
                    placeholder="Enter video title"
                  >
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Description</label>
                  <textarea 
                    class="form-control"
                    v-model="editingVideo.description"
                    rows="4"
                    placeholder="Enter video description"
                  ></textarea>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Category</label>
                  <select class="form-select" v-model="editingVideo.category">
                    <option value="events">Events</option>
                    <option value="activities">Activities</option>
                    <option value="meetings">Meetings</option>
                    <option value="festivals">Festivals</option>
                    <option value="interviews">Interviews</option>
                    <option value="documentaries">Documentaries</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                
                <div class="mb-3">
                  <label class="form-label">Tags</label>
                  <input 
                    type="text" 
                    class="form-control"
                    v-model="editingVideo.tags"
                    placeholder="Enter tags separated by commas"
                  >
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeEditModal">
              Cancel
            </button>
            <button type="button" class="btn btn-primary" @click="saveVideoEdit">
              Save Changes
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-if="editingVideo" class="modal-backdrop fade show"></div>
  </div>
</template>

<script>
import { api } from '@/utils/api'

export default {
  name: 'AddVideo',
  data() {
    return {
      loading: false,
      isDragOver: false,
      activeTab: 'upload',
      videos: [],
      uploadProgress: {
        show: false,
        filename: '',
        percent: 0,
        status: 'uploading'
      },
      youtubeUrl: '',
      vimeoUrl: '',
      youtubePreview: null,
      vimeoPreview: null,
      editingVideo: null,
      editingIndex: -1,
      defaultCategory: 'events',
      defaultTags: '',
      autoGenerateThumbnail: true,
      enableComments: true,
      defaultPrivacy: 'public',
      recentVideos: [
        {
          id: 1,
          title: 'Community Meeting 2024',
          thumbnail: '/api/placeholder/120/80',
          views: '1.2K',
          date: '2 days ago'
        },
        {
          id: 2,
          title: 'Festival Celebration',
          thumbnail: '/api/placeholder/120/80',
          views: '856',
          date: '1 week ago'
        }
      ]
    }
  },

  computed: {
    totalSize() {
      return this.videos.reduce((total, video) => total + (video.size || 0), 0)
    },

    totalDuration() {
      const totalSeconds = this.videos.reduce((total, video) => {
        return total + this.parseDuration(video.duration || '0:00')
      }, 0)
      
      const hours = Math.floor(totalSeconds / 3600)
      const minutes = Math.floor((totalSeconds % 3600) / 60)
      const seconds = totalSeconds % 60
      
      if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
      }
      return `${minutes}:${seconds.toString().padStart(2, '0')}`
    }
  },

  methods: {
    handleVideoUpload(event) {
      const file = event.target.files[0]
      if (file) {
        this.processVideoFile(file)
      }
    },

    handleDrop(event) {
      this.isDragOver = false
      const files = Array.from(event.dataTransfer.files)
      const videoFile = files.find(file => file.type.startsWith('video/'))
      
      if (videoFile) {
        this.processVideoFile(videoFile)
      } else {
        this.$toast.error('Please drop a valid video file')
      }
    },

    async processVideoFile(file) {
      if (file.size > 100 * 1024 * 1024) { // 100MB limit
        this.$toast.error('Video file is too large (max 100MB)')
        return
      }

      try {
        this.uploadProgress = {
          show: true,
          filename: file.name,
          percent: 0,
          status: 'uploading'
        }

        // Simulate upload progress
        for (let i = 0; i <= 100; i += 10) {
          this.uploadProgress.percent = i
          await new Promise(resolve => setTimeout(resolve, 200))
        }

        this.uploadProgress.status = 'processing'

        // Simulate video processing
        await new Promise(resolve => setTimeout(resolve, 2000))

        // Create video object
        const video = {
          type: 'upload',
          title: file.name.replace(/\.[^/.]+$/, ""),
          description: '',
          file: file,
          size: file.size,
          duration: '0:00', // Would be extracted from video metadata
          thumbnail: '/api/placeholder/300/200', // Would be generated
          category: this.defaultCategory,
          tags: this.defaultTags,
          privacy: this.defaultPrivacy,
          comments_enabled: this.enableComments
        }

        this.videos.push(video)
        this.uploadProgress.show = false
        this.$toast.success('Video uploaded successfully!')

      } catch (error) {
        console.error('Error processing video:', error)
        this.uploadProgress.status = 'error'
        this.$toast.error('Failed to process video')
      }
    },

    extractYouTubeInfo() {
      if (!this.youtubeUrl) {
        this.youtubePreview = null
        return
      }

      // Mock YouTube info extraction
      setTimeout(() => {
        if (this.youtubeUrl.includes('youtube.com') || this.youtubeUrl.includes('youtu.be')) {
          this.youtubePreview = {
            title: 'Sample YouTube Video Title',
            description: 'This is a sample description extracted from YouTube...',
            thumbnail: '/api/placeholder/300/200',
            duration: '5:32',
            views: '10K'
          }
        }
      }, 500)
    },

    extractVimeoInfo() {
      if (!this.vimeoUrl) {
        this.vimeoPreview = null
        return
      }

      // Mock Vimeo info extraction
      setTimeout(() => {
        if (this.vimeoUrl.includes('vimeo.com')) {
          this.vimeoPreview = {
            title: 'Sample Vimeo Video Title',
            description: 'This is a sample description extracted from Vimeo...',
            thumbnail: '/api/placeholder/300/200',
            duration: '3:45',
            views: '2.5K'
          }
        }
      }, 500)
    },

    addYouTubeVideo() {
      if (!this.youtubePreview) return

      const video = {
        type: 'youtube',
        title: this.youtubePreview.title,
        description: this.youtubePreview.description,
        url: this.youtubeUrl,
        thumbnail: this.youtubePreview.thumbnail,
        duration: this.youtubePreview.duration,
        category: this.defaultCategory,
        tags: this.defaultTags,
        privacy: this.defaultPrivacy,
        comments_enabled: this.enableComments,
        size: 0
      }

      this.videos.push(video)
      this.youtubeUrl = ''
      this.youtubePreview = null
      this.$toast.success('YouTube video added successfully!')
    },

    addVimeoVideo() {
      if (!this.vimeoPreview) return

      const video = {
        type: 'vimeo',
        title: this.vimeoPreview.title,
        description: this.vimeoPreview.description,
        url: this.vimeoUrl,
        thumbnail: this.vimeoPreview.thumbnail,
        duration: this.vimeoPreview.duration,
        category: this.defaultCategory,
        tags: this.defaultTags,
        privacy: this.defaultPrivacy,
        comments_enabled: this.enableComments,
        size: 0
      }

      this.videos.push(video)
      this.vimeoUrl = ''
      this.vimeoPreview = null
      this.$toast.success('Vimeo video added successfully!')
    },

    editVideo(index) {
      this.editingIndex = index
      this.editingVideo = { ...this.videos[index] }
    },

    closeEditModal() {
      this.editingVideo = null
      this.editingIndex = -1
    },

    saveVideoEdit() {
      if (this.editingIndex >= 0) {
        this.videos[this.editingIndex] = { ...this.editingVideo }
        this.closeEditModal()
        this.$toast.success('Video details updated')
      }
    },

    removeVideo(index) {
      if (confirm('Are you sure you want to remove this video?')) {
        this.videos.splice(index, 1)
      }
    },

    clearAllVideos() {
      if (confirm('Are you sure you want to remove all videos?')) {
        this.videos = []
      }
    },

    async saveAllVideos() {
      if (this.videos.length === 0) {
        this.$toast.error('No videos to save')
        return
      }

      try {
        this.loading = true

        // Mock API call - replace with actual API
        console.log('Saving videos:', this.videos)
        
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 2000))

        this.$toast.success(`${this.videos.length} videos saved successfully!`)
        
        // Reset form
        this.videos = []
        this.$refs.videoInput.value = ''
        
      } catch (error) {
        console.error('Error saving videos:', error)
        this.$toast.error('Failed to save videos')
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

    parseDuration(duration) {
      const parts = duration.split(':').map(Number)
      if (parts.length === 2) {
        return parts[0] * 60 + parts[1] // minutes:seconds
      } else if (parts.length === 3) {
        return parts[0] * 3600 + parts[1] * 60 + parts[2] // hours:minutes:seconds
      }
      return 0
    }
  },

  mounted() {
    document.title = 'Add Videos - Admin - BHRC'
  }
}
</script>

<style scoped>
.add-video-page {
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

.nav-tabs .nav-link {
  border: none;
  color: #666;
  font-weight: 500;
}

.nav-tabs .nav-link.active {
  background: white;
  color: #007bff;
  border-bottom: 2px solid #007bff;
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

.youtube-form,
.vimeo-form {
  padding: 2rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.youtube-preview,
.vimeo-preview {
  background: white;
  padding: 1rem;
  border-radius: 8px;
  margin-top: 1rem;
}

.video-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.video-item {
  background: white;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.video-thumbnail {
  position: relative;
  aspect-ratio: 16/9;
  border-radius: 8px;
  overflow: hidden;
}

.video-thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.play-overlay {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: rgba(0,0,0,0.7);
  color: white;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.video-duration {
  position: absolute;
  bottom: 8px;
  right: 8px;
  background: rgba(0,0,0,0.8);
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 0.75rem;
}

.video-title {
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.video-description {
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.video-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.progress-item {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
}

.recent-video-item {
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  transition: background-color 0.2s ease;
}

.recent-video-item:hover {
  background-color: #f8f9fa;
}

.video-thumb-small {
  position: relative;
  width: 60px;
  height: 40px;
  border-radius: 4px;
  overflow: hidden;
}

.thumb-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.play-icon {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: white;
  font-size: 0.8rem;
}

.video-name {
  font-size: 0.9rem;
  font-weight: 500;
  margin-bottom: 0.25rem;
}

.stat-item {
  padding: 0.25rem 0;
}

.video-preview {
  position: relative;
  aspect-ratio: 16/9;
  border-radius: 8px;
  overflow: hidden;
}

.play-overlay-large {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: rgba(0,0,0,0.7);
  color: white;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
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
  
  .video-item .row {
    flex-direction: column;
  }
  
  .video-item .col-md-3,
  .video-item .col-md-6,
  .video-item .col-md-3 {
    margin-bottom: 1rem;
  }
}
</style>