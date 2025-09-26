<template>
  <div class="member-certificates-page">
    <!-- Header Section -->
    <div class="certificates-header">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-8">
            <div class="header-content">
              <h1 class="page-title">
                <i class="fas fa-certificate me-3"></i>
                My Certificates
              </h1>
              <p class="page-subtitle">
                Access, download, and manage your certificates, awards, and training completions
              </p>
            </div>
          </div>
          <div class="col-lg-4 text-lg-end">
            <div class="header-stats">
              <div class="stat-card">
                <div class="stat-value">{{ certificateStats.totalCount }}</div>
                <div class="stat-label">Total Certificates</div>
              </div>
              <div class="stat-card">
                <div class="stat-value">{{ certificateStats.thisYear }}</div>
                <div class="stat-label">This Year</div>
              </div>
              <div class="stat-card">
                <div class="stat-value">{{ certificateStats.verified }}</div>
                <div class="stat-label">Verified</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Certificates Content -->
    <div class="certificates-content">
      <div class="container">
        <!-- Quick Actions -->
        <div class="quick-actions">
          <div class="row">
            <div class="col-lg-8">
              <div class="action-cards">
                <div class="action-card" @click="requestCertificate">
                  <div class="action-icon">
                    <i class="fas fa-plus"></i>
                  </div>
                  <div class="action-content">
                    <h5>Request Certificate</h5>
                    <p>Request a new certificate for completed activities</p>
                  </div>
                </div>
                
                <div class="action-card" @click="verifyCertificate">
                  <div class="action-icon">
                    <i class="fas fa-shield-alt"></i>
                  </div>
                  <div class="action-content">
                    <h5>Verify Certificate</h5>
                    <p>Verify the authenticity of any certificate</p>
                  </div>
                </div>
                
                <div class="action-card" @click="downloadAll">
                  <div class="action-icon">
                    <i class="fas fa-download"></i>
                  </div>
                  <div class="action-content">
                    <h5>Download All</h5>
                    <p>Download all certificates as a ZIP file</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="certificate-summary">
                <h6>Certificate Summary</h6>
                <div class="summary-item">
                  <span>Training Certificates</span>
                  <strong>{{ certificateStats.training }}</strong>
                </div>
                <div class="summary-item">
                  <span>Event Certificates</span>
                  <strong>{{ certificateStats.events }}</strong>
                </div>
                <div class="summary-item">
                  <span>Achievement Awards</span>
                  <strong>{{ certificateStats.achievements }}</strong>
                </div>
                <div class="summary-item">
                  <span>Membership Certificates</span>
                  <strong>{{ certificateStats.membership }}</strong>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Filter and Search -->
        <div class="filter-section">
          <div class="row align-items-center">
            <div class="col-lg-6">
              <div class="search-box">
                <div class="input-group">
                  <span class="input-group-text">
                    <i class="fas fa-search"></i>
                  </span>
                  <input 
                    type="text" 
                    class="form-control" 
                    placeholder="Search certificates..."
                    v-model="searchQuery"
                    @input="filterCertificates"
                  >
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="filter-controls">
                <select 
                  class="form-select me-2" 
                  v-model="selectedYear"
                  @change="filterCertificates"
                >
                  <option value="">All Years</option>
                  <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                </select>
                
                <select 
                  class="form-select me-2" 
                  v-model="selectedType"
                  @change="filterCertificates"
                >
                  <option value="">All Types</option>
                  <option value="training">Training</option>
                  <option value="event">Event</option>
                  <option value="achievement">Achievement</option>
                  <option value="membership">Membership</option>
                </select>
                
                <select 
                  class="form-select me-2" 
                  v-model="selectedStatus"
                  @change="filterCertificates"
                >
                  <option value="">All Status</option>
                  <option value="issued">Issued</option>
                  <option value="pending">Pending</option>
                  <option value="expired">Expired</option>
                  <option value="revoked">Revoked</option>
                </select>

                <button 
                  class="btn btn-outline-secondary"
                  @click="resetFilters"
                >
                  <i class="fas fa-times me-2"></i>
                  Clear
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Certificate Tabs -->
        <div class="certificate-tabs">
          <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button 
                class="nav-link" 
                :class="{ active: activeTab === 'all' }"
                @click="switchTab('all')"
                type="button"
              >
                <i class="fas fa-list me-2"></i>
                All Certificates
                <span class="badge bg-primary ms-2">{{ filteredCertificates.length }}</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button 
                class="nav-link" 
                :class="{ active: activeTab === 'training' }"
                @click="switchTab('training')"
                type="button"
              >
                <i class="fas fa-graduation-cap me-2"></i>
                Training
                <span class="badge bg-success ms-2">{{ getTabCount('training') }}</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button 
                class="nav-link" 
                :class="{ active: activeTab === 'events' }"
                @click="switchTab('events')"
                type="button"
              >
                <i class="fas fa-calendar-alt me-2"></i>
                Events
                <span class="badge bg-info ms-2">{{ getTabCount('event') }}</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button 
                class="nav-link" 
                :class="{ active: activeTab === 'achievements' }"
                @click="switchTab('achievements')"
                type="button"
              >
                <i class="fas fa-trophy me-2"></i>
                Achievements
                <span class="badge bg-warning ms-2">{{ getTabCount('achievement') }}</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button 
                class="nav-link" 
                :class="{ active: activeTab === 'membership' }"
                @click="switchTab('membership')"
                type="button"
              >
                <i class="fas fa-id-card me-2"></i>
                Membership
                <span class="badge bg-secondary ms-2">{{ getTabCount('membership') }}</span>
              </button>
            </li>
          </ul>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Loading certificates...</p>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ error }}
            <button class="btn btn-outline-danger btn-sm ms-3" @click="loadCertificates">
              <i class="fas fa-redo me-2"></i>
              Retry
            </button>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="displayedCertificates.length === 0" class="empty-state">
          <div class="empty-illustration">
            <i class="fas fa-certificate"></i>
          </div>
          <h3>{{ certificates.length === 0 ? 'No Certificates Yet' : 'No Matching Certificates' }}</h3>
          <p>
            {{ certificates.length === 0 
              ? 'You haven\'t earned any certificates yet. Participate in events and training to earn certificates!' 
              : 'No certificates match your current filters. Try adjusting your search criteria.' 
            }}
          </p>
          <button 
            v-if="certificates.length === 0"
            class="btn btn-primary" 
            @click="requestCertificate"
          >
            <i class="fas fa-plus me-2"></i>
            Request Certificate
          </button>
          <button 
            v-else
            class="btn btn-outline-primary" 
            @click="resetFilters"
          >
            <i class="fas fa-times me-2"></i>
            Clear Filters
          </button>
        </div>

        <!-- Certificates Grid -->
        <div v-else class="certificates-grid">
          <div 
            v-for="certificate in paginatedCertificates" 
            :key="certificate.id"
            class="certificate-card"
            :class="certificate.type"
          >
            <div class="certificate-header">
              <div class="certificate-type">
                <i class="fas" :class="getTypeIcon(certificate.type)"></i>
                <span>{{ getTypeText(certificate.type) }}</span>
              </div>
              <div class="certificate-status">
                <span class="status-badge" :class="certificate.status">
                  <i class="fas" :class="getStatusIcon(certificate.status)"></i>
                  {{ getStatusText(certificate.status) }}
                </span>
              </div>
            </div>

            <div class="certificate-content">
              <div class="certificate-preview" v-if="certificate.preview_image">
                <img :src="certificate.preview_image" :alt="certificate.title" @click="viewCertificate(certificate)">
                <div class="preview-overlay">
                  <button class="btn btn-light btn-sm" @click="viewCertificate(certificate)">
                    <i class="fas fa-eye me-2"></i>
                    View
                  </button>
                </div>
              </div>
              
              <div class="certificate-info">
                <h5 class="certificate-title">{{ certificate.title }}</h5>
                <p class="certificate-description">{{ certificate.description }}</p>
                
                <div class="certificate-details">
                  <div class="detail-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span><strong>Issued:</strong> {{ formatDate(certificate.issued_date) }}</span>
                  </div>
                  <div class="detail-item" v-if="certificate.expiry_date">
                    <i class="fas fa-clock"></i>
                    <span><strong>Expires:</strong> {{ formatDate(certificate.expiry_date) }}</span>
                  </div>
                  <div class="detail-item" v-if="certificate.issuer">
                    <i class="fas fa-building"></i>
                    <span><strong>Issuer:</strong> {{ certificate.issuer }}</span>
                  </div>
                  <div class="detail-item" v-if="certificate.certificate_id">
                    <i class="fas fa-hashtag"></i>
                    <span><strong>ID:</strong> {{ certificate.certificate_id }}</span>
                  </div>
                </div>

                <div class="certificate-verification" v-if="certificate.verification_code">
                  <div class="verification-info">
                    <i class="fas fa-shield-check text-success"></i>
                    <span>Verification Code: <strong>{{ certificate.verification_code }}</strong></span>
                  </div>
                </div>

                <div class="certificate-skills" v-if="certificate.skills && certificate.skills.length > 0">
                  <h6>Skills & Competencies</h6>
                  <div class="skills-tags">
                    <span 
                      v-for="skill in certificate.skills" 
                      :key="skill"
                      class="skill-tag"
                    >
                      {{ skill }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="certificate-actions">
              <button 
                v-if="certificate.status === 'issued'"
                class="btn btn-primary btn-sm"
                @click="downloadCertificate(certificate)"
              >
                <i class="fas fa-download me-2"></i>
                Download PDF
              </button>
              
              <button 
                class="btn btn-outline-info btn-sm"
                @click="viewCertificate(certificate)"
              >
                <i class="fas fa-eye me-2"></i>
                View Details
              </button>
              
              <button 
                v-if="certificate.verification_code"
                class="btn btn-outline-success btn-sm"
                @click="verifyCertificate(certificate.verification_code)"
              >
                <i class="fas fa-shield-alt me-2"></i>
                Verify
              </button>
              
              <button 
                v-if="certificate.shareable"
                class="btn btn-outline-secondary btn-sm"
                @click="shareCertificate(certificate)"
              >
                <i class="fas fa-share-alt me-2"></i>
                Share
              </button>
              
              <button 
                v-if="canRenew(certificate)"
                class="btn btn-outline-warning btn-sm"
                @click="renewCertificate(certificate)"
              >
                <i class="fas fa-redo me-2"></i>
                Renew
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="pagination-container">
          <nav aria-label="Certificates pagination">
            <ul class="pagination justify-content-center">
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
    </div>

    <!-- Certificate Details Modal -->
    <div class="modal fade" id="certificateDetailsModal" tabindex="-1" ref="certificateDetailsModal">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-certificate me-2"></i>
              {{ selectedCertificate?.title }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" v-if="selectedCertificate">
            <div class="certificate-details-content">
              <div class="row">
                <div class="col-lg-8">
                  <!-- Certificate Preview -->
                  <div class="certificate-preview-large">
                    <img 
                      v-if="selectedCertificate.preview_image"
                      :src="selectedCertificate.preview_image" 
                      :alt="selectedCertificate.title"
                      class="img-fluid"
                    >
                    <div v-else class="no-preview">
                      <i class="fas fa-certificate"></i>
                      <p>Certificate preview not available</p>
                    </div>
                  </div>
                </div>
                
                <div class="col-lg-4">
                  <!-- Certificate Information -->
                  <div class="certificate-info-panel">
                    <div class="info-section">
                      <h6>Certificate Information</h6>
                      <div class="info-item">
                        <strong>Title:</strong>
                        <span>{{ selectedCertificate.title }}</span>
                      </div>
                      <div class="info-item">
                        <strong>Type:</strong>
                        <span class="type-badge" :class="selectedCertificate.type">
                          {{ getTypeText(selectedCertificate.type) }}
                        </span>
                      </div>
                      <div class="info-item">
                        <strong>Status:</strong>
                        <span class="status-badge" :class="selectedCertificate.status">
                          {{ getStatusText(selectedCertificate.status) }}
                        </span>
                      </div>
                      <div class="info-item">
                        <strong>Issued Date:</strong>
                        <span>{{ formatDate(selectedCertificate.issued_date) }}</span>
                      </div>
                      <div class="info-item" v-if="selectedCertificate.expiry_date">
                        <strong>Expiry Date:</strong>
                        <span>{{ formatDate(selectedCertificate.expiry_date) }}</span>
                      </div>
                      <div class="info-item" v-if="selectedCertificate.issuer">
                        <strong>Issuer:</strong>
                        <span>{{ selectedCertificate.issuer }}</span>
                      </div>
                      <div class="info-item" v-if="selectedCertificate.certificate_id">
                        <strong>Certificate ID:</strong>
                        <span>{{ selectedCertificate.certificate_id }}</span>
                      </div>
                    </div>

                    <div class="info-section" v-if="selectedCertificate.verification_code">
                      <h6>Verification</h6>
                      <div class="verification-panel">
                        <div class="verification-code">
                          <strong>Verification Code:</strong>
                          <code>{{ selectedCertificate.verification_code }}</code>
                        </div>
                        <div class="verification-url" v-if="selectedCertificate.verification_url">
                          <strong>Verify Online:</strong>
                          <a :href="selectedCertificate.verification_url" target="_blank">
                            {{ selectedCertificate.verification_url }}
                          </a>
                        </div>
                      </div>
                    </div>

                    <div class="info-section" v-if="selectedCertificate.skills && selectedCertificate.skills.length > 0">
                      <h6>Skills & Competencies</h6>
                      <div class="skills-list">
                        <span 
                          v-for="skill in selectedCertificate.skills" 
                          :key="skill"
                          class="skill-badge"
                        >
                          {{ skill }}
                        </span>
                      </div>
                    </div>

                    <div class="info-section" v-if="selectedCertificate.description">
                      <h6>Description</h6>
                      <p>{{ selectedCertificate.description }}</p>
                    </div>

                    <div class="info-section" v-if="selectedCertificate.requirements">
                      <h6>Requirements Met</h6>
                      <ul class="requirements-list">
                        <li v-for="requirement in selectedCertificate.requirements" :key="requirement">
                          <i class="fas fa-check text-success me-2"></i>
                          {{ requirement }}
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button 
              v-if="selectedCertificate?.status === 'issued'"
              type="button" 
              class="btn btn-primary"
              @click="downloadCertificate(selectedCertificate)"
            >
              <i class="fas fa-download me-2"></i>
              Download PDF
            </button>
            <button 
              v-if="selectedCertificate?.verification_code"
              type="button" 
              class="btn btn-success"
              @click="verifyCertificate(selectedCertificate.verification_code)"
            >
              <i class="fas fa-shield-alt me-2"></i>
              Verify Certificate
            </button>
            <button 
              v-if="selectedCertificate?.shareable"
              type="button" 
              class="btn btn-info"
              @click="shareCertificate(selectedCertificate)"
            >
              <i class="fas fa-share-alt me-2"></i>
              Share Certificate
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Certificate Request Modal -->
    <div class="modal fade" id="certificateRequestModal" tabindex="-1" ref="certificateRequestModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-plus me-2"></i>
              Request Certificate
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitCertificateRequest">
              <div class="mb-3">
                <label class="form-label">Certificate Type *</label>
                <select class="form-select" v-model="certificateRequest.type" required>
                  <option value="">Select certificate type</option>
                  <option value="training">Training Certificate</option>
                  <option value="event">Event Participation</option>
                  <option value="achievement">Achievement Award</option>
                  <option value="membership">Membership Certificate</option>
                </select>
              </div>

              <div class="mb-3" v-if="certificateRequest.type">
                <label class="form-label">Related Activity *</label>
                <select class="form-select" v-model="certificateRequest.activity_id" required>
                  <option value="">Select activity</option>
                  <option 
                    v-for="activity in availableActivities" 
                    :key="activity.id" 
                    :value="activity.id"
                  >
                    {{ activity.title }} - {{ formatDate(activity.date) }}
                  </option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label">Additional Information</label>
                <textarea 
                  class="form-control" 
                  rows="3"
                  v-model="certificateRequest.notes"
                  placeholder="Any additional information or special requirements..."
                ></textarea>
              </div>

              <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> Certificate requests are reviewed by our team. 
                You will be notified once your certificate is ready for download.
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button 
              type="button" 
              class="btn btn-primary"
              @click="submitCertificateRequest"
              :disabled="submitting"
            >
              <i class="fas me-2" :class="submitting ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
              {{ submitting ? 'Submitting...' : 'Submit Request' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Certificate Verification Modal -->
    <div class="modal fade" id="verificationModal" tabindex="-1" ref="verificationModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-shield-alt me-2"></i>
              Verify Certificate
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="performVerification">
              <div class="mb-3">
                <label class="form-label">Verification Code *</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="verificationCode"
                  placeholder="Enter verification code"
                  required
                >
              </div>

              <div v-if="verificationResult" class="verification-result">
                <div v-if="verificationResult.valid" class="alert alert-success">
                  <i class="fas fa-check-circle me-2"></i>
                  <strong>Certificate Verified!</strong>
                  <div class="mt-2">
                    <strong>Title:</strong> {{ verificationResult.certificate.title }}<br>
                    <strong>Holder:</strong> {{ verificationResult.certificate.holder_name }}<br>
                    <strong>Issued:</strong> {{ formatDate(verificationResult.certificate.issued_date) }}
                  </div>
                </div>
                <div v-else class="alert alert-danger">
                  <i class="fas fa-times-circle me-2"></i>
                  <strong>Invalid Certificate</strong>
                  <p class="mb-0">{{ verificationResult.message }}</p>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button 
              type="button" 
              class="btn btn-primary"
              @click="performVerification"
              :disabled="verifying"
            >
              <i class="fas me-2" :class="verifying ? 'fa-spinner fa-spin' : 'fa-shield-alt'"></i>
              {{ verifying ? 'Verifying...' : 'Verify' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { Modal } from 'bootstrap'
import { api } from '@/utils/api'

export default {
  name: 'MemberCertificates',
  setup() {
    const authStore = useAuthStore()

    // Reactive data
    const loading = ref(false)
    const error = ref('')
    const submitting = ref(false)
    const verifying = ref(false)
    const searchQuery = ref('')
    const selectedYear = ref('')
    const selectedType = ref('')
    const selectedStatus = ref('')
    const activeTab = ref('all')
    const currentPage = ref(1)
    const itemsPerPage = 12
    const selectedCertificate = ref(null)
    const verificationCode = ref('')
    const verificationResult = ref(null)

    // Certificates data
    const certificates = ref([])
    const availableActivities = ref([])

    // Certificate request form
    const certificateRequest = reactive({
      type: '',
      activity_id: '',
      notes: ''
    })

    // Modals
    const certificateDetailsModal = ref(null)
    const certificateRequestModal = ref(null)
    const verificationModal = ref(null)

    // Computed properties
    const certificateStats = computed(() => {
      const currentYear = new Date().getFullYear()
      
      const totalCount = certificates.value.length
      const thisYear = certificates.value.filter(c => 
        new Date(c.issued_date).getFullYear() === currentYear
      ).length
      const verified = certificates.value.filter(c => c.verification_code).length
      
      const training = certificates.value.filter(c => c.type === 'training').length
      const events = certificates.value.filter(c => c.type === 'event').length
      const achievements = certificates.value.filter(c => c.type === 'achievement').length
      const membership = certificates.value.filter(c => c.type === 'membership').length

      return {
        totalCount,
        thisYear,
        verified,
        training,
        events,
        achievements,
        membership
      }
    })

    const availableYears = computed(() => {
      const years = [...new Set(certificates.value.map(c => new Date(c.issued_date).getFullYear()))]
      return years.sort((a, b) => b - a)
    })

    const filteredCertificates = computed(() => {
      let filtered = [...certificates.value]

      // Search filter
      if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(cert => 
          cert.title.toLowerCase().includes(query) ||
          cert.description.toLowerCase().includes(query) ||
          (cert.certificate_id && cert.certificate_id.toLowerCase().includes(query)) ||
          (cert.issuer && cert.issuer.toLowerCase().includes(query))
        )
      }

      // Year filter
      if (selectedYear.value) {
        filtered = filtered.filter(cert => 
          new Date(cert.issued_date).getFullYear() == selectedYear.value
        )
      }

      // Type filter
      if (selectedType.value) {
        filtered = filtered.filter(cert => cert.type === selectedType.value)
      }

      // Status filter
      if (selectedStatus.value) {
        filtered = filtered.filter(cert => cert.status === selectedStatus.value)
      }

      // Tab filter
      if (activeTab.value !== 'all') {
        const tabTypeMap = {
          'training': 'training',
          'events': 'event',
          'achievements': 'achievement',
          'membership': 'membership'
        }
        filtered = filtered.filter(cert => cert.type === tabTypeMap[activeTab.value])
      }

      // Sort by issued date (newest first)
      filtered.sort((a, b) => new Date(b.issued_date) - new Date(a.issued_date))

      return filtered
    })

    const displayedCertificates = computed(() => {
      return filteredCertificates.value
    })

    const paginatedCertificates = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage
      const end = start + itemsPerPage
      return displayedCertificates.value.slice(start, end)
    })

    const totalPages = computed(() => {
      return Math.ceil(displayedCertificates.value.length / itemsPerPage)
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

    // Methods
    const loadCertificates = async () => {
      loading.value = true
      error.value = ''
      
      try {
        const response = await api.get('member/certificates')
        certificates.value = response.data.certificates || []
      } catch (err) {
        console.error('Error loading certificates:', err)
        error.value = 'Failed to load certificates. Please try again.'
      } finally {
        loading.value = false
      }
    }

    const loadAvailableActivities = async () => {
      try {
        const response = await api.get('member/activities/eligible')
        availableActivities.value = response.data.activities || []
      } catch (err) {
        console.error('Error loading activities:', err)
      }
    }

    const filterCertificates = () => {
      currentPage.value = 1
    }

    const resetFilters = () => {
      searchQuery.value = ''
      selectedYear.value = ''
      selectedType.value = ''
      selectedStatus.value = ''
      currentPage.value = 1
    }

    const switchTab = (tab) => {
      activeTab.value = tab
      currentPage.value = 1
    }

    const getTabCount = (type) => {
      return certificates.value.filter(c => c.type === type).length
    }

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
      }
    }

    const viewCertificate = (certificate) => {
      selectedCertificate.value = certificate
      const modal = new Modal(certificateDetailsModal.value)
      modal.show()
    }

    const downloadCertificate = async (certificate) => {
      try {
        const response = await fetch(`/backend/api.php/member/certificates/${certificate.id}/download`, {
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          }
        })

        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = `${certificate.title.replace(/[^a-z0-9]/gi, '_').toLowerCase()}.pdf`
          a.click()
          window.URL.revokeObjectURL(url)
        } else {
          throw new Error('Failed to download certificate')
        }
      } catch (err) {
        console.error('Error downloading certificate:', err)
        alert('Failed to download certificate')
      }
    }

    const downloadAll = async () => {
      if (certificates.value.length === 0) {
        alert('No certificates to download')
        return
      }

      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/member/certificates/download-all', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          }
        })

        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = `certificates_${new Date().getFullYear()}.zip`
          a.click()
          window.URL.revokeObjectURL(url)
        } else {
          throw new Error('Failed to download certificates')
        }
      } catch (err) {
        console.error('Error downloading certificates:', err)
        alert('Failed to download certificates')
      }
    }

    const requestCertificate = async () => {
      await loadAvailableActivities()
      
      // Reset form
      Object.assign(certificateRequest, {
        type: '',
        activity_id: '',
        notes: ''
      })
      
      const modal = new Modal(certificateRequestModal.value)
      modal.show()
    }

    const submitCertificateRequest = async () => {
      if (!certificateRequest.type || !certificateRequest.activity_id) {
        alert('Please fill in all required fields')
        return
      }

      submitting.value = true
      
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/member/certificates/request', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(certificateRequest)
        })

        if (response.ok) {
          const data = await response.json()
          
          // Close modal
          const modal = Modal.getInstance(certificateRequestModal.value)
          modal.hide()
          
          alert('Certificate request submitted successfully! You will be notified when it\'s ready.')
          
          // Reload certificates
          await loadCertificates()
        } else {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Failed to submit certificate request')
        }
      } catch (err) {
        console.error('Error submitting certificate request:', err)
        alert(err.message || 'Failed to submit certificate request')
      } finally {
        submitting.value = false
      }
    }

    const verifyCertificate = (code = null) => {
      verificationCode.value = code || ''
      verificationResult.value = null
      
      const modal = new Modal(verificationModal.value)
      modal.show()
    }

    const performVerification = async () => {
      if (!verificationCode.value.trim()) {
        alert('Please enter a verification code')
        return
      }

      verifying.value = true
      verificationResult.value = null
      
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/certificates/verify', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            verification_code: verificationCode.value.trim()
          })
        })

        if (response.ok) {
          const data = await response.json()
          verificationResult.value = data
        } else {
          const errorData = await response.json()
          verificationResult.value = {
            valid: false,
            message: errorData.message || 'Verification failed'
          }
        }
      } catch (err) {
        console.error('Error verifying certificate:', err)
        verificationResult.value = {
          valid: false,
          message: 'Verification service unavailable'
        }
      } finally {
        verifying.value = false
      }
    }

    const shareCertificate = async (certificate) => {
      if (navigator.share) {
        try {
          await navigator.share({
            title: certificate.title,
            text: `Check out my certificate: ${certificate.title}`,
            url: certificate.verification_url || window.location.href
          })
        } catch (err) {
          console.error('Error sharing certificate:', err)
        }
      } else {
        // Fallback: copy to clipboard
        const shareText = `Check out my certificate: ${certificate.title}\nVerification Code: ${certificate.verification_code}`
        navigator.clipboard.writeText(shareText).then(() => {
          alert('Certificate details copied to clipboard!')
        }).catch(() => {
          alert('Unable to share certificate')
        })
      }
    }

    const renewCertificate = async (certificate) => {
      if (!confirm(`Are you sure you want to renew the certificate "${certificate.title}"?`)) return

      try {
        const response = await fetch(`/backend/api.php/member/certificates/${certificate.id}/renew`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          alert('Certificate renewal request submitted successfully!')
          await loadCertificates()
        } else {
          throw new Error('Failed to renew certificate')
        }
      } catch (err) {
        console.error('Error renewing certificate:', err)
        alert('Failed to renew certificate')
      }
    }

    const canRenew = (certificate) => {
      if (!certificate.expiry_date) return false
      
      const expiryDate = new Date(certificate.expiry_date)
      const now = new Date()
      const thirtyDaysFromNow = new Date(now.getTime() + (30 * 24 * 60 * 60 * 1000))
      
      return expiryDate <= thirtyDaysFromNow && certificate.status === 'issued'
    }

    const getTypeIcon = (type) => {
      const icons = {
        training: 'fa-graduation-cap',
        event: 'fa-calendar-alt',
        achievement: 'fa-trophy',
        membership: 'fa-id-card'
      }
      return icons[type] || 'fa-certificate'
    }

    const getTypeText = (type) => {
      const texts = {
        training: 'Training',
        event: 'Event',
        achievement: 'Achievement',
        membership: 'Membership'
      }
      return texts[type] || type
    }

    const getStatusIcon = (status) => {
      const icons = {
        issued: 'fa-check-circle',
        pending: 'fa-clock',
        expired: 'fa-exclamation-triangle',
        revoked: 'fa-ban'
      }
      return icons[status] || 'fa-question-circle'
    }

    const getStatusText = (status) => {
      const texts = {
        issued: 'Issued',
        pending: 'Pending',
        expired: 'Expired',
        revoked: 'Revoked'
      }
      return texts[status] || status
    }

    const formatDate = (dateString) => {
      const date = new Date(dateString)
      return date.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }

    // Lifecycle
    onMounted(() => {
      loadCertificates()
    })

    return {
      // Data
      loading,
      error,
      submitting,
      verifying,
      searchQuery,
      selectedYear,
      selectedType,
      selectedStatus,
      activeTab,
      currentPage,
      selectedCertificate,
      verificationCode,
      verificationResult,
      certificates,
      availableActivities,
      certificateRequest,
      certificateDetailsModal,
      certificateRequestModal,
      verificationModal,
      
      // Computed
      certificateStats,
      availableYears,
      filteredCertificates,
      displayedCertificates,
      paginatedCertificates,
      totalPages,
      visiblePages,
      
      // Methods
      loadCertificates,
      loadAvailableActivities,
      filterCertificates,
      resetFilters,
      switchTab,
      getTabCount,
      changePage,
      viewCertificate,
      downloadCertificate,
      downloadAll,
      requestCertificate,
      submitCertificateRequest,
      verifyCertificate,
      performVerification,
      shareCertificate,
      renewCertificate,
      canRenew,
      getTypeIcon,
      getTypeText,
      getStatusIcon,
      getStatusText,
      formatDate
    }
  }
}
</script>

