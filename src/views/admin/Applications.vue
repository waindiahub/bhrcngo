<template>
  <div class="applications-page">
    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
      <p>Loading applications...</p>
    </div>

    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <i class="fas fa-user-plus"></i>
            Membership Applications
          </h1>
          <p class="page-subtitle">Manage and review membership applications</p>
        </div>
        <div class="header-actions">
          <button @click="fetchApplications" class="btn btn-secondary" :disabled="loading">
            <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
            Refresh
          </button>
          <button @click="exportApplications" class="btn btn-success">
            <i class="fas fa-download"></i>
            Export
          </button>
        </div>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon total">
          <i class="fas fa-clipboard-list"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.total || 0 }}</h3>
          <p>Total Applications</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon pending">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.pending || 0 }}</h3>
          <p>Pending Review</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon approved">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.approved || 0 }}</h3>
          <p>Approved</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon rejected">
          <i class="fas fa-times-circle"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.rejected || 0 }}</h3>
          <p>Rejected</p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
      <div class="filters-row">
        <div class="filter-group">
          <label>Status:</label>
          <select v-model="filters.status" @change="fetchApplications">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="under_review">Under Review</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Membership Type:</label>
          <select v-model="filters.membership_type" @change="fetchApplications">
            <option value="">All Types</option>
            <option value="individual">Individual</option>
            <option value="family">Family</option>
            <option value="corporate">Corporate</option>
            <option value="student">Student</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Sort by:</label>
          <select v-model="filters.sort" @change="fetchApplications">
            <option value="created_at">Date Applied</option>
            <option value="full_name">Name</option>
            <option value="status">Status</option>
            <option value="membership_type">Type</option>
          </select>
        </div>
        <div class="filter-group">
          <label>Order:</label>
          <select v-model="filters.order" @change="fetchApplications">
            <option value="desc">Descending</option>
            <option value="asc">Ascending</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Search -->
    <div class="search-section">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input
          type="text"
          v-model="searchQuery"
          @input="debounceSearch"
          placeholder="Search by name, email, phone, or reference number..."
          class="search-input"
        />
        <button v-if="searchQuery" @click="clearSearch" class="clear-search">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>

    <!-- Bulk Actions -->
    <div v-if="selectedApplications.length > 0" class="bulk-actions">
      <div class="bulk-info">
        <span>{{ selectedApplications.length }} application(s) selected</span>
      </div>
      <div class="bulk-buttons">
        <button @click="bulkApprove" class="btn btn-success btn-sm">
          <i class="fas fa-check"></i>
          Approve Selected
        </button>
        <button @click="bulkReject" class="btn btn-danger btn-sm">
          <i class="fas fa-times"></i>
          Reject Selected
        </button>
        <button @click="bulkDelete" class="btn btn-danger btn-sm">
          <i class="fas fa-trash"></i>
          Delete Selected
        </button>
      </div>
    </div>

    <!-- Applications List -->
    <div class="applications-list">
      <div v-if="applications.length === 0 && !loading" class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>No Applications Found</h3>
        <p>{{ searchQuery ? 'No applications match your search criteria.' : 'No membership applications have been submitted yet.' }}</p>
      </div>

      <div v-else class="applications-grid">
        <div
          v-for="application in applications"
          :key="application.id"
          class="application-card"
          :class="{ 'selected': selectedApplications.includes(application.id) }"
        >
          <div class="card-header">
            <div class="card-select">
              <input
                type="checkbox"
                :value="application.id"
                v-model="selectedApplications"
                class="select-checkbox"
              />
            </div>
            <div class="card-status">
              <span class="status-badge" :class="application.status">
                {{ formatStatus(application.status) }}
              </span>
            </div>
            <div class="card-actions">
              <button @click="viewApplication(application)" class="btn-icon" title="View Details">
                <i class="fas fa-eye"></i>
              </button>
              <button @click="editApplication(application)" class="btn-icon" title="Edit">
                <i class="fas fa-edit"></i>
              </button>
              <div class="dropdown">
                <button class="btn-icon dropdown-toggle" title="More Actions">
                  <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="dropdown-menu">
                  <button v-if="application.status === 'pending'" @click="approveApplication(application.id)" class="dropdown-item">
                    <i class="fas fa-check"></i>
                    Approve
                  </button>
                  <button v-if="application.status === 'pending'" @click="rejectApplication(application.id)" class="dropdown-item">
                    <i class="fas fa-times"></i>
                    Reject
                  </button>
                  <button @click="downloadApplication(application.id)" class="dropdown-item">
                    <i class="fas fa-download"></i>
                    Download PDF
                  </button>
                  <button @click="deleteApplication(application.id)" class="dropdown-item danger">
                    <i class="fas fa-trash"></i>
                    Delete
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="card-content">
            <div class="applicant-info">
              <h3 class="applicant-name">{{ application.full_name }}</h3>
              <p class="applicant-email">{{ application.email }}</p>
              <p class="applicant-phone">{{ application.phone }}</p>
            </div>

            <div class="application-details">
              <div class="detail-row">
                <span class="label">Reference:</span>
                <span class="value">{{ application.reference_number }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Type:</span>
                <span class="value">{{ formatMembershipType(application.membership_type) }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Applied:</span>
                <span class="value">{{ formatDate(application.created_at) }}</span>
              </div>
              <div v-if="application.reviewed_at" class="detail-row">
                <span class="label">Reviewed:</span>
                <span class="value">{{ formatDate(application.reviewed_at) }}</span>
              </div>
            </div>

            <div v-if="application.notes" class="application-notes">
              <p><strong>Notes:</strong> {{ application.notes }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="pagination">
      <button
        @click="changePage(currentPage - 1)"
        :disabled="currentPage === 1"
        class="btn btn-secondary btn-sm"
      >
        <i class="fas fa-chevron-left"></i>
        Previous
      </button>

      <div class="page-numbers">
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="changePage(page)"
          :class="['btn', 'btn-sm', page === currentPage ? 'btn-primary' : 'btn-secondary']"
        >
          {{ page }}
        </button>
      </div>

      <button
        @click="changePage(currentPage + 1)"
        :disabled="currentPage === totalPages"
        class="btn btn-secondary btn-sm"
      >
        Next
        <i class="fas fa-chevron-right"></i>
      </button>

      <div class="pagination-info">
        Showing {{ ((currentPage - 1) * perPage) + 1 }} to {{ Math.min(currentPage * perPage, totalApplications) }} of {{ totalApplications }} applications
      </div>
    </div>

    <!-- View Application Modal -->
    <div v-if="showViewModal" class="modal-overlay" @click="closeViewModal">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h2>Application Details</h2>
          <button @click="closeViewModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div v-if="selectedApplication" class="application-details-full">
            <div class="detail-section">
              <h3>Personal Information</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <label>Full Name:</label>
                  <span>{{ selectedApplication.full_name }}</span>
                </div>
                <div class="detail-item">
                  <label>Email:</label>
                  <span>{{ selectedApplication.email }}</span>
                </div>
                <div class="detail-item">
                  <label>Phone:</label>
                  <span>{{ selectedApplication.phone }}</span>
                </div>
                <div class="detail-item">
                  <label>Date of Birth:</label>
                  <span>{{ formatDate(selectedApplication.date_of_birth) }}</span>
                </div>
                <div class="detail-item">
                  <label>Address:</label>
                  <span>{{ selectedApplication.address }}</span>
                </div>
                <div class="detail-item">
                  <label>Occupation:</label>
                  <span>{{ selectedApplication.occupation }}</span>
                </div>
              </div>
            </div>

            <div class="detail-section">
              <h3>Membership Information</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <label>Membership Type:</label>
                  <span>{{ formatMembershipType(selectedApplication.membership_type) }}</span>
                </div>
                <div class="detail-item">
                  <label>Reference Number:</label>
                  <span>{{ selectedApplication.reference_number }}</span>
                </div>
                <div class="detail-item">
                  <label>Status:</label>
                  <span class="status-badge" :class="selectedApplication.status">
                    {{ formatStatus(selectedApplication.status) }}
                  </span>
                </div>
                <div class="detail-item">
                  <label>Applied Date:</label>
                  <span>{{ formatDate(selectedApplication.created_at) }}</span>
                </div>
              </div>
            </div>

            <div v-if="selectedApplication.emergency_contact_name" class="detail-section">
              <h3>Emergency Contact</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <label>Name:</label>
                  <span>{{ selectedApplication.emergency_contact_name }}</span>
                </div>
                <div class="detail-item">
                  <label>Phone:</label>
                  <span>{{ selectedApplication.emergency_contact_phone }}</span>
                </div>
                <div class="detail-item">
                  <label>Relationship:</label>
                  <span>{{ selectedApplication.emergency_contact_relationship }}</span>
                </div>
              </div>
            </div>

            <div v-if="selectedApplication.notes" class="detail-section">
              <h3>Additional Notes</h3>
              <p>{{ selectedApplication.notes }}</p>
            </div>

            <div v-if="selectedApplication.reviewed_by" class="detail-section">
              <h3>Review Information</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <label>Reviewed By:</label>
                  <span>{{ selectedApplication.reviewed_by }}</span>
                </div>
                <div class="detail-item">
                  <label>Reviewed Date:</label>
                  <span>{{ formatDate(selectedApplication.reviewed_at) }}</span>
                </div>
                <div v-if="selectedApplication.review_notes" class="detail-item full-width">
                  <label>Review Notes:</label>
                  <span>{{ selectedApplication.review_notes }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeViewModal" class="btn btn-secondary">Close</button>
          <button v-if="selectedApplication && selectedApplication.status === 'pending'" @click="approveApplication(selectedApplication.id)" class="btn btn-success">
            <i class="fas fa-check"></i>
            Approve
          </button>
          <button v-if="selectedApplication && selectedApplication.status === 'pending'" @click="rejectApplication(selectedApplication.id)" class="btn btn-danger">
            <i class="fas fa-times"></i>
            Reject
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Application Modal -->
    <div v-if="showEditModal" class="modal-overlay" @click="closeEditModal">
      <div class="modal modal-large" @click.stop>
        <div class="modal-header">
          <h2>{{ editingApplication.id ? 'Edit Application' : 'New Application' }}</h2>
          <button @click="closeEditModal" class="modal-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveApplication" class="application-form">
            <div class="form-section">
              <h3>Personal Information</h3>
              <div class="form-grid">
                <div class="form-group">
                  <label for="full_name">Full Name *</label>
                  <input
                    type="text"
                    id="full_name"
                    v-model="editingApplication.full_name"
                    required
                    class="form-control"
                  />
                </div>
                <div class="form-group">
                  <label for="email">Email *</label>
                  <input
                    type="email"
                    id="email"
                    v-model="editingApplication.email"
                    required
                    class="form-control"
                  />
                </div>
                <div class="form-group">
                  <label for="phone">Phone *</label>
                  <input
                    type="tel"
                    id="phone"
                    v-model="editingApplication.phone"
                    required
                    class="form-control"
                  />
                </div>
                <div class="form-group">
                  <label for="date_of_birth">Date of Birth</label>
                  <input
                    type="date"
                    id="date_of_birth"
                    v-model="editingApplication.date_of_birth"
                    class="form-control"
                  />
                </div>
                <div class="form-group full-width">
                  <label for="address">Address</label>
                  <textarea
                    id="address"
                    v-model="editingApplication.address"
                    rows="3"
                    class="form-control"
                  ></textarea>
                </div>
                <div class="form-group">
                  <label for="occupation">Occupation</label>
                  <input
                    type="text"
                    id="occupation"
                    v-model="editingApplication.occupation"
                    class="form-control"
                  />
                </div>
              </div>
            </div>

            <div class="form-section">
              <h3>Membership Information</h3>
              <div class="form-grid">
                <div class="form-group">
                  <label for="membership_type">Membership Type *</label>
                  <select
                    id="membership_type"
                    v-model="editingApplication.membership_type"
                    required
                    class="form-control"
                  >
                    <option value="">Select Type</option>
                    <option value="individual">Individual</option>
                    <option value="family">Family</option>
                    <option value="corporate">Corporate</option>
                    <option value="student">Student</option>
                  </select>
                </div>
                <div class="form-group">
                  <label for="status">Status</label>
                  <select
                    id="status"
                    v-model="editingApplication.status"
                    class="form-control"
                  >
                    <option value="pending">Pending</option>
                    <option value="under_review">Under Review</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="form-section">
              <h3>Emergency Contact</h3>
              <div class="form-grid">
                <div class="form-group">
                  <label for="emergency_contact_name">Contact Name</label>
                  <input
                    type="text"
                    id="emergency_contact_name"
                    v-model="editingApplication.emergency_contact_name"
                    class="form-control"
                  />
                </div>
                <div class="form-group">
                  <label for="emergency_contact_phone">Contact Phone</label>
                  <input
                    type="tel"
                    id="emergency_contact_phone"
                    v-model="editingApplication.emergency_contact_phone"
                    class="form-control"
                  />
                </div>
                <div class="form-group">
                  <label for="emergency_contact_relationship">Relationship</label>
                  <input
                    type="text"
                    id="emergency_contact_relationship"
                    v-model="editingApplication.emergency_contact_relationship"
                    class="form-control"
                  />
                </div>
              </div>
            </div>

            <div class="form-section">
              <h3>Additional Information</h3>
              <div class="form-group">
                <label for="notes">Notes</label>
                <textarea
                  id="notes"
                  v-model="editingApplication.notes"
                  rows="4"
                  class="form-control"
                  placeholder="Any additional notes or comments..."
                ></textarea>
              </div>
              <div class="form-group">
                <label for="review_notes">Review Notes</label>
                <textarea
                  id="review_notes"
                  v-model="editingApplication.review_notes"
                  rows="3"
                  class="form-control"
                  placeholder="Internal review notes..."
                ></textarea>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button @click="closeEditModal" type="button" class="btn btn-secondary">Cancel</button>
          <button @click="saveApplication" type="submit" class="btn btn-primary" :disabled="saving">
            <i class="fas fa-save"></i>
            {{ saving ? 'Saving...' : 'Save Application' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Success Toast -->
    <div v-if="showToast" class="toast toast-success">
      <i class="fas fa-check-circle"></i>
      <span>{{ toastMessage }}</span>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue'
import { api } from '@/utils/api'

export default {
  name: 'Applications',
  setup() {
    // Reactive data
    const loading = ref(false)
    const saving = ref(false)
    const applications = ref([])
    const stats = reactive({
      total: 0,
      pending: 0,
      approved: 0,
      rejected: 0
    })

    // Filters and search
    const filters = reactive({
      status: '',
      membership_type: '',
      sort: 'created_at',
      order: 'desc'
    })
    const searchQuery = ref('')
    const searchTimeout = ref(null)

    // Pagination
    const currentPage = ref(1)
    const perPage = ref(12)
    const totalApplications = ref(0)

    // Selection
    const selectedApplications = ref([])

    // Modals
    const showViewModal = ref(false)
    const showEditModal = ref(false)
    const selectedApplication = ref(null)
    const editingApplication = reactive({
      id: null,
      full_name: '',
      email: '',
      phone: '',
      date_of_birth: '',
      address: '',
      occupation: '',
      membership_type: '',
      status: 'pending',
      emergency_contact_name: '',
      emergency_contact_phone: '',
      emergency_contact_relationship: '',
      notes: '',
      review_notes: ''
    })

    // Toast
    const showToast = ref(false)
    const toastMessage = ref('')

    // Computed properties
    const totalPages = computed(() => Math.ceil(totalApplications.value / perPage.value))
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
        const response = await api.get('/admin/applications/stats')
        if (response.data.success) {
          Object.assign(stats, response.data.stats)
        }
      } catch (error) {
        console.error('Error fetching stats:', error)
      }
    }

    const fetchApplications = async () => {
      loading.value = true
      try {
        const params = {
          page: currentPage.value,
          per_page: perPage.value,
          search: searchQuery.value,
          ...filters
        }

        const response = await api.get('/admin/applications/applications', { params })
        
        if (response.data.success) {
          applications.value = response.data.applications || []
          totalApplications.value = response.data.total || 0
        }
      } catch (error) {
        console.error('Error fetching applications:', error)
        applications.value = []
        totalApplications.value = 0
      } finally {
        loading.value = false
      }
    }

    const debounceSearch = () => {
      if (searchTimeout.value) {
        clearTimeout(searchTimeout.value)
      }
      searchTimeout.value = setTimeout(() => {
        currentPage.value = 1
        fetchApplications()
      }, 500)
    }

    const clearSearch = () => {
      searchQuery.value = ''
      currentPage.value = 1
      fetchApplications()
    }

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
        fetchApplications()
      }
    }

    const viewApplication = (application) => {
      selectedApplication.value = application
      showViewModal.value = true
    }

    const closeViewModal = () => {
      showViewModal.value = false
      selectedApplication.value = null
    }

    const editApplication = (application = null) => {
      if (application) {
        Object.assign(editingApplication, application)
      } else {
        Object.assign(editingApplication, {
          id: null,
          full_name: '',
          email: '',
          phone: '',
          date_of_birth: '',
          address: '',
          occupation: '',
          membership_type: '',
          status: 'pending',
          emergency_contact_name: '',
          emergency_contact_phone: '',
          emergency_contact_relationship: '',
          notes: '',
          review_notes: ''
        })
      }
      showEditModal.value = true
    }

    const closeEditModal = () => {
      showEditModal.value = false
    }

    const saveApplication = async () => {
      saving.value = true
      try {
        let response
        if (editingApplication.id) {
          response = await api.put(`/admin/applications/applications/${editingApplication.id}`, editingApplication)
        } else {
          response = await api.post('/admin/applications/applications', editingApplication)
        }

        if (response.data.success) {
          showToast.value = true
          toastMessage.value = editingApplication.id ? 'Application updated successfully!' : 'Application created successfully!'
          setTimeout(() => { showToast.value = false }, 3000)
          
          closeEditModal()
          fetchApplications()
          fetchStats()
        }
      } catch (error) {
        console.error('Error saving application:', error)
      } finally {
        saving.value = false
      }
    }

    const approveApplication = async (id) => {
      try {
        const response = await api.put(`/admin/applications/applications/${id}`, { status: 'approved' })

        if (response.data.success) {
          showToast.value = true
          toastMessage.value = 'Application approved successfully!'
          setTimeout(() => { showToast.value = false }, 3000)
          
          fetchApplications()
          fetchStats()
          closeViewModal()
        }
      } catch (error) {
        console.error('Error approving application:', error)
      }
    }

    const rejectApplication = async (id) => {
      try {
        const response = await api.put(`/admin/applications/applications/${id}`, { status: 'rejected' })

        if (response.data.success) {
          showToast.value = true
          toastMessage.value = 'Application rejected successfully!'
          setTimeout(() => { showToast.value = false }, 3000)
          
          fetchApplications()
          fetchStats()
          closeViewModal()
        }
      } catch (error) {
        console.error('Error rejecting application:', error)
      }
    }

    const deleteApplication = async (id) => {
      if (!confirm('Are you sure you want to delete this application? This action cannot be undone.')) {
        return
      }

      try {
        const response = await api.delete(`/admin/applications/applications/${id}`)

        if (response.data.success) {
          showToast.value = true
          toastMessage.value = 'Application deleted successfully!'
          setTimeout(() => { showToast.value = false }, 3000)
          
          fetchApplications()
          fetchStats()
        }
      } catch (error) {
        console.error('Error deleting application:', error)
      }
    }

    const bulkApprove = async () => {
      if (!confirm(`Are you sure you want to approve ${selectedApplications.value.length} application(s)?`)) {
        return
      }

      try {
        const response = await api.put('/admin/applications/applications/bulk', {
          ids: selectedApplications.value,
          status: 'approved'
        })

        if (response.data.success) {
          showToast.value = true
          toastMessage.value = `${selectedApplications.value.length} application(s) approved successfully!`
          setTimeout(() => { showToast.value = false }, 3000)
          
          selectedApplications.value = []
          fetchApplications()
          fetchStats()
        }
      } catch (error) {
        console.error('Error bulk approving applications:', error)
      }
    }

    const bulkReject = async () => {
      if (!confirm(`Are you sure you want to reject ${selectedApplications.value.length} application(s)?`)) {
        return
      }

      try {
        const response = await api.put('/admin/applications/applications/bulk', {
          ids: selectedApplications.value,
          status: 'rejected'
        })

        if (response.data.success) {
          showToast.value = true
          toastMessage.value = `${selectedApplications.value.length} application(s) rejected successfully!`
          setTimeout(() => { showToast.value = false }, 3000)
          
          selectedApplications.value = []
          fetchApplications()
          fetchStats()
        }
      } catch (error) {
        console.error('Error bulk rejecting applications:', error)
      }
    }

    const bulkDelete = async () => {
      if (!confirm(`Are you sure you want to delete ${selectedApplications.value.length} application(s)? This action cannot be undone.`)) {
        return
      }

      try {
        const response = await api.delete('/admin/applications/applications/bulk', {
          data: { ids: selectedApplications.value }
        })

        if (response.data.success) {
          showToast.value = true
          toastMessage.value = `${selectedApplications.value.length} application(s) deleted successfully!`
          setTimeout(() => { showToast.value = false }, 3000)
          
          selectedApplications.value = []
          fetchApplications()
          fetchStats()
        }
      } catch (error) {
        console.error('Error bulk deleting applications:', error)
      }
    }

    const exportApplications = async () => {
      try {
        const params = {
          export: 'csv',
          search: searchQuery.value,
          ...filters
        }

        const response = await api.get('/admin/applications/export', { 
          params,
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(response.data)
        const a = document.createElement('a')
        a.href = url
        a.download = `applications-${new Date().toISOString().split('T')[0]}.csv`
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        window.URL.revokeObjectURL(url)
      } catch (error) {
        console.error('Error exporting applications:', error)
      }
    }

    const downloadApplication = async (id) => {
      try {
        const response = await api.get(`/admin/applications/download/${id}`, {
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(response.data)
        const a = document.createElement('a')
        a.href = url
        a.download = `application-${id}.pdf`
        document.body.appendChild(a)
        a.click()
        document.body.removeChild(a)
        window.URL.revokeObjectURL(url)
      } catch (error) {
        console.error('Error downloading application:', error)
      }
    }

    // Utility functions
    const formatStatus = (status) => {
      const statusMap = {
        pending: 'Pending',
        under_review: 'Under Review',
        approved: 'Approved',
        rejected: 'Rejected'
      }
      return statusMap[status] || status
    }

    const formatMembershipType = (type) => {
      const typeMap = {
        individual: 'Individual',
        family: 'Family',
        corporate: 'Corporate',
        student: 'Student'
      }
      return typeMap[type] || type
    }

    const formatDate = (dateString) => {
      if (!dateString) return ''
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }

    // Lifecycle
    onMounted(() => {
      fetchStats()
      fetchApplications()
    })

    return {
      // Data
      loading,
      saving,
      applications,
      stats,
      filters,
      searchQuery,
      currentPage,
      perPage,
      totalApplications,
      selectedApplications,
      showViewModal,
      showEditModal,
      selectedApplication,
      editingApplication,
      showToast,
      toastMessage,
      
      // Computed
      totalPages,
      visiblePages,
      
      // Methods
      fetchStats,
      fetchApplications,
      debounceSearch,
      clearSearch,
      changePage,
      viewApplication,
      closeViewModal,
      editApplication,
      closeEditModal,
      saveApplication,
      approveApplication,
      rejectApplication,
      deleteApplication,
      bulkApprove,
      bulkReject,
      bulkDelete,
      exportApplications,
      downloadApplication,
      formatStatus,
      formatMembershipType,
      formatDate
    }
  }
}
</script>

<style scoped>
.applications-page {
  padding: 2rem;
  background-color: #f8fafc;
  min-height: 100vh;
}

/* Loading Overlay */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.9);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #e2e8f0;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Page Header */
.page-header {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.header-left h1 {
  margin: 0;
  color: #1e293b;
  font-size: 2rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.header-left h1 i {
  color: #3b82f6;
}

.page-subtitle {
  margin: 0.5rem 0 0 0;
  color: #64748b;
  font-size: 1.1rem;
}

.header-actions {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

/* Statistics Cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
}

.stat-icon.total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.stat-icon.pending { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.stat-icon.approved { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.stat-icon.rejected { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }

.stat-content h3 {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
  color: #1e293b;
}

.stat-content p {
  margin: 0.25rem 0 0 0;
  color: #64748b;
  font-weight: 500;
}

/* Filters */
.filters-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.filters-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  align-items: end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group label {
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
}

/* Search */
.search-section {
  margin-bottom: 1.5rem;
}

.search-box {
  position: relative;
  max-width: 500px;
}

.search-box i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.clear-search {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: color 0.2s;
}

.clear-search:hover {
  color: #6b7280;
}

/* Bulk Actions */
.bulk-actions {
  background: #3b82f6;
  color: white;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.bulk-info {
  font-weight: 600;
}

.bulk-buttons {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

/* Applications List */
.applications-list {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #6b7280;
}

.empty-state i {
  font-size: 4rem;
  margin-bottom: 1rem;
  color: #d1d5db;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
  color: #374151;
}

.applications-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
}

.application-card {
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.2s;
  background: #fafafa;
}

.application-card:hover {
  border-color: #3b82f6;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.application-card.selected {
  border-color: #3b82f6;
  background: #eff6ff;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.card-select input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.status-badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.under_review {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.approved {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.rejected {
  background: #fee2e2;
  color: #991b1b;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.btn-icon {
  background: none;
  border: none;
  padding: 0.5rem;
  border-radius: 6px;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-icon:hover {
  background: #f3f4f6;
  color: #374151;
}

.dropdown {
  position: relative;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  min-width: 150px;
  display: none;
}

.dropdown:hover .dropdown-menu {
  display: block;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.75rem 1rem;
  border: none;
  background: none;
  text-align: left;
  cursor: pointer;
  transition: background-color 0.2s;
  font-size: 0.875rem;
}

.dropdown-item:hover {
  background: #f9fafb;
}

.dropdown-item.danger {
  color: #dc2626;
}

.dropdown-item.danger:hover {
  background: #fef2f2;
}

.card-content {
  margin-bottom: 1rem;
}

.applicant-info {
  margin-bottom: 1rem;
}

.applicant-name {
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
}

.applicant-email,
.applicant-phone {
  margin: 0.25rem 0;
  color: #64748b;
  font-size: 0.875rem;
}

.application-details {
  margin-bottom: 1rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.detail-row .label {
  font-weight: 600;
  color: #374151;
}

.detail-row .value {
  color: #6b7280;
}

.application-notes {
  background: #f8fafc;
  padding: 1rem;
  border-radius: 8px;
  border-left: 4px solid #3b82f6;
  font-size: 0.875rem;
  color: #64748b;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 2rem;
  flex-wrap: wrap;
}

.page-numbers {
  display: flex;
  gap: 0.5rem;
}

.pagination-info {
  color: #6b7280;
  font-size: 0.875rem;
  margin-left: 1rem;
}

/* Buttons */
.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  font-size: 0.875rem;
}

.btn:disabled {
  opacity: 0.5;
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
  background: #6b7280;
  color: white;
}

.btn-secondary:hover:not(:disabled) {
  background: #4b5563;
}

.btn-success {
  background: #10b981;
  color: white;
}

.btn-success:hover:not(:disabled) {
  background: #059669;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626;
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
}

/* Form Controls */
.form-control,
select {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  font-size: 1rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-control:focus,
select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Modals */
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
  z-index: 10000;
  padding: 1rem;
}

.modal {
  background: white;
  border-radius: 12px;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-large {
  max-width: 900px;
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
  color: #1e293b;
  font-size: 1.5rem;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6b7280;
  padding: 0.25rem;
  border-radius: 4px;
  transition: color 0.2s;
}

.modal-close:hover {
  color: #374151;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}

.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

/* Application Details */
.application-details-full {
  margin-bottom: 2rem;
}

.detail-section {
  margin-bottom: 2rem;
}

.detail-section h3 {
  margin: 0 0 1rem 0;
  color: #1e293b;
  font-size: 1.25rem;
  font-weight: 600;
  border-bottom: 2px solid #e5e7eb;
  padding-bottom: 0.5rem;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-item.full-width {
  grid-column: 1 / -1;
}

.detail-item label {
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
}

.detail-item span {
  color: #6b7280;
}

/* Form */
.application-form {
  margin-bottom: 2rem;
}

.form-section {
  margin-bottom: 2rem;
}

.form-section h3 {
  margin: 0 0 1rem 0;
  color: #1e293b;
  font-size: 1.25rem;
  font-weight: 600;
  border-bottom: 2px solid #e5e7eb;
  padding-bottom: 0.5rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
}

/* Toast */
.toast {
  position: fixed;
  top: 2rem;
  right: 2rem;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  color: white;
  font-weight: 600;
  z-index: 10001;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  animation: slideIn 0.3s ease-out;
}

.toast-success {
  background: #10b981;
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .applications-page {
    padding: 1rem;
  }
  
  .header-content {
    flex-direction: column;
    align-items: stretch;
  }
  
  .header-actions {
    justify-content: center;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .filters-row {
    grid-template-columns: 1fr;
  }
  
  .applications-grid {
    grid-template-columns: 1fr;
  }
  
  .pagination {
    flex-direction: column;
    gap: 1rem;
  }
  
  .pagination-info {
    margin-left: 0;
    text-align: center;
  }
  
  .modal {
    margin: 1rem;
    max-width: none;
  }
  
  .detail-grid,
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .bulk-actions {
    flex-direction: column;
    text-align: center;
  }
}

@media (max-width: 480px) {
  .applications-page {
    padding: 0.5rem;
  }
  
  .page-header {
    padding: 1rem;
  }
  
  .header-left h1 {
    font-size: 1.5rem;
  }
  
  .stat-card {
    padding: 1rem;
  }
  
  .stat-icon {
    width: 50px;
    height: 50px;
    font-size: 1.25rem;
  }
  
  .stat-content h3 {
    font-size: 1.5rem;
  }
  
  .application-card {
    padding: 1rem;
  }
  
  .modal-overlay {
    padding: 0.5rem;
  }
  
  .modal-header,
  .modal-body,
  .modal-footer {
    padding: 1rem;
  }
}

/* Print Styles */
@media print {
  .applications-page {
    background: white;
    padding: 0;
  }
  
  .page-header,
  .filters-section,
  .search-section,
  .bulk-actions,
  .pagination,
  .header-actions,
  .card-actions {
    display: none;
  }
  
  .applications-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .application-card {
    border: 1px solid #000;
    break-inside: avoid;
    margin-bottom: 1rem;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .applications-page {
    background-color: #0f172a;
    color: #e2e8f0;
  }
  
  .page-header,
  .filters-section,
  .applications-list,
  .stat-card,
  .application-card {
    background: #1e293b;
    border-color: #334155;
  }
  
  .page-title,
  .applicant-name {
    color: #f1f5f9;
  }
  
  .page-subtitle,
  .applicant-email,
  .applicant-phone,
  .detail-row .value {
    color: #94a3b8;
  }
  
  .form-control,
  select {
    background: #334155;
    border-color: #475569;
    color: #e2e8f0;
  }
  
  .modal {
    background: #1e293b;
  }
  
  .modal-header,
  .modal-footer {
    border-color: #334155;
  }
}
</style>