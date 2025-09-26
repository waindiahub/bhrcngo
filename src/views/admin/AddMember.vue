<template>
  <div class="add-member-page">
    <!-- Header Section -->
    <div class="page-header">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="col-md-6">
            <h1 class="page-title">
              <i class="fas fa-user-plus me-3"></i>
              Add New Member
            </h1>
            <p class="page-subtitle">Register a new member to the organization</p>
          </div>
          <div class="col-md-6 text-md-end">
            <router-link to="/admin/members" class="btn btn-outline-secondary me-2">
              <i class="fas fa-arrow-left me-2"></i>
              Back to Members
            </router-link>
            <button 
              class="btn btn-info me-2"
              @click="generateMemberId"
            >
              <i class="fas fa-refresh me-2"></i>
              Generate ID
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Form Section -->
    <div class="form-section">
      <div class="container-fluid">
        <form @submit.prevent="submitMember" class="member-form">
          <div class="row">
            <!-- Main Form -->
            <div class="col-lg-8">
              <!-- Personal Information -->
              <div class="card mb-4">
                <div class="card-header">
                  <h5 class="card-title mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                  <div class="row mb-3">
                    <div class="col-md-4">
                      <label class="form-label required">First Name</label>
                      <input 
                        type="text" 
                        class="form-control"
                        v-model="member.first_name"
                        placeholder="Enter first name"
                        required
                      >
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Middle Name</label>
                      <input 
                        type="text" 
                        class="form-control"
                        v-model="member.middle_name"
                        placeholder="Enter middle name"
                      >
                    </div>
                    <div class="col-md-4">
                      <label class="form-label required">Last Name</label>
                      <input 
                        type="text" 
                        class="form-control"
                        v-model="member.last_name"
                        placeholder="Enter last name"
                        required
                      >
                    </div>
                  </div>

                  <div class="row mb-3">
                    <div class="col-md-6">
                      <label class="form-label required">Email</label>
                      <input 
                        type="email" 
                        class="form-control"
                        v-model="member.email"
                        placeholder="Enter email address"
                        required
                      >
                    </div>
                    <div class="col-md-6">
                      <label class="form-label required">Phone Number</label>
                      <input 
                        type="tel" 
                        class="form-control"
                        v-model="member.phone"
                        placeholder="Enter phone number"
                        required
                      >
                    </div>
                  </div>

                  <div class="row mb-3">
                    <div class="col-md-4">
                      <label class="form-label required">Date of Birth</label>
                      <input 
                        type="date" 
                        class="form-control"
                        v-model="member.date_of_birth"
                        required
                      >
                    </div>
                    <div class="col-md-4">
                      <label class="form-label required">Gender</label>
                      <select class="form-select" v-model="member.gender" required>
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Marital Status</label>
                      <select class="form-select" v-model="member.marital_status">
                        <option value="">Select Status</option>
                        <option value="single">Single</option>
                        <option value="married">Married</option>
                        <option value="divorced">Divorced</option>
                        <option value="widowed">Widowed</option>
                      </select>
                    </div>
                  </div>

                  <div class="row mb-3">
                    <div class="col-md-6">
                      <label class="form-label">Father's Name</label>
                      <input 
                        type="text" 
                        class="form-control"
                        v-model="member.father_name"
                        placeholder="Enter father's name"
                      >
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Mother's Name</label>
                      <input 
                        type="text" 
                        class="form-control"
                        v-model="member.mother_name"
                        placeholder="Enter mother's name"
                      >
                    </div>
                  </div>
                </div>
              </div>

              <!-- Address Information -->
              <div class="card mb-4">
                <div class="card-header">
                  <h5 class="card-title mb-0">Address Information</h5>
                </div>
                <div class="card-body">
                  <div class="mb-3">
                    <label class="form-label required">Street Address</label>
                    <textarea 
                      class="form-control"
                      v-model="member.address"
                      rows="2"
                      placeholder="Enter complete address"
                      required
                    ></textarea>
                  </div>

                  <div class="row mb-3">
                    <div class="col-md-4">
                      <label class="form-label required">City</label>
                      <input 
                        type="text" 
                        class="form-control"
                        v-model="member.city"
                        placeholder="Enter city"
                        required
                      >
                    </div>
                    <div class="col-md-4">
                      <label class="form-label required">State</label>
                      <select class="form-select" v-model="member.state" required>
                        <option value="">Select State</option>
                        <option v-for="state in indianStates" :key="state" :value="state">
                          {{ state }}
                        </option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label required">PIN Code</label>
                      <input 
                        type="text" 
                        class="form-control"
                        v-model="member.pincode"
                        placeholder="Enter PIN code"
                        pattern="[0-9]{6}"
                        required
                      >
                    </div>
                  </div>
                </div>
              </div>

              <!-- Professional Information -->
              <div class="card mb-4">
                <div class="card-header">
                  <h5 class="card-title mb-0">Professional Information</h5>
                </div>
                <div class="card-body">
                  <div class="row mb-3">
                    <div class="col-md-6">
                      <label class="form-label">Occupation</label>
                      <input 
                        type="text" 
                        class="form-control"
                        v-model="member.occupation"
                        placeholder="Enter occupation"
                      >
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Organization</label>
                      <input 
                        type="text" 
                        class="form-control"
                        v-model="member.organization"
                        placeholder="Enter organization name"
                      >
                    </div>
                  </div>

                  <div class="row mb-3">
                    <div class="col-md-6">
                      <label class="form-label">Education</label>
                      <select class="form-select" v-model="member.education">
                        <option value="">Select Education Level</option>
                        <option value="high_school">High School</option>
                        <option value="diploma">Diploma</option>
                        <option value="bachelor">Bachelor's Degree</option>
                        <option value="master">Master's Degree</option>
                        <option value="phd">PhD</option>
                        <option value="other">Other</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Annual Income</label>
                      <select class="form-select" v-model="member.annual_income">
                        <option value="">Select Income Range</option>
                        <option value="below_2_lakh">Below ₹2 Lakh</option>
                        <option value="2_5_lakh">₹2-5 Lakh</option>
                        <option value="5_10_lakh">₹5-10 Lakh</option>
                        <option value="10_20_lakh">₹10-20 Lakh</option>
                        <option value="above_20_lakh">Above ₹20 Lakh</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Emergency Contact -->
              <div class="card">
                <div class="card-header">
                  <h5 class="card-title mb-0">Emergency Contact</h5>
                </div>
                <div class="card-body">
                  <div class="row mb-3">
                    <div class="col-md-6">
                      <label class="form-label">Contact Name</label>
                      <input 
                        type="text" 
                        class="form-control"
                        v-model="member.emergency_contact_name"
                        placeholder="Enter emergency contact name"
                      >
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Contact Phone</label>
                      <input 
                        type="tel" 
                        class="form-control"
                        v-model="member.emergency_contact_phone"
                        placeholder="Enter emergency contact phone"
                      >
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Relationship</label>
                    <select class="form-select" v-model="member.emergency_contact_relation">
                      <option value="">Select Relationship</option>
                      <option value="spouse">Spouse</option>
                      <option value="parent">Parent</option>
                      <option value="sibling">Sibling</option>
                      <option value="child">Child</option>
                      <option value="friend">Friend</option>
                      <option value="other">Other</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
              <!-- Member Details -->
              <div class="card mb-4">
                <div class="card-header">
                  <h6 class="card-title mb-0">Member Details</h6>
                </div>
                <div class="card-body">
                  <div class="mb-3">
                    <label class="form-label required">Member ID</label>
                    <div class="input-group">
                      <input 
                        type="text" 
                        class="form-control"
                        v-model="member.member_id"
                        placeholder="Auto-generated"
                        readonly
                      >
                      <button 
                        type="button" 
                        class="btn btn-outline-secondary"
                        @click="generateMemberId"
                      >
                        <i class="fas fa-refresh"></i>
                      </button>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label required">Membership Type</label>
                    <select class="form-select" v-model="member.membership_type" required>
                      <option value="">Select Type</option>
                      <option value="regular">Regular Member</option>
                      <option value="life">Life Member</option>
                      <option value="associate">Associate Member</option>
                      <option value="honorary">Honorary Member</option>
                    </select>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Join Date</label>
                    <input 
                      type="date" 
                      class="form-control"
                      v-model="member.join_date"
                    >
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" v-model="member.status">
                      <option value="active">Active</option>
                      <option value="inactive">Inactive</option>
                      <option value="suspended">Suspended</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Profile Picture -->
              <div class="card mb-4">
                <div class="card-header">
                  <h6 class="card-title mb-0">Profile Picture</h6>
                </div>
                <div class="card-body">
                  <div class="profile-upload-area" @click="$refs.profileInput.click()">
                    <div v-if="!member.profile_picture" class="upload-placeholder">
                      <i class="fas fa-user-circle fa-4x text-muted mb-3"></i>
                      <p class="text-muted">Click to upload photo</p>
                      <small class="text-muted">Max: 1MB, JPG/PNG</small>
                    </div>
                    <div v-else class="uploaded-profile">
                      <img :src="member.profile_picture" alt="Profile" class="profile-img">
                      <div class="profile-overlay">
                        <button type="button" class="btn btn-sm btn-danger" @click.stop="removeProfilePicture">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                  <input 
                    type="file" 
                    ref="profileInput" 
                    @change="handleProfileUpload"
                    accept="image/*"
                    class="d-none"
                  >
                </div>
              </div>

              <!-- Additional Information -->
              <div class="card">
                <div class="card-header">
                  <h6 class="card-title mb-0">Additional Information</h6>
                </div>
                <div class="card-body">
                  <div class="mb-3">
                    <label class="form-label">Blood Group</label>
                    <select class="form-select" v-model="member.blood_group">
                      <option value="">Select Blood Group</option>
                      <option value="A+">A+</option>
                      <option value="A-">A-</option>
                      <option value="B+">B+</option>
                      <option value="B-">B-</option>
                      <option value="AB+">AB+</option>
                      <option value="AB-">AB-</option>
                      <option value="O+">O+</option>
                      <option value="O-">O-</option>
                    </select>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Interests</label>
                    <textarea 
                      class="form-control"
                      v-model="member.interests"
                      rows="3"
                      placeholder="Areas of interest, skills, hobbies"
                    ></textarea>
                  </div>

                  <div class="mb-3">
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        v-model="member.newsletter_subscription"
                        id="newsletter"
                      >
                      <label class="form-check-label" for="newsletter">
                        Subscribe to Newsletter
                      </label>
                    </div>
                  </div>

                  <div class="mb-3">
                    <div class="form-check">
                      <input 
                        class="form-check-input" 
                        type="checkbox" 
                        v-model="member.sms_notifications"
                        id="sms"
                      >
                      <label class="form-check-label" for="sms">
                        SMS Notifications
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="form-actions mt-4">
            <div class="d-flex justify-content-between">
              <div>
                <router-link to="/admin/members" class="btn btn-outline-secondary">
                  <i class="fas fa-times me-2"></i>
                  Cancel
                </router-link>
              </div>
              <div>
                <button 
                  type="button" 
                  class="btn btn-outline-info me-2"
                  @click="sendWelcomeEmail"
                  :disabled="!member.email"
                >
                  <i class="fas fa-envelope me-2"></i>
                  Send Welcome Email
                </button>
                <button 
                  type="submit" 
                  class="btn btn-success"
                  :disabled="loading"
                >
                  <i class="fas fa-user-plus me-2"></i>
                  {{ loading ? 'Adding Member...' : 'Add Member' }}
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { api } from '@/utils/api'

