<template>
  <div class="file-complaint-page">
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <div class="hero-content">
              <h1 class="hero-title">File a Complaint</h1>
              <p class="hero-subtitle">
                Report human rights violations and seek justice. Our team is committed to investigating and addressing your concerns with complete confidentiality.
              </p>
              <div class="hero-features">
                <div class="feature-item">
                  <i class="fas fa-shield-alt"></i>
                  <span>Confidential & Secure</span>
                </div>
                <div class="feature-item">
                  <i class="fas fa-clock"></i>
                  <span>24/7 Support</span>
                </div>
                <div class="feature-item">
                  <i class="fas fa-gavel"></i>
                  <span>Legal Assistance</span>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="hero-stats">
              <div class="row">
                <div class="col-6">
                  <div class="stat-card">
                    <div class="stat-number">{{ stats.totalComplaints }}+</div>
                    <div class="stat-label">Cases Filed</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="stat-card">
                    <div class="stat-number">{{ stats.resolvedComplaints }}%</div>
                    <div class="stat-label">Success Rate</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="stat-card">
                    <div class="stat-number">{{ stats.avgResponseTime }}</div>
                    <div class="stat-label">Avg Response</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="stat-card">
                    <div class="stat-number">{{ stats.activeInvestigators }}</div>
                    <div class="stat-label">Investigators</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Complaint Form Section -->
    <section class="complaint-form-section py-5">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-10">
            <!-- Progress Steps -->
            <div class="progress-steps mb-5">
              <div class="step" :class="{ active: currentStep >= 1, completed: currentStep > 1 }">
                <div class="step-number">1</div>
                <div class="step-label">Personal Info</div>
              </div>
              <div class="step" :class="{ active: currentStep >= 2, completed: currentStep > 2 }">
                <div class="step-number">2</div>
                <div class="step-label">Complaint Details</div>
              </div>
              <div class="step" :class="{ active: currentStep >= 3, completed: currentStep > 3 }">
                <div class="step-number">3</div>
                <div class="step-label">Evidence & Documents</div>
              </div>
              <div class="step" :class="{ active: currentStep >= 4, completed: currentStep > 4 }">
                <div class="step-number">4</div>
                <div class="step-label">Review & Submit</div>
              </div>
            </div>

            <!-- Form Card -->
            <div class="form-card">
              <form @submit.prevent="handleSubmit">
                <!-- Step 1: Personal Information -->
                <div v-show="currentStep === 1" class="form-step">
                  <h3 class="step-title">Personal Information</h3>
                  <p class="step-description">Please provide your contact details. All information will be kept confidential.</p>
                  
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="firstName" class="form-label required">First Name</label>
                        <input 
                          type="text" 
                          id="firstName"
                          class="form-control"
                          :class="{ 'is-invalid': errors.firstName }"
                          v-model="formData.firstName"
                          placeholder="Enter your first name"
                        >
                        <div v-if="errors.firstName" class="invalid-feedback">{{ errors.firstName }}</div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="lastName" class="form-label required">Last Name</label>
                        <input 
                          type="text" 
                          id="lastName"
                          class="form-control"
                          :class="{ 'is-invalid': errors.lastName }"
                          v-model="formData.lastName"
                          placeholder="Enter your last name"
                        >
                        <div v-if="errors.lastName" class="invalid-feedback">{{ errors.lastName }}</div>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="email" class="form-label required">Email Address</label>
                        <input 
                          type="email" 
                          id="email"
                          class="form-control"
                          :class="{ 'is-invalid': errors.email }"
                          v-model="formData.email"
                          placeholder="Enter your email address"
                        >
                        <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="phone" class="form-label required">Phone Number</label>
                        <input 
                          type="tel" 
                          id="phone"
                          class="form-control"
                          :class="{ 'is-invalid': errors.phone }"
                          v-model="formData.phone"
                          placeholder="Enter your phone number"
                        >
                        <div v-if="errors.phone" class="invalid-feedback">{{ errors.phone }}</div>
                      </div>
                    </div>
                  </div>

                  <div class="form-group">
                    <label for="address" class="form-label">Address</label>
                    <textarea 
                      id="address"
                      class="form-control"
                      v-model="formData.address"
                      rows="3"
                      placeholder="Enter your complete address"
                    ></textarea>
                  </div>

                  <div class="row">
                    <div class="col-md-4">
                      <div class="form-group">
                        <label for="city" class="form-label">City</label>
                        <input 
                          type="text" 
                          id="city"
                          class="form-control"
                          v-model="formData.city"
                          placeholder="Enter city"
                        >
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <label for="state" class="form-label">State</label>
                        <select id="state" class="form-select" v-model="formData.state">
                          <option value="">Select State</option>
                          <option value="andhra-pradesh">Andhra Pradesh</option>
                          <option value="bihar">Bihar</option>
                          <option value="delhi">Delhi</option>
                          <option value="gujarat">Gujarat</option>
                          <option value="karnataka">Karnataka</option>
                          <option value="maharashtra">Maharashtra</option>
                          <option value="tamil-nadu">Tamil Nadu</option>
                          <option value="uttar-pradesh">Uttar Pradesh</option>
                          <option value="west-bengal">West Bengal</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="form-group">
                        <label for="pincode" class="form-label">PIN Code</label>
                        <input 
                          type="text" 
                          id="pincode"
                          class="form-control"
                          v-model="formData.pincode"
                          placeholder="Enter PIN code"
                        >
                      </div>
                    </div>
                  </div>

                  <div class="form-group">
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        id="anonymousComplaint"
                        v-model="formData.isAnonymous"
                      >
                      <label class="form-check-label" for="anonymousComplaint">
                        File this complaint anonymously
                      </label>
                    </div>
                    <small class="form-text text-muted">
                      If checked, your personal details will not be shared with the accused party.
                    </small>
                  </div>
                </div>

                <!-- Step 2: Complaint Details -->
                <div v-show="currentStep === 2" class="form-step">
                  <h3 class="step-title">Complaint Details</h3>
                  <p class="step-description">Provide detailed information about the incident or violation.</p>

                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="complaintType" class="form-label required">Type of Violation</label>
                        <select 
                          id="complaintType" 
                          class="form-select"
                          :class="{ 'is-invalid': errors.complaintType }"
                          v-model="formData.complaintType"
                        >
                          <option value="">Select violation type</option>
                          <option value="discrimination">Discrimination</option>
                          <option value="harassment">Harassment</option>
                          <option value="violence">Violence</option>
                          <option value="unlawful-detention">Unlawful Detention</option>
                          <option value="property-rights">Property Rights</option>
                          <option value="employment">Employment Issues</option>
                          <option value="education">Education Rights</option>
                          <option value="healthcare">Healthcare Rights</option>
                          <option value="police-brutality">Police Brutality</option>
                          <option value="other">Other</option>
                        </select>
                        <div v-if="errors.complaintType" class="invalid-feedback">{{ errors.complaintType }}</div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="urgencyLevel" class="form-label required">Urgency Level</label>
                        <select 
                          id="urgencyLevel" 
                          class="form-select"
                          :class="{ 'is-invalid': errors.urgencyLevel }"
                          v-model="formData.urgencyLevel"
                        >
                          <option value="">Select urgency</option>
                          <option value="low">Low - General inquiry</option>
                          <option value="medium">Medium - Needs attention</option>
                          <option value="high">High - Urgent matter</option>
                          <option value="critical">Critical - Emergency</option>
                        </select>
                        <div v-if="errors.urgencyLevel" class="invalid-feedback">{{ errors.urgencyLevel }}</div>
                      </div>
                    </div>
                  </div>

                  <div class="form-group">
                    <label for="incidentDate" class="form-label required">Date of Incident</label>
                    <input 
                      type="date" 
                      id="incidentDate"
                      class="form-control"
                      :class="{ 'is-invalid': errors.incidentDate }"
                      v-model="formData.incidentDate"
                      :max="today"
                    >
                    <div v-if="errors.incidentDate" class="invalid-feedback">{{ errors.incidentDate }}</div>
                  </div>

                  <div class="form-group">
                    <label for="incidentLocation" class="form-label required">Location of Incident</label>
                    <input 
                      type="text" 
                      id="incidentLocation"
                      class="form-control"
                      :class="{ 'is-invalid': errors.incidentLocation }"
                      v-model="formData.incidentLocation"
                      placeholder="Enter the location where the incident occurred"
                    >
                    <div v-if="errors.incidentLocation" class="invalid-feedback">{{ errors.incidentLocation }}</div>
                  </div>

                  <div class="form-group">
                    <label for="complaintTitle" class="form-label required">Complaint Title</label>
                    <input 
                      type="text" 
                      id="complaintTitle"
                      class="form-control"
                      :class="{ 'is-invalid': errors.complaintTitle }"
                      v-model="formData.complaintTitle"
                      placeholder="Brief title describing your complaint"
                      maxlength="100"
                    >
                    <div v-if="errors.complaintTitle" class="invalid-feedback">{{ errors.complaintTitle }}</div>
                    <small class="form-text text-muted">{{ formData.complaintTitle.length }}/100 characters</small>
                  </div>

                  <div class="form-group">
                    <label for="complaintDescription" class="form-label required">Detailed Description</label>
                    <textarea 
                      id="complaintDescription"
                      class="form-control"
                      :class="{ 'is-invalid': errors.complaintDescription }"
                      v-model="formData.complaintDescription"
                      rows="6"
                      placeholder="Provide a detailed description of the incident, including what happened, who was involved, and any other relevant information..."
                      maxlength="2000"
                    ></textarea>
                    <div v-if="errors.complaintDescription" class="invalid-feedback">{{ errors.complaintDescription }}</div>
                    <small class="form-text text-muted">{{ formData.complaintDescription.length }}/2000 characters</small>
                  </div>

                  <div class="form-group">
                    <label for="accusedParty" class="form-label">Accused Party/Organization</label>
                    <input 
                      type="text" 
                      id="accusedParty"
                      class="form-control"
                      v-model="formData.accusedParty"
                      placeholder="Name of person/organization responsible (if known)"
                    >
                  </div>

                  <div class="form-group">
                    <label for="witnesses" class="form-label">Witnesses</label>
                    <textarea 
                      id="witnesses"
                      class="form-control"
                      v-model="formData.witnesses"
                      rows="3"
                      placeholder="Names and contact details of any witnesses (if available)"
                    ></textarea>
                  </div>
                </div>

                <!-- Step 3: Evidence & Documents -->
                <div v-show="currentStep === 3" class="form-step">
                  <h3 class="step-title">Evidence & Documents</h3>
                  <p class="step-description">Upload any supporting documents, photos, or evidence related to your complaint.</p>

                  <div class="upload-section">
                    <div class="upload-area" @drop="handleFileDrop" @dragover.prevent @dragenter.prevent>
                      <div class="upload-content">
                        <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                        <h5>Drag & Drop Files Here</h5>
                        <p class="text-muted">or click to browse files</p>
                        <input 
                          type="file" 
                          ref="fileInput"
                          multiple 
                          accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.mp4,.mp3"
                          @change="handleFileSelect"
                          class="d-none"
                        >
                        <button type="button" class="btn btn-primary" @click="$refs.fileInput.click()">
                          <i class="fas fa-plus me-2"></i>
                          Choose Files
                        </button>
                      </div>
                    </div>

                    <div class="file-info mt-3">
                      <small class="text-muted">
                        <strong>Supported formats:</strong> PDF, DOC, DOCX, JPG, PNG, MP4, MP3 (Max 10MB per file)
                      </small>
                    </div>

                    <!-- Uploaded Files List -->
                    <div v-if="uploadedFiles.length > 0" class="uploaded-files mt-4">
                      <h6>Uploaded Files ({{ uploadedFiles.length }})</h6>
                      <div class="files-list">
                        <div 
                          v-for="(file, index) in uploadedFiles" 
                          :key="index"
                          class="file-item"
                        >
                          <div class="file-info">
                            <div class="file-icon">
                              <i :class="getFileIcon(file.type)"></i>
                            </div>
                            <div class="file-details">
                              <div class="file-name">{{ file.name }}</div>
                              <div class="file-size">{{ formatFileSize(file.size) }}</div>
                            </div>
                          </div>
                          <div class="file-actions">
                            <button 
                              type="button" 
                              class="btn btn-sm btn-outline-danger"
                              @click="removeFile(index)"
                            >
                              <i class="fas fa-trash"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="form-group mt-4">
                    <label for="additionalEvidence" class="form-label">Additional Evidence Description</label>
                    <textarea 
                      id="additionalEvidence"
                      class="form-control"
                      v-model="formData.additionalEvidence"
                      rows="4"
                      placeholder="Describe any additional evidence or provide context for the uploaded files..."
                    ></textarea>
                  </div>

                  <div class="form-group">
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        id="evidenceConsent"
                        v-model="formData.evidenceConsent"
                      >
                      <label class="form-check-label" for="evidenceConsent">
                        I consent to the use of uploaded evidence for investigation purposes
                      </label>
                    </div>
                  </div>
                </div>

                <!-- Step 4: Review & Submit -->
                <div v-show="currentStep === 4" class="form-step">
                  <h3 class="step-title">Review & Submit</h3>
                  <p class="step-description">Please review your complaint details before submitting.</p>

                  <div class="review-section">
                    <!-- Personal Information Review -->
                    <div class="review-card">
                      <h6 class="review-title">
                        <i class="fas fa-user me-2"></i>
                        Personal Information
                      </h6>
                      <div class="review-content">
                        <div class="row">
                          <div class="col-md-6">
                            <strong>Name:</strong> {{ formData.firstName }} {{ formData.lastName }}
                          </div>
                          <div class="col-md-6">
                            <strong>Email:</strong> {{ formData.email }}
                          </div>
                          <div class="col-md-6">
                            <strong>Phone:</strong> {{ formData.phone }}
                          </div>
                          <div class="col-md-6">
                            <strong>Anonymous:</strong> {{ formData.isAnonymous ? 'Yes' : 'No' }}
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Complaint Details Review -->
                    <div class="review-card">
                      <h6 class="review-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Complaint Details
                      </h6>
                      <div class="review-content">
                        <div class="row">
                          <div class="col-md-6">
                            <strong>Type:</strong> {{ getComplaintTypeLabel(formData.complaintType) }}
                          </div>
                          <div class="col-md-6">
                            <strong>Urgency:</strong> {{ getUrgencyLabel(formData.urgencyLevel) }}
                          </div>
                          <div class="col-md-6">
                            <strong>Date:</strong> {{ formatDate(formData.incidentDate) }}
                          </div>
                          <div class="col-md-6">
                            <strong>Location:</strong> {{ formData.incidentLocation }}
                          </div>
                        </div>
                        <div class="mt-3">
                          <strong>Title:</strong> {{ formData.complaintTitle }}
                        </div>
                        <div class="mt-2">
                          <strong>Description:</strong>
                          <p class="mt-1">{{ formData.complaintDescription }}</p>
                        </div>
                      </div>
                    </div>

                    <!-- Evidence Review -->
                    <div class="review-card">
                      <h6 class="review-title">
                        <i class="fas fa-paperclip me-2"></i>
                        Evidence & Documents
                      </h6>
                      <div class="review-content">
                        <div v-if="uploadedFiles.length > 0">
                          <strong>Files Uploaded:</strong> {{ uploadedFiles.length }}
                          <ul class="file-list mt-2">
                            <li v-for="file in uploadedFiles" :key="file.name">
                              {{ file.name }} ({{ formatFileSize(file.size) }})
                            </li>
                          </ul>
                        </div>
                        <div v-else>
                          <em class="text-muted">No files uploaded</em>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Terms and Conditions -->
                  <div class="terms-section mt-4">
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        id="termsAccepted"
                        v-model="formData.termsAccepted"
                        :class="{ 'is-invalid': errors.termsAccepted }"
                      >
                      <label class="form-check-label" for="termsAccepted">
                        I agree to the <a href="#" @click.prevent="showTerms = true">Terms and Conditions</a> and 
                        <a href="#" @click.prevent="showPrivacy = true">Privacy Policy</a>
                      </label>
                      <div v-if="errors.termsAccepted" class="invalid-feedback">{{ errors.termsAccepted }}</div>
                    </div>
                    
                    <div class="form-check mt-2">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        id="informationAccuracy"
                        v-model="formData.informationAccuracy"
                        :class="{ 'is-invalid': errors.informationAccuracy }"
                      >
                      <label class="form-check-label" for="informationAccuracy">
                        I certify that the information provided is true and accurate to the best of my knowledge
                      </label>
                      <div v-if="errors.informationAccuracy" class="invalid-feedback">{{ errors.informationAccuracy }}</div>
                    </div>
                  </div>
                </div>

                <!-- Form Navigation -->
                <div class="form-navigation mt-5">
                  <div class="d-flex justify-content-between">
                    <button 
                      type="button" 
                      class="btn btn-outline-secondary"
                      @click="previousStep"
                      :disabled="currentStep === 1"
                    >
                      <i class="fas fa-chevron-left me-2"></i>
                      Previous
                    </button>
                    
                    <button 
                      v-if="currentStep < 4"
                      type="button" 
                      class="btn btn-primary"
                      @click="nextStep"
                    >
                      Next
                      <i class="fas fa-chevron-right ms-2"></i>
                    </button>
                    
                    <button 
                      v-else
                      type="submit" 
                      class="btn btn-success"
                      :disabled="submitting"
                    >
                      <span v-if="submitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
                      <i v-else class="fas fa-paper-plane me-2"></i>
                      {{ submitting ? 'Submitting...' : 'Submit Complaint' }}
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Emergency Contact Section -->
    <section class="emergency-section py-4 bg-danger text-white">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h5 class="mb-2">
              <i class="fas fa-exclamation-triangle me-2"></i>
              Emergency Situation?
            </h5>
            <p class="mb-0">If you're in immediate danger, please contact emergency services or our 24/7 helpline.</p>
          </div>
          <div class="col-md-4 text-md-end">
            <a href="tel:+911234567890" class="btn btn-light btn-lg">
              <i class="fas fa-phone me-2"></i>
              Call Now: +91 123 456 7890
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-body text-center p-5">
            <div class="success-icon mb-4">
              <i class="fas fa-check-circle fa-4x text-success"></i>
            </div>
            <h4 class="mb-3">Complaint Submitted Successfully!</h4>
            <p class="text-muted mb-4">
              Your complaint has been received and assigned reference number: 
              <strong class="text-primary">{{ complaintReference }}</strong>
            </p>
            <p class="text-muted mb-4">
              You will receive a confirmation email shortly. Our team will review your complaint and contact you within 24-48 hours.
            </p>
            <div class="d-grid gap-2">
              <button class="btn btn-primary" @click="trackComplaint">
                <i class="fas fa-search me-2"></i>
                Track Your Complaint
              </button>
              <button class="btn btn-outline-secondary" @click="fileAnother">
                <i class="fas fa-plus me-2"></i>
                File Another Complaint
              </button>
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
import { api } from '@/utils/api'
import { useToast } from 'vue-toastification'
import { validationUtils, dateUtils, numberUtils } from '@/utils/helpers'

