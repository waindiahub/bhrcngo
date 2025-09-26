<template>
  <div class="member-donations-page">
    <!-- Header Section -->
    <div class="donations-header">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-8">
            <div class="header-content">
              <h1 class="page-title">
                <i class="fas fa-heart me-3"></i>
                My Donations
              </h1>
              <p class="page-subtitle">
                Track your contributions, download receipts, and view your impact on human rights causes
              </p>
            </div>
          </div>
          <div class="col-lg-4 text-lg-end">
            <div class="header-stats">
              <div class="stat-card">
                <div class="stat-value">₹{{ formatCurrency(donationStats.totalAmount) }}</div>
                <div class="stat-label">Total Donated</div>
              </div>
              <div class="stat-card">
                <div class="stat-value">{{ donationStats.totalCount }}</div>
                <div class="stat-label">Donations</div>
              </div>
              <div class="stat-card">
                <div class="stat-value">{{ donationStats.currentYear }}</div>
                <div class="stat-label">This Year</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Donations Content -->
    <div class="donations-content">
      <div class="container">
        <!-- Quick Actions -->
        <div class="quick-actions">
          <div class="row">
            <div class="col-lg-8">
              <div class="action-cards">
                <div class="action-card" @click="showNewDonationModal">
                  <div class="action-icon">
                    <i class="fas fa-plus"></i>
                  </div>
                  <div class="action-content">
                    <h5>Make New Donation</h5>
                    <p>Support our human rights initiatives</p>
                  </div>
                </div>
                
                <div class="action-card" @click="downloadTaxCertificate">
                  <div class="action-icon">
                    <i class="fas fa-file-invoice"></i>
                  </div>
                  <div class="action-content">
                    <h5>Tax Certificate</h5>
                    <p>Download 80G tax exemption certificate</p>
                  </div>
                </div>
                
                <div class="action-card" @click="viewImpactReport">
                  <div class="action-icon">
                    <i class="fas fa-chart-line"></i>
                  </div>
                  <div class="action-content">
                    <h5>Impact Report</h5>
                    <p>See how your donations make a difference</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="donation-summary">
                <h6>Donation Summary</h6>
                <div class="summary-item">
                  <span>This Month</span>
                  <strong>₹{{ formatCurrency(donationStats.thisMonth) }}</strong>
                </div>
                <div class="summary-item">
                  <span>Last Month</span>
                  <strong>₹{{ formatCurrency(donationStats.lastMonth) }}</strong>
                </div>
                <div class="summary-item">
                  <span>Average Donation</span>
                  <strong>₹{{ formatCurrency(donationStats.averageAmount) }}</strong>
                </div>
                <div class="summary-item">
                  <span>Largest Donation</span>
                  <strong>₹{{ formatCurrency(donationStats.largestAmount) }}</strong>
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
                    placeholder="Search donations..."
                    v-model="searchQuery"
                    @input="filterDonations"
                  >
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="filter-controls">
                <select 
                  class="form-select me-2" 
                  v-model="selectedYear"
                  @change="filterDonations"
                >
                  <option value="">All Years</option>
                  <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                </select>
                
                <select 
                  class="form-select me-2" 
                  v-model="selectedType"
                  @change="filterDonations"
                >
                  <option value="">All Types</option>
                  <option value="one-time">One-time</option>
                  <option value="monthly">Monthly</option>
                  <option value="annual">Annual</option>
                </select>
                
                <select 
                  class="form-select me-2" 
                  v-model="selectedPurpose"
                  @change="filterDonations"
                >
                  <option value="">All Purposes</option>
                  <option value="general">General Fund</option>
                  <option value="legal-aid">Legal Aid</option>
                  <option value="education">Education</option>
                  <option value="awareness">Awareness</option>
                  <option value="emergency">Emergency Relief</option>
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

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Loading donations...</p>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ error }}
            <button class="btn btn-outline-danger btn-sm ms-3" @click="loadDonations">
              <i class="fas fa-redo me-2"></i>
              Retry
            </button>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="filteredDonations.length === 0" class="empty-state">
          <div class="empty-illustration">
            <i class="fas fa-heart"></i>
          </div>
          <h3>{{ donations.length === 0 ? 'No Donations Yet' : 'No Matching Donations' }}</h3>
          <p>
            {{ donations.length === 0 
              ? 'You haven\'t made any donations yet. Start supporting human rights causes today!' 
              : 'No donations match your current filters. Try adjusting your search criteria.' 
            }}
          </p>
          <button 
            v-if="donations.length === 0"
            class="btn btn-primary" 
            @click="showNewDonationModal"
          >
            <i class="fas fa-heart me-2"></i>
            Make Your First Donation
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

        <!-- Donations List -->
        <div v-else class="donations-list">
          <div 
            v-for="donation in paginatedDonations" 
            :key="donation.id"
            class="donation-card"
          >
            <div class="donation-header">
              <div class="donation-info">
                <div class="donation-id">
                  <strong>Donation #{{ donation.transaction_id }}</strong>
                  <span class="donation-date">{{ formatDate(donation.created_at) }}</span>
                </div>
                <div class="donation-amount">
                  <span class="amount">₹{{ formatCurrency(donation.amount) }}</span>
                  <span class="type-badge" :class="donation.type">{{ getDonationTypeText(donation.type) }}</span>
                </div>
              </div>
              <div class="donation-status">
                <span class="status-badge" :class="donation.status">
                  <i class="fas" :class="getStatusIcon(donation.status)"></i>
                  {{ getStatusText(donation.status) }}
                </span>
              </div>
            </div>

            <div class="donation-content">
              <div class="donation-details">
                <div class="detail-item">
                  <i class="fas fa-tag"></i>
                  <span><strong>Purpose:</strong> {{ getPurposeText(donation.purpose) }}</span>
                </div>
                <div class="detail-item" v-if="donation.payment_method">
                  <i class="fas fa-credit-card"></i>
                  <span><strong>Payment:</strong> {{ getPaymentMethodText(donation.payment_method) }}</span>
                </div>
                <div class="detail-item" v-if="donation.tax_exemption">
                  <i class="fas fa-receipt"></i>
                  <span><strong>Tax Exemption:</strong> Available (80G)</span>
                </div>
                <div class="detail-item" v-if="donation.recurring && donation.next_payment_date">
                  <i class="fas fa-calendar-alt"></i>
                  <span><strong>Next Payment:</strong> {{ formatDate(donation.next_payment_date) }}</span>
                </div>
              </div>

              <div class="donation-message" v-if="donation.message">
                <div class="message-content">
                  <i class="fas fa-quote-left"></i>
                  <p>{{ donation.message }}</p>
                </div>
              </div>

              <div class="donation-impact" v-if="donation.impact">
                <div class="impact-content">
                  <h6><i class="fas fa-hands-helping me-2"></i>Your Impact</h6>
                  <p>{{ donation.impact.description }}</p>
                  <div class="impact-stats" v-if="donation.impact.stats">
                    <div class="impact-stat" v-for="stat in donation.impact.stats" :key="stat.label">
                      <strong>{{ stat.value }}</strong>
                      <span>{{ stat.label }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="donation-actions">
              <button 
                class="btn btn-outline-primary btn-sm"
                @click="downloadReceipt(donation)"
                :disabled="donation.status !== 'completed'"
              >
                <i class="fas fa-download me-2"></i>
                Download Receipt
              </button>
              
              <button 
                v-if="donation.tax_exemption && donation.status === 'completed'"
                class="btn btn-outline-success btn-sm"
                @click="downloadTaxCertificate(donation)"
              >
                <i class="fas fa-file-invoice me-2"></i>
                Tax Certificate
              </button>
              
              <button 
                class="btn btn-outline-info btn-sm"
                @click="viewDonationDetails(donation)"
              >
                <i class="fas fa-eye me-2"></i>
                View Details
              </button>
              
              <button 
                v-if="donation.recurring && donation.status === 'active'"
                class="btn btn-outline-warning btn-sm"
                @click="manageDonation(donation)"
              >
                <i class="fas fa-cog me-2"></i>
                Manage
              </button>
              
              <button 
                v-if="canCancelDonation(donation)"
                class="btn btn-outline-danger btn-sm"
                @click="cancelDonation(donation)"
              >
                <i class="fas fa-times me-2"></i>
                Cancel
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="pagination-container">
          <nav aria-label="Donations pagination">
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

    <!-- Donation Details Modal -->
    <div class="modal fade" id="donationDetailsModal" tabindex="-1" ref="donationDetailsModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              Donation Details #{{ selectedDonation?.transaction_id }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" v-if="selectedDonation">
            <div class="donation-details-content">
              <!-- Transaction Information -->
              <div class="details-section">
                <h6>Transaction Information</h6>
                <div class="info-grid">
                  <div class="info-item">
                    <strong>Transaction ID:</strong>
                    <span>{{ selectedDonation.transaction_id }}</span>
                  </div>
                  <div class="info-item">
                    <strong>Amount:</strong>
                    <span class="amount-highlight">₹{{ formatCurrency(selectedDonation.amount) }}</span>
                  </div>
                  <div class="info-item">
                    <strong>Type:</strong>
                    <span class="type-badge" :class="selectedDonation.type">
                      {{ getDonationTypeText(selectedDonation.type) }}
                    </span>
                  </div>
                  <div class="info-item">
                    <strong>Status:</strong>
                    <span class="status-badge" :class="selectedDonation.status">
                      {{ getStatusText(selectedDonation.status) }}
                    </span>
                  </div>
                  <div class="info-item">
                    <strong>Date:</strong>
                    <span>{{ formatDateTime(selectedDonation.created_at) }}</span>
                  </div>
                  <div class="info-item">
                    <strong>Purpose:</strong>
                    <span>{{ getPurposeText(selectedDonation.purpose) }}</span>
                  </div>
                </div>
              </div>

              <!-- Payment Information -->
              <div class="details-section">
                <h6>Payment Information</h6>
                <div class="info-grid">
                  <div class="info-item">
                    <strong>Payment Method:</strong>
                    <span>{{ getPaymentMethodText(selectedDonation.payment_method) }}</span>
                  </div>
                  <div class="info-item" v-if="selectedDonation.payment_reference">
                    <strong>Payment Reference:</strong>
                    <span>{{ selectedDonation.payment_reference }}</span>
                  </div>
                  <div class="info-item" v-if="selectedDonation.gateway_transaction_id">
                    <strong>Gateway Transaction ID:</strong>
                    <span>{{ selectedDonation.gateway_transaction_id }}</span>
                  </div>
                  <div class="info-item">
                    <strong>Tax Exemption:</strong>
                    <span>{{ selectedDonation.tax_exemption ? 'Available (80G)' : 'Not Available' }}</span>
                  </div>
                </div>
              </div>

              <!-- Donor Message -->
              <div class="details-section" v-if="selectedDonation.message">
                <h6>Your Message</h6>
                <div class="message-display">
                  {{ selectedDonation.message }}
                </div>
              </div>

              <!-- Recurring Information -->
              <div class="details-section" v-if="selectedDonation.recurring">
                <h6>Recurring Donation</h6>
                <div class="info-grid">
                  <div class="info-item">
                    <strong>Frequency:</strong>
                    <span>{{ selectedDonation.frequency }}</span>
                  </div>
                  <div class="info-item" v-if="selectedDonation.next_payment_date">
                    <strong>Next Payment:</strong>
                    <span>{{ formatDate(selectedDonation.next_payment_date) }}</span>
                  </div>
                  <div class="info-item" v-if="selectedDonation.end_date">
                    <strong>End Date:</strong>
                    <span>{{ formatDate(selectedDonation.end_date) }}</span>
                  </div>
                  <div class="info-item">
                    <strong>Total Payments:</strong>
                    <span>{{ selectedDonation.total_payments || 0 }}</span>
                  </div>
                </div>
              </div>

              <!-- Impact Information -->
              <div class="details-section" v-if="selectedDonation.impact">
                <h6>Your Impact</h6>
                <div class="impact-display">
                  <p>{{ selectedDonation.impact.description }}</p>
                  <div class="impact-metrics" v-if="selectedDonation.impact.stats">
                    <div 
                      v-for="stat in selectedDonation.impact.stats" 
                      :key="stat.label"
                      class="impact-metric"
                    >
                      <div class="metric-value">{{ stat.value }}</div>
                      <div class="metric-label">{{ stat.label }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button 
              v-if="selectedDonation?.status === 'completed'"
              type="button" 
              class="btn btn-primary"
              @click="downloadReceipt(selectedDonation)"
            >
              <i class="fas fa-download me-2"></i>
              Download Receipt
            </button>
            <button 
              v-if="selectedDonation?.tax_exemption && selectedDonation?.status === 'completed'"
              type="button" 
              class="btn btn-success"
              @click="downloadTaxCertificate(selectedDonation)"
            >
              <i class="fas fa-file-invoice me-2"></i>
              Tax Certificate
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- New Donation Modal -->
    <div class="modal fade" id="newDonationModal" tabindex="-1" ref="newDonationModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-heart me-2"></i>
              Make a Donation
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="processDonation">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Donation Type *</label>
                    <select class="form-select" v-model="newDonation.type" required>
                      <option value="one-time">One-time Donation</option>
                      <option value="monthly">Monthly Donation</option>
                      <option value="annual">Annual Donation</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Purpose *</label>
                    <select class="form-select" v-model="newDonation.purpose" required>
                      <option value="general">General Fund</option>
                      <option value="legal-aid">Legal Aid</option>
                      <option value="education">Education Programs</option>
                      <option value="awareness">Awareness Campaigns</option>
                      <option value="emergency">Emergency Relief</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Donation Amount *</label>
                <div class="amount-selection">
                  <div class="preset-amounts">
                    <button 
                      type="button"
                      v-for="amount in presetAmounts" 
                      :key="amount"
                      class="btn btn-outline-primary"
                      :class="{ active: newDonation.amount == amount }"
                      @click="selectAmount(amount)"
                    >
                      ₹{{ formatCurrency(amount) }}
                    </button>
                  </div>
                  <div class="custom-amount mt-2">
                    <input 
                      type="number" 
                      class="form-control" 
                      v-model="newDonation.amount"
                      placeholder="Enter custom amount"
                      min="100"
                      required
                    >
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Personal Message (Optional)</label>
                <textarea 
                  class="form-control" 
                  rows="3"
                  v-model="newDonation.message"
                  placeholder="Share why you're supporting this cause..."
                ></textarea>
              </div>

              <div class="mb-3">
                <div class="form-check">
                  <input 
                    class="form-check-input" 
                    type="checkbox" 
                    v-model="newDonation.anonymous"
                    id="anonymousDonation"
                  >
                  <label class="form-check-label" for="anonymousDonation">
                    Make this donation anonymous
                  </label>
                </div>
              </div>

              <div class="mb-3">
                <div class="form-check">
                  <input 
                    class="form-check-input" 
                    type="checkbox" 
                    v-model="newDonation.newsletter"
                    id="newsletterSubscribe"
                  >
                  <label class="form-check-label" for="newsletterSubscribe">
                    Subscribe to our newsletter for updates
                  </label>
                </div>
              </div>

              <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Tax Benefits:</strong> Your donation is eligible for tax exemption under Section 80G. 
                You will receive a tax certificate after successful payment.
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button 
              type="button" 
              class="btn btn-primary"
              @click="processDonation"
              :disabled="processing"
            >
              <i class="fas me-2" :class="processing ? 'fa-spinner fa-spin' : 'fa-heart'"></i>
              {{ processing ? 'Processing...' : `Donate ₹${formatCurrency(newDonation.amount || 0)}` }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Impact Report Modal -->
    <div class="modal fade" id="impactReportModal" tabindex="-1" ref="impactReportModal">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-chart-line me-2"></i>
              Your Impact Report
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="impact-report-content">
              <div class="impact-summary">
                <h6>Your Total Impact</h6>
                <div class="impact-cards">
                  <div class="impact-card">
                    <div class="impact-icon">
                      <i class="fas fa-balance-scale"></i>
                    </div>
                    <div class="impact-info">
                      <div class="impact-number">{{ impactData.casesSupported || 0 }}</div>
                      <div class="impact-label">Legal Cases Supported</div>
                    </div>
                  </div>
                  
                  <div class="impact-card">
                    <div class="impact-icon">
                      <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="impact-info">
                      <div class="impact-number">{{ impactData.studentsHelped || 0 }}</div>
                      <div class="impact-label">Students Educated</div>
                    </div>
                  </div>
                  
                  <div class="impact-card">
                    <div class="impact-icon">
                      <i class="fas fa-users"></i>
                    </div>
                    <div class="impact-info">
                      <div class="impact-number">{{ impactData.peopleReached || 0 }}</div>
                      <div class="impact-label">People Reached</div>
                    </div>
                  </div>
                  
                  <div class="impact-card">
                    <div class="impact-icon">
                      <i class="fas fa-hands-helping"></i>
                    </div>
                    <div class="impact-info">
                      <div class="impact-number">{{ impactData.familiesSupported || 0 }}</div>
                      <div class="impact-label">Families Supported</div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="impact-stories" v-if="impactData.stories && impactData.stories.length > 0">
                <h6>Success Stories</h6>
                <div class="stories-grid">
                  <div 
                    v-for="story in impactData.stories" 
                    :key="story.id"
                    class="story-card"
                  >
                    <div class="story-image" v-if="story.image">
                      <img :src="story.image" :alt="story.title">
                    </div>
                    <div class="story-content">
                      <h6>{{ story.title }}</h6>
                      <p>{{ story.description }}</p>
                      <small class="story-date">{{ formatDate(story.date) }}</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" @click="downloadImpactReport">
              <i class="fas fa-download me-2"></i>
              Download Report
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
  name: 'MemberDonations',
  setup() {
    const authStore = useAuthStore()

    // Reactive data
    const loading = ref(false)
    const error = ref('')
    const processing = ref(false)
    const searchQuery = ref('')
    const selectedYear = ref('')
    const selectedType = ref('')
    const selectedPurpose = ref('')
    const currentPage = ref(1)
    const itemsPerPage = 10
    const selectedDonation = ref(null)

    // Donations data
    const donations = ref([])
    const impactData = ref({})

    // New donation form
    const newDonation = reactive({
      type: 'one-time',
      purpose: 'general',
      amount: null,
      message: '',
      anonymous: false,
      newsletter: false
    })

    const presetAmounts = [500, 1000, 2500, 5000, 10000, 25000]

    // Modals
    const donationDetailsModal = ref(null)
    const newDonationModal = ref(null)
    const impactReportModal = ref(null)

    // Computed properties
    const donationStats = computed(() => {
      const currentYear = new Date().getFullYear()
      const currentMonth = new Date().getMonth()
      const lastMonth = currentMonth === 0 ? 11 : currentMonth - 1
      
      const totalAmount = donations.value.reduce((sum, d) => sum + parseFloat(d.amount), 0)
      const totalCount = donations.value.length
      
      const thisYearDonations = donations.value.filter(d => 
        new Date(d.created_at).getFullYear() === currentYear
      )
      
      const thisMonthDonations = donations.value.filter(d => {
        const date = new Date(d.created_at)
        return date.getFullYear() === currentYear && date.getMonth() === currentMonth
      })
      
      const lastMonthDonations = donations.value.filter(d => {
        const date = new Date(d.created_at)
        const year = lastMonth === 11 ? currentYear - 1 : currentYear
        return date.getFullYear() === year && date.getMonth() === lastMonth
      })
      
      const amounts = donations.value.map(d => parseFloat(d.amount))
      const averageAmount = amounts.length > 0 ? amounts.reduce((a, b) => a + b, 0) / amounts.length : 0
      const largestAmount = amounts.length > 0 ? Math.max(...amounts) : 0

      return {
        totalAmount,
        totalCount,
        currentYear: thisYearDonations.length,
        thisMonth: thisMonthDonations.reduce((sum, d) => sum + parseFloat(d.amount), 0),
        lastMonth: lastMonthDonations.reduce((sum, d) => sum + parseFloat(d.amount), 0),
        averageAmount,
        largestAmount
      }
    })

    const availableYears = computed(() => {
      const years = [...new Set(donations.value.map(d => new Date(d.created_at).getFullYear()))]
      return years.sort((a, b) => b - a)
    })

    const filteredDonations = computed(() => {
      let filtered = [...donations.value]

      // Search filter
      if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(donation => 
          donation.transaction_id.toLowerCase().includes(query) ||
          donation.purpose.toLowerCase().includes(query) ||
          (donation.message && donation.message.toLowerCase().includes(query))
        )
      }

      // Year filter
      if (selectedYear.value) {
        filtered = filtered.filter(donation => 
          new Date(donation.created_at).getFullYear() == selectedYear.value
        )
      }

      // Type filter
      if (selectedType.value) {
        filtered = filtered.filter(donation => donation.type === selectedType.value)
      }

      // Purpose filter
      if (selectedPurpose.value) {
        filtered = filtered.filter(donation => donation.purpose === selectedPurpose.value)
      }

      // Sort by creation date (newest first)
      filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))

      return filtered
    })

    const paginatedDonations = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage
      const end = start + itemsPerPage
      return filteredDonations.value.slice(start, end)
    })

    const totalPages = computed(() => {
      return Math.ceil(filteredDonations.value.length / itemsPerPage)
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
    const loadDonations = async () => {
      loading.value = true
      error.value = ''
      
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/member/donations', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          const data = await response.json()
          donations.value = data.donations || []
        } else {
          throw new Error('Failed to load donations')
        }
      } catch (err) {
        console.error('Error loading donations:', err)
        error.value = 'Failed to load donations. Please try again.'
      } finally {
        loading.value = false
      }
    }

    const loadImpactData = async () => {
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/member/impact', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          const data = await response.json()
          impactData.value = data.impact || {}
        }
      } catch (err) {
        console.error('Error loading impact data:', err)
      }
    }

    const filterDonations = () => {
      currentPage.value = 1
    }

    const resetFilters = () => {
      searchQuery.value = ''
      selectedYear.value = ''
      selectedType.value = ''
      selectedPurpose.value = ''
      currentPage.value = 1
    }

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
      }
    }

    const viewDonationDetails = (donation) => {
      selectedDonation.value = donation
      const modal = new Modal(donationDetailsModal.value)
      modal.show()
    }

    const showNewDonationModal = () => {
      // Reset form
      Object.assign(newDonation, {
        type: 'one-time',
        purpose: 'general',
        amount: null,
        message: '',
        anonymous: false,
        newsletter: false
      })
      
      const modal = new Modal(newDonationModal.value)
      modal.show()
    }

    const selectAmount = (amount) => {
      newDonation.amount = amount
    }

    const processDonation = async () => {
      if (!newDonation.amount || newDonation.amount < 100) {
        alert('Minimum donation amount is ₹100')
        return
      }

      processing.value = true
      
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/member/donations', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(newDonation)
        })

        if (response.ok) {
          const data = await response.json()
          
          // Redirect to payment gateway or show success
          if (data.payment_url) {
            window.location.href = data.payment_url
          } else {
            // Add new donation to list
            donations.value.unshift(data.donation)
            
            // Close modal
            const modal = Modal.getInstance(newDonationModal.value)
            modal.hide()
            
            alert('Donation processed successfully!')
          }
        } else {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Failed to process donation')
        }
      } catch (err) {
        console.error('Error processing donation:', err)
        alert(err.message || 'Failed to process donation')
      } finally {
        processing.value = false
      }
    }

    const downloadReceipt = async (donation) => {
      try {
        const response = await fetch(`/backend/api.php/member/donations/${donation.id}/receipt`, {
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          }
        })

        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = `donation-receipt-${donation.transaction_id}.pdf`
          a.click()
          window.URL.revokeObjectURL(url)
        } else {
          throw new Error('Failed to download receipt')
        }
      } catch (err) {
        console.error('Error downloading receipt:', err)
        alert('Failed to download receipt')
      }
    }

    const downloadTaxCertificate = async (donation = null) => {
      try {
        const url = donation 
          ? `/backend/api.php/member/donations/${donation.id}/tax-certificate`
          : 'https://bhrcdata.online/backend/api.php/member/tax-certificate'
          
        const response = await fetch(url, {
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          }
        })

        if (response.ok) {
          const blob = await response.blob()
          const downloadUrl = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = downloadUrl
          a.download = donation 
            ? `tax-certificate-${donation.transaction_id}.pdf`
            : `tax-certificate-${new Date().getFullYear()}.pdf`
          a.click()
          window.URL.revokeObjectURL(downloadUrl)
        } else {
          throw new Error('Failed to download tax certificate')
        }
      } catch (err) {
        console.error('Error downloading tax certificate:', err)
        alert('Failed to download tax certificate')
      }
    }

    const viewImpactReport = async () => {
      await loadImpactData()
      const modal = new Modal(impactReportModal.value)
      modal.show()
    }

    const downloadImpactReport = async () => {
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/member/impact/report', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          }
        })

        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = `impact-report-${new Date().getFullYear()}.pdf`
          a.click()
          window.URL.revokeObjectURL(url)
        } else {
          throw new Error('Failed to download impact report')
        }
      } catch (err) {
        console.error('Error downloading impact report:', err)
        alert('Failed to download impact report')
      }
    }

    const manageDonation = (donation) => {
      // This would open a management interface for recurring donations
      alert('Donation management feature coming soon!')
    }

    const cancelDonation = async (donation) => {
      if (!confirm(`Are you sure you want to cancel this ${donation.type} donation?`)) return

      try {
        const response = await fetch(`/backend/api.php/member/donations/${donation.id}/cancel`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          // Update donation status
          const donationIndex = donations.value.findIndex(d => d.id === donation.id)
          if (donationIndex !== -1) {
            donations.value[donationIndex].status = 'cancelled'
          }
          
          alert('Donation cancelled successfully')
        } else {
          throw new Error('Failed to cancel donation')
        }
      } catch (err) {
        console.error('Error cancelling donation:', err)
        alert('Failed to cancel donation')
      }
    }

    const canCancelDonation = (donation) => {
      return donation.recurring && ['active', 'pending'].includes(donation.status)
    }

    const getStatusIcon = (status) => {
      const icons = {
        completed: 'fa-check-circle',
        pending: 'fa-clock',
        processing: 'fa-spinner',
        failed: 'fa-times-circle',
        cancelled: 'fa-ban',
        refunded: 'fa-undo'
      }
      return icons[status] || 'fa-question-circle'
    }

    const getStatusText = (status) => {
      const texts = {
        completed: 'Completed',
        pending: 'Pending',
        processing: 'Processing',
        failed: 'Failed',
        cancelled: 'Cancelled',
        refunded: 'Refunded'
      }
      return texts[status] || status
    }

    const getDonationTypeText = (type) => {
      const texts = {
        'one-time': 'One-time',
        'monthly': 'Monthly',
        'annual': 'Annual'
      }
      return texts[type] || type
    }

    const getPurposeText = (purpose) => {
      const texts = {
        general: 'General Fund',
        'legal-aid': 'Legal Aid',
        education: 'Education Programs',
        awareness: 'Awareness Campaigns',
        emergency: 'Emergency Relief'
      }
      return texts[purpose] || purpose
    }

    const getPaymentMethodText = (method) => {
      const texts = {
        'credit-card': 'Credit Card',
        'debit-card': 'Debit Card',
        'net-banking': 'Net Banking',
        'upi': 'UPI',
        'wallet': 'Digital Wallet',
        'bank-transfer': 'Bank Transfer'
      }
      return texts[method] || method
    }

    const formatCurrency = (amount) => {
      return new Intl.NumberFormat('en-IN').format(amount)
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

    // Lifecycle
    onMounted(() => {
      loadDonations()
    })

    return {
      // Data
      loading,
      error,
      processing,
      searchQuery,
      selectedYear,
      selectedType,
      selectedPurpose,
      currentPage,
      selectedDonation,
      donations,
      newDonation,
      presetAmounts,
      impactData,
      donationDetailsModal,
      newDonationModal,
      impactReportModal,
      
      // Computed
      donationStats,
      availableYears,
      filteredDonations,
      paginatedDonations,
      totalPages,
      visiblePages,
      
      // Methods
      loadDonations,
      loadImpactData,
      filterDonations,
      resetFilters,
      changePage,
      viewDonationDetails,
      showNewDonationModal,
      selectAmount,
      processDonation,
      downloadReceipt,
      downloadTaxCertificate,
      viewImpactReport,
      downloadImpactReport,
      manageDonation,
      cancelDonation,
      canCancelDonation,
      getStatusIcon,
      getStatusText,
      getDonationTypeText,
      getPurposeText,
      getPaymentMethodText,
      formatCurrency,
      formatDate,
      formatDateTime
    }
  }
}
</script>