export default {
  name: 'AddMember',
  data() {
    return {
      loading: false,
      member: {
        member_id: '',
        first_name: '',
        middle_name: '',
        last_name: '',
        email: '',
        phone: '',
        date_of_birth: '',
        gender: '',
        marital_status: '',
        father_name: '',
        mother_name: '',
        address: '',
        city: '',
        state: '',
        pincode: '',
        occupation: '',
        organization: '',
        education: '',
        annual_income: '',
        emergency_contact_name: '',
        emergency_contact_phone: '',
        emergency_contact_relation: '',
        membership_type: 'regular',
        join_date: new Date().toISOString().split('T')[0],
        status: 'active',
        profile_picture: null,
        blood_group: '',
        interests: '',
        newsletter_subscription: true,
        sms_notifications: true
      },
      indianStates: [
        'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
        'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka',
        'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram',
        'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu',
        'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
        'Andaman and Nicobar Islands', 'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu',
        'Delhi', 'Jammu and Kashmir', 'Ladakh', 'Lakshadweep', 'Puducherry'
      ]
    }
  },

  methods: {
    async submitMember() {
      try {
        this.loading = true
        
        // Validate required fields
        if (!this.validateForm()) {
          return
        }

        // Create FormData for file upload
        const formData = new FormData()
        Object.keys(this.member).forEach(key => {
          if (this.member[key] !== null && this.member[key] !== '') {
            formData.append(key, this.member[key])
          }
        })

        // Mock API call - replace with actual API
        console.log('Adding member:', this.member)
        
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        this.$toast.success('Member added successfully!')
        this.$router.push('/admin/members')
        
      } catch (error) {
        console.error('Error adding member:', error)
        this.$toast.error('Failed to add member')
      } finally {
        this.loading = false
      }
    },

    validateForm() {
      if (!this.member.first_name.trim()) {
        this.$toast.error('First name is required')
        return false
      }
      
      if (!this.member.last_name.trim()) {
        this.$toast.error('Last name is required')
        return false
      }
      
      if (!this.member.email.trim()) {
        this.$toast.error('Email is required')
        return false
      }
      
      if (!this.member.phone.trim()) {
        this.$toast.error('Phone number is required')
        return false
      }
      
      if (!this.member.date_of_birth) {
        this.$toast.error('Date of birth is required')
        return false
      }
      
      if (!this.member.gender) {
        this.$toast.error('Gender is required')
        return false
      }
      
      if (!this.member.address.trim()) {
        this.$toast.error('Address is required')
        return false
      }
      
      if (!this.member.city.trim()) {
        this.$toast.error('City is required')
        return false
      }
      
      if (!this.member.state) {
        this.$toast.error('State is required')
        return false
      }
      
      if (!this.member.pincode.trim()) {
        this.$toast.error('PIN code is required')
        return false
      }
      
      if (!this.member.membership_type) {
        this.$toast.error('Membership type is required')
        return false
      }
      
      return true
    },

    generateMemberId() {
      const year = new Date().getFullYear()
      const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0')
      this.member.member_id = `BHRC${year}${random}`
    },

    handleProfileUpload(event) {
      const file = event.target.files[0]
      if (file) {
        // Validate file size (1MB max)
        if (file.size > 1024 * 1024) {
          this.$toast.error('Profile picture size should be less than 1MB')
          return
        }
        
        // Validate file type
        if (!file.type.startsWith('image/')) {
          this.$toast.error('Please select a valid image file')
          return
        }
        
        // Create preview URL
        const reader = new FileReader()
        reader.onload = (e) => {
          this.member.profile_picture = e.target.result
        }
        reader.readAsDataURL(file)
      }
    },

    removeProfilePicture() {
      this.member.profile_picture = null
      this.$refs.profileInput.value = ''
    },

    sendWelcomeEmail() {
      if (!this.member.email) {
        this.$toast.error('Email is required to send welcome email')
        return
      }
      
      // Mock welcome email functionality
      this.$toast.success('Welcome email will be sent after member registration')
    }
  },

  mounted() {
    document.title = 'Add Member - Admin - BHRC'
    this.generateMemberId()
  }
}
</script>

