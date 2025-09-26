<template>
  <div class="events-page">
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <div class="hero-content">
              <h1 class="hero-title">Events & Programs</h1>
              <p class="hero-subtitle">
                Join us in our mission to promote human rights awareness through workshops, seminars, and community programs.
              </p>
              <div class="hero-stats">
                <div class="row">
                  <div class="col-4">
                    <div class="stat-item">
                      <div class="stat-number">{{ stats.totalEvents }}+</div>
                      <div class="stat-label">Events Organized</div>
                    </div>
                  </div>
                  <div class="col-4">
                    <div class="stat-item">
                      <div class="stat-number">{{ stats.totalParticipants }}+</div>
                      <div class="stat-label">Participants</div>
                    </div>
                  </div>
                  <div class="col-4">
                    <div class="stat-item">
                      <div class="stat-number">{{ stats.upcomingEvents }}</div>
                      <div class="stat-label">Upcoming</div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="hero-cta">
                <button class="btn btn-primary btn-lg me-3" @click="scrollToEvents">
                  <i class="fas fa-calendar me-2"></i>
                  View Events
                </button>
                <router-link to="/contact" class="btn btn-outline-primary btn-lg">
                  <i class="fas fa-plus me-2"></i>
                  Organize Event
                </router-link>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="hero-image">
              <img src="/images/events-hero.jpg" alt="BHRC Events" class="img-fluid rounded-lg">
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Filters Section -->
    <section class="filters-section py-4 bg-light">
      <div class="container">
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
                  @input="handleSearch"
                >
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="filter-controls">
              <div class="row">
                <div class="col-md-4">
                  <select v-model="filters.category" @change="applyFilters" class="form-select">
                    <option value="">All Categories</option>
                    <option value="workshop">Workshops</option>
                    <option value="seminar">Seminars</option>
                    <option value="training">Training</option>
                    <option value="awareness">Awareness</option>
                    <option value="conference">Conference</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <select v-model="filters.status" @change="applyFilters" class="form-select">
                    <option value="">All Events</option>
                    <option value="upcoming">Upcoming</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="completed">Completed</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <select v-model="filters.location" @change="applyFilters" class="form-select">
                    <option value="">All Locations</option>
                    <option value="online">Online</option>
                    <option value="delhi">Delhi</option>
                    <option value="mumbai">Mumbai</option>
                    <option value="bangalore">Bangalore</option>
                    <option value="chennai">Chennai</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Events Section -->
    <section id="events" class="events-section py-5">
      <div class="container">
        <!-- Featured Event -->
        <div v-if="featuredEvent" class="featured-event mb-5">
          <div class="row align-items-center">
            <div class="col-lg-6">
              <div class="featured-content">
                <div class="event-badge">
                  <i class="fas fa-star me-1"></i>
                  Featured Event
                </div>
                <h2>{{ featuredEvent.title }}</h2>
                <p class="event-description">{{ featuredEvent.description }}</p>
                <div class="event-meta">
                  <div class="meta-item">
                    <i class="fas fa-calendar-alt me-2"></i>
                    {{ formatDate(featuredEvent.date) }}
                  </div>
                  <div class="meta-item">
                    <i class="fas fa-clock me-2"></i>
                    {{ featuredEvent.time }}
                  </div>
                  <div class="meta-item">
                    <i class="fas fa-map-marker-alt me-2"></i>
                    {{ featuredEvent.location }}
                  </div>
                  <div class="meta-item">
                    <i class="fas fa-users me-2"></i>
                    {{ featuredEvent.registered }}/{{ featuredEvent.capacity }} Registered
                  </div>
                </div>
                <div class="event-actions">
                  <button 
                    class="btn btn-primary btn-lg me-3"
                    @click="registerForEvent(featuredEvent)"
                    :disabled="featuredEvent.registered >= featuredEvent.capacity"
                  >
                    <i class="fas fa-user-plus me-2"></i>
                    {{ featuredEvent.registered >= featuredEvent.capacity ? 'Event Full' : 'Register Now' }}
                  </button>
                  <button class="btn btn-outline-primary btn-lg" @click="viewEventDetails(featuredEvent)">
                    <i class="fas fa-info-circle me-2"></i>
                    Learn More
                  </button>
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="featured-image">
                <img :src="featuredEvent.image" :alt="featuredEvent.title" class="img-fluid rounded-lg">
                <div class="event-status-badge" :class="getStatusClass(featuredEvent.status)">
                  {{ featuredEvent.status }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Events Grid -->
        <div class="events-grid">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>{{ getEventsTitle() }}</h3>
            <div class="view-toggle">
              <div class="btn-group" role="group">
                <button 
                  type="button" 
                  class="btn btn-outline-secondary"
                  :class="{ active: viewMode === 'grid' }"
                  @click="viewMode = 'grid'"
                >
                  <i class="fas fa-th"></i>
                </button>
                <button 
                  type="button" 
                  class="btn btn-outline-secondary"
                  :class="{ active: viewMode === 'list' }"
                  @click="viewMode = 'list'"
                >
                  <i class="fas fa-list"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Loading State -->
          <div v-if="loading" class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3">Loading events...</p>
          </div>

          <!-- No Events Found -->
          <div v-else-if="filteredEvents.length === 0" class="text-center py-5">
            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
            <h4>No Events Found</h4>
            <p class="text-muted">Try adjusting your search or filter criteria.</p>
            <button class="btn btn-primary" @click="clearFilters">
              <i class="fas fa-refresh me-2"></i>
              Clear Filters
            </button>
          </div>

          <!-- Events List -->
          <div v-else>
            <!-- Grid View -->
            <div v-if="viewMode === 'grid'" class="row">
              <div 
                class="col-lg-4 col-md-6 mb-4" 
                v-for="event in paginatedEvents" 
                :key="event.id"
              >
                <div class="event-card h-100">
                  <div class="event-image">
                    <img :src="event.image" :alt="event.title" class="img-fluid">
                    <div class="event-category">{{ event.category }}</div>
                    <div class="event-status" :class="getStatusClass(event.status)">
                      {{ event.status }}
                    </div>
                  </div>
                  <div class="event-content">
                    <h5>{{ event.title }}</h5>
                    <p class="event-excerpt">{{ truncateText(event.description, 100) }}</p>
                    <div class="event-meta">
                      <div class="meta-row">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <span>{{ formatDate(event.date) }}</span>
                      </div>
                      <div class="meta-row">
                        <i class="fas fa-clock me-2"></i>
                        <span>{{ event.time }}</span>
                      </div>
                      <div class="meta-row">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        <span>{{ event.location }}</span>
                      </div>
                      <div class="meta-row">
                        <i class="fas fa-users me-2"></i>
                        <span>{{ event.registered }}/{{ event.capacity }}</span>
                      </div>
                    </div>
                    <div class="event-actions">
                      <button 
                        class="btn btn-primary btn-sm me-2"
                        @click="registerForEvent(event)"
                        :disabled="event.registered >= event.capacity || event.status === 'completed'"
                      >
                        <i class="fas fa-user-plus me-1"></i>
                        {{ getRegisterButtonText(event) }}
                      </button>
                      <button class="btn btn-outline-secondary btn-sm" @click="viewEventDetails(event)">
                        <i class="fas fa-eye me-1"></i>
                        View
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- List View -->
            <div v-else class="events-list">
              <div 
                class="event-list-item" 
                v-for="event in paginatedEvents" 
                :key="event.id"
              >
                <div class="row align-items-center">
                  <div class="col-md-3">
                    <div class="event-image-small">
                      <img :src="event.image" :alt="event.title" class="img-fluid rounded">
                      <div class="event-status-small" :class="getStatusClass(event.status)">
                        {{ event.status }}
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="event-info">
                      <div class="event-category-small">{{ event.category }}</div>
                      <h5>{{ event.title }}</h5>
                      <p class="event-excerpt">{{ truncateText(event.description, 150) }}</p>
                      <div class="event-meta-inline">
                        <span class="meta-item">
                          <i class="fas fa-calendar-alt me-1"></i>
                          {{ formatDate(event.date) }}
                        </span>
                        <span class="meta-item">
                          <i class="fas fa-clock me-1"></i>
                          {{ event.time }}
                        </span>
                        <span class="meta-item">
                          <i class="fas fa-map-marker-alt me-1"></i>
                          {{ event.location }}
                        </span>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-3 text-end">
                    <div class="event-actions-list">
                      <div class="registration-info mb-2">
                        <small class="text-muted">
                          {{ event.registered }}/{{ event.capacity }} registered
                        </small>
                      </div>
                      <button 
                        class="btn btn-primary btn-sm d-block w-100 mb-2"
                        @click="registerForEvent(event)"
                        :disabled="event.registered >= event.capacity || event.status === 'completed'"
                      >
                        <i class="fas fa-user-plus me-1"></i>
                        {{ getRegisterButtonText(event) }}
                      </button>
                      <button class="btn btn-outline-secondary btn-sm d-block w-100" @click="viewEventDetails(event)">
                        <i class="fas fa-eye me-1"></i>
                        View Details
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="pagination-wrapper mt-5">
              <nav aria-label="Events pagination">
                <ul class="pagination justify-content-center">
                  <li class="page-item" :class="{ disabled: currentPage === 1 }">
                    <button class="page-link" @click="changePage(currentPage - 1)" :disabled="currentPage === 1">
                      <i class="fas fa-chevron-left"></i>
                    </button>
                  </li>
                  <li 
                    class="page-item" 
                    :class="{ active: page === currentPage }"
                    v-for="page in visiblePages" 
                    :key="page"
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
      </div>
    </section>

    <!-- Event Registration Modal -->
    <div class="modal fade" id="eventRegistrationModal" tabindex="-1" aria-labelledby="eventRegistrationModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="eventRegistrationModalLabel">
              Register for Event: {{ selectedEvent?.title }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div v-if="selectedEvent" class="event-summary mb-4">
              <div class="row">
                <div class="col-md-4">
                  <img :src="selectedEvent.image" :alt="selectedEvent.title" class="img-fluid rounded">
                </div>
                <div class="col-md-8">
                  <h6>{{ selectedEvent.title }}</h6>
                  <div class="event-details">
                    <div class="detail-item">
                      <i class="fas fa-calendar-alt me-2"></i>
                      {{ formatDate(selectedEvent.date) }}
                    </div>
                    <div class="detail-item">
                      <i class="fas fa-clock me-2"></i>
                      {{ selectedEvent.time }}
                    </div>
                    <div class="detail-item">
                      <i class="fas fa-map-marker-alt me-2"></i>
                      {{ selectedEvent.location }}
                    </div>
                    <div class="detail-item">
                      <i class="fas fa-users me-2"></i>
                      {{ selectedEvent.registered }}/{{ selectedEvent.capacity }} registered
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <form @submit.prevent="submitRegistration">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="regName" class="form-label">Full Name *</label>
                  <input 
                    type="text" 
                    id="regName"
                    v-model="registration.name"
                    class="form-control"
                    required
                  >
                </div>
                <div class="col-md-6 mb-3">
                  <label for="regEmail" class="form-label">Email *</label>
                  <input 
                    type="email" 
                    id="regEmail"
                    v-model="registration.email"
                    class="form-control"
                    required
                  >
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="regPhone" class="form-label">Phone Number *</label>
                  <input 
                    type="tel" 
                    id="regPhone"
                    v-model="registration.phone"
                    class="form-control"
                    required
                  >
                </div>
                <div class="col-md-6 mb-3">
                  <label for="regOrganization" class="form-label">Organization</label>
                  <input 
                    type="text" 
                    id="regOrganization"
                    v-model="registration.organization"
                    class="form-control"
                  >
                </div>
              </div>
              <div class="mb-3">
                <label for="regDesignation" class="form-label">Designation</label>
                <input 
                  type="text" 
                  id="regDesignation"
                  v-model="registration.designation"
                  class="form-control"
                >
              </div>
              <div class="mb-3">
                <label for="regExpectations" class="form-label">What do you expect from this event?</label>
                <textarea 
                  id="regExpectations"
                  v-model="registration.expectations"
                  class="form-control"
                  rows="3"
                  placeholder="Optional: Share your expectations..."
                ></textarea>
              </div>
              <div class="mb-3">
                <div class="form-check">
                  <input 
                    type="checkbox" 
                    id="regConsent"
                    v-model="registration.consent"
                    class="form-check-input"
                    required
                  >
                  <label for="regConsent" class="form-check-label">
                    I agree to receive event updates and communications from BHRC
                  </label>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button 
              type="button" 
              class="btn btn-primary"
              @click="submitRegistration"
              :disabled="isSubmitting"
            >
              <i v-if="isSubmitting" class="fas fa-spinner fa-spin me-2"></i>
              {{ isSubmitting ? 'Registering...' : 'Register Now' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/utils/api'
import { useToast } from 'vue-toastification'
import { dateUtils, stringUtils } from '@/utils/helpers'

export default {
  name: 'Events',
  setup() {
    const router = useRouter()
    const toast = useToast()

    // Reactive state
    const loading = ref(false)
    const events = ref([])
    const featuredEvent = ref(null)
    const selectedEvent = ref(null)
    const isSubmitting = ref(false)
    const viewMode = ref('grid')
    const currentPage = ref(1)
    const itemsPerPage = ref(9)
    const searchQuery = ref('')

    const stats = ref({
      totalEvents: 150,
      totalParticipants: 5000,
      upcomingEvents: 12
    })

    const filters = reactive({
      category: '',
      status: '',
      location: ''
    })

    const registration = reactive({
      name: '',
      email: '',
      phone: '',
      organization: '',
      designation: '',
      expectations: '',
      consent: false
    })

    // Computed properties
    const filteredEvents = computed(() => {
      let filtered = events.value

      // Search filter
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(event => 
          event.title.toLowerCase().includes(query) ||
          event.description.toLowerCase().includes(query) ||
          event.location.toLowerCase().includes(query)
        )
      }

      // Category filter
      if (filters.category) {
        filtered = filtered.filter(event => event.category === filters.category)
      }

      // Status filter
      if (filters.status) {
        filtered = filtered.filter(event => event.status === filters.status)
      }

      // Location filter
      if (filters.location) {
        filtered = filtered.filter(event => 
          event.location.toLowerCase().includes(filters.location.toLowerCase())
        )
      }

      return filtered
    })

    const totalPages = computed(() => {
      return Math.ceil(filteredEvents.value.length / itemsPerPage.value)
    })

    const paginatedEvents = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage.value
      const end = start + itemsPerPage.value
      return filteredEvents.value.slice(start, end)
    })

    const visiblePages = computed(() => {
      const pages = []
      const total = totalPages.value
      const current = currentPage.value
      
      if (total <= 7) {
        for (let i = 1; i <= total; i++) {
          pages.push(i)
        }
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) {
            pages.push(i)
          }
          pages.push('...')
          pages.push(total)
        } else if (current >= total - 3) {
          pages.push(1)
          pages.push('...')
          for (let i = total - 4; i <= total; i++) {
            pages.push(i)
          }
        } else {
          pages.push(1)
          pages.push('...')
          for (let i = current - 1; i <= current + 1; i++) {
            pages.push(i)
          }
          pages.push('...')
          pages.push(total)
        }
      }
      
      return pages
    })

    // Methods
    const loadEvents = async () => {
      loading.value = true
      try {
        const response = await api.get('/events')
        if (response.data.success) {
          events.value = response.data.events
          featuredEvent.value = response.data.featured_event
          
          // Update stats
          stats.value = {
            totalEvents: response.data.stats.total_events || 150,
            totalParticipants: response.data.stats.total_participants || 5000,
            upcomingEvents: response.data.stats.upcoming_events || 12
          }
        }
      } catch (error) {
        console.error('Error loading events:', error)
        // Load dummy data for demo
        loadDummyEvents()
      } finally {
        loading.value = false
      }
    }

    const loadDummyEvents = () => {
      events.value = [
        {
          id: 1,
          title: 'Human Rights Awareness Workshop',
          description: 'A comprehensive workshop on understanding and protecting human rights in the digital age.',
          category: 'workshop',
          status: 'upcoming',
          date: '2024-02-15',
          time: '10:00 AM - 4:00 PM',
          location: 'Delhi Community Center',
          capacity: 50,
          registered: 35,
          image: '/images/events/workshop1.jpg'
        },
        {
          id: 2,
          title: 'Women Rights Legal Seminar',
          description: 'Expert panel discussion on legal remedies available for women facing discrimination.',
          category: 'seminar',
          status: 'upcoming',
          date: '2024-02-20',
          time: '2:00 PM - 5:00 PM',
          location: 'Online',
          capacity: 100,
          registered: 78,
          image: '/images/events/seminar1.jpg'
        },
        {
          id: 3,
          title: 'Child Protection Training',
          description: 'Training program for educators and social workers on child protection mechanisms.',
          category: 'training',
          status: 'ongoing',
          date: '2024-02-10',
          time: '9:00 AM - 6:00 PM',
          location: 'Mumbai Training Center',
          capacity: 30,
          registered: 30,
          image: '/images/events/training1.jpg'
        }
      ]

      featuredEvent.value = events.value[0]
    }

    const handleSearch = () => {
      currentPage.value = 1
    }

    const applyFilters = () => {
      currentPage.value = 1
    }

    const clearFilters = () => {
      searchQuery.value = ''
      filters.category = ''
      filters.status = ''
      filters.location = ''
      currentPage.value = 1
    }

    const changePage = (page) => {
      if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
        scrollToEvents()
      }
    }

    const scrollToEvents = () => {
      const element = document.getElementById('events')
      if (element) {
        element.scrollIntoView({ behavior: 'smooth' })
      }
    }

    const getEventsTitle = () => {
      if (filters.status === 'upcoming') return 'Upcoming Events'
      if (filters.status === 'ongoing') return 'Ongoing Events'
      if (filters.status === 'completed') return 'Past Events'
      return 'All Events'
    }

    const getStatusClass = (status) => {
      const classes = {
        upcoming: 'status-upcoming',
        ongoing: 'status-ongoing',
        completed: 'status-completed'
      }
      return classes[status] || 'status-default'
    }

    const getRegisterButtonText = (event) => {
      if (event.status === 'completed') return 'Completed'
      if (event.registered >= event.capacity) return 'Full'
      return 'Register'
    }

    const registerForEvent = (event) => {
      if (event.status === 'completed' || event.registered >= event.capacity) {
        return
      }

      selectedEvent.value = event
      
      // Reset form
      Object.assign(registration, {
        name: '',
        email: '',
        phone: '',
        organization: '',
        designation: '',
        expectations: '',
        consent: false
      })

      // Show modal
      const modal = new bootstrap.Modal(document.getElementById('eventRegistrationModal'))
      modal.show()
    }

    const viewEventDetails = (event) => {
      router.push(`/events/${event.id}`)
    }

    const submitRegistration = async () => {
      if (!registration.consent) {
        toast.error('Please provide consent to proceed')
        return
      }

      isSubmitting.value = true

      try {
        const registrationData = {
          event_id: selectedEvent.value.id,
          name: registration.name,
          email: registration.email,
          phone: registration.phone,
          organization: registration.organization,
          designation: registration.designation,
          expectations: registration.expectations
        }

        const response = await api.post('/events/register', registrationData)

        if (response.data.success) {
          toast.success('Registration successful! You will receive a confirmation email shortly.')
          
          // Update event registration count
          const eventIndex = events.value.findIndex(e => e.id === selectedEvent.value.id)
          if (eventIndex !== -1) {
            events.value[eventIndex].registered++
          }
          
          // Hide modal
          const modal = bootstrap.Modal.getInstance(document.getElementById('eventRegistrationModal'))
          modal.hide()
          
          // Reset form
          Object.assign(registration, {
            name: '',
            email: '',
            phone: '',
            organization: '',
            designation: '',
            expectations: '',
            consent: false
          })
        }
      } catch (error) {
        console.error('Error submitting registration:', error)
        if (error.response?.data?.message) {
          toast.error(error.response.data.message)
        } else {
          toast.error('Failed to register. Please try again.')
        }
      } finally {
        isSubmitting.value = false
      }
    }

    const formatDate = (date) => {
      return dateUtils.formatDate(date, 'MMM DD, YYYY')
    }

    const truncateText = (text, length) => {
      return stringUtils.truncate(text, length)
    }

    // Watch for filter changes
    watch([() => filters.category, () => filters.status, () => filters.location], () => {
      currentPage.value = 1
    })

    // Lifecycle
    onMounted(() => {
      loadEvents()
    })

    return {
      // State
      loading,
      events,
      featuredEvent,
      selectedEvent,
      isSubmitting,
      viewMode,
      currentPage,
      searchQuery,
      stats,
      filters,
      registration,
      
      // Computed
      filteredEvents,
      totalPages,
      paginatedEvents,
      visiblePages,
      
      // Methods
      handleSearch,
      applyFilters,
      clearFilters,
      changePage,
      scrollToEvents,
      getEventsTitle,
      getStatusClass,
      getRegisterButtonText,
      registerForEvent,
      viewEventDetails,
      submitRegistration,
      formatDate,
      truncateText
    }
  }
}
</script>