<style scoped>
/* Donations Header */
.donations-header {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
  min-width: 100px;
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

/* Donations Content */
.donations-content {
  padding: 3rem 0;
  background: #f8f9fa;
  min-height: calc(100vh - 200px);
}

/* Quick Actions */
.quick-actions {
  margin-bottom: 3rem;
}

.action-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.action-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.action-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.action-icon {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #28a745, #20c997);
  border-radius: 50%;
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

.donation-summary {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.donation-summary h6 {
  color: #333;
  font-weight: 600;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #28a745;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid #e9ecef;
}

.summary-item:last-child {
  border-bottom: none;
}

.summary-item span {
  color: #666;
}

.summary-item strong {
  color: #28a745;
  font-weight: 600;
}

/* Filter Section */
.filter-section {
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

.filter-controls {
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

/* Donations List */
.donations-list {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.donation-card {
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  transition: all 0.3s ease;
}

.donation-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.donation-card:last-child {
  margin-bottom: 0;
}

/* Donation Header */
.donation-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.donation-info {
  flex: 1;
}

.donation-id strong {
  color: #333;
  font-size: 1.1rem;
}

.donation-date {
  display: block;
  color: #666;
  font-size: 0.9rem;
  margin-top: 0.25rem;
}

.donation-amount {
  text-align: right;
}

.amount {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: #28a745;
  margin-bottom: 0.25rem;
}

.type-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
}

.type-badge.one-time {
  background: #e3f2fd;
  color: #1976d2;
}

.type-badge.monthly {
  background: #f3e5f5;
  color: #7b1fa2;
}

.type-badge.annual {
  background: #fff3e0;
  color: #f57c00;
}

.donation-status {
  display: flex;
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

.status-badge.completed {
  background: #e8f5e8;
  color: #2e7d32;
}

.status-badge.pending {
  background: #fff3e0;
  color: #f57c00;
}

.status-badge.processing {
  background: #e3f2fd;
  color: #1976d2;
}

.status-badge.failed {
  background: #ffebee;
  color: #c62828;
}

.status-badge.cancelled {
  background: #fafafa;
  color: #616161;
}

.status-badge.refunded {
  background: #f5f5f5;
  color: #757575;
}

/* Donation Content */
.donation-content {
  margin-bottom: 1rem;
}

.donation-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: #666;
}

.detail-item i {
  color: #28a745;
  width: 16px;
}

/* Donation Message */
.donation-message {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 6px;
  margin-bottom: 1rem;
}

.message-content {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
}

.message-content i {
  color: #28a745;
  margin-top: 0.25rem;
}

.message-content p {
  margin: 0;
  color: #333;
  font-style: italic;
  line-height: 1.5;
}

/* Donation Impact */
.donation-impact {
  background: linear-gradient(135deg, #e8f5e8, #f0f8f0);
  padding: 1rem;
  border-radius: 6px;
  margin-bottom: 1rem;
}

.impact-content h6 {
  color: #28a745;
  font-weight: 600;
  margin-bottom: 0.75rem;
}

.impact-content p {
  margin: 0 0 1rem 0;
  color: #333;
  line-height: 1.5;
}

.impact-stats {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.impact-stat {
  text-align: center;
}

.impact-stat strong {
  display: block;
  font-size: 1.25rem;
  color: #28a745;
  font-weight: 700;
}

.impact-stat span {
  font-size: 0.8rem;
  color: #666;
}

/* Donation Actions */
.donation-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.donation-actions .btn {
  font-size: 0.85rem;
}

/* Pagination */
.pagination-container {
  margin-top: 3rem;
}

.pagination .page-link {
  color: #28a745;
  border-color: #28a745;
}

.pagination .page-item.active .page-link {
  background-color: #28a745;
  border-color: #28a745;
}

.pagination .page-link:hover {
  color: #20c997;
  background-color: #f8f9fa;
  border-color: #20c997;
}

/* Modal Styles */
.donation-details-content {
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

.amount-highlight {
  font-size: 1.25rem;
  font-weight: 700;
  color: #28a745 !important;
}

.message-display {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 6px;
  border-left: 4px solid #28a745;
  font-style: italic;
  color: #333;
}

.impact-display {
  background: linear-gradient(135deg, #e8f5e8, #f0f8f0);
  padding: 1.5rem;
  border-radius: 8px;
}

.impact-display p {
  margin-bottom: 1rem;
  color: #333;
  line-height: 1.6;
}

.impact-metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 1rem;
}

.impact-metric {
  text-align: center;
  background: white;
  padding: 1rem;
  border-radius: 6px;
}

.metric-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #28a745;
  margin-bottom: 0.25rem;
}

.metric-label {
  font-size: 0.8rem;
  color: #666;
}

/* New Donation Modal */
.amount-selection {
  margin-top: 0.5rem;
}

.preset-amounts {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.preset-amounts .btn {
  font-size: 0.9rem;
  padding: 0.5rem;
}

.preset-amounts .btn.active {
  background-color: #28a745;
  border-color: #28a745;
  color: white;
}

.custom-amount input {
  font-size: 1.1rem;
  font-weight: 600;
}

/* Impact Report Modal */
.impact-report-content {
  max-height: 70vh;
  overflow-y: auto;
}

.impact-summary {
  margin-bottom: 2rem;
}

.impact-summary h6 {
  color: #333;
  font-weight: 600;
  margin-bottom: 1.5rem;
  font-size: 1.2rem;
}

.impact-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.impact-card {
  background: linear-gradient(135deg, #28a745, #20c997);
  color: white;
  padding: 1.5rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.impact-icon {
  width: 50px;
  height: 50px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.impact-info {
  flex: 1;
}

.impact-number {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.impact-label {
  font-size: 0.9rem;
  opacity: 0.9;
}

.impact-stories {
  margin-top: 2rem;
}

.impact-stories h6 {
  color: #333;
  font-weight: 600;
  margin-bottom: 1.5rem;
  font-size: 1.2rem;
}

.stories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.story-card {
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  overflow: hidden;
  transition: all 0.3s ease;
}

.story-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.story-image {
  height: 150px;
  overflow: hidden;
}

.story-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.story-content {
  padding: 1rem;
}

.story-content h6 {
  color: #333;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.story-content p {
  color: #666;
  font-size: 0.9rem;
  line-height: 1.5;
  margin-bottom: 0.75rem;
}

.story-date {
  color: #999;
  font-size: 0.8rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .donations-header {
    padding: 2rem 0;
  }
  
  .page-title {
    font-size: 2rem;
  }
  
  .header-stats {
    flex-direction: column;
    gap: 0.5rem;
    margin-top: 1rem;
  }
  
  .stat-card {
    min-width: auto;
  }
  
  .action-cards {
    grid-template-columns: 1fr;
  }
  
  .filter-controls {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .form-select {
    min-width: auto;
  }
  
  .donation-header {
    flex-direction: column;
    gap: 1rem;
  }
  
  .donation-amount {
    text-align: left;
  }
  
  .donation-details {
    grid-template-columns: 1fr;
  }
  
  .donation-actions {
    justify-content: center;
  }
  
  .impact-stats {
    justify-content: center;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .preset-amounts {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .impact-cards {
    grid-template-columns: 1fr;
  }
  
  .stories-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 576px) {
  .donations-content {
    padding: 2rem 0;
  }
  
  .page-title {
    font-size: 1.75rem;
  }
  
  .donation-card {
    padding: 1rem;
  }
  
  .amount {
    font-size: 1.25rem;
  }
  
  .donation-actions .btn {
    font-size: 0.8rem;
    padding: 0.375rem 0.75rem;
  }
  
  .impact-card {
    flex-direction: column;
    text-align: center;
  }
  
  .impact-icon {
    margin-bottom: 0.5rem;
  }
}

/* Print Styles */
@media print {
  .donations-header,
  .filter-section,
  .donation-actions,
  .pagination-container {
    display: none;
  }
  
  .donation-card {
    break-inside: avoid;
    margin-bottom: 1rem;
    border: 1px solid #333;
  }
  
  .donations-content {
    background: white;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .donations-content {
    background: #1a1a1a;
  }
  
  .action-card,
  .donation-summary,
  .filter-section,
  .donations-list,
  .donation-card {
    background: #2d2d2d;
    color: #e0e0e0;
    border-color: #404040;
  }
  
  .donation-id strong,
  .action-content h5,
  .donation-summary h6,
  .details-section h6 {
    color: #e0e0e0;
  }
  
  .donation-date,
  .action-content p,
  .summary-item span,
  .detail-item,
  .info-item span {
    color: #b0b0b0;
  }
  
  .message-display,
  .impact-display {
    background: #404040;
    color: #e0e0e0;
  }
  
  .story-card {
    background: #2d2d2d;
    border-color: #404040;
  }
  
  .story-content h6 {
    color: #e0e0e0;
  }
  
  .story-content p {
    color: #b0b0b0;
  }
}

/* Accessibility */
.donation-card:focus-within {
  outline: 2px solid #28a745;
  outline-offset: 2px;
}

.btn:focus {
  box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

/* Animation */
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

.donation-card {
  animation: fadeInUp 0.3s ease-out;
}

.action-card {
  animation: fadeInUp 0.3s ease-out;
}

/* Loading Animation */
.spinner-border {
  width: 3rem;
  height: 3rem;
}

/* Custom Scrollbar */
.donation-details-content::-webkit-scrollbar,
.impact-report-content::-webkit-scrollbar {
  width: 6px;
}

.donation-details-content::-webkit-scrollbar-track,
.impact-report-content::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.donation-details-content::-webkit-scrollbar-thumb,
.impact-report-content::-webkit-scrollbar-thumb {
  background: #28a745;
  border-radius: 3px;
}

.donation-details-content::-webkit-scrollbar-thumb:hover,
.impact-report-content::-webkit-scrollbar-thumb:hover {
  background: #20c997;
}
</style>