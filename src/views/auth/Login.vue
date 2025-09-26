<template>
  <DefaultLayout>
    <!-- Hero Section -->
    <section class="auth-hero">
      <div class="container">
        <div class="hero-content">
          <h1 class="hero-title">Welcome Back</h1>
          <p class="hero-subtitle">
            Sign in to access your account and continue your journey with us
          </p>
        </div>
      </div>
    </section>

    <!-- Login Section -->
    <section class="login-section">
      <div class="container">
        <div class="auth-wrapper">
          <!-- Login Form -->
          <div class="auth-form-container">
            <div class="form-header">
              <div class="auth-tabs">
                <button
                  class="tab-btn"
                  :class="{ active: loginType === 'user' }"
                  @click="switchLoginType('user')"
                >
                  <i class="fas fa-user"></i>
                  Member Login
                </button>
                <button
                  class="tab-btn"
                  :class="{ active: loginType === 'admin' }"
                  @click="switchLoginType('admin')"
                >
                  <i class="fas fa-user-shield"></i>
                  Admin Login
                </button>
              </div>
              
              <div class="form-title">
                <h2>{{ loginType === 'admin' ? 'Admin Portal' : 'Member Portal' }}</h2>
                <p>{{ loginType === 'admin' ? 'Access administrative functions' : 'Access your member dashboard' }}</p>
              </div>
            </div>

            <form @submit.prevent="handleLogin" class="login-form">
              <!-- Email/Username Field -->
              <div class="form-group">
                <label for="email">
                  <i class="fas fa-envelope"></i>
                  {{ loginType === 'admin' ? 'Admin Email' : 'Email Address' }}
                </label>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  required
                  class="form-input"
                  :class="{ error: errors.email }"
                  :placeholder="loginType === 'admin' ? 'Enter admin email' : 'Enter your email address'"
                  autocomplete="email"
                >
                <span v-if="errors.email" class="error-message">{{ errors.email }}</span>
              </div>

              <!-- Password Field -->
              <div class="form-group">
                <label for="password">
                  <i class="fas fa-lock"></i>
                  Password
                </label>
                <div class="password-input-group">
                  <input
                    id="password"
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    required
                    class="form-input"
                    :class="{ error: errors.password }"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                  >
                  <button
                    type="button"
                    class="password-toggle"
                    @click="showPassword = !showPassword"
                  >
                    <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                  </button>
                </div>
                <span v-if="errors.password" class="error-message">{{ errors.password }}</span>
              </div>

              <!-- Remember Me & Forgot Password -->
              <div class="form-options">
                <div class="checkbox-group">
                  <input
                    id="remember"
                    v-model="form.remember"
                    type="checkbox"
                    class="checkbox-input"
                  >
                  <label for="remember" class="checkbox-label">
                    Remember me
                  </label>
                </div>
                
                <router-link
                  :to="loginType === 'admin' ? '/admin/forgot-password' : '/forgot-password'"
                  class="forgot-link"
                >
                  Forgot Password?
                </router-link>
              </div>

              <!-- Login Button -->
              <button
                type="submit"
                class="login-btn"
                :disabled="submitting"
              >
                <i v-if="submitting" class="fas fa-spinner fa-spin"></i>
                <i v-else class="fas fa-sign-in-alt"></i>
                {{ submitting ? 'Signing In...' : 'Sign In' }}
              </button>

              <!-- Error Message -->
              <div v-if="loginError" class="login-error">
                <i class="fas fa-exclamation-triangle"></i>
                {{ loginError }}
              </div>

              <!-- Register Link (for members only) -->
              <div v-if="loginType === 'user'" class="register-link">
                <p>
                  Don't have an account?
                  <router-link to="/join-us" class="link">
                    Join as a Member
                  </router-link>
                </p>
              </div>
            </form>
          </div>

          <!-- Info Sidebar -->
          <div class="auth-sidebar">
            <!-- Benefits Card -->
            <div class="sidebar-card">
              <div class="card-icon">
                <i :class="loginType === 'admin' ? 'fas fa-cogs' : 'fas fa-users'"></i>
              </div>
              <h3>{{ loginType === 'admin' ? 'Admin Features' : 'Member Benefits' }}</h3>
              <ul class="benefits-list">
                <li v-for="benefit in currentBenefits" :key="benefit">
                  <i class="fas fa-check"></i>
                  {{ benefit }}
                </li>
              </ul>
            </div>

            <!-- Security Info -->
            <div class="sidebar-card">
              <div class="card-icon security">
                <i class="fas fa-shield-alt"></i>
              </div>
              <h3>Secure Login</h3>
              <p>
                Your data is protected with industry-standard encryption and security measures.
                We never store your password in plain text.
              </p>
              <div class="security-features">
                <div class="security-item">
                  <i class="fas fa-lock"></i>
                  <span>SSL Encrypted</span>
                </div>
                <div class="security-item">
                  <i class="fas fa-user-shield"></i>
                  <span>Privacy Protected</span>
                </div>
                <div class="security-item">
                  <i class="fas fa-database"></i>
                  <span>Secure Storage</span>
                </div>
              </div>
            </div>

            <!-- Contact Support -->
            <div class="sidebar-card">
              <div class="card-icon support">
                <i class="fas fa-headset"></i>
              </div>
              <h3>Need Help?</h3>
              <p>
                Having trouble logging in? Our support team is here to help you.
              </p>
              <div class="contact-options">
                <a href="tel:+919876543210" class="contact-item">
                  <i class="fas fa-phone"></i>
                  <span>+91 9876543210</span>
                </a>
                <a href="mailto:support@bhrc.org" class="contact-item">
                  <i class="fas fa-envelope"></i>
                  <span>support@bhrc.org</span>
                </a>
                <div class="contact-item">
                  <i class="fas fa-clock"></i>
                  <span>24/7 Support</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Recent Activity Section (for returning users) -->
    <section v-if="recentActivity.length > 0" class="activity-section">
      <div class="container">
        <div class="section-header">
          <h2>Recent Activity</h2>
          <p>Welcome back! Here's what's been happening</p>
        </div>
        
        <div class="activity-grid">
          <div
            v-for="activity in recentActivity"
            :key="activity.id"
            class="activity-card"
          >
            <div class="activity-icon">
              <i :class="activity.icon"></i>
            </div>
            <div class="activity-content">
              <h4>{{ activity.title }}</h4>
              <p>{{ activity.description }}</p>
              <span class="activity-time">{{ formatTime(activity.created_at) }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>
  </DefaultLayout>
</template>

<script>
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import { useAuthStore } from '@/stores/auth'

export default {
  name: 'Login',
  components: {
    DefaultLayout
  },
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    return {
      loginType: 'user', // 'user' or 'admin'
      showPassword: false,
      submitting: false,
      loginError: '',
      form: {
        email: '',
        password: '',
        remember: false
      },
      errors: {},
      recentActivity: [],
      memberBenefits: [
        'Access to member dashboard',
        'File complaints online',
        'Track complaint status',
        'Download certificates',
        'Event registration',
        'Donation history',
        'Profile management',
        'Newsletter updates'
      ],
      adminBenefits: [
        'Complete system administration',
        'Member management',
        'Complaint handling',
        'Event management',
        'Content management',
        'Analytics and reports',
        'Gallery management',
        'System configuration'
      ]
    }
  },
  computed: {
    currentBenefits() {
      return this.loginType === 'admin' ? this.adminBenefits : this.memberBenefits
    }
  },
  async mounted() {
    // Check if user is already logged in
    if (this.authStore.isAuthenticated) {
      this.redirectAfterLogin()
      return
    }
    
    // Load recent activity for returning users
    await this.loadRecentActivity()
    
    // Check for login type from query params
    const loginTypeParam = this.$route.query.type
    if (loginTypeParam === 'admin') {
      this.loginType = 'admin'
    }
    
    // Pre-fill email if provided in query params
    const emailParam = this.$route.query.email
    if (emailParam) {
      this.form.email = emailParam
    }
  },
  methods: {
    switchLoginType(type) {
      this.loginType = type
      this.clearForm()
      this.clearErrors()
      
      // Update URL without page reload
      const query = { ...this.$route.query }
      if (type === 'admin') {
        query.type = 'admin'
      } else {
        delete query.type
      }
      this.$router.replace({ query })
    },
    clearForm() {
      this.form = {
        email: '',
        password: '',
        remember: false
      }
    },
    clearErrors() {
      this.errors = {}
      this.loginError = ''
    },
    validateForm() {
      this.errors = {}
      
      // Email validation
      if (!this.form.email.trim()) {
        this.errors.email = 'Email is required'
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.email)) {
        this.errors.email = 'Please enter a valid email address'
      }
      
      // Password validation
      if (!this.form.password) {
        this.errors.password = 'Password is required'
      } else if (this.form.password.length < 6) {
        this.errors.password = 'Password must be at least 6 characters'
      }
      
      return Object.keys(this.errors).length === 0
    },
    async handleLogin() {
      this.clearErrors()
      
      if (!this.validateForm()) {
        // Focus on first error field
        const firstErrorField = Object.keys(this.errors)[0]
        const element = document.getElementById(firstErrorField)
        if (element) {
          element.focus()
        }
        return
      }
      
      try {
        this.submitting = true
        
        // Prepare login data
        const loginData = {
          email: this.form.email.trim(),
          password: this.form.password,
          remember: this.form.remember,
          login_type: this.loginType
        }
        
        // Submit login request
        const endpoint = this.loginType === 'admin' 
          ? 'https://bhrcdata.online/backend/api.php/auth/admin-login'
          : 'https://bhrcdata.online/backend/api.php/auth/login'
          
        const response = await fetch(endpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(loginData)
        })
        
        const data = await response.json()
        
        if (data.success) {
          // Store authentication data
          await this.authStore.login({
            user: data.user,
            token: data.token,
            type: this.loginType
          })
          
          // Show success message
          this.$toast?.success(`Welcome back, ${data.user.name}!`)
          
          // Redirect to appropriate dashboard
          this.redirectAfterLogin()
        } else {
          this.loginError = data.message || 'Invalid email or password'
          
          // Clear password field on error
          this.form.password = ''
        }
      } catch (error) {
        console.error('Login error:', error)
        this.loginError = 'Login failed. Please check your connection and try again.'
        this.form.password = ''
      } finally {
        this.submitting = false
      }
    },
    redirectAfterLogin() {
      // Get redirect URL from query params or use default
      const redirectTo = this.$route.query.redirect
      
      if (redirectTo) {
        this.$router.push(redirectTo)
      } else if (this.loginType === 'admin' || this.authStore.user?.role === 'admin') {
        this.$router.push('/admin/dashboard')
      } else {
        this.$router.push('/member/dashboard')
      }
    },
    async loadRecentActivity() {
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/activity/recent')
        if (response.ok) {
          const data = await response.json()
          this.recentActivity = data.activities || []
        }
      } catch (error) {
        console.error('Error loading recent activity:', error)
      }
    },
    formatTime(dateString) {
      const date = new Date(dateString)
      const now = new Date()
      const diffInHours = Math.floor((now - date) / (1000 * 60 * 60))
      
      if (diffInHours < 1) return 'Just now'
      if (diffInHours < 24) return `${diffInHours}h ago`
      return `${Math.floor(diffInHours / 24)}d ago`
    }
  }
}
</script>

