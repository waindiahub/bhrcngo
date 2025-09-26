<template>
  <div class="admin-members">
    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
      <p>Loading members...</p>
    </div>

    <!-- Page Header -->
    <div class="members-header">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h1><i class="fas fa-users"></i> Member Management</h1>
            <p>Manage and monitor all registered members</p>
          </div>
          <div class="col-md-4 text-end">
            <div class="header-actions">
              <button 
                class="btn btn-primary" 
                @click="showAddMemberModal = true"
                :disabled="loading"
              >
                <i class="fas fa-plus"></i> Add Member
              </button>
              <button 
                class="btn btn-outline-primary" 
                @click="exportMembers"
                :disabled="loading || exporting"
              >
                <i class="fas fa-download"></i> 
                {{ exporting ? 'Exporting...' : 'Export' }}
              </button>
              <button 
                class="btn btn-outline-primary" 
                @click="refreshData"
                :disabled="loading"
              >
                <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="container-fluid">
      <!-- Statistics Cards -->
      <div class="stats-section">
        <div class="row">
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card total">
              <div class="card-body">
                <div class="stats-icon">
                  <i class="fas fa-users"></i>
                </div>
                <div class="stats-content">
                  <h3>{{ stats.total || 0 }}</h3>
                  <p>Total Members</p>
                  <div class="stats-change positive">
                    <i class="fas fa-arrow-up"></i>
                    +{{ stats.newThisMonth || 0 }} this month
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card active">
              <div class="card-body">
                <div class="stats-icon">
                  <i class="fas fa-user-check"></i>
                </div>
                <div class="stats-content">
                  <h3>{{ stats.active || 0 }}</h3>
                  <p>Active Members</p>
                  <div class="stats-change positive">
                    <i class="fas fa-percentage"></i>
                    {{ ((stats.active / stats.total) * 100).toFixed(1) }}% active
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card pending">
              <div class="card-body">
                <div class="stats-icon">
                  <i class="fas fa-user-clock"></i>
                </div>
                <div class="stats-content">
                  <h3>{{ stats.pending || 0 }}</h3>
                  <p>Pending Approval</p>
                  <div class="stats-change" :class="stats.pending > 0 ? 'negative' : 'positive'">
                    <i class="fas fa-exclamation-triangle" v-if="stats.pending > 0"></i>
                    <i class="fas fa-check" v-else></i>
                    {{ stats.pending > 0 ? 'Needs attention' : 'All approved' }}
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6 mb-4">
            <div class="stats-card suspended">
              <div class="card-body">
                <div class="stats-icon">
                  <i class="fas fa-user-slash"></i>
                </div>
                <div class="stats-content">
                  <h3>{{ stats.suspended || 0 }}</h3>
                  <p>Suspended</p>
                  <div class="stats-change" :class="stats.suspended > 0 ? 'negative' : 'positive'">
                    <i class="fas fa-ban" v-if="stats.suspended > 0"></i>
                    <i class="fas fa-check" v-else></i>
                    {{ stats.suspended > 0 ? 'Action required' : 'None suspended' }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters and Search -->
      <div class="filters-section">
        <div class="card">
          <div class="card-body">
            <div class="row align-items-end">
              <div class="col-md-3">
                <label class="form-label">Search Members</label>
                <div class="search-input">
                  <i class="fas fa-search"></i>
                  <input 
                    type="text" 
                    class="form-control" 
                    placeholder="Search by name, email, or ID..."
                    v-model="filters.search"
                    @input="debouncedSearch"
                  >
                </div>
              </div>
              <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" v-model="filters.status" @change="applyFilters">
                  <option value="">All Status</option>
                  <option value="active">Active</option>
                  <option value="pending">Pending</option>
                  <option value="suspended">Suspended</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label">Membership Type</label>
                <select class="form-select" v-model="filters.membershipType" @change="applyFilters">
                  <option value="">All Types</option>
                  <option value="regular">Regular</option>
                  <option value="premium">Premium</option>
                  <option value="lifetime">Lifetime</option>
                  <option value="student">Student</option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label">Join Date</label>
                <select class="form-select" v-model="filters.joinDate" @change="applyFilters">
                  <option value="">All Time</option>
                  <option value="today">Today</option>
                  <option value="week">This Week</option>
                  <option value="month">This Month</option>
                  <option value="year">This Year</option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label">Sort By</label>
                <select class="form-select" v-model="filters.sortBy" @change="applyFilters">
                  <option value="created_at">Join Date</option>
                  <option value="name">Name</option>
                  <option value="email">Email</option>
                  <option value="last_login">Last Login</option>
                  <option value="status">Status</option>
                </select>
              </div>
              <div class="col-md-1">
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

      <!-- Members Table -->
      <div class="members-section">
        <div class="card">
          <div class="card-header">
            <div class="row align-items-center">
              <div class="col">
                <h5><i class="fas fa-list"></i> Members List</h5>
                <small class="text-muted">
                  Showing {{ filteredMembers.length }} of {{ members.length }} members
                </small>
              </div>
              <div class="col-auto">
                <div class="view-toggle">
                  <button 
                    class="btn btn-sm" 
                    :class="viewMode === 'table' ? 'btn-primary' : 'btn-outline-primary'"
                    @click="viewMode = 'table'"
                  >
                    <i class="fas fa-table"></i>
                  </button>
                  <button 
                    class="btn btn-sm" 
                    :class="viewMode === 'grid' ? 'btn-primary' : 'btn-outline-primary'"
                    @click="viewMode = 'grid'"
                  >
                    <i class="fas fa-th"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div class="card-body p-0">
            <!-- Table View -->
            <div v-if="viewMode === 'table'" class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr>
                    <th>
                      <input 
                        type="checkbox" 
                        class="form-check-input"
                        @change="toggleSelectAll"
                        :checked="selectedMembers.length === filteredMembers.length && filteredMembers.length > 0"
                      >
                    </th>
                    <th>Member</th>
                    <th>Contact</th>
                    <th>Membership</th>
                    <th>Status</th>
                    <th>Join Date</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="filteredMembers.length === 0">
                    <td colspan="8" class="text-center py-5">
                      <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <p>No members found matching your criteria</p>
                      </div>
                    </td>
                  </tr>
                  <tr v-for="member in paginatedMembers" :key="member.id">
                    <td>
                      <input 
                        type="checkbox" 
                        class="form-check-input"
                        :value="member.id"
                        v-model="selectedMembers"
                      >
                    </td>
                    <td>
                      <div class="member-info">
                        <img 
                          :src="member.avatar || '/default-avatar.png'" 
                          :alt="member.name"
                          class="member-avatar"
                        >
                        <div>
                          <div class="member-name">{{ member.name }}</div>
                          <div class="member-id">ID: {{ member.member_id }}</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="contact-info">
                        <div><i class="fas fa-envelope"></i> {{ member.email }}</div>
                        <div v-if="member.phone">
                          <i class="fas fa-phone"></i> {{ member.phone }}
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="membership-info">
                        <span class="membership-type" :class="member.membership_type">
                          {{ member.membership_type }}
                        </span>
                        <div class="membership-expiry" v-if="member.expiry_date">
                          Expires: {{ formatDate(member.expiry_date) }}
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="status-badge" :class="member.status">
                        {{ member.status }}
                      </span>
                    </td>
                    <td>{{ formatDate(member.created_at) }}</td>
                    <td>
                      <span v-if="member.last_login" class="text-muted">
                        {{ formatRelativeTime(member.last_login) }}
                      </span>
                      <span v-else class="text-muted">Never</span>
                    </td>
                    <td>
                      <div class="action-buttons">
                        <button 
                          class="btn btn-sm btn-outline-primary"
                          @click="viewMember(member)"
                          title="View Details"
                        >
                          <i class="fas fa-eye"></i>
                        </button>
                        <button 
                          class="btn btn-sm btn-outline-warning"
                          @click="editMember(member)"
                          title="Edit Member"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <div class="dropdown d-inline">
                          <button 
                            class="btn btn-sm btn-outline-secondary dropdown-toggle"
                            data-bs-toggle="dropdown"
                            title="More Actions"
                          >
                            <i class="fas fa-ellipsis-v"></i>
                          </button>
                          <ul class="dropdown-menu">
                            <li>
                              <a class="dropdown-item" @click="sendMessage(member)">
                                <i class="fas fa-envelope"></i> Send Message
                              </a>
                            </li>
                            <li>
                              <a class="dropdown-item" @click="viewHistory(member)">
                                <i class="fas fa-history"></i> View History
                              </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li v-if="member.status === 'active'">
                              <a class="dropdown-item text-warning" @click="suspendMember(member)">
                                <i class="fas fa-ban"></i> Suspend
                              </a>
                            </li>
                            <li v-if="member.status === 'suspended'">
                              <a class="dropdown-item text-success" @click="activateMember(member)">
                                <i class="fas fa-check"></i> Activate
                              </a>
                            </li>
                            <li>
                              <a class="dropdown-item text-danger" @click="deleteMember(member)">
                                <i class="fas fa-trash"></i> Delete
                              </a>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Grid View -->
            <div v-if="viewMode === 'grid'" class="members-grid">
              <div v-if="filteredMembers.length === 0" class="empty-state">
                <i class="fas fa-users"></i>
                <p>No members found matching your criteria</p>
              </div>
              <div class="row">
                <div 
                  v-for="member in paginatedMembers" 
                  :key="member.id"
                  class="col-xl-3 col-lg-4 col-md-6 mb-4"
                >
                  <div class="member-card">
                    <div class="member-card-header">
                      <input 
                        type="checkbox" 
                        class="form-check-input member-select"
                        :value="member.id"
                        v-model="selectedMembers"
                      >
                      <span class="status-badge" :class="member.status">
                        {{ member.status }}
                      </span>
                    </div>
                    <div class="member-card-body">
                      <img 
                        :src="member.avatar || '/default-avatar.png'" 
                        :alt="member.name"
                        class="member-card-avatar"
                      >
                      <h6 class="member-card-name">{{ member.name }}</h6>
                      <p class="member-card-id">ID: {{ member.member_id }}</p>
                      <div class="member-card-info">
                        <div><i class="fas fa-envelope"></i> {{ member.email }}</div>
                        <div v-if="member.phone">
                          <i class="fas fa-phone"></i> {{ member.phone }}
                        </div>
                        <div class="membership-type" :class="member.membership_type">
                          <i class="fas fa-crown"></i> {{ member.membership_type }}
                        </div>
                      </div>
                    </div>
                    <div class="member-card-footer">
                      <small class="text-muted">
                        Joined {{ formatDate(member.created_at) }}
                      </small>
                      <div class="member-card-actions">
                        <button 
                          class="btn btn-sm btn-outline-primary"
                          @click="viewMember(member)"
                          title="View Details"
                        >
                          <i class="fas fa-eye"></i>
                        </button>
                        <button 
                          class="btn btn-sm btn-outline-warning"
                          @click="editMember(member)"
                          title="Edit Member"
                        >
                          <i class="fas fa-edit"></i>
                        </button>
                        <div class="dropdown d-inline">
                          <button 
                            class="btn btn-sm btn-outline-secondary dropdown-toggle"
                            data-bs-toggle="dropdown"
                            title="More Actions"
                          >
                            <i class="fas fa-ellipsis-v"></i>
                          </button>
                          <ul class="dropdown-menu">
                            <li>
                              <a class="dropdown-item" @click="sendMessage(member)">
                                <i class="fas fa-envelope"></i> Send Message
                              </a>
                            </li>
                            <li>
                              <a class="dropdown-item" @click="viewHistory(member)">
                                <i class="fas fa-history"></i> View History
                              </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li v-if="member.status === 'active'">
                              <a class="dropdown-item text-warning" @click="suspendMember(member)">
                                <i class="fas fa-ban"></i> Suspend
                              </a>
                            </li>
                            <li v-if="member.status === 'suspended'">
                              <a class="dropdown-item text-success" @click="activateMember(member)">
                                <i class="fas fa-check"></i> Activate
                              </a>
                            </li>
                            <li>
                              <a class="dropdown-item text-danger" @click="deleteMember(member)">
                                <i class="fas fa-trash"></i> Delete
                              </a>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div class="pagination-section" v-if="totalPages > 1">
        <nav>
          <ul class="pagination justify-content-center">
            <li class="page-item" :class="{ disabled: currentPage === 1 }">
              <button class="page-link" @click="changePage(currentPage - 1)">
                <i class="fas fa-chevron-left"></i>
              </button>
            </li>
            <li 
              v-for="page in visiblePages" 
              :key="page"
              class="page-item" 
              :class="{ active: page === currentPage }"
            >
              <button class="page-link" @click="changePage(page)">
                {{ page }}
              </button>
            </li>
            <li class="page-item" :class="{ disabled: currentPage === totalPages }">
              <button class="page-link" @click="changePage(currentPage + 1)">
                <i class="fas fa-chevron-right"></i>
              </button>
            </li>
          </ul>
        </nav>
      </div>

      <!-- Bulk Actions -->
      <div class="bulk-actions" v-if="selectedMembers.length > 0">
        <div class="bulk-actions-bar">
          <div class="selected-count">
            {{ selectedMembers.length }} member(s) selected
          </div>
          <div class="bulk-buttons">
            <button class="btn btn-outline-primary" @click="bulkExport">
              <i class="fas fa-download"></i> Export Selected
            </button>
            <button class="btn btn-outline-warning" @click="bulkSuspend">
              <i class="fas fa-ban"></i> Suspend
            </button>
            <button class="btn btn-outline-success" @click="bulkActivate">
              <i class="fas fa-check"></i> Activate
            </button>
            <button class="btn btn-outline-danger" @click="bulkDelete">
              <i class="fas fa-trash"></i> Delete
            </button>
            <button class="btn btn-secondary" @click="clearSelection">
              <i class="fas fa-times"></i> Clear
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Member Modal -->
    <div class="modal fade" :class="{ show: showAddMemberModal }" :style="{ display: showAddMemberModal ? 'block' : 'none' }">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-user-plus"></i> Add New Member
            </h5>
            <button type="button" class="btn-close" @click="showAddMemberModal = false"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="addMember">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Full Name *</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      v-model="newMember.name"
                      required
                    >
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Email *</label>
                    <input 
                      type="email" 
                      class="form-control" 
                      v-model="newMember.email"
                      required
                    >
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input 
                      type="tel" 
                      class="form-control" 
                      v-model="newMember.phone"
                    >
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Membership Type *</label>
                    <select class="form-select" v-model="newMember.membership_type" required>
                      <option value="">Select Type</option>
                      <option value="regular">Regular</option>
                      <option value="premium">Premium</option>
                      <option value="lifetime">Lifetime</option>
                      <option value="student">Student</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input 
                      type="date" 
                      class="form-control" 
                      v-model="newMember.date_of_birth"
                    >
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Gender</label>
                    <select class="form-select" v-model="newMember.gender">
                      <option value="">Select Gender</option>
                      <option value="male">Male</option>
                      <option value="female">Female</option>
                      <option value="other">Other</option>
                    </select>
                  </div>
                </div>
                <div class="col-12">
                  <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea 
                      class="form-control" 
                      rows="3"
                      v-model="newMember.address"
                    ></textarea>
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showAddMemberModal = false">
              Cancel
            </button>
            <button 
              type="button" 
              class="btn btn-primary" 
              @click="addMember"
              :disabled="addingMember"
            >
              <i class="fas fa-plus"></i>
              {{ addingMember ? 'Adding...' : 'Add Member' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- View Member Modal -->
    <div class="modal fade" :class="{ show: showViewMemberModal }" :style="{ display: showViewMemberModal ? 'block' : 'none' }">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-user"></i> Member Details
            </h5>
            <button type="button" class="btn-close" @click="showViewMemberModal = false"></button>
          </div>
          <div class="modal-body" v-if="selectedMember">
            <div class="row">
              <div class="col-md-4">
                <div class="member-profile">
                  <img 
                    :src="selectedMember.avatar || '/default-avatar.png'" 
                    :alt="selectedMember.name"
                    class="profile-avatar"
                  >
                  <h4>{{ selectedMember.name }}</h4>
                  <p class="member-id">ID: {{ selectedMember.member_id }}</p>
                  <span class="status-badge large" :class="selectedMember.status">
                    {{ selectedMember.status }}
                  </span>
                </div>
              </div>
              <div class="col-md-8">
                <div class="member-details">
                  <div class="detail-section">
                    <h6><i class="fas fa-info-circle"></i> Basic Information</h6>
                    <div class="detail-grid">
                      <div class="detail-item">
                        <label>Email:</label>
                        <span>{{ selectedMember.email }}</span>
                      </div>
                      <div class="detail-item">
                        <label>Phone:</label>
                        <span>{{ selectedMember.phone || 'Not provided' }}</span>
                      </div>
                      <div class="detail-item">
                        <label>Date of Birth:</label>
                        <span>{{ selectedMember.date_of_birth ? formatDate(selectedMember.date_of_birth) : 'Not provided' }}</span>
                      </div>
                      <div class="detail-item">
                        <label>Gender:</label>
                        <span>{{ selectedMember.gender || 'Not specified' }}</span>
                      </div>
                    </div>
                  </div>
                  
                  <div class="detail-section">
                    <h6><i class="fas fa-crown"></i> Membership Information</h6>
                    <div class="detail-grid">
                      <div class="detail-item">
                        <label>Type:</label>
                        <span class="membership-type" :class="selectedMember.membership_type">
                          {{ selectedMember.membership_type }}
                        </span>
                      </div>
                      <div class="detail-item">
                        <label>Join Date:</label>
                        <span>{{ formatDate(selectedMember.created_at) }}</span>
                      </div>
                      <div class="detail-item">
                        <label>Expiry Date:</label>
                        <span>{{ selectedMember.expiry_date ? formatDate(selectedMember.expiry_date) : 'No expiry' }}</span>
                      </div>
                      <div class="detail-item">
                        <label>Last Login:</label>
                        <span>{{ selectedMember.last_login ? formatRelativeTime(selectedMember.last_login) : 'Never' }}</span>
                      </div>
                    </div>
                  </div>

                  <div class="detail-section" v-if="selectedMember.address">
                    <h6><i class="fas fa-map-marker-alt"></i> Address</h6>
                    <p>{{ selectedMember.address }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="showViewMemberModal = false">
              Close
            </button>
            <button type="button" class="btn btn-warning" @click="editMember(selectedMember)">
              <i class="fas fa-edit"></i> Edit Member
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Toast -->
    <div v-if="showSuccessToast" class="success-toast">
      <i class="fas fa-check-circle"></i>
      {{ successMessage }}
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/utils/api'

export default {
  name: 'AdminMembers',
  setup() {
    const authStore = useAuthStore()
    
    // Reactive data
    const loading = ref(false)
    const exporting = ref(false)
    const addingMember = ref(false)
    const members = ref([])
    const selectedMembers = ref([])
    const viewMode = ref('table')
    const currentPage = ref(1)
    const itemsPerPage = ref(20)
    const showAddMemberModal = ref(false)
    const showViewMemberModal = ref(false)
    const selectedMember = ref(null)
    const showSuccessToast = ref(false)
    const successMessage = ref('')

    // Statistics
    const stats = reactive({
      total: 0,
      active: 0,
      pending: 0,
      suspended: 0,
      newThisMonth: 0
    })

    // Filters
    const filters = reactive({
      search: '',
      status: '',
      membershipType: '',
      joinDate: '',
      sortBy: 'created_at'
    })

    // New member form
    const newMember = reactive({
      name: '',
      email: '',
      phone: '',
      membership_type: '',
      date_of_birth: '',
      gender: '',
      address: ''
    })

    // Computed properties
    const filteredMembers = computed(() => {
      let filtered = [...members.value]

      // Search filter
      if (filters.search) {
        const search = filters.search.toLowerCase()
        filtered = filtered.filter(member => 
          member.name.toLowerCase().includes(search) ||
          member.email.toLowerCase().includes(search) ||
          member.member_id.toLowerCase().includes(search)
        )
      }

      // Status filter
      if (filters.status) {
        filtered = filtered.filter(member => member.status === filters.status)
      }

      // Membership type filter
      if (filters.membershipType) {
        filtered = filtered.filter(member => member.membership_type === filters.membershipType)
      }

      // Join date filter
      if (filters.joinDate) {
        const now = new Date()
        const filterDate = new Date()
        
        switch (filters.joinDate) {
          case 'today':
            filterDate.setHours(0, 0, 0, 0)
            break
          case 'week':
            filterDate.setDate(now.getDate() - 7)
            break
          case 'month':
            filterDate.setMonth(now.getMonth() - 1)
            break
          case 'year':
            filterDate.setFullYear(now.getFullYear() - 1)
            break
        }
        
        filtered = filtered.filter(member => new Date(member.created_at) >= filterDate)
      }

      // Sort
      filtered.sort((a, b) => {
        const aVal = a[filters.sortBy]
        const bVal = b[filters.sortBy]
        
        if (filters.sortBy === 'created_at' || filters.sortBy === 'last_login') {
          return new Date(bVal) - new Date(aVal)
        }
        
        return aVal.localeCompare(bVal)
      })

      return filtered
    })

    const totalPages = computed(() => Math.ceil(filteredMembers.value.length / itemsPerPage.value))

    const paginatedMembers = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage.value
      const end = start + itemsPerPage.value
      return filteredMembers.value.slice(start, end)
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
    const loadMembers = async () => {
  loading.value = true
  try {
    // api.get should return the HTTP library's result (e.g. axios returns response)
    // Normalize to handle both axios-like (response.data) and fetch-like (direct JSON)
    const resp = await api.get('/admin/members')
    // If api.get returns an axios-style response, members live in resp.data.data.members
    // If it returns already-parsed JSON, members live in resp.data.members (or resp.data.data.members)
    let payload = resp
    if (resp && resp.data) payload = resp.data
    // payload now should be { success, message, data: {...} }
    const body = payload.data ?? payload // fallback if payload is already the inner data object

    members.value = (body.members) ? body.members : []
    // update pagination / total if provided
    if (body.total !== undefined) {
      // optionally store total somewhere if you want (not used currently)
    }

    updateStats()
  } catch (error) {
    console.error('Error loading members:', error)
    // optionally show a toast
  } finally {
    loading.value = false
  }
}


    const updateStats = () => {
      stats.total = members.value.length
      stats.active = members.value.filter(m => m.status === 'active').length
      stats.pending = members.value.filter(m => m.status === 'pending').length
      stats.suspended = members.value.filter(m => m.status === 'suspended').length
      
      const thisMonth = new Date()
      thisMonth.setMonth(thisMonth.getMonth() - 1)
      stats.newThisMonth = members.value.filter(m => new Date(m.created_at) >= thisMonth).length
    }

    const refreshData = () => {
      loadMembers()
    }

    const exportMembers = async () => {
  exporting.value = true
  try {
    // If your api.post wrapper supports options, pass responseType: 'blob' (axios)
    const resp = await api.post('/admin/members/export', {
      filters: filters,
      members: selectedMembers.value.length > 0 ? selectedMembers.value : null
    }, {
      responseType: 'blob'
    })

    // Normalize blob extraction: axios returns resp.data, fetch wrapper might return resp directly
    const blob = resp && resp.data ? resp.data : resp

    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    // backend returns CSV filename; but frontend can name it
    a.download = `members-export-${new Date().toISOString().split('T')[0]}.csv`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    window.URL.revokeObjectURL(url)

    showSuccess('Members exported successfully')
  } catch (error) {
    console.error('Error exporting members:', error)
  } finally {
    exporting.value = false
  }
}


    const addMember = async () => {
  addingMember.value = true
  try {
    const resp = await api.post('/admin/members', newMember)
    let payload = resp
    if (resp && resp.data) payload = resp.data
    // created member is in payload.data.member (based on sendSuccess(['member'=>..]) on backend)
    const created = (payload.data && payload.data.member) ? payload.data.member : (payload.member ?? null)

    if (created) {
      // Add to local list and update stats
      members.value.unshift(created)
      updateStats()
      showAddMemberModal.value = false
      resetNewMemberForm()
      showSuccess('Member added successfully')
    } else {
      console.warn('Add member: unexpected response shape', payload)
      showSuccess('Member added (server response unexpected).')
      // Optionally refresh list
      await loadMembers()
    }
  } catch (error) {
    console.error('Error adding member:', error)
  } finally {
    addingMember.value = false
  }
}


    const viewMember = (member) => {
      selectedMember.value = member
      showViewMemberModal.value = true
    }

    const editMember = (member) => {
      // Navigate to edit page or open edit modal
      console.log('Edit member:', member)
    }

    const suspendMember = async (member) => {
      if (confirm(`Are you sure you want to suspend ${member.name}?`)) {
        try {
          await api.post(`/admin/members/${member.id}/suspend`)
          member.status = 'suspended'
          updateStats()
          showSuccess('Member suspended successfully')
        } catch (error) {
          console.error('Error suspending member:', error)
        }
      }
    }

    const activateMember = async (member) => {
      try {
        await api.post(`/admin/members/${member.id}/activate`)
        member.status = 'active'
        updateStats()
        showSuccess('Member activated successfully')
      } catch (error) {
        console.error('Error activating member:', error)
      }
    }

    const deleteMember = async (member) => {
      if (confirm(`Are you sure you want to delete ${member.name}? This action cannot be undone.`)) {
        try {
          await api.delete(`/admin/members/${member.id}`)
          const index = members.value.findIndex(m => m.id === member.id)
          if (index > -1) {
            members.value.splice(index, 1)
          }
          updateStats()
          showSuccess('Member deleted successfully')
        } catch (error) {
          console.error('Error deleting member:', error)
        }
      }
    }

    const sendMessage = (member) => {
      // Navigate to messaging or open message modal
      console.log('Send message to:', member)
    }

    const viewHistory = (member) => {
      // Navigate to member history page
      console.log('View history for:', member)
    }

    const toggleSelectAll = () => {
      if (selectedMembers.value.length === filteredMembers.value.length) {
        selectedMembers.value = []
      } else {
        selectedMembers.value = filteredMembers.value.map(m => m.id)
      }
    }

    const clearSelection = () => {
      selectedMembers.value = []
    }

    const bulkExport = () => {
      exportMembers()
    }

    const bulkSuspend = async () => {
      if (confirm(`Are you sure you want to suspend ${selectedMembers.value.length} members?`)) {
        // Implement bulk suspend
        console.log('Bulk suspend:', selectedMembers.value)
      }
    }

    const bulkActivate = async () => {
      if (confirm(`Are you sure you want to activate ${selectedMembers.value.length} members?`)) {
        // Implement bulk activate
        console.log('Bulk activate:', selectedMembers.value)
      }
    }

    const bulkDelete = async () => {
      if (confirm(`Are you sure you want to delete ${selectedMembers.value.length} members? This action cannot be undone.`)) {
        // Implement bulk delete
        console.log('Bulk delete:', selectedMembers.value)
      }
    }

    const applyFilters = () => {
      currentPage.value = 1
    }

    const clearFilters = () => {
      filters.search = ''
      filters.status = ''
      filters.membershipType = ''
      filters.joinDate = ''
      filters.sortBy = 'created_at'
      currentPage.value = 1
    }

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
      }
    }

    const resetNewMemberForm = () => {
      Object.keys(newMember).forEach(key => {
        newMember[key] = ''
      })
    }

    const showSuccess = (message) => {
      successMessage.value = message
      showSuccessToast.value = true
      setTimeout(() => {
        showSuccessToast.value = false
      }, 3000)
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleDateString()
    }

    const formatRelativeTime = (date) => {
      const now = new Date()
      const past = new Date(date)
      const diffInSeconds = Math.floor((now - past) / 1000)
      
      if (diffInSeconds < 60) return 'Just now'
      if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} minutes ago`
      if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`
      return `${Math.floor(diffInSeconds / 86400)} days ago`
    }

    // Debounced search
    let searchTimeout
    const debouncedSearch = () => {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        applyFilters()
      }, 300)
    }

    // Watchers
    watch(() => filters.search, () => {
      currentPage.value = 1
    })

    // Lifecycle
    onMounted(() => {
      loadMembers()
    })

    return {
      // Data
      loading,
      exporting,
      addingMember,
      members,
      selectedMembers,
      viewMode,
      currentPage,
      itemsPerPage,
      showAddMemberModal,
      showViewMemberModal,
      selectedMember,
      showSuccessToast,
      successMessage,
      stats,
      filters,
      newMember,
      
      // Computed
      filteredMembers,
      totalPages,
      paginatedMembers,
      visiblePages,
      
      // Methods
      loadMembers,
      refreshData,
      exportMembers,
      addMember,
      viewMember,
      editMember,
      suspendMember,
      activateMember,
      deleteMember,
      sendMessage,
      viewHistory,
      toggleSelectAll,
      clearSelection,
      bulkExport,
      bulkSuspend,
      bulkActivate,
      bulkDelete,
      applyFilters,
      clearFilters,
      changePage,
      resetNewMemberForm,
      showSuccess,
      formatDate,
      formatRelativeTime,
      debouncedSearch
    }
  }
}
</script>

