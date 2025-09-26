<template>
  <footer class="footer bg-dark text-white">
    <!-- Main Footer -->
    <div class="footer-main py-5">
      <div class="container">
        <div class="row">
          <!-- About Section -->
          <div class="col-lg-4 col-md-6 mb-4">
            <div class="footer-section">
              <h5 class="footer-title mb-3">
                <img src="/logo.png" alt="BHRC" class="footer-logo me-2" height="30">
                BHRC
              </h5>
              <p class="footer-text">
                Bharatiya Human Rights Council is dedicated to protecting and promoting human rights 
                across India. We work tirelessly to ensure justice, equality, and dignity for all.
              </p>
              <div class="social-links mt-3">
                <a href="#" class="social-link me-3" title="Facebook">
                  <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" class="social-link me-3" title="Twitter">
                  <i class="fab fa-twitter"></i>
                </a>
                <a href="#" class="social-link me-3" title="LinkedIn">
                  <i class="fab fa-linkedin-in"></i>
                </a>
                <a href="#" class="social-link me-3" title="YouTube">
                  <i class="fab fa-youtube"></i>
                </a>
                <a href="#" class="social-link" title="Instagram">
                  <i class="fab fa-instagram"></i>
                </a>
              </div>
            </div>
          </div>

          <!-- Quick Links -->
          <div class="col-lg-2 col-md-6 mb-4">
            <div class="footer-section">
              <h6 class="footer-title mb-3">Quick Links</h6>
              <ul class="footer-links">
                <li><router-link to="/">Home</router-link></li>
                <li><router-link to="/about">About Us</router-link></li>
                <li><router-link to="/mission">Our Mission</router-link></li>
                <li><router-link to="/team">Our Team</router-link></li>
                <li><router-link to="/events">Events</router-link></li>
                <li><router-link to="/gallery">Gallery</router-link></li>
              </ul>
            </div>
          </div>

          <!-- Services -->
          <div class="col-lg-2 col-md-6 mb-4">
            <div class="footer-section">
              <h6 class="footer-title mb-3">Services</h6>
              <ul class="footer-links">
                <li><router-link to="/services/legal-aid">Legal Aid</router-link></li>
                <li><router-link to="/services/complaint-filing">File Complaint</router-link></li>
                <li><router-link to="/services/awareness">Awareness Programs</router-link></li>
                <li><router-link to="/services/support">Community Support</router-link></li>
                <li><router-link to="/donate">Donate</router-link></li>
              </ul>
            </div>
          </div>

          <!-- Contact Info -->
          <div class="col-lg-4 col-md-6 mb-4">
            <div class="footer-section">
              <h6 class="footer-title mb-3">Contact Information</h6>
              <div class="contact-info">
                <div class="contact-item mb-2">
                  <i class="fas fa-map-marker-alt me-2"></i>
                  <span>123 Human Rights Street, New Delhi, India - 110001</span>
                </div>
                <div class="contact-item mb-2">
                  <i class="fas fa-phone me-2"></i>
                  <a href="tel:+911123456789">+91-11-2345-6789</a>
                </div>
                <div class="contact-item mb-2">
                  <i class="fas fa-envelope me-2"></i>
                  <a href="mailto:info@bhrcindia.org">info@bhrcindia.org</a>
                </div>
                <div class="contact-item mb-3">
                  <i class="fas fa-clock me-2"></i>
                  <span>Mon - Fri: 9:00 AM - 6:00 PM</span>
                </div>
              </div>

              <!-- Newsletter Signup -->
              <div class="newsletter-signup">
                <h6 class="mb-2">Subscribe to Newsletter</h6>
                <form @submit.prevent="subscribeNewsletter" class="d-flex">
                  <input 
                    type="email" 
                    v-model="newsletterEmail"
                    class="form-control form-control-sm me-2" 
                    placeholder="Your email"
                    required
                  >
                  <button 
                    type="submit" 
                    class="btn btn-primary btn-sm"
                    :disabled="isSubscribing"
                  >
                    <i v-if="isSubscribing" class="fas fa-spinner fa-spin"></i>
                    <i v-else class="fas fa-paper-plane"></i>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Emergency Helpline -->
    <div class="emergency-helpline bg-danger py-3">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-md-8">
            <div class="d-flex align-items-center">
              <i class="fas fa-phone-alt me-3 fa-lg"></i>
              <div>
                <strong>24/7 Emergency Helpline:</strong>
                <a href="tel:1800123456" class="text-white ms-2">1800-123-456</a>
                <span class="ms-3 small">For urgent human rights violations</span>
              </div>
            </div>
          </div>
          <div class="col-md-4 text-md-end mt-2 mt-md-0">
            <router-link to="/complaints" class="btn btn-light btn-sm">
              <i class="fas fa-file-alt me-1"></i>
              File Complaint
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Footer -->
    <div class="footer-bottom bg-darker py-3">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-md-6">
            <p class="mb-0 small">
              &copy; {{ currentYear }} Bharatiya Human Rights Council. All rights reserved.
            </p>
          </div>
          <div class="col-md-6 text-md-end">
            <div class="footer-bottom-links">
              <router-link to="/privacy-policy" class="small me-3">Privacy Policy</router-link>
              <router-link to="/terms-of-service" class="small me-3">Terms of Service</router-link>
              <router-link to="/sitemap" class="small">Sitemap</router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script>
