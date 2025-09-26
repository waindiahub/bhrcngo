<template>
  <div class="admin-donations">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <h1><i class="fas fa-heart"></i> Donations Management</h1>
        <p class="text-muted">Manage and track all donations received by the organization</p>
      </div>
      <div class="header-actions">
        <button @click="exportDonations" class="btn btn-outline-primary me-2" :disabled="exporting">
          <i class="fas fa-download"></i>
          <span v-if="exporting">Exporting...</span>
          <span v-else>Export</span>
        </button>
        <button @click="showAddModal = true" class="btn btn-primary">
          <i class="fas fa-plus"></i>
          Add Donation
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-section">
      <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="stats-card total">
            <div class="stats-icon">
              <i class="fas fa-rupee-sign"></i>
            </div>
            <div class="stats-content">
              <h3>₹{{ formatCurrency(stats.totalAmount) }}</h3>
              <p>Total Donations</p>
              <small class="text-success">
                <i class="fas fa-arrow-up"></i>
                +{{ stats.monthlyGrowth }}% this month
              </small>
            </div>
          </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="stats-card count">
            <div class="stats-icon">
              <i class="fas fa-hand-holding-heart"></i>
            </div>
            <div class="stats-content">
              <h3>{{ stats.totalDonations }}</h3>
              <p>Total Donations</p>
              <small class="text-info">
                {{ stats.newDonations }} new this week
              </small>
            </div>
          </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="stats-card donors">
            <div class="stats-icon">
              <i class="fas fa-users"></i>
            </div>
            <div class="stats-content">
              <h3>{{ stats.totalDonors }}</h3>
              <p>Unique Donors</p>
              <small class="text-primary">
                {{ stats.recurringDonors }} recurring
              </small>
            </div>
          </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="stats-card average">
            <div class="stats-icon">
              <i class="fas fa-chart-line"></i>
            </div>
            <div class="stats-content">
              <h3>₹{{ formatCurrency(stats.averageAmount) }}</h3>
              <p>Average Donation</p>
              <small class="text-warning">
                Per donation amount
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-section">
      <div class="row align-items-center">
        <div class="col-md-4">
          <div class="search-box">
            <i class="fas fa-search"></i>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Search donations..." 
              class="form-control"
            >
          </div>
        </div>
        <div class="col-md-8">
          <div class="filters">
            <select v-model="filterStatus" class="form-control me-2">
              <option value="">All Status</option>
              <option value="completed">Completed</option>
              <option value="pending">Pending</option>
              <option value="failed">Failed</option>
              <option value="refunded">Refunded</option>
            </select>
            
            <select v-model="filterType" class="form-control me-2">
              <option value="">All Types</option>
              <option value="online">Online</option>
              <option value="cash">Cash</option>
              <option value="cheque">Cheque</option>
              <option value="bank_transfer">Bank Transfer</option>
            </select>
            
            <input 
              type="date" 
              v-model="filterDateFrom" 
              class="form-control me-2"
              placeholder="From Date"
            >
            
            <input 
              type="date" 
              v-model="filterDateTo" 
              class="form-control me-2"
              placeholder="To Date"
            >
            
            <button @click="clearFilters" class="btn btn-outline-secondary">
              <i class="fas fa-times"></i>
              Clear
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Donations Table -->
    <div class="table-section">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>
                <input type="checkbox" v-model="selectAll" @change="toggleSelectAll">
              </th>
              <th @click="sortBy('id')" class="sortable">
                ID
                <i class="fas fa-sort" :class="getSortIcon('id')"></i>
              </th>
              <th @click="sortBy('donor_name')" class="sortable">
                Donor
                <i class="fas fa-sort" :class="getSortIcon('donor_name')"></i>
              </th>
              <th @click="sortBy('amount')" class="sortable">
                Amount
                <i class="fas fa-sort" :class="getSortIcon('amount')"></i>
              </th>
              <th @click="sortBy('type')" class="sortable">
                Type
                <i class="fas fa-sort" :class="getSortIcon('type')"></i>
              </th>
              <th @click="sortBy('status')" class="sortable">
                Status
                <i class="fas fa-sort" :class="getSortIcon('status')"></i>
              </th>
              <th @click="sortBy('created_at')" class="sortable">
                Date
                <i class="fas fa-sort" :class="getSortIcon('created_at')"></i>
              </th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="text-center py-4">
                <div class="loading-spinner"></div>
                Loading donations...
              </td>
            </tr>
            <tr v-else-if="paginatedDonations.length === 0">
              <td colspan="8" class="text-center py-4">
                <div class="empty-state">
                  <i class="fas fa-heart text-muted"></i>
                  <p class="text-muted mt-2">No donations found</p>
                </div>
              </td>
            </tr>
            <tr v-else v-for="donation in paginatedDonations" :key="donation.id">
              <td>
                <input type="checkbox" v-model="selectedDonations" :value="donation.id">
              </td>
              <td>
                <span class="donation-id">#{{ donation.id }}</span>
              </td>
              <td>
                <div class="donor-info">
                  <strong>{{ donation.donor_name }}</strong>
                  <br>
                  <small class="text-muted">{{ donation.donor_email }}</small>
                  <br>
                  <small class="text-muted">{{ donation.donor_phone }}</small>
                </div>
              </td>
              <td>
                <span class="amount">₹{{ formatCurrency(donation.amount) }}</span>
              </td>
              <td>
                <span class="badge" :class="getTypeClass(donation.type)">
                  {{ getTypeLabel(donation.type) }}
                </span>
              </td>
              <td>
                <span class="badge" :class="getStatusClass(donation.status)">
                  {{ getStatusLabel(donation.status) }}
                </span>
              </td>
              <td>
                <div class="date-info">
                  {{ formatDate(donation.created_at) }}
                  <br>
                  <small class="text-muted">{{ formatTime(donation.created_at) }}</small>
                </div>
              </td>
              <td>
                <div class="action-buttons">
                  <button @click="viewDonation(donation)" class="btn btn-sm btn-outline-primary" title="View Details">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button @click="editDonation(donation)" class="btn btn-sm btn-outline-warning" title="Edit">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button @click="generateReceipt(donation)" class="btn btn-sm btn-outline-success" title="Generate Receipt">
                    <i class="fas fa-receipt"></i>
                  </button>
                  <button @click="deleteDonation(donation)" class="btn btn-sm btn-outline-danger" title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-section" v-if="totalPages > 1">
      <nav>
        <ul class="pagination justify-content-center">
          <li class="page-item" :class="{ disabled: currentPage === 1 }">
            <button class="page-link" @click="changePage(currentPage - 1)" :disabled="currentPage === 1">
              <i class="fas fa-chevron-left"></i>
            </button>
          </li>
          
          <li v-for="page in visiblePages" :key="page" class="page-item" :class="{ active: page === currentPage }">
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

    <!-- Add/Edit Donation Modal -->
    <div class="modal fade" :class="{ show: showAddModal }" :style="{ display: showAddModal ? 'block' : 'none' }" v-if="showAddModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-plus"></i>
              {{ editingDonation ? 'Edit Donation' : 'Add New Donation' }}
            </h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveDonation">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Donor Name *</label>
                    <input 
                      type="text" 
                      v-model="donationForm.donor_name" 
                      class="form-control" 
                      :class="{ 'is-invalid': errors.donor_name }"
                      required
                    >
                    <div v-if="errors.donor_name" class="invalid-feedback">{{ errors.donor_name }}</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input 
                      type="email" 
                      v-model="donationForm.donor_email" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.donor_email }"
                    >
                    <div v-if="errors.donor_email" class="invalid-feedback">{{ errors.donor_email }}</div>
                  </div>
                </div>
              </div>
              
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input 
                      type="tel" 
                      v-model="donationForm.donor_phone" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.donor_phone }"
                    >
                    <div v-if="errors.donor_phone" class="invalid-feedback">{{ errors.donor_phone }}</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Amount *</label>
                    <input 
                      type="number" 
                      v-model="donationForm.amount" 
                      class="form-control" 
                      :class="{ 'is-invalid': errors.amount }"
                      min="1"
                      step="0.01"
                      required
                    >
                    <div v-if="errors.amount" class="invalid-feedback">{{ errors.amount }}</div>
                  </div>
                </div>
              </div>
              
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Donation Type *</label>
                    <select 
                      v-model="donationForm.type" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.type }"
                      required
                    >
                      <option value="">Select Type</option>
                      <option value="online">Online</option>
                      <option value="cash">Cash</option>
                      <option value="cheque">Cheque</option>
                      <option value="bank_transfer">Bank Transfer</option>
                    </select>
                    <div v-if="errors.type" class="invalid-feedback">{{ errors.type }}</div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Status *</label>
                    <select 
                      v-model="donationForm.status" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.status }"
                      required
                    >
                      <option value="">Select Status</option>
                      <option value="completed">Completed</option>
                      <option value="pending">Pending</option>
                      <option value="failed">Failed</option>
                      <option value="refunded">Refunded</option>
                    </select>
                    <div v-if="errors.status" class="invalid-feedback">{{ errors.status }}</div>
                  </div>
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Purpose/Notes</label>
                <textarea 
                  v-model="donationForm.purpose" 
                  class="form-control" 
                  rows="3"
                  placeholder="Purpose of donation or additional notes..."
                ></textarea>
              </div>
              
              <div class="row" v-if="donationForm.type === 'cheque'">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Cheque Number</label>
                    <input 
                      type="text" 
                      v-model="donationForm.cheque_number" 
                      class="form-control"
                    >
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Bank Name</label>
                    <input 
                      type="text" 
                      v-model="donationForm.bank_name" 
                      class="form-control"
                    >
                  </div>
                </div>
              </div>
              
              <div class="row" v-if="donationForm.type === 'online'">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Transaction ID</label>
                    <input 
                      type="text" 
                      v-model="donationForm.transaction_id" 
                      class="form-control"
                    >
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Payment Gateway</label>
                    <select v-model="donationForm.payment_gateway" class="form-control">
                      <option value="">Select Gateway</option>
                      <option value="razorpay">Razorpay</option>
                      <option value="paytm">Paytm</option>
                      <option value="phonepe">PhonePe</option>
                      <option value="gpay">Google Pay</option>
                    </select>
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="saveDonation" :disabled="saving">
              <span v-if="saving">
                <i class="fas fa-spinner fa-spin"></i>
                Saving...
              </span>
              <span v-else>
                <i class="fas fa-save"></i>
                {{ editingDonation ? 'Update' : 'Save' }} Donation
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AdminDonations',
  data() {
    return {
      loading: false,
      saving: false,
      exporting: false,
      showAddModal: false,
      editingDonation: null,
      searchQuery: '',
      filterStatus: '',
      filterType: '',
      filterDateFrom: '',
      filterDateTo: '',
      sortField: 'created_at',
      sortDirection: 'desc',
      currentPage: 1,
      itemsPerPage: 10,
      selectAll: false,
      selectedDonations: [],
      
      stats: {
        totalAmount: 2450000,
        totalDonations: 1247,
        totalDonors: 892,
        averageAmount: 1965,
        monthlyGrowth: 12.5,
        newDonations: 23,
        recurringDonors: 156
      },
      
      donations: [
        {
          id: 1,
          donor_name: 'Rajesh Kumar',
          donor_email: 'rajesh@email.com',
          donor_phone: '+91 9876543210',
          amount: 5000,
          type: 'online',
          status: 'completed',
          purpose: 'Education Support',
          transaction_id: 'TXN123456789',
          payment_gateway: 'razorpay',
          created_at: '2024-01-15 10:30:00'
        },
        {
          id: 2,
          donor_name: 'Priya Sharma',
          donor_email: 'priya@email.com',
          donor_phone: '+91 9876543211',
          amount: 2500,
          type: 'cash',
          status: 'completed',
          purpose: 'Medical Aid',
          created_at: '2024-01-14 14:20:00'
        },
        {
          id: 3,
          donor_name: 'Amit Patel',
          donor_email: 'amit@email.com',
          donor_phone: '+91 9876543212',
          amount: 10000,
          type: 'cheque',
          status: 'pending',
          purpose: 'Infrastructure Development',
          cheque_number: 'CHQ001234',
          bank_name: 'SBI Bank',
          created_at: '2024-01-13 09:15:00'
        }
      ],
      
      donationForm: {
        donor_name: '',
        donor_email: '',
        donor_phone: '',
        amount: '',
        type: '',
        status: '',
        purpose: '',
        cheque_number: '',
        bank_name: '',
        transaction_id: '',
        payment_gateway: ''
      },
      
      errors: {}
    }
  },
  
  computed: {
    filteredDonations() {
      let filtered = [...this.donations]
      
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(donation => 
          donation.donor_name.toLowerCase().includes(query) ||
          donation.donor_email.toLowerCase().includes(query) ||
          donation.donor_phone.includes(query) ||
          donation.purpose.toLowerCase().includes(query)
        )
      }
      
      if (this.filterStatus) {
        filtered = filtered.filter(donation => donation.status === this.filterStatus)
      }
      
      if (this.filterType) {
        filtered = filtered.filter(donation => donation.type === this.filterType)
      }
      
      if (this.filterDateFrom) {
        filtered = filtered.filter(donation => 
          new Date(donation.created_at) >= new Date(this.filterDateFrom)
        )
      }
      
      if (this.filterDateTo) {
        filtered = filtered.filter(donation => 
          new Date(donation.created_at) <= new Date(this.filterDateTo + ' 23:59:59')
        )
      }
      
      // Sort
      filtered.sort((a, b) => {
        let aVal = a[this.sortField]
        let bVal = b[this.sortField]
        
        if (this.sortField === 'amount') {
          aVal = parseFloat(aVal)
          bVal = parseFloat(bVal)
        }
        
        if (this.sortDirection === 'asc') {
          return aVal > bVal ? 1 : -1
        } else {
          return aVal < bVal ? 1 : -1
        }
      })
      
      return filtered
    },
    
    paginatedDonations() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredDonations.slice(start, end)
    },
    
    totalPages() {
      return Math.ceil(this.filteredDonations.length / this.itemsPerPage)
    },
    
    visiblePages() {
      const pages = []
      const total = this.totalPages
      const current = this.currentPage
      
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
    }
  },
  
  methods: {
    // Utility methods
    formatCurrency(amount) {
      return new Intl.NumberFormat('en-IN').format(amount)
    },
    
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('en-IN')
    },
    
    formatTime(dateString) {
      return new Date(dateString).toLocaleTimeString('en-IN', { 
        hour: '2-digit', 
        minute: '2-digit' 
      })
    },
    
    getStatusClass(status) {
      const classes = {
        completed: 'badge-success',
        pending: 'badge-warning',
        failed: 'badge-danger',
        refunded: 'badge-info'
      }
      return classes[status] || 'badge-secondary'
    },
    
    getStatusLabel(status) {
      const labels = {
        completed: 'Completed',
        pending: 'Pending',
        failed: 'Failed',
        refunded: 'Refunded'
      }
      return labels[status] || status
    },
    
    getTypeClass(type) {
      const classes = {
        online: 'badge-primary',
        cash: 'badge-success',
        cheque: 'badge-info',
        bank_transfer: 'badge-warning'
      }
      return classes[type] || 'badge-secondary'
    },
    
    getTypeLabel(type) {
      const labels = {
        online: 'Online',
        cash: 'Cash',
        cheque: 'Cheque',
        bank_transfer: 'Bank Transfer'
      }
      return labels[type] || type
    },
    
    getSortIcon(field) {
      if (this.sortField !== field) return ''
      return this.sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'
    },
    
    // Actions
    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc'
      } else {
        this.sortField = field
        this.sortDirection = 'asc'
      }
    },
    
    changePage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page
      }
    },
    
    clearFilters() {
      this.searchQuery = ''
      this.filterStatus = ''
      this.filterType = ''
      this.filterDateFrom = ''
      this.filterDateTo = ''
      this.currentPage = 1
    },
    
    toggleSelectAll() {
      if (this.selectAll) {
        this.selectedDonations = this.paginatedDonations.map(d => d.id)
      } else {
        this.selectedDonations = []
      }
    },
    
    viewDonation(donation) {
      // Implement view donation details
      console.log('View donation:', donation)
    },
    
    editDonation(donation) {
      this.editingDonation = donation
      this.donationForm = { ...donation }
      this.showAddModal = true
    },
    
    deleteDonation(donation) {
      if (confirm(`Are you sure you want to delete donation #${donation.id}?`)) {
        const index = this.donations.findIndex(d => d.id === donation.id)
        if (index > -1) {
          this.donations.splice(index, 1)
        }
      }
    },
    
    generateReceipt(donation) {
      // Implement receipt generation
      console.log('Generate receipt for:', donation)
    },
    
    exportDonations() {
      this.exporting = true
      // Simulate export
      setTimeout(() => {
        this.exporting = false
        alert('Donations exported successfully!')
      }, 2000)
    },
    
    closeModal() {
      this.showAddModal = false
      this.editingDonation = null
      this.resetForm()
    },
    
    resetForm() {
      this.donationForm = {
        donor_name: '',
        donor_email: '',
        donor_phone: '',
        amount: '',
        type: '',
        status: '',
        purpose: '',
        cheque_number: '',
        bank_name: '',
        transaction_id: '',
        payment_gateway: ''
      }
      this.errors = {}
    },
    
    validateForm() {
      this.errors = {}
      
      if (!this.donationForm.donor_name.trim()) {
        this.errors.donor_name = 'Donor name is required'
      }
      
      if (!this.donationForm.amount || this.donationForm.amount <= 0) {
        this.errors.amount = 'Valid amount is required'
      }
      
      if (!this.donationForm.type) {
        this.errors.type = 'Donation type is required'
      }
      
      if (!this.donationForm.status) {
        this.errors.status = 'Status is required'
      }
      
      if (this.donationForm.donor_email && !this.isValidEmail(this.donationForm.donor_email)) {
        this.errors.donor_email = 'Valid email is required'
      }
      
      return Object.keys(this.errors).length === 0
    },
    
    isValidEmail(email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      return emailRegex.test(email)
    },
    
    saveDonation() {
      if (!this.validateForm()) return
      
      this.saving = true
      
      // Simulate API call
      setTimeout(() => {
        if (this.editingDonation) {
          const index = this.donations.findIndex(d => d.id === this.editingDonation.id)
          if (index > -1) {
            this.donations[index] = { ...this.donationForm, id: this.editingDonation.id }
          }
        } else {
          const newDonation = {
            ...this.donationForm,
            id: Date.now(),
            created_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
          }
          this.donations.unshift(newDonation)
        }
        
        this.saving = false
        this.closeModal()
      }, 1000)
    }
  },
  
  mounted() {
    // Load donations data
    this.loadDonations()
  }
}
</script>