<style scoped>
/* Admin Members Styles */
.admin-members {
  min-height: 100vh;
  background: #f8f9fa;
  position: relative;
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
  z-index: 1050;
}

.loading-spinner {
  width: 3rem;
  height: 3rem;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Members Header */
.members-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2rem 0;
  margin-bottom: 2rem;
}

.members-header h1 {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.members-header p {
  font-size: 1.1rem;
  opacity: 0.9;
  margin: 0;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.btn {
  border-radius: 10px;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  transition: all 0.3s ease;
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
}

.btn-primary {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  backdrop-filter: blur(10px);
}

.btn-primary:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-2px);
}

.btn-outline-primary {
  border: 2px solid rgba(255, 255, 255, 0.3);
  color: white;
  background: transparent;
}

.btn-outline-primary:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

/* Stats Section */
.stats-section {
  margin-bottom: 2rem;
}

.stats-card {
  background: white;
  border-radius: 15px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  overflow: hidden;
  position: relative;
}

.stats-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.stats-card.total {
  border-left: 5px solid #667eea;
}

.stats-card.active {
  border-left: 5px solid #28a745;
}

.stats-card.pending {
  border-left: 5px solid #ffc107;
}

.stats-card.suspended {
  border-left: 5px solid #dc3545;
}

.stats-card .card-body {
  padding: 2rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.stats-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  color: white;
  flex-shrink: 0;
}