import { ref, computed } from 'vue'
import api from '@/utils/api'
import { useToast } from 'vue-toastification'

export default {
  name: 'Footer',
  setup() {
    const toast = useToast()
    
    // Reactive state
    const newsletterEmail = ref('')
    const isSubscribing = ref(false)

    // Computed properties
    const currentYear = computed(() => new Date().getFullYear())

    // Methods
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

    return {
      // State
      newsletterEmail,
      isSubscribing,
      
      // Computed
      currentYear,
      
      // Methods
      subscribeNewsletter
    }
  }
}
</script>

<style scoped>
.footer {
  margin-top: auto;
}

.footer-logo {
  max-height: 30px;
  width: auto;
}

.footer-title {
  color: #fff;
  font-weight: 600;
  margin-bottom: 1rem;
}

.footer-text {
  color: #adb5bd;
  line-height: 1.6;
}

.footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-links li {
  margin-bottom: 0.5rem;
}

.footer-links a {
  color: #adb5bd;
  text-decoration: none;
  transition: color 0.3s ease;
}

.footer-links a:hover {
  color: #fff;
}

.social-links {
  display: flex;
  align-items: center;
}

.social-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background-color: rgba(255, 255, 255, 0.1);
  color: #fff;
  text-decoration: none;
  border-radius: 50%;
  transition: all 0.3s ease;
}

.social-link:hover {
  background-color: #007bff;
  color: #fff;
  transform: translateY(-2px);
}

.contact-info {
  color: #adb5bd;
}

.contact-item {
  display: flex;
  align-items: flex-start;
}

.contact-item i {
  color: #007bff;
  margin-top: 2px;
  flex-shrink: 0;
}

.contact-item a {
  color: #adb5bd;
  text-decoration: none;
  transition: color 0.3s ease;
}

.contact-item a:hover {
  color: #fff;
}

.newsletter-signup .form-control {
  background-color: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #fff;
}

.newsletter-signup .form-control::placeholder {
  color: #adb5bd;
}

.newsletter-signup .form-control:focus {
  background-color: rgba(255, 255, 255, 0.15);
  border-color: #007bff;
  color: #fff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.emergency-helpline {
  background: linear-gradient(135deg, #dc3545, #c82333) !important;
}

.emergency-helpline a {
  color: #fff;
  text-decoration: none;
  font-weight: 600;
}

.emergency-helpline a:hover {
  text-decoration: underline;
}

.bg-darker {
  background-color: #1a1a1a !important;
}

.footer-bottom-links a {
  color: #adb5bd;
  text-decoration: none;
  transition: color 0.3s ease;
}

.footer-bottom-links a:hover {
  color: #fff;
}

/* Responsive Design */
@media (max-width: 767.98px) {
  .footer-main {
    padding: 3rem 0;
  }
  
  .social-links {
    justify-content: flex-start;
  }
  
  .newsletter-signup form {
    flex-direction: column;
  }
  
  .newsletter-signup .form-control {
    margin-bottom: 0.5rem;
    margin-right: 0 !important;
  }
  
  .emergency-helpline .col-md-8,
  .emergency-helpline .col-md-4 {
    text-align: center;
  }
  
  .footer-bottom .col-md-6 {
    text-align: center;
    margin-bottom: 1rem;
  }
  
  .footer-bottom .col-md-6:last-child {
    margin-bottom: 0;
  }
}

@media (max-width: 575.98px) {
  .contact-item {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .contact-item i {
    margin-bottom: 0.25rem;
  }
  
  .emergency-helpline .d-flex {
    flex-direction: column;
    text-align: center;
  }
  
  .emergency-helpline i {
    margin-bottom: 0.5rem;
  }
}
</style>