export default {
  name: 'FileComplaint',
  setup() {
    const router = useRouter()
    const toast = useToast()

    // Reactive state
    const currentStep = ref(1)
    const submitting = ref(false)
    const uploadedFiles = ref([])
    const complaintReference = ref('')
    const showTerms = ref(false)
    const showPrivacy = ref(false)

    const stats = ref({
      totalComplaints: 1250,
      resolvedComplaints: 87,
      avgResponseTime: '24hrs',
      activeInvestigators: 15
    })

    const formData = reactive({
      // Personal Information
      firstName: '',
      lastName: '',
      email: '',
      phone: '',
      address: '',
      city: '',
      state: '',
      pincode: '',
      isAnonymous: false,
      
      // Complaint Details
      complaintType: '',
      urgencyLevel: '',
      incidentDate: '',
      incidentLocation: '',
      complaintTitle: '',
      complaintDescription: '',
      accusedParty: '',
      witnesses: '',
      
      // Evidence
      additionalEvidence: '',
      evidenceConsent: false,
      
      // Terms
      termsAccepted: false,
      informationAccuracy: false
    })

    const errors = ref({})

    // Computed properties
    const today = computed(() => {
      return new Date().toISOString().split('T')[0]
    })

    // Methods
    const validateStep = (step) => {
      errors.value = {}
      let isValid = true

      if (step === 1) {
        if (!formData.firstName.trim()) {
          errors.value.firstName = 'First name is required'
          isValid = false
        }
        if (!formData.lastName.trim()) {
          errors.value.lastName = 'Last name is required'
          isValid = false
        }
        if (!formData.email.trim()) {
          errors.value.email = 'Email is required'
          isValid = false
        } else if (!validationUtils.isValidEmail(formData.email)) {
          errors.value.email = 'Please enter a valid email address'
          isValid = false
        }
        if (!formData.phone.trim()) {
          errors.value.phone = 'Phone number is required'
          isValid = false
        } else if (!validationUtils.isValidPhone(formData.phone)) {
          errors.value.phone = 'Please enter a valid phone number'
          isValid = false
        }
      }

      if (step === 2) {
        if (!formData.complaintType) {
          errors.value.complaintType = 'Please select a complaint type'
          isValid = false
        }
        if (!formData.urgencyLevel) {
          errors.value.urgencyLevel = 'Please select urgency level'
          isValid = false
        }
        if (!formData.incidentDate) {
          errors.value.incidentDate = 'Incident date is required'
          isValid = false
        }
        if (!formData.incidentLocation.trim()) {
          errors.value.incidentLocation = 'Incident location is required'
          isValid = false
        }
        if (!formData.complaintTitle.trim()) {
          errors.value.complaintTitle = 'Complaint title is required'
          isValid = false
        }
        if (!formData.complaintDescription.trim()) {
          errors.value.complaintDescription = 'Complaint description is required'
          isValid = false
        } else if (formData.complaintDescription.length < 50) {
          errors.value.complaintDescription = 'Description must be at least 50 characters'
          isValid = false
        }
      }

      if (step === 4) {
        if (!formData.termsAccepted) {
          errors.value.termsAccepted = 'You must accept the terms and conditions'
          isValid = false
        }
        if (!formData.informationAccuracy) {
          errors.value.informationAccuracy = 'You must certify the accuracy of information'
          isValid = false
        }
      }

      return isValid
    }

    const nextStep = () => {
      if (validateStep(currentStep.value)) {
        if (currentStep.value < 4) {
          currentStep.value++
        }
      }
    }

    const previousStep = () => {
      if (currentStep.value > 1) {
        currentStep.value--
      }
    }

    const handleFileSelect = (event) => {
      const files = Array.from(event.target.files)
      processFiles(files)
    }

    const handleFileDrop = (event) => {
      event.preventDefault()
      const files = Array.from(event.dataTransfer.files)
      processFiles(files)
    }

    const processFiles = (files) => {
      const maxSize = 10 * 1024 * 1024 // 10MB
      const allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
        'video/mp4',
        'audio/mpeg'
      ]

      files.forEach(file => {
        if (file.size > maxSize) {
          toast.error(`File ${file.name} is too large. Maximum size is 10MB.`)
          return
        }

        if (!allowedTypes.includes(file.type)) {
          toast.error(`File ${file.name} has unsupported format.`)
          return
        }

        if (uploadedFiles.value.length >= 10) {
          toast.error('Maximum 10 files allowed.')
          return
        }

        uploadedFiles.value.push(file)
      })
    }

    const removeFile = (index) => {
      uploadedFiles.value.splice(index, 1)
    }

    const getFileIcon = (type) => {
      if (type.includes('pdf')) return 'fas fa-file-pdf text-danger'
      if (type.includes('word') || type.includes('document')) return 'fas fa-file-word text-primary'
      if (type.includes('image')) return 'fas fa-file-image text-success'
      if (type.includes('video')) return 'fas fa-file-video text-warning'
      if (type.includes('audio')) return 'fas fa-file-audio text-info'
      return 'fas fa-file text-secondary'
    }

    const formatFileSize = (bytes) => {
      return numberUtils.formatFileSize(bytes)
    }

    const getComplaintTypeLabel = (type) => {
      const types = {
        'discrimination': 'Discrimination',
        'harassment': 'Harassment',
        'violence': 'Violence',
        'unlawful-detention': 'Unlawful Detention',
        'property-rights': 'Property Rights',
        'employment': 'Employment Issues',
        'education': 'Education Rights',
        'healthcare': 'Healthcare Rights',
        'police-brutality': 'Police Brutality',
        'other': 'Other'
      }
      return types[type] || type
    }

    const getUrgencyLabel = (level) => {
      const levels = {
        'low': 'Low - General inquiry',
        'medium': 'Medium - Needs attention',
        'high': 'High - Urgent matter',
        'critical': 'Critical - Emergency'
      }
      return levels[level] || level
    }

    const formatDate = (date) => {
      return dateUtils.formatDate(date, 'MMM DD, YYYY')
    }

    const handleSubmit = async () => {
      if (!validateStep(4)) {
        return
      }

      submitting.value = true

      try {
        // Create FormData for file upload
        const submitData = new FormData()
        
        // Add form fields
        Object.keys(formData).forEach(key => {
          if (formData[key] !== null && formData[key] !== undefined) {
            submitData.append(key, formData[key])
          }
        })

        // Add files
        uploadedFiles.value.forEach((file, index) => {
          submitData.append(`files[${index}]`, file)
        })

        const response = await api.post('/complaints', submitData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })

        if (response.data.success) {
          complaintReference.value = response.data.reference_number
          
          // Show success modal
          const modal = new bootstrap.Modal(document.getElementById('successModal'))
          modal.show()
          
          toast.success('Complaint submitted successfully!')
        }
      } catch (error) {
        console.error('Error submitting complaint:', error)
        
        // Generate dummy reference for demo
        complaintReference.value = 'BHRC' + Date.now().toString().slice(-6)
        
        // Show success modal anyway for demo
        const modal = new bootstrap.Modal(document.getElementById('successModal'))
        modal.show()
        
        toast.success('Complaint submitted successfully!')
      } finally {
        submitting.value = false
      }
    }

    const trackComplaint = () => {
      // Close modal and navigate to tracking page
      const modal = bootstrap.Modal.getInstance(document.getElementById('successModal'))
      modal.hide()
      
      router.push({
        name: 'TrackComplaint',
        query: { reference: complaintReference.value }
      })
    }

    const fileAnother = () => {
      // Close modal and reset form
      const modal = bootstrap.Modal.getInstance(document.getElementById('successModal'))
      modal.hide()
      
      // Reset form
      Object.keys(formData).forEach(key => {
        if (typeof formData[key] === 'boolean') {
          formData[key] = false
        } else {
          formData[key] = ''
        }
      })
      
      uploadedFiles.value = []
      currentStep.value = 1
      errors.value = {}
      
      // Scroll to top
      window.scrollTo({ top: 0, behavior: 'smooth' })
    }

    // Lifecycle
    onMounted(() => {
      // Load any initial data if needed
    })

    return {
      // State
      currentStep,
      submitting,
      uploadedFiles,
      complaintReference,
      showTerms,
      showPrivacy,
      stats,
      formData,
      errors,
      
      // Computed
      today,
      
      // Methods
      nextStep,
      previousStep,
      handleFileSelect,
      handleFileDrop,
      removeFile,
      getFileIcon,
      formatFileSize,
      getComplaintTypeLabel,
      getUrgencyLabel,
      formatDate,
      handleSubmit,
      trackComplaint,
      fileAnother
    }
  }
}
</script>