<style scoped>
/* Certificates Page Styles */
.member-certificates-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Header Section */
.certificates-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 3rem 0;
  position: relative;
  overflow: hidden;
}

.certificates-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="20" cy="60" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="30" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
  opacity: 0.3;
}

.header-content {
  position: relative;
  z-index: 2;
}

.page-title {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.page-subtitle {
  font-size: 1.1rem;
  opacity: 0.9;
  margin-bottom: 0;
}

.header-stats {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.stat-card {
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  padding: 1rem;
  text-align: center;
  min-width: 80px;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: white;
}

.stat-label {
  font-size: 0.8rem;
  opacity: 0.9;
  margin-top: 0.25rem;
}

/* Content Section */
.certificates-content {
  background: #f8f9fa;
  min-height: calc(100vh - 200px);
  padding: 2rem 0;
  position: relative;
  z-index: 1;
}

/* Quick Actions */
.quick-actions {
  margin-bottom: 2rem;
}

.action-cards {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.action-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  border: 1px solid #e9ecef;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  flex: 1;
  min-width: 250px;
}

.action-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0,0,0,0.15);
  border-color: #007bff;
}

.action-icon {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #007bff, #0056b3);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
}

.action-content h5 {
  margin: 0 0 0.25rem 0;
  color: #333;
  font-weight: 600;
}