.stats-card.total .stats-icon {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stats-card.active .stats-icon {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.stats-card.pending .stats-icon {
  background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
}

.stats-card.suspended .stats-icon {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}

.stats-content h3 {
  font-size: 2.5rem;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 0.5rem;
}

.stats-content p {
  font-size: 1.1rem;
  color: #29e801;
  margin-bottom: 0.75rem;
  font-weight: 600;
}

.stats-change {
  font-size: 0.9rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.stats-change.positive {
  color: #28a745;
}

.stats-change.negative {
  color: #dc3545;
}

/* Filters Section */
.filters-section {
  margin-bottom: 2rem;
}

.filters-section .card {
  border-radius: 15px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  border: none;
}

.search-input {
  position: relative;
}

.search-input i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #6c757d;
  z-index: 2;
}

.search-input .form-control {
  padding-left: 3rem;
  border-radius: 10px;
  border: 1px solid #e9ecef;
  transition: all 0.3s ease;
}

.search-input .form-control:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-select {
  border-radius: 10px;
  border: 1px solid #e9ecef;
  transition: all 0.3s ease;
}

.form-select:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Members Section */
.members-section {
  margin-bottom: 2rem;
}

.members-section .card {
  border-radius: 15px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  border: none;
}

.card-header {
  background: #f8f9fa;
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
  border-radius: 15px 15px 0 0;
}

.card-header h5 {
  margin: 0;
  font-weight: 600;
  color: #2c3e50;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.view-toggle {
  display: flex;
  gap: 0.5rem;
}

.view-toggle .btn {
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
}

/* Table Styles */
.table {
  margin: 0;
}

.table th {
  background: #f8f9fa;
  border-bottom: 2px solid #e9ecef;
  font-weight: 600;
  color: #2c3e50;
  padding: 1rem;
}

.table td {
  padding: 1rem;
  vertical-align: middle;
  border-bottom: 1px solid #f1f3f4;
}

.table tbody tr:hover {
  background: #f8f9fa;
}

.member-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.member-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #e9ecef;
}

.member-name {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.member-id {
  font-size: 0.85rem;
  color: #6c757d;
}

.contact-info div {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
  font-size: 0.9rem;
  color: #6c757d;
}

.contact-info i {
  width: 16px;
  color: #667eea;
}

.membership-info {
  text-align: center;
}

.membership-type {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  margin-bottom: 0.5rem;
}

.membership-type.regular {
  background: #e3f2fd;
  color: #1976d2;
}

.membership-type.premium {
  background: #fff3e0;
  color: #f57c00;
}

.membership-type.lifetime {
  background: #f3e5f5;
  color: #7b1fa2;
}

.membership-type.student {
  background: #e8f5e8;
  color: #388e3c;
}

.membership-expiry {
  font-size: 0.8rem;
  color: #6c757d;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.active {
  background: #d4edda;
  color: #155724;
}

.status-badge.pending {
  background: #fff3cd;
  color: #856404;
}

.status-badge.suspended {
  background: #f8d7da;
  color: #721c24;
}

.status-badge.inactive {
  background: #e2e3e5;
  color: #383d41;
}

.status-badge.large {
  padding: 0.5rem 1rem;
  font-size: 0.9rem;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.action-buttons .btn {
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
}

/* Grid View */
.members-grid {
  padding: 1.5rem;
}

.member-card {
  background: white;
  border-radius: 15px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  overflow: hidden;
  position: relative;
}

.member-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
}

.member-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem 0;
}

.member-select {
  margin: 0;
}

.member-card-body {
  text-align: center;
  padding: 1.5rem;
}

.member-card-avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid #e9ecef;
  margin-bottom: 1rem;
}

