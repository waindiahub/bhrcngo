<template>
  <DefaultLayout>
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="hero-content">
          <h1 class="hero-title">Join Our Mission</h1>
          <p class="hero-subtitle">
            Become a part of Bihar Human Rights Commission and help us fight for justice and human dignity
          </p>
          <div class="hero-features">
            <div class="feature-item">
              <i class="fas fa-users"></i>
              <span>Join 5000+ Members</span>
            </div>
            <div class="feature-item">
              <i class="fas fa-globe"></i>
              <span>Make Global Impact</span>
            </div>
            <div class="feature-item">
              <i class="fas fa-certificate"></i>
              <span>Get Certified</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Membership Types -->
    <section class="membership-types-section">
      <div class="container">
        <div class="section-header">
          <h2>Choose Your Membership Type</h2>
          <p>Select the membership level that best fits your commitment and goals</p>
        </div>
        
        <div class="membership-grid">
          <div
            v-for="type in membershipTypes"
            :key="type.id"
            class="membership-card"
            :class="{ active: selectedMembershipType === type.id }"
            @click="selectMembershipType(type.id)"
          >
            <div class="membership-icon">
              <i :class="type.icon"></i>
            </div>
            <h3>{{ type.name }}</h3>
            <p class="membership-description">{{ type.description }}</p>
            <div class="membership-features">
              <ul>
                <li v-for="feature in type.features" :key="feature">
                  <i class="fas fa-check"></i>
                  {{ feature }}
                </li>
              </ul>
            </div>
            <div class="membership-fee">
              <span class="fee-amount">{{ type.fee }}</span>
              <span class="fee-period">{{ type.period }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Registration Form -->
    <section class="registration-section">
      <div class="container">
        <div class="form-wrapper">
          <div class="form-header">
            <h2>Membership Registration Form</h2>
            <p>Fill out the form below to join our organization</p>
          </div>

          <form @submit.prevent="submitRegistration" class="registration-form">
            <!-- Personal Information -->
            <div class="form-section">
              <h3 class="section-title">
                <i class="fas fa-user"></i>
                Personal Information
              </h3>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="firstName">First Name *</label>
                  <input
                    id="firstName"
                    v-model="form.firstName"
                    type="text"
                    required
                    class="form-input"
                    :class="{ error: errors.firstName }"
                  >
                  <span v-if="errors.firstName" class="error-message">{{ errors.firstName }}</span>
                </div>
                
                <div class="form-group">
                  <label for="lastName">Last Name *</label>
                  <input
                    id="lastName"
                    v-model="form.lastName"
                    type="text"
                    required
                    class="form-input"
                    :class="{ error: errors.lastName }"
                  >
                  <span v-if="errors.lastName" class="error-message">{{ errors.lastName }}</span>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="email">Email Address *</label>
                  <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    class="form-input"
                    :class="{ error: errors.email }"
                  >
                  <span v-if="errors.email" class="error-message">{{ errors.email }}</span>
                </div>
                
                <div class="form-group">
                  <label for="phone">Phone Number *</label>
                  <input
                    id="phone"
                    v-model="form.phone"
                    type="tel"
                    required
                    class="form-input"
                    :class="{ error: errors.phone }"
                  >
                  <span v-if="errors.phone" class="error-message">{{ errors.phone }}</span>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="dateOfBirth">Date of Birth *</label>
                  <input
                    id="dateOfBirth"
                    v-model="form.dateOfBirth"
                    type="date"
                    required
                    class="form-input"
                    :class="{ error: errors.dateOfBirth }"
                  >
                  <span v-if="errors.dateOfBirth" class="error-message">{{ errors.dateOfBirth }}</span>
                </div>
                
                <div class="form-group">
                  <label for="gender">Gender *</label>
                  <select
                    id="gender"
                    v-model="form.gender"
                    required
                    class="form-input"
                    :class="{ error: errors.gender }"
                  >
                    <option value="">Select Gender</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                  </select>
                  <span v-if="errors.gender" class="error-message">{{ errors.gender }}</span>
                </div>
              </div>
            </div>

            <!-- Address Information -->
            <div class="form-section">
              <h3 class="section-title">
                <i class="fas fa-map-marker-alt"></i>
                Address Information
              </h3>
              
              <div class="form-group">
                <label for="address">Full Address *</label>
                <textarea
                  id="address"
                  v-model="form.address"
                  required
                  rows="3"
                  class="form-input"
                  :class="{ error: errors.address }"
                  placeholder="Enter your complete address"
                ></textarea>
                <span v-if="errors.address" class="error-message">{{ errors.address }}</span>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="state">State *</label>
                  <select
                    id="state"
                    v-model="form.state"
                    required
                    class="form-input"
                    :class="{ error: errors.state }"
                    @change="onStateChange"
                  >
                    <option value="">Select State</option>
                    <option v-for="state in states" :key="state" :value="state">
                      {{ state }}
                    </option>
                  </select>
                  <span v-if="errors.state" class="error-message">{{ errors.state }}</span>
                </div>
                
                <div class="form-group">
                  <label for="district">District *</label>
                  <select
                    id="district"
                    v-model="form.district"
                    required
                    class="form-input"
                    :class="{ error: errors.district }"
                    :disabled="!form.state"
                  >
                    <option value="">Select District</option>
                    <option v-for="district in districts" :key="district" :value="district">
                      {{ district }}
                    </option>
                  </select>
                  <span v-if="errors.district" class="error-message">{{ errors.district }}</span>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="pincode">PIN Code *</label>
                  <input
                    id="pincode"
                    v-model="form.pincode"
                    type="text"
                    required
                    pattern="[0-9]{6}"
                    class="form-input"
                    :class="{ error: errors.pincode }"
                    placeholder="6-digit PIN code"
                  >
                  <span v-if="errors.pincode" class="error-message">{{ errors.pincode }}</span>
                </div>
              </div>
            </div>

            <!-- Professional Information -->
            <div class="form-section">
              <h3 class="section-title">
                <i class="fas fa-briefcase"></i>
                Professional Information
              </h3>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="occupation">Occupation *</label>
                  <input
                    id="occupation"
                    v-model="form.occupation"
                    type="text"
                    required
                    class="form-input"
                    :class="{ error: errors.occupation }"
                  >
                  <span v-if="errors.occupation" class="error-message">{{ errors.occupation }}</span>
                </div>
                
                <div class="form-group">
                  <label for="organization">Organization/Company</label>
                  <input
                    id="organization"
                    v-model="form.organization"
                    type="text"
                    class="form-input"
                  >
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="experience">Years of Experience</label>
                  <select
                    id="experience"
                    v-model="form.experience"
                    class="form-input"
                  >
                    <option value="">Select Experience</option>
                    <option value="0-2">0-2 years</option>
                    <option value="3-5">3-5 years</option>
                    <option value="6-10">6-10 years</option>
                    <option value="11-15">11-15 years</option>
                    <option value="16+">16+ years</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label for="specialization">Area of Specialization</label>
                  <select
                    id="specialization"
                    v-model="form.specialization"
                    class="form-input"
                  >
                    <option value="">Select Specialization</option>
                    <option value="Legal Aid">Legal Aid</option>
                    <option value="Social Work">Social Work</option>
                    <option value="Human Rights">Human Rights</option>
                    <option value="Education">Education</option>
                    <option value="Healthcare">Healthcare</option>
                    <option value="Women Rights">Women Rights</option>
                    <option value="Child Rights">Child Rights</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label for="motivation">Why do you want to join us? *</label>
                <textarea
                  id="motivation"
                  v-model="form.motivation"
                  required
                  rows="4"
                  class="form-input"
                  :class="{ error: errors.motivation }"
                  placeholder="Tell us about your motivation to join our organization..."
                ></textarea>
                <span v-if="errors.motivation" class="error-message">{{ errors.motivation }}</span>
              </div>
            </div>

            <!-- Document Upload -->
            <div class="form-section">
              <h3 class="section-title">
                <i class="fas fa-file-upload"></i>
                Document Upload
              </h3>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="photo">Profile Photo</label>
                  <input
                    id="photo"
                    type="file"
                    accept="image/*"
                    class="form-input file-input"
                    @change="handleFileUpload('photo', $event)"
                  >
                  <small class="form-help">Upload a recent passport-size photograph (Max: 2MB)</small>
                </div>
                
                <div class="form-group">
                  <label for="idProof">ID Proof (Aadhar/PAN/Passport)</label>
                  <input
                    id="idProof"
                    type="file"
                    accept=".pdf,.jpg,.jpeg,.png"
                    class="form-input file-input"
                    @change="handleFileUpload('idProof', $event)"
                  >
                  <small class="form-help">Upload a valid ID proof (Max: 5MB)</small>
                </div>
              </div>
            </div>

            <!-- Terms and Conditions -->
            <div class="form-section">
              <div class="checkbox-group">
                <input
                  id="agreeTerms"
                  v-model="form.agreeTerms"
                  type="checkbox"
                  required
                  class="checkbox-input"
                >
                <label for="agreeTerms" class="checkbox-label">
                  I agree to the <a href="/terms" target="_blank">Terms and Conditions</a> and 
                  <a href="/privacy" target="_blank">Privacy Policy</a> *
                </label>
              </div>
              
              <div class="checkbox-group">
                <input
                  id="agreeNewsletter"
                  v-model="form.agreeNewsletter"
                  type="checkbox"
                  class="checkbox-input"
                >
                <label for="agreeNewsletter" class="checkbox-label">
                  I would like to receive newsletters and updates about BHRC activities
                </label>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="form-actions">
              <button
                type="submit"
                class="submit-btn"
                :disabled="submitting"
              >
                <i v-if="submitting" class="fas fa-spinner fa-spin"></i>
                <i v-else class="fas fa-user-plus"></i>
                {{ submitting ? 'Submitting...' : 'Submit Registration' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </section>

    <!-- Success Modal -->
    <div v-if="showSuccessModal" class="modal-overlay" @click="closeSuccessModal">
      <div class="modal-content success-modal" @click.stop>
        <div class="success-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <h2>Registration Successful!</h2>
        <p>
          Thank you for joining Bihar Human Rights Commission. Your application has been submitted successfully.
        </p>
        <p>
          <strong>Application ID:</strong> {{ applicationId }}
        </p>
        <p>
          We will review your application and contact you within 3-5 business days.
        </p>
        <div class="modal-actions">
          <button @click="closeSuccessModal" class="btn-primary">
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
  name: 'JoinUs',
  components: {
    DefaultLayout
  },
  data() {
    return {
      selectedMembershipType: 'general',
      submitting: false,
      showSuccessModal: false,
      applicationId: '',
      membershipTypes: [
        {
          id: 'general',
          name: 'General Member',
          description: 'Perfect for individuals who want to support our cause',
          icon: 'fas fa-user',
          fee: '₹500',
          period: '/year',
          features: [
            'Access to all events',
            'Monthly newsletter',
            'Volunteer opportunities',
            'Basic legal guidance',
            'Community support'
          ]
        },
        {
          id: 'associate',
          name: 'Associate Member',
          description: 'For professionals who want to contribute actively',
          icon: 'fas fa-user-tie',
          fee: '₹1,500',
          period: '/year',
          features: [
            'All General Member benefits',
            'Priority event access',
            'Professional networking',
            'Training workshops',
            'Certificate of membership',
            'Voting rights'
          ]
        },
        {
          id: 'life',
          name: 'Life Member',
          description: 'One-time payment for lifetime commitment',
          icon: 'fas fa-crown',
          fee: '₹10,000',
          period: 'one-time',
          features: [
            'All Associate Member benefits',
            'Lifetime membership',
            'Special recognition',
            'Advisory board eligibility',
            'Exclusive events access',
            'Mentorship opportunities'
          ]
        }
      ],
      form: {
        firstName: '',
        lastName: '',
        email: '',
        phone: '',
        dateOfBirth: '',
        gender: '',
        address: '',
        state: '',
        district: '',
        pincode: '',
        occupation: '',
        organization: '',
        experience: '',
        specialization: '',
        motivation: '',
        membershipType: 'general',
        agreeTerms: false,
        agreeNewsletter: false
      },
      errors: {},
      states: [
        'Bihar', 'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Chhattisgarh',
        'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand',
        'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur',
        'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab',
        'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura',
        'Uttar Pradesh', 'Uttarakhand', 'West Bengal'
      ],
      districts: [],
      biharDistricts: [
        'Araria', 'Arwal', 'Aurangabad', 'Banka', 'Begusarai', 'Bhagalpur',
        'Bhojpur', 'Buxar', 'Darbhanga', 'East Champaran', 'Gaya', 'Gopalganj',
        'Jamui', 'Jehanabad', 'Kaimur', 'Katihar', 'Khagaria', 'Kishanganj',
        'Lakhisarai', 'Madhepura', 'Madhubani', 'Munger', 'Muzaffarpur',
        'Nalanda', 'Nawada', 'Patna', 'Purnia', 'Rohtas', 'Saharsa',
        'Samastipur', 'Saran', 'Sheikhpura', 'Sheohar', 'Sitamarhi',
        'Siwan', 'Supaul', 'Vaishali', 'West Champaran'
      ],
      uploadedFiles: {}
    }
  },
  watch: {
    selectedMembershipType(newType) {
      this.form.membershipType = newType
    }
  },
  methods: {
    selectMembershipType(typeId) {
      this.selectedMembershipType = typeId
      this.form.membershipType = typeId
    },
    onStateChange() {
      this.form.district = ''
      if (this.form.state === 'Bihar') {
        this.districts = this.biharDistricts
      } else {
        this.districts = []
      }
    },
    handleFileUpload(fieldName, event) {
      const file = event.target.files[0]
      if (file) {
        // Validate file size
        const maxSize = fieldName === 'photo' ? 2 * 1024 * 1024 : 5 * 1024 * 1024 // 2MB for photo, 5MB for ID
        if (file.size > maxSize) {
          alert(`File size should not exceed ${fieldName === 'photo' ? '2MB' : '5MB'}`)
          event.target.value = ''
          return
        }
        
        this.uploadedFiles[fieldName] = file
      }
    },
    validateForm() {
      this.errors = {}
      
      // Required field validation
      const requiredFields = [
        'firstName', 'lastName', 'email', 'phone', 'dateOfBirth', 'gender',
        'address', 'state', 'district', 'pincode', 'occupation', 'motivation'
      ]
      
      requiredFields.forEach(field => {
        if (!this.form[field] || this.form[field].trim() === '') {
          this.errors[field] = 'This field is required'
        }
      })
      
      // Email validation
      if (this.form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
        this.errors.email = 'Please enter a valid email address'
      }
      
      // Phone validation
      if (this.form.phone && !/^[0-9]{10}$/.test(this.form.phone.replace(/\D/g, ''))) {
        this.errors.phone = 'Please enter a valid 10-digit phone number'
      }
      
      // PIN code validation
      if (this.form.pincode && !/^[0-9]{6}$/.test(this.form.pincode)) {
        this.errors.pincode = 'Please enter a valid 6-digit PIN code'
      }
      
      // Age validation (minimum 18 years)
      if (this.form.dateOfBirth) {
        const today = new Date()
        const birthDate = new Date(this.form.dateOfBirth)
        const age = today.getFullYear() - birthDate.getFullYear()
        if (age < 18) {
          this.errors.dateOfBirth = 'You must be at least 18 years old to register'
        }
      }
      
      // Terms agreement validation
      if (!this.form.agreeTerms) {
        this.errors.agreeTerms = 'You must agree to the terms and conditions'
      }
      
      return Object.keys(this.errors).length === 0
    },
    async submitRegistration() {
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
        this.submitting = true
        
        // Prepare form data
        const formData = new FormData()
        
        // Add form fields
        Object.keys(this.form).forEach(key => {
          if (this.form[key] !== null && this.form[key] !== '') {
            formData.append(key, this.form[key])
          }
        })
        
        // Add uploaded files
        Object.keys(this.uploadedFiles).forEach(key => {
          formData.append(key, this.uploadedFiles[key])
        })
        
        // Submit to API
        const response = await api.post('membership/register', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })
        
        if (response.data.success) {
          this.applicationId = response.data.application_id || 'BHRC' + Date.now()
          this.showSuccessModal = true
          this.resetForm()
        } else {
          throw new Error(response.data.message || 'Registration failed')
        }
      } catch (error) {
        console.error('Registration error:', error)
        alert('Registration failed: ' + error.message)
      } finally {
        this.submitting = false
      }
    },
    resetForm() {
      this.form = {
        firstName: '',
        lastName: '',
        email: '',
        phone: '',
        dateOfBirth: '',
        gender: '',
        address: '',
        state: '',
        district: '',
        pincode: '',
        occupation: '',
        organization: '',
        experience: '',
        specialization: '',
        motivation: '',
        membershipType: 'general',
        agreeTerms: false,
        agreeNewsletter: false
      }
      this.errors = {}
      this.uploadedFiles = {}
      this.selectedMembershipType = 'general'
    },
    closeSuccessModal() {
      this.showSuccessModal = false
      // Optionally redirect to home or login page
      this.$router.push('/')
    }
  }
}
</script>