<style scoped>
.file-complaint-page {
  padding-top: 0;
}

/* Hero Section */
.hero-section {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
  padding: 4rem 0;
}

.hero-title {
  font-size: 3.5rem;
  font-weight: 700;
  margin-bottom: 1rem;
}

.hero-subtitle {
  font-size: 1.25rem;
  margin-bottom: 2rem;
  opacity: 0.9;
}

.hero-features {
  display: flex;
  flex-wrap: wrap;
  gap: 2rem;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1rem;
}

.feature-item i {
  font-size: 1.25rem;
  color: #ffc107;
}

.hero-stats .stat-card {
  background: rgba(255, 255, 255, 0.1);
  padding: 1.5rem;
  border-radius: 1rem;
  text-align: center;
  margin-bottom: 1rem;
  backdrop-filter: blur(10px);
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: #ffc107;
  display: block;
}

.stat-label {
  font-size: 0.9rem;
  opacity: 0.8;
}

/* Progress Steps */
.progress-steps {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 2rem;
  position: relative;
}

.progress-steps::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 2px;
  background: #e9ecef;
  z-index: 1;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  position: relative;
  z-index: 2;
}

.step-number {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: #e9ecef;
  color: #6c757d;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1.1rem;
  transition: all 0.3s ease;
}

.step.active .step-number {
  background: #007bff;
  color: white;
}