<style scoped>
/* Auth Hero */
.auth-hero {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
  padding: 60px 0;
  text-align: center;
}

.hero-title {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 1rem;
}

.hero-subtitle {
  font-size: 1.1rem;
  opacity: 0.9;
}

/* Login Section */
.login-section {
  padding: 4rem 0;
  background: #f8f9fa;
}

.auth-wrapper {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 3rem;
  max-width: 1200px;
  margin: 0 auto;
}

.auth-form-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

/* Auth Tabs */
.form-header {
  background: #f8f9fa;
  padding: 0;
}

.auth-tabs {
  display: flex;
  border-bottom: 1px solid #e9ecef;
}

.tab-btn {
  flex: 1;
  padding: 1rem 1.5rem;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 1rem;
  font-weight: 500;
  color: #666;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.tab-btn:hover {
  background: #e9ecef;
}

.tab-btn.active {
  background: white;
  color: #dc3545;
  border-bottom: 3px solid #dc3545;
}

.form-title {
  padding: 2rem;
  text-align: center;
  background: white;
}

.form-title h2 {
  font-size: 1.8rem;
  color: #333;
  margin-bottom: 0.5rem;
}

.form-title p {
  color: #666;
  margin: 0;
}

/* Login Form */
.login-form {
  padding: 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #333;
}

