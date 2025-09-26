<template>
  <div class="home-page">
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="hero-background">
        <div class="hero-overlay"></div>
        <div class="container">
          <div class="row align-items-center min-vh-100">
            <div class="col-lg-6">
              <div class="hero-content">
                <h1 class="hero-title">
                  Protecting Human Rights
                  <span class="text-primary">Across India</span>
                </h1>
                <p class="hero-description">
                  Bharatiya Human Rights Council stands as a beacon of hope for those seeking justice. 
                  We are committed to protecting and promoting human rights, ensuring dignity and equality for all.
                </p>
                <div class="hero-actions">
                  <router-link to="/complaints" class="btn btn-primary btn-lg me-3">
                    <i class="fas fa-file-alt me-2"></i>
                    File a Complaint
                  </router-link>
                  <router-link to="/about" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-info-circle me-2"></i>
                    Learn More
                  </router-link>
                </div>
                <div class="hero-stats mt-4">
                  <div class="row">
                    <div class="col-4">
                      <div class="stat-item">
                        <div class="stat-number">{{ formatNumber(stats.totalComplaints) }}</div>
                        <div class="stat-label">Cases Resolved</div>
                      </div>
                    </div>
                    <div class="col-4">
                      <div class="stat-item">
                        <div class="stat-number">{{ formatNumber(stats.totalMembers) }}</div>
                        <div class="stat-label">Active Members</div>
                      </div>
                    </div>
                    <div class="col-4">
                      <div class="stat-item">
                        <div class="stat-number">{{ formatNumber(stats.totalEvents) }}</div>
                        <div class="stat-label">Events Conducted</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="hero-image">
                <img src="@/assets/images/hero-image.jpg" alt="Human Rights" class="img-fluid rounded-3 shadow-lg">
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Services Section -->
    <section class="services-section py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-8 mx-auto text-center mb-5">
            <h2 class="section-title">Our Services</h2>
            <p class="section-description">
              We provide comprehensive support to protect and promote human rights through various services
            </p>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-3 col-md-6 mb-4" v-for="service in services" :key="service.id">
            <div class="service-card h-100">
              <div class="service-icon">
                <i :class="service.icon"></i>
              </div>
              <div class="service-content">
                <h5 class="service-title">{{ service.title }}</h5>
                <p class="service-description">{{ service.description }}</p>
                <router-link :to="service.link" class="btn btn-outline-primary btn-sm">
                  Learn More
                  <i class="fas fa-arrow-right ms-1"></i>
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Statistics Section -->
    <section class="statistics-section bg-primary text-white py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-8 mx-auto text-center mb-5">
            <h2 class="section-title text-white">Our Impact</h2>
            <p class="section-description text-white-50">
              Numbers that reflect our commitment to human rights protection
            </p>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-3 col-md-6 mb-4" v-for="stat in detailedStats" :key="stat.key">
            <div class="stat-card text-center">
              <div class="stat-icon mb-3">
                <i :class="stat.icon"></i>
              </div>
              <div class="stat-number">{{ formatNumber(stat.value) }}</div>
              <div class="stat-label">{{ stat.label }}</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Recent Events Section -->
    <section class="events-section py-5">
      <div class="container">
        <div class="row align-items-center mb-5">
          <div class="col-lg-6">
            <h2 class="section-title">Recent Events</h2>
            <p class="section-description">
              Stay updated with our latest activities and events
            </p>
          </div>
          <div class="col-lg-6 text-lg-end">
            <router-link to="/events" class="btn btn-primary">
              View All Events
              <i class="fas fa-arrow-right ms-1"></i>
            </router-link>
          </div>
        </div>
        <div class="row">
          <div class="col-lg-4 col-md-6 mb-4" v-for="event in recentEvents" :key="event.id">
            <div class="event-card h-100">
              <div class="event-image">
                <img :src="event.image || '/images/default-event.jpg'" :alt="event.title" class="img-fluid">
                <div class="event-date">
                  <div class="date-day">{{ formatDate(event.event_date, 'dd') }}</div>
                  <div class="date-month">{{ formatDate(event.event_date, 'MMM') }}</div>
                </div>
              </div>
              <div class="event-content">
                <h5 class="event-title">{{ event.title }}</h5>
                <p class="event-description">{{ truncateText(event.description, 100) }}</p>
                <div class="event-meta">
                  <div class="event-location">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    {{ event.location }}
                  </div>
                  <div class="event-time">
                    <i class="fas fa-clock me-1"></i>
                    {{ formatDate(event.event_date, 'HH:mm') }}
                  </div>
                </div>
                <router-link :to="`/events/${event.id}`" class="btn btn-outline-primary btn-sm mt-2">
                  Read More
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section bg-light py-5">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-8">
            <h3 class="cta-title">Join Our Mission</h3>
            <p class="cta-description">
              Be part of the change. Help us protect and promote human rights across India.
            </p>
          </div>
          <div class="col-lg-4 text-lg-end">
            <div class="cta-actions">
              <router-link to="/register" class="btn btn-primary btn-lg me-2 mb-2">
                <i class="fas fa-user-plus me-2"></i>
                Become a Member
              </router-link>
              <router-link to="/donate" class="btn btn-success btn-lg mb-2">
                <i class="fas fa-heart me-2"></i>
                Donate Now
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Newsletter Section -->
    <section class="newsletter-section bg-dark text-white py-5">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <h4 class="newsletter-title">Stay Informed</h4>
            <p class="newsletter-description">
              Subscribe to our newsletter for updates on human rights issues and our activities.
            </p>
          </div>
          <div class="col-lg-6">
            <form @submit.prevent="subscribeNewsletter" class="newsletter-form">
              <div class="input-group">
                <input 
                  type="email" 
                  v-model="newsletterEmail"
                  class="form-control form-control-lg" 
                  placeholder="Enter your email address"
                  required
                >
                <button 
                  type="submit" 
                  class="btn btn-primary btn-lg"
                  :disabled="isSubscribing"
                >
                  <i v-if="isSubscribing" class="fas fa-spinner fa-spin"></i>
                  <span v-else>Subscribe</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import api from '@/utils/api'