.member-card-name {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.member-card-id {
  font-size: 0.85rem;
  color: #6c757d;
  margin-bottom: 1rem;
}

.member-card-info {
  text-align: left;
  font-size: 0.9rem;
}

.member-card-info div {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  color: #6c757d;
}

.member-card-info i {
  width: 16px;
  color: #667eea;
}

.member-card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background: #f8f9fa;
  border-top: 1px solid #e9ecef;
}

.member-card-actions {
  display: flex;
  gap: 0.5rem;
}

.member-card-actions .btn {
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
}

/* Pagination */
.pagination-section {
  margin: 2rem 0;
}

.pagination {
  margin: 0;
}

.page-link {
  border: none;
  padding: 0.75rem 1rem;
  margin: 0 0.25rem;
  border-radius: 10px;
  color: #667eea;
  background: white;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.page-link:hover {
  background: #667eea;
  color: white;
  transform: translateY(-2px);
}

.page-item.active .page-link {
  background: #667eea;
  color: white;
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.page-item.disabled .page-link {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Bulk Actions */
.bulk-actions {
  position: fixed;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%);
  z-index: 1040;
}

.bulk-actions-bar {
  background: white;
  border-radius: 15px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
  padding: 1rem 2rem;
  display: flex;
  align-items: center;
  gap: 2rem;
  border: 1px solid #e9ecef;
}

.selected-count {
  font-weight: 600;
  color: #2c3e50;
}

.bulk-buttons {
  display: flex;
  gap: 1rem;
}

.bulk-buttons .btn {
  padding: 0.5rem 1rem;
  font-size: 0.9rem;
}

/* Modals */
.modal {
  background: rgba(0, 0, 0, 0.5);
}

.modal-dialog {
  margin: 2rem auto;
}

.modal-content {
  border-radius: 15px;
  border: none;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
  border-radius: 15px 15px 0 0;
  padding: 1.5rem;
}

.modal-title {
  font-weight: 600;
  color: #2c3e50;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-body {
  padding: 2rem;
}

.modal-footer {
  background: #f8f9fa;
  border-top: 1px solid #e9ecef;
  border-radius: 0 0 15px 15px;
  padding: 1.5rem;
}

/* Member Profile in Modal */
.member-profile {
  text-align: center;
  padding: 2rem;
  background: #f8f9fa;
  border-radius: 15px;
}

.profile-avatar {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  object-fit: cover;
  border: 5px solid white;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  margin-bottom: 1rem;
}

.member-profile h4 {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.member-profile .member-id {
  font-size: 0.9rem;
  color: #6c757d;
  margin-bottom: 1rem;
}

.member-details {
  padding-left: 2rem;
}

.detail-section {
  margin-bottom: 2rem;
}

.detail-section h6 {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  border-bottom: 2px solid #e9ecef;
  padding-bottom: 0.5rem;
}

.detail-section i {
  color: #667eea;
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

.detail-item label {
  font-weight: 600;
  color: #6c757d;
  font-size: 0.9rem;
}

.detail-item span {
  color: #2c3e50;
  font-weight: 500;
}

/* Form Styles */
.form-label {
  font-weight: 600;
  color: #ffffff;
  margin-bottom: 0.5rem;
}

.form-control,
.form-select {
  border-radius: 10px;
  border: 1px solid #e9ecef;
  padding: 0.75rem 1rem;
  transition: all 0.3s ease;
}

.form-control:focus,
.form-select:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: #6c757d;
}

.empty-state i {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-state p {
  margin: 0;
  font-size: 1.1rem;
}

/* Success Toast */
.success-toast {
  position: fixed;
  top: 2rem;
  right: 2rem;
  background: #28a745;
  color: white;
  padding: 1rem 1.5rem;
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
  z-index: 1050;
  animation: slideInRight 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

@keyframes slideInRight {
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
@media (max-width: 1200px) {
  .bulk-actions-bar {
    flex-direction: column;
    gap: 1rem;
    padding: 1.5rem;
  }

  .bulk-buttons {
    flex-wrap: wrap;
    justify-content: center;
  }
}

@media (max-width: 768px) {
  .members-header {
    padding: 1.5rem 0;
  }

  .members-header h1 {
    font-size: 2rem;
  }

  .header-actions {
    flex-direction: column;
    align-items: stretch;
    gap: 0.5rem;
  }

  .stats-card .card-body {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }

  .stats-icon {
    width: 60px;
    height: 60px;
    font-size: 1.5rem;
  }

  .stats-content h3 {
    font-size: 2rem;
  }

  .filters-section .row {
    gap: 1rem;
  }

  .filters-section .col-md-3,
  .filters-section .col-md-2,
  .filters-section .col-md-1 {
    width: 100%;
    margin-bottom: 1rem;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .view-toggle {
    align-self: stretch;
  }

  .view-toggle .btn {
    flex: 1;
  }

  .table-responsive {
    font-size: 0.9rem;
  }

  .member-info {
    flex-direction: column;
    text-align: center;
    gap: 0.5rem;
  }

  .member-avatar {
    width: 40px;
    height: 40px;
  }

  .action-buttons {
    flex-direction: column;
    gap: 0.25rem;
  }

  .action-buttons .btn {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
  }

  .member-details {
    padding-left: 0;
    margin-top: 2rem;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }

  .bulk-actions {
    bottom: 1rem;
    left: 1rem;
    right: 1rem;
    transform: none;
  }

  .success-toast {
    top: 1rem;
    right: 1rem;
    left: 1rem;
  }
}

@media (max-width: 576px) {
  .members-header h1 {
    font-size: 1.75rem;
  }

  .stats-content h3 {
    font-size: 1.75rem;
  }

  .btn {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
  }

  .member-card-body {
    padding: 1rem;
  }

  .member-card-avatar {
    width: 60px;
    height: 60px;
  }

  .modal-dialog {
    margin: 1rem;
  }

  .modal-body {
    padding: 1rem;
  }

  .member-profile {
    padding: 1rem;
  }

  .profile-avatar {
    width: 80px;
    height: 80px;
  }
}

/* Print Styles */
@media print {
  .admin-members {
    background: white !important;
  }

  .header-actions,
  .filters-section,
  .view-toggle,
  .action-buttons,
  .bulk-actions,
  .success-toast,
  .pagination-section {
    display: none !important;
  }

  .stats-card,
  .members-section .card {
    box-shadow: none !important;
    border: 1px solid #ddd !important;
    break-inside: avoid;
  }

  .table {
    font-size: 0.8rem;
  }

  .member-card {
    box-shadow: none !important;
    border: 1px solid #ddd !important;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .admin-members {
    background: #1a1a1a;
  }

  .stats-card,
  .filters-section .card,
  .members-section .card,
  .member-card,
  .bulk-actions-bar,
  .modal-content {
    background: #2d2d2d;
    color: #f8f9fa;
  }

  .card-header,
  .modal-header,
  .modal-footer,
  .member-profile,
  .member-card-footer {
    background: #3d3d3d;
    border-color: #4d4d4d;
  }

  .table th {
    background: #3d3d3d;
    color: #f8f9fa;
  }

  .table tbody tr:hover {
    background: #3d3d3d;
  }

  .form-control,
  .form-select {
    background: #3d3d3d;
    border-color: #4d4d4d;
    color: #f8f9fa;
  }

  .page-link {
    background: #2d2d2d;
    color: #f8f9fa;
  }

  .page-link:hover {
    background: #667eea;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Focus Styles for Accessibility */
.btn:focus,
.form-control:focus,
.form-select:focus,
.page-link:focus {
  outline: 2px solid #667eea;
  outline-offset: 2px;
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .stats-card,
  .filters-section .card,
  .members-section .card,
  .member-card,
  .modal-content {
    border: 2px solid #000;
  }

  .btn-primary {
    background: #000;
    border: 2px solid #fff;
  }

  .btn-outline-primary {
    border-color: #000;
    color: #000;
  }

  .status-badge {
    border: 1px solid #000;
  }
}
</style>