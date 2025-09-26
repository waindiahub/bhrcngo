import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'

// Import Bootstrap CSS and JS
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'

// Import global styles
import './assets/scss/main.scss'

// Import Toast notifications
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'

// Import validation
import { configure } from 'vee-validate'
import { localize } from '@vee-validate/i18n'

// Import auth store for initialization
import { useAuthStore } from './stores/auth'

// Configure validation
configure({
  generateMessage: localize('en', {
    messages: {
      required: 'This field is required',
      email: 'This field must be a valid email',
      min: 'This field must have at least {length} characters',
      confirmed: 'This field confirmation does not match'
    }
  })
})

// Create Vue app
const app = createApp(App)

// Create Pinia instance
const pinia = createPinia()

// Use plugins
app.use(pinia)
app.use(router)
app.use(Toast, {
  position: 'top-right',
  timeout: 5000,
  closeOnClick: true,
  pauseOnFocusLoss: true,
  pauseOnHover: true,
  draggable: true,
  draggablePercent: 0.6,
  showCloseButtonOnHover: false,
  hideProgressBar: false,
  closeButton: 'button',
  icon: true,
  rtl: false
})

// Global properties
app.config.globalProperties.$filters = {
  formatDate(date) {
    if (!date) return ''
    return new Date(date).toLocaleDateString('en-IN', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  },
  formatDateTime(date) {
    if (!date) return ''
    return new Date(date).toLocaleString('en-IN', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  },
  formatCurrency(amount) {
    if (!amount) return '₹0'
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR'
    }).format(amount)
  }
}

// Mount app and initialize auth
app.mount('#app')

// Initialize authentication after app is mounted
const authStore = useAuthStore()
authStore.initializeAuth()