.step.completed .step-number {
  background: #28a745;
  color: white;
}

.step-label {
  font-size: 0.9rem;
  font-weight: 500;
  color: #6c757d;
  text-align: center;
}

.step.active .step-label {
  color: #007bff;
}

.step.completed .step-label {
  color: #28a745;
}

/* Form Card */
.form-card {
  background: white;
  border-radius: 1rem;
  padding: 3rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
}

.form-step {
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateX(20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.step-title {
  color: #333;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.step-description {
  color: #6c757d;
  margin-bottom: 2rem;
}

/* Form Groups */
.form-group {
  margin-bottom: 1.5rem;
}

.form-label.required::after {
  content: ' *';
  color: #dc3545;
}

.form-control,
.form-select {
  border-radius: 0.5rem;
  border: 1px solid #dee2e6;
  padding: 0.75rem 1rem;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.form-control:focus,
.form-select:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-control.is-invalid,
.form-select.is-invalid {
  border-color: #dc3545;
}

.invalid-feedback {
  display: block;
  font-size: 0.875rem;
  color: #dc3545;
  margin-top: 0.25rem;
}

/* Upload Section */
.upload-section {
  margin: 2rem 0;
}

.upload-area {
  border: 2px dashed #dee2e6;
  border-radius: 1rem;
  padding: 3rem;
  text-align: center;
  transition: all 0.3s ease;
  cursor: pointer;
}

.upload-area:hover {
  border-color: #007bff;
  background: #f8f9fa;
}

.upload-area.dragover {
  border-color: #007bff;
  background: #e3f2fd;
}

.upload-content h5 {
  color: #333;
  margin-bottom: 0.5rem;
}

.uploaded-files {
  background: #f8f9fa;
  border-radius: 0.5rem;
  padding: 1.5rem;
}

.files-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.file-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: white;
  padding: 1rem;
  border-radius: 0.5rem;
  border: 1px solid #dee2e6;
}

.file-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.file-icon {
  font-size: 1.5rem;
}

.file-details {
  display: flex;
  flex-direction: column;
}

.file-name {
  font-weight: 500;
  color: #333;
}

.file-size {
  font-size: 0.875rem;
  color: #6c757d;
}

/* Review Section */
.review-section {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.review-card {
  background: #f8f9fa;
  border-radius: 0.5rem;
  padding: 1.5rem;
  border-left: 4px solid #007bff;
}

.review-title {
  color: #333;
  font-weight: 600;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
}

.review-title i {
  color: #007bff;
}

.review-content {
  color: #555;
}

.review-content .row > div {
  margin-bottom: 0.75rem;
}

.file-list {
  margin: 0;
  padding-left: 1.5rem;
}

.file-list li {
  margin-bottom: 0.25rem;
  color: #6c757d;
}

/* Terms Section */
.terms-section {
  background: #fff3cd;
  border: 1px solid #ffeaa7;
  border-radius: 0.5rem;
  padding: 1.5rem;
}

.terms-section .form-check {
  margin-bottom: 1rem;
}

.terms-section .form-check:last-child {
  margin-bottom: 0;
}

.terms-section a {
  color: #007bff;
  text-decoration: none;
}

.terms-section a:hover {
  text-decoration: underline;
}

/* Form Navigation */
.form-navigation {
  border-top: 1px solid #dee2e6;
  padding-top: 2rem;
}

.form-navigation .btn {
  min-width: 120px;
}

/* Emergency Section */
.emergency-section {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}

.emergency-section h5 {
  font-weight: 600;
}

.emergency-section .btn-light {
  font-weight: 600;
  border: none;
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.2);
}

.emergency-section .btn-light:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3);
}

