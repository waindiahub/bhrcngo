<template>
  <div class="register-page">
    <div class="container-fluid">
      <div class="row min-vh-100">
        <!-- Left Side - Registration Form -->
        <div class="col-lg-8 d-flex align-items-center justify-content-center">
          <div class="register-form-container">
            <div class="text-center mb-4">
              <img src="/logo.png" alt="BHRC" class="register-logo mb-3" height="60">
              <h2 class="register-title">Join BHRC</h2>
              <p class="register-subtitle text-muted">Create your account to become a member</p>
            </div>

            <form @submit.prevent="handleRegister" class="register-form">
              <!-- Step Indicator -->
              <div class="step-indicator mb-4">
                <div class="step" :class="{ active: currentStep >= 1, completed: currentStep > 1 }">
                  <div class="step-number">1</div>
                  <div class="step-label">Personal Info</div>
                </div>
                <div class="step" :class="{ active: currentStep >= 2, completed: currentStep > 2 }">
                  <div class="step-number">2</div>
                  <div class="step-label">Contact Details</div>
                </div>
                <div class="step" :class="{ active: currentStep >= 3 }">
                  <div class="step-number">3</div>
                  <div class="step-label">Account Setup</div>
                </div>
              </div>

              <!-- Step 1: Personal Information -->
              <div v-show="currentStep === 1" class="step-content">
                <h4 class="step-title mb-3">Personal Information</h4>
                
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="firstName" class="form-label">First Name *</label>
                    <input 
                      type="text" 
                      id="firstName"
                      v-model="form.firstName"
                      class="form-control"
                      :class="{ 'is-invalid': errors.firstName }"
                      placeholder="Enter first name"
                      required
                    >
                    <div v-if="errors.firstName" class="invalid-feedback">
                      {{ errors.firstName }}
                    </div>
                  </div>
                  
                  <div class="col-md-6 mb-3">
                    <label for="lastName" class="form-label">Last Name *</label>
                    <input 
                      type="text" 
                      id="lastName"
                      v-model="form.lastName"
                      class="form-control"
                      :class="{ 'is-invalid': errors.lastName }"
                      placeholder="Enter last name"
                      required
                    >
                    <div v-if="errors.lastName" class="invalid-feedback">
                      {{ errors.lastName }}
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="dateOfBirth" class="form-label">Date of Birth *</label>
                    <input 
                      type="date" 
                      id="dateOfBirth"
                      v-model="form.dateOfBirth"
                      class="form-control"
                      :class="{ 'is-invalid': errors.dateOfBirth }"
                      :max="maxDate"
                      required
                    >
                    <div v-if="errors.dateOfBirth" class="invalid-feedback">
                      {{ errors.dateOfBirth }}
                    </div>
                  </div>
                  
                  <div class="col-md-6 mb-3">
                    <label for="gender" class="form-label">Gender *</label>
                    <select 
                      id="gender"
                      v-model="form.gender"
                      class="form-select"
                      :class="{ 'is-invalid': errors.gender }"
                      required
                    >
                      <option value="">Select Gender</option>
                      <option value="male">Male</option>
                      <option value="female">Female</option>
                      <option value="other">Other</option>
                      <option value="prefer_not_to_say">Prefer not to say</option>
                    </select>
                    <div v-if="errors.gender" class="invalid-feedback">
                      {{ errors.gender }}
                    </div>
                  </div>
                </div>

                <div class="mb-3">
                  <label for="aadharNumber" class="form-label">Aadhar Number *</label>
                  <input 
                    type="text" 
                    id="aadharNumber"
                    v-model="form.aadharNumber"
                    class="form-control"
                    :class="{ 'is-invalid': errors.aadharNumber }"
                    placeholder="Enter 12-digit Aadhar number"
                    maxlength="12"
                    @input="formatAadhar"
                    required
                  >
                  <div v-if="errors.aadharNumber" class="invalid-feedback">
                    {{ errors.aadharNumber }}
                  </div>
                </div>
              </div>

              <!-- Step 2: Contact Details -->
              <div v-show="currentStep === 2" class="step-content">
                <h4 class="step-title mb-3">Contact Details</h4>
                
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email Address *</label>
                    <input 
                      type="email" 
                      id="email"
                      v-model="form.email"
                      class="form-control"
                      :class="{ 'is-invalid': errors.email }"
                      placeholder="Enter email address"
                      required
                    >
                    <div v-if="errors.email" class="invalid-feedback">
                      {{ errors.email }}
                    </div>
                  </div>
                  
                  <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Phone Number *</label>
                    <input 
                      type="tel" 
                      id="phone"
                      v-model="form.phone"
                      class="form-control"
                      :class="{ 'is-invalid': errors.phone }"
                      placeholder="Enter 10-digit phone number"
                      maxlength="10"
                      @input="formatPhone"
                      required
                    >
                    <div v-if="errors.phone" class="invalid-feedback">
                      {{ errors.phone }}
                    </div>
                  </div>
                </div>

                <div class="mb-3">
                  <label for="address" class="form-label">Address *</label>
                  <textarea 
                    id="address"
                    v-model="form.address"
                    class="form-control"
                    :class="{ 'is-invalid': errors.address }"
                    placeholder="Enter complete address"
                    rows="3"
                    required
                  ></textarea>
                  <div v-if="errors.address" class="invalid-feedback">
                    {{ errors.address }}
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label for="city" class="form-label">City *</label>
                    <input 
                      type="text" 
                      id="city"
                      v-model="form.city"
                      class="form-control"
                      :class="{ 'is-invalid': errors.city }"
                      placeholder="Enter city"
                      required
                    >
                    <div v-if="errors.city" class="invalid-feedback">
                      {{ errors.city }}
                    </div>
                  </div>
                  
                  <div class="col-md-4 mb-3">
                    <label for="state" class="form-label">State *</label>
                    <select 
                      id="state"
                      v-model="form.state"
                      class="form-select"
                      :class="{ 'is-invalid': errors.state }"
                      required
                    >
                      <option value="">Select State</option>
                      <option v-for="state in indianStates" :key="state" :value="state">
                        {{ state }}
                      </option>
                    </select>
                    <div v-if="errors.state" class="invalid-feedback">
                      {{ errors.state }}
                    </div>
                  </div>
                  
                  <div class="col-md-4 mb-3">
                    <label for="pincode" class="form-label">Pincode *</label>
                    <input 
                      type="text" 
                      id="pincode"
                      v-model="form.pincode"
                      class="form-control"
                      :class="{ 'is-invalid': errors.pincode }"
                      placeholder="Enter pincode"
                      maxlength="6"
                      @input="formatPincode"
                      required
                    >
                    <div v-if="errors.pincode" class="invalid-feedback">
                      {{ errors.pincode }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Step 3: Account Setup -->
              <div v-show="currentStep === 3" class="step-content">
                <h4 class="step-title mb-3">Account Setup</h4>
                
                <div class="mb-3">
                  <label for="password" class="form-label">Password *</label>
                  <div class="input-group">
                    <input 
                      :type="showPassword ? 'text' : 'password'"
                      id="password"
                      v-model="form.password"
                      class="form-control"
                      :class="{ 'is-invalid': errors.password }"
                      placeholder="Enter password"
                      required
                    >
                    <button 
                      type="button" 
                      class="btn btn-outline-secondary"
                      @click="togglePassword"
                    >
                      <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                  </div>
                  <div v-if="errors.password" class="invalid-feedback">
                    {{ errors.password }}
                  </div>
                  <div class="password-strength mt-2">
                    <div class="password-strength-bar">
                      <div 
                        class="password-strength-fill" 
                        :class="passwordStrength.class"
                        :style="{ width: passwordStrength.width }"
                      ></div>
                    </div>
                    <small class="text-muted">{{ passwordStrength.text }}</small>
                  </div>
                </div>

                <div class="mb-3">
                  <label for="confirmPassword" class="form-label">Confirm Password *</label>
                  <input 
                    :type="showConfirmPassword ? 'text' : 'password'"
                    id="confirmPassword"
                    v-model="form.confirmPassword"
                    class="form-control"
                    :class="{ 'is-invalid': errors.confirmPassword }"
                    placeholder="Confirm password"
                    required
                  >
                  <div v-if="errors.confirmPassword" class="invalid-feedback">
                    {{ errors.confirmPassword }}
                  </div>
                </div>

                <div class="mb-3">
                  <label for="membershipType" class="form-label">Membership Type *</label>
                  <select 
                    id="membershipType"
                    v-model="form.membershipType"
                    class="form-select"
                    :class="{ 'is-invalid': errors.membershipType }"
                    required
                  >
                    <option value="">Select Membership Type</option>
                    <option value="individual">Individual Member</option>
                    <option value="student">Student Member</option>
                    <option value="senior_citizen">Senior Citizen</option>
                    <option value="organization">Organization</option>
                  </select>
                  <div v-if="errors.membershipType" class="invalid-feedback">
                    {{ errors.membershipType }}
                  </div>
                </div>

                <div class="mb-4">
                  <div class="form-check">
                    <input 
                      type="checkbox" 
                      id="agreeTerms"
                      v-model="form.agreeTerms"
                      class="form-check-input"
                      :class="{ 'is-invalid': errors.agreeTerms }"
                      required
                    >
                    <label for="agreeTerms" class="form-check-label">
                      I agree to the 
                      <a href="/terms" target="_blank" class="text-decoration-none">Terms of Service</a> 
                      and 
                      <a href="/privacy" target="_blank" class="text-decoration-none">Privacy Policy</a>
                    </label>
                    <div v-if="errors.agreeTerms" class="invalid-feedback">
                      {{ errors.agreeTerms }}
                    </div>
                  </div>
                </div>

                <div class="mb-3">
                  <div class="form-check">
                    <input 
                      type="checkbox" 
                      id="subscribeNewsletter"
                      v-model="form.subscribeNewsletter"
                      class="form-check-input"
                    >
                    <label for="subscribeNewsletter" class="form-check-label">
                      Subscribe to our newsletter for updates and news
                    </label>
                  </div>
                </div>
              </div>

              <!-- Navigation Buttons -->
              <div class="step-navigation d-flex justify-content-between">
                <button 
                  type="button" 
                  class="btn btn-outline-secondary"
                  @click="previousStep"
                  v-show="currentStep > 1"
                >
                  <i class="fas fa-arrow-left me-2"></i>
                  Previous
                </button>
                
                <div class="ms-auto">
                  <button 
                    type="button" 
                    class="btn btn-primary"
                    @click="nextStep"
                    v-show="currentStep < 3"
                  >
                    Next
                    <i class="fas fa-arrow-right ms-2"></i>
                  </button>
                  
                  <button 
                    type="submit" 
                    class="btn btn-success"
                    :disabled="isLoading"
                    v-show="currentStep === 3"
                  >
                    <i v-if="isLoading" class="fas fa-spinner fa-spin me-2"></i>
                    <i v-else class="fas fa-user-plus me-2"></i>
                    {{ isLoading ? 'Creating Account...' : 'Create Account' }}
                  </button>
                </div>
              </div>
            </form>

            <div class="text-center mt-4">
              <p class="mb-0">
                Already have an account? 
                <router-link to="/login" class="text-decoration-none fw-bold">
                  Sign In
                </router-link>
              </p>
            </div>
          </div>
        </div>

        <!-- Right Side - Info -->
        <div class="col-lg-4 d-none d-lg-flex align-items-center justify-content-center bg-gradient-primary">
          <div class="register-info text-white text-center p-4">
            <i class="fas fa-users fa-4x mb-4 opacity-75"></i>
            <h3 class="mb-3">Join Our Community</h3>
            <p class="mb-4">
              Become part of a movement dedicated to protecting human rights and creating positive change.
            </p>
            <div class="benefits-list">
              <div class="benefit-item mb-3">
                <i class="fas fa-check-circle me-2"></i>
                Access to legal resources
              </div>
              <div class="benefit-item mb-3">
                <i class="fas fa-check-circle me-2"></i>
                Community support network
              </div>
              <div class="benefit-item mb-3">
                <i class="fas fa-check-circle me-2"></i>
                Regular updates and events
              </div>
              <div class="benefit-item">
                <i class="fas fa-check-circle me-2"></i>
                Make a real difference
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { validationUtils } from '@/utils/helpers'
import { useToast } from 'vue-toastification'

export default {
  name: 'Register',
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    const toast = useToast()

    // Reactive state
    const currentStep = ref(1)
    const form = reactive({
      firstName: '',
      lastName: '',
      dateOfBirth: '',
      gender: '',
      aadharNumber: '',
      email: '',
      phone: '',
      address: '',
      city: '',
      state: '',
      pincode: '',
      password: '',
      confirmPassword: '',
      membershipType: '',
      agreeTerms: false,
      subscribeNewsletter: false
    })
    
    const errors = ref({})
    const showPassword = ref(false)
    const showConfirmPassword = ref(false)
    const isLoading = computed(() => authStore.isLoading)

    // Indian states list
    const indianStates = [
      'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh',
      'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka',
      'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram',
      'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu',
      'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
      'Andaman and Nicobar Islands', 'Chandigarh', 'Dadra and Nagar Haveli and Daman and Diu',
      'Delhi', 'Jammu and Kashmir', 'Ladakh', 'Lakshadweep', 'Puducherry'
    ]

    // Computed properties
    const maxDate = computed(() => {
      const today = new Date()
      const eighteenYearsAgo = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate())
      return eighteenYearsAgo.toISOString().split('T')[0]
    })

    const passwordStrength = computed(() => {
      const password = form.password
      if (!password) return { width: '0%', class: '', text: '' }

      let score = 0
      if (password.length >= 8) score++
      if (/[a-z]/.test(password)) score++
      if (/[A-Z]/.test(password)) score++
      if (/[0-9]/.test(password)) score++
      if (/[^A-Za-z0-9]/.test(password)) score++

      const strengths = [
        { width: '20%', class: 'weak', text: 'Very Weak' },
        { width: '40%', class: 'weak', text: 'Weak' },
        { width: '60%', class: 'medium', text: 'Medium' },
        { width: '80%', class: 'strong', text: 'Strong' },
        { width: '100%', class: 'very-strong', text: 'Very Strong' }
      ]

      return strengths[score - 1] || strengths[0]
    })

    // Methods
    const validateStep = (step) => {
      const newErrors = {}

      if (step === 1) {
        if (!form.firstName) newErrors.firstName = 'First name is required'
        if (!form.lastName) newErrors.lastName = 'Last name is required'
        if (!form.dateOfBirth) newErrors.dateOfBirth = 'Date of birth is required'
        if (!form.gender) newErrors.gender = 'Gender is required'
        if (!form.aadharNumber) {
          newErrors.aadharNumber = 'Aadhar number is required'
        } else if (!validationUtils.isAadhar(form.aadharNumber)) {
          newErrors.aadharNumber = 'Please enter a valid 12-digit Aadhar number'
        }
      }

      if (step === 2) {
        if (!form.email) {
          newErrors.email = 'Email is required'
        } else if (!validationUtils.isEmail(form.email)) {
          newErrors.email = 'Please enter a valid email address'
        }
        
        if (!form.phone) {
          newErrors.phone = 'Phone number is required'
        } else if (!validationUtils.isPhone(form.phone)) {
          newErrors.phone = 'Please enter a valid 10-digit phone number'
        }
        
        if (!form.address) newErrors.address = 'Address is required'
        if (!form.city) newErrors.city = 'City is required'
        if (!form.state) newErrors.state = 'State is required'
        if (!form.pincode) {
          newErrors.pincode = 'Pincode is required'
        } else if (!/^\d{6}$/.test(form.pincode)) {
          newErrors.pincode = 'Please enter a valid 6-digit pincode'
        }
      }

      if (step === 3) {
        if (!form.password) {
          newErrors.password = 'Password is required'
        } else if (!validationUtils.isStrongPassword(form.password)) {
          newErrors.password = 'Password must be at least 8 characters with uppercase, lowercase, number and special character'
        }
        
        if (!form.confirmPassword) {
          newErrors.confirmPassword = 'Please confirm your password'
        } else if (form.password !== form.confirmPassword) {
          newErrors.confirmPassword = 'Passwords do not match'
        }
        
        if (!form.membershipType) newErrors.membershipType = 'Please select membership type'
        if (!form.agreeTerms) newErrors.agreeTerms = 'You must agree to the terms and conditions'
      }

      errors.value = { ...errors.value, ...newErrors }
      return Object.keys(newErrors).length === 0
    }

    const nextStep = () => {
      if (validateStep(currentStep.value)) {
        currentStep.value++
      }
    }

    const previousStep = () => {
      currentStep.value--
      // Clear errors for the previous step
      errors.value = {}
    }

    const handleRegister = async () => {
      if (!validateStep(3)) return

      const registrationData = {
        first_name: form.firstName,
        last_name: form.lastName,
        email: form.email,
        phone: form.phone,
        password: form.password,
        date_of_birth: form.dateOfBirth,
        gender: form.gender,
        aadhar_number: form.aadharNumber,
        address: form.address,
        city: form.city,
        state: form.state,
        pincode: form.pincode,
        membership_type: form.membershipType,
        subscribe_newsletter: form.subscribeNewsletter
      }

      const result = await authStore.register(registrationData)

      if (result.success) {
        toast.success('Registration successful! Please check your email for verification.')
        router.push('/login?message=registration_success')
      }
    }

    const togglePassword = () => {
      showPassword.value = !showPassword.value
    }

    const toggleConfirmPassword = () => {
      showConfirmPassword.value = !showConfirmPassword.value
    }

    const formatAadhar = (event) => {
      const value = event.target.value.replace(/\D/g, '')
      form.aadharNumber = value.slice(0, 12)
    }

    const formatPhone = (event) => {
      const value = event.target.value.replace(/\D/g, '')
      form.phone = value.slice(0, 10)
    }

    const formatPincode = (event) => {
      const value = event.target.value.replace(/\D/g, '')
      form.pincode = value.slice(0, 6)
    }

    return {
      // State
      currentStep,
      form,
      errors,
      showPassword,
      showConfirmPassword,
      isLoading,
      indianStates,
      
      // Computed
      maxDate,
      passwordStrength,
      
      // Methods
      nextStep,
      previousStep,
      handleRegister,
      togglePassword,
      toggleConfirmPassword,
      formatAadhar,
      formatPhone,
      formatPincode
    }
  }
}
</script>

