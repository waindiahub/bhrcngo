import axios from 'axios'
import { useToast } from 'vue-toastification'

// Create axios instance
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'https://bhrcdata.online/backend/api.php',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Export api for named imports
export { api }

// Request interceptor
api.interceptors.request.use(
  (config) => {
    // Add auth token if available
    const token = localStorage.getItem('bhrc_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    
    // Add timestamp to prevent caching
    if (config.method === 'get') {
      config.params = {
        ...config.params,
        _t: Date.now()
      }
    }
    
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response) => {
    return response
  },
  (error) => {
    const toast = useToast()
    
    if (error.response) {
      // Server responded with error status
      const { status, data } = error.response
      
      switch (status) {
        case 401:
          // Unauthorized - clear token and redirect to login
          localStorage.removeItem('bhrc_token')
          delete api.defaults.headers.common['Authorization']
          
          if (window.location.pathname !== '/login') {
            toast.error('Session expired. Please login again.')
            window.location.href = '/login'
          }
          break
          
        case 403:
          toast.error('Access denied. You don\'t have permission to perform this action.')
          break
          
        case 404:
          toast.error('Resource not found.')
          break
          
        case 422:
          // Validation errors
          if (data.errors) {
            const firstError = Object.values(data.errors)[0]
            if (Array.isArray(firstError)) {
              toast.error(firstError[0])
            } else {
              toast.error(firstError)
            }
          } else {
            toast.error(data.message || 'Validation error occurred.')
          }
          break
          
        case 429:
          toast.error('Too many requests. Please try again later.')
          break
          
        case 500:
          toast.error('Server error. Please try again later.')
          break
          
        default:
          toast.error(data.message || 'An error occurred. Please try again.')
      }
    } else if (error.request) {
      // Network error
      toast.error('Network error. Please check your connection.')
    } else {
      // Other error
      toast.error('An unexpected error occurred.')
    }
    
    return Promise.reject(error)
  }
)

// API helper functions
export const apiHelpers = {
  // Handle file uploads
  uploadFile(file, endpoint = '/upload', onProgress = null) {
    const formData = new FormData()
    formData.append('file', file)
    
    return api.post(endpoint, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      },
      onUploadProgress: (progressEvent) => {
        if (onProgress) {
          const percentCompleted = Math.round(
            (progressEvent.loaded * 100) / progressEvent.total
          )
          onProgress(percentCompleted)
        }
      }
    })
  },
  
  // Handle multiple file uploads
  uploadFiles(files, endpoint = '/upload/multiple', onProgress = null) {
    const formData = new FormData()
    files.forEach((file, index) => {
      formData.append(`files[${index}]`, file)
    })
    
    return api.post(endpoint, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      },
      onUploadProgress: (progressEvent) => {
        if (onProgress) {
          const percentCompleted = Math.round(
            (progressEvent.loaded * 100) / progressEvent.total
          )
          onProgress(percentCompleted)
        }
      }
    })
  },
  
  // Download file
  downloadFile(url, filename = null) {
    return api.get(url, {
      responseType: 'blob'
    }).then(response => {
      const blob = new Blob([response.data])
      const downloadUrl = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = downloadUrl
      link.download = filename || 'download'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(downloadUrl)
    })
  },
  
  // Get paginated data
  async getPaginated(endpoint, params = {}) {
    const response = await api.get(endpoint, { params })
    return {
      data: response.data.data || [],
      pagination: response.data.pagination || {},
      total: response.data.total || 0
    }
  },
  
  // Search with debouncing
  searchDebounced: (() => {
    let timeoutId = null
    return (endpoint, query, delay = 300) => {
      return new Promise((resolve, reject) => {
        if (timeoutId) {
          clearTimeout(timeoutId)
        }
        
        timeoutId = setTimeout(async () => {
          try {
            const response = await api.get(endpoint, {
              params: { q: query }
            })
            resolve(response.data)
          } catch (error) {
            reject(error)
          }
        }, delay)
      })
    }
  })()
}

// Export specific API endpoints
export const endpoints = {
  // Authentication
  auth: {
    login: 'auth/login',
    register: 'auth/register',
    logout: 'auth/logout',
    me: 'auth/me',
    profile: 'auth/profile',
    changePassword: 'auth/change-password',
    forgotPassword: 'auth/forgot-password',
    resetPassword: 'auth/reset-password'
  },
  
  // Statistics
  stats: 'statistics',
  
  // Events
  events: 'events',
  eventRegistration: 'event-registration',
  
  // Members
  members: 'members',
  
  // Gallery
  gallery: {
    albums: 'gallery/albums',
    photos: 'gallery/photos',
    videos: 'gallery/videos'
  },
  
  // Donations
  donations: 'donations',
  
  // Complaints
  complaints: 'complaints',
  
  // Newsletter
  newsletter: 'newsletter',
  
  // Activities
  activities: 'activities',
  
  // Contact
  contact: 'contact',
  
  // Files
  upload: 'upload',
  files: 'files',
  
  // Admin
  admin: {
    users: 'admin/users',
    settings: 'admin/settings',
    logs: 'admin/logs',
    notifications: 'admin/notifications',
    dashboard: 'admin/dashboard'
  }
}

// Default export
export default api