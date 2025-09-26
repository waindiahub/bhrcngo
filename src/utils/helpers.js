import { format, parseISO, isValid, formatDistanceToNow } from 'date-fns'

// Date utilities
export const dateUtils = {
  format(date, formatStr = 'dd/MM/yyyy') {
    if (!date) return ''
    
    try {
      const dateObj = typeof date === 'string' ? parseISO(date) : date
      return isValid(dateObj) ? format(dateObj, formatStr) : ''
    } catch (error) {
      console.error('Date formatting error:', error)
      return ''
    }
  },
  
  formatDateTime(date, formatStr = 'dd/MM/yyyy HH:mm') {
    return this.format(date, formatStr)
  },
  
  formatTime(date, formatStr = 'HH:mm') {
    return this.format(date, formatStr)
  },
  
  formatRelative(date) {
    if (!date) return ''
    
    try {
      const dateObj = typeof date === 'string' ? parseISO(date) : date
      return isValid(dateObj) ? formatDistanceToNow(dateObj, { addSuffix: true }) : ''
    } catch (error) {
      console.error('Relative date formatting error:', error)
      return ''
    }
  },
  
  isValidDate(date) {
    if (!date) return false
    const dateObj = typeof date === 'string' ? parseISO(date) : date
    return isValid(dateObj)
  }
}

// String utilities
export const stringUtils = {
  capitalize(str) {
    if (!str) return ''
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase()
  },
  
  capitalizeWords(str) {
    if (!str) return ''
    return str.split(' ').map(word => this.capitalize(word)).join(' ')
  },
  
  truncate(str, length = 100, suffix = '...') {
    if (!str) return ''
    if (str.length <= length) return str
    return str.substring(0, length) + suffix
  },
  
  slugify(str) {
    if (!str) return ''
    return str
      .toLowerCase()
      .trim()
      .replace(/[^\w\s-]/g, '')
      .replace(/[\s_-]+/g, '-')
      .replace(/^-+|-+$/g, '')
  },
  
  stripHtml(str) {
    if (!str) return ''
    return str.replace(/<[^>]*>/g, '')
  },
  
  extractNumbers(str) {
    if (!str) return ''
    return str.replace(/\D/g, '')
  }
}

// Number utilities
export const numberUtils = {
  formatCurrency(amount, currency = '₹', decimals = 2) {
    if (amount === null || amount === undefined) return ''
    
    const num = parseFloat(amount)
    if (isNaN(num)) return ''
    
    return `${currency}${num.toLocaleString('en-IN', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    })}`
  },
  
  formatNumber(num, decimals = 0) {
    if (num === null || num === undefined) return ''
    
    const number = parseFloat(num)
    if (isNaN(number)) return ''
    
    return number.toLocaleString('en-IN', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    })
  },
  
  formatPercentage(num, decimals = 1) {
    if (num === null || num === undefined) return ''
    
    const number = parseFloat(num)
    if (isNaN(number)) return ''
    
    return `${number.toFixed(decimals)}%`
  },
  
  formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes'
    
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
  }
}

// Validation utilities
export const validationUtils = {
  isEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(email)
  },
  
  isPhone(phone) {
    const phoneRegex = /^[+]?[\d\s\-\(\)]{10,}$/
    return phoneRegex.test(phone)
  },
  
  isIndianPhone(phone) {
    const indianPhoneRegex = /^[+]?91?[6-9]\d{9}$/
    return indianPhoneRegex.test(phone.replace(/\s|-/g, ''))
  },
  
  isUrl(url) {
    try {
      new URL(url)
      return true
    } catch {
      return false
    }
  },
  
  isStrongPassword(password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character
    const strongPasswordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/
    return strongPasswordRegex.test(password)
  },
  
  isPincode(pincode) {
    const pincodeRegex = /^[1-9][0-9]{5}$/
    return pincodeRegex.test(pincode)
  },
  
  isAadhar(aadhar) {
    const aadharRegex = /^[2-9]{1}[0-9]{3}[0-9]{4}[0-9]{4}$/
    return aadharRegex.test(aadhar.replace(/\s/g, ''))
  },
  
  isPAN(pan) {
    const panRegex = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/
    return panRegex.test(pan.toUpperCase())
  }
}

