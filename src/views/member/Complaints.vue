<template>
  <div class="member-complaints-page">
    <!-- Header Section -->
    <div class="complaints-header">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-8">
            <div class="header-content">
              <h1 class="page-title">
                <i class="fas fa-file-alt me-3"></i>
                My Complaints
              </h1>
              <p class="page-subtitle">
                Track your complaints, view status updates, and manage your submissions
              </p>
            </div>
          </div>
          <div class="col-lg-4 text-lg-end">
            <div class="header-stats">
              <div class="stat-card">
                <div class="stat-value">{{ complaintStats.total }}</div>
                <div class="stat-label">Total</div>
              </div>
              <div class="stat-card">
                <div class="stat-value">{{ complaintStats.pending }}</div>
                <div class="stat-label">Pending</div>
              </div>
              <div class="stat-card">
                <div class="stat-value">{{ complaintStats.resolved }}</div>
                <div class="stat-label">Resolved</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Complaints Content -->
    <div class="complaints-content">
      <div class="container">
        <!-- Action Bar -->
        <div class="action-bar">
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
                    placeholder="Search complaints..."
                    v-model="searchQuery"
                    @input="filterComplaints"
                  >
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="action-controls">
                <select 
                  class="form-select me-2" 
                  v-model="selectedStatus"
                  @change="filterComplaints"
                >
                  <option value="">All Status</option>
                  <option value="submitted">Submitted</option>
                  <option value="under_review">Under Review</option>
                  <option value="in_progress">In Progress</option>
                  <option value="resolved">Resolved</option>
                  <option value="closed">Closed</option>
                  <option value="rejected">Rejected</option>
                </select>
                
                <select 
                  class="form-select me-2" 
                  v-model="selectedPriority"
                  @change="filterComplaints"
                >
                  <option value="">All Priority</option>
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>

                <button 
                  class="btn btn-outline-secondary me-2"
                  @click="resetFilters"
                >
                  <i class="fas fa-times me-2"></i>
                  Clear
                </button>

                <button 
                  class="btn btn-primary"
                  @click="showNewComplaintModal"
                >
                  <i class="fas fa-plus me-2"></i>
                  New Complaint
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Loading complaints...</p>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ error }}
            <button class="btn btn-outline-danger btn-sm ms-3" @click="loadComplaints">
              <i class="fas fa-redo me-2"></i>
              Retry
            </button>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="filteredComplaints.length === 0" class="empty-state">
          <div class="empty-illustration">
            <i class="fas fa-file-alt"></i>
          </div>
          <h3>{{ complaints.length === 0 ? 'No Complaints Yet' : 'No Matching Complaints' }}</h3>
          <p>
            {{ complaints.length === 0 
              ? 'You haven\'t submitted any complaints yet. Click the button below to file your first complaint.' 
              : 'No complaints match your current filters. Try adjusting your search criteria.' 
            }}
          </p>
          <button 
            v-if="complaints.length === 0"
            class="btn btn-primary" 
            @click="showNewComplaintModal"
          >
            <i class="fas fa-plus me-2"></i>
            File Your First Complaint
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

        <!-- Complaints List -->
        <div v-else class="complaints-list">
          <div 
            v-for="complaint in paginatedComplaints" 
            :key="complaint.id"
            class="complaint-card"
            @click="viewComplaintDetails(complaint)"
          >
            <div class="complaint-header">
              <div class="complaint-id">
                <strong>Complaint #{{ complaint.complaint_id }}</strong>
                <span class="complaint-date">{{ formatDate(complaint.created_at) }}</span>
              </div>
              <div class="complaint-status">
                <span class="status-badge" :class="complaint.status">
                  <i class="fas" :class="getStatusIcon(complaint.status)"></i>
                  {{ getStatusText(complaint.status) }}
                </span>
                <span class="priority-badge" :class="complaint.priority">
                  {{ complaint.priority.toUpperCase() }}
                </span>
              </div>
            </div>

            <div class="complaint-content">
              <h4 class="complaint-title">{{ complaint.title }}</h4>
              <p class="complaint-description">{{ complaint.description }}</p>
              
              <div class="complaint-meta">
                <div class="meta-item">
                  <i class="fas fa-tag"></i>
                  <span>{{ complaint.type }}</span>
                </div>
                <div class="meta-item" v-if="complaint.location">
                  <i class="fas fa-map-marker-alt"></i>
                  <span>{{ complaint.location }}</span>
                </div>
                <div class="meta-item" v-if="complaint.incident_date">
                  <i class="fas fa-calendar"></i>
                  <span>{{ formatDate(complaint.incident_date) }}</span>
                </div>
              </div>

              <div class="complaint-progress" v-if="complaint.status !== 'submitted'">
                <div class="progress-info">
                  <span>Progress</span>
                  <span>{{ getProgressPercentage(complaint.status) }}%</span>
                </div>
                <div class="progress">
                  <div 
                    class="progress-bar" 
                    :class="getProgressBarClass(complaint.status)"
                    :style="{ width: getProgressPercentage(complaint.status) + '%' }"
                  ></div>
                </div>
              </div>

              <div class="complaint-updates" v-if="complaint.latest_update">
                <div class="update-item">
                  <i class="fas fa-clock"></i>
                  <div class="update-content">
                    <strong>Latest Update:</strong>
                    <p>{{ complaint.latest_update.message }}</p>
                    <small>{{ formatDateTime(complaint.latest_update.created_at) }}</small>
                  </div>
                </div>
              </div>
            </div>

            <div class="complaint-actions">
              <button 
                class="btn btn-outline-primary btn-sm"
                @click.stop="viewComplaintDetails(complaint)"
              >
                <i class="fas fa-eye me-2"></i>
                View Details
              </button>
              
              <button 
                v-if="complaint.documents && complaint.documents.length > 0"
                class="btn btn-outline-secondary btn-sm"
                @click.stop="viewDocuments(complaint)"
              >
                <i class="fas fa-paperclip me-2"></i>
                Documents ({{ complaint.documents.length }})
              </button>
              
              <button 
                v-if="canAddUpdate(complaint)"
                class="btn btn-outline-info btn-sm"
                @click.stop="addUpdate(complaint)"
              >
                <i class="fas fa-plus me-2"></i>
                Add Update
              </button>
              
              <button 
                v-if="canWithdraw(complaint)"
                class="btn btn-outline-danger btn-sm"
                @click.stop="withdrawComplaint(complaint)"
              >
                <i class="fas fa-times me-2"></i>
                Withdraw
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="pagination-container">
          <nav aria-label="Complaints pagination">
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

    <!-- Complaint Details Modal -->
    <div class="modal fade" id="complaintDetailsModal" tabindex="-1" ref="complaintDetailsModal">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              Complaint #{{ selectedComplaint?.complaint_id }}
              <span class="status-badge ms-2" :class="selectedComplaint?.status">
                {{ getStatusText(selectedComplaint?.status) }}
              </span>
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" v-if="selectedComplaint">
            <div class="complaint-details-content">
              <!-- Complaint Information -->
              <div class="details-section">
                <h6>Complaint Information</h6>
                <div class="info-grid">
                  <div class="info-item">
                    <strong>Title:</strong>
                    <span>{{ selectedComplaint.title }}</span>
                  </div>
                  <div class="info-item">
                    <strong>Type:</strong>
                    <span>{{ selectedComplaint.type }}</span>
                  </div>
                  <div class="info-item">
                    <strong>Priority:</strong>
                    <span class="priority-badge" :class="selectedComplaint.priority">
                      {{ selectedComplaint.priority.toUpperCase() }}
                    </span>
                  </div>
                  <div class="info-item">
                    <strong>Submitted:</strong>
                    <span>{{ formatDateTime(selectedComplaint.created_at) }}</span>
                  </div>
                  <div class="info-item" v-if="selectedComplaint.incident_date">
                    <strong>Incident Date:</strong>
                    <span>{{ formatDate(selectedComplaint.incident_date) }}</span>
                  </div>
                  <div class="info-item" v-if="selectedComplaint.location">
                    <strong>Location:</strong>
                    <span>{{ selectedComplaint.location }}</span>
                  </div>
                </div>
              </div>

              <!-- Description -->
              <div class="details-section">
                <h6>Description</h6>
                <div class="description-content">
                  {{ selectedComplaint.description }}
                </div>
              </div>

              <!-- Documents -->
              <div class="details-section" v-if="selectedComplaint.documents && selectedComplaint.documents.length > 0">
                <h6>Attached Documents</h6>
                <div class="documents-grid">
                  <div 
                    v-for="document in selectedComplaint.documents" 
                    :key="document.id"
                    class="document-item"
                  >
                    <div class="document-icon">
                      <i class="fas" :class="getDocumentIcon(document.type)"></i>
                    </div>
                    <div class="document-info">
                      <strong>{{ document.name }}</strong>
                      <small>{{ formatFileSize(document.size) }}</small>
                    </div>
                    <button 
                      class="btn btn-outline-primary btn-sm"
                      @click="downloadDocument(document)"
                    >
                      <i class="fas fa-download"></i>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Status Timeline -->
              <div class="details-section">
                <h6>Status Timeline</h6>
                <div class="status-timeline">
                  <div 
                    v-for="update in selectedComplaint.updates" 
                    :key="update.id"
                    class="timeline-item"
                  >
                    <div class="timeline-marker" :class="update.type">
                      <i class="fas" :class="getUpdateIcon(update.type)"></i>
                    </div>
                    <div class="timeline-content">
                      <div class="timeline-header">
                        <strong>{{ update.title }}</strong>
                        <span class="timeline-date">{{ formatDateTime(update.created_at) }}</span>
                      </div>
                      <p class="timeline-message">{{ update.message }}</p>
                      <div class="timeline-author" v-if="update.author">
                        <small>By: {{ update.author }}</small>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Assigned Officer -->
              <div class="details-section" v-if="selectedComplaint.assigned_officer">
                <h6>Assigned Officer</h6>
                <div class="officer-info">
                  <div class="officer-avatar">
                    <img :src="selectedComplaint.assigned_officer.avatar || '/images/default-avatar.png'" :alt="selectedComplaint.assigned_officer.name">
                  </div>
                  <div class="officer-details">
                    <strong>{{ selectedComplaint.assigned_officer.name }}</strong>
                    <p>{{ selectedComplaint.assigned_officer.designation }}</p>
                    <div class="contact-info">
                      <span><i class="fas fa-envelope"></i> {{ selectedComplaint.assigned_officer.email }}</span>
                      <span><i class="fas fa-phone"></i> {{ selectedComplaint.assigned_officer.phone }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button 
              v-if="canAddUpdate(selectedComplaint)"
              type="button" 
              class="btn btn-info"
              @click="addUpdate(selectedComplaint)"
            >
              <i class="fas fa-plus me-2"></i>
              Add Update
            </button>
            <button 
              v-if="canWithdraw(selectedComplaint)"
              type="button" 
              class="btn btn-danger"
              @click="withdrawComplaint(selectedComplaint)"
            >
              <i class="fas fa-times me-2"></i>
              Withdraw Complaint
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- New Complaint Modal -->
    <div class="modal fade" id="newComplaintModal" tabindex="-1" ref="newComplaintModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-plus me-2"></i>
              File New Complaint
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitComplaint">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Complaint Type *</label>
                    <select class="form-select" v-model="newComplaint.type" required>
                      <option value="">Select Type</option>
                      <option value="discrimination">Discrimination</option>
                      <option value="harassment">Harassment</option>
                      <option value="violence">Violence</option>
                      <option value="corruption">Corruption</option>
                      <option value="negligence">Negligence</option>
                      <option value="other">Other</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Priority Level *</label>
                    <select class="form-select" v-model="newComplaint.priority" required>
                      <option value="low">Low</option>
                      <option value="medium">Medium</option>
                      <option value="high">High</option>
                      <option value="urgent">Urgent</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Complaint Title *</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="newComplaint.title"
                  placeholder="Brief title describing your complaint"
                  required
                >
              </div>

              <div class="mb-3">
                <label class="form-label">Detailed Description *</label>
                <textarea 
                  class="form-control" 
                  rows="5"
                  v-model="newComplaint.description"
                  placeholder="Provide detailed information about your complaint..."
                  required
                ></textarea>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Incident Date</label>
                    <input 
                      type="date" 
                      class="form-control" 
                      v-model="newComplaint.incident_date"
                    >
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Location</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="newComplaint.location"
                      placeholder="Where did this incident occur?"
                    >
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Supporting Documents</label>
                <input 
                  type="file" 
                  class="form-control" 
                  multiple
                  accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                  @change="handleFileUpload"
                >
                <small class="form-text text-muted">
                  Upload supporting documents (PDF, DOC, DOCX, JPG, PNG). Max 5MB per file.
                </small>
              </div>

              <div class="mb-3">
                <div class="form-check">
                  <input 
                    class="form-check-input" 
                    type="checkbox" 
                    v-model="newComplaint.anonymous"
                    id="anonymousCheck"
                  >
                  <label class="form-check-label" for="anonymousCheck">
                    Submit this complaint anonymously
                  </label>
                </div>
              </div>

              <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Please Note:</strong> All complaints are treated with strict confidentiality. 
                You will receive updates on the progress of your complaint via email and through this portal.
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button 
              type="button" 
              class="btn btn-primary"
              @click="submitComplaint"
              :disabled="submitting"
            >
              <i class="fas me-2" :class="submitting ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i>
              {{ submitting ? 'Submitting...' : 'Submit Complaint' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Update Modal -->
    <div class="modal fade" id="addUpdateModal" tabindex="-1" ref="addUpdateModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Add Update</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitUpdate">
              <div class="mb-3">
                <label class="form-label">Update Message *</label>
                <textarea 
                  class="form-control" 
                  rows="4"
                  v-model="updateMessage"
                  placeholder="Provide additional information or updates..."
                  required
                ></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button 
              type="button" 
              class="btn btn-primary"
              @click="submitUpdate"
              :disabled="submittingUpdate"
            >
              <i class="fas me-2" :class="submittingUpdate ? 'fa-spinner fa-spin' : 'fa-plus'"></i>
              {{ submittingUpdate ? 'Adding...' : 'Add Update' }}
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

export default {
  name: 'MemberComplaints',
  setup() {
    const authStore = useAuthStore()

    // Reactive data
    const loading = ref(false)
    const error = ref('')
    const searchQuery = ref('')
    const selectedStatus = ref('')
    const selectedPriority = ref('')
    const currentPage = ref(1)
    const itemsPerPage = 10
    const submitting = ref(false)
    const submittingUpdate = ref(false)
    const selectedComplaint = ref(null)
    const updateMessage = ref('')
    const updateComplaintId = ref(null)

    // Complaints data
    const complaints = ref([])

    // New complaint form
    const newComplaint = reactive({
      type: '',
      priority: 'medium',
      title: '',
      description: '',
      incident_date: '',
      location: '',
      anonymous: false,
      documents: []
    })

    // Modals
    const complaintDetailsModal = ref(null)
    const newComplaintModal = ref(null)
    const addUpdateModal = ref(null)

    // Computed properties
    const complaintStats = computed(() => ({
      total: complaints.value.length,
      pending: complaints.value.filter(c => ['submitted', 'under_review', 'in_progress'].includes(c.status)).length,
      resolved: complaints.value.filter(c => c.status === 'resolved').length
    }))

    const filteredComplaints = computed(() => {
      let filtered = [...complaints.value]

      // Search filter
      if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(complaint => 
          complaint.title.toLowerCase().includes(query) ||
          complaint.description.toLowerCase().includes(query) ||
          complaint.complaint_id.toLowerCase().includes(query) ||
          complaint.type.toLowerCase().includes(query)
        )
      }

      // Status filter
      if (selectedStatus.value) {
        filtered = filtered.filter(complaint => complaint.status === selectedStatus.value)
      }

      // Priority filter
      if (selectedPriority.value) {
        filtered = filtered.filter(complaint => complaint.priority === selectedPriority.value)
      }

      // Sort by creation date (newest first)
      filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))

      return filtered
    })

    const paginatedComplaints = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage
      const end = start + itemsPerPage
      return filteredComplaints.value.slice(start, end)
    })

    const totalPages = computed(() => {
      return Math.ceil(filteredComplaints.value.length / itemsPerPage)
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
    const loadComplaints = async () => {
      loading.value = true
      error.value = ''
      
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/member/complaints', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          const data = await response.json()
          complaints.value = data.complaints || []
        } else {
          throw new Error('Failed to load complaints')
        }
      } catch (err) {
        console.error('Error loading complaints:', err)
        error.value = 'Failed to load complaints. Please try again.'
      } finally {
        loading.value = false
      }
    }

    const filterComplaints = () => {
      currentPage.value = 1
    }

    const resetFilters = () => {
      searchQuery.value = ''
      selectedStatus.value = ''
      selectedPriority.value = ''
      currentPage.value = 1
    }

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
      }
    }

    const viewComplaintDetails = async (complaint) => {
      selectedComplaint.value = complaint
      
      // Load detailed complaint information
      try {
        const response = await fetch(`/backend/api.php/member/complaints/${complaint.id}`, {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          const data = await response.json()
          selectedComplaint.value = data.complaint
        }
      } catch (err) {
        console.error('Error loading complaint details:', err)
      }
      
      const modal = new Modal(complaintDetailsModal.value)
      modal.show()
    }

    const showNewComplaintModal = () => {
      // Reset form
      Object.assign(newComplaint, {
        type: '',
        priority: 'medium',
        title: '',
        description: '',
        incident_date: '',
        location: '',
        anonymous: false,
        documents: []
      })
      
      const modal = new Modal(newComplaintModal.value)
      modal.show()
    }

    const handleFileUpload = (event) => {
      const files = Array.from(event.target.files)
      newComplaint.documents = files.filter(file => file.size <= 5 * 1024 * 1024) // 5MB limit
    }

    const submitComplaint = async () => {
      submitting.value = true
      
      try {
        const formData = new FormData()
        
        // Add complaint data
        Object.keys(newComplaint).forEach(key => {
          if (key !== 'documents') {
            formData.append(key, newComplaint[key])
          }
        })
        
        // Add documents
        newComplaint.documents.forEach((file, index) => {
          formData.append(`documents[${index}]`, file)
        })

        const response = await fetch('https://bhrcdata.online/backend/api.php/member/complaints', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          },
          body: formData
        })

        if (response.ok) {
          const data = await response.json()
          
          // Add new complaint to list
          complaints.value.unshift(data.complaint)
          
          // Close modal
          const modal = Modal.getInstance(newComplaintModal.value)
          modal.hide()
          
          alert('Complaint submitted successfully!')
        } else {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Failed to submit complaint')
        }
      } catch (err) {
        console.error('Error submitting complaint:', err)
        alert(err.message || 'Failed to submit complaint')
      } finally {
        submitting.value = false
      }
    }

    const addUpdate = (complaint) => {
      updateComplaintId.value = complaint.id
      updateMessage.value = ''
      
      // Close details modal if open
      const detailsModal = Modal.getInstance(complaintDetailsModal.value)
      if (detailsModal) detailsModal.hide()
      
      const modal = new Modal(addUpdateModal.value)
      modal.show()
    }

    const submitUpdate = async () => {
      if (!updateMessage.value.trim()) return

      submittingUpdate.value = true
      
      try {
        const response = await fetch(`/backend/api.php/member/complaints/${updateComplaintId.value}/update`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            message: updateMessage.value
          })
        })

        if (response.ok) {
          // Reload complaints to get updated data
          await loadComplaints()
          
          // Close modal
          const modal = Modal.getInstance(addUpdateModal.value)
          modal.hide()
          
          alert('Update added successfully!')
        } else {
          throw new Error('Failed to add update')
        }
      } catch (err) {
        console.error('Error adding update:', err)
        alert('Failed to add update')
      } finally {
        submittingUpdate.value = false
      }
    }

    const withdrawComplaint = async (complaint) => {
      if (!confirm(`Are you sure you want to withdraw complaint #${complaint.complaint_id}?`)) return

      try {
        const response = await fetch(`/backend/api.php/member/complaints/${complaint.id}/withdraw`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          // Update complaint status
          const complaintIndex = complaints.value.findIndex(c => c.id === complaint.id)
          if (complaintIndex !== -1) {
            complaints.value[complaintIndex].status = 'withdrawn'
          }
          
          alert('Complaint withdrawn successfully')
        } else {
          throw new Error('Failed to withdraw complaint')
        }
      } catch (err) {
        console.error('Error withdrawing complaint:', err)
        alert('Failed to withdraw complaint')
      }
    }

    const viewDocuments = (complaint) => {
      // This would open a document viewer or download documents
      alert('Document viewer feature coming soon!')
    }

    const downloadDocument = async (document) => {
      try {
        const response = await fetch(`/backend/api.php/member/complaints/document/${document.id}`, {
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          }
        })

        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = document.name
          a.click()
          window.URL.revokeObjectURL(url)
        } else {
          throw new Error('Failed to download document')
        }
      } catch (err) {
        console.error('Error downloading document:', err)
        alert('Failed to download document')
      }
    }

    const canAddUpdate = (complaint) => {
      return complaint && ['submitted', 'under_review', 'in_progress'].includes(complaint.status)
    }

    const canWithdraw = (complaint) => {
      return complaint && ['submitted', 'under_review'].includes(complaint.status)
    }

    const getStatusIcon = (status) => {
      const icons = {
        submitted: 'fa-paper-plane',
        under_review: 'fa-search',
        in_progress: 'fa-cog',
        resolved: 'fa-check-circle',
        closed: 'fa-times-circle',
        rejected: 'fa-ban',
        withdrawn: 'fa-undo'
      }
      return icons[status] || 'fa-question-circle'
    }

    const getStatusText = (status) => {
      const texts = {
        submitted: 'Submitted',
        under_review: 'Under Review',
        in_progress: 'In Progress',
        resolved: 'Resolved',
        closed: 'Closed',
        rejected: 'Rejected',
        withdrawn: 'Withdrawn'
      }
      return texts[status] || status
    }

    const getProgressPercentage = (status) => {
      const percentages = {
        submitted: 25,
        under_review: 50,
        in_progress: 75,
        resolved: 100,
        closed: 100,
        rejected: 100,
        withdrawn: 0
      }
      return percentages[status] || 0
    }

    const getProgressBarClass = (status) => {
      if (['resolved', 'closed'].includes(status)) return 'bg-success'
      if (status === 'rejected') return 'bg-danger'
      if (status === 'withdrawn') return 'bg-secondary'
      return 'bg-primary'
    }

    const getUpdateIcon = (type) => {
      const icons = {
        status_change: 'fa-exchange-alt',
        comment: 'fa-comment',
        document: 'fa-paperclip',
        assignment: 'fa-user-plus'
      }
      return icons[type] || 'fa-info-circle'
    }

    const getDocumentIcon = (type) => {
      if (type.includes('pdf')) return 'fa-file-pdf'
      if (type.includes('doc')) return 'fa-file-word'
      if (type.includes('image')) return 'fa-file-image'
      return 'fa-file'
    }

    const formatDate = (dateString) => {
      const date = new Date(dateString)
      return date.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }

    const formatDateTime = (dateString) => {
      const date = new Date(dateString)
      return date.toLocaleString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
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
      loadComplaints()
    })

    return {
      // Data
      loading,
      error,
      searchQuery,
      selectedStatus,
      selectedPriority,
      currentPage,
      submitting,
      submittingUpdate,
      selectedComplaint,
      updateMessage,
      complaints,
      newComplaint,
      complaintDetailsModal,
      newComplaintModal,
      addUpdateModal,
      
      // Computed
      complaintStats,
      filteredComplaints,
      paginatedComplaints,
      totalPages,
      visiblePages,
      
      // Methods
      loadComplaints,
      filterComplaints,
      resetFilters,
      changePage,
      viewComplaintDetails,
      showNewComplaintModal,
      handleFileUpload,
      submitComplaint,
      addUpdate,
      submitUpdate,
      withdrawComplaint,
      viewDocuments,
      downloadDocument,
      canAddUpdate,
      canWithdraw,
      getStatusIcon,
      getStatusText,
      getProgressPercentage,
      getProgressBarClass,
      getUpdateIcon,
      getDocumentIcon,
      formatDate,
      formatDateTime,
      formatFileSize
    }
  }
}
</script>

