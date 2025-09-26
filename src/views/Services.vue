<template>
  <div class="services-page">
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <div class="hero-content">
              <h1 class="hero-title">Our Services</h1>
              <p class="hero-subtitle">
                Comprehensive human rights protection services designed to ensure justice and equality for all.
              </p>
              <div class="hero-features">
                <div class="feature-item">
                  <i class="fas fa-check-circle"></i>
                  <span>Free Legal Consultation</span>
                </div>
                <div class="feature-item">
                  <i class="fas fa-check-circle"></i>
                  <span>24/7 Emergency Support</span>
                </div>
                <div class="feature-item">
                  <i class="fas fa-check-circle"></i>
                  <span>Expert Legal Representation</span>
                </div>
              </div>
              <div class="hero-cta">
                <router-link to="/contact" class="btn btn-primary btn-lg me-3">
                  <i class="fas fa-phone me-2"></i>
                  Get Help Now
                </router-link>
                <a href="#services" class="btn btn-outline-primary btn-lg">
                  <i class="fas fa-arrow-down me-2"></i>
                  View Services
                </a>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="hero-image">
              <img src="/images/services-hero.jpg" alt="BHRC Services" class="img-fluid rounded-lg">
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Services Grid Section -->
    <section id="services" class="services-section py-5">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="section-title">Our Core Services</h2>
          <p class="section-subtitle">
            We provide comprehensive human rights protection services across various domains
          </p>
        </div>
        
        <div class="row">
          <div class="col-lg-4 col-md-6 mb-4" v-for="service in services" :key="service.id">
            <div class="service-card h-100">
              <div class="service-icon">
                <i :class="service.icon"></i>
              </div>
              <div class="service-content">
                <h4>{{ service.title }}</h4>
                <p>{{ service.description }}</p>
                <ul class="service-features">
                  <li v-for="feature in service.features" :key="feature">
                    <i class="fas fa-check text-success me-2"></i>
                    {{ feature }}
                  </li>
                </ul>
                <div class="service-stats">
                  <div class="row text-center">
                    <div class="col-6">
                      <div class="stat-number">{{ service.casesHandled }}+</div>
                      <div class="stat-label">Cases</div>
                    </div>
                    <div class="col-6">
                      <div class="stat-number">{{ service.successRate }}%</div>
                      <div class="stat-label">Success Rate</div>
                    </div>
                  </div>
                </div>
                <div class="service-actions">
                  <button 
                    class="btn btn-primary btn-sm me-2"
                    @click="requestService(service)"
                  >
                    <i class="fas fa-hand-paper me-1"></i>
                    Request Service
                  </button>
                  <button 
                    class="btn btn-outline-secondary btn-sm"
                    @click="learnMore(service)"
                  >
                    <i class="fas fa-info-circle me-1"></i>
                    Learn More
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Emergency Services Section -->
    <section class="emergency-section py-5 bg-danger text-white">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-8">
            <div class="emergency-content">
              <h3 class="mb-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Emergency Human Rights Violations
              </h3>
              <p class="mb-4">
                If you or someone you know is facing immediate human rights violations, 
                contact our emergency helpline for immediate assistance.
              </p>
              <div class="emergency-features">
                <div class="row">
                  <div class="col-md-4 mb-2">
                    <i class="fas fa-clock me-2"></i>
                    24/7 Available
                  </div>
                  <div class="col-md-4 mb-2">
                    <i class="fas fa-shield-alt me-2"></i>
                    Confidential
                  </div>
                  <div class="col-md-4 mb-2">
                    <i class="fas fa-bolt me-2"></i>
                    Immediate Response
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-4 text-center">
            <div class="emergency-contact">
              <div class="emergency-number">
                <i class="fas fa-phone-alt"></i>
                <span>1800-123-4567</span>
              </div>
              <button class="btn btn-light btn-lg mt-3" @click="callEmergency">
                <i class="fas fa-phone me-2"></i>
                Call Now
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Process Section -->
    <section class="process-section py-5 bg-light">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="section-title">How We Help You</h2>
          <p class="section-subtitle">
            Our systematic approach to handling human rights cases
          </p>
        </div>
        
        <div class="row">
          <div class="col-lg-3 col-md-6 mb-4" v-for="(step, index) in processSteps" :key="step.id">
            <div class="process-step">
              <div class="step-number">{{ index + 1 }}</div>
              <div class="step-icon">
                <i :class="step.icon"></i>
              </div>
              <h5>{{ step.title }}</h5>
              <p>{{ step.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials-section py-5">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="section-title">What Our Clients Say</h2>
          <p class="section-subtitle">
            Real stories from people we've helped
          </p>
        </div>
        
        <div class="row">
          <div class="col-lg-4 col-md-6 mb-4" v-for="testimonial in testimonials" :key="testimonial.id">
            <div class="testimonial-card">
              <div class="testimonial-content">
                <div class="quote-icon">
                  <i class="fas fa-quote-left"></i>
                </div>
                <p>"{{ testimonial.content }}"</p>
                <div class="testimonial-rating">
                  <i v-for="n in 5" :key="n" class="fas fa-star" :class="{ 'text-warning': n <= testimonial.rating, 'text-muted': n > testimonial.rating }"></i>
                </div>
              </div>
              <div class="testimonial-author">
                <div class="author-info">
                  <h6>{{ testimonial.name }}</h6>
                  <small class="text-muted">{{ testimonial.case }}</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section py-5 bg-light">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="section-title">Frequently Asked Questions</h2>
          <p class="section-subtitle">
            Common questions about our services
          </p>
        </div>
        
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <div class="accordion" id="faqAccordion">
              <div class="accordion-item" v-for="(faq, index) in faqs" :key="faq.id">
                <h2 class="accordion-header" :id="`heading${index}`">
                  <button 
                    class="accordion-button" 
                    :class="{ collapsed: index !== 0 }"
                    type="button" 
                    data-bs-toggle="collapse" 
                    :data-bs-target="`#collapse${index}`"
                    :aria-expanded="index === 0 ? 'true' : 'false'"
                    :aria-controls="`collapse${index}`"
                  >
                    {{ faq.question }}
                  </button>
                </h2>
                <div 
                  :id="`collapse${index}`" 
                  class="accordion-collapse collapse"
                  :class="{ show: index === 0 }"
                  :aria-labelledby="`heading${index}`"
                  data-bs-parent="#faqAccordion"
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

    <!-- CTA Section -->
    <section class="cta-section py-5">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-8">
            <h3>Need Legal Assistance?</h3>
            <p class="mb-0">
              Don't let human rights violations go unaddressed. Contact us today for expert legal support.
            </p>
          </div>
          <div class="col-lg-4 text-lg-end">
            <router-link to="/complaint" class="btn btn-light btn-lg me-3">
              <i class="fas fa-file-alt me-2"></i>
              File Complaint
            </router-link>
            <router-link to="/contact" class="btn btn-outline-light btn-lg">
              <i class="fas fa-phone me-2"></i>
              Contact Us
            </router-link>
          </div>
        </div>
      </div>
    </section>

    <!-- Service Request Modal -->
    <div class="modal fade" id="serviceRequestModal" tabindex="-1" aria-labelledby="serviceRequestModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="serviceRequestModalLabel">
              Request Service: {{ selectedService?.title }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="submitServiceRequest">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="requestName" class="form-label">Full Name *</label>
                  <input 
                    type="text" 
                    id="requestName"
                    v-model="serviceRequest.name"
                    class="form-control"
                    required
                  >
                </div>
                <div class="col-md-6 mb-3">
                  <label for="requestEmail" class="form-label">Email *</label>
                  <input 
                    type="email" 
                    id="requestEmail"
                    v-model="serviceRequest.email"
                    class="form-control"
                    required
                  >
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="requestPhone" class="form-label">Phone Number *</label>
                  <input 
                    type="tel" 
                    id="requestPhone"
                    v-model="serviceRequest.phone"
                    class="form-control"
                    required
                  >
                </div>
                <div class="col-md-6 mb-3">
                  <label for="requestUrgency" class="form-label">Urgency Level *</label>
                  <select 
                    id="requestUrgency"
                    v-model="serviceRequest.urgency"
                    class="form-select"
                    required
                  >
                    <option value="">Select Urgency</option>
                    <option value="low">Low - General Inquiry</option>
                    <option value="medium">Medium - Within a Week</option>
                    <option value="high">High - Within 24 Hours</option>
                    <option value="emergency">Emergency - Immediate</option>
                  </select>
                </div>
              </div>
              <div class="mb-3">
                <label for="requestDescription" class="form-label">Case Description *</label>
                <textarea 
                  id="requestDescription"
                  v-model="serviceRequest.description"
                  class="form-control"
                  rows="4"
                  placeholder="Please describe your case in detail..."
                  required
                ></textarea>
              </div>
              <div class="mb-3">
                <div class="form-check">
                  <input 
                    type="checkbox" 
                    id="requestConsent"
                    v-model="serviceRequest.consent"
                    class="form-check-input"
                    required
                  >
                  <label for="requestConsent" class="form-check-label">
                    I consent to BHRC contacting me regarding this service request
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
              @click="submitServiceRequest"
              :disabled="isSubmitting"
            >
              <i v-if="isSubmitting" class="fas fa-spinner fa-spin me-2"></i>
              {{ isSubmitting ? 'Submitting...' : 'Submit Request' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/utils/api'
import { useToast } from 'vue-toastification'

export default {
  name: 'Services',
  setup() {
    const router = useRouter()
    const toast = useToast()

    // Reactive state
    const services = ref([
      {
        id: 1,
        title: 'Legal Aid & Consultation',
        description: 'Free legal consultation and representation for human rights violations',
        icon: 'fas fa-gavel',
        features: [
          'Free initial consultation',
          'Expert legal advice',
          'Court representation',
          'Document preparation'
        ],
        casesHandled: 500,
        successRate: 85
      },
      {
        id: 2,
        title: 'Women Rights Protection',
        description: 'Specialized services for women facing discrimination and violence',
        icon: 'fas fa-venus',
        features: [
          'Domestic violence support',
          'Workplace harassment',
          'Property rights',
          'Safety measures'
        ],
        casesHandled: 300,
        successRate: 90
      },
      {
        id: 3,
        title: 'Child Protection Services',
        description: 'Safeguarding children from abuse, exploitation, and neglect',
        icon: 'fas fa-child',
        features: [
          'Child abuse cases',
          'Educational rights',
          'Child labor issues',
          'Custody matters'
        ],
        casesHandled: 200,
        successRate: 88
      },
      {
        id: 4,
        title: 'Minority Rights Advocacy',
        description: 'Protecting rights of religious and ethnic minorities',
        icon: 'fas fa-users',
        features: [
          'Religious freedom',
          'Cultural rights',
          'Anti-discrimination',
          'Community support'
        ],
        casesHandled: 150,
        successRate: 82
      },
      {
        id: 5,
        title: 'Labor Rights Protection',
        description: 'Ensuring fair treatment and rights for workers',
        icon: 'fas fa-hard-hat',
        features: [
          'Wage disputes',
          'Working conditions',
          'Union rights',
          'Safety violations'
        ],
        casesHandled: 400,
        successRate: 87
      },
      {
        id: 6,
        title: 'Disability Rights Support',
        description: 'Advocating for persons with disabilities',
        icon: 'fas fa-wheelchair',
        features: [
          'Accessibility rights',
          'Employment discrimination',
          'Education access',
          'Social inclusion'
        ],
        casesHandled: 100,
        successRate: 85
      }
    ])

    const processSteps = ref([
      {
        id: 1,
        title: 'Initial Consultation',
        description: 'Free consultation to understand your case and legal options',
        icon: 'fas fa-comments'
      },
      {
        id: 2,
        title: 'Case Assessment',
        description: 'Thorough evaluation of your case by our legal experts',
        icon: 'fas fa-search'
      },
      {
        id: 3,
        title: 'Legal Strategy',
        description: 'Development of comprehensive legal strategy for your case',
        icon: 'fas fa-chess'
      },
      {
        id: 4,
        title: 'Representation',
        description: 'Expert legal representation in courts and tribunals',
        icon: 'fas fa-balance-scale'
      }
    ])

    const testimonials = ref([
      {
        id: 1,
        name: 'Priya Sharma',
        case: 'Workplace Harassment',
        content: 'BHRC helped me get justice when I faced harassment at work. Their support was invaluable.',
        rating: 5
      },
      {
        id: 2,
        name: 'Rajesh Kumar',
        case: 'Property Rights',
        content: 'Professional and dedicated team. They fought for my rights and won the case.',
        rating: 5
      },
      {
        id: 3,
        name: 'Anita Patel',
        case: 'Child Custody',
        content: 'Excellent legal support during a difficult time. Highly recommend their services.',
        rating: 5
      }
    ])

    const faqs = ref([
      {
        id: 1,
        question: 'Are your legal services free?',
        answer: 'Yes, we provide free legal consultation and representation for human rights cases. Our mission is to ensure access to justice for all, regardless of financial status.'
      },
      {
        id: 2,
        question: 'How long does it take to resolve a case?',
        answer: 'The duration varies depending on the complexity of the case and court procedures. Simple cases may be resolved in a few months, while complex cases might take longer. We keep you informed throughout the process.'
      },
      {
        id: 3,
        question: 'Do you handle cases outside your city?',
        answer: 'Yes, we handle cases across India. We have a network of lawyers and can provide representation in various states and cities.'
      },
      {
        id: 4,
        question: 'What documents do I need to bring?',
        answer: 'Bring any relevant documents related to your case, such as police reports, medical records, employment documents, or any correspondence. Our team will guide you on specific requirements.'
      },
      {
        id: 5,
        question: 'Is my information kept confidential?',
        answer: 'Absolutely. We maintain strict confidentiality of all client information and follow professional ethics guidelines. Your privacy and safety are our top priorities.'
      }
    ])

    const selectedService = ref(null)
    const isSubmitting = ref(false)
    const serviceRequest = reactive({
      name: '',
      email: '',
      phone: '',
      urgency: '',
      description: '',
      consent: false
    })

    // Methods
    const requestService = (service) => {
      selectedService.value = service
      // Reset form
      Object.assign(serviceRequest, {
        name: '',
        email: '',
        phone: '',
        urgency: '',
        description: '',
        consent: false
      })
      // Show modal (Bootstrap modal)
      const modal = new bootstrap.Modal(document.getElementById('serviceRequestModal'))
      modal.show()
    }

    const learnMore = (service) => {
      // Navigate to detailed service page or show more info
      router.push(`/services/${service.id}`)
    }

    const submitServiceRequest = async () => {
      if (!serviceRequest.consent) {
        toast.error('Please provide consent to proceed')
        return
      }

      isSubmitting.value = true

      try {
        const requestData = {
          service_id: selectedService.value.id,
          service_name: selectedService.value.title,
          name: serviceRequest.name,
          email: serviceRequest.email,
          phone: serviceRequest.phone,
          urgency: serviceRequest.urgency,
          description: serviceRequest.description
        }

        const response = await api.post('/services/request', requestData)

        if (response.data.success) {
          toast.success('Service request submitted successfully! We will contact you soon.')
          
          // Hide modal
          const modal = bootstrap.Modal.getInstance(document.getElementById('serviceRequestModal'))
          modal.hide()
          
          // Reset form
          Object.assign(serviceRequest, {
            name: '',
            email: '',
            phone: '',
            urgency: '',
            description: '',
            consent: false
          })
        }
      } catch (error) {
        console.error('Error submitting service request:', error)
        toast.error('Failed to submit request. Please try again.')
      } finally {
        isSubmitting.value = false
      }
    }

    const callEmergency = () => {
      window.location.href = 'tel:1800-123-4567'
    }

    // Lifecycle
    onMounted(() => {
      // Load any dynamic data if needed
    })

    return {
      // State
      services,
      processSteps,
      testimonials,
      faqs,
      selectedService,
      serviceRequest,
      isSubmitting,
      
      // Methods
      requestService,
      learnMore,
      submitServiceRequest,
      callEmergency
    }
  }
}
</script>

<style scoped>
.services-page {
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

.hero-features {
  margin-bottom: 2rem;
}

.feature-item {
  display: flex;
  align-items: center;
  margin-bottom: 0.75rem;
  font-size: 1.1rem;
}

.feature-item i {
  color: #28a745;
  margin-right: 0.75rem;
  font-size: 1.2rem;
}

.hero-cta .btn {
  padding: 0.75rem 2rem;
  font-weight: 600;
}

/* Service Cards */
.service-card {
  background: white;
  border-radius: 1rem;
  padding: 2rem;
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  border-top: 4px solid #007bff;
}

.service-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.service-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #007bff, #0056b3);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.5rem;
}

.service-icon i {
  font-size: 2rem;
  color: white;
}

.service-content h4 {
  color: #333;
  font-weight: 600;
  margin-bottom: 1rem;
}

.service-features {
  list-style: none;
  padding: 0;
  margin-bottom: 1.5rem;
}

.service-features li {
  padding: 0.25rem 0;
  font-size: 0.9rem;
}

.service-stats {
  background: #f8f9fa;
  border-radius: 0.5rem;
  padding: 1rem;
  margin-bottom: 1.5rem;
}

.service-stats .stat-number {
  font-size: 1.5rem;
  font-weight: 700;
  color: #007bff;
}

.service-stats .stat-label {
  font-size: 0.8rem;
  color: #6c757d;
  text-transform: uppercase;
}

.service-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

/* Emergency Section */
.emergency-section {
  background: linear-gradient(135deg, #dc3545, #c82333);
}

.emergency-content h3 {
  font-size: 1.75rem;
  font-weight: 600;
}

.emergency-features {
  font-size: 0.9rem;
}

.emergency-contact {
  text-align: center;
}

.emergency-number {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1rem;
}

.emergency-number i {
  margin-right: 0.5rem;
  font-size: 1.25rem;
}

/* Process Steps */
.process-step {
  text-align: center;
  padding: 2rem 1rem;
  position: relative;
}

.step-number {
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 40px;
  height: 40px;
  background: #007bff;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.1rem;
}

.step-icon {
  width: 80px;
  height: 80px;
  background: #f8f9fa;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 2rem auto 1.5rem;
  border: 3px solid #007bff;
}

.step-icon i {
  font-size: 1.5rem;
  color: #007bff;
}

.process-step h5 {
  color: #333;
  font-weight: 600;
  margin-bottom: 1rem;
}

/* Testimonials */
.testimonial-card {
  background: white;
  border-radius: 1rem;
  padding: 2rem;
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
  height: 100%;
  position: relative;
}

.quote-icon {
  position: absolute;
  top: -10px;
  left: 20px;
  width: 40px;
  height: 40px;
  background: #007bff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.testimonial-content {
  margin-top: 1rem;
  margin-bottom: 1.5rem;
}

.testimonial-rating {
  margin-top: 1rem;
}

.testimonial-author h6 {
  color: #333;
  font-weight: 600;
  margin-bottom: 0.25rem;
}

/* FAQ Section */
.accordion-button {
  font-weight: 600;
  color: #333;
}

.accordion-button:not(.collapsed) {
  background-color: #007bff;
  color: white;
}

.accordion-button:focus {
  box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
}

/* CTA Section */
.cta-section {
  background: linear-gradient(135deg, #007bff, #0056b3);
  color: white;
}

.cta-section h3 {
  color: white;
  font-weight: 600;
  margin-bottom: 0.5rem;
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

/* Section Titles */
.section-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #333;
  margin-bottom: 1rem;
}

.section-subtitle {
  font-size: 1.1rem;
  color: #6c757d;
  margin-bottom: 0;
}

/* Responsive Design */
@media (max-width: 991.98px) {
  .hero-title {
    font-size: 2.5rem;
  }
  
  .hero-cta .btn {
    display: block;
    width: 100%;
    margin-bottom: 0.5rem;
  }
  
  .emergency-contact {
    margin-top: 2rem;
  }
  
  .service-actions {
    justify-content: center;
  }
}

@media (max-width: 767.98px) {
  .hero-title {
    font-size: 2rem;
  }
  
  .section-title {
    font-size: 2rem;
  }
  
  .service-card {
    padding: 1.5rem;
  }
  
  .process-step {
    padding: 1.5rem 0.5rem;
  }
  
  .emergency-number {
    font-size: 1.25rem;
  }
}

/* Animations */
.services-page {
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

.btn-outline-primary:hover {
  transform: translateY(-1px);
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