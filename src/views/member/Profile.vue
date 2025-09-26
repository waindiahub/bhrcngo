<template>
  <div class="profile-page">
    <!-- Header Section -->
    <div class="profile-header">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-8">
            <div class="header-content">
              <h1 class="page-title">
                <i class="fas fa-user-circle me-3"></i>
                My Profile
              </h1>
              <p class="page-subtitle">
                Manage your personal information, preferences, and account settings
              </p>
            </div>
          </div>
          <div class="col-lg-4 text-lg-end">
            <div class="header-actions">
              <button 
                class="btn btn-outline-primary me-2"
                @click="downloadProfile"
                :disabled="downloading"
              >
                <i class="fas fa-download me-2"></i>
                {{ downloading ? 'Downloading...' : 'Download Profile' }}
              </button>
              <button 
                class="btn btn-primary"
                @click="editMode = !editMode"
              >
                <i class="fas me-2" :class="editMode ? 'fa-times' : 'fa-edit'"></i>
                {{ editMode ? 'Cancel' : 'Edit Profile' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Profile Content -->
    <div class="profile-content">
      <div class="container">
        <div class="row">
          <!-- Profile Sidebar -->
          <div class="col-lg-4">
            <div class="profile-sidebar">
              <!-- Profile Card -->
              <div class="profile-card">
                <div class="profile-avatar-section">
                  <div class="avatar-container">
                    <img 
                      :src="profile.avatar || '/images/default-avatar.png'" 
                      :alt="profile.name"
                      class="profile-avatar"
                    >
                    <div class="avatar-overlay" v-if="editMode">
                      <button class="btn btn-sm btn-light" @click="triggerFileUpload">
                        <i class="fas fa-camera"></i>
                      </button>
                      <input 
                        ref="avatarInput" 
                        type="file" 
                        accept="image/*" 
                        @change="handleAvatarUpload"
                        style="display: none"
                      >
                    </div>
                  </div>
                  <div class="profile-basic-info">
                    <h3 class="profile-name">{{ profile.name }}</h3>
                    <p class="profile-designation">{{ profile.designation || 'Member' }}</p>
                    <div class="profile-badges">
                      <span class="badge bg-primary">{{ profile.membership_type || 'Standard' }} Member</span>
                      <span class="badge bg-success" v-if="profile.verified">
                        <i class="fas fa-check-circle me-1"></i>
                        Verified
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Profile Stats -->
                <div class="profile-stats">
                  <div class="stat-item">
                    <div class="stat-value">{{ profileStats.completionPercentage }}%</div>
                    <div class="stat-label">Profile Complete</div>
                  </div>
                  <div class="stat-item">
                    <div class="stat-value">{{ profileStats.memberSince }}</div>
                    <div class="stat-label">Member Since</div>
                  </div>
                  <div class="stat-item">
                    <div class="stat-value">{{ profileStats.totalComplaints }}</div>
                    <div class="stat-label">Complaints Filed</div>
                  </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                  <h4>Quick Actions</h4>
                  <div class="action-buttons">
                    <router-link to="/member/complaints" class="action-btn">
                      <i class="fas fa-file-alt"></i>
                      <span>File Complaint</span>
                    </router-link>
                    <router-link to="/member/events" class="action-btn">
                      <i class="fas fa-calendar"></i>
                      <span>My Events</span>
                    </router-link>
                    <router-link to="/member/certificates" class="action-btn">
                      <i class="fas fa-certificate"></i>
                      <span>Certificates</span>
                    </router-link>
                    <router-link to="/member/donations" class="action-btn">
                      <i class="fas fa-heart"></i>
                      <span>Donations</span>
                    </router-link>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Profile Form -->
          <div class="col-lg-8">
            <div class="profile-form-container">
              <!-- Success/Error Messages -->
              <div v-if="successMessage" class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                {{ successMessage }}
                <button type="button" class="btn-close" @click="successMessage = ''"></button>
              </div>

              <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ errorMessage }}
                <button type="button" class="btn-close" @click="errorMessage = ''"></button>
              </div>

              <!-- Profile Tabs -->
              <div class="profile-tabs">
                <ul class="nav nav-tabs" role="tablist">
                  <li class="nav-item">
                    <button 
                      class="nav-link" 
                      :class="{ active: activeTab === 'personal' }"
                      @click="activeTab = 'personal'"
                    >
                      <i class="fas fa-user me-2"></i>
                      Personal Information
                    </button>
                  </li>
                  <li class="nav-item">
                    <button 
                      class="nav-link" 
                      :class="{ active: activeTab === 'address' }"
                      @click="activeTab = 'address'"
                    >
                      <i class="fas fa-map-marker-alt me-2"></i>
                      Address Details
                    </button>
                  </li>
                  <li class="nav-item">
                    <button 
                      class="nav-link" 
                      :class="{ active: activeTab === 'professional' }"
                      @click="activeTab = 'professional'"
                    >
                      <i class="fas fa-briefcase me-2"></i>
                      Professional Info
                    </button>
                  </li>
                  <li class="nav-item">
                    <button 
                      class="nav-link" 
                      :class="{ active: activeTab === 'documents' }"
                      @click="activeTab = 'documents'"
                    >
                      <i class="fas fa-file-upload me-2"></i>
                      Documents
                    </button>
                  </li>
                  <li class="nav-item">
                    <button 
                      class="nav-link" 
                      :class="{ active: activeTab === 'security' }"
                      @click="activeTab = 'security'"
                    >
                      <i class="fas fa-shield-alt me-2"></i>
                      Security
                    </button>
                  </li>
                </ul>
              </div>

              <!-- Tab Content -->
              <div class="tab-content">
                <!-- Personal Information Tab -->
                <div v-show="activeTab === 'personal'" class="tab-pane">
                  <form @submit.prevent="updateProfile" class="profile-form">
                    <div class="form-section">
                      <h4 class="section-title">Basic Information</h4>
                      
                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label class="form-label">
                              <i class="fas fa-user me-2"></i>
                              Full Name *
                            </label>
                            <input 
                              type="text" 
                              class="form-control"
                              v-model="profile.name"
                              :disabled="!editMode"
                              required
                            >
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label class="form-label">
                              <i class="fas fa-envelope me-2"></i>
                              Email Address *
                            </label>
                            <input 
                              type="email" 
                              class="form-control"
                              v-model="profile.email"
                              :disabled="!editMode"
                              required
                            >
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label class="form-label">
                              <i class="fas fa-phone me-2"></i>
                              Phone Number *
                            </label>
                            <input 
                              type="tel" 
                              class="form-control"
                              v-model="profile.phone"
                              :disabled="!editMode"
                              required
                            >
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label class="form-label">
                              <i class="fas fa-calendar me-2"></i>
                              Date of Birth
                            </label>
                            <input 
                              type="date" 
                              class="form-control"
                              v-model="profile.date_of_birth"
                              :disabled="!editMode"
                            >
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label class="form-label">
                              <i class="fas fa-venus-mars me-2"></i>
                              Gender
                            </label>
                            <select 
                              class="form-control"
                              v-model="profile.gender"
                              :disabled="!editMode"
                            >
                              <option value="">Select Gender</option>
                              <option value="male">Male</option>
                              <option value="female">Female</option>
                              <option value="other">Other</option>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label class="form-label">
                              <i class="fas fa-id-card me-2"></i>
                              Aadhar Number
                            </label>
                            <input 
                              type="text" 
                              class="form-control"
                              v-model="profile.aadhar_number"
                              :disabled="!editMode"
                              placeholder="XXXX-XXXX-XXXX"
                            >
                          </div>
                        </div>
                      </div>

                      <div class="form-group">
                        <label class="form-label">
                          <i class="fas fa-info-circle me-2"></i>
                          About Me
                        </label>
                        <textarea 
                          class="form-control"
                          v-model="profile.bio"
                          :disabled="!editMode"
                          rows="4"
                          placeholder="Tell us about yourself..."
                        ></textarea>
                      </div>
                    </div>

                    <div class="form-actions" v-if="editMode">
                      <button type="submit" class="btn btn-primary" :disabled="updating">
                        <i class="fas fa-save me-2"></i>
                        {{ updating ? 'Updating...' : 'Save Changes' }}
                      </button>
                      <button type="button" class="btn btn-secondary" @click="cancelEdit">
                        <i class="fas fa-times me-2"></i>
                        Cancel
                      </button>
                    </div>
                  </form>
                </div>

                <!-- Address Details Tab -->
                <div v-show="activeTab === 'address'" class="tab-pane">
                  <form @submit.prevent="updateAddress" class="profile-form">
                    <div class="form-section">
                      <h4 class="section-title">Current Address</h4>
                      
                      <div class="form-group">
                        <label class="form-label">
                          <i class="fas fa-home me-2"></i>
                          Address Line 1 *
                        </label>
                        <input 
                          type="text" 
                          class="form-control"
                          v-model="profile.address_line1"
                          :disabled="!editMode"
                          required
                        >
                      </div>

                      <div class="form-group">
                        <label class="form-label">
                          <i class="fas fa-home me-2"></i>
                          Address Line 2
                        </label>
                        <input 
                          type="text" 
                          class="form-control"
                          v-model="profile.address_line2"
                          :disabled="!editMode"
                        >
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label class="form-label">
                              <i class="fas fa-map me-2"></i>
                              State *
                            </label>
                            <select 
                              class="form-control"
                              v-model="profile.state"
                              :disabled="!editMode"
                              @change="loadDistricts"
                              required
                            >
                              <option value="">Select State</option>
                              <option 
                                v-for="state in states" 
                                :key="state.id" 
                                :value="state.name"
                              >
                                {{ state.name }}
                              </option>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label class="form-label">
                              <i class="fas fa-map-marker me-2"></i>
                              District *
                            </label>
                            <select 
                              class="form-control"
                              v-model="profile.district"
                              :disabled="!editMode || !profile.state"
                              required
                            >
                              <option value="">Select District</option>
                              <option 
                                v-for="district in districts" 
                                :key="district.id" 
                                :value="district.name"
                              >
                                {{ district.name }}
                              </option>
                            </select>
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label class="form-label">
                              <i class="fas fa-city me-2"></i>
                              City *
                            </label>
                            <input 
                              type="text" 
                              class="form-control"
                              v-model="profile.city"
                              :disabled="!editMode"
                              required
                            >
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label class="form-label">
                              <i class="fas fa-mail-bulk me-2"></i>
                              PIN Code *
                            </label>
                            <input 
                              type="text" 
                              class="form-control"
                              v-model="profile.pincode"
                              :disabled="!editMode"
                              pattern="[0-9]{6}"
                              required
                            >
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="form-actions" v-if="editMode">
                      <button type="submit" class="btn btn-primary" :disabled="updating">
                        <i class="fas fa-save me-2"></i>
                        {{ updating ? 'Updating...' : 'Save Address' }}
                      </button>
                    </div>
                  </form>
                </div>

                <!-- Professional Information Tab -->
                <div v-show="activeTab === 'professional'" class="tab-pane">
                  <form @submit.prevent="updateProfessional" class="profile-form">
                    <div class="form-section">
                      <h4 class="section-title">Professional Details</h4>
                      
                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label class="form-label">
                              <i class="fas fa-briefcase me-2"></i>
                              Designation
                            </label>
                            <input 
                              type="text" 
                              class="form-control"
                              v-model="profile.designation"
                              :disabled="!editMode"
                            >
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label class="form-label">
                              <i class="fas fa-building me-2"></i>
                              Organization
                            </label>
                            <input 
                              type="text" 
                              class="form-control"
                              v-model="profile.organization"
                              :disabled="!editMode"
                            >
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label class="form-label">
                              <i class="fas fa-graduation-cap me-2"></i>
                              Education
                            </label>
                            <input 
                              type="text" 
                              class="form-control"
                              v-model="profile.education"
                              :disabled="!editMode"
                            >
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label class="form-label">
                              <i class="fas fa-clock me-2"></i>
                              Experience (Years)
                            </label>
                            <input 
                              type="number" 
                              class="form-control"
                              v-model="profile.experience"
                              :disabled="!editMode"
                              min="0"
                            >
                          </div>
                        </div>
                      </div>

                      <div class="form-group">
                        <label class="form-label">
                          <i class="fas fa-tags me-2"></i>
                          Specialization
                        </label>
                        <textarea 
                          class="form-control"
                          v-model="profile.specialization"
                          :disabled="!editMode"
                          rows="3"
                          placeholder="Describe your areas of expertise..."
                        ></textarea>
                      </div>

                      <div class="form-group">
                        <label class="form-label">
                          <i class="fas fa-award me-2"></i>
                          Achievements
                        </label>
                        <textarea 
                          class="form-control"
                          v-model="profile.achievements"
                          :disabled="!editMode"
                          rows="4"
                          placeholder="List your notable achievements..."
                        ></textarea>
                      </div>
                    </div>

                    <div class="form-actions" v-if="editMode">
                      <button type="submit" class="btn btn-primary" :disabled="updating">
                        <i class="fas fa-save me-2"></i>
                        {{ updating ? 'Updating...' : 'Save Professional Info' }}
                      </button>
                    </div>
                  </form>
                </div>

                <!-- Documents Tab -->
                <div v-show="activeTab === 'documents'" class="tab-pane">
                  <div class="documents-section">
                    <h4 class="section-title">Document Management</h4>
                    
                    <div class="documents-grid">
                      <div 
                        v-for="docType in documentTypes" 
                        :key="docType.key"
                        class="document-card"
                      >
                        <div class="document-header">
                          <div class="document-icon">
                            <i :class="docType.icon"></i>
                          </div>
                          <div class="document-info">
                            <h5>{{ docType.name }}</h5>
                            <p>{{ docType.description }}</p>
                          </div>
                        </div>
                        
                        <div class="document-status">
                          <div v-if="profile.documents && profile.documents[docType.key]" class="uploaded">
                            <div class="status-badge success">
                              <i class="fas fa-check-circle"></i>
                              Uploaded
                            </div>
                            <div class="document-actions">
                              <button 
                                class="btn btn-sm btn-outline-primary"
                                @click="viewDocument(docType.key)"
                              >
                                <i class="fas fa-eye"></i>
                                View
                              </button>
                              <button 
                                class="btn btn-sm btn-outline-danger"
                                @click="deleteDocument(docType.key)"
                                v-if="editMode"
                              >
                                <i class="fas fa-trash"></i>
                                Delete
                              </button>
                            </div>
                          </div>
                          
                          <div v-else class="not-uploaded">
                            <div class="status-badge warning">
                              <i class="fas fa-exclamation-triangle"></i>
                              Not Uploaded
                            </div>
                            <div class="upload-actions" v-if="editMode">
                              <input 
                                type="file" 
                                :ref="`${docType.key}Input`"
                                @change="uploadDocument(docType.key, $event)"
                                accept=".pdf,.jpg,.jpeg,.png"
                                style="display: none"
                              >
                              <button 
                                class="btn btn-sm btn-primary"
                                @click="triggerDocumentUpload(docType.key)"
                              >
                                <i class="fas fa-upload"></i>
                                Upload
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Security Tab -->
                <div v-show="activeTab === 'security'" class="tab-pane">
                  <div class="security-section">
                    <h4 class="section-title">Security Settings</h4>
                    
                    <!-- Change Password -->
                    <div class="security-card">
                      <div class="security-header">
                        <h5>
                          <i class="fas fa-key me-2"></i>
                          Change Password
                        </h5>
                        <p>Update your password to keep your account secure</p>
                      </div>
                      
                      <form @submit.prevent="changePassword" class="password-form">
                        <div class="form-group">
                          <label class="form-label">Current Password</label>
                          <input 
                            type="password" 
                            class="form-control"
                            v-model="passwordForm.currentPassword"
                            required
                          >
                        </div>
                        
                        <div class="form-group">
                          <label class="form-label">New Password</label>
                          <input 
                            type="password" 
                            class="form-control"
                            v-model="passwordForm.newPassword"
                            required
                            minlength="8"
                          >
                        </div>
                        
                        <div class="form-group">
                          <label class="form-label">Confirm New Password</label>
                          <input 
                            type="password" 
                            class="form-control"
                            v-model="passwordForm.confirmPassword"
                            required
                          >
                        </div>
                        
                        <button type="submit" class="btn btn-primary" :disabled="changingPassword">
                          <i class="fas fa-save me-2"></i>
                          {{ changingPassword ? 'Changing...' : 'Change Password' }}
                        </button>
                      </form>
                    </div>

                    <!-- Two-Factor Authentication -->
                    <div class="security-card">
                      <div class="security-header">
                        <h5>
                          <i class="fas fa-shield-alt me-2"></i>
                          Two-Factor Authentication
                        </h5>
                        <p>Add an extra layer of security to your account</p>
                      </div>
                      
                      <div class="two-factor-status">
                        <div class="status-indicator" :class="profile.two_factor_enabled ? 'enabled' : 'disabled'">
                          <i class="fas" :class="profile.two_factor_enabled ? 'fa-check-circle' : 'fa-times-circle'"></i>
                          {{ profile.two_factor_enabled ? 'Enabled' : 'Disabled' }}
                        </div>
                        
                        <button 
                          class="btn"
                          :class="profile.two_factor_enabled ? 'btn-danger' : 'btn-success'"
                          @click="toggleTwoFactor"
                          :disabled="togglingTwoFactor"
                        >
                          <i class="fas me-2" :class="profile.two_factor_enabled ? 'fa-times' : 'fa-plus'"></i>
                          {{ profile.two_factor_enabled ? 'Disable' : 'Enable' }} 2FA
                        </button>
                      </div>
                    </div>

                    <!-- Login Sessions -->
                    <div class="security-card">
                      <div class="security-header">
                        <h5>
                          <i class="fas fa-laptop me-2"></i>
                          Active Sessions
                        </h5>
                        <p>Manage your active login sessions</p>
                      </div>
                      
                      <div class="sessions-list">
                        <div 
                          v-for="session in activeSessions" 
                          :key="session.id"
                          class="session-item"
                        >
                          <div class="session-info">
                            <div class="session-device">
                              <i class="fas" :class="getDeviceIcon(session.device)"></i>
                              {{ session.device }} - {{ session.browser }}
                            </div>
                            <div class="session-details">
                              <span class="session-location">{{ session.location }}</span>
                              <span class="session-time">{{ formatTime(session.last_activity) }}</span>
                            </div>
                          </div>
                          
                          <div class="session-actions">
                            <span v-if="session.current" class="badge bg-success">Current</span>
                            <button 
                              v-else
                              class="btn btn-sm btn-outline-danger"
                              @click="terminateSession(session.id)"
                            >
                              <i class="fas fa-times"></i>
                              Terminate
                            </button>
                          </div>
                        </div>
                      </div>
                      
                      <button 
                        class="btn btn-outline-danger mt-3"
                        @click="terminateAllSessions"
                      >
                        <i class="fas fa-sign-out-alt me-2"></i>
                        Terminate All Other Sessions
                      </button>
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
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

