import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAppStore = defineStore('app', () => {
  // State
  const isLoading = ref(false)
  const sidebarOpen = ref(false)
  const notifications = ref([])
  const settings = ref({
    siteName: 'Bharatiya Human Rights Council',
    siteDescription: 'Protecting and promoting human rights in India',
    adminEmail: 'admin@bhrcindia.org',
    maxFileSize: 10485760, // 10MB
    allowedFileTypes: ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']
  })

  // Getters
  const unreadNotifications = computed(() => 
    notifications.value.filter(n => !n.read_at)
  )

  const unreadNotificationCount = computed(() => 
    unreadNotifications.value.length
  )

  // Actions
  function setLoading(loading) {
    isLoading.value = loading
  }

  function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value
  }

  function closeSidebar() {
    sidebarOpen.value = false
  }

  function addNotification(notification) {
    notifications.value.unshift({
      id: Date.now(),
      ...notification,
      created_at: new Date().toISOString()
    })
  }

  function markNotificationAsRead(id) {
    const notification = notifications.value.find(n => n.id === id)
    if (notification) {
      notification.read_at = new Date().toISOString()
    }
  }

  function markAllNotificationsAsRead() {
    notifications.value.forEach(notification => {
      if (!notification.read_at) {
        notification.read_at = new Date().toISOString()
      }
    })
  }

  function removeNotification(id) {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index > -1) {
      notifications.value.splice(index, 1)
    }
  }

  function clearNotifications() {
    notifications.value = []
  }

  function updateSettings(newSettings) {
    settings.value = { ...settings.value, ...newSettings }
  }

  async function initializeApp() {
    try {
      setLoading(true)
      
      // Initialize authentication first
      const { useAuthStore } = await import('./auth')
      const authStore = useAuthStore()
      await authStore.initializeAuth()
      
      // Initialize any other app-wide data here
      // For example, load system settings, check authentication, etc.
      
      // Simulate initialization delay
      await new Promise(resolve => setTimeout(resolve, 500))
      
    } catch (error) {
      console.error('Failed to initialize app:', error)
      addNotification({
        type: 'error',
        title: 'Initialization Error',
        message: 'Failed to initialize application. Please refresh the page.'
      })
    } finally {
      setLoading(false)
    }
  }

  // Utility functions
  function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
  }

  function isValidFileType(fileName) {
    const extension = fileName.split('.').pop().toLowerCase()
    return settings.value.allowedFileTypes.includes(extension)
  }

  function isValidFileSize(fileSize) {
    return fileSize <= settings.value.maxFileSize
  }

  return {
    // State
    isLoading,
    sidebarOpen,
    notifications,
    settings,
    
    // Getters
    unreadNotifications,
    unreadNotificationCount,
    
    // Actions
    setLoading,
    toggleSidebar,
    closeSidebar,
    addNotification,
    markNotificationAsRead,
    markAllNotificationsAsRead,
    removeNotification,
    clearNotifications,
    updateSettings,
    initializeApp,
    
    // Utilities
    formatFileSize,
    isValidFileType,
    isValidFileSize
  }
})