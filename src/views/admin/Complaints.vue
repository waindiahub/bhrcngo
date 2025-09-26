<template>
  <div class="admin-complaints">
    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
      <p>Loading complaints...</p>
    </div>

    <!-- Complaints Header -->
    <div class="complaints-header">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h1>
              <i class="fas fa-exclamation-triangle"></i>
              Complaint Management
            </h1>
            <p>Manage and resolve member complaints efficiently</p>
          </div>
          <div class="col-md-4">
            <div class="header-actions">
              <button 
                class="btn btn-primary" 
                @click="refreshData"
                :disabled="loading"
              >
                <i class="fas fa-sync-alt"></i>
                Refresh
              </button>
              <button 
                class="btn btn-outline-primary" 
                @click="exportComplaints"
                :disabled="loading"
              >
                <i class="fas fa-download"></i>
                Export
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="container">
      <!-- Statistics Section -->
      <div class="stats-section">
        <div class="row">
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card total">
              <div class="card-body">
                <div class="stats-icon">
                  <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stats-content">
                  <h3>{{ stats.total || 0 }}</h3>
                  <p>Total Complaints</p>
                  <div class="stats-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +{{ stats.totalChange || 0 }}% this month
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card pending">
              <div class="card-body">
                <div class="stats-icon">
                  <i class="fas fa-clock"></i>
                </div>
                <div class="stats-content">
                  <h3>{{ stats.pending || 0 }}</h3>
                  <p>Pending Review</p>
                  <div class="stats-change negative">
                    <i class="fas fa-arrow-down"></i>
                    {{ stats.pendingChange || 0 }}% from last week
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card investigating">
              <div class="card-body">
                <div class="stats-icon">
                  <i class="fas fa-search"></i>
                </div>
                <div class="stats-content">
                  <h3>{{ stats.investigating || 0 }}</h3>
                  <p>Under Investigation</p>
                  <div class="stats-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +{{ stats.investigatingChange || 0 }}% this week
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card resolved">
              <div class="card-body">
                <div class="stats-icon">
                  <i class="fas fa-check-circle"></i>
                </div>
                <div class="stats-content">
                  <h3>{{ stats.resolved || 0 }}</h3>
                  <p>Resolved</p>
                  <div class="stats-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +{{ stats.resolvedChange || 0 }}% this month
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters Section -->
      <div class="filters-section">
        <div class="card">
          <div class="card-body">
            <div class="row align-items-end">
              <div class="col-md-3 mb-3">
                <label class="form-label">Search Complaints</label>
                <div class="search-input">
                  <i class="fas fa-search"></i>
                  <input 
                    type="text" 
                    class="form-control" 
                    placeholder="Search by ID, member, or subject..."
                    v-model="filters.search"
                    @input="applyFilters"
                  >
                </div>
              </div>
              <div class="col-md-2 mb-3">
                <label class="form-label">Status</label>
                <select 
                  class="form-select" 
                  v-model="filters.status"
                  @change="applyFilters"
                >
                  <option value="">All Status</option>
                  <option value="pending">Pending</option>
                  <option value="investigating">Investigating</option>
                  <option value="resolved">Resolved</option>
                  <option value="closed">Closed</option>
                  <option value="rejected">Rejected</option>
                </select>
              </div>
              <div class="col-md-2 mb-3">
                <label class="form-label">Priority</label>
                <select 
                  class="form-select" 
                  v-model="filters.priority"
                  @change="applyFilters"
                >
                  <option value="">All Priorities</option>
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
              <div class="col-md-2 mb-3">
                <label class="form-label">Category</label>
                <select 
                  class="form-select" 
                  v-model="filters.category"
                  @change="applyFilters"
                >
                  <option value="">All Categories</option>
                  <option value="service">Service</option>
                  <option value="billing">Billing</option>
                  <option value="technical">Technical</option>
                  <option value="general">General</option>
                  <option value="other">Other</option>
                </select>
              </div>
              <div class="col-md-2 mb-3">
                <label class="form-label">Date Range</label>
                <select 
                  class="form-select" 
                  v-model="filters.dateRange"
                  @change="applyFilters"
                >
                  <option value="">All Time</option>
                  <option value="today">Today</option>
                  <option value="week">This Week</option>
                  <option value="month">This Month</option>
                  <option value="quarter">This Quarter</option>
                </select>
              </div>
              <div class="col-md-1 mb-3">
                <button 
                  class="btn btn-outline-secondary w-100" 
                  @click="clearFilters"
                  title="Clear Filters"
                >
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Complaints Section -->
      <div class="complaints-section">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5>
              <i class="fas fa-list"></i>
              Complaints ({{ filteredComplaints.length }})
            </h5>
            <div class="view-actions">
              <div class="btn-group" role="group">
                <button 
                  type="button" 
                  class="btn btn-sm"
                  :class="viewMode === 'list' ? 'btn-primary' : 'btn-outline-primary'"
                  @click="viewMode = 'list'"
                >
                  <i class="fas fa-list"></i>
                </button>
                <button 
                  type="button" 
                  class="btn btn-sm"
                  :class="viewMode === 'grid' ? 'btn-primary' : 'btn-outline-primary'"
                  @click="viewMode = 'grid'"
                >
                  <i class="fas fa-th"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- List View -->
          <div v-if="viewMode === 'list'" class="table-responsive">
            <table class="table table-hover mb-0" v-if="filteredComplaints.length > 0">
              <thead>
                <tr>
                  <th>
                    <input 
                      type="checkbox" 
                      class="form-check-input"
                      @change="toggleSelectAll"
                      :checked="selectedComplaints.length === filteredComplaints.length"
                    >
                  </th>
                  <th>Complaint ID</th>
                  <th>Member</th>
                  <th>Subject</th>
                  <th>Category</th>
                  <th>Priority</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="complaint in paginatedComplaints" :key="complaint.id">
                  <td>
                    <input 
                      type="checkbox" 
                      class="form-check-input"
                      :value="complaint.id"
                      v-model="selectedComplaints"
                    >
                  </td>
                  <td>
                    <div class="complaint-id">
                      <strong>#{{ complaint.id }}</strong>
                    </div>
                  </td>
                  <td>
                    <div class="member-info">
                      <img 
                        :src="complaint.member.avatar || '/default-avatar.png'" 
                        :alt="complaint.member.name"
                        class="member-avatar"
                      >
                      <div>
                        <div class="member-name">{{ complaint.member.name }}</div>
                        <div class="member-id">ID: {{ complaint.member.id }}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="complaint-subject">
                      <strong>{{ complaint.subject }}</strong>
                      <div class="complaint-preview">
                        {{ complaint.description.substring(0, 50) }}...
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="category-badge" :class="complaint.category">
                      {{ complaint.category }}
                    </span>
                  </td>
                  <td>
                    <span class="priority-badge" :class="complaint.priority">
                      <i class="fas fa-exclamation-circle" v-if="complaint.priority === 'urgent'"></i>
                      <i class="fas fa-exclamation-triangle" v-else-if="complaint.priority === 'high'"></i>
                      <i class="fas fa-minus" v-else-if="complaint.priority === 'medium'"></i>
                      <i class="fas fa-arrow-down" v-else></i>
                      {{ complaint.priority }}
                    </span>
                  </td>
                  <td>
                    <span class="status-badge" :class="complaint.status">
                      {{ complaint.status }}
                    </span>
                  </td>
                  <td>
                    <div class="date-info">
                      <div>{{ formatDate(complaint.created_at) }}</div>
                      <small class="text-muted">{{ formatTimeAgo(complaint.created_at) }}</small>
                    </div>
                  </td>
                  <td>
                    <div class="action-buttons">
                      <button 
                        class="btn btn-sm btn-primary" 
                        @click="viewComplaint(complaint)"
                        title="View Details"
                      >
                        <i class="fas fa-eye"></i>
                      </button>
                      <button 
                        class="btn btn-sm btn-success" 
                        @click="assignComplaint(complaint)"
                        title="Assign"
                        v-if="complaint.status === 'pending'"
                      >
                        <i class="fas fa-user-plus"></i>
                      </button>
                      <button 
                        class="btn btn-sm btn-warning" 
                        @click="updateStatus(complaint, 'investigating')"
                        title="Start Investigation"
                        v-if="complaint.status === 'pending'"
                      >
                        <i class="fas fa-search"></i>
                      </button>
                      <button 
                        class="btn btn-sm btn-info" 
                        @click="resolveComplaint(complaint)"
                        title="Resolve"
                        v-if="complaint.status === 'investigating'"
                      >
                        <i class="fas fa-check"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Empty State -->
            <div v-else class="empty-state">
              <i class="fas fa-inbox"></i>
              <p>No complaints found matching your criteria</p>
            </div>
          </div>

          <!-- Grid View -->
          <div v-else class="complaints-grid">
            <div class="row" v-if="filteredComplaints.length > 0">
              <div class="col-lg-4 col-md-6 mb-4" v-for="complaint in paginatedComplaints" :key="complaint.id">
                <div class="complaint-card">
                  <div class="complaint-card-header">
                    <div class="complaint-id">
                      <strong>#{{ complaint.id }}</strong>
                    </div>
                    <input 
                      type="checkbox" 
                      class="form-check-input"
                      :value="complaint.id"
                      v-model="selectedComplaints"
                    >
                  </div>
                  <div class="complaint-card-body">
                    <div class="member-info mb-3">
                      <img 
                        :src="complaint.member.avatar || '/default-avatar.png'" 
                        :alt="complaint.member.name"
                        class="member-avatar"
                      >
                      <div>
                        <div class="member-name">{{ complaint.member.name }}</div>
                        <div class="member-id">ID: {{ complaint.member.id }}</div>
                      </div>
                    </div>
                    <h6 class="complaint-subject">{{ complaint.subject }}</h6>
                    <p class="complaint-description">{{ complaint.description.substring(0, 100) }}...</p>
                    <div class="complaint-meta">
                      <span class="category-badge" :class="complaint.category">
                        {{ complaint.category }}
                      </span>
                      <span class="priority-badge" :class="complaint.priority">
                        {{ complaint.priority }}
                      </span>
                    </div>
                  </div>
                  <div class="complaint-card-footer">
                    <div class="status-info">
                      <span class="status-badge" :class="complaint.status">
                        {{ complaint.status }}
                      </span>
                      <small class="text-muted">{{ formatTimeAgo(complaint.created_at) }}</small>
                    </div>
                    <div class="card-actions">
                      <button 
                        class="btn btn-sm btn-primary" 
                        @click="viewComplaint(complaint)"
                      >
                        <i class="fas fa-eye"></i>
                      </button>
                      <button 
                        class="btn btn-sm btn-success" 
                        @click="assignComplaint(complaint)"
                        v-if="complaint.status === 'pending'"
                      >
                        <i class="fas fa-user-plus"></i>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-else class="empty-state">
              <i class="fas fa-inbox"></i>
              <p>No complaints found matching your criteria</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div class="pagination-section" v-if="filteredComplaints.length > 0">
        <nav>
          <ul class="pagination justify-content-center">
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <a class="page-link" @click="changePage(currentPage - 1)">
                <i class="fas fa-chevron-left"></i>
              </a>
            </li>
            <li 
              class="page-item" 
              :class="{ active: page === currentPage }"
              v-for="page in visiblePages" 
              :key="page"
            >
              <a class="page-link" @click="changePage(page)">{{ page }}</a>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
              <a class="page-link" @click="changePage(currentPage + 1)">
                <i class="fas fa-chevron-right"></i>
              </a>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- Bulk Actions -->
    <div class="bulk-actions" v-if="selectedComplaints.length > 0">
      <div class="bulk-actions-bar">
        <div class="selected-count">
          {{ selectedComplaints.length }} complaint(s) selected
        </div>
        <div class="bulk-buttons">
          <button class="btn btn-warning" @click="bulkAssign">
            <i class="fas fa-user-plus"></i>
            Assign
          </button>
          <button class="btn btn-info" @click="bulkUpdateStatus">
            <i class="fas fa-edit"></i>
            Update Status
          </button>
          <button class="btn btn-success" @click="bulkExport">
            <i class="fas fa-download"></i>
            Export
          </button>
          <button class="btn btn-outline-secondary" @click="clearSelection">
            <i class="fas fa-times"></i>
            Clear
          </button>
        </div>
      </div>
    </div>

    <!-- View Complaint Modal -->
    <div class="modal fade" id="viewComplaintModal" tabindex="-1">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-eye"></i>
              Complaint Details - #{{ selectedComplaint?.id }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" v-if="selectedComplaint">
            <div class="row">
              <div class="col-md-8">
                <div class="complaint-details">
                  <div class="detail-section">
                    <h6>
                      <i class="fas fa-info-circle"></i>
                      Complaint Information
                    </h6>
                    <div class="detail-grid">
                      <div class="detail-item">
                        <label>Subject</label>
                        <span>{{ selectedComplaint.subject }}</span>
                      </div>
                      <div class="detail-item">
                        <label>Category</label>
                        <span class="category-badge" :class="selectedComplaint.category">
                          {{ selectedComplaint.category }}
                        </span>
                      </div>
                      <div class="detail-item">
                        <label>Priority</label>
                        <span class="priority-badge" :class="selectedComplaint.priority">
                          {{ selectedComplaint.priority }}
                        </span>
                      </div>
                      <div class="detail-item">
                        <label>Status</label>
                        <span class="status-badge" :class="selectedComplaint.status">
                          {{ selectedComplaint.status }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <div class="detail-section">
                    <h6>
                      <i class="fas fa-align-left"></i>
                      Description
                    </h6>
                    <div class="complaint-description-full">
                      {{ selectedComplaint.description }}
                    </div>
                  </div>

                  <div class="detail-section" v-if="selectedComplaint.attachments?.length">
                    <h6>
                      <i class="fas fa-paperclip"></i>
                      Attachments
                    </h6>
                    <div class="attachments-list">
                      <div 
                        class="attachment-item" 
                        v-for="attachment in selectedComplaint.attachments" 
                        :key="attachment.id"
                      >
                        <i class="fas fa-file"></i>
                        <span>{{ attachment.name }}</span>
                        <button class="btn btn-sm btn-outline-primary" @click="downloadAttachment(attachment)">
                          <i class="fas fa-download"></i>
                        </button>
                      </div>
                    </div>
                  </div>

                  <div class="detail-section">
                    <h6>
                      <i class="fas fa-comments"></i>
                      Updates & Comments
                    </h6>
                    <div class="updates-timeline">
                      <div 
                        class="timeline-item" 
                        v-for="update in selectedComplaint.updates" 
                        :key="update.id"
                      >
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                          <div class="update-header">
                            <strong>{{ update.author }}</strong>
                            <span class="update-type" :class="update.type">{{ update.type }}</span>
                            <small class="text-muted">{{ formatDate(update.created_at) }}</small>
                          </div>
                          <div class="update-message">{{ update.message }}</div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="complaint-sidebar">
                  <div class="member-profile">
                    <img 
                      :src="selectedComplaint.member.avatar || '/default-avatar.png'" 
                      :alt="selectedComplaint.member.name"
                      class="profile-avatar"
                    >
                    <h5>{{ selectedComplaint.member.name }}</h5>
                    <p class="member-id">Member ID: {{ selectedComplaint.member.id }}</p>
                    <div class="member-contact">
                      <div><i class="fas fa-envelope"></i> {{ selectedComplaint.member.email }}</div>
                      <div><i class="fas fa-phone"></i> {{ selectedComplaint.member.phone }}</div>
                    </div>
                  </div>

                  <div class="action-section">
                    <h6>Quick Actions</h6>
                    <div class="d-grid gap-2">
                      <button class="btn btn-success" @click="assignToMe(selectedComplaint)">
                        <i class="fas fa-user-check"></i>
                        Assign to Me
                      </button>
                      <button class="btn btn-warning" @click="changeStatus(selectedComplaint, 'investigating')">
                        <i class="fas fa-search"></i>
                        Start Investigation
                      </button>
                      <button class="btn btn-info" @click="addUpdate(selectedComplaint)">
                        <i class="fas fa-comment-plus"></i>
                        Add Update
                      </button>
                      <button class="btn btn-primary" @click="resolveComplaint(selectedComplaint)">
                        <i class="fas fa-check-circle"></i>
                        Mark Resolved
                      </button>
                    </div>
                  </div>

                  <div class="timeline-section">
                    <h6>Timeline</h6>
                    <div class="complaint-timeline">
                      <div class="timeline-item">
                        <i class="fas fa-plus-circle text-primary"></i>
                        <div>
                          <strong>Created</strong>
                          <small>{{ formatDate(selectedComplaint.created_at) }}</small>
                        </div>
                      </div>
                      <div class="timeline-item" v-if="selectedComplaint.assigned_at">
                        <i class="fas fa-user-plus text-success"></i>
                        <div>
                          <strong>Assigned</strong>
                          <small>{{ formatDate(selectedComplaint.assigned_at) }}</small>
                        </div>
                      </div>
                      <div class="timeline-item" v-if="selectedComplaint.resolved_at">
                        <i class="fas fa-check-circle text-info"></i>
                        <div>
                          <strong>Resolved</strong>
                          <small>{{ formatDate(selectedComplaint.resolved_at) }}</small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" @click="printComplaint">
              <i class="fas fa-print"></i>
              Print
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Update Modal -->
    <div class="modal fade" id="addUpdateModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-comment-plus"></i>
              Add Update
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitUpdate">
              <div class="mb-3">
                <label class="form-label">Update Type</label>
                <select class="form-select" v-model="newUpdate.type" required>
                  <option value="">Select Type</option>
                  <option value="comment">Comment</option>
                  <option value="status_change">Status Change</option>
                  <option value="assignment">Assignment</option>
                  <option value="resolution">Resolution</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea 
                  class="form-control" 
                  rows="4" 
                  v-model="newUpdate.message"
                  placeholder="Enter your update message..."
                  required
                ></textarea>
              </div>
              <div class="mb-3" v-if="newUpdate.type === 'status_change'">
                <label class="form-label">New Status</label>
                <select class="form-select" v-model="newUpdate.status">
                  <option value="pending">Pending</option>
                  <option value="investigating">Investigating</option>
                  <option value="resolved">Resolved</option>
                  <option value="closed">Closed</option>
                  <option value="rejected">Rejected</option>
                </select>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="submitUpdate">
              <i class="fas fa-save"></i>
              Add Update
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Toast -->
    <div class="success-toast" v-if="showSuccessToast">
      <i class="fas fa-check-circle"></i>
      {{ successMessage }}
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'

export default {
  name: 'AdminComplaints',
  setup() {
    const authStore = useAuthStore()
    
    // Reactive data
    const loading = ref(false)
    const viewMode = ref('list')
    const currentPage = ref(1)
    const itemsPerPage = ref(10)
    const selectedComplaints = ref([])
    const selectedComplaint = ref(null)
    const showSuccessToast = ref(false)
    const successMessage = ref('')

    // Stats data
    const stats = reactive({
      total: 0,
      pending: 0,
      investigating: 0,
      resolved: 0,
      totalChange: 0,
      pendingChange: 0,
      investigatingChange: 0,
      resolvedChange: 0
    })

    // Filters
    const filters = reactive({
      search: '',
      status: '',
      priority: '',
      category: '',
      dateRange: ''
    })

    // New update form
    const newUpdate = reactive({
      type: '',
      message: '',
      status: ''
    })

    // Sample complaints data
    const complaints = ref([
      {
        id: 'CMP001',
        member: {
          id: 'MEM001',
          name: 'John Doe',
          email: 'john@example.com',
          phone: '+1234567890',
          avatar: '/avatars/john.jpg'
        },
        subject: 'Service Quality Issue',
        description: 'I am experiencing poor service quality during peak hours. The connection is very slow and sometimes disconnects completely.',
        category: 'service',
        priority: 'high',
        status: 'pending',
        created_at: '2024-01-15T10:30:00Z',
        assigned_at: null,
        resolved_at: null,
        attachments: [
          { id: 1, name: 'screenshot.png', url: '/attachments/screenshot.png' }
        ],
        updates: [
          {
            id: 1,
            author: 'System',
            type: 'created',
            message: 'Complaint created by member',
            created_at: '2024-01-15T10:30:00Z'
          }
        ]
      },
      {
        id: 'CMP002',
        member: {
          id: 'MEM002',
          name: 'Jane Smith',
          email: 'jane@example.com',
          phone: '+1234567891',
          avatar: '/avatars/jane.jpg'
        },
        subject: 'Billing Discrepancy',
        description: 'There is an error in my monthly bill. I was charged for services I did not use.',
        category: 'billing',
        priority: 'medium',
        status: 'investigating',
        created_at: '2024-01-14T14:20:00Z',
        assigned_at: '2024-01-14T15:00:00Z',
        resolved_at: null,
        attachments: [],
        updates: [
          {
            id: 1,
            author: 'System',
            type: 'created',
            message: 'Complaint created by member',
            created_at: '2024-01-14T14:20:00Z'
          },
          {
            id: 2,
            author: 'Admin User',
            type: 'assignment',
            message: 'Complaint assigned to billing team',
            created_at: '2024-01-14T15:00:00Z'
          }
        ]
      },
      {
        id: 'CMP003',
        member: {
          id: 'MEM003',
          name: 'Mike Johnson',
          email: 'mike@example.com',
          phone: '+1234567892',
          avatar: '/avatars/mike.jpg'
        },
        subject: 'Technical Support Request',
        description: 'Need help setting up the new features. The documentation is not clear enough.',
        category: 'technical',
        priority: 'low',
        status: 'resolved',
        created_at: '2024-01-13T09:15:00Z',
        assigned_at: '2024-01-13T10:00:00Z',
        resolved_at: '2024-01-13T16:30:00Z',
        attachments: [],
        updates: [
          {
            id: 1,
            author: 'System',
            type: 'created',
            message: 'Complaint created by member',
            created_at: '2024-01-13T09:15:00Z'
          },
          {
            id: 2,
            author: 'Tech Support',
            type: 'resolution',
            message: 'Provided detailed setup guide and resolved the issue',
            created_at: '2024-01-13T16:30:00Z'
          }
        ]
      }
    ])

    // Computed properties
    const filteredComplaints = computed(() => {
      let filtered = complaints.value

      if (filters.search) {
        const search = filters.search.toLowerCase()
        filtered = filtered.filter(complaint => 
          complaint.id.toLowerCase().includes(search) ||
          complaint.member.name.toLowerCase().includes(search) ||
          complaint.subject.toLowerCase().includes(search) ||
          complaint.description.toLowerCase().includes(search)
        )
      }

      if (filters.status) {
        filtered = filtered.filter(complaint => complaint.status === filters.status)
      }

      if (filters.priority) {
        filtered = filtered.filter(complaint => complaint.priority === filters.priority)
      }

      if (filters.category) {
        filtered = filtered.filter(complaint => complaint.category === filters.category)
      }

      if (filters.dateRange) {
        const now = new Date()
        const filterDate = new Date()
        
        switch (filters.dateRange) {
          case 'today':
            filterDate.setHours(0, 0, 0, 0)
            break
          case 'week':
            filterDate.setDate(now.getDate() - 7)
            break
          case 'month':
            filterDate.setMonth(now.getMonth() - 1)
            break
          case 'quarter':
            filterDate.setMonth(now.getMonth() - 3)
            break
        }
        
        filtered = filtered.filter(complaint => 
          new Date(complaint.created_at) >= filterDate
        )
      }

      return filtered
    })

    const paginatedComplaints = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage.value
      const end = start + itemsPerPage.value
      return filteredComplaints.value.slice(start, end)
    })

    const totalPages = computed(() => {
      return Math.ceil(filteredComplaints.value.length / itemsPerPage.value)
    })

    const visiblePages = computed(() => {
      const pages = []
      const total = totalPages.value
      const current = currentPage.value
      
      if (total <= 7) {
        for (let i = 1; i <= total; i++) {
          pages.push(i)
        }
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) {
            pages.push(i)
          }
          pages.push('...')
          pages.push(total)
        } else if (current >= total - 3) {
          pages.push(1)
          pages.push('...')
          for (let i = total - 4; i <= total; i++) {
            pages.push(i)
          }
        } else {
          pages.push(1)
          pages.push('...')
          for (let i = current - 1; i <= current + 1; i++) {
            pages.push(i)
          }
          pages.push('...')
          pages.push(total)
        }
      }
      
      return pages
    })

    // Methods
    const loadStats = async () => {
      try {
        // Simulate API call
        stats.total = complaints.value.length
        stats.pending = complaints.value.filter(c => c.status === 'pending').length
        stats.investigating = complaints.value.filter(c => c.status === 'investigating').length
        stats.resolved = complaints.value.filter(c => c.status === 'resolved').length
        stats.totalChange = 12
        stats.pendingChange = -8
        stats.investigatingChange = 15
        stats.resolvedChange = 25
      } catch (error) {
        console.error('Error loading stats:', error)
      }
    }

    const loadComplaints = async () => {
      try {
        loading.value = true
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1000))
        // Data is already loaded in the ref
      } catch (error) {
        console.error('Error loading complaints:', error)
      } finally {
        loading.value = false
      }
    }

    const refreshData = async () => {
      await Promise.all([loadStats(), loadComplaints()])
      showToast('Data refreshed successfully')
    }

    const applyFilters = () => {
      currentPage.value = 1
    }

    const clearFilters = () => {
      Object.keys(filters).forEach(key => {
        filters[key] = ''
      })
      currentPage.value = 1
    }

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
      }
    }

    const toggleSelectAll = () => {
      if (selectedComplaints.value.length === filteredComplaints.value.length) {
        selectedComplaints.value = []
      } else {
        selectedComplaints.value = filteredComplaints.value.map(c => c.id)
      }
    }

    const clearSelection = () => {
      selectedComplaints.value = []
    }

    const viewComplaint = (complaint) => {
      selectedComplaint.value = complaint
      // Show modal (Bootstrap modal)
      const modal = new bootstrap.Modal(document.getElementById('viewComplaintModal'))
      modal.show()
    }

    const assignComplaint = (complaint) => {
      // Implement assignment logic
      showToast(`Complaint #${complaint.id} assigned successfully`)
    }

    const updateStatus = (complaint, status) => {
      complaint.status = status
      showToast(`Complaint #${complaint.id} status updated to ${status}`)
    }

    const resolveComplaint = (complaint) => {
      complaint.status = 'resolved'
      complaint.resolved_at = new Date().toISOString()
      showToast(`Complaint #${complaint.id} marked as resolved`)
    }

    const assignToMe = (complaint) => {
      complaint.assigned_at = new Date().toISOString()
      showToast(`Complaint #${complaint.id} assigned to you`)
    }

    const changeStatus = (complaint, status) => {
      complaint.status = status
      showToast(`Complaint status changed to ${status}`)
    }

    const addUpdate = (complaint) => {
      // Show add update modal
      const modal = new bootstrap.Modal(document.getElementById('addUpdateModal'))
      modal.show()
    }

    const submitUpdate = () => {
      if (selectedComplaint.value && newUpdate.message) {
        const update = {
          id: selectedComplaint.value.updates.length + 1,
          author: authStore.user.name,
          type: newUpdate.type,
          message: newUpdate.message,
          created_at: new Date().toISOString()
        }
        
        selectedComplaint.value.updates.push(update)
        
        if (newUpdate.type === 'status_change' && newUpdate.status) {
          selectedComplaint.value.status = newUpdate.status
        }
        
        // Reset form
        Object.keys(newUpdate).forEach(key => {
          newUpdate[key] = ''
        })
        
        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('addUpdateModal'))
        modal.hide()
        
        showToast('Update added successfully')
      }
    }

    const bulkAssign = () => {
      showToast(`${selectedComplaints.value.length} complaints assigned`)
      selectedComplaints.value = []
    }

    const bulkUpdateStatus = () => {
      showToast(`${selectedComplaints.value.length} complaints status updated`)
      selectedComplaints.value = []
    }

    const bulkExport = () => {
      showToast(`${selectedComplaints.value.length} complaints exported`)
      selectedComplaints.value = []
    }

    const exportComplaints = () => {
      showToast('Complaints exported successfully')
    }

    const downloadAttachment = (attachment) => {
      // Implement download logic
      showToast(`Downloading ${attachment.name}`)
    }

    const printComplaint = () => {
      window.print()
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString()
    }

    const formatTimeAgo = (dateString) => {
      const now = new Date()
      const date = new Date(dateString)
      const diffInHours = Math.floor((now - date) / (1000 * 60 * 60))
      
      if (diffInHours < 1) return 'Just now'
      if (diffInHours < 24) return `${diffInHours}h ago`
      
      const diffInDays = Math.floor(diffInHours / 24)
      if (diffInDays < 7) return `${diffInDays}d ago`
      
      const diffInWeeks = Math.floor(diffInDays / 7)
      return `${diffInWeeks}w ago`
    }

    const showToast = (message) => {
      successMessage.value = message
      showSuccessToast.value = true
      setTimeout(() => {
        showSuccessToast.value = false
      }, 3000)
    }

    // Watchers
    watch(() => filters.search, applyFilters)

    // Lifecycle
    onMounted(async () => {
      await Promise.all([loadStats(), loadComplaints()])
    })

    return {
      // Data
      loading,
      viewMode,
      currentPage,
      selectedComplaints,
      selectedComplaint,
      showSuccessToast,
      successMessage,
      stats,
      filters,
      newUpdate,
      complaints,
      
      // Computed
      filteredComplaints,
      paginatedComplaints,
      totalPages,
      visiblePages,
      
      // Methods
      refreshData,
      applyFilters,
      clearFilters,
      changePage,
      toggleSelectAll,
      clearSelection,
      viewComplaint,
      assignComplaint,
      updateStatus,
      resolveComplaint,
      assignToMe,
      changeStatus,
      addUpdate,
      submitUpdate,
      bulkAssign,
      bulkUpdateStatus,
      bulkExport,
      exportComplaints,
      downloadAttachment,
      printComplaint,
      formatDate,
      formatTimeAgo
    }
  }
}
</script>