<style scoped>
/* Complaints Header */
.complaints-header {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
  padding: 3rem 0;
}

.page-title {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.page-subtitle {
  font-size: 1.1rem;
  opacity: 0.9;
  margin: 0;
}

.header-stats {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.stat-card {
  background: rgba(255, 255, 255, 0.1);
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
  min-width: 80px;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  display: block;
}

.stat-label {
  font-size: 0.8rem;
  opacity: 0.9;
}

/* Complaints Content */
.complaints-content {
  padding: 3rem 0;
  background: #f8f9fa;
  min-height: calc(100vh - 200px);
}

/* Action Bar */
.action-bar {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
}

.search-box .input-group-text {
  background: #f8f9fa;
  border-right: none;
}

.search-box .form-control {
  border-left: none;
}

.action-controls {
  display: flex;
  gap: 0.5rem;
}

.form-select {
  min-width: 150px;
}

/* Loading and Error States */
.loading-state,
.error-state {
  background: white;
  border-radius: 12px;
  padding: 3rem;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 12px;
}

.empty-illustration {
  font-size: 4rem;
  color: #dee2e6;
  margin-bottom: 1.5rem;
}

.empty-state h3 {
  color: #333;
  margin-bottom: 1rem;
}

.empty-state p {
  color: #666;
  margin-bottom: 2rem;
}

/* Complaints List */
.complaints-list {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.complaint-card {
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  transition: all 0.3s ease;
  cursor: pointer;
}

.complaint-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.complaint-card:last-child {
  margin-bottom: 0;
}

/* Complaint Header */
.complaint-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.complaint-id strong {
  color: #333;
  font-size: 1.1rem;
}

.complaint-date {
  display: block;
  color: #666;
  font-size: 0.9rem;
  margin-top: 0.25rem;
}

.complaint-status {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-badge.submitted {
  background: #e3f2fd;
  color: #1976d2;
}

.status-badge.under_review {
  background: #fff3e0;
  color: #f57c00;
}

.status-badge.in_progress {
  background: #f3e5f5;
  color: #7b1fa2;
}

.status-badge.resolved {
  background: #e8f5e8;
  color: #2e7d32;
}

.status-badge.closed {
  background: #fafafa;
  color: #616161;
}

.status-badge.rejected {
  background: #ffebee;
  color: #c62828;
}

.status-badge.withdrawn {
  background: #f5f5f5;
  color: #757575;
}

.priority-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 700;
}

.priority-badge.low {
  background: #e8f5e8;
  color: #2e7d32;
}

.priority-badge.medium {
  background: #fff3e0;
  color: #f57c00;
}

.priority-badge.high {
  background: #ffebee;
  color: #c62828;
}

.priority-badge.urgent {
  background: #c62828;
  color: white;
}

/* Complaint Content */
.complaint-content {
  margin-bottom: 1rem;
}

.complaint-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #333;
  margin-bottom: 0.75rem;
  line-height: 1.3;
}

.complaint-description {
  color: #666;
  margin-bottom: 1rem;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.complaint-meta {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: #666;
}

.meta-item i {
  color: #dc3545;
  width: 16px;
}

/* Progress Bar */
.complaint-progress {
  margin-bottom: 1rem;
}

.progress-info {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  color: #666;
}

.progress {
  height: 6px;
  background: #e9ecef;
  border-radius: 3px;
}

.progress-bar {
  height: 100%;
  border-radius: 3px;
  transition: width 0.3s ease;
}

/* Complaint Updates */
.complaint-updates {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 6px;
  margin-bottom: 1rem;
}

.update-item {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
}

.update-item i {
  color: #dc3545;
  margin-top: 0.25rem;
}

.update-content strong {
  display: block;
  color: #333;
  margin-bottom: 0.25rem;
}

.update-content p {
  margin: 0 0 0.25rem 0;
  color: #666;
  font-size: 0.9rem;
}

.update-content small {
  color: #999;
  font-size: 0.8rem;
}

/* Complaint Actions */
.complaint-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.complaint-actions .btn {
  font-size: 0.85rem;
}

/* Pagination */
.pagination-container {
  margin-top: 3rem;
}

.pagination .page-link {
  color: #dc3545;
  border-color: #dc3545;
}

.pagination .page-item.active .page-link {
  background-color: #dc3545;
  border-color: #dc3545;
}

.pagination .page-link:hover {
  color: #c82333;
  background-color: #f8f9fa;
  border-color: #c82333;
}

/* Modal Styles */
.complaint-details-content {
  max-height: 70vh;
  overflow-y: auto;
}

.details-section {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e9ecef;
}

.details-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.details-section h6 {
  color: #333;
  font-weight: 600;
  margin-bottom: 1rem;
  font-size: 1.1rem;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.info-item strong {
  color: #333;
  font-size: 0.9rem;
}

.info-item span {
  color: #666;
}

.description-content {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 6px;
  line-height: 1.6;
  color: #333;
}

/* Documents Grid */
.documents-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
}

.document-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 6px;
  border: 1px solid #e9ecef;
}

.document-icon {
  font-size: 1.5rem;
  color: #dc3545;
}

.document-info {
  flex: 1;
}

.document-info strong {
  display: block;
  color: #333;
  margin-bottom: 0.25rem;
}

.document-info small {
  color: #666;
}

/* Status Timeline */
.status-timeline {
  position: relative;
}

.timeline-item {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  position: relative;
}

.timeline-item:not(:last-child)::after {
  content: '';
  position: absolute;
  left: 15px;
  top: 40px;
  bottom: -1.5rem;
  width: 2px;
  background: #e9ecef;
}

.timeline-marker {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.8rem;
  flex-shrink: 0;
  background: #dc3545;
}

.timeline-marker.status_change {
  background: #007bff;
}

.timeline-marker.comment {
  background: #28a745;
}

.timeline-marker.document {
  background: #ffc107;
  color: #333;
}

.timeline-marker.assignment {
  background: #6f42c1;
}

.timeline-content {
  flex: 1;
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 6px;
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.timeline-header strong {
  color: #333;
}

.timeline-date {
  color: #666;
  font-size: 0.85rem;
}

.timeline-message {
  color: #666;
  margin: 0 0 0.5rem 0;
  line-height: 1.5;
}

.timeline-author {
  color: #999;
  font-size: 0.8rem;
}

/* Officer Info */
.officer-info {
  display: flex;
  gap: 1rem;
  align-items: center;
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 6px;
}

.officer-avatar img {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  object-fit: cover;
}

.officer-details strong {
  display: block;
  color: #333;
  margin-bottom: 0.25rem;
}

.officer-details p {
  margin: 0 0 0.5rem 0;
  color: #666;
}

.contact-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.contact-info span {
  font-size: 0.85rem;
  color: #666;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.contact-info i {
  color: #dc3545;
  width: 14px;
}

/* Responsive Design */
@media (max-width: 992px) {
  .header-stats {
    justify-content: center;
    margin-top: 2rem;
  }
  
  .action-controls {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .form-select {
    min-width: auto;
  }
  
  .complaint-header {
    flex-direction: column;
    gap: 1rem;
  }
  
  .complaint-status {
    justify-content: flex-start;
  }
}

@media (max-width: 768px) {
  .complaints-header {
    padding: 2rem 0;
  }
  
  .page-title {
    font-size: 2rem;
  }
  
  .complaints-content {
    padding: 2rem 0;
  }
  
  .action-bar {
    padding: 1rem;
  }
  
  .action-bar .row {
    flex-direction: column;
    gap: 1rem;
  }
  
  .complaints-list {
    padding: 1rem;
  }
  
  .complaint-meta {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .complaint-actions {
    flex-direction: column;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .documents-grid {
    grid-template-columns: 1fr;
  }
  
  .timeline-item {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .timeline-item::after {
    display: none;
  }
  
  .timeline-header {
    flex-direction: column;
    gap: 0.25rem;
  }
  
  .officer-info {
    flex-direction: column;
    text-align: center;
  }
}

@media (max-width: 480px) {
  .header-stats {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .stat-card {
    min-width: auto;
  }
  
  .complaint-card {
    padding: 1rem;
  }
  
  .complaint-actions .btn {
    font-size: 0.8rem;
  }
}
</style>