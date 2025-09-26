<template>
  <DefaultLayout>
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="hero-content">
          <h1 class="hero-title">Support Our Mission</h1>
          <p class="hero-subtitle">
            Your donation helps us fight for human rights and create a more just society
          </p>
          <div class="impact-stats">
            <div class="stat-item">
              <span class="stat-number">{{ stats.totalDonations }}</span>
              <span class="stat-label">Total Donations</span>
            </div>
            <div class="stat-item">
              <span class="stat-number">{{ stats.casesSupported }}</span>
              <span class="stat-label">Cases Supported</span>
            </div>
            <div class="stat-item">
              <span class="stat-number">{{ stats.livesImpacted }}</span>
              <span class="stat-label">Lives Impacted</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Impact Section -->
    <section class="impact-section">
      <div class="container">
        <div class="section-header">
          <h2>Your Impact Matters</h2>
          <p>See how your donations make a real difference in people's lives</p>
        </div>
        
        <div class="impact-grid">
          <div class="impact-card">
            <div class="impact-icon">
              <i class="fas fa-balance-scale"></i>
            </div>
            <h3>Legal Aid</h3>
            <p>Provide free legal assistance to those who cannot afford it</p>
            <div class="impact-amount">₹500 = 1 Legal Consultation</div>
          </div>
          
          <div class="impact-card">
            <div class="impact-icon">
              <i class="fas fa-graduation-cap"></i>
            </div>
            <h3>Education & Awareness</h3>
            <p>Conduct workshops and training programs on human rights</p>
            <div class="impact-amount">₹1,000 = 1 Workshop</div>
          </div>
          
          <div class="impact-card">
            <div class="impact-icon">
              <i class="fas fa-hands-helping"></i>
            </div>
            <h3>Emergency Support</h3>
            <p>Provide immediate assistance to victims of human rights violations</p>
            <div class="impact-amount">₹2,000 = Emergency Aid</div>
          </div>
          
          <div class="impact-card">
            <div class="impact-icon">
              <i class="fas fa-megaphone"></i>
            </div>
            <h3>Advocacy Campaigns</h3>
            <p>Run campaigns to raise awareness and drive policy changes</p>
            <div class="impact-amount">₹5,000 = 1 Campaign</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Donation Form -->
    <section class="donation-section">
      <div class="container">
        <div class="donation-wrapper">
          <div class="donation-form-container">
            <div class="form-header">
              <h2>Make a Donation</h2>
              <p>Choose your donation amount and help us continue our mission</p>
            </div>

            <form @submit.prevent="processDonation" class="donation-form">
              <!-- Donation Type -->
              <div class="form-section">
                <h3 class="section-title">
                  <i class="fas fa-heart"></i>
                  Donation Type
                </h3>
                
                <div class="donation-type-options">
                  <div
                    v-for="type in donationTypes"
                    :key="type.value"
                    class="type-option"
                    :class="{ active: form.donationType === type.value }"
                    @click="form.donationType = type.value"
                  >
                    <div class="type-icon">
                      <i :class="type.icon"></i>
                    </div>
                    <div class="type-info">
                      <h4>{{ type.label }}</h4>
                      <p>{{ type.description }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Amount Selection -->
              <div class="form-section">
                <h3 class="section-title">
                  <i class="fas fa-rupee-sign"></i>
                  Donation Amount
                </h3>
                
                <div class="amount-options">
                  <div
                    v-for="amount in predefinedAmounts"
                    :key="amount"
                    class="amount-option"
                    :class="{ active: form.amount === amount }"
                    @click="selectAmount(amount)"
                  >
                    ₹{{ formatAmount(amount) }}
                  </div>
                </div>
                
                <div class="custom-amount">
                  <label for="customAmount">Or enter custom amount:</label>
                  <div class="amount-input-group">
                    <span class="currency-symbol">₹</span>
                    <input
                      id="customAmount"
                      v-model="form.customAmount"
                      type="number"
                      min="100"
                      placeholder="Enter amount"
                      class="amount-input"
                      @input="onCustomAmountChange"
                    >
                  </div>
                  <small class="amount-help">Minimum donation amount is ₹100</small>
                </div>
              </div>

              <!-- Purpose Selection -->
              <div class="form-section">
                <h3 class="section-title">
                  <i class="fas fa-bullseye"></i>
                  Purpose of Donation
                </h3>
                
                <div class="form-group">
                  <select
                    v-model="form.purpose"
                    class="form-input"
                    required
                  >
                    <option value="">Select Purpose</option>
                    <option value="general">General Fund</option>
                    <option value="legal_aid">Legal Aid Program</option>
                    <option value="education">Education & Awareness</option>
                    <option value="emergency">Emergency Support</option>
                    <option value="advocacy">Advocacy Campaigns</option>
                    <option value="infrastructure">Infrastructure Development</option>
                    <option value="research">Research & Documentation</option>
                  </select>
                </div>
              </div>

              <!-- Donor Information -->
              <div class="form-section">
                <h3 class="section-title">
                  <i class="fas fa-user"></i>
                  Donor Information
                </h3>
                
                <div class="form-row">
                  <div class="form-group">
                    <label for="donorName">Full Name *</label>
                    <input
                      id="donorName"
                      v-model="form.donorName"
                      type="text"
                      required
                      class="form-input"
                      :class="{ error: errors.donorName }"
                    >
                    <span v-if="errors.donorName" class="error-message">{{ errors.donorName }}</span>
                  </div>
                  
                  <div class="form-group">
                    <label for="donorEmail">Email Address *</label>
                    <input
                      id="donorEmail"
                      v-model="form.donorEmail"
                      type="email"
                      required
                      class="form-input"
                      :class="{ error: errors.donorEmail }"
                    >
                    <span v-if="errors.donorEmail" class="error-message">{{ errors.donorEmail }}</span>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label for="donorPhone">Phone Number *</label>
                    <input
                      id="donorPhone"
                      v-model="form.donorPhone"
                      type="tel"
                      required
                      class="form-input"
                      :class="{ error: errors.donorPhone }"
                    >
                    <span v-if="errors.donorPhone" class="error-message">{{ errors.donorPhone }}</span>
                  </div>
                  
                  <div class="form-group">
                    <label for="donorPan">PAN Number (for 80G certificate)</label>
                    <input
                      id="donorPan"
                      v-model="form.donorPan"
                      type="text"
                      class="form-input"
                      placeholder="ABCDE1234F"
                      pattern="[A-Z]{5}[0-9]{4}[A-Z]{1}"
                    >
                    <small class="form-help">Required for tax exemption certificate</small>
                  </div>
                </div>

                <div class="form-group">
                  <label for="donorAddress">Address</label>
                  <textarea
                    id="donorAddress"
                    v-model="form.donorAddress"
                    rows="3"
                    class="form-input"
                    placeholder="Enter your complete address"
                  ></textarea>
                </div>
              </div>

              <!-- Anonymous Donation -->
              <div class="form-section">
                <div class="checkbox-group">
                  <input
                    id="anonymous"
                    v-model="form.anonymous"
                    type="checkbox"
                    class="checkbox-input"
                  >
                  <label for="anonymous" class="checkbox-label">
                    <strong>Make this donation anonymous</strong>
                    <br>
                    <small>Your name will not be displayed in public donor lists</small>
                  </label>
                </div>
                
                <div class="checkbox-group">
                  <input
                    id="newsletter"
                    v-model="form.newsletter"
                    type="checkbox"
                    class="checkbox-input"
                  >
                  <label for="newsletter" class="checkbox-label">
                    Subscribe to our newsletter for updates on our work and impact
                  </label>
                </div>
              </div>

              <!-- Payment Method -->
              <div class="form-section">
                <h3 class="section-title">
                  <i class="fas fa-credit-card"></i>
                  Payment Method
                </h3>
                
                <div class="payment-methods">
                  <div
                    v-for="method in paymentMethods"
                    :key="method.value"
                    class="payment-method"
                    :class="{ active: form.paymentMethod === method.value }"
                    @click="form.paymentMethod = method.value"
                  >
                    <div class="payment-icon">
                      <i :class="method.icon"></i>
                    </div>
                    <div class="payment-info">
                      <h4>{{ method.label }}</h4>
                      <p>{{ method.description }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Donation Summary -->
              <div class="donation-summary">
                <h3>Donation Summary</h3>
                <div class="summary-row">
                  <span>Donation Amount:</span>
                  <span class="amount">₹{{ formatAmount(finalAmount) }}</span>
                </div>
                <div class="summary-row">
                  <span>Purpose:</span>
                  <span>{{ getPurposeLabel(form.purpose) }}</span>
                </div>
                <div class="summary-row">
                  <span>Type:</span>
                  <span>{{ getTypeLabel(form.donationType) }}</span>
                </div>
                <div class="summary-row total">
                  <span>Total Amount:</span>
                  <span class="amount">₹{{ formatAmount(finalAmount) }}</span>
                </div>
                <div class="tax-info">
                  <i class="fas fa-info-circle"></i>
                  This donation is eligible for 80G tax exemption
                </div>
              </div>

              <!-- Submit Button -->
              <div class="form-actions">
                <button
                  type="submit"
                  class="donate-btn"
                  :disabled="!isFormValid || processing"
                >
                  <i v-if="processing" class="fas fa-spinner fa-spin"></i>
                  <i v-else class="fas fa-heart"></i>
                  {{ processing ? 'Processing...' : `Donate ₹${formatAmount(finalAmount)}` }}
                </button>
              </div>
            </form>
          </div>

          <!-- Sidebar -->
          <div class="donation-sidebar">
            <!-- Recent Donors -->
            <div class="sidebar-card">
              <h3>Recent Donors</h3>
              <div class="donor-list">
                <div
                  v-for="donor in recentDonors"
                  :key="donor.id"
                  class="donor-item"
                >
                  <div class="donor-avatar">
                    {{ donor.name.charAt(0) }}
                  </div>
                  <div class="donor-info">
                    <div class="donor-name">{{ donor.anonymous ? 'Anonymous' : donor.name }}</div>
                    <div class="donor-amount">₹{{ formatAmount(donor.amount) }}</div>
                    <div class="donor-time">{{ formatTime(donor.created_at) }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Donation Goals -->
            <div class="sidebar-card">
              <h3>Current Goals</h3>
              <div class="goal-list">
                <div
                  v-for="goal in donationGoals"
                  :key="goal.id"
                  class="goal-item"
                >
                  <div class="goal-header">
                    <h4>{{ goal.title }}</h4>
                    <span class="goal-percentage">{{ Math.round((goal.raised / goal.target) * 100) }}%</span>
                  </div>
                  <div class="goal-progress">
                    <div
                      class="progress-bar"
                      :style="{ width: Math.min((goal.raised / goal.target) * 100, 100) + '%' }"
                    ></div>
                  </div>
                  <div class="goal-amounts">
                    <span>₹{{ formatAmount(goal.raised) }} raised</span>
                    <span>₹{{ formatAmount(goal.target) }} goal</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Contact Info -->
            <div class="sidebar-card">
              <h3>Need Help?</h3>
              <div class="contact-info">
                <div class="contact-item">
                  <i class="fas fa-phone"></i>
                  <span>+91 9876543210</span>
                </div>
                <div class="contact-item">
                  <i class="fas fa-envelope"></i>
                  <span>donations@bhrc.org</span>
                </div>
                <div class="contact-item">
                  <i class="fas fa-clock"></i>
                  <span>Mon-Fri: 9 AM - 6 PM</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Success Modal -->
    <div v-if="showSuccessModal" class="modal-overlay" @click="closeSuccessModal">
      <div class="modal-content success-modal" @click.stop>
        <div class="success-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <h2>Thank You for Your Donation!</h2>
        <p>
          Your generous contribution of <strong>₹{{ formatAmount(donationResult.amount) }}</strong> 
          has been received successfully.
        </p>
        <div class="donation-details">
          <p><strong>Transaction ID:</strong> {{ donationResult.transaction_id }}</p>
          <p><strong>Receipt Number:</strong> {{ donationResult.receipt_number }}</p>
          <p><strong>Date:</strong> {{ formatDate(donationResult.date) }}</p>
        </div>
        <p>
          A receipt has been sent to your email address. You can use this for tax exemption under Section 80G.
        </p>
        <div class="modal-actions">
          <button @click="downloadReceipt" class="btn-primary">
            <i class="fas fa-download"></i>
            Download Receipt
          </button>
          <button @click="closeSuccessModal" class="btn-secondary">
            Continue
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
  name: 'Donate',
  components: {
    DefaultLayout
  },
  data() {
    return {
      loading: false,
      processing: false,
      showSuccessModal: false,
      donationResult: {},
      stats: {
        totalDonations: '₹50L+',
        casesSupported: '1,200+',
        livesImpacted: '5,000+'
      },
      form: {
        donationType: 'one_time',
        amount: 1000,
        customAmount: '',
        purpose: '',
        donorName: '',
        donorEmail: '',
        donorPhone: '',
        donorPan: '',
        donorAddress: '',
        anonymous: false,
        newsletter: true,
        paymentMethod: 'online'
      },
      errors: {},
      donationTypes: [
        {
          value: 'one_time',
          label: 'One-time Donation',
          description: 'Make a single donation to support our cause',
          icon: 'fas fa-hand-holding-heart'
        },
        {
          value: 'monthly',
          label: 'Monthly Donation',
          description: 'Become a monthly supporter with recurring donations',
          icon: 'fas fa-calendar-alt'
        }
      ],
      predefinedAmounts: [500, 1000, 2000, 5000, 10000, 25000],
      paymentMethods: [
        {
          value: 'online',
          label: 'Online Payment',
          description: 'Credit/Debit Card, UPI, Net Banking',
          icon: 'fas fa-credit-card'
        },
        {
          value: 'bank_transfer',
          label: 'Bank Transfer',
          description: 'Direct bank transfer (NEFT/RTGS)',
          icon: 'fas fa-university'
        },
        {
          value: 'cheque',
          label: 'Cheque/DD',
          description: 'Cheque or Demand Draft',
          icon: 'fas fa-money-check'
        }
      ],
      recentDonors: [],
      donationGoals: []
    }
  },
  computed: {
    finalAmount() {
      return this.form.customAmount || this.form.amount || 0
    },
    isFormValid() {
      return (
        this.finalAmount >= 100 &&
        this.form.purpose &&
        this.form.donorName &&
        this.form.donorEmail &&
        this.form.donorPhone &&
        this.form.paymentMethod
      )
    }
  },
  async mounted() {
    await this.loadDonationData()
  },
  methods: {
    async loadDonationData() {
      try {
        this.loading = true
        
        // Load recent donors
        const donorsResponse = await fetch('https://bhrcdata.online/backend/api.php/donations/recent')
        if (donorsResponse.ok) {
          const donorsData = await donorsResponse.json()
          this.recentDonors = donorsData.donors || []
        }
        
        // Load donation goals
        const goalsResponse = await fetch('https://bhrcdata.online/backend/api.php/donations/goals')
        if (goalsResponse.ok) {
          const goalsData = await goalsResponse.json()
          this.donationGoals = goalsData.goals || []
        }
        
        // Load donation stats
        const statsResponse = await fetch('https://bhrcdata.online/backend/api.php/donations/stats')
        if (statsResponse.ok) {
          const statsData = await statsResponse.json()
          if (statsData.stats) {
            this.stats = statsData.stats
          }
        }
      } catch (error) {
        console.error('Error loading donation data:', error)
      } finally {
        this.loading = false
      }
    },
    selectAmount(amount) {
      this.form.amount = amount
      this.form.customAmount = ''
    },
    onCustomAmountChange() {
      if (this.form.customAmount) {
        this.form.amount = 0
      }
    },
    formatAmount(amount) {
      if (!amount) return '0'
      return new Intl.NumberFormat('en-IN').format(amount)
    },
    formatTime(dateString) {
      const date = new Date(dateString)
      const now = new Date()
      const diffInHours = Math.floor((now - date) / (1000 * 60 * 60))
      
      if (diffInHours < 1) return 'Just now'
      if (diffInHours < 24) return `${diffInHours}h ago`
      return `${Math.floor(diffInHours / 24)}d ago`
    },
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    },
    getPurposeLabel(purpose) {
      const purposes = {
        general: 'General Fund',
        legal_aid: 'Legal Aid Program',
        education: 'Education & Awareness',
        emergency: 'Emergency Support',
        advocacy: 'Advocacy Campaigns',
        infrastructure: 'Infrastructure Development',
        research: 'Research & Documentation'
      }
      return purposes[purpose] || purpose
    },
    getTypeLabel(type) {
      const types = {
        one_time: 'One-time Donation',
        monthly: 'Monthly Donation'
      }
      return types[type] || type
    },
    validateForm() {
      this.errors = {}
      
      // Amount validation
      if (this.finalAmount < 100) {
        this.errors.amount = 'Minimum donation amount is ₹100'
      }
      
      // Required field validation
      if (!this.form.donorName.trim()) {
        this.errors.donorName = 'Name is required'
      }
      
      if (!this.form.donorEmail.trim()) {
        this.errors.donorEmail = 'Email is required'
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.donorEmail)) {
        this.errors.donorEmail = 'Please enter a valid email address'
      }
      
      if (!this.form.donorPhone.trim()) {
        this.errors.donorPhone = 'Phone number is required'
      } else if (!/^[0-9]{10}$/.test(this.form.donorPhone.replace(/\D/g, ''))) {
        this.errors.donorPhone = 'Please enter a valid 10-digit phone number'
      }
      
      // PAN validation (if provided)
      if (this.form.donorPan && !/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(this.form.donorPan)) {
        this.errors.donorPan = 'Please enter a valid PAN number'
      }
      
      return Object.keys(this.errors).length === 0
    },
    async processDonation() {
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
        this.processing = true
        
        // Prepare donation data
        const donationData = {
          ...this.form,
          amount: this.finalAmount,
          transaction_type: 'donation'
        }
        
        // Submit donation
        const response = await api.post('donations', donationData)
        
        if (response.data.success) {
          this.donationResult = {
            amount: this.finalAmount,
            transaction_id: response.data.transaction_id || 'TXN' + Date.now(),
            receipt_number: response.data.receipt_number || 'RCP' + Date.now(),
            date: new Date().toISOString()
          }
          this.showSuccessModal = true
          this.resetForm()
          
          // Reload donation data to show updated stats
          await this.loadDonationData()
        } else {
          throw new Error(response.data.message || 'Donation processing failed')
        }
      } catch (error) {
        console.error('Donation processing error:', error)
        alert('Failed to process donation: ' + error.message)
      } finally {
        this.processing = false
      }
    },
    resetForm() {
      this.form = {
        donationType: 'one_time',
        amount: 1000,
        customAmount: '',
        purpose: '',
        donorName: '',
        donorEmail: '',
        donorPhone: '',
        donorPan: '',
        donorAddress: '',
        anonymous: false,
        newsletter: true,
        paymentMethod: 'online'
      }
      this.errors = {}
    },
    closeSuccessModal() {
      this.showSuccessModal = false
    },
    downloadReceipt() {
      // Generate and download receipt
      const receiptUrl = `https://bhrcdata.online/backend/api.php/donations/receipt/${this.donationResult.transaction_id}`
      window.open(receiptUrl, '_blank')
    }
  }
}
</script>

