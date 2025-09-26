import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/utils/api'
import { useToast } from 'vue-toastification'

export const useAuthStore = defineStore('auth', () => {
  const toast = useToast()
  
  // State
  const user = ref(null)
  const token = ref(localStorage.getItem('token'))
  const isLoading = ref(false)
  const isInitialized = ref(false) // Add initialization flag

  // Getters
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const userRole = computed(() => user.value?.role || null)
  const userName = computed(() => user.value?.name || '')
  const userEmail = computed(() => user.value?.email || '')

  // Actions
  function setToken(newToken) {
    token.value = newToken
    if (newToken) {
      localStorage.setItem('bhrc_token', newToken)
      api.defaults.headers.common['Authorization'] = `Bearer ${newToken}`
    } else {
      localStorage.removeItem('bhrc_token')
      delete api.defaults.headers.common['Authorization']
    }
  }

  function setUser(userData) {
    user.value = userData
  }

  async function login(credentials) {
    try {
      isLoading.value = true
      
      const response = await api.post('/auth/login', credentials)
      
      if (response.data.success) {
        const { token: authToken, user: userData } = response.data.data
        
        setToken(authToken)
        setUser(userData)
        
        toast.success('Login successful!')
        return { success: true, user: userData }
      } else {
        throw new Error(response.data.message || 'Login failed')
      }
    } catch (error) {
      const message = error.response?.data?.message || error.message || 'Login failed'
      toast.error(message)
      return { success: false, message }
    } finally {
      isLoading.value = false
    }
  }

  async function register(userData) {
    try {
      isLoading.value = true
      
      const response = await api.post('/auth/register', userData)
      
      if (response.data.success) {
        toast.success('Registration successful! Please check your email for verification.')
        return { success: true, message: response.data.message }
      } else {
        throw new Error(response.data.message || 'Registration failed')
      }
    } catch (error) {
      const message = error.response?.data?.message || error.message || 'Registration failed'
      toast.error(message)
      return { success: false, message }
    } finally {
      isLoading.value = false
    }
  }

  async function logout() {
    try {
      // Call logout endpoint to invalidate token on server
      if (token.value) {
        await api.post('/auth/logout')
      }
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      // Clear local state regardless of API call result
      setToken(null)
      setUser(null)
      toast.info('You have been logged out')
    }
  }

  async function fetchUser() {
    try {
      if (!token.value) return false
      
      const response = await api.get('/auth/me')
      
      if (response.data.success) {
        setUser(response.data.data)
        return true
      } else {
        // Token is invalid, clear it
        console.log('fetchUser: Token invalid, clearing auth state')
        setToken(null)
        setUser(null)
        return false
      }
    } catch (error) {
      console.error('Fetch user error:', error)
      
      // Check if it's a 401 (unauthorized) or 403 (forbidden) error
      if (error.response && (error.response.status === 401 || error.response.status === 403)) {
        console.log('fetchUser: Authentication failed, clearing auth state')
        setToken(null)
        setUser(null)
      } else {
        // For other errors (network issues, etc.), don't clear the token
        // This prevents losing authentication on temporary network issues
        console.log('fetchUser: Network or server error, keeping token')
      }
      
      return false
    }
  }

  async function updateProfile(profileData) {
    try {
      isLoading.value = true
      
      const response = await api.put('/auth/profile', profileData)
      
      if (response.data.success) {
        setUser(response.data.data)
        toast.success('Profile updated successfully!')
        return { success: true }
      } else {
        throw new Error(response.data.message || 'Profile update failed')
      }
    } catch (error) {
      const message = error.response?.data?.message || error.message || 'Profile update failed'
      toast.error(message)
      return { success: false, message }
    } finally {
      isLoading.value = false
    }
  }

  async function changePassword(passwordData) {
    try {
      isLoading.value = true
      
      const response = await api.put('/auth/change-password', passwordData)
      
      if (response.data.success) {
        toast.success('Password changed successfully!')
        return { success: true }
      } else {
        throw new Error(response.data.message || 'Password change failed')
      }
    } catch (error) {
      const message = error.response?.data?.message || error.message || 'Password change failed'
      toast.error(message)
      return { success: false, message }
    } finally {
      isLoading.value = false
    }
  }

  async function forgotPassword(email) {
    try {
      isLoading.value = true
      
      const response = await api.post('/auth/forgot-password', { email })
      
      if (response.data.success) {
        toast.success('Password reset link sent to your email!')
        return { success: true }
      } else {
        throw new Error(response.data.message || 'Failed to send reset link')
      }
    } catch (error) {
      const message = error.response?.data?.message || error.message || 'Failed to send reset link'
      toast.error(message)
      return { success: false, message }
    } finally {
      isLoading.value = false
    }
  }

  async function resetPassword(resetData) {
    try {
      isLoading.value = true
      
      const response = await api.post('/auth/reset-password', resetData)
      
      if (response.data.success) {
        toast.success('Password reset successfully!')
        return { success: true }
      } else {
        throw new Error(response.data.message || 'Password reset failed')
      }
    } catch (error) {
      const message = error.response?.data?.message || error.message || 'Password reset failed'
      toast.error(message)
      return { success: false, message }
    } finally {
      isLoading.value = false
    }
  }

  function hasRole(role) {
    if (!user.value) return false
    
    const userRoles = Array.isArray(user.value.role) ? user.value.role : [user.value.role]
    
    if (role === 'admin') {
      return userRoles.includes('admin') || userRoles.includes('moderator')
    } else if (role === 'member') {
      return userRoles.includes('admin') || userRoles.includes('moderator') || userRoles.includes('member') || userRoles.includes('volunteer')
    }
    
    return userRoles.includes(role)
  }

  function hasPermission(permission) {
    if (!user.value) return false
    
    // Admin has all permissions
    if (user.value.role === 'admin') return true
    
    // Check specific permissions based on role
    const permissions = user.value.permissions || []
    return permissions.includes(permission)
  }

  // Initialize auth state
  const initializeAuth = async () => {
    console.log('Initializing authentication...')
    
    // If already initialized, skip
    if (isInitialized.value) {
      return
    }
    
    const storedToken = localStorage.getItem('token')
    
    if (storedToken) {
      try {
        // Set token and header first
        token.value = storedToken
        api.defaults.headers.common['Authorization'] = `Bearer ${storedToken}`
        
        // Try to fetch user data to validate token
        await fetchUser()
        
        console.log('Authentication initialized successfully')
      } catch (error) {
        console.warn('Token validation failed during initialization:', error.message)
        // Clear invalid authentication state
        clearAuth()
      }
    } else {
      console.log('No token found, user not authenticated')
    }
    
    isInitialized.value = true
  }

  // Clear authentication state
  const clearAuth = () => {
    user.value = null
    token.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    delete api.defaults.headers.common['Authorization']
    isInitialized.value = false
  }

  return {
    // State
    user,
    token,
    isLoading,
    isInitialized, // Add to return object
    
    // Getters
    isAuthenticated,
    userRole,
    userName,
    userEmail,
    
    // Actions
    setToken,
    setUser,
    login,
    register,
    logout,
    fetchUser,
    updateProfile,
    changePassword,
    forgotPassword,
    resetPassword,
    hasRole,
    hasPermission,
    initializeAuth,
    clearAuth // Add to return object
  }
})