<style scoped>
.register-page {
  min-height: 100vh;
  background-color: #f8f9fa;
}

.register-form-container {
  width: 100%;
  max-width: 600px;
  padding: 2rem;
}

.register-logo {
  max-height: 60px;
  width: auto;
}

.register-title {
  font-size: 2rem;
  font-weight: 700;
  color: #333;
  margin-bottom: 0.5rem;
}

.register-subtitle {
  font-size: 1rem;
  margin-bottom: 2rem;
}

/* Step Indicator */
.step-indicator {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 2rem;
}

.step {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  flex: 1;
  max-width: 120px;
}

.step:not(:last-child)::after {
  content: '';
  position: absolute;
  top: 15px;
  left: 60%;
  width: 100%;
  height: 2px;
  background-color: #dee2e6;
  z-index: 1;
}

.step.completed:not(:last-child)::after {
  background-color: #28a745;
}

.step-number {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  background-color: #dee2e6;
  color: #6c757d;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.875rem;
  position: relative;
  z-index: 2;
  transition: all 0.3s ease;
}

.step.active .step-number {
  background-color: #007bff;
  color: white;
}

.step.completed .step-number {
  background-color: #28a745;
  color: white;
}

.step-label {
  margin-top: 0.5rem;
  font-size: 0.75rem;
  color: #6c757d;
  text-align: center;
}

