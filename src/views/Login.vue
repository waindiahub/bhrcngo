<template>
  <div class="login-page">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
          <div class="login-card">
            <!-- Logo and Title -->
            <div class="login-header">
              <div class="logo">
                <img src="@/assets/images/logo.png" alt="BHRC Logo" class="logo-img">
              </div>
              <h2 class="login-title">Welcome Back</h2>
              <p class="login-subtitle">Sign in to your account</p>
            </div>

            <!-- Login Form -->
            <form @submit.prevent="handleLogin" class="login-form">
              <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                  <span class="input-group-text">
                    <i class="fas fa-envelope"></i>
                  </span>
                  <input
                    type="email"
                    id="email"
                    v-model="loginForm.email"
                    class="form-control"
                    :class="{ 'is-invalid': errors.email }"
                    placeholder="Enter your email"
                    required
                  >
                </div>
                <div v-if="errors.email" class="invalid-feedback">
                  {{ errors.email }}
                </div>
              </div>

              <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                  <span class="input-group-text">
                    <i class="fas fa-lock"></i>
                  </span>
                  <input
                    :type="showPassword ? 'text' : 'password'"
                    id="password"
                    v-model="loginForm.password"
                    class="form-control"
                    :class="{ 'is-invalid': errors.password }"
                    placeholder="Enter your password"
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
              </div>

              <div class="form-options">
                <div class="form-check">
                  <input
                    type="checkbox"
                    id="remember"
                    v-model="loginForm.remember"
                    class="form-check-input"
                  >
                  <label for="remember" class="form-check-label">
                    Remember me
                  </label>
                </div>
                <router-link to="/forgot-password" class="forgot-link">
                  Forgot Password?
                </router-link>
              </div>

              <button
                type="submit"
                class="btn btn-primary btn-login"
                :disabled="isLoading"
              >
                <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                {{ isLoading ? 'Signing In...' : 'Sign In' }}
              </button>

              <!-- Error Message -->
              <div v-if="errorMessage" class="alert alert-danger mt-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ errorMessage }}
              </div>

              <!-- Success Message -->
              <div v-if="successMessage" class="alert alert-success mt-3">
                <i class="fas fa-check-circle me-2"></i>
                {{ successMessage }}
              </div>
            </form>

            <!-- Register Link -->
            <div class="login-footer">
              <p class="register-text">
                Don't have an account?
                <router-link to="/register" class="register-link">
                  Create Account
                </router-link>
              </p>
            </div>

            <!-- Social Login -->
            <div class="social-login">
              <div class="divider">
                <span>Or continue with</span>
              </div>
              <div class="social-buttons">
                <button class="btn btn-social btn-google" @click="loginWithGoogle">
                  <i class="fab fa-google"></i>
                  Google
                </button>
                <button class="btn btn-social btn-facebook" @click="loginWithFacebook">
                  <i class="fab fa-facebook-f"></i>
                  Facebook
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import api from '@/utils/api'

