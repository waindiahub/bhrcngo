<template>
  <div class="admin-events">
    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
      <p>Loading events...</p>
    </div>

    <!-- Events Header -->
    <div class="events-header">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h1>Event Management</h1>
            <p>Manage and schedule community events</p>
          </div>
          <div class="col-md-4">
            <div class="header-actions">
              <button 
                class="btn btn-outline-light" 
                @click="refreshData"
                :disabled="loading"
              >
                <i class="fas fa-sync-alt"></i>
                Refresh
              </button>
              <button 
                class="btn btn-outline-light" 
                @click="exportEvents"
                :disabled="loading"
              >
                <i class="fas fa-download"></i>
                Export
              </button>
              <button 
                class="btn btn-light" 
                @click="showAddEventModal = true"
              >
                <i class="fas fa-plus"></i>
                Add Event
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="container">
      <!-- Statistics Section -->
      <div class="stats-section">
        <div class="row">
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="stats-card total">
              <div class="card-body">
                <h6 class="card-title">Total Events</h6>
                <div class="stats-number">{{ stats.total || 0 }}</div>
                <div class="stats-change positive">
                  <i class="fas fa-arrow-up"></i>
                  +{{ stats.totalChange || 0 }}% from last month
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="stats-card upcoming">
              <div class="card-body">
                <h6 class="card-title">Upcoming Events</h6>
                <div class="stats-number">{{ stats.upcoming || 0 }}</div>
                <div class="stats-change positive">
                  <i class="fas fa-calendar-alt"></i>
                  Next 30 days
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="stats-card ongoing">
              <div class="card-body">
                <h6 class="card-title">Ongoing Events</h6>
                <div class="stats-number">{{ stats.ongoing || 0 }}</div>
                <div class="stats-change">
                  <i class="fas fa-play-circle"></i>
                  Currently active
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6 mb-3">
            <div class="stats-card completed">
              <div class="card-body">
                <h6 class="card-title">Completed Events</h6>
                <div class="stats-number">{{ stats.completed || 0 }}</div>
                <div class="stats-change positive">
                  <i class="fas fa-check-circle"></i>
                  This month
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters Section -->
      <div class="filters-section">
        <div class="filters-header">
          <h5 class="filters-title">Filter & Search Events</h5>
          <div class="view-toggle">
            <button 
              class="view-btn" 
              :class="{ active: viewMode === 'list' }"
              @click="viewMode = 'list'"
            >
              <i class="fas fa-list"></i>
              List
            </button>
            <button 
              class="view-btn" 
              :class="{ active: viewMode === 'grid' }"
              @click="viewMode = 'grid'"
            >
              <i class="fas fa-th-large"></i>
              Grid
            </button>
            <button 
              class="view-btn" 
              :class="{ active: viewMode === 'calendar' }"
              @click="viewMode = 'calendar'"
            >
              <i class="fas fa-calendar"></i>
              Calendar
            </button>
          </div>
        </div>

        <div class="row filters-row">
          <div class="col-md-3">
            <div class="filter-group">
              <label class="filter-label">Search Events</label>
              <input 
                type="text" 
                class="form-control" 
                placeholder="Search by title, description..."
                v-model="filters.search"
                @input="applyFilters"
              >
            </div>
          </div>
          <div class="col-md-2">
            <div class="filter-group">
              <label class="filter-label">Status</label>
              <select 
                class="form-control form-select" 
                v-model="filters.status"
                @change="applyFilters"
              >
                <option value="">All Status</option>
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="ongoing">Ongoing</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
          </div>
          <div class="col-md-2">
            <div class="filter-group">
              <label class="filter-label">Category</label>
              <select 
                class="form-control form-select" 
                v-model="filters.category"
                @change="applyFilters"
              >
                <option value="">All Categories</option>
                <option value="meeting">Meeting</option>
                <option value="workshop">Workshop</option>
                <option value="seminar">Seminar</option>
                <option value="conference">Conference</option>
                <option value="social">Social</option>
                <option value="fundraising">Fundraising</option>
                <option value="community">Community Service</option>
              </select>
            </div>
          </div>
          <div class="col-md-2">
            <div class="filter-group">
              <label class="filter-label">Date Range</label>
              <select 
                class="form-control form-select" 
                v-model="filters.dateRange"
                @change="applyFilters"
              >
                <option value="">All Dates</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="quarter">This Quarter</option>
                <option value="year">This Year</option>
              </select>
            </div>
          </div>
          <div class="col-md-2">
            <div class="filter-group">
              <label class="filter-label">Sort By</label>
              <select 
                class="form-control form-select" 
                v-model="filters.sortBy"
                @change="applyFilters"
              >
                <option value="date_desc">Date (Newest)</option>
                <option value="date_asc">Date (Oldest)</option>
                <option value="title_asc">Title (A-Z)</option>
                <option value="title_desc">Title (Z-A)</option>
                <option value="attendees_desc">Most Attendees</option>
                <option value="created_desc">Recently Created</option>
              </select>
            </div>
          </div>
          <div class="col-md-1">
            <div class="filter-group">
              <label class="filter-label">&nbsp;</label>
              <div class="filters-actions">
                <button 
                  class="btn btn-outline-secondary btn-sm" 
                  @click="clearFilters"
                >
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Events Section -->
      <div class="events-section">
        <div class="section-header">
          <div>
            <h5 class="section-title">Events</h5>
            <p class="section-subtitle">{{ filteredEvents.length }} events found</p>
          </div>
          <div v-if="selectedEvents.length > 0" class="bulk-actions">
            <span class="bulk-info">{{ selectedEvents.length }} selected</span>
            <button 
              class="btn btn-outline-primary btn-sm" 
              @click="bulkUpdateStatus"
            >
              Update Status
            </button>
            <button 
              class="btn btn-outline-warning btn-sm" 
              @click="bulkExport"
            >
              Export Selected
            </button>
            <button 
              class="btn btn-outline-danger btn-sm" 
              @click="bulkDelete"
            >
              Delete Selected
            </button>
          </div>
        </div>

        <!-- List View -->
        <div v-if="viewMode === 'list'" class="events-list">
          <div v-if="filteredEvents.length === 0" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-calendar-times"></i>
            </div>
            <h6 class="empty-title">No Events Found</h6>
            <p class="empty-text">No events match your current filters. Try adjusting your search criteria or create a new event.</p>
          </div>
          
          <div 
            v-for="event in paginatedEvents" 
            :key="event.id" 
            class="event-item"
            @click="viewEventDetails(event)"
          >
            <div class="event-header">
              <div class="event-info">
                <div class="event-checkbox">
                  <input 
                    type="checkbox" 
                    :value="event.id" 
                    v-model="selectedEvents"
                    @click.stop
                  >
                </div>
                <div class="event-details">
                  <h6 class="event-title">{{ event.title }}</h6>
                  <p class="event-id">ID: {{ event.id }}</p>
                </div>
              </div>
              <div class="event-badges">
                <span 
                  class="badge" 
                  :class="`badge-${getStatusColor(event.status)}`"
                >
                  {{ event.status }}
                </span>
                <span 
                  class="badge badge-secondary"
                >
                  {{ event.category }}
                </span>
              </div>
            </div>

            <div class="event-meta">
              <div class="meta-item">
                <span class="meta-label">Date & Time</span>
                <span class="meta-value">
                  {{ formatDateTime(event.start_date) }} - {{ formatDateTime(event.end_date) }}
                </span>
              </div>
              <div class="meta-item">
                <span class="meta-label">Location</span>
                <span class="meta-value">{{ event.location || 'TBD' }}</span>
              </div>
              <div class="meta-item">
                <span class="meta-label">Organizer</span>
                <span class="meta-value">{{ event.organizer_name }}</span>
              </div>
              <div class="meta-item">
                <span class="meta-label">Attendees</span>
                <span class="meta-value">{{ event.attendees_count || 0 }} / {{ event.max_attendees || '∞' }}</span>
              </div>
            </div>

            <div class="event-description">
              {{ event.description }}
            </div>

            <div class="event-actions">
              <button 
                class="btn btn-outline-primary btn-sm" 
                @click.stop="editEvent(event)"
              >
                <i class="fas fa-edit"></i>
                Edit
              </button>
              <button 
                class="btn btn-outline-info btn-sm" 
                @click.stop="viewAttendees(event)"
              >
                <i class="fas fa-users"></i>
                Attendees
              </button>
              <button 
                class="btn btn-outline-success btn-sm" 
                @click.stop="duplicateEvent(event)"
              >
                <i class="fas fa-copy"></i>
                Duplicate
              </button>
              <button 
                class="btn btn-outline-danger btn-sm" 
                @click.stop="deleteEvent(event)"
              >
                <i class="fas fa-trash"></i>
                Delete
              </button>
            </div>
          </div>
        </div>

        <!-- Grid View -->
        <div v-if="viewMode === 'grid'" class="events-grid">
          <div v-if="filteredEvents.length === 0" class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-calendar-times"></i>
            </div>
            <h6 class="empty-title">No Events Found</h6>
            <p class="empty-text">No events match your current filters. Try adjusting your search criteria or create a new event.</p>
          </div>
          
          <div 
            v-for="event in paginatedEvents" 
            :key="event.id" 
            class="event-card"
            @click="viewEventDetails(event)"
          >
            <div class="event-card-header">
              <div class="event-checkbox">
                <input 
                  type="checkbox" 
                  :value="event.id" 
                  v-model="selectedEvents"
                  @click.stop
                >
              </div>
              <div class="event-badges">
                <span 
                  class="badge" 
                  :class="`badge-${getStatusColor(event.status)}`"
                >
                  {{ event.status }}
                </span>
              </div>
            </div>
            
            <div class="event-card-body">
              <h6 class="event-title">{{ event.title }}</h6>
              <p class="event-category">{{ event.category }}</p>
              <p class="event-date">
                <i class="fas fa-calendar"></i>
                {{ formatDate(event.start_date) }}
              </p>
              <p class="event-location">
                <i class="fas fa-map-marker-alt"></i>
                {{ event.location || 'TBD' }}
              </p>
              <p class="event-attendees">
                <i class="fas fa-users"></i>
                {{ event.attendees_count || 0 }} attendees
              </p>
              <p class="event-description">{{ event.description }}</p>
            </div>
            
            <div class="event-card-footer">
              <button 
                class="btn btn-outline-primary btn-sm" 
                @click.stop="editEvent(event)"
              >
                <i class="fas fa-edit"></i>
              </button>
              <button 
                class="btn btn-outline-info btn-sm" 
                @click.stop="viewAttendees(event)"
              >
                <i class="fas fa-users"></i>
              </button>
              <button 
                class="btn btn-outline-danger btn-sm" 
                @click.stop="deleteEvent(event)"
              >
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Calendar View -->
        <div v-if="viewMode === 'calendar'" class="events-calendar">
          <div class="calendar-header">
            <button 
              class="btn btn-outline-secondary" 
              @click="previousMonth"
            >
              <i class="fas fa-chevron-left"></i>
            </button>
            <h5 class="calendar-title">{{ currentMonthYear }}</h5>
            <button 
              class="btn btn-outline-secondary" 
              @click="nextMonth"
            >
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
          
          <div class="calendar-grid">
            <div class="calendar-weekdays">
              <div class="weekday" v-for="day in weekdays" :key="day">{{ day }}</div>
            </div>
            <div class="calendar-days">
              <div 
                v-for="day in calendarDays" 
                :key="day.date" 
                class="calendar-day"
                :class="{ 
                  'other-month': !day.isCurrentMonth,
                  'today': day.isToday,
                  'has-events': day.events.length > 0
                }"
              >
                <div class="day-number">{{ day.day }}</div>
                <div class="day-events">
                  <div 
                    v-for="event in day.events.slice(0, 3)" 
                    :key="event.id"
                    class="calendar-event"
                    :class="`event-${getStatusColor(event.status)}`"
                    @click="viewEventDetails(event)"
                  >
                    {{ event.title }}
                  </div>
                  <div v-if="day.events.length > 3" class="more-events">
                    +{{ day.events.length - 3 }} more
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="viewMode !== 'calendar'" class="pagination-section">
          <div class="pagination-info">
            <span class="pagination-text">
              Showing {{ (currentPage - 1) * pageSize + 1 }} to 
              {{ Math.min(currentPage * pageSize, filteredEvents.length) }} of 
              {{ filteredEvents.length }} events
            </span>
            <div class="pagination">
              <button 
                class="page-btn" 
                @click="currentPage = 1"
                :disabled="currentPage === 1"
              >
                First
              </button>
              <button 
                class="page-btn" 
                @click="currentPage--"
                :disabled="currentPage === 1"
              >
                Previous
              </button>
              <button 
                v-for="page in visiblePages" 
                :key="page"
                class="page-btn" 
                :class="{ active: page === currentPage }"
                @click="currentPage = page"
              >
                {{ page }}
              </button>
              <button 
                class="page-btn" 
                @click="currentPage++"
                :disabled="currentPage === totalPages"
              >
                Next
              </button>
              <button 
                class="page-btn" 
                @click="currentPage = totalPages"
                :disabled="currentPage === totalPages"
              >
                Last
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bulk Actions Bar -->
    <div v-if="selectedEvents.length > 0" class="bulk-actions-bar">
      <div class="bulk-info">
        {{ selectedEvents.length }} event{{ selectedEvents.length > 1 ? 's' : '' }} selected
      </div>
      <div class="bulk-actions">
        <button 
          class="btn btn-outline-primary btn-sm" 
          @click="bulkUpdateStatus"
        >
          Update Status
        </button>
        <button 
          class="btn btn-outline-warning btn-sm" 
          @click="bulkExport"
        >
          Export
        </button>
        <button 
          class="btn btn-outline-danger btn-sm" 
          @click="bulkDelete"
        >
          Delete
        </button>
      </div>
    </div>

    <!-- Add/Edit Event Modal -->
    <div v-if="showAddEventModal || showEditEventModal" class="modal" @click="closeModals">
      <div class="modal-dialog" @click.stop>
        <div class="modal-header">
          <h5 class="modal-title">
            {{ showAddEventModal ? 'Add New Event' : 'Edit Event' }}
          </h5>
          <button class="btn-close" @click="closeModals">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveEvent">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Event Title *</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="eventForm.title"
                    required
                  >
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Category *</label>
                  <select class="form-control form-select" v-model="eventForm.category" required>
                    <option value="">Select Category</option>
                    <option value="meeting">Meeting</option>
                    <option value="workshop">Workshop</option>
                    <option value="seminar">Seminar</option>
                    <option value="conference">Conference</option>
                    <option value="social">Social</option>
                    <option value="fundraising">Fundraising</option>
                    <option value="community">Community Service</option>
                  </select>
                </div>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea 
                class="form-control" 
                rows="3"
                v-model="eventForm.description"
              ></textarea>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Start Date & Time *</label>
                  <input 
                    type="datetime-local" 
                    class="form-control" 
                    v-model="eventForm.start_date"
                    required
                  >
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">End Date & Time *</label>
                  <input 
                    type="datetime-local" 
                    class="form-control" 
                    v-model="eventForm.end_date"
                    required
                  >
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-8">
                <div class="form-group">
                  <label class="form-label">Location</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="eventForm.location"
                    placeholder="Event venue or online meeting link"
                  >
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label class="form-label">Max Attendees</label>
                  <input 
                    type="number" 
                    class="form-control" 
                    v-model="eventForm.max_attendees"
                    min="1"
                  >
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Status</label>
                  <select class="form-control form-select" v-model="eventForm.status">
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">Registration Required</label>
                  <select class="form-control form-select" v-model="eventForm.registration_required">
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                  </select>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" @click="closeModals">
            Cancel
          </button>
          <button class="btn btn-primary" @click="saveEvent">
            {{ showAddEventModal ? 'Create Event' : 'Update Event' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Event Details Modal -->
    <div v-if="showEventDetailsModal" class="modal" @click="closeModals">
      <div class="modal-dialog modal-lg" @click.stop>
        <div class="modal-header">
          <h5 class="modal-title">Event Details</h5>
          <button class="btn-close" @click="closeModals">&times;</button>
        </div>
        <div class="modal-body">
          <div v-if="selectedEvent">
            <div class="event-detail-header">
              <h4>{{ selectedEvent.title }}</h4>
              <div class="event-detail-badges">
                <span 
                  class="badge" 
                  :class="`badge-${getStatusColor(selectedEvent.status)}`"
                >
                  {{ selectedEvent.status }}
                </span>
                <span class="badge badge-secondary">{{ selectedEvent.category }}</span>
              </div>
            </div>
            
            <div class="event-detail-info">
              <div class="row">
                <div class="col-md-6">
                  <div class="info-item">
                    <strong>Start Date:</strong>
                    <span>{{ formatDateTime(selectedEvent.start_date) }}</span>
                  </div>
                  <div class="info-item">
                    <strong>End Date:</strong>
                    <span>{{ formatDateTime(selectedEvent.end_date) }}</span>
                  </div>
                  <div class="info-item">
                    <strong>Location:</strong>
                    <span>{{ selectedEvent.location || 'TBD' }}</span>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="info-item">
                    <strong>Organizer:</strong>
                    <span>{{ selectedEvent.organizer_name }}</span>
                  </div>
                  <div class="info-item">
                    <strong>Attendees:</strong>
                    <span>{{ selectedEvent.attendees_count || 0 }} / {{ selectedEvent.max_attendees || '∞' }}</span>
                  </div>
                  <div class="info-item">
                    <strong>Registration:</strong>
                    <span>{{ selectedEvent.registration_required ? 'Required' : 'Not Required' }}</span>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="event-detail-description">
              <h6>Description</h6>
              <p>{{ selectedEvent.description || 'No description provided.' }}</p>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" @click="closeModals">
            Close
          </button>
          <button class="btn btn-primary" @click="editEvent(selectedEvent)">
            Edit Event
          </button>
        </div>
      </div>
    </div>

    <!-- Success Toast -->
    <div v-if="showSuccessToast" class="success-toast">
      {{ successMessage }}
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'

export default {
  name: 'AdminEvents',
  setup() {
    const authStore = useAuthStore()
    
    // Reactive data
    const loading = ref(false)
    const events = ref([])
    const stats = ref({})
    const selectedEvents = ref([])
    const viewMode = ref('list')
    const currentPage = ref(1)
    const pageSize = ref(10)
    const currentDate = ref(new Date())
    
    // Modals
    const showAddEventModal = ref(false)
    const showEditEventModal = ref(false)
    const showEventDetailsModal = ref(false)
    const selectedEvent = ref(null)
    
    // Success toast
    const showSuccessToast = ref(false)
    const successMessage = ref('')
    
    // Filters
    const filters = reactive({
      search: '',
      status: '',
      category: '',
      dateRange: '',
      sortBy: 'date_desc'
    })
    
    // Event form
    const eventForm = reactive({
      title: '',
      description: '',
      category: '',
      start_date: '',
      end_date: '',
      location: '',
      max_attendees: '',
      status: 'draft',
      registration_required: '1'
    })
    
    // Calendar data
    const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
    
    // Computed properties
    const filteredEvents = computed(() => {
      let filtered = [...events.value]
      
      // Search filter
      if (filters.search) {
        const search = filters.search.toLowerCase()
        filtered = filtered.filter(event => 
          event.title.toLowerCase().includes(search) ||
          event.description.toLowerCase().includes(search) ||
          event.organizer_name.toLowerCase().includes(search)
        )
      }
      
      // Status filter
      if (filters.status) {
        filtered = filtered.filter(event => event.status === filters.status)
      }
      
      // Category filter
      if (filters.category) {
        filtered = filtered.filter(event => event.category === filters.category)
      }
      
      // Date range filter
      if (filters.dateRange) {
        const now = new Date()
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
        
        filtered = filtered.filter(event => {
          const eventDate = new Date(event.start_date)
          
          switch (filters.dateRange) {
            case 'today':
              return eventDate >= today && eventDate < new Date(today.getTime() + 24 * 60 * 60 * 1000)
            case 'week':
              const weekStart = new Date(today.getTime() - today.getDay() * 24 * 60 * 60 * 1000)
              const weekEnd = new Date(weekStart.getTime() + 7 * 24 * 60 * 60 * 1000)
              return eventDate >= weekStart && eventDate < weekEnd
            case 'month':
              return eventDate.getMonth() === now.getMonth() && eventDate.getFullYear() === now.getFullYear()
            case 'quarter':
              const quarter = Math.floor(now.getMonth() / 3)
              const quarterStart = quarter * 3
              return eventDate.getFullYear() === now.getFullYear() && 
                     Math.floor(eventDate.getMonth() / 3) === quarter
            case 'year':
              return eventDate.getFullYear() === now.getFullYear()
            default:
              return true
          }
        })
      }
      
      // Sort
      filtered.sort((a, b) => {
        switch (filters.sortBy) {
          case 'date_asc':
            return new Date(a.start_date) - new Date(b.start_date)
          case 'date_desc':
            return new Date(b.start_date) - new Date(a.start_date)
          case 'title_asc':
            return a.title.localeCompare(b.title)
          case 'title_desc':
            return b.title.localeCompare(a.title)
          case 'attendees_desc':
            return (b.attendees_count || 0) - (a.attendees_count || 0)
          case 'created_desc':
            return new Date(b.created_at) - new Date(a.created_at)
          default:
            return 0
        }
      })
      
      return filtered
    })
    
    const totalPages = computed(() => {
      return Math.ceil(filteredEvents.value.length / pageSize.value)
    })
    
    const paginatedEvents = computed(() => {
      const start = (currentPage.value - 1) * pageSize.value
      const end = start + pageSize.value
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
    
    const currentMonthYear = computed(() => {
      return currentDate.value.toLocaleDateString('en-US', { 
        month: 'long', 
        year: 'numeric' 
      })
    })
    
    const calendarDays = computed(() => {
      const year = currentDate.value.getFullYear()
      const month = currentDate.value.getMonth()
      const firstDay = new Date(year, month, 1)
      const lastDay = new Date(year, month + 1, 0)
      const startDate = new Date(firstDay)
      startDate.setDate(startDate.getDate() - firstDay.getDay())
      
      const days = []
      const today = new Date()
      
      for (let i = 0; i < 42; i++) {
        const date = new Date(startDate)
        date.setDate(startDate.getDate() + i)
        
        const dayEvents = events.value.filter(event => {
          const eventDate = new Date(event.start_date)
          return eventDate.toDateString() === date.toDateString()
        })
        
        days.push({
          date: date.toISOString().split('T')[0],
          day: date.getDate(),
          isCurrentMonth: date.getMonth() === month,
          isToday: date.toDateString() === today.toDateString(),
          events: dayEvents
        })
      }
      
      return days
    })
    
    // Methods
    const loadEvents = async () => {
      loading.value = true
      try {
        const response = await fetch('/api/admin/events', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          events.value = data.events || []
        }
      } catch (error) {
        console.error('Error loading events:', error)
      } finally {
        loading.value = false
      }
    }
    
    const loadStats = async () => {
      try {
        const response = await fetch('/api/admin/events/stats', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })
        
        if (response.ok) {
          const data = await response.json()
          stats.value = data
        }
      } catch (error) {
        console.error('Error loading stats:', error)
      }
    }
    
    const refreshData = async () => {
      await Promise.all([loadEvents(), loadStats()])
    }
    
    const applyFilters = () => {
      currentPage.value = 1
    }
    
    const clearFilters = () => {
      Object.assign(filters, {
        search: '',
        status: '',
        category: '',
        dateRange: '',
        sortBy: 'date_desc'
      })
      currentPage.value = 1
    }
    
    const getStatusColor = (status) => {
      const colors = {
        draft: 'secondary',
        published: 'primary',
        ongoing: 'info',
        completed: 'success',
        cancelled: 'danger'
      }
      return colors[status] || 'secondary'
    }
    
    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
    
    const formatDateTime = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }
    
    const viewEventDetails = (event) => {
      selectedEvent.value = event
      showEventDetailsModal.value = true
    }
    
    const editEvent = (event) => {
      selectedEvent.value = event
      Object.assign(eventForm, {
        title: event.title,
        description: event.description,
        category: event.category,
        start_date: event.start_date,
        end_date: event.end_date,
        location: event.location,
        max_attendees: event.max_attendees,
        status: event.status,
        registration_required: event.registration_required ? '1' : '0'
      })
      showEventDetailsModal.value = false
      showEditEventModal.value = true
    }
    
    const saveEvent = async () => {
      loading.value = true
      try {
        const url = showEditEventModal.value 
          ? `/api/admin/events/${selectedEvent.value.id}`
          : '/api/admin/events'
        
        const method = showEditEventModal.value ? 'PUT' : 'POST'
        
        const response = await fetch(url, {
          method,
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(eventForm)
        })
        
        if (response.ok) {
          showSuccess(showEditEventModal.value ? 'Event updated successfully!' : 'Event created successfully!')
          closeModals()
          await refreshData()
        }
      } catch (error) {
        console.error('Error saving event:', error)
      } finally {
        loading.value = false
      }
    }
    
    const deleteEvent = async (event) => {
      if (!confirm(`Are you sure you want to delete "${event.title}"?`)) return
      
      loading.value = true
      try {
        const response = await fetch(`/api/admin/events/${event.id}`, {
          method: 'DELETE',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })
        
        if (response.ok) {
          showSuccess('Event deleted successfully!')
          await refreshData()
        }
      } catch (error) {
        console.error('Error deleting event:', error)
      } finally {
        loading.value = false
      }
    }
    
    const duplicateEvent = async (event) => {
      Object.assign(eventForm, {
        title: `${event.title} (Copy)`,
        description: event.description,
        category: event.category,
        start_date: '',
        end_date: '',
        location: event.location,
        max_attendees: event.max_attendees,
        status: 'draft',
        registration_required: event.registration_required ? '1' : '0'
      })
      showAddEventModal.value = true
    }
    
    const viewAttendees = (event) => {
      // Navigate to attendees page or show attendees modal
      console.log('View attendees for event:', event.id)
    }
    
    const bulkUpdateStatus = async () => {
      const status = prompt('Enter new status (draft, published, ongoing, completed, cancelled):')
      if (!status) return
      
      loading.value = true
      try {
        const response = await fetch('/api/admin/events/bulk-action', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'update_status',
            event_ids: selectedEvents.value,
            status: status
          })
        })
        
        if (response.ok) {
          showSuccess('Events updated successfully!')
          selectedEvents.value = []
          await refreshData()
        }
      } catch (error) {
        console.error('Error updating events:', error)
      } finally {
        loading.value = false
      }
    }
    
    const bulkExport = async () => {
      try {
        const response = await fetch('/api/admin/events/export', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            event_ids: selectedEvents.value
          })
        })
        
        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = 'events.csv'
          a.click()
          window.URL.revokeObjectURL(url)
          showSuccess('Events exported successfully!')
        }
      } catch (error) {
        console.error('Error exporting events:', error)
      }
    }
    
    const bulkDelete = async () => {
      if (!confirm(`Are you sure you want to delete ${selectedEvents.value.length} events?`)) return
      
      loading.value = true
      try {
        const response = await fetch('/api/admin/events/bulk-action', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'delete',
            event_ids: selectedEvents.value
          })
        })
        
        if (response.ok) {
          showSuccess('Events deleted successfully!')
          selectedEvents.value = []
          await refreshData()
        }
      } catch (error) {
        console.error('Error deleting events:', error)
      } finally {
        loading.value = false
      }
    }
    
    const exportEvents = async () => {
      try {
        const response = await fetch('/api/admin/events/export', {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'Content-Type': 'application/json'
          }
        })
        
        if (response.ok) {
          const blob = await response.blob()
          const url = window.URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url
          a.download = 'all_events.csv'
          a.click()
          window.URL.revokeObjectURL(url)
          showSuccess('Events exported successfully!')
        }
      } catch (error) {
        console.error('Error exporting events:', error)
      }
    }
    
    const previousMonth = () => {
      currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1)
    }
    
    const nextMonth = () => {
      currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1)
    }
    
    const closeModals = () => {
      showAddEventModal.value = false
      showEditEventModal.value = false
      showEventDetailsModal.value = false
      selectedEvent.value = null
      
      // Reset form
      Object.assign(eventForm, {
        title: '',
        description: '',
        category: '',
        start_date: '',
        end_date: '',
        location: '',
        max_attendees: '',
        status: 'draft',
        registration_required: '1'
      })
    }
    
    const showSuccess = (message) => {
      successMessage.value = message
      showSuccessToast.value = true
      setTimeout(() => {
        showSuccessToast.value = false
      }, 3000)
    }
    
    // Watchers
    watch(() => filteredEvents.value.length, () => {
      if (currentPage.value > totalPages.value) {
        currentPage.value = Math.max(1, totalPages.value)
      }
    })
    
    // Lifecycle
    onMounted(() => {
      refreshData()
    })
    
    return {
      // Data
      loading,
      events,
      stats,
      selectedEvents,
      viewMode,
      currentPage,
      pageSize,
      currentDate,
      
      // Modals
      showAddEventModal,
      showEditEventModal,
      showEventDetailsModal,
      selectedEvent,
      
      // Toast
      showSuccessToast,
      successMessage,
      
      // Forms
      filters,
      eventForm,
      
      // Calendar
      weekdays,
      
      // Computed
      filteredEvents,
      totalPages,
      paginatedEvents,
      visiblePages,
      currentMonthYear,
      calendarDays,
      
      // Methods
      loadEvents,
      loadStats,
      refreshData,
      applyFilters,
      clearFilters,
      getStatusColor,
      formatDate,
      formatDateTime,
      viewEventDetails,
      editEvent,
      saveEvent,
      deleteEvent,
      duplicateEvent,
      viewAttendees,
      bulkUpdateStatus,
      bulkExport,
      bulkDelete,
      exportEvents,
      previousMonth,
      nextMonth,
      closeModals,
      showSuccess
    }
  }
}
</script>

