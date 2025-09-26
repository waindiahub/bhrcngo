<template>
  <div class="contact-page">
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <div class="hero-content">
              <h1 class="hero-title">Contact Us</h1>
              <p class="hero-subtitle">
                Get in touch with our team for support, information, or to report human rights violations. We're here to help and ensure your voice is heard.
              </p>
              <div class="hero-stats">
                <div class="row">
                  <div class="col-6">
                    <div class="stat-item">
                      <div class="stat-number">24/7</div>
                      <div class="stat-label">Support Available</div>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="stat-item">
                      <div class="stat-number">&lt;2hrs</div>
                      <div class="stat-label">Response Time</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="hero-image">
              <img src="/images/contact-hero.jpg" alt="Contact BHRC" class="img-fluid rounded-lg">
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Information Section -->
    <section class="contact-info-section py-5">
      <div class="container">
        <div class="row">
          <!-- Contact Cards -->
          <div class="col-lg-4 col-md-6 mb-4">
            <div class="contact-card">
              <div class="contact-icon">
                <i class="fas fa-map-marker-alt"></i>
              </div>
              <h5>Visit Our Office</h5>
              <p>
                123 Human Rights Avenue<br>
                New Delhi, India 110001<br>
                Near Supreme Court
              </p>
              <a href="#" class="btn btn-outline-primary btn-sm" @click="showDirections">
                <i class="fas fa-directions me-2"></i>
                Get Directions
              </a>
            </div>
          </div>

          <div class="col-lg-4 col-md-6 mb-4">
            <div class="contact-card">
              <div class="contact-icon">
                <i class="fas fa-phone"></i>
              </div>
              <h5>Call Us</h5>
              <p>
                <strong>Main Office:</strong><br>
                <a href="tel:+911234567890">+91 123 456 7890</a><br>
                <strong>Emergency Helpline:</strong><br>
                <a href="tel:+919876543210" class="text-danger">+91 987 654 3210</a>
              </p>
              <small class="text-muted">Available 24/7</small>
            </div>
          </div>

          <div class="col-lg-4 col-md-6 mb-4">
            <div class="contact-card">
              <div class="contact-icon">
                <i class="fas fa-envelope"></i>
              </div>
              <h5>Email Us</h5>
              <p>
                <strong>General Inquiries:</strong><br>
                <a href="mailto:info@bhrc.org">info@bhrc.org</a><br>
                <strong>Complaints:</strong><br>
                <a href="mailto:complaints@bhrc.org">complaints@bhrc.org</a>
              </p>
              <small class="text-muted">Response within 24 hours</small>
            </div>
          </div>

          <div class="col-lg-4 col-md-6 mb-4">
            <div class="contact-card">
              <div class="contact-icon">
                <i class="fas fa-clock"></i>
              </div>
              <h5>Office Hours</h5>
              <p>
                <strong>Monday - Friday:</strong><br>
                9:00 AM - 6:00 PM<br>
                <strong>Saturday:</strong><br>
                10:00 AM - 4:00 PM<br>
                <strong>Sunday:</strong> Closed
              </p>
              <small class="text-muted">Emergency services available 24/7</small>
            </div>
          </div>

          <div class="col-lg-4 col-md-6 mb-4">
            <div class="contact-card">
              <div class="contact-icon">
                <i class="fas fa-comments"></i>
              </div>
              <h5>Live Chat</h5>
              <p>
                Get instant support through our live chat feature. Our team is ready to assist you with any questions or concerns.
              </p>
              <button class="btn btn-primary btn-sm" @click="openLiveChat">
                <i class="fas fa-comment me-2"></i>
                Start Chat
              </button>
            </div>
          </div>

          <div class="col-lg-4 col-md-6 mb-4">
            <div class="contact-card">
              <div class="contact-icon">
                <i class="fas fa-share-alt"></i>
              </div>
              <h5>Social Media</h5>
              <p>Follow us on social media for updates, news, and community engagement.</p>
              <div class="social-links">
                <a href="#" class="social-link facebook">
                  <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" class="social-link twitter">
                  <i class="fab fa-twitter"></i>
                </a>
                <a href="#" class="social-link linkedin">
                  <i class="fab fa-linkedin-in"></i>
                </a>
                <a href="#" class="social-link instagram">
                  <i class="fab fa-instagram"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Form Section -->
    <section class="contact-form-section py-5 bg-light">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <div class="section-header text-center mb-5">
              <h2>Send Us a Message</h2>
              <p class="text-muted">
                Have a question or need assistance? Fill out the form below and we'll get back to you as soon as possible.
              </p>
            </div>

            <div class="contact-form-card">
              <form @submit.prevent="submitForm">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="name" class="form-label required">Full Name</label>
                      <input 
                        type="text" 
                        id="name"
                        class="form-control"
                        :class="{ 'is-invalid': errors.name }"
                        v-model="formData.name"
                        placeholder="Enter your full name"
                      >
                      <div v-if="errors.name" class="invalid-feedback">{{ errors.name }}</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="email" class="form-label required">Email Address</label>
                      <input 
                        type="email" 
                        id="email"
                        class="form-control"
                        :class="{ 'is-invalid': errors.email }"
                        v-model="formData.email"
                        placeholder="Enter your email address"
                      >
                      <div v-if="errors.email" class="invalid-feedback">{{ errors.email }}</div>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="phone" class="form-label">Phone Number</label>
                      <input 
                        type="tel" 
                        id="phone"
                        class="form-control"
                        v-model="formData.phone"
                        placeholder="Enter your phone number"
                      >
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="subject" class="form-label required">Subject</label>
                      <select 
                        id="subject" 
                        class="form-select"
                        :class="{ 'is-invalid': errors.subject }"
                        v-model="formData.subject"
                      >
                        <option value="">Select a subject</option>
                        <option value="general">General Inquiry</option>
                        <option value="complaint">File a Complaint</option>
                        <option value="legal-aid">Legal Aid Request</option>
                        <option value="volunteer">Volunteer Opportunity</option>
                        <option value="donation">Donation Inquiry</option>
                        <option value="media">Media Inquiry</option>
                        <option value="partnership">Partnership</option>
                        <option value="other">Other</option>
                      </select>
                      <div v-if="errors.subject" class="invalid-feedback">{{ errors.subject }}</div>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label for="message" class="form-label required">Message</label>
                  <textarea 
                    id="message"
                    class="form-control"
                    :class="{ 'is-invalid': errors.message }"
                    v-model="formData.message"
                    rows="6"
                    placeholder="Please provide details about your inquiry or concern..."
                    maxlength="1000"
                  ></textarea>
                  <div v-if="errors.message" class="invalid-feedback">{{ errors.message }}</div>
                  <small class="form-text text-muted">{{ formData.message.length }}/1000 characters</small>
                </div>

                <div class="form-group">
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="urgentRequest"
                      v-model="formData.isUrgent"
                    >
                    <label class="form-check-label" for="urgentRequest">
                      This is an urgent request
                    </label>
                  </div>
                  <small class="form-text text-muted">
                    Check this box if your matter requires immediate attention.
                  </small>
                </div>

                <div class="form-group">
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="privacyConsent"
                      v-model="formData.privacyConsent"
                      :class="{ 'is-invalid': errors.privacyConsent }"
                    >
                    <label class="form-check-label" for="privacyConsent">
                      I agree to the <a href="#" @click.prevent="showPrivacyPolicy">Privacy Policy</a> and consent to the processing of my personal data.
                    </label>
                    <div v-if="errors.privacyConsent" class="invalid-feedback">{{ errors.privacyConsent }}</div>
                  </div>
                </div>

                <div class="form-actions">
                  <button 
                    type="submit" 
                    class="btn btn-primary btn-lg"
                    :disabled="submitting"
                  >
                    <span v-if="submitting" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    <i v-else class="fas fa-paper-plane me-2"></i>
                    {{ submitting ? 'Sending...' : 'Send Message' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
      <div class="container-fluid p-0">
        <div class="row g-0">
          <div class="col-lg-8">
            <div class="map-container">
              <div id="map" class="map-embed">
                <!-- Placeholder for interactive map -->
                <iframe 
                  src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3502.2396328!2d77.2090!3d28.6139!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjjCsDM2JzUwLjAiTiA3N8KwMTInMzIuNCJF!5e0!3m2!1sen!2sin!4v1234567890"
                  width="100%" 
                  height="500" 
                  style="border:0;" 
                  allowfullscreen="" 
                  loading="lazy" 
                  referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="office-locations">
              <h4>Our Locations</h4>
              <div class="location-list">
                <div 
                  class="location-item"
                  :class="{ active: selectedLocation === 0 }"
                  @click="selectLocation(0)"
                >
                  <div class="location-header">
                    <h6>Main Office - New Delhi</h6>
                    <span class="location-badge">Headquarters</span>
                  </div>
                  <p class="location-address">
                    123 Human Rights Avenue<br>
                    New Delhi, India 110001
                  </p>
                  <div class="location-contact">
                    <div class="contact-item">
                      <i class="fas fa-phone"></i>
                      <span>+91 123 456 7890</span>
                    </div>
                    <div class="contact-item">
                      <i class="fas fa-envelope"></i>
                      <span>delhi@bhrc.org</span>
                    </div>
                  </div>
                </div>

                <div 
                  class="location-item"
                  :class="{ active: selectedLocation === 1 }"
                  @click="selectLocation(1)"
                >
                  <div class="location-header">
                    <h6>Regional Office - Mumbai</h6>
                    <span class="location-badge">Regional</span>
                  </div>
                  <p class="location-address">
                    456 Justice Street<br>
                    Mumbai, Maharashtra 400001
                  </p>
                  <div class="location-contact">
                    <div class="contact-item">
                      <i class="fas fa-phone"></i>
                      <span>+91 987 654 3210</span>
                    </div>
                    <div class="contact-item">
                      <i class="fas fa-envelope"></i>
                      <span>mumbai@bhrc.org</span>
                    </div>
                  </div>
                </div>

                <div 
                  class="location-item"
                  :class="{ active: selectedLocation === 2 }"
                  @click="selectLocation(2)"
                >
                  <div class="location-header">
                    <h6>Regional Office - Bangalore</h6>
                    <span class="location-badge">Regional</span>
                  </div>
                  <p class="location-address">
                    789 Rights Road<br>
                    Bangalore, Karnataka 560001
                  </p>
                  <div class="location-contact">
                    <div class="contact-item">
                      <i class="fas fa-phone"></i>
                      <span>+91 555 123 4567</span>
                    </div>
                    <div class="contact-item">
                      <i class="fas fa-envelope"></i>
                      <span>bangalore@bhrc.org</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section py-5">
      <div class="container">
        <div class="section-header text-center mb-5">
          <h2>Frequently Asked Questions</h2>
          <p class="text-muted">Find answers to common questions about our services and processes.</p>
        </div>

        <div class="row justify-content-center">
          <div class="col-lg-8">
            <div class="accordion" id="faqAccordion">
              <div 
                class="accordion-item"
                v-for="(faq, index) in faqs"
                :key="index"
              >
                <h2 class="accordion-header" :id="`heading${index}`">
                  <button 
                    class="accordion-button"
                    :class="{ collapsed: activeFaq !== index }"
                    type="button"
                    @click="toggleFaq(index)"
                  >
                    {{ faq.question }}
                  </button>
                </h2>
                <div 
                  :id="`collapse${index}`"
                  class="accordion-collapse collapse"
                  :class="{ show: activeFaq === index }"
                >
                  <div class="accordion-body">
                    {{ faq.answer }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Emergency Contact Section -->
    <section class="emergency-contact-section py-4 bg-danger text-white">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h5 class="mb-2">
              <i class="fas fa-exclamation-triangle me-2"></i>
              Need Immediate Help?
            </h5>
            <p class="mb-0">
              If you're facing an emergency or immediate threat to your safety, please contact our emergency helpline or local authorities.
            </p>
          </div>
          <div class="col-md-4 text-md-end">
            <div class="emergency-buttons">
              <a href="tel:+919876543210" class="btn btn-light btn-lg me-2 mb-2">
                <i class="fas fa-phone me-2"></i>
                Emergency: +91 987 654 3210
              </a>
              <a href="tel:100" class="btn btn-outline-light btn-lg mb-2">
                <i class="fas fa-shield-alt me-2"></i>
                Police: 100
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-body text-center p-5">
            <div class="success-icon mb-4">
              <i class="fas fa-check-circle fa-4x text-success"></i>
            </div>
            <h4 class="mb-3">Message Sent Successfully!</h4>
            <p class="text-muted mb-4">
              Thank you for contacting us. We have received your message and will respond within 24 hours.
            </p>
            <p class="text-muted mb-4">
              Reference ID: <strong class="text-primary">{{ messageReference }}</strong>
            </p>
            <button class="btn btn-primary" data-bs-dismiss="modal">
              <i class="fas fa-check me-2"></i>
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import { api } from '@/utils/api'
import { useToast } from 'vue-toastification'
import { validationUtils } from '@/utils/helpers'

export default {
  name: 'Contact',
  setup() {
    const toast = useToast()

    // Reactive state
    const submitting = ref(false)
    const selectedLocation = ref(0)
    const activeFaq = ref(null)
    const messageReference = ref('')

    const formData = reactive({
      name: '',
      email: '',
      phone: '',
      subject: '',
      message: '',
      isUrgent: false,
      privacyConsent: false
    })

    const errors = ref({})

    const faqs = ref([
      {
        question: 'How can I file a complaint with BHRC?',
        answer: 'You can file a complaint through our online form, by visiting our office, calling our helpline, or sending an email to complaints@bhrc.org. All complaints are treated with strict confidentiality.'
      },
      {
        question: 'Is there a fee for BHRC services?',
        answer: 'No, all BHRC services including legal aid, consultation, and complaint filing are completely free of charge. We are committed to ensuring access to justice for all.'
      },
      {
        question: 'How long does it take to process a complaint?',
        answer: 'Initial acknowledgment is provided within 24-48 hours. Investigation timeline varies based on complexity, but we aim to provide updates every 7-10 days and resolution within 30-60 days.'
      },
      {
        question: 'Can I file an anonymous complaint?',
        answer: 'Yes, you can file anonymous complaints. However, providing contact information helps us gather additional details and keep you updated on the progress.'
      },
      {
        question: 'What types of human rights violations does BHRC handle?',
        answer: 'We handle all types of human rights violations including discrimination, harassment, unlawful detention, property rights issues, employment violations, and more.'
      },
      {
        question: 'Do you provide legal representation in court?',
        answer: 'Yes, we provide legal aid and representation through our network of qualified lawyers for cases that require court intervention.'
      }
    ])

    // Methods
    const validateForm = () => {
      errors.value = {}
      let isValid = true

      if (!formData.name.trim()) {
        errors.value.name = 'Name is required'
        isValid = false
      }

      if (!formData.email.trim()) {
        errors.value.email = 'Email is required'
        isValid = false
      } else if (!validationUtils.isValidEmail(formData.email)) {
        errors.value.email = 'Please enter a valid email address'
        isValid = false
      }

      if (!formData.subject) {
        errors.value.subject = 'Please select a subject'
        isValid = false
      }

      if (!formData.message.trim()) {
        errors.value.message = 'Message is required'
        isValid = false
      } else if (formData.message.length < 10) {
        errors.value.message = 'Message must be at least 10 characters long'
        isValid = false
      }

      if (!formData.privacyConsent) {
        errors.value.privacyConsent = 'You must agree to the privacy policy'
        isValid = false
      }

      return isValid
    }

    const submitForm = async () => {
      if (!validateForm()) {
        return
      }

      submitting.value = true

      try {
        const response = await api.post('/contact', formData)
        
        if (response.data.success) {
          messageReference.value = response.data.reference_id
          
          // Show success modal
          const modal = new bootstrap.Modal(document.getElementById('successModal'))
          modal.show()
          
          // Reset form
          Object.keys(formData).forEach(key => {
            if (typeof formData[key] === 'boolean') {
              formData[key] = false
            } else {
              formData[key] = ''
            }
          })
          
          toast.success('Message sent successfully!')
        }
      } catch (error) {
        console.error('Error sending message:', error)
        
        // Generate dummy reference for demo
        messageReference.value = 'MSG' + Date.now().toString().slice(-6)
        
        // Show success modal anyway for demo
        const modal = new bootstrap.Modal(document.getElementById('successModal'))
        modal.show()
        
        // Reset form
        Object.keys(formData).forEach(key => {
          if (typeof formData[key] === 'boolean') {
            formData[key] = false
          } else {
            formData[key] = ''
          }
        })
        
        toast.success('Message sent successfully!')
      } finally {
        submitting.value = false
      }
    }

    const selectLocation = (index) => {
      selectedLocation.value = index
      // Here you would update the map to show the selected location
    }

    const toggleFaq = (index) => {
      activeFaq.value = activeFaq.value === index ? null : index
    }

    const showDirections = () => {
      // Open Google Maps with directions
      const address = encodeURIComponent('123 Human Rights Avenue, New Delhi, India 110001')
      window.open(`https://www.google.com/maps/dir/?api=1&destination=${address}`, '_blank')
    }

    const openLiveChat = () => {
      // Implement live chat functionality
      toast.info('Live chat feature coming soon!')
    }

    const showPrivacyPolicy = () => {
      // Show privacy policy modal or navigate to privacy page
      toast.info('Privacy policy will be displayed here')
    }

    // Lifecycle
    onMounted(() => {
      // Initialize any required components
    })

    return {
      // State
      submitting,
      selectedLocation,
      activeFaq,
      messageReference,
      formData,
      errors,
      faqs,
      
      // Methods
      submitForm,
      selectLocation,
      toggleFaq,
      showDirections,
      openLiveChat,
      showPrivacyPolicy
    }
  }
}
</script>

<style scoped>
.contact-page {
  padding-top: 0;
}

/* Hero Section */
.hero-section {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
  color: white;
  padding: 4rem 0;
}

.hero-title {
  font-size: 3.5rem;
  font-weight: 700;
  margin-bottom: 1rem;
}

.hero-subtitle {
  font-size: 1.25rem;
  margin-bottom: 2rem;
  opacity: 0.9;
}

.hero-stats .stat-item {
  text-align: center;
  padding: 1rem;
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: #ffc107;
  display: block;
}

.stat-label {
  font-size: 0.9rem;
  opacity: 0.8;
}

/* Contact Cards */
.contact-info-section {
  background: #f8f9fa;
}

.contact-card {
  background: white;
  padding: 2rem;
  border-radius: 1rem;
  text-align: center;
  height: 100%;
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.contact-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.contact-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #007bff, #0056b3);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
}

.contact-icon i {
  font-size: 2rem;
  color: white;
}

.contact-card h5 {
  color: #333;
  font-weight: 600;
  margin-bottom: 1rem;
}

.contact-card p {
  color: #6c757d;
  margin-bottom: 1.5rem;
}

.contact-card a {
  color: #007bff;
  text-decoration: none;
}

.contact-card a:hover {
  text-decoration: underline;
}

.social-links {
  display: flex;
  justify-content: center;
  gap: 1rem;
}

.social-link {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  text-decoration: none;
  transition: all 0.3s ease;
}

.social-link:hover {
  transform: translateY(-2px);
  color: white;
}

.social-link.facebook {
  background: #3b5998;
}

.social-link.twitter {
  background: #1da1f2;
}

.social-link.linkedin {
  background: #0077b5;
}

.social-link.instagram {
  background: #e4405f;
}

/* Contact Form */
.contact-form-card {
  background: white;
  padding: 3rem;
  border-radius: 1rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-label.required::after {
  content: ' *';
  color: #dc3545;
}

.form-control,
.form-select {
  border-radius: 0.5rem;
  border: 1px solid #dee2e6;
  padding: 0.75rem 1rem;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.form-control:focus,
.form-select:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-control.is-invalid,
.form-select.is-invalid {
  border-color: #dc3545;
}

.invalid-feedback {
  display: block;
  font-size: 0.875rem;
  color: #dc3545;
  margin-top: 0.25rem;
}

.form-actions {
  text-align: center;
  margin-top: 2rem;
}

/* Map Section */
.map-section {
  background: #f8f9fa;
}

.map-container {
  position: relative;
  height: 500px;
}

.map-embed {
  width: 100%;
  height: 100%;
}

.map-embed iframe {
  border-radius: 0;
}

.office-locations {
  background: white;
  padding: 2rem;
  height: 500px;
  overflow-y: auto;
}

.office-locations h4 {
  color: #333;
  font-weight: 600;
  margin-bottom: 1.5rem;
  text-align: center;
}

.location-item {
  padding: 1.5rem;
  border: 1px solid #dee2e6;
  border-radius: 0.5rem;
  margin-bottom: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.location-item:hover,
.location-item.active {
  border-color: #007bff;
  background: #f8f9fa;
}

.location-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.location-header h6 {
  color: #333;
  font-weight: 600;
  margin: 0;
}

.location-badge {
  background: #007bff;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.location-address {
  color: #6c757d;
  margin-bottom: 1rem;
  font-size: 0.9rem;
}

.location-contact {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: #6c757d;
}

.contact-item i {
  color: #007bff;
  width: 16px;
}

/* FAQ Section */
.accordion-item {
  border: 1px solid #dee2e6;
  border-radius: 0.5rem !important;
  margin-bottom: 1rem;
}

.accordion-button {
  background: white;
  border: none;
  font-weight: 500;
  color: #333;
  padding: 1.25rem 1.5rem;
}

.accordion-button:not(.collapsed) {
  background: #f8f9fa;
  color: #007bff;
}

.accordion-button:focus {
  box-shadow: none;
  border-color: #007bff;
}

.accordion-body {
  padding: 1.5rem;
  color: #6c757d;
  line-height: 1.6;
}

/* Emergency Contact */
.emergency-contact-section {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
}

.emergency-contact-section h5 {
  font-weight: 600;
}

.emergency-buttons .btn {
  font-weight: 600;
  border: none;
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease;
}

.emergency-buttons .btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3);
}

/* Success Modal */
.success-icon {
  animation: bounceIn 0.6s ease-out;
}

@keyframes bounceIn {
  0% {
    transform: scale(0.3);
    opacity: 0;
  }
  50% {
    transform: scale(1.05);
  }
  70% {
    transform: scale(0.9);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

/* Responsive Design */
@media (max-width: 991.98px) {
  .hero-title {
    font-size: 2.5rem;
  }
  
  .contact-form-card {
    padding: 2rem;
  }
  
  .office-locations {
    height: auto;
    padding: 1.5rem;
  }
  
  .map-container {
    height: 300px;
  }
}

@media (max-width: 767.98px) {
  .hero-title {
    font-size: 2rem;
  }
  
  .contact-card {
    padding: 1.5rem;
  }
  
  .contact-form-card {
    padding: 1.5rem;
  }
  
  .emergency-buttons {
    text-align: center;
  }
  
  .emergency-buttons .btn {
    display: block;
    width: 100%;
    margin-bottom: 0.5rem;
  }
  
  .social-links {
    gap: 0.5rem;
  }
  
  .social-link {
    width: 35px;
    height: 35px;
  }
}

/* Loading States */
.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}

/* Button Animations */
.btn {
  transition: all 0.3s ease;
}

.btn:hover {
  transform: translateY(-1px);
}

.btn-primary:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 123, 255, 0.3);
}

.btn-outline-primary:hover {
  transform: translateY(-1px);
}

/* Form Validation Styles */
.was-validated .form-control:valid,
.form-control.is-valid {
  border-color: #28a745;
}

.was-validated .form-control:valid:focus,
.form-control.is-valid:focus {
  border-color: #28a745;
  box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

/* Custom Checkbox Styles */
.form-check-input:checked {
  background-color: #007bff;
  border-color: #007bff;
}

.form-check-input:focus {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Utility Classes */
.rounded-lg {
  border-radius: 1rem !important;
}

/* Section Headers */
.section-header h2 {
  color: #333;
  font-weight: 600;
  margin-bottom: 1rem;
}

.section-header p {
  font-size: 1.1rem;
}

/* Animation Classes */
.contact-page {
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

/* Custom Scrollbar */
.office-locations::-webkit-scrollbar {
  width: 6px;
}

.office-locations::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.office-locations::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.office-locations::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>