export default {
  name: 'Login',
  data() {
    return {
      loginForm: {
        email: '',
        password: '',
        remember: false
      },
      showPassword: false,
      isLoading: false,
      errorMessage: '',
      successMessage: '',
      errors: {}
    }
  },
  methods: {
    async handleLogin() {
      this.clearMessages()
      
      if (!this.validateForm()) {
        return
      }

      this.isLoading = true

      try {
        // API call to login endpoint
        const response = await api.post('auth/login', {
          email: this.loginForm.email,
          password: this.loginForm.password,
          remember: this.loginForm.remember
        })

        if (response.data.success) {
          this.successMessage = 'Login successful! Redirecting...'
          
          // Update auth store properly using the setToken and setUser methods
          const authStore = useAuthStore()
          authStore.setToken(response.data.data.token)
          authStore.setUser(response.data.data)
          
          // Store user data in localStorage (for persistence)
          localStorage.setItem('user', JSON.stringify(response.data.data))
          
          // Check for redirect parameter first, then redirect based on user role
          setTimeout(() => {
            const redirectPath = this.$route.query.redirect
            
            if (redirectPath) {
              // If there's a redirect parameter, decode it and use it
              const decodedPath = decodeURIComponent(redirectPath)
              this.$router.push(decodedPath)
            } else {
              // Otherwise, redirect based on user role
              if (response.data.data.role === 'admin') {
                this.$router.push('/admin/dashboard')
              } else if (response.data.data.role === 'moderator') {
                this.$router.push('/admin/dashboard') // Moderators use admin dashboard
              } else if (response.data.data.role === 'member') {
                this.$router.push('/member/dashboard')
              } else if (response.data.data.role === 'volunteer') {
                this.$router.push('/member/dashboard') // Volunteers use member dashboard
              } else {
                this.$router.push('/') // Regular users go to home
              }
            }
          }, 1500)
        } else {
          this.errorMessage = response.data.message || 'Login failed. Please try again.'
        }
      } catch (error) {
        console.error('Login error:', error)
        this.errorMessage = 'Network error. Please check your connection and try again.'
      } finally {
        this.isLoading = false
      }
    },

    validateForm() {
      this.errors = {}
      let isValid = true

      // Email validation
      if (!this.loginForm.email) {
        this.errors.email = 'Email is required'
        isValid = false
      } else if (!this.isValidEmail(this.loginForm.email)) {
        this.errors.email = 'Please enter a valid email address'
        isValid = false
      }

      // Password validation
      if (!this.loginForm.password) {
        this.errors.password = 'Password is required'
        isValid = false
      } else if (this.loginForm.password.length < 6) {
        this.errors.password = 'Password must be at least 6 characters'
        isValid = false
      }

      return isValid
    },

    isValidEmail(email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      return emailRegex.test(email)
    },

    togglePassword() {
      this.showPassword = !this.showPassword
    },

    clearMessages() {
      this.errorMessage = ''
      this.successMessage = ''
      this.errors = {}
    },

    async loginWithGoogle() {
      // Implement Google OAuth login
      this.errorMessage = 'Google login is not yet implemented'
    },

    async loginWithFacebook() {
      // Implement Facebook OAuth login
      this.errorMessage = 'Facebook login is not yet implemented'
    }
  },

  mounted() {
    document.title = 'Login - BHRC'
    
    // Check if user is already logged in
    const token = localStorage.getItem('token')
    if (token) {
      this.$router.push('/')
    }
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  padding: 2rem 0;
}

.login-card {
  background: white;
  border-radius: 1rem;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  padding: 2.5rem;
  width: 100%;
}

.login-header {
  text-align: center;
  margin-bottom: 2rem;
}

.logo {
  margin-bottom: 1.5rem;
}

.logo-img {
  width: 80px;
  height: 80px;
  object-fit: contain;
}

.login-title {
  font-size: 2rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.login-subtitle {
  color: #6c757d;
  font-size: 1rem;
}

.login-form {
  margin-bottom: 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  font-weight: 500;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.input-group {
  position: relative;
}

.input-group-text {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  color: #6c757d;
}

.form-control {
  border: 1px solid #e9ecef;
  border-radius: 0.5rem;
  padding: 0.75rem 1rem;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.form-control:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-control.is-invalid {
  border-color: #dc3545;
}

.invalid-feedback {
  display: block;
  font-size: 0.875rem;
  color: #dc3545;
  margin-top: 0.25rem;
}

.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.form-check {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.form-check-input {
  margin: 0;
}

.form-check-label {
  color: #6c757d;
  font-size: 0.9rem;
  margin: 0;
}

.forgot-link {
  color: #667eea;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
}

.forgot-link:hover {
  color: #5a6fd8;
  text-decoration: underline;
}

.btn-login {
  width: 100%;
  padding: 0.75rem;
  font-size: 1rem;
  font-weight: 500;
  background: #667eea;
  border-color: #667eea;
  border-radius: 0.5rem;
  transition: all 0.3s ease;
}

.btn-login:hover:not(:disabled) {
  background: #5a6fd8;
  border-color: #5a6fd8;
  transform: translateY(-1px);
}

.btn-login:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.alert {
  border-radius: 0.5rem;
  border: none;
  font-size: 0.9rem;
}

.login-footer {
  text-align: center;
  padding-top: 1.5rem;
  border-top: 1px solid #e9ecef;
}

.register-text {
  color: #6c757d;
  margin: 0;
}

.register-link {
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
  margin-left: 0.5rem;
}

.register-link:hover {
  color: #5a6fd8;
  text-decoration: underline;
}

.social-login {
  margin-top: 2rem;
}

.divider {
  text-align: center;
  position: relative;
  margin-bottom: 1.5rem;
}

.divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: #e9ecef;
}

.divider span {
  background: white;
  padding: 0 1rem;
  color: #6c757d;
  font-size: 0.9rem;
}

.social-buttons {
  display: flex;
  gap: 1rem;
}

.btn-social {
  flex: 1;
  padding: 0.75rem;
  border-radius: 0.5rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.btn-google {
  background: #db4437;
  border-color: #db4437;
  color: white;
}

.btn-google:hover {
  background: #c23321;
  border-color: #c23321;
}

.btn-facebook {
  background: #3b5998;
  border-color: #3b5998;
  color: white;
}

.btn-facebook:hover {
  background: #2d4373;
  border-color: #2d4373;
}

@media (max-width: 768px) {
  .login-card {
    padding: 2rem 1.5rem;
    margin: 1rem;
  }
  
  .login-title {
    font-size: 1.5rem;
  }
  
  .form-options {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
  
  .social-buttons {
    flex-direction: column;
  }
}
</style>