<style scoped>
/* Admin Events Page Styles */
.admin-events {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* Loading Overlay */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.9);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  backdrop-filter: blur(4px);
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #e3f2fd;
  border-top: 4px solid #1976d2;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Header */
.events-header {
  background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
  color: white;
  padding: 2rem 0;
  margin-bottom: 2rem;
  box-shadow: 0 4px 20px rgba(25, 118, 210, 0.3);
}

.events-header h1 {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.events-header p {
  margin: 0.5rem 0 0 0;
  opacity: 0.9;
  font-size: 1.1rem;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.header-actions .btn {
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

.header-actions .btn:hover {
  background: rgba(255, 255, 255, 0.3);
  border-color: rgba(255, 255, 255, 0.5);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.header-actions .btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Statistics Cards */
.stats-section {
  margin-bottom: 2rem;
}

.stats-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  border: 1px solid #e0e0e0;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  height: 100%;
}

.stats-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: var(--card-color, #1976d2);
}

.stats-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.stats-card.total { --card-color: #1976d2; }
.stats-card.upcoming { --card-color: #f57c00; }
.stats-card.ongoing { --card-color: #0288d1; }
.stats-card.completed { --card-color: #388e3c; }

.stats-card .card-body {
  padding: 1.5rem;
}

.stats-card .card-title {
  font-size: 0.9rem;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 1rem;
}

.stats-card .stats-number {
  font-size: 2.5rem;
  font-weight: 700;
  color: #333;
  line-height: 1;
  margin-bottom: 0.5rem;
}

.stats-card .stats-change {
  font-size: 0.85rem;
}

.stats-card .stats-change.positive { color: #388e3c; }
.stats-card .stats-change.negative { color: #d32f2f; }

/* Filters Section */
.filters-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  border: 1px solid #e0e0e0;
}

.filters-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.filters-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.view-toggle {
  display: flex;
  background: #f5f5f5;
  border-radius: 8px;
  padding: 4px;
}

.view-btn {
  background: none;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  color: #666;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.view-btn.active {
  background: white;
  color: #1976d2;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.filters-row {
  margin-bottom: 1rem;
}

.filter-group {
  margin-bottom: 1rem;
}

.filter-label {
  font-size: 0.9rem;
  font-weight: 500;
  color: #555;
  margin-bottom: 0.5rem;
  display: block;
}

.form-control {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: all 0.2s ease;
  background: white;
  width: 100%;
}

.form-control:focus {
  outline: none;
  border-color: #1976d2;
  box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
}

.form-select {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 0.75rem center;
  background-repeat: no-repeat;
  background-size: 1rem;
  padding-right: 2.5rem;
}

.filters-actions {
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
  flex-wrap: wrap;
}

/* Buttons */
.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  border: 1px solid transparent;
}

.btn-primary {
  background: #1976d2;
  color: white;
}

.btn-primary:hover {
  background: #1565c0;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
}

.btn-outline-primary {
  background: transparent;
  color: #1976d2;
  border: 1px solid #1976d2;
}

.btn-outline-primary:hover {
  background: #1976d2;
  color: white;
}

.btn-outline-secondary {
  background: transparent;
  color: #666;
  border: 1px solid #ddd;
}

.btn-outline-secondary:hover {
  background: #f8f9fa;
  border-color: #999;
}

.btn-outline-info {
  background: transparent;
  color: #0288d1;
  border: 1px solid #0288d1;
}

.btn-outline-info:hover {
  background: #0288d1;
  color: white;
}

.btn-outline-success {
  background: transparent;
  color: #388e3c;
  border: 1px solid #388e3c;
}

.btn-outline-success:hover {
  background: #388e3c;
  color: white;
}

.btn-outline-warning {
  background: transparent;
  color: #f57c00;
  border: 1px solid #f57c00;
}

.btn-outline-warning:hover {
  background: #f57c00;
  color: white;
}

.btn-outline-danger {
  background: transparent;
  color: #d32f2f;
  border: 1px solid #d32f2f;
}

.btn-outline-danger:hover {
  background: #d32f2f;
  color: white;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.8rem;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Events Section */
.events-section {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  border: 1px solid #e0e0e0;
  overflow: hidden;
}

.section-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e0e0e0;
  background: #f8f9fa;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.section-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.section-subtitle {
  color: #666;
  font-size: 0.9rem;
  margin: 0.25rem 0 0 0;
}

.bulk-actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.bulk-info {
  font-weight: 500;
  color: #333;
  margin-right: 0.5rem;
}

/* List View */
.events-list {
  max-height: 600px;
  overflow-y: auto;
}

.event-item {
  padding: 1.5rem;
  border-bottom: 1px solid #f0f0f0;
  transition: all 0.2s ease;
  cursor: pointer;
}

.event-item:hover {
  background: #f8f9fa;
}

.event-item:last-child {
  border-bottom: none;
}

.event-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.event-info {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.event-checkbox input {
  margin: 0;
  transform: scale(1.2);
}

.event-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #333;
  margin: 0 0 0.25rem 0;
}

.event-id {
  font-size: 0.85rem;
  color: #666;
  font-family: 'Monaco', 'Menlo', monospace;
  margin: 0;
}

.event-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge-primary { background: #e3f2fd; color: #1976d2; }
.badge-warning { background: #fff3cd; color: #856404; }
.badge-info { background: #d1ecf1; color: #0c5460; }
.badge-success { background: #d4edda; color: #155724; }
.badge-secondary { background: #e2e3e5; color: #383d41; }
.badge-danger { background: #f8d7da; color: #721c24; }

.event-meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.meta-item {
  display: flex;
  flex-direction: column;
}

.meta-label {
  font-size: 0.8rem;
  color: #666;
  font-weight: 500;
  margin-bottom: 0.25rem;
}

.meta-value {
  font-size: 0.9rem;
  color: #333;
}

.event-description {
  color: #666;
  line-height: 1.5;
  margin-bottom: 1rem;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.event-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

/* Grid View */
.events-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
  padding: 1.5rem;
}

.event-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  padding: 1.5rem;
  transition: all 0.3s ease;
  cursor: pointer;
}

.event-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  border-color: #1976d2;
}

.event-card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.event-card-body {
  margin-bottom: 1rem;
}

.event-card-body .event-title {
  margin-bottom: 0.5rem;
}

.event-category {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 1rem;
  text-transform: capitalize;
}

.event-date,
.event-location,
.event-attendees {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
}

.event-card-body .event-description {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 0;
}

.event-card-footer {
  display: flex;
  gap: 0.5rem;
  justify-content: flex-end;
}

/* Calendar View */
.events-calendar {
  padding: 1.5rem;
}

.calendar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.calendar-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.calendar-grid {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
}

.calendar-weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  background: #f8f9fa;
}

.weekday {
  padding: 1rem;
  text-align: center;
  font-weight: 600;
  color: #666;
  border-right: 1px solid #e0e0e0;
}

.weekday:last-child {
  border-right: none;
}

.calendar-days {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
}

.calendar-day {
  min-height: 120px;
  padding: 0.5rem;
  border-right: 1px solid #e0e0e0;
  border-bottom: 1px solid #e0e0e0;
  background: white;
  position: relative;
}

.calendar-day:nth-child(7n) {
  border-right: none;
}

.calendar-day.other-month {
  background: #f8f9fa;
  color: #999;
}

.calendar-day.today {
  background: #e3f2fd;
}

.calendar-day.has-events {
  background: #f3e5f5;
}

.day-number {
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.day-events {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.calendar-event {
  background: #1976d2;
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.calendar-event:hover {
  background: #1565c0;
}

.calendar-event.event-primary { background: #1976d2; }
.calendar-event.event-warning { background: #f57c00; }
.calendar-event.event-info { background: #0288d1; }
.calendar-event.event-success { background: #388e3c; }
.calendar-event.event-danger { background: #d32f2f; }

.more-events {
  font-size: 0.7rem;
  color: #666;
  text-align: center;
  margin-top: 2px;
}

/* Bulk Actions Bar */
.bulk-actions-bar {
  position: fixed;
  bottom: 2rem;
  left: 50%;
  transform: translateX(-50%);
  background: white;
  border-radius: 12px;
  padding: 1rem 1.5rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  border: 1px solid #e0e0e0;
  display: flex;
  align-items: center;
  gap: 1rem;
  z-index: 1000;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateX(-50%) translateY(100%);
  }
  to {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
}

/* Pagination */
.pagination-section {
  padding: 1.5rem;
  border-top: 1px solid #e0e0e0;
  background: #f8f9fa;
}

.pagination-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.pagination-text {
  color: #666;
  font-size: 0.9rem;
}

.pagination {
  display: flex;
  gap: 0.25rem;
  align-items: center;
}

.page-btn {
  padding: 0.5rem 0.75rem;
  border: 1px solid #ddd;
  background: white;
  color: #666;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.9rem;
}

.page-btn:hover:not(:disabled) {
  background: #f8f9fa;
  border-color: #999;
}

.page-btn.active {
  background: #1976d2;
  color: white;
  border-color: #1976d2;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modals */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-dialog {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow: hidden;
  animation: slideIn 0.3s ease;
}

.modal-dialog.modal-lg {
  max-width: 800px;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-50px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e0e0e0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-title {
  font-size: 1.3rem;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #666;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.btn-close:hover {
  background: #f8f9fa;
  color: #333;
}

.modal-body {
  padding: 1.5rem;
  max-height: 60vh;
  overflow-y: auto;
}

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #e0e0e0;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  background: #f8f9fa;
}

/* Event Details Modal */
.event-detail-header {
  margin-bottom: 1.5rem;
}

.event-detail-header h4 {
  margin: 0 0 0.5rem 0;
  color: #333;
}

.event-detail-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.event-detail-info {
  margin-bottom: 1.5rem;
}

.info-item {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
  border-bottom: none;
}

.info-item strong {
  color: #333;
  font-weight: 600;
}

.event-detail-description {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e0e0e0;
}

.event-detail-description h6 {
  margin: 0 0 1rem 0;
  color: #333;
  font-weight: 600;
}

.event-detail-description p {
  color: #666;
  line-height: 1.6;
  margin: 0;
}

/* Form Styles */
.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  font-weight: 500;
  color: #333;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

textarea.form-control {
  resize: vertical;
  min-height: 100px;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 1.5rem;
  color: #666;
}

.empty-icon {
  font-size: 4rem;
  color: #ddd;
  margin-bottom: 1rem;
}

.empty-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #333;
  margin-bottom: 0.5rem;
}

.empty-text {
  color: #666;
  line-height: 1.5;
}

/* Success Toast */
.success-toast {
  position: fixed;
  top: 2rem;
  right: 2rem;
  background: #4caf50;
  color: white;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
  z-index: 9999;
  animation: slideInRight 0.3s ease;
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .events-header .row {
    flex-direction: column;
  }

  .header-actions {
    justify-content: center;
    width: 100%;
  }

  .stats-section .row > div {
    margin-bottom: 1rem;
  }

  .filters-header {
    flex-direction: column;
    align-items: stretch;
  }

  .view-toggle {
    justify-content: center;
  }

  .filters-actions {
    justify-content: stretch;
  }

  .filters-actions .btn {
    flex: 1;
    justify-content: center;
  }

  .events-grid {
    grid-template-columns: 1fr;
    padding: 1rem;
  }

  .event-header {
    flex-direction: column;
    align-items: stretch;
  }

  .event-info {
    flex-direction: row;
    align-items: center;
  }

  .event-meta {
    grid-template-columns: 1fr;
  }

  .bulk-actions-bar {
    left: 1rem;
    right: 1rem;
    transform: none;
    flex-direction: column;
    align-items: stretch;
  }

  .bulk-actions {
    justify-content: center;
  }

  .pagination-info {
    flex-direction: column;
    text-align: center;
  }

  .calendar-day {
    min-height: 80px;
  }

  .calendar-event {
    font-size: 0.7rem;
    padding: 1px 4px;
  }

  .modal-dialog {
    margin: 0;
    max-width: none;
    height: 100vh;
    border-radius: 0;
  }
}

@media (max-width: 480px) {
  .events-header h1 {
    font-size: 1.5rem;
  }

  .stats-card .stats-number {
    font-size: 2rem;
  }

  .event-actions {
    justify-content: stretch;
  }

  .event-actions .btn {
    flex: 1;
    justify-content: center;
  }

  .calendar-days {
    grid-template-columns: repeat(7, 1fr);
  }

  .calendar-day {
    min-height: 60px;
    padding: 0.25rem;
  }

  .day-number {
    font-size: 0.8rem;
  }

  .calendar-event {
    font-size: 0.6rem;
    padding: 1px 2px;
  }
}

/* Print Styles */
@media print {
  .admin-events {
    background: white !important;
  }

  .loading-overlay,
  .header-actions,
  .filters-section,
  .event-actions,
  .bulk-actions-bar,
  .pagination-section,
  .modal {
    display: none !important;
  }

  .events-header {
    background: white !important;
    color: black !important;
    box-shadow: none !important;
  }

  .stats-card,
  .events-section {
    box-shadow: none !important;
    border: 1px solid #ddd !important;
  }

  .event-item {
    break-inside: avoid;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .admin-events {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
    color: #e0e0e0;
  }

  .stats-card,
  .filters-section,
  .events-section,
  .events-calendar {
    background: #2d2d2d;
    border-color: #404040;
    color: #e0e0e0;
  }

  .form-control {
    background: #404040;
    border-color: #555;
    color: #e0e0e0;
  }

  .form-control:focus {
    border-color: #64b5f6;
    box-shadow: 0 0 0 3px rgba(100, 181, 246, 0.1);
  }

  .event-item:hover,
  .calendar-day {
    background: #404040;
  }

  .calendar-weekdays {
    background: #404040;
  }

  .modal-dialog {
    background: #2d2d2d;
    color: #e0e0e0;
  }

  .modal-footer,
  .section-header,
  .pagination-section {
    background: #404040;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Focus Styles */
.btn:focus,
.form-control:focus,
.page-btn:focus,
.view-btn:focus {
  outline: 2px solid #1976d2;
  outline-offset: 2px;
}

/* High Contrast Mode */
@media (prefers-contrast: high) {
  .stats-card,
  .filters-section,
  .events-section {
    border: 2px solid #000;
  }

  .btn {
    border: 2px solid currentColor;
  }

  .badge {
    border: 1px solid currentColor;
  }
}

/* Custom Scrollbar */
.events-list::-webkit-scrollbar,
.modal-body::-webkit-scrollbar {
  width: 8px;
}

.events-list::-webkit-scrollbar-track,
.modal-body::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.events-list::-webkit-scrollbar-thumb,
.modal-body::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

.events-list::-webkit-scrollbar-thumb:hover,
.modal-body::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>