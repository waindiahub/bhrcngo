<template>
  <div class="founder-page">
    <div class="container">
      <!-- Hero Section -->
      <div class="hero-section">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <div class="founder-content">
              <h1 class="founder-title">Meet Our Founder</h1>
              <p class="founder-subtitle">
                A visionary leader dedicated to human rights and social justice
              </p>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="founder-image-container">
              <div class="founder-image">
                <img src="@/assets/images/founder.jpg" alt="Founder" class="img-fluid">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Founder Details -->
      <div class="founder-details">
        <div class="row">
          <div class="col-lg-8 mx-auto">
            <div class="founder-bio">
              <h2 class="bio-title">Dr. [Founder Name]</h2>
              <p class="bio-designation">Founder & Chairman, Bihar Human Rights Commission</p>
              
              <div class="bio-content">
                <p>
                  With over two decades of experience in human rights advocacy and social justice, 
                  our founder has dedicated their life to protecting the rights of the marginalized 
                  and ensuring equal justice for all. Their vision led to the establishment of the 
                  Bihar Human Rights Commission, which has become a beacon of hope for countless individuals.
                </p>
                
                <p>
                  Born and raised in Bihar, they witnessed firsthand the challenges faced by 
                  underprivileged communities. This experience ignited a passion for human rights 
                  that would shape their entire career. After completing their education in law 
                  and social work, they began working with various NGOs and legal aid organizations.
                </p>
                
                <p>
                  Their relentless efforts in fighting for justice, combined with their deep 
                  understanding of legal frameworks and social dynamics, made them a respected 
                  figure in the human rights community. The establishment of BHRC was the 
                  culmination of years of grassroots work and advocacy.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Achievements Section -->
      <div class="achievements-section">
        <div class="text-center mb-5">
          <h2 class="section-title">Key Achievements</h2>
          <p class="section-subtitle">Milestones in the journey of human rights advocacy</p>
        </div>
        
        <div class="row">
          <div class="col-md-6 col-lg-3 mb-4">
            <div class="achievement-card">
              <div class="achievement-icon">
                <i class="fas fa-gavel"></i>
              </div>
              <div class="achievement-number">500+</div>
              <div class="achievement-text">Cases Resolved</div>
            </div>
          </div>
          
          <div class="col-md-6 col-lg-3 mb-4">
            <div class="achievement-card">
              <div class="achievement-icon">
                <i class="fas fa-users"></i>
              </div>
              <div class="achievement-number">10,000+</div>
              <div class="achievement-text">Lives Impacted</div>
            </div>
          </div>
          
          <div class="col-md-6 col-lg-3 mb-4">
            <div class="achievement-card">
              <div class="achievement-icon">
                <i class="fas fa-award"></i>
              </div>
              <div class="achievement-number">25+</div>
              <div class="achievement-text">Awards Received</div>
            </div>
          </div>
          
          <div class="col-md-6 col-lg-3 mb-4">
            <div class="achievement-card">
              <div class="achievement-icon">
                <i class="fas fa-calendar"></i>
              </div>
              <div class="achievement-number">20+</div>
              <div class="achievement-text">Years of Service</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Vision & Message -->
      <div class="message-section">
        <div class="row">
          <div class="col-lg-10 mx-auto">
            <div class="message-card">
              <div class="quote-icon">
                <i class="fas fa-quote-left"></i>
              </div>
              <blockquote class="founder-message">
                "Human rights are not a privilege to be granted; they are a birthright to be protected. 
                Every individual, regardless of their background, deserves to live with dignity and freedom. 
                Our mission at BHRC is to ensure that this fundamental truth is upheld for every person in Bihar."
              </blockquote>
              <div class="message-author">
                <strong>- Dr. [Founder Name]</strong>
                <span>Founder & Chairman, BHRC</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Education & Background -->
      <div class="background-section">
        <div class="row">
          <div class="col-lg-6">
            <div class="background-card">
              <h3 class="card-title">
                <i class="fas fa-graduation-cap"></i>
                Education
              </h3>
              <ul class="education-list">
                <li>
                  <strong>LL.M in Human Rights Law</strong>
                  <span>National Law University, Delhi</span>
                </li>
                <li>
                  <strong>Master's in Social Work</strong>
                  <span>Tata Institute of Social Sciences, Mumbai</span>
                </li>
                <li>
                  <strong>Bachelor of Laws (LL.B)</strong>
                  <span>Patna University, Bihar</span>
                </li>
              </ul>
            </div>
          </div>
          
          <div class="col-lg-6">
            <div class="background-card">
              <h3 class="card-title">
                <i class="fas fa-briefcase"></i>
                Professional Experience
              </h3>
              <ul class="experience-list">
                <li>
                  <strong>Founder & Chairman</strong>
                  <span>Bihar Human Rights Commission (2010 - Present)</span>
                </li>
                <li>
                  <strong>Senior Legal Advisor</strong>
                  <span>National Human Rights Commission (2005 - 2010)</span>
                </li>
                <li>
                  <strong>Human Rights Lawyer</strong>
                  <span>Supreme Court of India (2000 - 2005)</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Founder',
  mounted() {
    document.title = 'Our Founder - BHRC'
  }
}
</script>