<style scoped>
/* Admin Complaints Page Styles */
.admin-complaints {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
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
  backdrop-filter: blur(4px);
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #e3f2fd;
  border-top: 4px solid #1976d2;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Header */
.complaints-header {
  background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
  color: white;
  padding: 2rem 0;
  margin-bottom: 2rem;
  box-shadow: 0 4px 20px rgba(25, 118, 210, 0.3);
}

.complaints-header h1 {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.complaints-header p {
  margin: 0.5rem 0 0 0;
  opacity: 0.9;
  font-size: 1.1rem;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.header-actions .btn {
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

.header-actions .btn:hover {
  background: rgba(255, 255, 255, 0.3);
  border-color: rgba(255, 255, 255, 0.5);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.header-actions .btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Statistics Cards */
.stats-section {
  margin-bottom: 2rem;
}

.stats-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  border: 1px solid #e0e0e0;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  height: 100%;
}

.stats-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: var(--card-color, #1976d2);
}

.stats-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.stats-card.total { --card-color: #1976d2; }
.stats-card.pending { --card-color: #f57c00; }
.stats-card.investigating { --card-color: #0288d1; }
.stats-card.resolved { --card-color: #388e3c; }

.stats-card .card-body {
  padding: 1.5rem;
}

.stats-card .card-title {
  font-size: 0.9rem;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 1rem;
}

.stats-card .stats-number {
  font-size: 2.5rem;
  font-weight: 700;
  color: #333;
  line-height: 1;
  margin-bottom: 0.5rem;
}

.stats-card .stats-change {
  font-size: 0.85rem;
}

.stats-card .stats-change.positive { color: #388e3c; }
.stats-card .stats-change.negative { color: #d32f2f; }

/* Filters Section */
.filters-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  border: 1px solid #e0e0e0;
}

.filters-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.filters-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.view-toggle {
  display: flex;
  background: #f5f5f5;
  border-radius: 8px;
  padding: 4px;
}

.view-btn {
  background: none;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  color: #666;
}

.view-btn.active {
  background: white;
  color: #1976d2;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.filters-row {
  margin-bottom: 1rem;
}

.filter-group {
  margin-bottom: 1rem;
}

.filter-label {
  font-size: 0.9rem;
  font-weight: 500;
  color: #555;
  margin-bottom: 0.5rem;
  display: block;
}

.form-control {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: all 0.2s ease;
  background: white;
  width: 100%;
}

.form-control:focus {
  outline: none;
  border-color: #1976d2;
  box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
}

.form-select {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 0.75rem center;
  background-repeat: no-repeat;
  background-size: 1rem;
  padding-right: 2.5rem;
}

.filters-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  flex-wrap: wrap;
}

/* Buttons */
.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  border: 1px solid transparent;
}

.btn-primary {
  background: #1976d2;
  color: white;
}

.btn-primary:hover {
  background: #1565c0;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
}

.btn-outline-primary {
  background: transparent;
  color: #1976d2;
  border: 1px solid #1976d2;
}

.btn-outline-primary:hover {
  background: #1976d2;
  color: white;
}

.btn-outline-secondary {
  background: transparent;
  color: #666;
  border: 1px solid #ddd;
}

.btn-outline-secondary:hover {
  background: #f8f9fa;
  border-color: #999;
}

.btn-outline-warning {
  background: transparent;
  color: #f57c00;
  border: 1px solid #f57c00;
}

.btn-outline-warning:hover {
  background: #f57c00;
  color: white;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.8rem;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Complaints Section */
.complaints-section {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  border: 1px solid #e0e0e0;
  overflow: hidden;
}

.section-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e0e0e0;
  background: #f8f9fa;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.section-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.section-subtitle {
  color: #666;
  font-size: 0.9rem;
  margin: 0.25rem 0 0 0;
}

/* List View */
.complaints-list {
  max-height: 600px;
  overflow-y: auto;
}

.complaint-item {
  padding: 1.5rem;
  border-bottom: 1px solid #f0f0f0;
  transition: all 0.2s ease;
  cursor: pointer;
}

.complaint-item:hover {
  background: #f8f9fa;
}

.complaint-item:last-child {
  border-bottom: none;
}

.complaint-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.complaint-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #333;
  margin: 0 0 0.25rem 0;
}

.complaint-id {
  font-size: 0.85rem;
  color: #666;
  font-family: 'Monaco', 'Menlo', monospace;
}

.complaint-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge-warning { background: #fff3cd; color: #856404; }
.badge-info { background: #d1ecf1; color: #0c5460; }
.badge-success { background: #d4edda; color: #155724; }
.badge-secondary { background: #e2e3e5; color: #383d41; }
.badge-danger { background: #f8d7da; color: #721c24; }

.complaint-meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.meta-item {
  display: flex;
  flex-direction: column;
}

.meta-label {
  font-size: 0.8rem;
  color: #666;
  font-weight: 500;
  margin-bottom: 0.25rem;
}

.meta-value {
  font-size: 0.9rem;
  color: #333;
}

.complaint-description {
  color: #666;
  line-height: 1.5;
  margin-bottom: 1rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.complaint-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

/* Grid View */
.complaints-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
  padding: 1.5rem;
}

.complaint-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.3s ease;
  cursor: pointer;
}

.complaint-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  border-color: #1976d2;
}

/* Bulk Actions Bar */
.bulk-actions-bar {
  position: fixed;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%);
  background: white;
  border-radius: 12px;
  padding: 1rem 1.5rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  border: 1px solid #e0e0e0;
  display: flex;
  align-items: center;
  gap: 1rem;
  z-index: 1000;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(100%);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
}

.bulk-info {
  font-weight: 500;
  color: #333;
}

.bulk-actions {
  display: flex;
  gap: 0.5rem;
}

/* Pagination */
.pagination-section {
  padding: 1.5rem;
  border-top: 1px solid #e0e0e0;
  background: #f8f9fa;
}

.pagination-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.pagination-text {
  color: #666;
  font-size: 0.9rem;
}

.pagination {
  display: flex;
  gap: 0.25rem;
  align-items: center;
}

.page-btn {
  padding: 0.5rem 0.75rem;
  border: 1px solid #ddd;
  background: white;
  color: #666;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.9rem;
}

.page-btn:hover:not(:disabled) {
  background: #f8f9fa;
  border-color: #999;
}

.page-btn.active {
  background: #1976d2;
  color: white;
  border-color: #1976d2;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modals */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-dialog {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow: hidden;
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-50px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e0e0e0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  font-size: 1.3rem;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #666;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.btn-close:hover {
  background: #f8f9fa;
  color: #333;
}

.modal-body {
  padding: 1.5rem;
  max-height: 60vh;
  overflow-y: auto;
}

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #e0e0e0;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  background: #f8f9fa;
}

/* Form Styles */
.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  font-weight: 500;
  color: #333;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

textarea.form-control {
  resize: vertical;
  min-height: 100px;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 1.5rem;
  color: #666;
}

.empty-icon {
  font-size: 4rem;
  color: #ddd;
  margin-bottom: 1rem;
}

.empty-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #333;
  margin-bottom: 0.5rem;
}

.empty-text {
  color: #666;
  line-height: 1.5;
}

/* Success Toast */
.success-toast {
  position: fixed;
  top: 2rem;
  right: 2rem;
  background: #4caf50;
  color: white;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
  z-index: 9999;
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
@media (max-width: 768px) {
  .complaints-header .row {
    flex-direction: column;
  }

  .header-actions {
    justify-content: center;
    width: 100%;
  }

  .stats-section .row > div {
    margin-bottom: 1rem;
  }

  .filters-header {
    flex-direction: column;
    align-items: stretch;
  }

  .filters-actions {
    justify-content: stretch;
  }

  .filters-actions .btn {
    flex: 1;
    justify-content: center;
  }

  .complaints-grid {
    grid-template-columns: 1fr;
    padding: 1rem;
  }

  .complaint-header {
    flex-direction: column;
    align-items: stretch;
  }

  .complaint-meta {
    grid-template-columns: 1fr;
  }

  .bulk-actions-bar {
    left: 1rem;
    right: 1rem;
    transform: none;
    flex-direction: column;
    align-items: stretch;
  }

  .bulk-actions {
    justify-content: center;
  }

  .pagination-info {
    flex-direction: column;
    text-align: center;
  }

  .modal-dialog {
    margin: 0;
    max-width: none;
    height: 100vh;
    border-radius: 0;
  }
}

@media (max-width: 480px) {
  .complaints-header h1 {
    font-size: 1.5rem;
  }

  .stats-card .stats-number {
    font-size: 2rem;
  }

  .complaint-actions {
    justify-content: stretch;
  }

  .complaint-actions .btn {
    flex: 1;
    justify-content: center;
  }
}

/* Print Styles */
@media print {
  .admin-complaints {
    background: white !important;
  }

  .loading-overlay,
  .header-actions,
  .filters-section,
  .complaint-actions,
  .bulk-actions-bar,
  .pagination-section,
  .modal {
    display: none !important;
  }

  .complaints-header {
    background: white !important;
    color: black !important;
    box-shadow: none !important;
  }

  .stats-card,
  .complaints-section {
    box-shadow: none !important;
    border: 1px solid #ddd !important;
  }

  .complaint-item {
    break-inside: avoid;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .admin-complaints {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    color: #e0e0e0;
  }

  .stats-card,
  .filters-section,
  .complaints-section {
    background: #2d2d2d;
    border-color: #404040;
    color: #e0e0e0;
  }

  .form-control {
    background: #404040;
    border-color: #555;
    color: #e0e0e0;
  }

  .form-control:focus {
    border-color: #64b5f6;
    box-shadow: 0 0 0 3px rgba(100, 181, 246, 0.1);
  }

  .complaint-item:hover {
    background: #404040;
  }

  .modal-dialog {
    background: #2d2d2d;
    color: #e0e0e0;
  }

  .modal-footer {
    background: #404040;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Focus Styles */
.btn:focus,
.form-control:focus,
.page-btn:focus {
  outline: 2px solid #1976d2;
  outline-offset: 2px;
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .stats-card,
  .filters-section,
  .complaints-section {
    border: 2px solid #000;
  }

  .btn {
    border: 2px solid currentColor;
  }

  .badge {
    border: 1px solid currentColor;
  }
}

/* Custom Scrollbar */
.complaints-list::-webkit-scrollbar,
.modal-body::-webkit-scrollbar {
  width: 8px;
}

.complaints-list::-webkit-scrollbar-track,
.modal-body::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.complaints-list::-webkit-scrollbar-thumb,
.modal-body::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

.complaints-list::-webkit-scrollbar-thumb:hover,
.modal-body::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>