.action-content p {
  margin: 0;
  color: #666;
  font-size: 0.9rem;
}

.certificate-summary {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #e9ecef;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.certificate-summary h6 {
  color: #333;
  font-weight: 600;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #f8f9fa;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f8f9fa;
}

.summary-item:last-child {
  border-bottom: none;
}

.summary-item span {
  color: #666;
}

.summary-item strong {
  color: #333;
  font-weight: 600;
}

/* Filter Section */
.filter-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  border: 1px solid #e9ecef;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.search-box .input-group-text {
  background: #f8f9fa;
  border-color: #dee2e6;
  color: #666;
}

.search-box .form-control {
  border-left: none;
}

.search-box .form-control:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.filter-controls {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.filter-controls .form-select {
  min-width: 120px;
}

/* Certificate Tabs */
.certificate-tabs {
  margin-bottom: 2rem;
}

.nav-tabs {
  border-bottom: 2px solid #e9ecef;
  background: white;
  border-radius: 12px 12px 0 0;
  padding: 0.5rem 0.5rem 0 0.5rem;
}

.nav-tabs .nav-link {
  border: none;
  border-radius: 8px;
  color: #666;
  font-weight: 500;
  padding: 0.75rem 1rem;
  margin-right: 0.25rem;
  transition: all 0.3s ease;
}

.nav-tabs .nav-link:hover {
  background: #f8f9fa;
  color: #333;
}

.nav-tabs .nav-link.active {
  background: #007bff;
  color: white;
}

.nav-tabs .badge {
  font-size: 0.7rem;
}

/* Loading, Error, Empty States */
.loading-state,
.error-state,
.empty-state {
  background: white;
  border-radius: 12px;
  padding: 3rem;
  text-align: center;
  border: 1px solid #e9ecef;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.empty-illustration {
  font-size: 4rem;
  color: #dee2e6;
  margin-bottom: 1rem;
}

.empty-state h3 {
  color: #333;
  margin-bottom: 1rem;
}

.empty-state p {
  color: #666;
  margin-bottom: 2rem;
}

/* Certificates Grid */
.certificates-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.certificate-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  border: 1px solid #e9ecef;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  transition: all 0.3s ease;
  position: relative;
}