<style scoped>
.founder-page {
  padding: 2rem 0;
}

.hero-section {
  padding: 4rem 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 1rem;
  margin-bottom: 4rem;
}

.founder-content {
  padding: 2rem;
}

.founder-title {
  font-size: 3.5rem;
  font-weight: 700;
  margin-bottom: 1rem;
  line-height: 1.2;
}

.founder-subtitle {
  font-size: 1.3rem;
  opacity: 0.9;
  line-height: 1.6;
}

.founder-image-container {
  text-align: center;
  padding: 2rem;
}

.founder-image {
  position: relative;
  display: inline-block;
}

.founder-image img {
  width: 300px;
  height: 300px;
  object-fit: cover;
  border-radius: 50%;
  border: 8px solid rgba(255, 255, 255, 0.2);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.founder-details {
  margin-bottom: 5rem;
}

.founder-bio {
  background: white;
  padding: 3rem;
  border-radius: 1rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.bio-title {
  font-size: 2.5rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.bio-designation {
  font-size: 1.2rem;
  color: #667eea;
  font-weight: 500;
  margin-bottom: 2rem;
}

.bio-content p {
  font-size: 1.1rem;
  line-height: 1.8;
  color: #555;
  margin-bottom: 1.5rem;
}

.achievements-section {
  margin-bottom: 5rem;
  padding: 4rem 0;
  background-color: #f8f9fa;
  border-radius: 1rem;
}

.section-title {
  font-size: 2.5rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 1rem;
}

.section-subtitle {
  font-size: 1.1rem;
  color: #6c757d;
  margin-bottom: 3rem;
}

.achievement-card {
  background: white;
  padding: 2rem;
  border-radius: 1rem;
  text-align: center;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease;
  height: 100%;
}

.achievement-card:hover {
  transform: translateY(-5px);
}

.achievement-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
}

.achievement-icon i {
  font-size: 2rem;
  color: white;
}

.achievement-number {
  font-size: 2.5rem;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.achievement-text {
  font-size: 1.1rem;
  color: #6c757d;
  font-weight: 500;
}

.message-section {
  margin-bottom: 5rem;
}

.message-card {
  background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
  color: white;
  padding: 4rem;
  border-radius: 1rem;
  text-align: center;
  position: relative;
}

.quote-icon {
  font-size: 3rem;
  opacity: 0.3;
  margin-bottom: 2rem;
}

.founder-message {
  font-size: 1.3rem;
  line-height: 1.8;
  font-style: italic;
  margin-bottom: 2rem;
  border: none;
}

.message-author {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.message-author strong {
  font-size: 1.2rem;
}

.message-author span {
  opacity: 0.8;
}

.background-section {
  margin-bottom: 3rem;
}

.background-card {
  background: white;
  padding: 2.5rem;
  border-radius: 1rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  height: 100%;
  margin-bottom: 2rem;
}

.card-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 2rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.card-title i {
  color: #667eea;
}

.education-list,
.experience-list {
  list-style: none;
  padding: 0;
}

.education-list li,
.experience-list li {
  padding: 1rem 0;
  border-bottom: 1px solid #eee;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.education-list li:last-child,
.experience-list li:last-child {
  border-bottom: none;
}

.education-list strong,
.experience-list strong {
  color: #2c3e50;
  font-size: 1.1rem;
}

.education-list span,
.experience-list span {
  color: #6c757d;
  font-size: 0.95rem;
}

@media (max-width: 768px) {
  .founder-title {
    font-size: 2.5rem;
  }
  
  .founder-image img {
    width: 250px;
    height: 250px;
  }
  
  .founder-bio {
    padding: 2rem;
  }
  
  .bio-title {
    font-size: 2rem;
  }
  
  .message-card {
    padding: 2rem;
  }
  
  .founder-message {
    font-size: 1.1rem;
  }
  
  .background-card {
    padding: 2rem;
  }
}
</style>