<style scoped>
/* Hero Section */
.hero-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

.hero-features {
  display: flex;
  justify-content: center;
  gap: 3rem;
  margin-top: 2rem;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1rem;
}

.feature-item i {
  font-size: 1.2rem;
  color: #ffd700;
}

/* Membership Types */
.membership-types-section {
  padding: 4rem 0;
  background: #f8f9fa;
}

.section-header {
  text-align: center;
  margin-bottom: 3rem;
}

.section-header h2 {
  font-size: 2.5rem;
  margin-bottom: 1rem;
  color: #333;
}

.membership-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  margin-top: 2rem;
}

.membership-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  text-align: center;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  cursor: pointer;
  border: 2px solid transparent;
}

.membership-card:hover,
.membership-card.active {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  border-color: #667eea;
}

.membership-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
}

.membership-icon i {
  font-size: 2rem;
  color: white;
}

.membership-card h3 {
  font-size: 1.5rem;
  margin-bottom: 1rem;
  color: #333;
}

.membership-description {
  color: #666;
  margin-bottom: 1.5rem;
  line-height: 1.6;
}

.membership-features ul {
  list-style: none;
  padding: 0;
  text-align: left;
  margin-bottom: 2rem;
}

.membership-features li {
  padding: 0.5rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.membership-features i {
  color: #28a745;
  font-size: 0.9rem;
}

.membership-fee {
  border-top: 1px solid #eee;
  padding-top: 1.5rem;
}

.fee-amount {
  font-size: 2rem;
  font-weight: 700;
  color: #667eea;
}

.fee-period {
  color: #666;
  font-size: 0.9rem;
}

/* Registration Form */
.registration-section {
  padding: 4rem 0;
}

.form-wrapper {
  max-width: 800px;
  margin: 0 auto;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.form-header {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  padding: 2rem;
  text-align: center;
}

.form-header h2 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.registration-form {
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
  border-bottom: 2px solid #667eea;
}

.section-title i {
  color: #667eea;
}

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
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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

.file-input {
  padding: 0.5rem;
}

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

.checkbox-label a {
  color: #667eea;
  text-decoration: none;
}

.checkbox-label a:hover {
  text-decoration: underline;
}

.form-actions {
  text-align: center;
  margin-top: 2rem;
}

.submit-btn {
  background: linear-gradient(135deg, #667eea, #764ba2);
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

.submit-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.submit-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
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

.success-modal {
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

.modal-actions {
  margin-top: 2rem;
}

.btn-primary {
  background: #667eea;
  color: white;
  border: none;
  padding: 0.75rem 2rem;
  border-radius: 8px;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 600;
}

/* Responsive Design */
@media (max-width: 768px) {
  .hero-title {
    font-size: 2rem;
  }
  
  .hero-features {
    flex-direction: column;
    gap: 1rem;
  }
  
  .membership-grid {
    grid-template-columns: 1fr;
  }
  
  .form-row {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .registration-form {
    padding: 1rem;
  }
  
  .form-header {
    padding: 1.5rem;
  }
}

@media (max-width: 480px) {
  .hero-section {
    padding: 60px 0;
  }
  
  .section-header h2 {
    font-size: 2rem;
  }
  
  .membership-card {
    padding: 1.5rem;
  }
  
  .submit-btn {
    width: 100%;
    padding: 1rem;
  }
}
</style>