.certificate-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}

.certificate-card.training {
  border-left: 4px solid #28a745;
}

.certificate-card.event {
  border-left: 4px solid #17a2b8;
}

.certificate-card.achievement {
  border-left: 4px solid #ffc107;
}

.certificate-card.membership {
  border-left: 4px solid #6f42c1;
}

.certificate-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

.certificate-type {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
  color: #333;
}

.certificate-type i {
  width: 20px;
  text-align: center;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.status-badge.issued {
  background: #d4edda;
  color: #155724;
}

.status-badge.pending {
  background: #fff3cd;
  color: #856404;
}

.status-badge.expired {
  background: #f8d7da;
  color: #721c24;
}

.status-badge.revoked {
  background: #f5c6cb;
  color: #721c24;
}

.certificate-content {
  padding: 1.5rem;
}

.certificate-preview {
  position: relative;
  margin-bottom: 1rem;
  border-radius: 8px;
  overflow: hidden;
  background: #f8f9fa;
}

.certificate-preview img {
  width: 100%;
  height: 200px;
  object-fit: cover;
  cursor: pointer;
  transition: transform 0.3s ease;
}

.certificate-preview:hover img {
  transform: scale(1.05);
}

.preview-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.certificate-preview:hover .preview-overlay {
  opacity: 1;
}

.certificate-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #333;
  margin-bottom: 0.5rem;
  line-height: 1.4;
}

