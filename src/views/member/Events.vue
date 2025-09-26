<template>
  <div class="member-events-page">
    <!-- Header Section -->
    <div class="events-header">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-8">
            <div class="header-content">
              <h1 class="page-title">
                <i class="fas fa-calendar-alt me-3"></i>
                My Events
              </h1>
              <p class="page-subtitle">
                Track your registered events, view history, and discover upcoming opportunities
              </p>
            </div>
          </div>
          <div class="col-lg-4 text-lg-end">
            <div class="header-stats">
              <div class="stat-card">
                <div class="stat-value">{{ eventStats.registered }}</div>
                <div class="stat-label">Registered</div>
              </div>
              <div class="stat-card">
                <div class="stat-value">{{ eventStats.attended }}</div>
                <div class="stat-label">Attended</div>
              </div>
              <div class="stat-card">
                <div class="stat-value">{{ eventStats.upcoming }}</div>
                <div class="stat-label">Upcoming</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Events Content -->
    <div class="events-content">
      <div class="container">
        <!-- Filter and Search Section -->
        <div class="events-filters">
          <div class="row align-items-center">
            <div class="col-lg-6">
              <div class="search-box">
                <div class="input-group">
                  <span class="input-group-text">
                    <i class="fas fa-search"></i>
                  </span>
                  <input 
                    type="text" 
                    class="form-control" 
                    placeholder="Search events..."
                    v-model="searchQuery"
                    @input="filterEvents"
                  >
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="filter-controls">
                <select 
                  class="form-select me-2" 
                  v-model="selectedStatus"
                  @change="filterEvents"
                >
                  <option value="">All Status</option>
                  <option value="upcoming">Upcoming</option>
                  <option value="ongoing">Ongoing</option>
                  <option value="completed">Completed</option>
                  <option value="cancelled">Cancelled</option>
                </select>
                
                <select 
                  class="form-select me-2" 
                  v-model="selectedType"
                  @change="filterEvents"
                >
                  <option value="">All Types</option>
                  <option value="workshop">Workshop</option>
                  <option value="seminar">Seminar</option>
                  <option value="conference">Conference</option>
                  <option value="training">Training</option>
                  <option value="meeting">Meeting</option>
                </select>

                <button 
                  class="btn btn-outline-secondary"
                  @click="resetFilters"
                >
                  <i class="fas fa-times me-2"></i>
                  Clear
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Events Tabs -->
        <div class="events-tabs">
          <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
              <button 
                class="nav-link" 
                :class="{ active: activeTab === 'registered' }"
                @click="activeTab = 'registered'"
              >
                <i class="fas fa-calendar-check me-2"></i>
                Registered Events
                <span class="badge bg-primary ms-2">{{ registeredEvents.length }}</span>
              </button>
            </li>
            <li class="nav-item">
              <button 
                class="nav-link" 
                :class="{ active: activeTab === 'available' }"
                @click="activeTab = 'available'"
              >
                <i class="fas fa-calendar-plus me-2"></i>
                Available Events
                <span class="badge bg-success ms-2">{{ availableEvents.length }}</span>
              </button>
            </li>
            <li class="nav-item">
              <button 
                class="nav-link" 
                :class="{ active: activeTab === 'history' }"
                @click="activeTab = 'history'"
              >
                <i class="fas fa-history me-2"></i>
                Event History
                <span class="badge bg-secondary ms-2">{{ eventHistory.length }}</span>
              </button>
            </li>
          </ul>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-state">
          <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Loading events...</p>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="error-state">
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ error }}
            <button class="btn btn-outline-danger btn-sm ms-3" @click="loadEvents">
              <i class="fas fa-redo me-2"></i>
              Retry
            </button>
          </div>
        </div>

        <!-- Tab Content -->
        <div v-else class="tab-content">
          <!-- Registered Events Tab -->
          <div v-show="activeTab === 'registered'" class="tab-pane">
            <div v-if="filteredRegisteredEvents.length === 0" class="empty-state">
              <div class="empty-illustration">
                <i class="fas fa-calendar-times"></i>
              </div>
              <h3>No Registered Events</h3>
              <p>You haven't registered for any events yet. Browse available events to get started!</p>
              <button class="btn btn-primary" @click="activeTab = 'available'">
                <i class="fas fa-calendar-plus me-2"></i>
                Browse Available Events
              </button>
            </div>

            <div v-else class="events-grid">
              <div 
                v-for="event in filteredRegisteredEvents" 
                :key="event.id"
                class="event-card registered"
              >
                <div class="event-image">
                  <img :src="event.image || '/images/default-event.jpg'" :alt="event.title">
                  <div class="event-status" :class="event.status">
                    <i class="fas" :class="getStatusIcon(event.status)"></i>
                    {{ event.status.charAt(0).toUpperCase() + event.status.slice(1) }}
                  </div>
                </div>

                <div class="event-content">
                  <div class="event-meta">
                    <span class="event-type">{{ event.type }}</span>
                    <span class="event-date">
                      <i class="fas fa-calendar me-1"></i>
                      {{ formatDate(event.date) }}
                    </span>
                  </div>

                  <h3 class="event-title">{{ event.title }}</h3>
                  <p class="event-description">{{ event.description }}</p>

                  <div class="event-details">
                    <div class="detail-item">
                      <i class="fas fa-clock"></i>
                      <span>{{ event.time }}</span>
                    </div>
                    <div class="detail-item">
                      <i class="fas fa-map-marker-alt"></i>
                      <span>{{ event.location }}</span>
                    </div>
                    <div class="detail-item">
                      <i class="fas fa-users"></i>
                      <span>{{ event.registered_count }}/{{ event.max_participants }} participants</span>
                    </div>
                  </div>

                  <div class="event-registration-info">
                    <div class="registration-status">
                      <i class="fas fa-check-circle text-success"></i>
                      <span>Registered on {{ formatDate(event.registration_date) }}</span>
                    </div>
                    <div class="registration-id">
                      Registration ID: <strong>{{ event.registration_id }}</strong>
                    </div>
                  </div>

                  <div class="event-actions">
                    <button 
                      class="btn btn-outline-primary btn-sm"
                      @click="viewEventDetails(event)"
                    >
                      <i class="fas fa-eye me-2"></i>
                      View Details
                    </button>
                    
                    <button 
                      v-if="event.status === 'upcoming'"
                      class="btn btn-outline-secondary btn-sm"
                      @click="downloadTicket(event)"
                    >
                      <i class="fas fa-ticket-alt me-2"></i>
                      Download Ticket
                    </button>
                    
                    <button 
                      v-if="event.status === 'completed' && event.certificate_available"
                      class="btn btn-outline-success btn-sm"
                      @click="downloadCertificate(event)"
                    >
                      <i class="fas fa-certificate me-2"></i>
                      Certificate
                    </button>
                    
                    <button 
                      v-if="event.status === 'upcoming' && canCancelRegistration(event)"
                      class="btn btn-outline-danger btn-sm"
                      @click="cancelRegistration(event)"
                    >
                      <i class="fas fa-times me-2"></i>
                      Cancel
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Available Events Tab -->
          <div v-show="activeTab === 'available'" class="tab-pane">
            <div v-if="filteredAvailableEvents.length === 0" class="empty-state">
              <div class="empty-illustration">
                <i class="fas fa-calendar-plus"></i>
              </div>
              <h3>No Available Events</h3>
              <p>There are no events available for registration at the moment. Check back later!</p>
            </div>

            <div v-else class="events-grid">
              <div 
                v-for="event in filteredAvailableEvents" 
                :key="event.id"
                class="event-card available"
              >
                <div class="event-image">
                  <img :src="event.image || '/images/default-event.jpg'" :alt="event.title">
                  <div class="event-badge" v-if="event.is_featured">
                    <i class="fas fa-star"></i>
                    Featured
                  </div>
                </div>

                <div class="event-content">
                  <div class="event-meta">
                    <span class="event-type">{{ event.type }}</span>
                    <span class="event-date">
                      <i class="fas fa-calendar me-1"></i>
                      {{ formatDate(event.date) }}
                    </span>
                  </div>

                  <h3 class="event-title">{{ event.title }}</h3>
                  <p class="event-description">{{ event.description }}</p>

                  <div class="event-details">
                    <div class="detail-item">
                      <i class="fas fa-clock"></i>
                      <span>{{ event.time }}</span>
                    </div>
                    <div class="detail-item">
                      <i class="fas fa-map-marker-alt"></i>
                      <span>{{ event.location }}</span>
                    </div>
                    <div class="detail-item">
                      <i class="fas fa-users"></i>
                      <span>{{ event.registered_count }}/{{ event.max_participants }} participants</span>
                    </div>
                  </div>

                  <div class="event-pricing" v-if="event.fee">
                    <div class="price">
                      <span class="currency">₹</span>
                      <span class="amount">{{ event.fee }}</span>
                    </div>
                  </div>

                  <div class="event-actions">
                    <button 
                      class="btn btn-outline-primary btn-sm"
                      @click="viewEventDetails(event)"
                    >
                      <i class="fas fa-eye me-2"></i>
                      View Details
                    </button>
                    
                    <button 
                      class="btn btn-primary btn-sm"
                      @click="registerForEvent(event)"
                      :disabled="!canRegister(event) || registering === event.id"
                    >
                      <i class="fas me-2" :class="registering === event.id ? 'fa-spinner fa-spin' : 'fa-plus'"></i>
                      {{ registering === event.id ? 'Registering...' : 'Register' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Event History Tab -->
          <div v-show="activeTab === 'history'" class="tab-pane">
            <div v-if="filteredEventHistory.length === 0" class="empty-state">
              <div class="empty-illustration">
                <i class="fas fa-history"></i>
              </div>
              <h3>No Event History</h3>
              <p>Your event history will appear here once you attend events.</p>
            </div>

            <div v-else class="history-timeline">
              <div 
                v-for="event in filteredEventHistory" 
                :key="event.id"
                class="timeline-item"
              >
                <div class="timeline-marker" :class="event.status">
                  <i class="fas" :class="getStatusIcon(event.status)"></i>
                </div>
                
                <div class="timeline-content">
                  <div class="timeline-header">
                    <h4 class="timeline-title">{{ event.title }}</h4>
                    <span class="timeline-date">{{ formatDate(event.date) }}</span>
                  </div>
                  
                  <div class="timeline-details">
                    <div class="detail-row">
                      <span class="detail-label">Type:</span>
                      <span class="detail-value">{{ event.type }}</span>
                    </div>
                    <div class="detail-row">
                      <span class="detail-label">Location:</span>
                      <span class="detail-value">{{ event.location }}</span>
                    </div>
                    <div class="detail-row">
                      <span class="detail-label">Status:</span>
                      <span class="detail-value" :class="event.status">{{ event.status }}</span>
                    </div>
                    <div class="detail-row" v-if="event.attendance_status">
                      <span class="detail-label">Attendance:</span>
                      <span class="detail-value" :class="event.attendance_status">{{ event.attendance_status }}</span>
                    </div>
                  </div>
                  
                  <div class="timeline-actions">
                    <button 
                      class="btn btn-outline-primary btn-sm"
                      @click="viewEventDetails(event)"
                    >
                      <i class="fas fa-eye me-2"></i>
                      View Details
                    </button>
                    
                    <button 
                      v-if="event.certificate_available"
                      class="btn btn-outline-success btn-sm"
                      @click="downloadCertificate(event)"
                    >
                      <i class="fas fa-certificate me-2"></i>
                      Certificate
                    </button>
                    
                    <button 
                      v-if="event.feedback_available"
                      class="btn btn-outline-warning btn-sm"
                      @click="provideFeedback(event)"
                    >
                      <i class="fas fa-star me-2"></i>
                      Feedback
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="pagination-container">
          <nav aria-label="Events pagination">
            <ul class="pagination justify-content-center">
              <li class="page-item" :class="{ disabled: currentPage === 1 }">
                <button class="page-link" @click="changePage(currentPage - 1)" :disabled="currentPage === 1">
                  <i class="fas fa-chevron-left"></i>
                </button>
              </li>
              
              <li 
                v-for="page in visiblePages" 
                :key="page"
                class="page-item" 
                :class="{ active: page === currentPage }"
              >
                <button class="page-link" @click="changePage(page)">{{ page }}</button>
              </li>
              
              <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                <button class="page-link" @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages">
                  <i class="fas fa-chevron-right"></i>
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- Event Details Modal -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1" ref="eventDetailsModal">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ selectedEvent?.title }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" v-if="selectedEvent">
            <div class="event-details-content">
              <div class="event-image-large">
                <img :src="selectedEvent.image || '/images/default-event.jpg'" :alt="selectedEvent.title">
              </div>
              
              <div class="event-info-grid">
                <div class="info-item">
                  <i class="fas fa-calendar"></i>
                  <div>
                    <strong>Date & Time</strong>
                    <p>{{ formatDate(selectedEvent.date) }} at {{ selectedEvent.time }}</p>
                  </div>
                </div>
                
                <div class="info-item">
                  <i class="fas fa-map-marker-alt"></i>
                  <div>
                    <strong>Location</strong>
                    <p>{{ selectedEvent.location }}</p>
                  </div>
                </div>
                
                <div class="info-item">
                  <i class="fas fa-users"></i>
                  <div>
                    <strong>Participants</strong>
                    <p>{{ selectedEvent.registered_count }}/{{ selectedEvent.max_participants }}</p>
                  </div>
                </div>
                
                <div class="info-item" v-if="selectedEvent.fee">
                  <i class="fas fa-rupee-sign"></i>
                  <div>
                    <strong>Registration Fee</strong>
                    <p>₹{{ selectedEvent.fee }}</p>
                  </div>
                </div>
              </div>
              
              <div class="event-description-full">
                <h6>About This Event</h6>
                <p>{{ selectedEvent.full_description || selectedEvent.description }}</p>
              </div>
              
              <div class="event-organizer" v-if="selectedEvent.organizer">
                <h6>Organizer</h6>
                <div class="organizer-info">
                  <img :src="selectedEvent.organizer.avatar" :alt="selectedEvent.organizer.name">
                  <div>
                    <strong>{{ selectedEvent.organizer.name }}</strong>
                    <p>{{ selectedEvent.organizer.designation }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button 
              v-if="selectedEvent && activeTab === 'available' && canRegister(selectedEvent)"
              type="button" 
              class="btn btn-primary"
              @click="registerForEvent(selectedEvent)"
              :disabled="registering === selectedEvent.id"
            >
              <i class="fas me-2" :class="registering === selectedEvent.id ? 'fa-spinner fa-spin' : 'fa-plus'"></i>
              {{ registering === selectedEvent.id ? 'Registering...' : 'Register Now' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Registration Success Modal -->
    <div class="modal fade" id="registrationSuccessModal" tabindex="-1" ref="registrationSuccessModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">
              <i class="fas fa-check-circle me-2"></i>
              Registration Successful!
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body text-center">
            <div class="success-icon">
              <i class="fas fa-check-circle text-success"></i>
            </div>
            <h4>You're all set!</h4>
            <p>Your registration for <strong>{{ registrationSuccess?.event_title }}</strong> has been confirmed.</p>
            <div class="registration-details">
              <p><strong>Registration ID:</strong> {{ registrationSuccess?.registration_id }}</p>
              <p><strong>Event Date:</strong> {{ registrationSuccess?.event_date }}</p>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" @click="downloadTicket(registrationSuccess)">
              <i class="fas fa-ticket-alt me-2"></i>
              Download Ticket
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { Modal } from 'bootstrap'

export default {
  name: 'MemberEvents',
  setup() {
    const authStore = useAuthStore()

    // Reactive data
    const loading = ref(false)
    const error = ref('')
    const activeTab = ref('registered')
    const searchQuery = ref('')
    const selectedStatus = ref('')
    const selectedType = ref('')
    const currentPage = ref(1)
    const itemsPerPage = 12
    const registering = ref(null)
    const selectedEvent = ref(null)
    const registrationSuccess = ref(null)

    // Events data
    const registeredEvents = ref([])
    const availableEvents = ref([])
    const eventHistory = ref([])

    // Modals
    const eventDetailsModal = ref(null)
    const registrationSuccessModal = ref(null)

    // Computed properties
    const eventStats = computed(() => ({
      registered: registeredEvents.value.filter(e => e.status === 'upcoming' || e.status === 'ongoing').length,
      attended: eventHistory.value.filter(e => e.attendance_status === 'attended').length,
      upcoming: registeredEvents.value.filter(e => e.status === 'upcoming').length
    }))

    const filteredRegisteredEvents = computed(() => {
      return filterEventsList(registeredEvents.value)
    })

    const filteredAvailableEvents = computed(() => {
      return filterEventsList(availableEvents.value)
    })

    const filteredEventHistory = computed(() => {
      return filterEventsList(eventHistory.value)
    })

    const totalPages = computed(() => {
      let totalItems = 0
      if (activeTab.value === 'registered') totalItems = filteredRegisteredEvents.value.length
      else if (activeTab.value === 'available') totalItems = filteredAvailableEvents.value.length
      else totalItems = filteredEventHistory.value.length
      
      return Math.ceil(totalItems / itemsPerPage)
    })

    const visiblePages = computed(() => {
      const pages = []
      const start = Math.max(1, currentPage.value - 2)
      const end = Math.min(totalPages.value, start + 4)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      return pages
    })

    // Methods
    const loadEvents = async () => {
      loading.value = true
      error.value = ''
      
      try {
        // Load registered events
        const registeredResponse = await fetch('https://bhrcdata.online/backend/api.php/member/events/registered', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (registeredResponse.ok) {
          const registeredData = await registeredResponse.json()
          registeredEvents.value = registeredData.events || []
        }

        // Load available events
        const availableResponse = await fetch('https://bhrcdata.online/backend/api.php/member/events/available', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (availableResponse.ok) {
          const availableData = await availableResponse.json()
          availableEvents.value = availableData.events || []
        }

        // Load event history
        const historyResponse = await fetch('https://bhrcdata.online/backend/api.php/member/events/history', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (historyResponse.ok) {
          const historyData = await historyResponse.json()
          eventHistory.value = historyData.events || []
        }

      } catch (err) {
        console.error('Error loading events:', err)
        error.value = 'Failed to load events. Please try again.'
      } finally {
        loading.value = false
      }
    }

    const filterEventsList = (events) => {
      let filtered = [...events]

      // Search filter
      if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(event => 
          event.title.toLowerCase().includes(query) ||
          event.description.toLowerCase().includes(query) ||
          event.location.toLowerCase().includes(query)
        )
      }

      // Status filter
      if (selectedStatus.value) {
        filtered = filtered.filter(event => event.status === selectedStatus.value)
      }

      // Type filter
      if (selectedType.value) {
        filtered = filtered.filter(event => event.type === selectedType.value)
      }

      return filtered
    }

    const filterEvents = () => {
      currentPage.value = 1
    }

    const resetFilters = () => {
      searchQuery.value = ''
      selectedStatus.value = ''
      selectedType.value = ''
      currentPage.value = 1
    }

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
      }
    }

    const viewEventDetails = (event) => {
      selectedEvent.value = event
      const modal = new Modal(eventDetailsModal.value)
      modal.show()
    }

    const registerForEvent = async (event) => {
      if (!canRegister(event)) return

      registering.value = event.id
      
      try {
        const response = await fetch('https://bhrcdata.online/backend/api.php/member/events/register', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            event_id: event.id
          })
        })

        if (response.ok) {
          const data = await response.json()
          
          // Update events lists
          registeredEvents.value.push({
            ...event,
            registration_id: data.registration_id,
            registration_date: new Date().toISOString()
          })
          
          availableEvents.value = availableEvents.value.filter(e => e.id !== event.id)
          
          // Show success modal
          registrationSuccess.value = {
            event_title: event.title,
            event_date: formatDate(event.date),
            registration_id: data.registration_id
          }
          
          // Close details modal if open
          const detailsModal = Modal.getInstance(eventDetailsModal.value)
          if (detailsModal) detailsModal.hide()
          
          // Show success modal
          const successModal = new Modal(registrationSuccessModal.value)
          successModal.show()
          
        } else {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Registration failed')
        }
      } catch (err) {
        console.error('Error registering for event:', err)
        alert(err.message || 'Failed to register for event')
      } finally {
        registering.value = null
      }
    }

    const cancelRegistration = async (event) => {
      if (!confirm(`Are you sure you want to cancel your registration for "${event.title}"?`)) return

      try {
        const response = await fetch(`/backend/api.php/member/events/cancel/${event.id}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })

        if (response.ok) {
          // Remove from registered events
          registeredEvents.value = registeredEvents.value.filter(e => e.id !== event.id)
          
          // Add back to available events if still open
          if (new Date(event.date) > new Date()) {
            availableEvents.value.push(event)
          }
          
          alert('Registration cancelled successfully')
        } else {
          throw new Error('Failed to cancel registration')
        }
      } catch (err) {
        console.error('Error cancelling registration:', err)
        alert('Failed to cancel registration')
      }
    }

    const downloadTicket = async (event) => {
      try {
        const response = await fetch(`/backend/api.php/member/events/ticket/${event.id}`, {
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          }
        })

        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = `${event.title}_ticket.pdf`
          a.click()
          window.URL.revokeObjectURL(url)
        } else {
          throw new Error('Failed to download ticket')
        }
      } catch (err) {
        console.error('Error downloading ticket:', err)
        alert('Failed to download ticket')
      }
    }

    const downloadCertificate = async (event) => {
      try {
        const response = await fetch(`/backend/api.php/member/events/certificate/${event.id}`, {
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          }
        })

        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = `${event.title}_certificate.pdf`
          a.click()
          window.URL.revokeObjectURL(url)
        } else {
          throw new Error('Failed to download certificate')
        }
      } catch (err) {
        console.error('Error downloading certificate:', err)
        alert('Failed to download certificate')
      }
    }

    const provideFeedback = (event) => {
      // Navigate to feedback page or open feedback modal
      // This would be implemented based on your feedback system
      alert('Feedback feature coming soon!')
    }

    const canRegister = (event) => {
      const eventDate = new Date(event.date)
      const now = new Date()
      const registrationDeadline = new Date(event.registration_deadline || event.date)
      
      return eventDate > now && 
             registrationDeadline > now && 
             event.registered_count < event.max_participants &&
             event.status === 'upcoming'
    }

    const canCancelRegistration = (event) => {
      const eventDate = new Date(event.date)
      const now = new Date()
      const cancelDeadline = new Date(eventDate.getTime() - (24 * 60 * 60 * 1000)) // 24 hours before
      
      return now < cancelDeadline && event.status === 'upcoming'
    }

    const getStatusIcon = (status) => {
      const icons = {
        upcoming: 'fa-clock',
        ongoing: 'fa-play-circle',
        completed: 'fa-check-circle',
        cancelled: 'fa-times-circle',
        attended: 'fa-check-circle',
        missed: 'fa-times-circle'
      }
      return icons[status] || 'fa-question-circle'
    }

    const formatDate = (dateString) => {
      const date = new Date(dateString)
      return date.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }

    // Lifecycle
    onMounted(() => {
      loadEvents()
    })

    return {
      // Data
      loading,
      error,
      activeTab,
      searchQuery,
      selectedStatus,
      selectedType,
      currentPage,
      registering,
      selectedEvent,
      registrationSuccess,
      registeredEvents,
      availableEvents,
      eventHistory,
      eventDetailsModal,
      registrationSuccessModal,
      
      // Computed
      eventStats,
      filteredRegisteredEvents,
      filteredAvailableEvents,
      filteredEventHistory,
      totalPages,
      visiblePages,
      
      // Methods
      loadEvents,
      filterEvents,
      resetFilters,
      changePage,
      viewEventDetails,
      registerForEvent,
      cancelRegistration,
      downloadTicket,
      downloadCertificate,
      provideFeedback,
      canRegister,
      canCancelRegistration,
      getStatusIcon,
      formatDate
    }
  }
}
</script>

<style scoped>
/* Events Header */
.events-header {
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

.header-stats {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.stat-card {
  background: rgba(255, 255, 255, 0.1);
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
  min-width: 80px;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  display: block;
}

.stat-label {
  font-size: 0.8rem;
  opacity: 0.9;
}

/* Events Content */
.events-content {
  padding: 3rem 0;
  background: #f8f9fa;
  min-height: calc(100vh - 200px);
}

/* Filters */
.events-filters {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
}

.search-box .input-group-text {
  background: #f8f9fa;
  border-right: none;
}

.search-box .form-control {
  border-left: none;
}

.filter-controls {
  display: flex;
  gap: 0.5rem;
}

.form-select {
  min-width: 150px;
}

/* Events Tabs */
.events-tabs {
  margin-bottom: 2rem;
}

.nav-tabs {
  border-bottom: 2px solid #e9ecef;
  background: white;
  border-radius: 12px 12px 0 0;
  padding: 0 1rem;
}

.nav-tabs .nav-link {
  border: none;
  background: transparent;
  color: #666;
  padding: 1rem 1.5rem;
  font-weight: 600;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
}

.nav-tabs .nav-link:hover {
  color: #dc3545;
  background: #f8f9fa;
}

.nav-tabs .nav-link.active {
  color: #dc3545;
  background: white;
  border-bottom: 3px solid #dc3545;
}

.nav-tabs .badge {
  font-size: 0.7rem;
}

/* Loading and Error States */
.loading-state,
.error-state {
  background: white;
  border-radius: 0 0 12px 12px;
  padding: 3rem;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 0 0 12px 12px;
}

.empty-illustration {
  font-size: 4rem;
  color: #dee2e6;
  margin-bottom: 1.5rem;
}

.empty-state h3 {
  color: #333;
  margin-bottom: 1rem;
}

.empty-state p {
  color: #666;
  margin-bottom: 2rem;
}

/* Events Grid */
.events-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 2rem;
  background: white;
  padding: 2rem;
  border-radius: 0 0 12px 12px;
}

.event-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: all 0.3s ease;
  border: 1px solid #e9ecef;
}

.event-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.event-card.registered {
  border-left: 4px solid #28a745;
}

.event-card.available {
  border-left: 4px solid #007bff;
}

/* Event Image */
.event-image {
  position: relative;
  height: 200px;
  overflow: hidden;
}

.event-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.event-status {
  position: absolute;
  top: 1rem;
  right: 1rem;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.event-status.upcoming {
  background: #007bff;
  color: white;
}

.event-status.ongoing {
  background: #ffc107;
  color: #333;
}

.event-status.completed {
  background: #28a745;
  color: white;
}

.event-status.cancelled {
  background: #dc3545;
  color: white;
}

.event-badge {
  position: absolute;
  top: 1rem;
  left: 1rem;
  background: #ffc107;
  color: #333;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Event Content */
.event-content {
  padding: 1.5rem;
}

.event-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.event-type {
  background: #e9ecef;
  color: #495057;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
}

.event-date {
  color: #666;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
}

.event-title {
  font-size: 1.25rem;
  font-weight: 700;
  margin-bottom: 0.75rem;
  color: #333;
  line-height: 1.3;
}

.event-description {
  color: #666;
  margin-bottom: 1rem;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.event-details {
  margin-bottom: 1rem;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  color: #666;
}

.detail-item i {
  width: 16px;
  color: #dc3545;
}

/* Registration Info */
.event-registration-info {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.registration-status {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.registration-id {
  font-size: 0.8rem;
  color: #666;
}

/* Event Pricing */
.event-pricing {
  text-align: center;
  margin-bottom: 1rem;
}

.price {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 0.25rem;
}

.currency {
  font-size: 1rem;
  color: #666;
}

.amount {
  font-size: 1.5rem;
  font-weight: 700;
  color: #dc3545;
}

/* Event Actions */
.event-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.event-actions .btn {
  flex: 1;
  min-width: 120px;
}

/* History Timeline */
.history-timeline {
  background: white;
  padding: 2rem;
  border-radius: 0 0 12px 12px;
}

.timeline-item {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 2rem;
  position: relative;
}

.timeline-item:not(:last-child)::after {
  content: '';
  position: absolute;
  left: 20px;
  top: 60px;
  bottom: -2rem;
  width: 2px;
  background: #e9ecef;
}

.timeline-marker {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1rem;
  flex-shrink: 0;
}

.timeline-marker.completed,
.timeline-marker.attended {
  background: #28a745;
}

.timeline-marker.cancelled,
.timeline-marker.missed {
  background: #dc3545;
}

.timeline-marker.upcoming {
  background: #007bff;
}

.timeline-content {
  flex: 1;
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 8px;
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.timeline-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.timeline-date {
  color: #666;
  font-size: 0.9rem;
}

.timeline-details {
  margin-bottom: 1rem;
}

.detail-row {
  display: flex;
  margin-bottom: 0.5rem;
}

.detail-label {
  font-weight: 600;
  min-width: 100px;
  color: #333;
}

.detail-value {
  color: #666;
}

.detail-value.completed,
.detail-value.attended {
  color: #28a745;
}

.detail-value.cancelled,
.detail-value.missed {
  color: #dc3545;
}

.timeline-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

/* Pagination */
.pagination-container {
  margin-top: 3rem;
}

.pagination .page-link {
  color: #dc3545;
  border-color: #dc3545;
}

.pagination .page-item.active .page-link {
  background-color: #dc3545;
  border-color: #dc3545;
}

.pagination .page-link:hover {
  color: #c82333;
  background-color: #f8f9fa;
  border-color: #c82333;
}

/* Modal Styles */
.event-details-content {
  max-height: 70vh;
  overflow-y: auto;
}

.event-image-large {
  margin-bottom: 1.5rem;
}

.event-image-large img {
  width: 100%;
  height: 300px;
  object-fit: cover;
  border-radius: 8px;
}

.event-info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.info-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.info-item i {
  color: #dc3545;
  font-size: 1.25rem;
  margin-top: 0.25rem;
}

.info-item strong {
  display: block;
  margin-bottom: 0.25rem;
  color: #333;
}

.info-item p {
  margin: 0;
  color: #666;
}

.event-description-full {
  margin-bottom: 1.5rem;
}

.event-description-full h6 {
  color: #333;
  margin-bottom: 0.75rem;
}

.event-organizer {
  border-top: 1px solid #e9ecef;
  padding-top: 1.5rem;
}

.organizer-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.organizer-info img {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  object-fit: cover;
}

.organizer-info strong {
  display: block;
  color: #333;
}

.organizer-info p {
  margin: 0;
  color: #666;
  font-size: 0.9rem;
}

/* Success Modal */
.success-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.registration-details {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  margin-top: 1rem;
}

/* Responsive Design */
@media (max-width: 992px) {
  .header-stats {
    justify-content: center;
    margin-top: 2rem;
  }
  
  .events-grid {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  }
  
  .filter-controls {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .form-select {
    min-width: auto;
  }
}

@media (max-width: 768px) {
  .events-header {
    padding: 2rem 0;
  }
  
  .page-title {
    font-size: 2rem;
  }
  
  .events-content {
    padding: 2rem 0;
  }
  
  .events-filters {
    padding: 1rem;
  }
  
  .events-filters .row {
    flex-direction: column;
    gap: 1rem;
  }
  
  .events-grid {
    grid-template-columns: 1fr;
    padding: 1rem;
  }
  
  .nav-tabs {
    flex-direction: column;
  }
  
  .nav-tabs .nav-link {
    text-align: left;
    padding: 0.75rem 1rem;
  }
  
  .event-actions {
    flex-direction: column;
  }
  
  .event-actions .btn {
    min-width: auto;
  }
  
  .timeline-item {
    flex-direction: column;
    gap: 1rem;
  }
  
  .timeline-item::after {
    display: none;
  }
  
  .timeline-header {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .event-info-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .header-stats {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .stat-card {
    min-width: auto;
  }
  
  .event-meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .timeline-actions {
    flex-direction: column;
  }
}
</style>