<style scoped>
.events-page {
  padding-top: 0;
}

/* Hero Section */
.hero-section {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 4rem 0;
}

.hero-title {
  font-size: 3.5rem;
  font-weight: 700;
  color: #333;
  margin-bottom: 1rem;
}

.hero-subtitle {
  font-size: 1.25rem;
  color: #6c757d;
  margin-bottom: 2rem;
}

.hero-stats {
  margin-bottom: 2rem;
}

.stat-item {
  text-align: center;
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: #007bff;
  display: block;
}

.stat-label {
  font-size: 0.9rem;
  color: #6c757d;
  text-transform: uppercase;
}

.hero-cta .btn {
  padding: 0.75rem 2rem;
  font-weight: 600;
}

/* Filters Section */
.filters-section {
  border-bottom: 1px solid #dee2e6;
}

.search-box .input-group-text {
  background: white;
  border-right: none;
}

.search-box .form-control {
  border-left: none;
}

.filter-controls .form-select {
  font-size: 0.9rem;
}

/* Featured Event */
.featured-event {
  background: linear-gradient(135deg, #007bff, #0056b3);
  border-radius: 1rem;
  padding: 3rem;
  color: white;
  position: relative;
  overflow: hidden;
}

.featured-event::before {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 200px;
  height: 200px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  transform: translate(50%, -50%);
}

.event-badge {
  display: inline-block;
  background: rgba(255, 255, 255, 0.2);
  padding: 0.5rem 1rem;
  border-radius: 2rem;
  font-size: 0.9rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.featured-content h2 {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 1rem;
}

.event-description {
  font-size: 1.1rem;
  margin-bottom: 2rem;
  opacity: 0.9;
}

.event-meta {
  margin-bottom: 2rem;
}

.meta-item {
  display: flex;
  align-items: center;
  margin-bottom: 0.75rem;
  font-size: 1rem;
}

.meta-item i {
  width: 20px;
  opacity: 0.8;
}

.event-actions .btn {
  padding: 0.75rem 2rem;
  font-weight: 600;
}

.featured-image {
  position: relative;
}

.event-status-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  padding: 0.5rem 1rem;
  border-radius: 2rem;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
}

/* Event Cards */
.event-card {
  background: white;
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.event-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

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

.event-category {
  position: absolute;
  top: 1rem;
  left: 1rem;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.8rem;
  text-transform: uppercase;
}

.event-status {
  position: absolute;
  top: 1rem;
  right: 1rem;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
}

.event-content {
  padding: 1.5rem;
}

.event-content h5 {
  color: #333;
  font-weight: 600;
  margin-bottom: 1rem;
}

.event-excerpt {
  color: #6c757d;
  font-size: 0.9rem;
  margin-bottom: 1rem;
}

.event-meta .meta-row {
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
  font-size: 0.85rem;
  color: #6c757d;
}

.event-meta .meta-row i {
  width: 16px;
  color: #007bff;
}

.event-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
}

/* List View */
.event-list-item {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  margin-bottom: 1rem;
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.event-list-item:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.event-image-small {
  position: relative;
  height: 120px;
  border-radius: 0.5rem;
  overflow: hidden;
}

.event-image-small img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.event-status-small {
  position: absolute;
  bottom: 0.5rem;
  right: 0.5rem;
  padding: 0.25rem 0.5rem;
  border-radius: 1rem;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
}

.event-category-small {
  display: inline-block;
  background: #f8f9fa;
  color: #007bff;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  margin-bottom: 0.5rem;
}

.event-meta-inline {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

.event-meta-inline .meta-item {
  font-size: 0.85rem;
  color: #6c757d;
}

.event-meta-inline .meta-item i {
  color: #007bff;
}

.event-actions-list .registration-info {
  text-align: center;
}

/* Status Classes */
.status-upcoming {
  background: #28a745;
  color: white;
}

.status-ongoing {
  background: #ffc107;
  color: #212529;
}

.status-completed {
  background: #6c757d;
  color: white;
}

/* View Toggle */
.view-toggle .btn-group .btn {
  padding: 0.5rem 0.75rem;
}

.view-toggle .btn.active {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

/* Pagination */
.pagination-wrapper {
  display: flex;
  justify-content: center;
}

.pagination .page-link {
  color: #007bff;
  border-color: #dee2e6;
}

.pagination .page-item.active .page-link {
  background: #007bff;
  border-color: #007bff;
}

.pagination .page-link:hover {
  background: #f8f9fa;
}

/* Modal Styles */
.modal-header {
  background: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}

.modal-title {
  color: #333;
  font-weight: 600;
}

.event-summary {
  background: #f8f9fa;
  border-radius: 0.5rem;
  padding: 1rem;
}

.event-summary h6 {
  color: #333;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.event-details .detail-item {
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  color: #6c757d;
}

.event-details .detail-item i {
  width: 20px;
  color: #007bff;
}

/* Responsive Design */
@media (max-width: 991.98px) {
  .hero-title {
    font-size: 2.5rem;
  }
  
  .featured-event {
    padding: 2rem;
  }
  
  .featured-content h2 {
    font-size: 2rem;
  }
  
  .hero-cta .btn {
    display: block;
    width: 100%;
    margin-bottom: 0.5rem;
  }
  
  .filter-controls .row > div {
    margin-bottom: 0.5rem;
  }
}

@media (max-width: 767.98px) {
  .hero-title {
    font-size: 2rem;
  }
  
  .featured-content h2 {
    font-size: 1.75rem;
  }
  
  .event-actions {
    flex-direction: column;
  }
  
  .event-actions .btn {
    width: 100%;
    margin-bottom: 0.5rem;
  }
  
  .event-list-item .row {
    text-align: center;
  }
  
  .event-list-item .col-md-3,
  .event-list-item .col-md-6,
  .event-list-item .col-md-3 {
    margin-bottom: 1rem;
  }
}

/* Animations */
.events-page {
  animation: fadeIn 0.6s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

/* Loading State */
.spinner-border {
  width: 3rem;
  height: 3rem;
}

/* Utility Classes */
.rounded-lg {
  border-radius: 1rem !important;
}

/* Button Hover Effects */
.btn {
  transition: all 0.3s ease;
}

.btn:hover {
  transform: translateY(-1px);
}

.btn-primary:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 123, 255, 0.3);
}

/* Form Styles */
.form-control:focus,
.form-select:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Loading State */
.btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
  transform: none !important;
}
</style>