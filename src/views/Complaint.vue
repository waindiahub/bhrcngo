<template>
  <DefaultLayout>
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="hero-content">
          <h1 class="hero-title">File a Complaint</h1>
          <p class="hero-subtitle">
            Report human rights violations and seek justice through our complaint system
          </p>
          <div class="hero-stats">
            <div class="stat-item">
              <span class="stat-number">24/7</span>
              <span class="stat-label">Support Available</span>
            </div>
            <div class="stat-item">
              <span class="stat-number">Free</span>
              <span class="stat-label">Service</span>
            </div>
            <div class="stat-item">
              <span class="stat-number">Confidential</span>
              <span class="stat-label">Process</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Information Section -->
    <section class="info-section">
      <div class="container">
        <div class="info-grid">
          <div class="info-card">
            <div class="info-icon">
              <i class="fas fa-shield-alt"></i>
            </div>
            <h3>Your Rights Are Protected</h3>
            <p>We ensure complete confidentiality and protection of your identity throughout the complaint process.</p>
          </div>
          
          <div class="info-card">
            <div class="info-icon">
              <i class="fas fa-clock"></i>
            </div>
            <h3>Quick Response</h3>
            <p>All complaints are acknowledged within 24 hours and investigated promptly by our expert team.</p>
          </div>
          
          <div class="info-card">
            <div class="info-icon">
              <i class="fas fa-balance-scale"></i>
            </div>
            <h3>Fair Investigation</h3>
            <p>Every complaint receives a thorough and impartial investigation following due process.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Complaint Form -->
    <section class="complaint-section">
      <div class="container">
        <div class="form-wrapper">
          <div class="form-header">
            <h2>Submit Your Complaint</h2>
            <p>Please provide detailed information about the incident. All fields marked with * are required.</p>
          </div>

          <form @submit.prevent="submitComplaint" class="complaint-form">
            <!-- Personal Information -->
            <div class="form-section">
              <h3 class="section-title">
                <i class="fas fa-user"></i>
                Personal Information
              </h3>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="name">Full Name *</label>
                  <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    class="form-input"
                    :class="{ error: errors.name }"
                  >
                  <span v-if="errors.name" class="error-message">{{ errors.name }}</span>
                </div>
                
                <div class="form-group">
                  <label for="email">Email Address *</label>
                  <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    class="form-input"
                    :class="{ error: errors.email }"
                  >
                  <span v-if="errors.email" class="error-message">{{ errors.email }}</span>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="phone">Phone Number *</label>
                  <input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    required
                    class="form-input"
                    :class="{ error: errors.phone }"
                  >
                  <span v-if="errors.phone" class="error-message">{{ errors.phone }}</span>
                </div>
                
                <div class="form-group">
                  <label for="gender">Gender</label>
                  <select
                    id="gender"
                    v-model="form.gender"
                    class="form-input"
                  >
                    <option value="">Select Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                    <option value="prefer_not_to_say">Prefer not to say</option>
                  </select>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="dateOfBirth">Date of Birth</label>
                  <input
                    id="dateOfBirth"
                    v-model="form.dateOfBirth"
                    type="date"
                    class="form-input"
                  >
                </div>
              </div>
            </div>

            <!-- Address Information -->
            <div class="form-section">
              <h3 class="section-title">
                <i class="fas fa-map-marker-alt"></i>
                Address Information
              </h3>
              
              <div class="form-group">
                <label for="address">Full Address *</label>
                <textarea
                  id="address"
                  v-model="form.address"
                  required
                  rows="3"
                  class="form-input"
                  :class="{ error: errors.address }"
                  placeholder="Enter your complete address"
                ></textarea>
                <span v-if="errors.address" class="error-message">{{ errors.address }}</span>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="state">State *</label>
                  <select
                    id="state"
                    v-model="form.state"
                    required
                    class="form-input"
                    :class="{ error: errors.state }"
                    @change="onStateChange"
                  >
                    <option value="">Select State</option>
                    <option v-for="state in states" :key="state" :value="state">
                      {{ state }}
                    </option>
                  </select>
                  <span v-if="errors.state" class="error-message">{{ errors.state }}</span>
                </div>
                
                <div class="form-group">
                  <label for="district">District *</label>
                  <select
                    id="district"
                    v-model="form.district"
                    required
                    class="form-input"
                    :class="{ error: errors.district }"
                    :disabled="!form.state"
                  >
                    <option value="">Select District</option>
                    <option v-for="district in districts" :key="district" :value="district">
                      {{ district }}
                    </option>
                  </select>
                  <span v-if="errors.district" class="error-message">{{ errors.district }}</span>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="pincode">PIN Code *</label>
                  <input
                    id="pincode"
                    v-model="form.pincode"
                    type="text"
                    required
                    pattern="[0-9]{6}"
                    class="form-input"
                    :class="{ error: errors.pincode }"
                    placeholder="6-digit PIN code"
                  >
                  <span v-if="errors.pincode" class="error-message">{{ errors.pincode }}</span>
                </div>
              </div>
            </div>

            <!-- Complaint Details -->
            <div class="form-section">
              <h3 class="section-title">
                <i class="fas fa-exclamation-triangle"></i>
                Complaint Details
              </h3>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="complaintType">Type of Complaint *</label>
                  <select
                    id="complaintType"
                    v-model="form.complaintType"
                    required
                    class="form-input"
                    :class="{ error: errors.complaintType }"
                  >
                    <option value="">Select Complaint Type</option>
                    <option value="police_brutality">Police Brutality</option>
                    <option value="discrimination">Discrimination</option>
                    <option value="illegal_detention">Illegal Detention</option>
                    <option value="torture">Torture</option>
                    <option value="women_rights">Women's Rights Violation</option>
                    <option value="child_rights">Child Rights Violation</option>
                    <option value="labor_rights">Labor Rights Violation</option>
                    <option value="right_to_information">Right to Information</option>
                    <option value="healthcare_rights">Healthcare Rights</option>
                    <option value="education_rights">Education Rights</option>
                    <option value="other">Other</option>
                  </select>
                  <span v-if="errors.complaintType" class="error-message">{{ errors.complaintType }}</span>
                </div>
                
                <div class="form-group">
                  <label for="dateOfIncident">Date of Incident *</label>
                  <input
                    id="dateOfIncident"
                    v-model="form.dateOfIncident"
                    type="date"
                    required
                    class="form-input"
                    :class="{ error: errors.dateOfIncident }"
                    :max="today"
                  >
                  <span v-if="errors.dateOfIncident" class="error-message">{{ errors.dateOfIncident }}</span>
                </div>
              </div>

              <div class="form-group">
                <label for="complaintDetails">Detailed Description of Incident *</label>
                <textarea
                  id="complaintDetails"
                  v-model="form.complaintDetails"
                  required
                  rows="6"
                  class="form-input"
                  :class="{ error: errors.complaintDetails }"
                  placeholder="Please provide a detailed description of the incident, including what happened, when it occurred, who was involved, and any other relevant information..."
                ></textarea>
                <span v-if="errors.complaintDetails" class="error-message">{{ errors.complaintDetails }}</span>
                <small class="form-help">Minimum 50 characters required</small>
              </div>

              <div class="form-group">
                <label for="locationOfIncident">Location of Incident</label>
                <input
                  id="locationOfIncident"
                  v-model="form.locationOfIncident"
                  type="text"
                  class="form-input"
                  placeholder="Specific location where the incident occurred"
                >
              </div>

              <div class="form-group">
                <label for="witnessDetails">Witness Details (if any)</label>
                <textarea
                  id="witnessDetails"
                  v-model="form.witnessDetails"
                  rows="3"
                  class="form-input"
                  placeholder="Names and contact details of witnesses, if any"
                ></textarea>
              </div>
            </div>

            <!-- Priority and Urgency -->
            <div class="form-section">
              <h3 class="section-title">
                <i class="fas fa-flag"></i>
                Priority Level
              </h3>
              
              <div class="priority-options">
                <div
                  v-for="priority in priorityLevels"
                  :key="priority.value"
                  class="priority-option"
                  :class="{ active: form.priority === priority.value }"
                  @click="form.priority = priority.value"
                >
                  <div class="priority-icon" :class="priority.class">
                    <i :class="priority.icon"></i>
                  </div>
                  <div class="priority-info">
                    <h4>{{ priority.label }}</h4>
                    <p>{{ priority.description }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Document Upload -->
            <div class="form-section">
              <h3 class="section-title">
                <i class="fas fa-file-upload"></i>
                Supporting Documents
              </h3>
              
              <div class="upload-area">
                <div class="upload-info">
                  <i class="fas fa-cloud-upload-alt"></i>
                  <h4>Upload Supporting Documents</h4>
                  <p>You can upload photos, videos, documents, or any other evidence related to your complaint</p>
                  <small>Supported formats: JPG, PNG, PDF, DOC, DOCX, MP4, AVI (Max: 10MB per file)</small>
                </div>
                
                <input
                  type="file"
                  multiple
                  accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.mp4,.avi"
                  class="file-input"
                  @change="handleFileUpload"
                  ref="fileInput"
                >
                
                <button type="button" @click="$refs.fileInput.click()" class="upload-btn">
                  <i class="fas fa-plus"></i>
                  Choose Files
                </button>
              </div>

              <!-- Uploaded Files List -->
              <div v-if="uploadedFiles.length > 0" class="uploaded-files">
                <h4>Uploaded Files:</h4>
                <div class="file-list">
                  <div
                    v-for="(file, index) in uploadedFiles"
                    :key="index"
                    class="file-item"
                  >
                    <div class="file-info">
                      <i class="fas fa-file"></i>
                      <span class="file-name">{{ file.name }}</span>
                      <span class="file-size">({{ formatFileSize(file.size) }})</span>
                    </div>
                    <button
                      type="button"
                      @click="removeFile(index)"
                      class="remove-file-btn"
                    >
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Anonymous Option -->
            <div class="form-section">
              <div class="checkbox-group">
                <input
                  id="anonymous"
                  v-model="form.anonymous"
                  type="checkbox"
                  class="checkbox-input"
                >
                <label for="anonymous" class="checkbox-label">
                  <strong>File this complaint anonymously</strong>
                  <br>
                  <small>Your personal information will be kept confidential and not shared with the accused party</small>
                </label>
              </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="form-section">
              <div class="checkbox-group">
                <input
                  id="agreeTerms"
                  v-model="form.agreeTerms"
                  type="checkbox"
                  required
                  class="checkbox-input"
                >
                <label for="agreeTerms" class="checkbox-label">
                  I hereby declare that the information provided is true to the best of my knowledge and I agree to the 
                  <a href="/terms" target="_blank">Terms and Conditions</a> *
                </label>
              </div>
              
              <div class="checkbox-group">
                <input
                  id="agreeUpdates"
                  v-model="form.agreeUpdates"
                  type="checkbox"
                  class="checkbox-input"
                >
                <label for="agreeUpdates" class="checkbox-label">
                  I would like to receive updates about my complaint via email and SMS
                </label>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="form-actions">
              <button
                type="submit"
                class="submit-btn"
                :disabled="submitting"
              >
                <i v-if="submitting" class="fas fa-spinner fa-spin"></i>
                <i v-else class="fas fa-paper-plane"></i>
                {{ submitting ? 'Submitting...' : 'Submit Complaint' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </section>

    <!-- Success Modal -->
    <div v-if="showSuccessModal" class="modal-overlay" @click="closeSuccessModal">
      <div class="modal-content success-modal" @click.stop>
        <div class="success-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <h2>Complaint Submitted Successfully!</h2>
        <p>
          Your complaint has been received and registered in our system.
        </p>
        <div class="complaint-details">
          <p><strong>Complaint ID:</strong> {{ complaintId }}</p>
          <p><strong>Reference Number:</strong> {{ referenceNumber }}</p>
          <p><strong>Status:</strong> Under Review</p>
        </div>
        <p>
          We will acknowledge your complaint within 24 hours and begin investigation promptly. 
          You will receive regular updates on the progress.
        </p>
        <div class="modal-actions">
          <button @click="closeSuccessModal" class="btn-primary">
            Continue
          </button>
          <button @click="trackComplaint" class="btn-secondary">
            Track Complaint
          </button>
        </div>
      </div>
    </div>
  </DefaultLayout>
</template>

<script>
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { api } from '@/utils/api'

export default {
  name: 'Complaint',
  components: {
    DefaultLayout
  },
  data() {
    return {
      submitting: false,
      showSuccessModal: false,
      complaintId: '',
      referenceNumber: '',
      form: {
        name: '',
        email: '',
        phone: '',
        gender: '',
        dateOfBirth: '',
        address: '',
        state: '',
        district: '',
        pincode: '',
        complaintType: '',
        dateOfIncident: '',
        complaintDetails: '',
        locationOfIncident: '',
        witnessDetails: '',
        priority: 'medium',
        anonymous: false,
        agreeTerms: false,
        agreeUpdates: true
      },
      errors: {},
      states: [
        'Bihar', 'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Chhattisgarh',
        'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand',
        'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur',
        'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab',
        'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura',
        'Uttar Pradesh', 'Uttarakhand', 'West Bengal'
      ],
      districts: [],
      biharDistricts: [
        'Araria', 'Arwal', 'Aurangabad', 'Banka', 'Begusarai', 'Bhagalpur',
        'Bhojpur', 'Buxar', 'Darbhanga', 'East Champaran', 'Gaya', 'Gopalganj',
        'Jamui', 'Jehanabad', 'Kaimur', 'Katihar', 'Khagaria', 'Kishanganj',
        'Lakhisarai', 'Madhepura', 'Madhubani', 'Munger', 'Muzaffarpur',
        'Nalanda', 'Nawada', 'Patna', 'Purnia', 'Rohtas', 'Saharsa',
        'Samastipur', 'Saran', 'Sheikhpura', 'Sheohar', 'Sitamarhi',
        'Siwan', 'Supaul', 'Vaishali', 'West Champaran'
      ],
      priorityLevels: [
        {
          value: 'low',
          label: 'Low Priority',
          description: 'Non-urgent matter that can be addressed in regular course',
          icon: 'fas fa-flag',
          class: 'low'
        },
        {
          value: 'medium',
          label: 'Medium Priority',
          description: 'Important matter requiring timely attention',
          icon: 'fas fa-flag',
          class: 'medium'
        },
        {
          value: 'high',
          label: 'High Priority',
          description: 'Serious violation requiring immediate attention',
          icon: 'fas fa-flag',
          class: 'high'
        },
        {
          value: 'urgent',
          label: 'Urgent',
          description: 'Life-threatening or emergency situation',
          icon: 'fas fa-exclamation-triangle',
          class: 'urgent'
        }
      ],
      uploadedFiles: []
    }
  },
  computed: {
    today() {
      return new Date().toISOString().split('T')[0]
    }
  },
  methods: {
    onStateChange() {
      this.form.district = ''
      if (this.form.state === 'Bihar') {
        this.districts = this.biharDistricts
      } else {
        this.districts = []
      }
    },
    handleFileUpload(event) {
      const files = Array.from(event.target.files)
      
      files.forEach(file => {
        // Validate file size (10MB max)
        if (file.size > 10 * 1024 * 1024) {
          alert(`File "${file.name}" is too large. Maximum size is 10MB.`)
          return
        }
        
        // Validate file type
        const allowedTypes = [
          'image/jpeg', 'image/jpg', 'image/png',
          'application/pdf',
          'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          'video/mp4', 'video/avi'
        ]
        
        if (!allowedTypes.includes(file.type)) {
          alert(`File "${file.name}" has an unsupported format.`)
          return
        }
        
        this.uploadedFiles.push(file)
      })
      
      // Clear the input
      event.target.value = ''
    },
    removeFile(index) {
      this.uploadedFiles.splice(index, 1)
    },
    formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    },
    validateForm() {
      this.errors = {}
      
      // Required field validation
      const requiredFields = [
        'name', 'email', 'phone', 'address', 'state', 'district', 'pincode',
        'complaintType', 'dateOfIncident', 'complaintDetails'
      ]
      
      requiredFields.forEach(field => {
        if (!this.form[field] || this.form[field].trim() === '') {
          this.errors[field] = 'This field is required'
        }
      })
      
      // Email validation
      if (this.form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
        this.errors.email = 'Please enter a valid email address'
      }
      
      // Phone validation
      if (this.form.phone && !/^[0-9]{10}$/.test(this.form.phone.replace(/\D/g, ''))) {
        this.errors.phone = 'Please enter a valid 10-digit phone number'
      }
      
      // PIN code validation
      if (this.form.pincode && !/^[0-9]{6}$/.test(this.form.pincode)) {
        this.errors.pincode = 'Please enter a valid 6-digit PIN code'
      }
      
      // Complaint details minimum length
      if (this.form.complaintDetails && this.form.complaintDetails.length < 50) {
        this.errors.complaintDetails = 'Please provide at least 50 characters describing the incident'
      }
      
      // Date validation
      if (this.form.dateOfIncident) {
        const incidentDate = new Date(this.form.dateOfIncident)
        const today = new Date()
        if (incidentDate > today) {
          this.errors.dateOfIncident = 'Incident date cannot be in the future'
        }
      }
      
      // Terms agreement validation
      if (!this.form.agreeTerms) {
        this.errors.agreeTerms = 'You must agree to the terms and conditions'
      }
      
      return Object.keys(this.errors).length === 0
    },
    async submitComplaint() {
      if (!this.validateForm()) {
        // Scroll to first error
        const firstErrorField = Object.keys(this.errors)[0]
        const element = document.getElementById(firstErrorField)
        if (element) {
          element.scrollIntoView({ behavior: 'smooth', block: 'center' })
          element.focus()
        }
        return
      }
      
      try {
        this.submitting = true
        
        // Prepare form data
        const formData = new FormData()
        
        // Add form fields
        Object.keys(this.form).forEach(key => {
          if (this.form[key] !== null && this.form[key] !== '') {
            formData.append(key, this.form[key])
          }
        })
        
        // Add uploaded files
        this.uploadedFiles.forEach((file, index) => {
          formData.append(`attachment_${index}`, file)
        })
        
        // Submit to API
        const response = await api.post('complaints', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })
        
        if (response.data.success) {
          this.complaintId = response.data.complaint_id || 'BHRC' + Date.now()
          this.referenceNumber = response.data.reference_number || 'REF' + Date.now()
          this.showSuccessModal = true
          this.resetForm()
        } else {
          throw new Error(response.data.message || 'Failed to submit complaint')
        }
      } catch (error) {
        console.error('Complaint submission error:', error)
        alert('Failed to submit complaint: ' + error.message)
      } finally {
        this.submitting = false
      }
    },
    resetForm() {
      this.form = {
        name: '',
        email: '',
        phone: '',
        gender: '',
        dateOfBirth: '',
        address: '',
        state: '',
        district: '',
        pincode: '',
        complaintType: '',
        dateOfIncident: '',
        complaintDetails: '',
        locationOfIncident: '',
        witnessDetails: '',
        priority: 'medium',
        anonymous: false,
        agreeTerms: false,
        agreeUpdates: true
      }
      this.errors = {}
      this.uploadedFiles = []
    },
    closeSuccessModal() {
      this.showSuccessModal = false
      this.$router.push('/')
    },
    trackComplaint() {
      this.showSuccessModal = false
      // Navigate to complaint tracking page with the complaint ID
      this.$router.push(`/track-complaint?id=${this.complaintId}`)
    }
  }
}
</script>

<style scoped>
/* Hero Section */
.hero-section {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
  padding: 80px 0;
  text-align: center;
}

.hero-title {
  font-size: 3rem;
  font-weight: 700;
  margin-bottom: 1rem;
}

.hero-subtitle {
  font-size: 1.2rem;
  margin-bottom: 2rem;
  opacity: 0.9;
}

.hero-stats {
  display: flex;
  justify-content: center;
  gap: 3rem;
  margin-top: 2rem;
}

.stat-item {
  text-align: center;
}

.stat-number {
  display: block;
  font-size: 2rem;
  font-weight: 700;
  color: #ffd700;
}

.stat-label {
  font-size: 0.9rem;
  opacity: 0.8;
}

/* Info Section */
.info-section {
  padding: 4rem 0;
  background: #f8f9fa;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.info-card {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  text-align: center;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.info-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #dc3545, #c82333);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
}

.info-icon i {
  font-size: 2rem;
  color: white;
}

.info-card h3 {
  margin-bottom: 1rem;
  color: #333;
}

.info-card p {
  color: #666;
  line-height: 1.6;
}

/* Complaint Form */
.complaint-section {
  padding: 4rem 0;
}

.form-wrapper {
  max-width: 900px;
  margin: 0 auto;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.form-header {
  background: linear-gradient(135deg, #dc3545, #c82333);
  color: white;
  padding: 2rem;
  text-align: center;
}

.form-header h2 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.complaint-form {
  padding: 2rem;
}

.form-section {
  margin-bottom: 3rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.3rem;
  color: #333;
  margin-bottom: 1.5rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #dc3545;
}

.section-title i {
  color: #dc3545;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #333;
}

.form-input {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.3s ease;
}

.form-input:focus {
  outline: none;
  border-color: #dc3545;
  box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
}

.form-input.error {
  border-color: #dc3545;
}

.error-message {
  color: #dc3545;
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

.form-help {
  color: #666;
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

/* Priority Options */
.priority-options {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.priority-option {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.priority-option:hover,
.priority-option.active {
  border-color: #dc3545;
  background: #fff5f5;
}

.priority-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.priority-icon.low {
  background: #28a745;
}

.priority-icon.medium {
  background: #ffc107;
}

.priority-icon.high {
  background: #fd7e14;
}

.priority-icon.urgent {
  background: #dc3545;
}

.priority-info h4 {
  margin-bottom: 0.25rem;
  color: #333;
}

.priority-info p {
  font-size: 0.9rem;
  color: #666;
  margin: 0;
}

/* File Upload */
.upload-area {
  border: 2px dashed #ddd;
  border-radius: 8px;
  padding: 2rem;
  text-align: center;
  transition: border-color 0.3s ease;
}

.upload-area:hover {
  border-color: #dc3545;
}

.upload-info i {
  font-size: 3rem;
  color: #dc3545;
  margin-bottom: 1rem;
}

.upload-info h4 {
  margin-bottom: 0.5rem;
  color: #333;
}

.upload-info p {
  color: #666;
  margin-bottom: 0.5rem;
}

.file-input {
  display: none;
}

.upload-btn {
  background: #dc3545;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  font-size: 1rem;
  margin-top: 1rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.uploaded-files {
  margin-top: 2rem;
}

.uploaded-files h4 {
  margin-bottom: 1rem;
  color: #333;
}

.file-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.file-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: #f8f9fa;
  border-radius: 6px;
}

.file-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.file-info i {
  color: #dc3545;
}

.file-name {
  font-weight: 500;
}

.file-size {
  color: #666;
  font-size: 0.9rem;
}

.remove-file-btn {
  background: #dc3545;
  color: white;
  border: none;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Checkbox Groups */
.checkbox-group {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.checkbox-input {
  margin-top: 0.25rem;
}

.checkbox-label {
  font-size: 0.9rem;
  line-height: 1.5;
}

.checkbox-label a {
  color: #dc3545;
  text-decoration: none;
}

.checkbox-label a:hover {
  text-decoration: underline;
}

/* Form Actions */
.form-actions {
  text-align: center;
  margin-top: 2rem;
}

.submit-btn {
  background: linear-gradient(135deg, #dc3545, #c82333);
  color: white;
  border: none;
  padding: 1rem 3rem;
  border-radius: 8px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.submit-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
}

.submit-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* Success Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 2rem;
}

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 500px;
  width: 100%;
  padding: 2rem;
  text-align: center;
}

.success-icon {
  width: 80px;
  height: 80px;
  background: #28a745;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
}

.success-icon i {
  font-size: 2.5rem;
  color: white;
}

.success-modal h2 {
  color: #28a745;
  margin-bottom: 1rem;
}

.success-modal p {
  margin-bottom: 1rem;
  line-height: 1.6;
  color: #666;
}

.complaint-details {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  margin: 1rem 0;
}

.complaint-details p {
  margin-bottom: 0.5rem;
  text-align: left;
}

.modal-actions {
  margin-top: 2rem;
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.btn-primary,
.btn-secondary {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 600;
  border: none;
}

.btn-primary {
  background: #dc3545;
  color: white;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
  .hero-title {
    font-size: 2rem;
  }
  
  .hero-stats {
    flex-direction: column;
    gap: 1rem;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .form-row {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .priority-options {
    grid-template-columns: 1fr;
  }
  
  .complaint-form {
    padding: 1rem;
  }
  
  .form-header {
    padding: 1.5rem;
  }
  
  .modal-actions {
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .hero-section {
    padding: 60px 0;
  }
  
  .submit-btn {
    width: 100%;
    padding: 1rem;
  }
}
</style>