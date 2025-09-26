<template>
  <div class="event-detail-page">
    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading event details...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-container">
      <div class="error-content">
        <i class="fas fa-exclamation-triangle"></i>
        <h2>Event Not Found</h2>
        <p>{{ error }}</p>
        <router-link to="/events" class="btn btn-primary">
          <i class="fas fa-arrow-left"></i>
          Back to Events
        </router-link>
      </div>
    </div>

    <!-- Event Content -->
    <div v-else-if="event" class="event-content">
      <!-- Hero Section -->
      <section class="event-hero">
        <div class="hero-image">
          <img :src="event.image || '/assets/images/event-placeholder.svg'" :alt="event.title" />
          <div class="hero-overlay">
            <div class="container">
              <div class="hero-content">
                <div class="event-category">
                  <span class="category-badge">{{ event.category }}</span>
                </div>
                <h1 class="event-title">{{ event.title }}</h1>
                <div class="event-meta">
                  <div class="meta-item">
                    <i class="fas fa-calendar"></i>
                    <span>{{ formatDate(event.date) }}</span>
                  </div>
                  <div class="meta-item">
                    <i class="fas fa-clock"></i>
                    <span>{{ event.time }}</span>
                  </div>
                  <div class="meta-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>{{ event.location }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Event Details -->
      <section class="event-details">
        <div class="container">
          <div class="details-grid">
            <!-- Main Content -->
            <div class="main-content">
              <div class="content-section">
                <h2>About This Event</h2>
                <div class="event-description" v-html="event.description"></div>
              </div>

              <div class="content-section" v-if="event.agenda && event.agenda.length">
                <h2>Event Agenda</h2>
                <div class="agenda-list">
                  <div 
                    v-for="(item, index) in event.agenda" 
                    :key="index"
                    class="agenda-item"
                  >
                    <div class="agenda-time">{{ item.time }}</div>
                    <div class="agenda-content">
                      <h3>{{ item.title }}</h3>
                      <p v-if="item.description">{{ item.description }}</p>
                      <span v-if="item.speaker" class="speaker">Speaker: {{ item.speaker }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="content-section" v-if="event.speakers && event.speakers.length">
                <h2>Speakers</h2>
                <div class="speakers-grid">
                  <div 
                    v-for="speaker in event.speakers" 
                    :key="speaker.id"
                    class="speaker-card"
                  >
                    <div class="speaker-image">
                      <img :src="speaker.image || '/assets/images/placeholder-avatar.svg'" :alt="speaker.name" />
                    </div>
                    <div class="speaker-info">
                      <h3>{{ speaker.name }}</h3>
                      <p class="speaker-title">{{ speaker.title }}</p>
                      <p class="speaker-bio">{{ speaker.bio }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="content-section" v-if="event.gallery && event.gallery.length">
                <h2>Event Gallery</h2>
                <div class="gallery-grid">
                  <div 
                    v-for="(image, index) in event.gallery" 
                    :key="index"
                    class="gallery-item"
                    @click="openGallery(index)"
                  >
                    <img :src="image.thumbnail || image.url" :alt="`Gallery image ${index + 1}`" />
                    <div class="gallery-overlay">
                      <i class="fas fa-search-plus"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
              <!-- Event Info Card -->
              <div class="info-card">
                <h3>Event Information</h3>
                <div class="info-list">
                  <div class="info-item">
                    <i class="fas fa-calendar"></i>
                    <div>
                      <strong>Date</strong>
                      <span>{{ formatDate(event.date) }}</span>
                    </div>
                  </div>
                  <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <div>
                      <strong>Time</strong>
                      <span>{{ event.time }}</span>
                    </div>
                  </div>
                  <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                      <strong>Location</strong>
                      <span>{{ event.location }}</span>
                    </div>
                  </div>
                  <div class="info-item" v-if="event.organizer">
                    <i class="fas fa-user"></i>
                    <div>
                      <strong>Organizer</strong>
                      <span>{{ event.organizer }}</span>
                    </div>
                  </div>
                  <div class="info-item" v-if="event.contact_email">
                    <i class="fas fa-envelope"></i>
                    <div>
                      <strong>Contact</strong>
                      <span>{{ event.contact_email }}</span>
                    </div>
                  </div>
                  <div class="info-item" v-if="event.contact_phone">
                    <i class="fas fa-phone"></i>
                    <div>
                      <strong>Phone</strong>
                      <span>{{ event.contact_phone }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Registration Card -->
              <div class="info-card" v-if="event.registration_required">
                <h3>Registration</h3>
                <div class="registration-info">
                  <div class="registration-status" :class="registrationStatusClass">
                    <i :class="registrationStatusIcon"></i>
                    <span>{{ registrationStatusText }}</span>
                  </div>
                  
                  <div v-if="event.registration_deadline" class="deadline">
                    <strong>Deadline:</strong> {{ formatDate(event.registration_deadline) }}
                  </div>
                  
                  <div v-if="event.max_participants" class="capacity">
                    <strong>Capacity:</strong> {{ event.registered_count || 0 }}/{{ event.max_participants }}
                  </div>
                  
                  <button 
                    v-if="canRegister" 
                    class="btn btn-primary btn-register"
                    @click="registerForEvent"
                    :disabled="registering"
                  >
                    <i class="fas fa-user-plus" v-if="!registering"></i>
                    <i class="fas fa-spinner fa-spin" v-else></i>
                    {{ registering ? 'Registering...' : 'Register Now' }}
                  </button>
                </div>
              </div>

              <!-- Share Card -->
              <div class="info-card">
                <h3>Share Event</h3>
                <div class="share-buttons">
                  <button class="share-btn facebook" @click="shareOnFacebook">
                    <i class="fab fa-facebook-f"></i>
                    Facebook
                  </button>
                  <button class="share-btn twitter" @click="shareOnTwitter">
                    <i class="fab fa-twitter"></i>
                    Twitter
                  </button>
                  <button class="share-btn whatsapp" @click="shareOnWhatsApp">
                    <i class="fab fa-whatsapp"></i>
                    WhatsApp
                  </button>
                  <button class="share-btn copy" @click="copyLink">
                    <i class="fas fa-link"></i>
                    Copy Link
                  </button>
                </div>
              </div>

              <!-- Related Events -->
              <div class="info-card" v-if="relatedEvents.length">
                <h3>Related Events</h3>
                <div class="related-events">
                  <div 
                    v-for="relatedEvent in relatedEvents" 
                    :key="relatedEvent.id"
                    class="related-event"
                  >
                    <router-link :to="`/events/${relatedEvent.id}`" class="related-link">
                      <div class="related-image">
                        <img :src="relatedEvent.image || '/assets/images/event-placeholder.svg'" :alt="relatedEvent.title" />
                      </div>
                      <div class="related-info">
                        <h4>{{ relatedEvent.title }}</h4>
                        <p class="related-date">{{ formatDate(relatedEvent.date) }}</p>
                      </div>
                    </router-link>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- Gallery Modal -->
    <div v-if="galleryModal.show" class="gallery-modal" @click="closeGallery">
      <div class="gallery-modal-content">
        <button class="gallery-close" @click="closeGallery">
          <i class="fas fa-times"></i>
        </button>
        <button class="gallery-prev" @click="prevImage" v-if="event.gallery.length > 1">
          <i class="fas fa-chevron-left"></i>
        </button>
        <button class="gallery-next" @click="nextImage" v-if="event.gallery.length > 1">
          <i class="fas fa-chevron-right"></i>
        </button>
        <img :src="event.gallery[galleryModal.currentIndex].url" :alt="`Gallery image ${galleryModal.currentIndex + 1}`" />
        <div class="gallery-counter">{{ galleryModal.currentIndex + 1 }} / {{ event.gallery.length }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// Reactive data
const loading = ref(true)
const error = ref(null)
const event = ref(null)
const relatedEvents = ref([])
const registering = ref(false)
const galleryModal = ref({
  show: false,
  currentIndex: 0
})

// Computed properties
const canRegister = computed(() => {
  if (!event.value?.registration_required) return false
  if (!authStore.isAuthenticated) return false
  
  const now = new Date()
  const eventDate = new Date(event.value.date)
  const deadline = event.value.registration_deadline ? new Date(event.value.registration_deadline) : eventDate
  
  return now < deadline && (!event.value.max_participants || (event.value.registered_count || 0) < event.value.max_participants)
})

const registrationStatusClass = computed(() => {
  if (!event.value?.registration_required) return ''
  
  const now = new Date()
  const eventDate = new Date(event.value.date)
  const deadline = event.value.registration_deadline ? new Date(event.value.registration_deadline) : eventDate
  
  if (now > eventDate) return 'status-closed'
  if (now > deadline) return 'status-closed'
  if (event.value.max_participants && (event.value.registered_count || 0) >= event.value.max_participants) return 'status-full'
  return 'status-open'
})

const registrationStatusIcon = computed(() => {
  const statusClass = registrationStatusClass.value
  switch (statusClass) {
    case 'status-open': return 'fas fa-check-circle'
    case 'status-full': return 'fas fa-users'
    case 'status-closed': return 'fas fa-times-circle'
    default: return 'fas fa-info-circle'
  }
})

const registrationStatusText = computed(() => {
  const statusClass = registrationStatusClass.value
  switch (statusClass) {
    case 'status-open': return 'Registration Open'
    case 'status-full': return 'Event Full'
    case 'status-closed': return 'Registration Closed'
    default: return 'Registration Not Required'
  }
})

// Methods
const fetchEventDetails = async () => {
  try {
    loading.value = true
    const eventId = route.params.id
    
    const response = await fetch(`/backend/api.php/events/${eventId}`)
    if (!response.ok) {
      throw new Error('Event not found')
    }
    
    const data = await response.json()
    event.value = data.event
    
    // Fetch related events
    await fetchRelatedEvents()
    
  } catch (err) {
    error.value = err.message
  } finally {
    loading.value = false
  }
}

const fetchRelatedEvents = async () => {
  try {
    const response = await fetch(`/backend/api.php/events?category=${event.value.category}&limit=3&exclude=${event.value.id}`)
    if (response.ok) {
      const data = await response.json()
      relatedEvents.value = data.events || []
    }
  } catch (err) {
    console.error('Failed to fetch related events:', err)
  }
}

const registerForEvent = async () => {
  if (!authStore.isAuthenticated) {
    router.push('/login')
    return
  }
  
  try {
    registering.value = true
    
    const response = await fetch(`/backend/api.php/events/${event.value.id}/register`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${authStore.token}`
      }
    })
    
    if (!response.ok) {
      throw new Error('Registration failed')
    }
    
    const data = await response.json()
    
    // Update event data
    event.value.registered_count = (event.value.registered_count || 0) + 1
    
    alert('Successfully registered for the event!')
    
  } catch (err) {
    alert('Registration failed: ' + err.message)
  } finally {
    registering.value = false
  }
}

const formatDate = (dateString) => {
  const options = { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric',
    weekday: 'long'
  }
  return new Date(dateString).toLocaleDateString('en-US', options)
}

const shareOnFacebook = () => {
  const url = encodeURIComponent(window.location.href)
  const title = encodeURIComponent(event.value.title)
  window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${title}`, '_blank')
}

const shareOnTwitter = () => {
  const url = encodeURIComponent(window.location.href)
  const text = encodeURIComponent(`${event.value.title} - ${event.value.description.substring(0, 100)}...`)
  window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank')
}

const shareOnWhatsApp = () => {
  const url = encodeURIComponent(window.location.href)
  const text = encodeURIComponent(`${event.value.title} - ${url}`)
  window.open(`https://wa.me/?text=${text}`, '_blank')
}

const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(window.location.href)
    alert('Link copied to clipboard!')
  } catch (err) {
    console.error('Failed to copy link:', err)
  }
}

const openGallery = (index) => {
  galleryModal.value.show = true
  galleryModal.value.currentIndex = index
}

const closeGallery = () => {
  galleryModal.value.show = false
}

const nextImage = () => {
  galleryModal.value.currentIndex = (galleryModal.value.currentIndex + 1) % event.value.gallery.length
}

const prevImage = () => {
  galleryModal.value.currentIndex = (galleryModal.value.currentIndex - 1 + event.value.gallery.length) % event.value.gallery.length
}

onMounted(() => {
  fetchEventDetails()
  document.title = 'Event Details - Bihar Human Rights Commission'
})
</script>

<style scoped>
.event-detail-page {
  min-height: 100vh;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

/* Loading & Error States */
.loading-container,
.error-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 50vh;
  text-align: center;
}

.loading-spinner i {
  font-size: 3rem;
  color: #2a5298;
  margin-bottom: 1rem;
}

.error-content i {
  font-size: 4rem;
  color: #dc3545;
  margin-bottom: 1rem;
}

.error-content h2 {
  color: #2c3e50;
  margin-bottom: 1rem;
}

/* Hero Section */
.event-hero {
  position: relative;
  height: 60vh;
  min-height: 400px;
  overflow: hidden;
}

.hero-image {
  width: 100%;
  height: 100%;
  position: relative;
}

.hero-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.hero-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.7));
  display: flex;
  align-items: flex-end;
  color: white;
}