export default {
  name: 'MemberProfile',
  setup() {
    const authStore = useAuthStore()
    const router = useRouter()

    // Reactive data
    const editMode = ref(false)
    const activeTab = ref('personal')
    const updating = ref(false)
    const downloading = ref(false)
    const changingPassword = ref(false)
    const togglingTwoFactor = ref(false)
    const successMessage = ref('')
    const errorMessage = ref('')

    // Profile data
    const profile = reactive({
      name: '',
      email: '',
      phone: '',
      date_of_birth: '',
      gender: '',
      aadhar_number: '',
      bio: '',
      avatar: '',
      address_line1: '',
      address_line2: '',
      state: '',
      district: '',
      city: '',
      pincode: '',
      designation: '',
      organization: '',
      education: '',
      experience: '',
      specialization: '',
      achievements: '',
      membership_type: '',
      verified: false,
      two_factor_enabled: false,
      documents: {}
    })

    // Form data
    const passwordForm = reactive({
      currentPassword: '',
      newPassword: '',
      confirmPassword: ''
    })

    // Lists data
    const states = ref([])
    const districts = ref([])
    const activeSessions = ref([])

    // Document types
    const documentTypes = [
      {
        key: 'photo',
        name: 'Profile Photo',
        description: 'Recent passport-size photograph',
        icon: 'fas fa-camera'
      },
      {
        key: 'id_proof',
        name: 'ID Proof',
        description: 'Aadhar Card, PAN Card, or Passport',
        icon: 'fas fa-id-card'
      },
      {
        key: 'address_proof',
        name: 'Address Proof',
        description: 'Utility bill or bank statement',
        icon: 'fas fa-home'
      },
      {
        key: 'education_certificate',
        name: 'Education Certificate',
        description: 'Highest qualification certificate',
        icon: 'fas fa-graduation-cap'
      },
      {
        key: 'experience_certificate',
        name: 'Experience Certificate',
        description: 'Work experience certificate',
        icon: 'fas fa-briefcase'
      }
    ]

    // Computed properties
    const profileStats = computed(() => {
      const requiredFields = ['name', 'email', 'phone', 'address_line1', 'state', 'district', 'city', 'pincode']
      const completedFields = requiredFields.filter(field => profile[field] && profile[field].trim() !== '')
      const completionPercentage = Math.round((completedFields.length / requiredFields.length) * 100)
      
      return {
        completionPercentage,
        memberSince: profile.created_at ? new Date(profile.created_at).getFullYear() : new Date().getFullYear(),
        totalComplaints: profile.total_complaints || 0
      }
    })

    // Methods
    const loadProfile = async () => {
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/profile', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          const data = await response.json()
          Object.assign(profile, data.profile)
        } else {
          throw new Error('Failed to load profile')
        }
      } catch (error) {
        console.error('Error loading profile:', error)
        errorMessage.value = 'Failed to load profile data'
      }
    }

    const loadStates = async () => {
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/states')
        if (response.ok) {
          const data = await response.json()
          states.value = data.states || []
        }
      } catch (error) {
        console.error('Error loading states:', error)
      }
    }

    const loadDistricts = async () => {
      if (!profile.state) {
        districts.value = []
        return
      }

      try {
        const response = await fetch(`/backend/api.php/districts?state=${encodeURIComponent(profile.state)}`)
        if (response.ok) {
          const data = await response.json()
          districts.value = data.districts || []
        }
      } catch (error) {
        console.error('Error loading districts:', error)
      }
    }

    const loadActiveSessions = async () => {
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/profile/sessions', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          const data = await response.json()
          activeSessions.value = data.sessions || []
        }
      } catch (error) {
        console.error('Error loading sessions:', error)
      }
    }

    const updateProfile = async () => {
      updating.value = true
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/profile', {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            name: profile.name,
            email: profile.email,
            phone: profile.phone,
            date_of_birth: profile.date_of_birth,
            gender: profile.gender,
            aadhar_number: profile.aadhar_number,
            bio: profile.bio
          })
        })

        if (response.ok) {
          successMessage.value = 'Profile updated successfully!'
          editMode.value = false
        } else {
          throw new Error('Failed to update profile')
        }
      } catch (error) {
        console.error('Error updating profile:', error)
        errorMessage.value = 'Failed to update profile'
      } finally {
        updating.value = false
      }
    }

    const updateAddress = async () => {
      updating.value = true
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/profile/address', {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            address_line1: profile.address_line1,
            address_line2: profile.address_line2,
            state: profile.state,
            district: profile.district,
            city: profile.city,
            pincode: profile.pincode
          })
        })

        if (response.ok) {
          successMessage.value = 'Address updated successfully!'
        } else {
          throw new Error('Failed to update address')
        }
      } catch (error) {
        console.error('Error updating address:', error)
        errorMessage.value = 'Failed to update address'
      } finally {
        updating.value = false
      }
    }

    const updateProfessional = async () => {
      updating.value = true
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/profile/professional', {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            designation: profile.designation,
            organization: profile.organization,
            education: profile.education,
            experience: profile.experience,
            specialization: profile.specialization,
            achievements: profile.achievements
          })
        })

        if (response.ok) {
          successMessage.value = 'Professional information updated successfully!'
        } else {
          throw new Error('Failed to update professional information')
        }
      } catch (error) {
        console.error('Error updating professional info:', error)
        errorMessage.value = 'Failed to update professional information'
      } finally {
        updating.value = false
      }
    }

    const changePassword = async () => {
      if (passwordForm.newPassword !== passwordForm.confirmPassword) {
        errorMessage.value = 'New passwords do not match'
        return
      }

      changingPassword.value = true
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/profile/password', {
          method: 'PUT',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            current_password: passwordForm.currentPassword,
            new_password: passwordForm.newPassword
          })
        })

        if (response.ok) {
          successMessage.value = 'Password changed successfully!'
          Object.assign(passwordForm, {
            currentPassword: '',
            newPassword: '',
            confirmPassword: ''
          })
        } else {
          const error = await response.json()
          throw new Error(error.message || 'Failed to change password')
        }
      } catch (error) {
        console.error('Error changing password:', error)
        errorMessage.value = error.message || 'Failed to change password'
      } finally {
        changingPassword.value = false
      }
    }

    const toggleTwoFactor = async () => {
      togglingTwoFactor.value = true
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/profile/two-factor', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            enable: !profile.two_factor_enabled
          })
        })

        if (response.ok) {
          profile.two_factor_enabled = !profile.two_factor_enabled
          successMessage.value = `Two-factor authentication ${profile.two_factor_enabled ? 'enabled' : 'disabled'} successfully!`
        } else {
          throw new Error('Failed to toggle two-factor authentication')
        }
      } catch (error) {
        console.error('Error toggling 2FA:', error)
        errorMessage.value = 'Failed to toggle two-factor authentication'
      } finally {
        togglingTwoFactor.value = false
      }
    }

    const triggerFileUpload = () => {
      const input = document.createElement('input')
      input.type = 'file'
      input.accept = 'image/*'
      input.onchange = handleAvatarUpload
      input.click()
    }

    const handleAvatarUpload = async (event) => {
      const file = event.target.files[0]
      if (!file) return

      const formData = new FormData()
      formData.append('avatar', file)

      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/profile/avatar', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          },
          body: formData
        })

        if (response.ok) {
          const data = await response.json()
          profile.avatar = data.avatar_url
          successMessage.value = 'Profile photo updated successfully!'
        } else {
          throw new Error('Failed to upload avatar')
        }
      } catch (error) {
        console.error('Error uploading avatar:', error)
        errorMessage.value = 'Failed to upload profile photo'
      }
    }

    const triggerDocumentUpload = (docType) => {
      const input = document.createElement('input')
      input.type = 'file'
      input.accept = '.pdf,.jpg,.jpeg,.png'
      input.onchange = (event) => uploadDocument(docType, event)
      input.click()
    }

    const uploadDocument = async (docType, event) => {
      const file = event.target.files[0]
      if (!file) return

      const formData = new FormData()
      formData.append('document', file)
      formData.append('type', docType)

      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/profile/documents', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          },
          body: formData
        })

        if (response.ok) {
          const data = await response.json()
          if (!profile.documents) profile.documents = {}
          profile.documents[docType] = data.document_url
          successMessage.value = 'Document uploaded successfully!'
        } else {
          throw new Error('Failed to upload document')
        }
      } catch (error) {
        console.error('Error uploading document:', error)
        errorMessage.value = 'Failed to upload document'
      }
    }

    const viewDocument = (docType) => {
      if (profile.documents && profile.documents[docType]) {
        window.open(profile.documents[docType], '_blank')
      }
    }

    const deleteDocument = async (docType) => {
      if (!confirm('Are you sure you want to delete this document?')) return

      try {
        const response = await fetch(`/backend/api.php/profile/documents/${docType}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          if (profile.documents) {
            delete profile.documents[docType]
          }
          successMessage.value = 'Document deleted successfully!'
        } else {
          throw new Error('Failed to delete document')
        }
      } catch (error) {
        console.error('Error deleting document:', error)
        errorMessage.value = 'Failed to delete document'
      }
    }

    const terminateSession = async (sessionId) => {
      try {
        const response = await fetch(`/backend/api.php/profile/sessions/${sessionId}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          activeSessions.value = activeSessions.value.filter(s => s.id !== sessionId)
          successMessage.value = 'Session terminated successfully!'
        } else {
          throw new Error('Failed to terminate session')
        }
      } catch (error) {
        console.error('Error terminating session:', error)
        errorMessage.value = 'Failed to terminate session'
      }
    }

    const terminateAllSessions = async () => {
      if (!confirm('Are you sure you want to terminate all other sessions?')) return

      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/profile/sessions', {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          activeSessions.value = activeSessions.value.filter(s => s.current)
          successMessage.value = 'All other sessions terminated successfully!'
        } else {
          throw new Error('Failed to terminate sessions')
        }
      } catch (error) {
        console.error('Error terminating sessions:', error)
        errorMessage.value = 'Failed to terminate sessions'
      }
    }

    const downloadProfile = async () => {
      downloading.value = true
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/profile/download', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          }
        })

        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = `${profile.name}_profile.pdf`
          a.click()
          window.URL.revokeObjectURL(url)
        } else {
          throw new Error('Failed to download profile')
        }
      } catch (error) {
        console.error('Error downloading profile:', error)
        errorMessage.value = 'Failed to download profile'
      } finally {
        downloading.value = false
      }
    }

    const cancelEdit = () => {
      editMode.value = false
      loadProfile() // Reload original data
    }

    const getDeviceIcon = (device) => {
      if (device.toLowerCase().includes('mobile')) return 'fa-mobile-alt'
      if (device.toLowerCase().includes('tablet')) return 'fa-tablet-alt'
      return 'fa-desktop'
    }

    const formatTime = (timestamp) => {
      return new Date(timestamp).toLocaleString()
    }

    // Lifecycle
    onMounted(() => {
      loadProfile()
      loadStates()
      loadActiveSessions()
    })

    return {
      // Data
      editMode,
      activeTab,
      updating,
      downloading,
      changingPassword,
      togglingTwoFactor,
      successMessage,
      errorMessage,
      profile,
      passwordForm,
      states,
      districts,
      activeSessions,
      documentTypes,
      
      // Computed
      profileStats,
      
      // Methods
      loadProfile,
      loadStates,
      loadDistricts,
      loadActiveSessions,
      updateProfile,
      updateAddress,
      updateProfessional,
      changePassword,
      toggleTwoFactor,
      triggerFileUpload,
      handleAvatarUpload,
      triggerDocumentUpload,
      uploadDocument,
      viewDocument,
      deleteDocument,
      terminateSession,
      terminateAllSessions,
      downloadProfile,
      cancelEdit,
      getDeviceIcon,
      formatTime
    }
  }
}
</script>