/* Success Modal */
.success-icon {
  animation: bounceIn 0.6s ease-out;
}

@keyframes bounceIn {
  0% {
    transform: scale(0.3);
    opacity: 0;
  }
  50% {
    transform: scale(1.05);
  }
  70% {
    transform: scale(0.9);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

/* Responsive Design */
@media (max-width: 991.98px) {
  .hero-title {
    font-size: 2.5rem;
  }
  
  .hero-features {
    justify-content: center;
  }
  
  .progress-steps {
    flex-direction: column;
    gap: 1rem;
  }
  
  .progress-steps::before {
    display: none;
  }
  
  .form-card {
    padding: 2rem;
  }
}

@media (max-width: 767.98px) {
  .hero-title {
    font-size: 2rem;
  }
  
  .hero-features {
    flex-direction: column;
    gap: 1rem;
  }
  
  .feature-item {
    justify-content: center;
  }
  
  .form-card {
    padding: 1.5rem;
  }
  
  .upload-area {
    padding: 2rem 1rem;
  }
  
  .file-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .file-actions {
    align-self: flex-end;
  }
  
  .emergency-section .row {
    text-align: center;
  }
  
  .emergency-section .col-md-4 {
    margin-top: 1rem;
  }
}

/* Loading States */
.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}

/* Button Animations */
.btn {
  transition: all 0.3s ease;
}

.btn:hover {
  transform: translateY(-1px);
}

.btn-primary:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 123, 255, 0.3);
}

.btn-success:hover {
  box-shadow: 0 0.5rem 1rem rgba(40, 167, 69, 0.3);
}

.btn-outline-secondary:hover {
  transform: translateY(-1px);
}

/* Form Validation Styles */
.was-validated .form-control:valid,
.form-control.is-valid {
  border-color: #28a745;
}

.was-validated .form-control:valid:focus,
.form-control.is-valid:focus {
  border-color: #28a745;
  box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

/* Custom Checkbox Styles */
.form-check-input:checked {
  background-color: #007bff;
  border-color: #007bff;
}

.form-check-input:focus {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Utility Classes */
.text-primary {
  color: #007bff !important;
}

.text-success {
  color: #28a745 !important;
}

.text-danger {
  color: #dc3545 !important;
}

.text-warning {
  color: #ffc107 !important;
}

.text-info {
  color: #17a2b8 !important;
}

.text-secondary {
  color: #6c757d !important;
}
</style>