<style scoped>
.add-member-page {
  background-color: #f8f9fa;
  min-height: 100vh;
}

.page-header {
  background: white;
  border-bottom: 1px solid #e9ecef;
  padding: 2rem 0;
  margin-bottom: 2rem;
}

.page-title {
  font-size: 2rem;
  font-weight: 600;
  color: #333;
  margin-bottom: 0.5rem;
}

.page-subtitle {
  color: #666;
  margin-bottom: 0;
}

.form-section {
  padding-bottom: 3rem;
}

.member-form .card {
  border: none;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  margin-bottom: 2rem;
}

.card-header {
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
  padding: 1rem 1.5rem;
}

.card-title {
  font-weight: 600;
  color: #333;
}

.form-label.required::after {
  content: ' *';
  color: #dc3545;
}

.profile-upload-area {
  border: 2px dashed #dee2e6;
  border-radius: 8px;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
}

.profile-upload-area:hover {
  border-color: #007bff;
  background-color: #f8f9fa;
}

.uploaded-profile {
  position: relative;
}

.profile-img {
  width: 150px;
  height: 150px;
  object-fit: cover;
  border-radius: 50%;
}

.profile-overlay {
  position: absolute;
  top: 10px;
  right: 10px;
}

.form-actions {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
  .page-header {
    padding: 1rem 0;
  }
  
  .page-title {
    font-size: 1.5rem;
  }
  
  .form-actions .d-flex {
    flex-direction: column;
    gap: 1rem;
  }
  
  .form-actions .d-flex > div {
    text-align: center;
  }
}
</style>