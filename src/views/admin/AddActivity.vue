<template>
  <div class="add-activity-page">
    <!-- Header Section -->
    <div class="page-header">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-6">
            <h1 class="page-title">
              <i class="fas fa-plus-circle me-3"></i>
              Add New Activity
            </h1>
            <p class="page-subtitle">Create and schedule a new activity or event</p>
          </div>
          <div class="col-md-6 text-md-end">
            <router-link to="/admin/activities" class="btn btn-outline-secondary me-2">
              <i class="fas fa-arrow-left me-2"></i>
              Back to Activities
            </router-link>
            <button 
              class="btn btn-success"
              @click="saveAsDraft"
              :disabled="loading"
            >
              <i class="fas fa-save me-2"></i>
              Save as Draft
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Form Section -->
    <div class="form-section">
      <div class="container-fluid">
        <form @submit.prevent="submitActivity" class="activity-form">
          <div class="row">
            <!-- Main Form -->
            <div class="col-lg-8">
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title mb-0">Activity Details</h5>
                </div>
                <div class="card-body">
                  <!-- Basic Information -->
                  <div class="row mb-4">
                    <div class="col-md-8">
                      <label class="form-label required">Activity Title</label>
                      <input 
                        type="text" 
                        class="form-control"
                        v-model="activity.title"
                        placeholder="Enter activity title"
                        required
                      >
                    </div>
                    <div class="col-md-4">
                      <label class="form-label required">Activity Type</label>
                      <select class="form-select" v-model="activity.type" required>
                        <option value="">Select Type</option>
                        <option value="workshop">Workshop</option>
                        <option value="seminar">Seminar</option>
                        <option value="training">Training</option>
                        <option value="awareness">Awareness Program</option>
                        <option value="legal_aid">Legal Aid</option>
                        <option value="community_service">Community Service</option>
                        <option value="blood_donation">Blood Donation</option>
                        <option value="other">Other</option>
                      </select>
                    </div>
                  </div>

                  <!-- Description -->
                  <div class="mb-4">
                    <label class="form-label required">Description</label>
                    <textarea 
                      class="form-control"
                      v-model="activity.description"
                      rows="4"
                      placeholder="Describe the activity, its objectives, and what participants can expect"
                      required
                    ></textarea>
                  </div>

                  <!-- Date and Time -->
                  <div class="row mb-4">
                    <div class="col-md-3">
                      <label class="form-label required">Start Date</label>
                      <input 
                        type="date" 
                        class="form-control"
                        v-model="activity.start_date"
                        required
                      >
                    </div>
                    <div class="col-md-3">
                      <label class="form-label required">Start Time</label>
                      <input 
                        type="time" 
                        class="form-control"
                        v-model="activity.start_time"
                        required
                      >
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">End Date</label>
                      <input 
                        type="date" 
                        class="form-control"
                        v-model="activity.end_date"
                      >
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">End Time</label>
                      <input 
                        type="time" 
                        class="form-control"
                        v-model="activity.end_time"
                      >
                    </div>
                  </div>

                  <!-- Location -->
                  <div class="row mb-4">
                    <div class="col-md-6">
                      <label class="form-label required">Venue</label>
                      <input 
                        type="text" 
                        class="form-control"
                        v-model="activity.venue"
                        placeholder="Enter venue name"
                        required
                      >
                    </div>
                    <div class="col-md-6">
                      <label class="form-label required">Address</label>
                      <input 
                        type="text" 
                        class="form-control"
                        v-model="activity.address"
                        placeholder="Enter complete address"
                        required
                      >
                    </div>
                  </div>

                  <!-- Capacity and Registration -->
                  <div class="row mb-4">
                    <div class="col-md-4">
                      <label class="form-label">Maximum Participants</label>
                      <input 
                        type="number" 
                        class="form-control"
                        v-model="activity.max_participants"
                        placeholder="0 for unlimited"
                        min="0"
                      >
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Registration Fee</label>
                      <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input 
                          type="number" 
                          class="form-control"
                          v-model="activity.registration_fee"
                          placeholder="0.00"
                          min="0"
                          step="0.01"
                        >
                      </div>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Registration Deadline</label>
                      <input 
                        type="date" 
                        class="form-control"
                        v-model="activity.registration_deadline"
                      >
                    </div>
                  </div>

                  <!-- Contact Information -->
                  <div class="row mb-4">
                    <div class="col-md-6">
                      <label class="form-label">Contact Person</label>
                      <input 
                        type="text" 
                        class="form-control"
                        v-model="activity.contact_person"
                        placeholder="Enter contact person name"
                      >
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Contact Phone</label>
                      <input 
                        type="tel" 
                        class="form-control"
                        v-model="activity.contact_phone"
                        placeholder="Enter contact phone number"
                      >
                    </div>
                  </div>

                  <!-- Requirements -->
                  <div class="mb-4">
                    <label class="form-label">Requirements/Prerequisites</label>
                    <textarea 
                      class="form-control"
                      v-model="activity.requirements"
                      rows="3"
                      placeholder="List any requirements, documents needed, or prerequisites for participation"
                    ></textarea>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
              <!-- Status and Visibility -->
              <div class="card mb-4">
                <div class="card-header">
                  <h6 class="card-title mb-0">Publication Settings</h6>
                </div>
                <div class="card-body">
                  <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" v-model="activity.status">
                      <option value="draft">Draft</option>
                      <option value="published">Published</option>
                      <option value="scheduled">Scheduled</option>
                      <option value="cancelled">Cancelled</option>
                    </select>
                  </div>
                  
                  <div class="mb-3">
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        v-model="activity.featured"
                        id="featured"
                      >
                      <label class="form-check-label" for="featured">
                        Featured Activity
                      </label>
                    </div>
                  </div>

                  <div class="mb-3">
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        v-model="activity.registration_required"
                        id="registration"
                      >
                      <label class="form-check-label" for="registration">
                        Registration Required
                      </label>
                    </div>
                  </div>

                  <div class="mb-3">
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        v-model="activity.certificate_provided"
                        id="certificate"
                      >
                      <label class="form-check-label" for="certificate">
                        Certificate Provided
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Featured Image -->
              <div class="card mb-4">
                <div class="card-header">
                  <h6 class="card-title mb-0">Featured Image</h6>
                </div>
                <div class="card-body">
                  <div class="image-upload-area" @click="$refs.imageInput.click()">
                    <div v-if="!activity.image" class="upload-placeholder">
                      <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                      <p class="text-muted">Click to upload image</p>
                      <small class="text-muted">Recommended: 800x600px, Max: 2MB</small>
                    </div>
                    <div v-else class="uploaded-image">
                      <img :src="activity.image" alt="Activity Image" class="img-fluid rounded">
                      <div class="image-overlay">
                        <button type="button" class="btn btn-sm btn-danger" @click.stop="removeImage">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                  <input 
                    type="file" 
                    ref="imageInput" 
                    @change="handleImageUpload"
                    accept="image/*"
                    class="d-none"
                  >
                </div>
              </div>

              <!-- Tags -->
              <div class="card">
                <div class="card-header">
                  <h6 class="card-title mb-0">Tags</h6>
                </div>
                <div class="card-body">
                  <div class="mb-3">
                    <input 
                      type="text" 
                      class="form-control"
                      v-model="newTag"
                      @keyup.enter="addTag"
                      placeholder="Add tag and press Enter"
                    >
                  </div>
                  <div class="tags-list">
                    <span 
                      v-for="(tag, index) in activity.tags" 
                      :key="index"
                      class="badge bg-primary me-2 mb-2"
                    >
                      {{ tag }}
                      <button 
                        type="button" 
                        class="btn-close btn-close-white ms-2"
                        @click="removeTag(index)"
                        style="font-size: 0.7em;"
                      ></button>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="form-actions mt-4">
            <div class="d-flex justify-content-between">
              <div>
                <router-link to="/admin/activities" class="btn btn-outline-secondary">
                  <i class="fas fa-times me-2"></i>
                  Cancel
                </router-link>
              </div>
              <div>
                <button 
                  type="button" 
                  class="btn btn-outline-primary me-2"
                  @click="previewActivity"
                >
                  <i class="fas fa-eye me-2"></i>
                  Preview
                </button>
                <button 
                  type="submit" 
                  class="btn btn-success"
                  :disabled="loading"
                >
                  <i class="fas fa-check me-2"></i>
                  {{ loading ? 'Creating...' : 'Create Activity' }}
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { api } from '@/utils/api'