import { dateUtils, numberUtils, stringUtils } from '@/utils/helpers'
import { useToast } from 'vue-toastification'

export default {
  name: 'Home',
  setup() {
    const toast = useToast()
    
    // Reactive state
    const stats = ref({
      totalComplaints: 0,
      totalMembers: 0,
      totalEvents: 0,
      totalDonations: 0
    })
    const recentEvents = ref([])
    const newsletterEmail = ref('')
    const isSubscribing = ref(false)
    const isLoading = ref(true)

    // Static data
    const services = ref([
      {
        id: 1,
        title: 'Legal Aid',
        description: 'Free legal assistance for human rights violations and social justice issues.',
        icon: 'fas fa-balance-scale',
        link: '/services/legal-aid'
      },
      {
        id: 2,
        title: 'Complaint Filing',
        description: 'Easy and secure platform to file complaints about human rights violations.',
        icon: 'fas fa-file-alt',
        link: '/complaints'
      },
      {
        id: 3,
        title: 'Awareness Programs',
        description: 'Educational programs to raise awareness about human rights and social issues.',
        icon: 'fas fa-graduation-cap',
        link: '/services/awareness'
      },
      {
        id: 4,
        title: 'Community Support',
        description: '24/7 support system for victims and their families during difficult times.',
        icon: 'fas fa-hands-helping',
        link: '/services/support'
      }
    ])

    // Computed properties
    const detailedStats = computed(() => [
      {
        key: 'complaints',
        value: stats.value.totalComplaints,
        label: 'Cases Resolved',
        icon: 'fas fa-check-circle'
      },
      {
        key: 'members',
        value: stats.value.totalMembers,
        label: 'Active Members',
        icon: 'fas fa-users'
      },
      {
        key: 'events',
        value: stats.value.totalEvents,
        label: 'Events Conducted',
        icon: 'fas fa-calendar-alt'
      },
      {
        key: 'donations',
        value: stats.value.totalDonations,
        label: 'Amount Raised',
        icon: 'fas fa-heart'
      }
    ])

    // Methods
    const fetchStats = async () => {
      try {
        const response = await api.get('/statistics')
        if (response.data.success) {
          stats.value = response.data.data
        }
      } catch (error) {
        console.error('Error fetching stats:', error)
      }
    }

    const fetchRecentEvents = async () => {
      try {
        const response = await api.get('/events', {
          params: { limit: 3 }
        })
        if (response.data.success) {
          recentEvents.value = response.data.data.events || response.data.data
        }
      } catch (error) {
        console.error('Error fetching events:', error)
      }
    }

    const subscribeNewsletter = async () => {
      if (!newsletterEmail.value) return

      try {
        isSubscribing.value = true
        
        const response = await api.post('/newsletter/subscribe', {
          email: newsletterEmail.value
        })

        if (response.data.success) {
          toast.success('Successfully subscribed to newsletter!')
          newsletterEmail.value = ''
        } else {
          throw new Error(response.data.message || 'Subscription failed')
        }
      } catch (error) {
        const message = error.response?.data?.message || error.message || 'Subscription failed'
        toast.error(message)
      } finally {
        isSubscribing.value = false
      }
    }

    const formatNumber = (num) => {
      return numberUtils.formatNumber(num)
    }

    const formatDate = (date, format) => {
      return dateUtils.format(date, format)
    }

    const truncateText = (text, length) => {
      return stringUtils.truncate(text, length)
    }

    // Lifecycle
    onMounted(async () => {
      try {
        await Promise.all([
          fetchStats(),
          fetchRecentEvents()
        ])
      } catch (error) {
        console.error('Error initializing home page:', error)
      } finally {
        isLoading.value = false
      }
    })

    return {
      // State
      stats,
      recentEvents,
      newsletterEmail,
      isSubscribing,
      isLoading,
      services,
      
      // Computed
      detailedStats,
      
      // Methods
      subscribeNewsletter,
      formatNumber,
      formatDate,
      truncateText
    }
  }
}
</script>