.step.active .step-label {
  color: #007bff;
  font-weight: 600;
}

.step.completed .step-label {
  color: #28a745;
}

/* Step Content */
.step-content {
  animation: fadeIn 0.3s ease-in-out;
}

.step-title {
  color: #333;
  font-weight: 600;
  border-bottom: 2px solid #007bff;
  padding-bottom: 0.5rem;
}

/* Password Strength */
.password-strength-bar {
  width: 100%;
  height: 4px;
  background-color: #e9ecef;
  border-radius: 2px;
  overflow: hidden;
}

.password-strength-fill {
  height: 100%;
  transition: all 0.3s ease;
}

.password-strength-fill.weak {
  background-color: #dc3545;
}

.password-strength-fill.medium {
  background-color: #ffc107;
}

.password-strength-fill.strong {
  background-color: #28a745;
}

.password-strength-fill.very-strong {
  background-color: #20c997;
}

/* Form Styles */
.form-control,
.form-select {
  border: 1px solid #ced4da;
  border-radius: 0.375rem;
  padding: 0.75rem;
  transition: all 0.3s ease;
}

.form-control:focus,
.form-select:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.is-invalid {
  border-color: #dc3545;
}

.invalid-feedback {
  display: block;
  width: 100%;
  margin-top: 0.25rem;
  font-size: 0.875rem;
  color: #dc3545;
}