export default {
  name: 'AddActivity',
  data() {
    return {
      loading: false,
      newTag: '',
      activity: {
        title: '',
        type: '',
        description: '',
        start_date: '',
        start_time: '',
        end_date: '',
        end_time: '',
        venue: '',
        address: '',
        max_participants: null,
        registration_fee: 0,
        registration_deadline: '',
        contact_person: '',
        contact_phone: '',
        requirements: '',
        status: 'draft',
        featured: false,
        registration_required: true,
        certificate_provided: false,
        image: null,
        tags: []
      }
    }
  },

  methods: {
    async submitActivity() {
      try {
        this.loading = true
        
        // Validate required fields
        if (!this.validateForm()) {
          return
        }

        // Create FormData for file upload
        const formData = new FormData()
        Object.keys(this.activity).forEach(key => {
          if (key === 'tags') {
            formData.append(key, JSON.stringify(this.activity[key]))
          } else if (this.activity[key] !== null && this.activity[key] !== '') {
            formData.append(key, this.activity[key])
          }
        })

        // Mock API call - replace with actual API
        console.log('Creating activity:', this.activity)
        
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        this.$toast.success('Activity created successfully!')
        this.$router.push('/admin/activities')
        
      } catch (error) {
        console.error('Error creating activity:', error)
        this.$toast.error('Failed to create activity')
      } finally {
        this.loading = false
      }
    },

    async saveAsDraft() {
      this.activity.status = 'draft'
      await this.submitActivity()
    },

    validateForm() {
      if (!this.activity.title.trim()) {
        this.$toast.error('Activity title is required')
        return false
      }
      
      if (!this.activity.type) {
        this.$toast.error('Activity type is required')
        return false
      }
      
      if (!this.activity.description.trim()) {
        this.$toast.error('Activity description is required')
        return false
      }
      
      if (!this.activity.start_date) {
        this.$toast.error('Start date is required')
        return false
      }
      
      if (!this.activity.start_time) {
        this.$toast.error('Start time is required')
        return false
      }
      
      if (!this.activity.venue.trim()) {
        this.$toast.error('Venue is required')
        return false
      }
      
      if (!this.activity.address.trim()) {
        this.$toast.error('Address is required')
        return false
      }
      
      return true
    },

    handleImageUpload(event) {
      const file = event.target.files[0]
      if (file) {
        // Validate file size (2MB max)
        if (file.size > 2 * 1024 * 1024) {
          this.$toast.error('Image size should be less than 2MB')
          return
        }
        
        // Validate file type
        if (!file.type.startsWith('image/')) {
          this.$toast.error('Please select a valid image file')
          return
        }
        
        // Create preview URL
        const reader = new FileReader()
        reader.onload = (e) => {
          this.activity.image = e.target.result
        }
        reader.readAsDataURL(file)
      }
    },

    removeImage() {
      this.activity.image = null
      this.$refs.imageInput.value = ''
    },

    addTag() {
      if (this.newTag.trim() && !this.activity.tags.includes(this.newTag.trim())) {
        this.activity.tags.push(this.newTag.trim())
        this.newTag = ''
      }
    },

    removeTag(index) {
      this.activity.tags.splice(index, 1)
    },

    previewActivity() {
      // Open preview modal or navigate to preview page
      console.log('Preview activity:', this.activity)
      this.$toast.info('Preview functionality coming soon')
    }
  },

  mounted() {
    document.title = 'Add Activity - Admin - BHRC'
  }
}
</script>

<style scoped>
.add-activity-page {
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

.form-section {
  padding-bottom: 3rem;
}

.activity-form .card {
  border: none;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  margin-bottom: 2rem;
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

.form-label.required::after {
  content: ' *';
  color: #dc3545;
}

.image-upload-area {
  border: 2px dashed #dee2e6;
  border-radius: 8px;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
}

.image-upload-area:hover {
  border-color: #007bff;
  background-color: #f8f9fa;
}

.uploaded-image {
  position: relative;
}

.uploaded-image img {
  max-height: 200px;
  object-fit: cover;
}

.image-overlay {
  position: absolute;
  top: 10px;
  right: 10px;
}

.tags-list {
  min-height: 40px;
}

.form-actions {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-header {
    padding: 1rem 0;
  }
  
  .page-title {
    font-size: 1.5rem;
  }
  
  .form-actions .d-flex {
    flex-direction: column;
    gap: 1rem;
  }
  
  .form-actions .d-flex > div {
    text-align: center;
  }
}
</style>