<style scoped>
.admin-donations {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e9ecef;
}

.header-content h1 {
  margin: 0;
  color: #2c3e50;
  font-size: 28px;
  font-weight: 600;
}

.header-content p {
  margin: 5px 0 0 0;
  font-size: 14px;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.stats-section {
  margin-bottom: 30px;
}

.stats-card {
  background: white;
  border-radius: 10px;
  padding: 25px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  display: flex;
  align-items: center;
  transition: transform 0.2s;
}

.stats-card:hover {
  transform: translateY(-2px);
}

.stats-card.total {
  border-left: 4px solid #28a745;
}

.stats-card.count {
  border-left: 4px solid #007bff;
}

.stats-card.donors {
  border-left: 4px solid #6f42c1;
}

.stats-card.average {
  border-left: 4px solid #fd7e14;
}

.stats-icon {
  font-size: 40px;
  margin-right: 20px;
  opacity: 0.8;
}

.stats-card.total .stats-icon { color: #28a745; }
.stats-card.count .stats-icon { color: #007bff; }
.stats-card.donors .stats-icon { color: #6f42c1; }
.stats-card.average .stats-icon { color: #fd7e14; }

.stats-content h3 {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: #2c3e50;
}

.stats-content p {
  margin: 5px 0;
  color: #6c757d;
  font-size: 14px;
}

.filters-section {
  background: white;
  padding: 20px;
  border-radius: 10px;
  margin-bottom: 20px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.search-box {
  position: relative;
}

.search-box i {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #6c757d;
}

.search-box input {
  padding-left: 45px;
}

.filters {
  display: flex;
  gap: 10px;
  align-items: center;
}

.table-section {
  background: white;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table {
  margin: 0;
}

.table th {
  background: #f8f9fa;
  border: none;
  font-weight: 600;
  color: #2c3e50;
  padding: 15px;
}

.table td {
  padding: 15px;
  vertical-align: middle;
  border-color: #e9ecef;
}

.sortable {
  cursor: pointer;
  user-select: none;
}

.sortable:hover {
  background: #e9ecef !important;
}

.donor-info strong {
  color: #2c3e50;
}

.amount {
  font-weight: 600;
  color: #28a745;
  font-size: 16px;
}

.date-info {
  font-size: 14px;
}

.action-buttons {
  display: flex;
  gap: 5px;
}

.action-buttons .btn {
  padding: 5px 8px;
}

.badge {
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 500;
  border-radius: 20px;
}

.badge-success { background: #d4edda; color: #155724; }
.badge-warning { background: #fff3cd; color: #856404; }
.badge-danger { background: #f8d7da; color: #721c24; }
.badge-info { background: #d1ecf1; color: #0c5460; }
.badge-primary { background: #cce7ff; color: #004085; }
.badge-secondary { background: #e2e3e5; color: #383d41; }

.pagination-section {
  margin-top: 20px;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #6c757d;
}

.empty-state i {
  font-size: 48px;
  margin-bottom: 15px;
}

.loading-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #f3f3f3;
  border-top: 2px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  display: inline-block;
  margin-right: 10px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.modal.show {
  background: rgba(0,0,0,0.5);
}

.modal-dialog {
  margin-top: 50px;
}

.modal-content {
  border: none;
  border-radius: 10px;
}

.modal-header {
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
  border-radius: 10px 10px 0 0;
}

.modal-title {
  color: #2c3e50;
  font-weight: 600;
}

.form-label {
  font-weight: 500;
  color: #2c3e50;
  margin-bottom: 5px;
}

.form-control:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.is-invalid {
  border-color: #dc3545;
}

.invalid-feedback {
  display: block;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }
  
  .filters {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filters .form-control {
    margin-bottom: 10px;
  }
  
  .action-buttons {
    flex-direction: column;
  }
  
  .stats-card {
    margin-bottom: 15px;
  }
}
</style>