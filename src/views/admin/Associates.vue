<template>
  <div class="associates-management">
    <!-- Header Section -->
    <div class="page-header">
      <div class="header-content">
        <h1 class="page-title">
          <i class="fas fa-users"></i>
          Associate Members
        </h1>
        <p class="page-description">Manage associate members and their applications</p>
      </div>
      <div class="header-actions">
        <button @click="exportData" class="btn btn-secondary">
          <i class="fas fa-download"></i>
          Export
        </button>
        <button @click="showAddModal = true" class="btn btn-primary">
          <i class="fas fa-user-plus"></i>
          Add Associate
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.totalAssociates }}</h3>
          <p>Total Associates</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-user-check"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.activeAssociates }}</h3>
          <p>Active Associates</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.pendingApplications }}</h3>
          <p>Pending Applications</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon">
          <i class="fas fa-calendar-plus"></i>
        </div>
        <div class="stat-content">
          <h3>{{ stats.newThisMonth }}</h3>
          <p>New This Month</p>
        </div>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-section">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Search associates..."
          @input="filterAssociates"
        >
      </div>
      <div class="filter-controls">
        <select v-model="selectedStatus" @change="filterAssociates" class="filter-select">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="pending">Pending</option>
          <option value="suspended">Suspended</option>
        </select>
        <select v-model="selectedState" @change="filterAssociates" class="filter-select">
          <option value="">All States</option>
          <option value="Delhi">Delhi</option>
          <option value="Mumbai">Mumbai</option>
          <option value="Kolkata">Kolkata</option>
          <option value="Chennai">Chennai</option>
          <option value="Bangalore">Bangalore</option>
        </select>
        <select v-model="sortBy" @change="sortAssociates" class="filter-select">
          <option value="created_at">Latest</option>
          <option value="name">Name</option>
          <option value="membership_date">Membership Date</option>
          <option value="status">Status</option>
        </select>
      </div>
    </div>

    <!-- Associates Table -->
    <div class="table-container" v-if="!loading">
      <table class="associates-table">
        <thead>
          <tr>
            <th>
              <input 
                type="checkbox" 
                @change="toggleSelectAll"
                :checked="selectedAssociates.length === filteredAssociates.length && filteredAssociates.length > 0"
              >
            </th>
            <th>Associate</th>
            <th>Contact</th>
            <th>Location</th>
            <th>Membership Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="associate in paginatedAssociates" :key="associate.id" class="associate-row">
            <td>
              <input 
                type="checkbox" 
                :value="associate.id"
                v-model="selectedAssociates"
              >
            </td>
            <td>
              <div class="associate-info">
                <div class="associate-avatar">
                  <img 
                    :src="associate.profile_image || '/assets/images/placeholder-avatar.svg'" 
                    :alt="associate.name"
                    @error="handleImageError"
                  >
                </div>
                <div class="associate-details">
                  <h4>{{ associate.name }}</h4>
                  <p>{{ associate.profession || 'Not specified' }}</p>
                  <span class="member-id">ID: {{ associate.member_id }}</span>
                </div>
              </div>
            </td>
            <td>
              <div class="contact-info">
                <div class="contact-item">
                  <i class="fas fa-envelope"></i>
                  <span>{{ associate.email }}</span>
                </div>
                <div class="contact-item">
                  <i class="fas fa-phone"></i>
                  <span>{{ associate.phone }}</span>
                </div>
              </div>
            </td>
            <td>
              <div class="location-info">
                <div>{{ associate.city }}, {{ associate.state }}</div>
                <small>{{ associate.country }}</small>
              </div>
            </td>
            <td>
              <div class="date-info">
                <div>{{ formatDate(associate.membership_date) }}</div>
                <small>{{ getTimeAgo(associate.membership_date) }}</small>
              </div>
            </td>
            <td>
              <span :class="['status-badge', associate.status]">
                <i :class="getStatusIcon(associate.status)"></i>
                {{ associate.status }}
              </span>
            </td>
            <td>
              <div class="action-buttons">
                <button @click="viewAssociate(associate)" class="action-btn view" title="View Details">
                  <i class="fas fa-eye"></i>
                </button>
                <button @click="editAssociate(associate)" class="action-btn edit" title="Edit Associate">
                  <i class="fas fa-edit"></i>
                </button>
                <button @click="toggleStatus(associate)" class="action-btn toggle" :title="associate.status === 'active' ? 'Deactivate' : 'Activate'">
                  <i :class="associate.status === 'active' ? 'fas fa-pause' : 'fas fa-play'"></i>
                </button>
                <button @click="deleteAssociate(associate)" class="action-btn delete" title="Delete Associate">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="pagination-container" v-if="totalPages > 1">
      <div class="pagination-info">
        Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, filteredAssociates.length) }} of {{ filteredAssociates.length }} associates
      </div>
      <div class="pagination">
        <button 
          @click="currentPage = 1" 
          :disabled="currentPage === 1"
          class="pagination-btn"
        >
          <i class="fas fa-angle-double-left"></i>
        </button>
        <button 
          @click="currentPage--" 
          :disabled="currentPage === 1"
          class="pagination-btn"
        >
          <i class="fas fa-angle-left"></i>
        </button>
        <span class="pagination-current">{{ currentPage }} of {{ totalPages }}</span>
        <button 
          @click="currentPage++" 
          :disabled="currentPage === totalPages"
          class="pagination-btn"
        >
          <i class="fas fa-angle-right"></i>
        </button>
        <button 
          @click="currentPage = totalPages" 
          :disabled="currentPage === totalPages"
          class="pagination-btn"
        >
          <i class="fas fa-angle-double-right"></i>
        </button>
      </div>
    </div>

    <!-- Bulk Actions -->
    <div class="bulk-actions" v-if="selectedAssociates.length > 0">
      <div class="bulk-info">
        {{ selectedAssociates.length }} associate(s) selected
      </div>
      <div class="bulk-buttons">
        <button @click="bulkActivate" class="btn btn-success">
          <i class="fas fa-check"></i>
          Activate
        </button>
        <button @click="bulkDeactivate" class="btn btn-warning">
          <i class="fas fa-pause"></i>
          Deactivate
        </button>
        <button @click="bulkDelete" class="btn btn-danger">
          <i class="fas fa-trash"></i>
          Delete
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading associates...</p>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && filteredAssociates.length === 0" class="empty-state">
      <i class="fas fa-users"></i>
      <h3>No Associates Found</h3>
      <p>{{ searchQuery ? 'No associates match your search criteria.' : 'No associates have been added yet.' }}</p>
      <button @click="showAddModal = true" class="btn btn-primary">
        <i class="fas fa-user-plus"></i>
        Add First Associate
      </button>
    </div>

    <!-- Add/Edit Associate Modal -->
    <div v-if="showAddModal || showEditModal" class="modal-overlay" @click="closeModals">
      <div class="modal-content large" @click.stop>
        <div class="modal-header">
          <h2>{{ showEditModal ? 'Edit Associate' : 'Add New Associate' }}</h2>
          <button @click="closeModals" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <form @submit.prevent="saveAssociate" class="associate-form">
          <div class="form-sections">
            <!-- Personal Information -->
            <div class="form-section">
              <h3>Personal Information</h3>
              <div class="form-row">
                <div class="form-group">
                  <label for="name">Full Name *</label>
                  <input 
                    id="name"
                    v-model="associateForm.name" 
                    type="text" 
                    required
                    placeholder="Enter full name"
                  >
                </div>
                <div class="form-group">
                  <label for="email">Email *</label>
                  <input 
                    id="email"
                    v-model="associateForm.email" 
                    type="email" 
                    required
                    placeholder="Enter email address"
                  >
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label for="phone">Phone *</label>
                  <input 
                    id="phone"
                    v-model="associateForm.phone" 
                    type="tel" 
                    required
                    placeholder="Enter phone number"
                  >
                </div>
                <div class="form-group">
                  <label for="dateOfBirth">Date of Birth</label>
                  <input 
                    id="dateOfBirth"
                    v-model="associateForm.date_of_birth" 
                    type="date"
                  >
                </div>
              </div>
              <div class="form-group">
                <label for="profession">Profession</label>
                <input 
                  id="profession"
                  v-model="associateForm.profession" 
                  type="text"
                  placeholder="Enter profession"
                >
              </div>
            </div>

            <!-- Address Information -->
            <div class="form-section">
              <h3>Address Information</h3>
              <div class="form-group">
                <label for="address">Address *</label>
                <textarea 
                  id="address"
                  v-model="associateForm.address" 
                  rows="2"
                  required
                  placeholder="Enter complete address"
                ></textarea>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label for="city">City *</label>
                  <input 
                    id="city"
                    v-model="associateForm.city" 
                    type="text" 
                    required
                    placeholder="Enter city"
                  >
                </div>
                <div class="form-group">
                  <label for="state">State *</label>
                  <select id="state" v-model="associateForm.state" required>
                    <option value="">Select State</option>
                    <option value="Delhi">Delhi</option>
                    <option value="Mumbai">Mumbai</option>
                    <option value="Kolkata">Kolkata</option>
                    <option value="Chennai">Chennai</option>
                    <option value="Bangalore">Bangalore</option>
                  </select>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label for="pincode">Pincode *</label>
                  <input 
                    id="pincode"
                    v-model="associateForm.pincode" 
                    type="text" 
                    required
                    placeholder="Enter pincode"
                  >
                </div>
                <div class="form-group">
                  <label for="country">Country</label>
                  <input 
                    id="country"
                    v-model="associateForm.country" 
                    type="text"
                    placeholder="Enter country"
                  >
                </div>
              </div>
            </div>

            <!-- Membership Information -->
            <div class="form-section">
              <h3>Membership Information</h3>
              <div class="form-row">
                <div class="form-group">
                  <label for="membershipDate">Membership Date *</label>
                  <input 
                    id="membershipDate"
                    v-model="associateForm.membership_date" 
                    type="date" 
                    required
                  >
                </div>
                <div class="form-group">
                  <label for="status">Status</label>
                  <select id="status" v-model="associateForm.status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="pending">Pending</option>
                    <option value="suspended">Suspended</option>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label for="notes">Notes</label>
                <textarea 
                  id="notes"
                  v-model="associateForm.notes" 
                  rows="3"
                  placeholder="Enter any additional notes"
                ></textarea>
              </div>
            </div>

            <!-- Profile Image -->
            <div class="form-section">
              <h3>Profile Image</h3>
              <div class="form-group">
                <div class="file-upload-area">
                  <input 
                    type="file" 
                    @change="handleImageUpload"
                    accept="image/*"
                    class="file-input"
                  >
                  <div class="upload-placeholder">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Click to upload profile image</p>
                  </div>
                </div>
                <div v-if="associateForm.profile_image_preview" class="image-preview">
                  <img :src="associateForm.profile_image_preview" alt="Profile preview">
                  <button type="button" @click="removeImage" class="remove-image">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="form-actions">
            <button type="button" @click="closeModals" class="btn btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <i class="fas fa-save"></i>
              {{ saving ? 'Saving...' : (showEditModal ? 'Update Associate' : 'Add Associate') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click="showDeleteModal = false">
      <div class="modal-content small" @click.stop>
        <div class="modal-header">
          <h2>Delete Associate</h2>
          <button @click="showDeleteModal = false" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete "{{ associateToDelete?.name }}"?</p>
          <p class="warning-text">This action cannot be undone.</p>
        </div>
        <div class="modal-actions">
          <button @click="showDeleteModal = false" class="btn btn-secondary">
            Cancel
          </button>
          <button @click="confirmDelete" class="btn btn-danger" :disabled="deleting">
            <i class="fas fa-trash"></i>
            {{ deleting ? 'Deleting...' : 'Delete Associate' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Associates',
  data() {
    return {
      loading: true,
      saving: false,
      deleting: false,
      associates: [],
      filteredAssociates: [],
      selectedAssociates: [],
      searchQuery: '',
      selectedStatus: '',
      selectedState: '',
      sortBy: 'created_at',
      currentPage: 1,
      itemsPerPage: 10,
      showAddModal: false,
      showEditModal: false,
      showDeleteModal: false,
      associateToDelete: null,
      editingAssociate: null,
      stats: {
        totalAssociates: 0,
        activeAssociates: 0,
        pendingApplications: 0,
        newThisMonth: 0
      },
      associateForm: {
        name: '',
        email: '',
        phone: '',
        date_of_birth: '',
        profession: '',
        address: '',
        city: '',
        state: '',
        pincode: '',
        country: 'India',
        membership_date: '',
        status: 'active',
        notes: '',
        profile_image: null,
        profile_image_preview: null
      }
    }
  },
  computed: {
    paginatedAssociates() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredAssociates.slice(start, end)
    },
    totalPages() {
      return Math.ceil(this.filteredAssociates.length / this.itemsPerPage)
    }
  },
  mounted() {
    this.loadAssociates()
    this.loadStats()
  },
  methods: {
    async loadAssociates() {
      try {
        this.loading = true
        // Mock data for demonstration
        this.associates = [
          {
            id: 1,
            member_id: 'BHRC-A001',
            name: 'Rajesh Kumar',
            email: 'rajesh.kumar@email.com',
            phone: '+91-9876543210',
            profession: 'Lawyer',
            address: '123 Legal Street, Central Delhi',
            city: 'Delhi',
            state: 'Delhi',
            pincode: '110001',
            country: 'India',
            membership_date: '2024-01-15T00:00:00Z',
            status: 'active',
            profile_image: null,
            notes: 'Specializes in human rights law',
            created_at: '2024-01-15T10:00:00Z'
          },
          {
            id: 2,
            member_id: 'BHRC-A002',
            name: 'Priya Sharma',
            email: 'priya.sharma@email.com',
            phone: '+91-9876543211',
            profession: 'Social Worker',
            address: '456 Service Road, Andheri',
            city: 'Mumbai',
            state: 'Mumbai',
            pincode: '400058',
            country: 'India',
            membership_date: '2024-01-10T00:00:00Z',
            status: 'active',
            profile_image: null,
            notes: 'Active in community outreach programs',
            created_at: '2024-01-10T14:30:00Z'
          },
          {
            id: 3,
            member_id: 'BHRC-A003',
            name: 'Amit Patel',
            email: 'amit.patel@email.com',
            phone: '+91-9876543212',
            profession: 'Journalist',
            address: '789 Press Colony, Salt Lake',
            city: 'Kolkata',
            state: 'Kolkata',
            pincode: '700064',
            country: 'India',
            membership_date: '2024-01-05T00:00:00Z',
            status: 'pending',
            profile_image: null,
            notes: 'Investigative journalist focusing on human rights',
            created_at: '2024-01-05T09:15:00Z'
          }
        ]
        this.filteredAssociates = [...this.associates]
      } catch (error) {
        console.error('Error loading associates:', error)
        this.$toast?.error('Failed to load associates')
      } finally {
        this.loading = false
      }
    },

    async loadStats() {
      try {
        // Mock stats data
        this.stats = {
          totalAssociates: 25,
          activeAssociates: 20,
          pendingApplications: 3,
          newThisMonth: 5
        }
      } catch (error) {
        console.error('Error loading stats:', error)
      }
    },

    filterAssociates() {
      let filtered = [...this.associates]

      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(associate => 
          associate.name.toLowerCase().includes(query) ||
          associate.email.toLowerCase().includes(query) ||
          associate.phone.includes(query) ||
          associate.member_id.toLowerCase().includes(query) ||
          associate.profession?.toLowerCase().includes(query)
        )
      }

      if (this.selectedStatus) {
        filtered = filtered.filter(associate => associate.status === this.selectedStatus)
      }

      if (this.selectedState) {
        filtered = filtered.filter(associate => associate.state === this.selectedState)
      }

      this.filteredAssociates = filtered
      this.currentPage = 1
      this.sortAssociates()
    },

    sortAssociates() {
      this.filteredAssociates.sort((a, b) => {
        switch (this.sortBy) {
          case 'name':
            return a.name.localeCompare(b.name)
          case 'membership_date':
            return new Date(b.membership_date) - new Date(a.membership_date)
          case 'status':
            return a.status.localeCompare(b.status)
          case 'created_at':
          default:
            return new Date(b.created_at) - new Date(a.created_at)
        }
      })
    },

    toggleSelectAll() {
      if (this.selectedAssociates.length === this.filteredAssociates.length) {
        this.selectedAssociates = []
      } else {
        this.selectedAssociates = this.filteredAssociates.map(a => a.id)
      }
    },

    viewAssociate(associate) {
      // Navigate to associate detail view
      this.$router.push(`/admin/associates/${associate.id}`)
    },

    editAssociate(associate) {
      this.editingAssociate = associate
      this.associateForm = {
        name: associate.name,
        email: associate.email,
        phone: associate.phone,
        date_of_birth: associate.date_of_birth || '',
        profession: associate.profession || '',
        address: associate.address,
        city: associate.city,
        state: associate.state,
        pincode: associate.pincode,
        country: associate.country,
        membership_date: associate.membership_date.split('T')[0],
        status: associate.status,
        notes: associate.notes || '',
        profile_image: null,
        profile_image_preview: associate.profile_image
      }
      this.showEditModal = true
    },

    deleteAssociate(associate) {
      this.associateToDelete = associate
      this.showDeleteModal = true
    },

    async confirmDelete() {
      try {
        this.deleting = true
        // API call would go here
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        this.associates = this.associates.filter(a => a.id !== this.associateToDelete.id)
        this.filterAssociates()
        this.$toast?.success('Associate deleted successfully')
        this.showDeleteModal = false
        this.associateToDelete = null
      } catch (error) {
        console.error('Error deleting associate:', error)
        this.$toast?.error('Failed to delete associate')
      } finally {
        this.deleting = false
      }
    },

    async toggleStatus(associate) {
      try {
        const newStatus = associate.status === 'active' ? 'inactive' : 'active'
        // API call would go here
        await new Promise(resolve => setTimeout(resolve, 500))
        
        associate.status = newStatus
        this.$toast?.success(`Associate ${newStatus === 'active' ? 'activated' : 'deactivated'} successfully`)
      } catch (error) {
        console.error('Error updating status:', error)
        this.$toast?.error('Failed to update status')
      }
    },

    async saveAssociate() {
      try {
        this.saving = true
        
        // API call would go here
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        if (this.showEditModal) {
          // Update existing associate
          const index = this.associates.findIndex(a => a.id === this.editingAssociate.id)
          if (index !== -1) {
            this.associates[index] = {
              ...this.associates[index],
              ...this.associateForm,
              profile_image: this.associateForm.profile_image_preview
            }
          }
          this.$toast?.success('Associate updated successfully')
        } else {
          // Create new associate
          const newAssociate = {
            id: Date.now(),
            member_id: `BHRC-A${String(this.associates.length + 1).padStart(3, '0')}`,
            ...this.associateForm,
            profile_image: this.associateForm.profile_image_preview,
            created_at: new Date().toISOString()
          }
          this.associates.unshift(newAssociate)
          this.$toast?.success('Associate added successfully')
        }
        
        this.filterAssociates()
        this.closeModals()
      } catch (error) {
        console.error('Error saving associate:', error)
        this.$toast?.error('Failed to save associate')
      } finally {
        this.saving = false
      }
    },

    handleImageUpload(event) {
      const file = event.target.files[0]
      if (file) {
        this.associateForm.profile_image = file
        const reader = new FileReader()
        reader.onload = (e) => {
          this.associateForm.profile_image_preview = e.target.result
        }
        reader.readAsDataURL(file)
      }
    },

    removeImage() {
      this.associateForm.profile_image = null
      this.associateForm.profile_image_preview = null
    },

    closeModals() {
      this.showAddModal = false
      this.showEditModal = false
      this.editingAssociate = null
      this.associateForm = {
        name: '',
        email: '',
        phone: '',
        date_of_birth: '',
        profession: '',
        address: '',
        city: '',
        state: '',
        pincode: '',
        country: 'India',
        membership_date: '',
        status: 'active',
        notes: '',
        profile_image: null,
        profile_image_preview: null
      }
    },

    async bulkActivate() {
      try {
        // API call would go here
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        this.associates.forEach(associate => {
          if (this.selectedAssociates.includes(associate.id)) {
            associate.status = 'active'
          }
        })
        
        this.selectedAssociates = []
        this.$toast?.success('Associates activated successfully')
      } catch (error) {
        console.error('Error activating associates:', error)
        this.$toast?.error('Failed to activate associates')
      }
    },

    async bulkDeactivate() {
      try {
        // API call would go here
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        this.associates.forEach(associate => {
          if (this.selectedAssociates.includes(associate.id)) {
            associate.status = 'inactive'
          }
        })
        
        this.selectedAssociates = []
        this.$toast?.success('Associates deactivated successfully')
      } catch (error) {
        console.error('Error deactivating associates:', error)
        this.$toast?.error('Failed to deactivate associates')
      }
    },

    async bulkDelete() {
      if (!confirm(`Are you sure you want to delete ${this.selectedAssociates.length} associate(s)?`)) {
        return
      }
      
      try {
        // API call would go here
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        this.associates = this.associates.filter(associate => 
          !this.selectedAssociates.includes(associate.id)
        )
        
        this.selectedAssociates = []
        this.filterAssociates()
        this.$toast?.success('Associates deleted successfully')
      } catch (error) {
        console.error('Error deleting associates:', error)
        this.$toast?.error('Failed to delete associates')
      }
    },

    exportData() {
      // Export functionality would go here
      this.$toast?.info('Export functionality will be implemented')
    },

    getStatusIcon(status) {
      switch (status) {
        case 'active': return 'fas fa-check-circle'
        case 'inactive': return 'fas fa-pause-circle'
        case 'pending': return 'fas fa-clock'
        case 'suspended': return 'fas fa-ban'
        default: return 'fas fa-question-circle'
      }
    },

    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },

    getTimeAgo(dateString) {
      const now = new Date()
      const date = new Date(dateString)
      const diffTime = Math.abs(now - date)
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      
      if (diffDays === 1) return '1 day ago'
      if (diffDays < 30) return `${diffDays} days ago`
      if (diffDays < 365) return `${Math.floor(diffDays / 30)} months ago`
      return `${Math.floor(diffDays / 365)} years ago`
    },

    handleImageError(event) {
      event.target.src = '/assets/images/placeholder-avatar.svg'
    }
  }
}
</script>