.form-group label i {
  color: #dc3545;
  width: 16px;
}

.form-input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.3s ease;
}

.form-input:focus {
  outline: none;
  border-color: #dc3545;
  box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
}

.form-input.error {
  border-color: #dc3545;
}

.password-input-group {
  position: relative;
}

.password-toggle {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #666;
  cursor: pointer;
  padding: 0.25rem;
}

.password-toggle:hover {
  color: #dc3545;
}

.error-message {
  color: #dc3545;
  font-size: 0.8rem;
  margin-top: 0.25rem;
  display: block;
}

/* Form Options */
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.checkbox-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.checkbox-input {
  margin: 0;
}

.checkbox-label {
  font-size: 0.9rem;
  color: #666;
  margin: 0;
}

.forgot-link {
  color: #dc3545;
  text-decoration: none;
  font-size: 0.9rem;
}

.forgot-link:hover {
  text-decoration: underline;
}

/* Login Button */
.login-btn {
  width: 100%;
  background: linear-gradient(135deg, #dc3545, #c82333);
  color: white;
  border: none;
  padding: 1rem;
  border-radius: 8px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.login-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
}

.login-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* Error Message */
.login-error {
  background: #f8d7da;
  color: #721c24;
  padding: 0.75rem;
  border-radius: 6px;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

/* Register Link */
.register-link {
  text-align: center;
  margin-top: 1rem;
}

.register-link p {
  color: #666;
  margin: 0;
}

.register-link .link {
  color: #dc3545;
  text-decoration: none;
  font-weight: 600;
}

.register-link .link:hover {
  text-decoration: underline;
}

/* Auth Sidebar */
.auth-sidebar {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.sidebar-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.card-icon {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #dc3545, #c82333);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
}

.card-icon.security {
  background: linear-gradient(135deg, #28a745, #20c997);
}

.card-icon.support {
  background: linear-gradient(135deg, #007bff, #0056b3);
}

.card-icon i {
  font-size: 1.5rem;
  color: white;
}

.sidebar-card h3 {
  margin-bottom: 1rem;
  color: #333;
}

.sidebar-card p {
  color: #666;
  line-height: 1.6;
  margin-bottom: 1rem;
}

/* Benefits List */
.benefits-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.benefits-list li {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  color: #666;
  font-size: 0.9rem;
}

.benefits-list li i {
  color: #28a745;
  font-size: 0.8rem;
}

/* Security Features */
.security-features {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.security-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #666;
  font-size: 0.9rem;
}

.security-item i {
  color: #28a745;
  width: 16px;
}

/* Contact Options */
.contact-options {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #666;
  text-decoration: none;
  font-size: 0.9rem;
}

.contact-item:hover {
  color: #dc3545;
}

.contact-item i {
  color: #007bff;
  width: 16px;
}

/* Activity Section */
.activity-section {
  padding: 4rem 0;
  background: white;
}

.section-header {
  text-align: center;
  margin-bottom: 3rem;
}

.section-header h2 {
  font-size: 2rem;
  color: #333;
  margin-bottom: 0.5rem;
}

.section-header p {
  color: #666;
}

.activity-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.activity-card {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.5rem;
  background: #f8f9fa;
  border-radius: 8px;
  border-left: 4px solid #dc3545;
}

.activity-icon {
  width: 40px;
  height: 40px;
  background: #dc3545;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.activity-content h4 {
  margin-bottom: 0.5rem;
  color: #333;
}

.activity-content p {
  color: #666;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.activity-time {
  font-size: 0.8rem;
  color: #999;
}

/* Responsive Design */
@media (max-width: 768px) {
  .auth-wrapper {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  
  .auth-tabs {
    flex-direction: column;
  }
  
  .tab-btn {
    padding: 1rem;
  }
  
  .form-options {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .login-form {
    padding: 1.5rem;
  }
  
  .form-title {
    padding: 1.5rem;
  }
  
  .sidebar-card {
    padding: 1.5rem;
  }
  
  .activity-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .auth-hero {
    padding: 40px 0;
  }
  
  .hero-title {
    font-size: 2rem;
  }
  
  .login-section {
    padding: 2rem 0;
  }
  
  .login-form {
    padding: 1rem;
  }
  
  .form-title {
    padding: 1rem;
  }
}
</style>