<style scoped>
/* Hero Section */
.hero-section {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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

.impact-stats {
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

/* Impact Section */
.impact-section {
  padding: 4rem 0;
  background: #f8f9fa;
}

.section-header {
  text-align: center;
  margin-bottom: 3rem;
}

.section-header h2 {
  font-size: 2.5rem;
  color: #333;
  margin-bottom: 1rem;
}

.section-header p {
  font-size: 1.1rem;
  color: #666;
}

.impact-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2rem;
}

.impact-card {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  text-align: center;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease;
}

.impact-card:hover {
  transform: translateY(-5px);
}

.impact-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #28a745, #20c997);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
}

.impact-icon i {
  font-size: 2rem;
  color: white;
}

.impact-card h3 {
  margin-bottom: 1rem;
  color: #333;
}

.impact-card p {
  color: #666;
  line-height: 1.6;
  margin-bottom: 1rem;
}

.impact-amount {
  font-weight: 600;
  color: #28a745;
  font-size: 1.1rem;
}

/* Donation Section */
.donation-section {
  padding: 4rem 0;
}

.donation-wrapper {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 3rem;
  max-width: 1200px;
  margin: 0 auto;
}

.donation-form-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.form-header {
  background: linear-gradient(135deg, #28a745, #20c997);
  color: white;
  padding: 2rem;
  text-align: center;
}

.form-header h2 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.donation-form {
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
  border-bottom: 2px solid #28a745;
}

.section-title i {
  color: #28a745;
}

/* Donation Type Options */
.donation-type-options {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.type-option {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.type-option:hover,
.type-option.active {
  border-color: #28a745;
  background: #f8fff9;
}

.type-icon {
  width: 50px;
  height: 50px;
  background: #28a745;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.type-info h4 {
  margin-bottom: 0.25rem;
  color: #333;
}

.type-info p {
  font-size: 0.9rem;
  color: #666;
  margin: 0;
}

/* Amount Options */
.amount-options {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}

.amount-option {
  padding: 1rem;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  text-align: center;
  cursor: pointer;
  font-weight: 600;
  font-size: 1.1rem;
  transition: all 0.3s ease;
}

.amount-option:hover,
.amount-option.active {
  border-color: #28a745;
  background: #28a745;
  color: white;
}

.custom-amount {
  margin-top: 1rem;
}

.custom-amount label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #333;
}

.amount-input-group {
  display: flex;
  align-items: center;
  border: 1px solid #ddd;
  border-radius: 8px;
  overflow: hidden;
}

.currency-symbol {
  background: #f8f9fa;
  padding: 0.75rem 1rem;
  font-weight: 600;
  color: #333;
}

.amount-input {
  flex: 1;
  padding: 0.75rem;
  border: none;
  font-size: 1rem;
}

.amount-input:focus {
  outline: none;
}

.amount-help {
  display: block;
  margin-top: 0.5rem;
  color: #666;
  font-size: 0.8rem;
}

/* Form Elements */
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
  border-color: #28a745;
  box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
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

/* Payment Methods */
.payment-methods {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.payment-method {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.payment-method:hover,
.payment-method.active {
  border-color: #28a745;
  background: #f8fff9;
}

.payment-icon {
  width: 50px;
  height: 50px;
  background: #28a745;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.payment-info h4 {
  margin-bottom: 0.25rem;
  color: #333;
}

.payment-info p {
  font-size: 0.9rem;
  color: #666;
  margin: 0;
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

/* Donation Summary */
.donation-summary {
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 2rem;
}

.donation-summary h3 {
  margin-bottom: 1rem;
  color: #333;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  padding: 0.5rem 0;
}

.summary-row.total {
  border-top: 2px solid #28a745;
  margin-top: 1rem;
  font-weight: 600;
  font-size: 1.1rem;
}

.summary-row .amount {
  font-weight: 600;
  color: #28a745;
}

.tax-info {
  margin-top: 1rem;
  padding: 0.75rem;
  background: #d4edda;
  border-radius: 6px;
  color: #155724;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Form Actions */
.form-actions {
  text-align: center;
}

.donate-btn {
  background: linear-gradient(135deg, #28a745, #20c997);
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

.donate-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
}

.donate-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* Sidebar */
.donation-sidebar {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.sidebar-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.sidebar-card h3 {
  margin-bottom: 1rem;
  color: #333;
  font-size: 1.2rem;
}

/* Recent Donors */
.donor-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.donor-item {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.donor-avatar {
  width: 40px;
  height: 40px;
  background: #28a745;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
}

.donor-info {
  flex: 1;
}

.donor-name {
  font-weight: 600;
  color: #333;
}

.donor-amount {
  color: #28a745;
  font-weight: 600;
}

.donor-time {
  font-size: 0.8rem;
  color: #666;
}

/* Donation Goals */
.goal-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.goal-item {
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.goal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.goal-header h4 {
  margin: 0;
  color: #333;
  font-size: 1rem;
}

.goal-percentage {
  font-weight: 600;
  color: #28a745;
}

.goal-progress {
  height: 8px;
  background: #e9ecef;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(135deg, #28a745, #20c997);
  transition: width 0.3s ease;
}

.goal-amounts {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
  color: #666;
}

/* Contact Info */
.contact-info {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #666;
}

.contact-item i {
  color: #28a745;
  width: 20px;
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

.donation-details {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  margin: 1rem 0;
}

.donation-details p {
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
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary {
  background: #28a745;
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
  
  .impact-stats {
    flex-direction: column;
    gap: 1rem;
  }
  
  .donation-wrapper {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  
  .donation-type-options {
    grid-template-columns: 1fr;
  }
  
  .amount-options {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .form-row {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .donation-form {
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
  
  .amount-options {
    grid-template-columns: 1fr;
  }
  
  .donate-btn {
    width: 100%;
    padding: 1rem;
  }
}
</style>