// Array utilities
export const arrayUtils = {
  groupBy(array, key) {
    return array.reduce((groups, item) => {
      const group = item[key]
      groups[group] = groups[group] || []
      groups[group].push(item)
      return groups
    }, {})
  },
  
  sortBy(array, key, direction = 'asc') {
    return [...array].sort((a, b) => {
      const aVal = a[key]
      const bVal = b[key]
      
      if (direction === 'desc') {
        return bVal > aVal ? 1 : bVal < aVal ? -1 : 0
      }
      return aVal > bVal ? 1 : aVal < bVal ? -1 : 0
    })
  },
  
  unique(array, key = null) {
    if (key) {
      const seen = new Set()
      return array.filter(item => {
        const value = item[key]
        if (seen.has(value)) {
          return false
        }
        seen.add(value)
        return true
      })
    }
    return [...new Set(array)]
  },
  
  chunk(array, size) {
    const chunks = []
    for (let i = 0; i < array.length; i += size) {
      chunks.push(array.slice(i, i + size))
    }
    return chunks
  }
}

// Object utilities
export const objectUtils = {
  pick(obj, keys) {
    const result = {}
    keys.forEach(key => {
      if (key in obj) {
        result[key] = obj[key]
      }
    })
    return result
  },
  
  omit(obj, keys) {
    const result = { ...obj }
    keys.forEach(key => {
      delete result[key]
    })
    return result
  },
  
  isEmpty(obj) {
    return Object.keys(obj).length === 0
  },
  
  deepClone(obj) {
    return JSON.parse(JSON.stringify(obj))
  },
  
  flattenObject(obj, prefix = '') {
    const flattened = {}
    
    for (const key in obj) {
      if (obj.hasOwnProperty(key)) {
        const newKey = prefix ? `${prefix}.${key}` : key
        
        if (typeof obj[key] === 'object' && obj[key] !== null && !Array.isArray(obj[key])) {
          Object.assign(flattened, this.flattenObject(obj[key], newKey))
        } else {
          flattened[newKey] = obj[key]
        }
      }
    }
    
    return flattened
  }
}

// File utilities
export const fileUtils = {
  getFileExtension(filename) {
    return filename.split('.').pop().toLowerCase()
  },
  
  isImageFile(filename) {
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']
    return imageExtensions.includes(this.getFileExtension(filename))
  },
  
  isVideoFile(filename) {
    const videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm']
    return videoExtensions.includes(this.getFileExtension(filename))
  },
  
  isDocumentFile(filename) {
    const docExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt']
    return docExtensions.includes(this.getFileExtension(filename))
  },
  
  readFileAsDataURL(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader()
      reader.onload = () => resolve(reader.result)
      reader.onerror = reject
      reader.readAsDataURL(file)
    })
  },
  
  readFileAsText(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader()
      reader.onload = () => resolve(reader.result)
      reader.onerror = reject
      reader.readAsText(file)
    })
  }
}

// Local storage utilities
export const storageUtils = {
  set(key, value) {
    try {
      localStorage.setItem(key, JSON.stringify(value))
    } catch (error) {
      console.error('Error saving to localStorage:', error)
    }
  },
  
  get(key, defaultValue = null) {
    try {
      const item = localStorage.getItem(key)
      return item ? JSON.parse(item) : defaultValue
    } catch (error) {
      console.error('Error reading from localStorage:', error)
      return defaultValue
    }
  },
  
  remove(key) {
    try {
      localStorage.removeItem(key)
    } catch (error) {
      console.error('Error removing from localStorage:', error)
    }
  },
  
  clear() {
    try {
      localStorage.clear()
    } catch (error) {
      console.error('Error clearing localStorage:', error)
    }
  }
}

// Debounce utility
export function debounce(func, wait, immediate = false) {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      timeout = null
      if (!immediate) func(...args)
    }
    const callNow = immediate && !timeout
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
    if (callNow) func(...args)
  }
}

// Throttle utility
export function throttle(func, limit) {
  let inThrottle
  return function(...args) {
    if (!inThrottle) {
      func.apply(this, args)
      inThrottle = true
      setTimeout(() => inThrottle = false, limit)
    }
  }
}

// Generate random ID
export function generateId(length = 8) {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
  let result = ''
  for (let i = 0; i < length; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  return result
}

// Copy to clipboard
export async function copyToClipboard(text) {
  try {
    await navigator.clipboard.writeText(text)
    return true
  } catch (error) {
    console.error('Failed to copy to clipboard:', error)
    return false
  }
}

// Scroll to element
export function scrollToElement(elementId, offset = 0) {
  const element = document.getElementById(elementId)
  if (element) {
    const elementPosition = element.offsetTop - offset
    window.scrollTo({
      top: elementPosition,
      behavior: 'smooth'
    })
  }
}

// Check if element is in viewport
export function isInViewport(element) {
  const rect = element.getBoundingClientRect()
  return (
    rect.top >= 0 &&
    rect.left >= 0 &&
    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
  )
}