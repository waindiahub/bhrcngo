<template>
  <div class="about-page">
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="row align-items-center min-vh-50">
          <div class="col-lg-6">
            <div class="hero-content">
              <h1 class="hero-title">About BHRC</h1>
              <p class="hero-subtitle">
                Dedicated to protecting and promoting human rights across India since our inception.
              </p>
              <div class="hero-stats">
                <div class="row">
                  <div class="col-4">
                    <div class="stat-item">
                      <div class="stat-number">{{ formatNumber(stats.totalCases) }}</div>
                      <div class="stat-label">Cases Handled</div>
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
                      <div class="stat-number">{{ stats.yearsActive }}+</div>
                      <div class="stat-label">Years Active</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="hero-image">
              <img src="/images/about-hero.jpg" alt="BHRC Team" class="img-fluid rounded-lg shadow-lg">
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Mission & Vision Section -->
    <section class="mission-vision-section py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-6 mb-4">
            <div class="mission-card h-100">
              <div class="card-icon">
                <i class="fas fa-bullseye"></i>
              </div>
              <h3>Our Mission</h3>
              <p>
                To protect, promote, and defend human rights for all individuals, 
                ensuring justice, equality, and dignity in every aspect of life. 
                We strive to create a society where fundamental rights are respected 
                and upheld without discrimination.
              </p>
              <ul class="mission-points">
                <li>Provide legal aid and support to victims of human rights violations</li>
                <li>Advocate for policy changes and legal reforms</li>
                <li>Raise awareness about human rights issues</li>
                <li>Build a network of human rights defenders</li>
              </ul>
            </div>
          </div>
          <div class="col-lg-6 mb-4">
            <div class="vision-card h-100">
              <div class="card-icon">
                <i class="fas fa-eye"></i>
              </div>
              <h3>Our Vision</h3>
              <p>
                A world where human rights are universally respected, protected, 
                and fulfilled. We envision a society free from discrimination, 
                violence, and injustice, where every person can live with dignity 
                and enjoy their fundamental freedoms.
              </p>
              <ul class="vision-points">
                <li>Equal access to justice for all</li>
                <li>Elimination of all forms of discrimination</li>
                <li>Transparent and accountable governance</li>
                <li>Empowered communities and individuals</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Our Work Section -->
    <section class="our-work-section py-5 bg-light">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="section-title">Our Work Areas</h2>
          <p class="section-subtitle">
            We focus on various aspects of human rights protection and promotion
          </p>
        </div>
        <div class="row">
          <div class="col-lg-4 col-md-6 mb-4" v-for="workArea in workAreas" :key="workArea.id">
            <div class="work-card">
              <div class="work-icon">
                <i :class="workArea.icon"></i>
              </div>
              <h4>{{ workArea.title }}</h4>
              <p>{{ workArea.description }}</p>
              <div class="work-stats">
                <span class="badge bg-primary">{{ workArea.casesHandled }} Cases</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Team Section -->
    <section class="team-section py-5">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="section-title">Our Leadership Team</h2>
          <p class="section-subtitle">
            Meet the dedicated professionals leading our human rights mission
          </p>
        </div>
        <div class="row">
          <div class="col-lg-4 col-md-6 mb-4" v-for="member in teamMembers" :key="member.id">
            <div class="team-card">
              <div class="team-image">
                <img :src="member.image" :alt="member.name" class="img-fluid">
                <div class="team-overlay">
                  <div class="social-links">
                    <a v-if="member.linkedin" :href="member.linkedin" target="_blank" class="social-link">
                      <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a v-if="member.twitter" :href="member.twitter" target="_blank" class="social-link">
                      <i class="fab fa-twitter"></i>
                    </a>
                    <a v-if="member.email" :href="`mailto:${member.email}`" class="social-link">
                      <i class="fas fa-envelope"></i>
                    </a>
                  </div>
                </div>
              </div>
              <div class="team-info">
                <h5>{{ member.name }}</h5>
                <p class="team-position">{{ member.position }}</p>
                <p class="team-bio">{{ member.bio }}</p>
                <div class="team-credentials">
                  <span v-for="credential in member.credentials" :key="credential" class="badge bg-outline-primary me-1">
                    {{ credential }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Achievements Section -->
    <section class="achievements-section py-5 bg-primary text-white">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="section-title text-white">Our Achievements</h2>
          <p class="section-subtitle text-white-50">
            Milestones in our journey towards justice and human rights protection
          </p>
        </div>
        <div class="row">
          <div class="col-lg-3 col-md-6 mb-4" v-for="achievement in achievements" :key="achievement.id">
            <div class="achievement-card">
              <div class="achievement-icon">
                <i :class="achievement.icon"></i>
              </div>
              <div class="achievement-number">{{ achievement.number }}</div>
              <div class="achievement-title">{{ achievement.title }}</div>
              <div class="achievement-description">{{ achievement.description }}</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Timeline Section -->
    <section class="timeline-section py-5">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="section-title">Our Journey</h2>
          <p class="section-subtitle">
            Key milestones in our organization's history
          </p>
        </div>
        <div class="timeline">
          <div class="timeline-item" v-for="(event, index) in timelineEvents" :key="event.id" :class="{ 'timeline-item-right': index % 2 === 1 }">
            <div class="timeline-marker">
              <i :class="event.icon"></i>
            </div>
            <div class="timeline-content">
              <div class="timeline-year">{{ event.year }}</div>
              <h4>{{ event.title }}</h4>
              <p>{{ event.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Partners Section -->
    <section class="partners-section py-5 bg-light">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="section-title">Our Partners</h2>
          <p class="section-subtitle">
            Working together with organizations that share our vision
          </p>
        </div>
        <div class="row align-items-center">
          <div class="col-lg-2 col-md-4 col-6 mb-4" v-for="partner in partners" :key="partner.id">
            <div class="partner-logo">
              <img :src="partner.logo" :alt="partner.name" class="img-fluid">
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Call to Action Section -->
    <section class="cta-section py-5">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-8">
            <h3>Join Our Mission</h3>
            <p class="mb-0">
              Be part of the change you want to see. Join BHRC and help us protect human rights for all.
            </p>
          </div>
          <div class="col-lg-4 text-lg-end">
            <router-link to="/register" class="btn btn-primary btn-lg">
              <i class="fas fa-user-plus me-2"></i>
              Become a Member
            </router-link>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { api } from '@/utils/api'
import { numberUtils } from '@/utils/helpers'

export default {
  name: 'About',
  setup() {
    // Reactive state
    const stats = ref({
      totalCases: 0,
      totalMembers: 0,
      yearsActive: 0
    })

    const workAreas = ref([
      {
        id: 1,
        title: 'Legal Aid',
        description: 'Providing free legal assistance to those who cannot afford it',
        icon: 'fas fa-gavel',
        casesHandled: 250
      },
      {
        id: 2,
        title: 'Women Rights',
        description: 'Protecting and promoting women\'s rights and gender equality',
        icon: 'fas fa-venus',
        casesHandled: 180
      },
      {
        id: 3,
        title: 'Child Protection',
        description: 'Safeguarding children from abuse, exploitation, and neglect',
        icon: 'fas fa-child',
        casesHandled: 120
      },
      {
        id: 4,
        title: 'Minority Rights',
        description: 'Defending the rights of religious and ethnic minorities',
        icon: 'fas fa-users',
        casesHandled: 95
      },
      {
        id: 5,
        title: 'Labor Rights',
        description: 'Ensuring fair treatment and rights for workers',
        icon: 'fas fa-hard-hat',
        casesHandled: 200
      },
      {
        id: 6,
        title: 'Disability Rights',
        description: 'Advocating for the rights of persons with disabilities',
        icon: 'fas fa-wheelchair',
        casesHandled: 75
      }
    ])

    const teamMembers = ref([
      {
        id: 1,
        name: 'Dr. Rajesh Kumar',
        position: 'Chairman & Founder',
        bio: 'A renowned human rights lawyer with over 25 years of experience in constitutional law.',
        image: '/images/team/chairman.jpg',
        credentials: ['LLM', 'PhD Law', 'Senior Advocate'],
        linkedin: 'https://linkedin.com/in/rajeshkumar',
        twitter: 'https://twitter.com/rajeshkumar',
        email: 'chairman@bhrc.org'
      },
      {
        id: 2,
        name: 'Priya Sharma',
        position: 'Executive Director',
        bio: 'Expert in international human rights law and policy advocacy.',
        image: '/images/team/director.jpg',
        credentials: ['LLM Human Rights', 'MA Political Science'],
        linkedin: 'https://linkedin.com/in/priyasharma',
        email: 'director@bhrc.org'
      },
      {
        id: 3,
        name: 'Advocate Suresh Patel',
        position: 'Legal Head',
        bio: 'Specializes in constitutional law and public interest litigation.',
        image: '/images/team/legal-head.jpg',
        credentials: ['LLB', 'LLM Constitutional Law'],
        linkedin: 'https://linkedin.com/in/sureshpatel',
        email: 'legal@bhrc.org'
      }
    ])

    const achievements = ref([
      {
        id: 1,
        number: '1000+',
        title: 'Cases Won',
        description: 'Successful legal victories',
        icon: 'fas fa-trophy'
      },
      {
        id: 2,
        number: '50+',
        title: 'Cities Covered',
        description: 'Across India',
        icon: 'fas fa-map-marker-alt'
      },
      {
        id: 3,
        number: '25+',
        title: 'Awards Received',
        description: 'National & International',
        icon: 'fas fa-medal'
      },
      {
        id: 4,
        number: '100K+',
        title: 'Lives Impacted',
        description: 'Through our initiatives',
        icon: 'fas fa-heart'
      }
    ])

    const timelineEvents = ref([
      {
        id: 1,
        year: '2010',
        title: 'Foundation',
        description: 'BHRC was established with a mission to protect human rights',
        icon: 'fas fa-flag'
      },
      {
        id: 2,
        year: '2012',
        title: 'First Major Victory',
        description: 'Won landmark case on women\'s rights in Supreme Court',
        icon: 'fas fa-gavel'
      },
      {
        id: 3,
        year: '2015',
        title: 'National Recognition',
        description: 'Received National Human Rights Award',
        icon: 'fas fa-award'
      },
      {
        id: 4,
        year: '2018',
        title: 'Digital Platform Launch',
        description: 'Launched online complaint system for better accessibility',
        icon: 'fas fa-laptop'
      },
      {
        id: 5,
        year: '2020',
        title: 'COVID Response',
        description: 'Provided legal aid during pandemic, helping thousands',
        icon: 'fas fa-hands-helping'
      },
      {
        id: 6,
        year: '2023',
        title: 'International Partnership',
        description: 'Partnered with UN Human Rights Council',
        icon: 'fas fa-globe'
      }
    ])

    const partners = ref([
      {
        id: 1,
        name: 'UN Human Rights',
        logo: '/images/partners/un-logo.png'
      },
      {
        id: 2,
        name: 'Amnesty International',
        logo: '/images/partners/amnesty-logo.png'
      },
      {
        id: 3,
        name: 'Human Rights Watch',
        logo: '/images/partners/hrw-logo.png'
      },
      {
        id: 4,
        name: 'National Human Rights Commission',
        logo: '/images/partners/nhrc-logo.png'
      },
      {
        id: 5,
        name: 'Supreme Court Legal Aid',
        logo: '/images/partners/sc-logo.png'
      },
      {
        id: 6,
        name: 'Bar Council of India',
        logo: '/images/partners/bci-logo.png'
      }
    ])

    // Methods
    const formatNumber = (num) => {
      return numberUtils.formatNumber(num)
    }

    const fetchStats = async () => {
      try {
        const response = await api.get('/statistics/overview')
        if (response.data.success) {
          stats.value = {
            totalCases: response.data.data.total_cases || 1250,
            totalMembers: response.data.data.total_members || 5000,
            yearsActive: new Date().getFullYear() - 2010
          }
        }
      } catch (error) {
        console.error('Error fetching stats:', error)
        // Use default values
        stats.value = {
          totalCases: 1250,
          totalMembers: 5000,
          yearsActive: new Date().getFullYear() - 2010
        }
      }
    }

    // Lifecycle
    onMounted(() => {
      fetchStats()
    })

    return {
      // State
      stats,
      workAreas,
      teamMembers,
      achievements,
      timelineEvents,
      partners,
      
      // Methods
      formatNumber
    }
  }
}
</script>

<style scoped>
.about-page {
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

.hero-stats .stat-item {
  text-align: center;
  padding: 1rem;
}

.hero-stats .stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: #007bff;
  display: block;
}

.hero-stats .stat-label {
  font-size: 0.875rem;
  color: #6c757d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.hero-image img {
  border-radius: 1rem;
}

/* Mission & Vision Section */
.mission-vision-section {
  background: white;
}

.mission-card,
.vision-card {
  background: white;
  padding: 2rem;
  border-radius: 1rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
  border-left: 4px solid #007bff;
  height: 100%;
}

.vision-card {
  border-left-color: #28a745;
}

.card-icon {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #007bff, #0056b3);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.5rem;
}

.vision-card .card-icon {
  background: linear-gradient(135deg, #28a745, #1e7e34);
}

.card-icon i {
  font-size: 1.5rem;
  color: white;
}

.mission-card h3,
.vision-card h3 {
  color: #333;
  font-weight: 600;
  margin-bottom: 1rem;
}

.mission-points,
.vision-points {
  list-style: none;
  padding: 0;
}

.mission-points li,
.vision-points li {
  padding: 0.5rem 0;
  position: relative;
  padding-left: 1.5rem;
}

.mission-points li::before,
.vision-points li::before {
  content: '\f00c';
  font-family: 'Font Awesome 5 Free';
  font-weight: 900;
  position: absolute;
  left: 0;
  color: #007bff;
}

.vision-points li::before {
  color: #28a745;
}

/* Work Areas Section */
.work-card {
  background: white;
  padding: 2rem;
  border-radius: 1rem;
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
  text-align: center;
  height: 100%;
  transition: all 0.3s ease;
}

.work-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.work-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #007bff, #0056b3);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
}

.work-icon i {
  font-size: 2rem;
  color: white;
}

.work-card h4 {
  color: #333;
  font-weight: 600;
  margin-bottom: 1rem;
}

.work-stats .badge {
  font-size: 0.75rem;
}

/* Team Section */
.team-card {
  background: white;
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  height: 100%;
}

.team-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.team-image {
  position: relative;
  overflow: hidden;
  height: 250px;
}

.team-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: all 0.3s ease;
}

.team-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 123, 255, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: all 0.3s ease;
}

.team-card:hover .team-overlay {
  opacity: 1;
}

.social-links {
  display: flex;
  gap: 1rem;
}

.social-link {
  width: 40px;
  height: 40px;
  background: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #007bff;
  text-decoration: none;
  transition: all 0.3s ease;
}

.social-link:hover {
  background: #007bff;
  color: white;
  transform: scale(1.1);
}

.team-info {
  padding: 1.5rem;
}

.team-info h5 {
  color: #333;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.team-position {
  color: #007bff;
  font-weight: 500;
  margin-bottom: 1rem;
}

.team-bio {
  color: #6c757d;
  font-size: 0.9rem;
  margin-bottom: 1rem;
}

.team-credentials .badge {
  font-size: 0.75rem;
  margin-bottom: 0.25rem;
}

/* Achievements Section */
.achievement-card {
  text-align: center;
  padding: 2rem 1rem;
}

.achievement-icon {
  width: 80px;
  height: 80px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
}

.achievement-icon i {
  font-size: 2rem;
  color: white;
}

.achievement-number {
  font-size: 2.5rem;
  font-weight: 700;
  color: white;
  margin-bottom: 0.5rem;
}

.achievement-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: white;
  margin-bottom: 0.5rem;
}