<style scoped>
/* Profile Header */
.profile-header {
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

.header-actions {
  display: flex;
  gap: 0.5rem;
}

/* Profile Content */
.profile-content {
  padding: 3rem 0;
  background: #f8f9fa;
  min-height: calc(100vh - 200px);
}

/* Profile Sidebar */
.profile-sidebar {
  position: sticky;
  top: 2rem;
}

.profile-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.profile-avatar-section {
  padding: 2rem;
  text-align: center;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.avatar-container {
  position: relative;
  display: inline-block;
  margin-bottom: 1rem;
}

.profile-avatar {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.avatar-overlay {
  position: absolute;
  bottom: 0;
  right: 0;
  background: rgba(0, 0, 0, 0.7);
  border-radius: 50%;
  padding: 0.5rem;
}

.profile-name {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
  color: #333;
}

.profile-designation {
  color: #666;
  margin-bottom: 1rem;
}

.profile-badges {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

/* Profile Stats */
.profile-stats {
  display: flex;
  padding: 1.5rem;
  border-top: 1px solid #e9ecef;
}

.stat-item {
  flex: 1;
  text-align: center;
}

.stat-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: #dc3545;
  display: block;
}

.stat-label {
  font-size: 0.8rem;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Quick Actions */
.quick-actions {
  padding: 1.5rem;
  border-top: 1px solid #e9ecef;
}

.quick-actions h4 {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 1rem;
  color: #333;
}

.action-buttons {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}

.action-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1rem 0.5rem;
  background: #f8f9fa;
  border-radius: 8px;
  text-decoration: none;
  color: #666;
  transition: all 0.3s ease;
  border: 1px solid #e9ecef;
}

.action-btn:hover {
  background: #dc3545;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.action-btn i {
  font-size: 1.25rem;
  margin-bottom: 0.5rem;
}

.action-btn span {
  font-size: 0.8rem;
  font-weight: 500;
}

/* Profile Form Container */
.profile-form-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

/* Profile Tabs */
.profile-tabs {
  border-bottom: 1px solid #e9ecef;
}

.nav-tabs {
  border-bottom: none;
  background: #f8f9fa;
}

.nav-tabs .nav-link {
  border: none;
  background: transparent;
  color: #666;
  padding: 1rem 1.5rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

.nav-tabs .nav-link:hover {
  background: #e9ecef;
  color: #333;
}

.nav-tabs .nav-link.active {
  background: white;
  color: #dc3545;
  border-bottom: 3px solid #dc3545;
}

/* Tab Content */
.tab-content {
  padding: 0;
}

.tab-pane {
  padding: 2rem;
}

/* Form Sections */
.form-section {
  margin-bottom: 2rem;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  color: #333;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #e9ecef;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: flex;
  align-items: center;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #333;
}

.form-label i {
  color: #dc3545;
  width: 20px;
}

.form-control {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.3s ease;
}

.form-control:focus {
  outline: none;
  border-color: #dc3545;
  box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
}

.form-control:disabled {
  background: #f8f9fa;
  color: #666;
}

/* Form Actions */
.form-actions {
  display: flex;
  gap: 1rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e9ecef;
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary {
  background: linear-gradient(135deg, #dc3545, #c82333);
  border: none;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
}

.btn-secondary {
  background: #6c757d;
  border: none;
  color: white;
}

.btn-outline-primary {
  border: 1px solid #dc3545;
  color: #dc3545;
  background: transparent;
}

.btn-outline-primary:hover {
  background: #dc3545;
  color: white;
}

/* Documents Section */
.documents-section {
  padding: 2rem;
}

.documents-grid {
  display: grid;
  gap: 1.5rem;
}

.document-card {
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 1.5rem;
  background: #f8f9fa;
}

.document-header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
}

.document-icon {
  width: 50px;
  height: 50px;
  background: #dc3545;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.document-info h5 {
  margin-bottom: 0.25rem;
  color: #333;
}

.document-info p {
  color: #666;
  font-size: 0.9rem;
  margin: 0;
}

.document-status {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
}

.status-badge.success {
  background: #d4edda;
  color: #155724;
}

.status-badge.warning {
  background: #fff3cd;
  color: #856404;
}

.document-actions {
  display: flex;
  gap: 0.5rem;
}

/* Security Section */
.security-section {
  padding: 2rem;
}

.security-card {
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  background: #f8f9fa;
}

.security-header h5 {
  margin-bottom: 0.5rem;
  color: #333;
}

.security-header p {
  color: #666;
  margin-bottom: 1rem;
}

.password-form .form-group {
  margin-bottom: 1rem;
}

.two-factor-status {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
}

.status-indicator.enabled {
  color: #28a745;
}

.status-indicator.disabled {
  color: #dc3545;
}

/* Sessions */
.sessions-list {
  margin-bottom: 1rem;
}

.session-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  margin-bottom: 0.5rem;
  background: white;
}

.session-info {
  flex: 1;
}

.session-device {
  font-weight: 600;
  margin-bottom: 0.25rem;
  color: #333;
}

.session-details {
  display: flex;
  gap: 1rem;
  font-size: 0.9rem;
  color: #666;
}

.session-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Responsive Design */
@media (max-width: 992px) {
  .profile-content .row {
    flex-direction: column-reverse;
  }
  
  .profile-sidebar {
    position: static;
    margin-top: 2rem;
  }
  
  .action-buttons {
    grid-template-columns: repeat(4, 1fr);
  }
}

@media (max-width: 768px) {
  .profile-header {
    padding: 2rem 0;
  }
  
  .page-title {
    font-size: 2rem;
  }
  
  .header-actions {
    flex-direction: column;
    gap: 0.5rem;
    margin-top: 1rem;
  }
  
  .profile-content {
    padding: 2rem 0;
  }
  
  .tab-pane {
    padding: 1rem;
  }
  
  .nav-tabs .nav-link {
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
  }
  
  .action-buttons {
    grid-template-columns: 1fr 1fr;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .document-header {
    flex-direction: column;
    text-align: center;
  }
  
  .document-status {
    flex-direction: column;
    gap: 1rem;
  }
  
  .session-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .session-details {
    flex-direction: column;
    gap: 0.25rem;
  }
}

@media (max-width: 480px) {
  .profile-stats {
    flex-direction: column;
    gap: 1rem;
  }
  
  .nav-tabs {
    flex-direction: column;
  }
  
  .nav-tabs .nav-link {
    text-align: left;
  }
}
</style>