<style scoped>
.associates-management {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.header-content h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
}

.header-content h1 i {
  margin-right: 0.5rem;
  color: #3b82f6;
}

.page-description {
  color: #6b7280;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
}

.stat-content h3 {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.stat-content p {
  color: #6b7280;
  margin: 0;
  font-size: 0.875rem;
}

.filters-section {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
  display: flex;
  gap: 1rem;
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
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #6b7280;
}

.search-box input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
}

.filter-controls {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.filter-select {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
  min-width: 120px;
}

.table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  margin-bottom: 2rem;
}

.associates-table {
  width: 100%;
  border-collapse: collapse;
}

.associates-table th {
  background: #f9fafb;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 1px solid #e5e7eb;
}

.associates-table td {
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
}

.associate-row:hover {
  background: #f9fafb;
}

.associate-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.associate-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
}

.associate-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.associate-details h4 {
  margin: 0 0 0.25rem 0;
  font-weight: 600;
  color: #1f2937;
}

.associate-details p {
  margin: 0 0 0.25rem 0;
  color: #6b7280;
  font-size: 0.875rem;
}

.member-id {
  font-size: 0.75rem;
  color: #9ca3af;
}

.contact-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.contact-item i {
  width: 12px;
  color: #9ca3af;
}

.location-info {
  font-size: 0.875rem;
  color: #6b7280;
}