.certificate-description {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 1rem;
  line-height: 1.5;
}

.certificate-details {
  margin-bottom: 1rem;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.detail-item i {
  width: 16px;
  color: #666;
  text-align: center;
}

.detail-item span {
  color: #333;
}

.certificate-verification {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.verification-info {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.certificate-skills h6 {
  font-size: 0.9rem;
  font-weight: 600;
  color: #333;
  margin-bottom: 0.5rem;
}

.skills-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
}

.skill-tag {
  background: #e9ecef;
  color: #495057;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.certificate-actions {
  padding: 1rem 1.5rem;
  background: #f8f9fa;
  border-top: 1px solid #e9ecef;
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.certificate-actions .btn {
  font-size: 0.8rem;
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-weight: 500;
}

/* Pagination */
.pagination-container {
  margin-top: 2rem;
  padding: 2rem 0;
}

.pagination .page-link {
  border-radius: 8px;
  margin: 0 0.125rem;
  border: 1px solid #dee2e6;
  color: #007bff;
  font-weight: 500;
}

.pagination .page-link:hover {
  background: #e9ecef;
  border-color: #adb5bd;
}

.pagination .page-item.active .page-link {
  background: #007bff;
  border-color: #007bff;
}

/* Modal Styles */
.modal-content {
  border-radius: 16px;
  border: none;
  box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.modal-header {
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
  border-radius: 16px 16px 0 0;
  padding: 1.5rem;
}

.modal-title {
  font-weight: 600;
  color: #333;
}

.modal-body {
  padding: 2rem;
}

.modal-footer {
  background: #f8f9fa;
  border-top: 1px solid #e9ecef;
  border-radius: 0 0 16px 16px;
  padding: 1rem 2rem;
}

/* Certificate Details Modal */
.certificate-details-content {
  margin: -1rem;
}

.certificate-preview-large {
  background: #f8f9fa;
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 1rem;
}

.certificate-preview-large img {
  width: 100%;
  height: auto;
  max-height: 500px;
  object-fit: contain;
}

.no-preview {
  padding: 3rem;
  text-align: center;
  color: #666;
}

.no-preview i {
  font-size: 3rem;
  margin-bottom: 1rem;
  color: #dee2e6;
}

.certificate-info-panel {
  background: #f8f9fa;
  border-radius: 12px;
  padding: 1.5rem;
  height: fit-content;
}

.info-section {
  margin-bottom: 2rem;
}

.info-section:last-child {
  margin-bottom: 0;
}

.info-section h6 {
  font-weight: 600;
  color: #333;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #e9ecef;
}

.info-item {
  display: flex;
  flex-direction: column;
  margin-bottom: 0.75rem;
}

.info-item strong {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 0.25rem;
}

.info-item span,
.info-item .type-badge,
.info-item .status-badge {
  color: #333;
  font-weight: 500;
}

.type-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  width: fit-content;
}

.type-badge.training {
  background: #d4edda;
  color: #155724;
}

.type-badge.event {
  background: #d1ecf1;
  color: #0c5460;
}

.type-badge.achievement {
  background: #fff3cd;
  color: #856404;
}

.type-badge.membership {
  background: #e2e3f1;
  color: #383d41;
}

.verification-panel {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  border: 1px solid #e9ecef;
}

.verification-code {
  margin-bottom: 0.5rem;
}

.verification-code code {
  background: #f8f9fa;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-family: 'Courier New', monospace;
  color: #e83e8c;
}

.verification-url a {
  color: #007bff;
  text-decoration: none;
  word-break: break-all;
}

.verification-url a:hover {
  text-decoration: underline;
}

.skills-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.skill-badge {
  background: #007bff;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
}

.requirements-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.requirements-list li {
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  color: #333;
}

/* Verification Modal */
.verification-result {
  margin-top: 1rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .certificates-grid {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  }
}

@media (max-width: 768px) {
  .page-title {
    font-size: 2rem;
  }
  
  .header-stats {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .stat-card {
    min-width: auto;
  }
  
  .action-cards {
    flex-direction: column;
  }
  
  .action-card {
    min-width: auto;
  }
  
  .filter-controls {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .filter-controls .form-select {
    min-width: auto;
  }
  
  .certificates-grid {
    grid-template-columns: 1fr;
  }
  
  .certificate-actions {
    flex-direction: column;
  }
  
  .certificate-actions .btn {
    width: 100%;
    justify-content: center;
  }
  
  .modal-dialog.modal-xl {
    max-width: 95%;
    margin: 1rem auto;
  }
  
  .certificate-details-content .row {
    flex-direction: column;
  }
  
  .certificate-info-panel {
    margin-top: 1rem;
  }
}

@media (max-width: 576px) {
  .certificates-header {
    padding: 2rem 0;
  }
  
  .page-title {
    font-size: 1.75rem;
  }
  
  .certificates-content {
    padding: 1rem 0;
  }
  
  .quick-actions,
  .filter-section,
  .certificate-tabs {
    margin-bottom: 1rem;
  }
  
  .action-card,
  .certificate-summary,
  .filter-section {
    padding: 1rem;
  }
  
  .nav-tabs {
    flex-wrap: wrap;
    padding: 0.25rem;
  }
  
  .nav-tabs .nav-link {
    padding: 0.5rem 0.75rem;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
  }
  
  .certificate-card {
    border-radius: 12px;
  }
  
  .certificate-content,
  .certificate-actions {
    padding: 1rem;
  }
  
  .modal-body {
    padding: 1rem;
  }
  
  .certificate-info-panel {
    padding: 1rem;
  }
}

/* Print Styles */
@media print {
  .certificates-header,
  .quick-actions,
  .filter-section,
  .certificate-tabs,
  .pagination-container {
    display: none;
  }
  
  .certificates-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }
  
  .certificate-card {
    break-inside: avoid;
    box-shadow: none;
    border: 1px solid #333;
  }
  
  .certificate-actions {
    display: none;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .certificates-content {
    background: #1a1a1a;
  }
  
  .action-card,
  .certificate-summary,
  .filter-section,
  .certificate-card {
    background: #2d2d2d;
    border-color: #404040;
    color: #e0e0e0;
  }
  
  .certificate-header,
  .certificate-actions {
    background: #404040;
    border-color: #555;
  }
  
  .nav-tabs {
    background: #2d2d2d;
    border-color: #404040;
  }
  
  .nav-tabs .nav-link {
    color: #ccc;
  }
  
  .nav-tabs .nav-link:hover {
    background: #404040;
    color: #fff;
  }
  
  .loading-state,
  .error-state,
  .empty-state {
    background: #2d2d2d;
    border-color: #404040;
    color: #e0e0e0;
  }
}

/* Accessibility */
.certificate-card:focus-within {
  outline: 2px solid #007bff;
  outline-offset: 2px;
}

.btn:focus {
  box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.form-control:focus,
.form-select:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

/* Animations */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.certificate-card {
  animation: fadeInUp 0.3s ease-out;
}

.certificate-card:nth-child(even) {
  animation-delay: 0.1s;
}

.certificate-card:nth-child(3n) {
  animation-delay: 0.2s;
}

/* Loading Animation */
@keyframes pulse {
  0% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
  100% {
    opacity: 1;
  }
}

.loading-state .spinner-border {
  animation: pulse 1.5s ease-in-out infinite;
}

/* Custom Scrollbar */
.certificate-info-panel::-webkit-scrollbar {
  width: 6px;
}

.certificate-info-panel::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.certificate-info-panel::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.certificate-info-panel::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>