<style scoped>
/* Hero Section */
.hero-section {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
}

.hero-background {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  z-index: -2;
}

.hero-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.4);
  z-index: -1;
}

.hero-content {
  color: white;
  z-index: 1;
}

.hero-title {
  font-size: 3.5rem;
  font-weight: 700;
  line-height: 1.2;
  margin-bottom: 1.5rem;
}

.hero-description {
  font-size: 1.25rem;
  line-height: 1.6;
  margin-bottom: 2rem;
  opacity: 0.9;
}

.hero-actions {
  margin-bottom: 3rem;
}

.hero-stats .stat-item {
  text-align: center;
}

.hero-stats .stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: #fff;
}

.hero-stats .stat-label {
  font-size: 0.875rem;
  opacity: 0.8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.hero-image img {
  max-width: 100%;
  height: auto;
}

/* Section Styles */
.section-title {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 1rem;
  color: #333;
}

.section-description {
  font-size: 1.125rem;
  color: #6c757d;
  line-height: 1.6;
}

/* Services Section */
.service-card {
  background: white;
  border-radius: 1rem;
  padding: 2rem;
  text-align: center;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  border: 1px solid #f1f3f4;
}

.service-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.15);
}

.service-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #007bff, #0056b3);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
}

.service-icon i {
  font-size: 2rem;
  color: white;
}

.service-title {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 1rem;
  color: #333;
}

.service-description {
  color: #6c757d;
  line-height: 1.6;
  margin-bottom: 1.5rem;
}

/* Statistics Section */
.stat-card {
  padding: 1.5rem;
}

.stat-icon i {
  font-size: 3rem;
  color: rgba(255, 255, 255, 0.8);
}

.stat-number {
  font-size: 3rem;
  font-weight: 700;
  color: white;
  margin-bottom: 0.5rem;
}

.stat-label {
  font-size: 1rem;
  color: rgba(255, 255, 255, 0.8);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Events Section */
.event-card {
  background: white;
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.event-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.15);
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
  transition: transform 0.3s ease;
}

.event-card:hover .event-image img {
  transform: scale(1.05);
}

.event-date {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: white;
  border-radius: 0.5rem;
  padding: 0.5rem;
  text-align: center;
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
}

.date-day {
  font-size: 1.25rem;
  font-weight: 700;
  color: #007bff;
  line-height: 1;
}

.date-month {
  font-size: 0.75rem;
  color: #6c757d;
  text-transform: uppercase;
}

.event-content {
  padding: 1.5rem;
}

.event-title {
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: 0.75rem;
  color: #333;
}

.event-description {
  color: #6c757d;
  line-height: 1.6;
  margin-bottom: 1rem;
}

.event-meta {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
  color: #6c757d;
  margin-bottom: 1rem;
}

/* CTA Section */
.cta-title {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: #333;
}

.cta-description {
  font-size: 1.125rem;
  color: #6c757d;
  margin-bottom: 0;
}

.cta-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

/* Newsletter Section */
.newsletter-title {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.newsletter-description {
  color: rgba(255, 255, 255, 0.8);
  margin-bottom: 0;
}

.newsletter-form .form-control {
  background-color: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
}

.newsletter-form .form-control::placeholder {
  color: rgba(255, 255, 255, 0.6);
}

.newsletter-form .form-control:focus {
  background-color: rgba(255, 255, 255, 0.15);
  border-color: #007bff;
  color: white;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Responsive Design */
@media (max-width: 991.98px) {
  .hero-title {
    font-size: 2.5rem;
  }
  
  .section-title {
    font-size: 2rem;
  }
  
  .cta-actions {
    justify-content: center;
    margin-top: 2rem;
  }
}

@media (max-width: 767.98px) {
  .hero-title {
    font-size: 2rem;
  }
  
  .hero-description {
    font-size: 1rem;
  }
  
  .hero-actions {
    text-align: center;
  }
  
  .hero-actions .btn {
    display: block;
    width: 100%;
    margin-bottom: 1rem;
  }
  
  .service-card {
    margin-bottom: 2rem;
  }
  
  .event-meta {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .newsletter-form .input-group {
    flex-direction: column;
  }
  
  .newsletter-form .form-control {
    margin-bottom: 1rem;
  }
}
</style>