.location-info small {
  color: #9ca3af;
}

.date-info {
  font-size: 0.875rem;
  color: #6b7280;
}

.date-info small {
  color: #9ca3af;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
}

.status-badge.active {
  background: #dcfce7;
  color: #166534;
}

.status-badge.inactive {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.pending {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.suspended {
  background: #fee2e2;
  color: #dc2626;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.action-btn.view {
  background: #dbeafe;
  color: #1e40af;
}

.action-btn.view:hover {
  background: #bfdbfe;
}

.action-btn.edit {
  background: #fef3c7;
  color: #92400e;
}

.action-btn.edit:hover {
  background: #fde68a;
}

.action-btn.toggle {
  background: #f3e8ff;
  color: #7c3aed;
}

.action-btn.toggle:hover {
  background: #e9d5ff;
}

.action-btn.delete {
  background: #fee2e2;
  color: #dc2626;
}

.action-btn.delete:hover {
  background: #fecaca;
}

.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding: 1rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.pagination-info {
  color: #6b7280;
  font-size: 0.875rem;
}

.pagination {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.pagination-btn {
  width: 36px;
  height: 36px;
  border: 1px solid #d1d5db;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  background: #f3f4f6;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-current {
  padding: 0 1rem;
  font-weight: 500;
  color: #374151;
}

.bulk-actions {
  position: fixed;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%);
  background: white;
  padding: 1rem 2rem;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  gap: 2rem;
  z-index: 100;
}

.bulk-info {
  font-weight: 500;
  color: #374151;
}

.bulk-buttons {
  display: flex;
  gap: 1rem;
}

.loading-state, .empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #6b7280;
}

.loading-state .spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.empty-state i {
  font-size: 4rem;
  color: #d1d5db;
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.5rem;
  color: #374151;
  margin-bottom: 0.5rem;
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
  border-radius: 12px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-content.large {
  max-width: 800px;
}

.modal-content.small {
  max-width: 400px;
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
  color: #1f2937;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #6b7280;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 4px;
}

.close-btn:hover {
  background: #f3f4f6;
}

.associate-form {
  padding: 1.5rem;
}

.form-sections {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.form-section {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1.5rem;
}

.form-section h3 {
  margin: 0 0 1rem 0;
  color: #374151;
  font-size: 1.125rem;
  font-weight: 600;
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 0.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #374151;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.875rem;
}

.form-group textarea {
  resize: vertical;
}

.file-upload-area {
  position: relative;
  border: 2px dashed #d1d5db;
  border-radius: 8px;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: border-color 0.2s;
}

.file-upload-area:hover {
  border-color: #3b82f6;
}

.file-input {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
}

.upload-placeholder i {
  font-size: 2rem;
  color: #9ca3af;
  margin-bottom: 0.5rem;
}

.image-preview {
  position: relative;
  margin-top: 1rem;
  display: inline-block;
}

.image-preview img {
  width: 120px;
  height: 120px;
  object-fit: cover;
  border-radius: 8px;
}

.remove-image {
  position: absolute;
  top: -8px;
  right: -8px;
  width: 24px;
  height: 24px;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
}

.form-actions,
.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
  margin-top: 1rem;
}

.modal-body {
  padding: 1.5rem;
}

.warning-text {
  color: #dc2626;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.btn:disabled {
  opacity: 0.6;
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
  background: #f3f4f6;
  color: #374151;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.btn-success {
  background: #10b981;
  color: white;
}

.btn-success:hover {
  background: #059669;
}

.btn-warning {
  background: #f59e0b;
  color: white;
}

.btn-warning:hover {
  background: #d97706;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626;
}

@media (max-width: 768px) {
  .associates-management {
    padding: 1rem;
  }
  
  .page-header {
    flex-direction: column;
    gap: 1rem;
  }
  
  .header-actions {
    width: 100%;
    justify-content: stretch;
  }
  
  .header-actions .btn {
    flex: 1;
  }
  
  .filters-section {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-box {
    min-width: auto;
  }
  
  .filter-controls {
    justify-content: stretch;
  }
  
  .filter-select {
    flex: 1;
    min-width: auto;
  }
  
  .table-container {
    overflow-x: auto;
  }
  
  .associates-table {
    min-width: 800px;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .modal-content {
    width: 95%;
    margin: 1rem;
  }
  
  .bulk-actions {
    left: 1rem;
    right: 1rem;
    transform: none;
    flex-direction: column;
    gap: 1rem;
  }
  
  .bulk-buttons {
    width: 100%;
    justify-content: stretch;
  }
  
  .bulk-buttons .btn {
    flex: 1;
  }
}
</style>