/* Buttons */
.btn {
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  border-radius: 0.375rem;
  transition: all 0.3s ease;
}

.btn-primary {
  background: linear-gradient(135deg, #007bff, #0056b3);
  border: none;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 0.5rem 1rem rgba(0, 123, 255, 0.3);
}

.btn-success {
  background: linear-gradient(135deg, #28a745, #1e7e34);
  border: none;
}

.btn-success:hover {
  transform: translateY(-1px);
  box-shadow: 0 0.5rem 1rem rgba(40, 167, 69, 0.3);
}

/* Right Side Info */
.bg-gradient-primary {
  background: linear-gradient(135deg, #007bff, #0056b3);
}

.register-info {
  max-width: 300px;
}

.benefit-item {
  display: flex;
  align-items: center;
  font-size: 0.9rem;
}

/* Responsive Design */
@media (max-width: 991.98px) {
  .register-form-container {
    padding: 1rem;
    margin: 2rem auto;
  }
  
  .register-title {
    font-size: 1.75rem;
  }
  
  .step-indicator {
    margin-bottom: 1.5rem;
  }
  
  .step-label {
    display: none;
  }
}

@media (max-width: 575.98px) {
  .register-form-container {
    padding: 1rem 0.5rem;
  }
  
  .step {
    max-width: 80px;
  }
  
  .step-number {
    width: 25px;
    height: 25px;
    font-size: 0.75rem;
  }
}

/* Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.register-form-container {
  animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Form Check Styles */
.form-check-input:checked {
  background-color: #007bff;
  border-color: #007bff;
}

.form-check-label {
  font-size: 0.9rem;
  line-height: 1.4;
}

/* Link Styles */
a {
  color: #007bff;
  transition: color 0.3s ease;
}

a:hover {
  color: #0056b3;
}

/* Loading State */
.btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: none !important;
}
</style>