.hero-content {
  padding: 2rem 0;
}

.category-badge {
  background: rgba(42, 82, 152, 0.9);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: 600;
  display: inline-block;
  margin-bottom: 1rem;
}

.event-title {
  font-size: 3rem;
  font-weight: bold;
  margin-bottom: 1rem;
  line-height: 1.2;
}

.event-meta {
  display: flex;
  gap: 2rem;
  flex-wrap: wrap;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.1rem;
}

.meta-item i {
  color: #ffd700;
}

/* Event Details */
.event-details {
  padding: 3rem 0;
  background: #f8f9fa;
}

.details-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 3rem;
}

.main-content {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.content-section {
  padding: 2rem;
  border-bottom: 1px solid #f0f0f0;
}

.content-section:last-child {
  border-bottom: none;
}

.content-section h2 {
  font-size: 1.8rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 1.5rem;
}

.event-description {
  color: #666;
  line-height: 1.8;
  font-size: 1.1rem;
}

/* Agenda */
.agenda-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.agenda-item {
  display: flex;
  gap: 1.5rem;
  padding: 1.5rem;
  background: #f8f9fa;
  border-radius: 8px;
  border-left: 4px solid #2a5298;
}

.agenda-time {
  font-weight: 600;
  color: #2a5298;
  min-width: 100px;
  font-size: 1.1rem;
}

.agenda-content h3 {
  font-size: 1.2rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.agenda-content p {
  color: #666;
  margin-bottom: 0.5rem;
}

.speaker {
  color: #2a5298;
  font-weight: 500;
  font-size: 0.9rem;
}

/* Speakers */
.speakers-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.speaker-card {
  display: flex;
  gap: 1rem;
  padding: 1.5rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.speaker-image {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
}

.speaker-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.speaker-info h3 {
  font-size: 1.2rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.speaker-title {
  color: #2a5298;
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.speaker-bio {
  color: #666;
  font-size: 0.9rem;
  line-height: 1.5;
}

/* Gallery */
.gallery-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.gallery-item {
  position: relative;
  aspect-ratio: 1;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.3s ease;
}

.gallery-item:hover {
  transform: scale(1.05);
}

.gallery-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.gallery-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
  color: white;
  font-size: 1.5rem;
}

.gallery-item:hover .gallery-overlay {
  opacity: 1;
}

/* Sidebar */
.sidebar {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.info-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.info-card h3 {
  font-size: 1.3rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 1.5rem;
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.info-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.info-item i {
  color: #2a5298;
  font-size: 1.1rem;
  margin-top: 0.2rem;
  width: 20px;
}

.info-item div {
  flex: 1;
}

.info-item strong {
  display: block;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.info-item span {
  color: #666;
}

/* Registration */
.registration-status {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  font-weight: 600;
}

.status-open {
  background: #d4edda;
  color: #155724;
}

.status-full {
  background: #fff3cd;
  color: #856404;
}

.status-closed {
  background: #f8d7da;
  color: #721c24;
}

.deadline,
.capacity {
  margin-bottom: 1rem;
  color: #666;
}

.btn-register {
  width: 100%;
  justify-content: center;
}

/* Share Buttons */
.share-buttons {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
}

.share-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
}

.share-btn.facebook {
  background: #1877f2;
  color: white;
}

.share-btn.twitter {
  background: #1da1f2;
  color: white;
}

.share-btn.whatsapp {
  background: #25d366;
  color: white;
}

.share-btn.copy {
  background: #6c757d;
  color: white;
}

.share-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

/* Related Events */
.related-events {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.related-event {
  border-radius: 8px;
  overflow: hidden;
  transition: transform 0.3s ease;
}

.related-event:hover {
  transform: translateY(-2px);
}

.related-link {
  display: flex;
  gap: 1rem;
  text-decoration: none;
  color: inherit;
}

.related-image {
  width: 80px;
  height: 60px;
  border-radius: 4px;
  overflow: hidden;
  flex-shrink: 0;
}

.related-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.related-info h4 {
  font-size: 1rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.25rem;
  line-height: 1.3;
}

.related-date {
  color: #666;
  font-size: 0.9rem;
}

/* Gallery Modal */
.gallery-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.gallery-modal-content {
  position: relative;
  max-width: 90vw;
  max-height: 90vh;
}

.gallery-modal-content img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.gallery-close,
.gallery-prev,
.gallery-next {
  position: absolute;
  background: rgba(0,0,0,0.5);
  color: white;
  border: none;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  cursor: pointer;
  font-size: 1.2rem;
  transition: background 0.3s ease;
}

.gallery-close {
  top: -60px;
  right: 0;
}

.gallery-prev {
  left: -60px;
  top: 50%;
  transform: translateY(-50%);
}

.gallery-next {
  right: -60px;
  top: 50%;
  transform: translateY(-50%);
}

.gallery-close:hover,
.gallery-prev:hover,
.gallery-next:hover {
  background: rgba(0,0,0,0.8);
}

.gallery-counter {
  position: absolute;
  bottom: -40px;
  left: 50%;
  transform: translateX(-50%);
  color: white;
  font-weight: 500;
}

/* Common Button Styles */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 25px;
  cursor: pointer;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
  justify-content: center;
}

.btn-primary {
  background: #2a5298;
  color: white;
}

.btn-primary:hover {
  background: #1e3c72;
  transform: translateY(-2px);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

/* Responsive Design */
@media (max-width: 768px) {
  .event-title {
    font-size: 2rem;
  }
  
  .event-meta {
    flex-direction: column;
    gap: 1rem;
  }
  
  .details-grid {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  
  .speakers-grid {
    grid-template-columns: 1fr;
  }
  
  .speaker-card {
    flex-direction: column;
    text-align: center;
  }
  
  .agenda-item {
    flex-direction: column;
    gap: 1rem;
  }
  
  .agenda-time {
    min-width: auto;
  }
  
  .share-buttons {
    grid-template-columns: 1fr;
  }
  
  .gallery-prev,
  .gallery-next {
    left: 10px;
    right: 10px;
    top: auto;
    bottom: 20px;
    transform: none;
  }
  
  .gallery-next {
    left: auto;
    right: 10px;
  }
}
</style>