.achievement-description {
  color: rgba(255, 255, 255, 0.8);
  font-size: 0.9rem;
}

/* Timeline Section */
.timeline {
  position: relative;
  max-width: 800px;
  margin: 0 auto;
}

.timeline::before {
  content: '';
  position: absolute;
  top: 0;
  left: 50%;
  width: 2px;
  height: 100%;
  background: #007bff;
  transform: translateX(-50%);
}

.timeline-item {
  position: relative;
  margin-bottom: 3rem;
  width: 50%;
}

.timeline-item-right {
  left: 50%;
}

.timeline-marker {
  position: absolute;
  top: 0;
  width: 40px;
  height: 40px;
  background: #007bff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1rem;
}

.timeline-item .timeline-marker {
  right: -20px;
}

.timeline-item-right .timeline-marker {
  left: -20px;
}

.timeline-content {
  background: white;
  padding: 1.5rem;
  border-radius: 0.5rem;
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
  margin-right: 2rem;
}

.timeline-item-right .timeline-content {
  margin-left: 2rem;
  margin-right: 0;
}

.timeline-year {
  color: #007bff;
  font-weight: 600;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
}

.timeline-content h4 {
  color: #333;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

/* Partners Section */
.partner-logo {
  text-align: center;
  padding: 1rem;
  background: white;
  border-radius: 0.5rem;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.partner-logo:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15);
}

.partner-logo img {
  max-height: 60px;
  width: auto;
  filter: grayscale(100%);
  transition: all 0.3s ease;
}

.partner-logo:hover img {
  filter: grayscale(0%);
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
  
  .timeline::before {
    left: 20px;
  }
  
  .timeline-item,
  .timeline-item-right {
    width: 100%;
    left: 0;
  }
  
  .timeline-item .timeline-marker,
  .timeline-item-right .timeline-marker {
    left: 0;
  }
  
  .timeline-content,
  .timeline-item-right .timeline-content {
    margin-left: 3rem;
    margin-right: 0;
  }
}

@media (max-width: 767.98px) {
  .hero-title {
    font-size: 2rem;
  }
  
  .section-title {
    font-size: 2rem;
  }
  
  .hero-stats .stat-number {
    font-size: 1.5rem;
  }
  
  .achievement-number {
    font-size: 2rem;
  }
}

/* Animations */
.about-page {
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
.min-vh-50 {
  min-height: 50vh;
}

.rounded-lg {
  border-radius: 1rem !important;
}

.bg-outline-primary {
  background-color: transparent;
  border: 1px solid #007bff;
  color: #007bff;
}
</style>