<template>
  <div class="mission-vision-page">
    <div class="container">
      <div class="page-header">
        <h1 class="page-title">Mission & Vision</h1>
        <p class="page-subtitle">Our commitment to human rights and social justice</p>
      </div>

      <div class="content-sections">
        <!-- Mission Section -->
        <div class="section mission-section">
          <div class="row align-items-center">
            <div class="col-lg-6">
              <div class="section-content">
                <h2 class="section-title">Our Mission</h2>
                <p class="section-text">
                  To protect, promote and defend human rights for all individuals, ensuring dignity, 
                  equality and justice in society. We work tirelessly to create awareness about 
                  human rights violations and provide support to those in need.
                </p>
                <ul class="mission-points">
                  <li>Advocate for human rights protection</li>
                  <li>Provide legal assistance to victims</li>
                  <li>Raise awareness about human rights issues</li>
                  <li>Support marginalized communities</li>
                </ul>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="section-image">
                <img src="@/assets/images/mission.svg" alt="Our Mission" class="img-fluid">
              </div>
            </div>
          </div>
        </div>

        <!-- Vision Section -->
        <div class="section vision-section">
          <div class="row align-items-center">
            <div class="col-lg-6 order-lg-2">
              <div class="section-content">
                <h2 class="section-title">Our Vision</h2>
                <p class="section-text">
                  A world where human rights are universally respected, protected and fulfilled. 
                  We envision a society free from discrimination, violence and injustice, where 
                  every individual can live with dignity and freedom.
                </p>
                <div class="vision-goals">
                  <div class="goal-item">
                    <i class="fas fa-balance-scale"></i>
                    <span>Equal Justice for All</span>
                  </div>
                  <div class="goal-item">
                    <i class="fas fa-hands-helping"></i>
                    <span>Community Support</span>
                  </div>
                  <div class="goal-item">
                    <i class="fas fa-globe"></i>
                    <span>Global Human Rights</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-6 order-lg-1">
              <div class="section-image">
                <img src="@/assets/images/vision.svg" alt="Our Vision" class="img-fluid">
              </div>
            </div>
          </div>
        </div>

        <!-- Values Section -->
        <div class="section values-section">
          <div class="text-center mb-5">
            <h2 class="section-title">Our Core Values</h2>
            <p class="section-subtitle">The principles that guide our work</p>
          </div>
          
          <div class="row">
            <div class="col-md-4 mb-4">
              <div class="value-card">
                <div class="value-icon">
                  <i class="fas fa-heart"></i>
                </div>
                <h3 class="value-title">Compassion</h3>
                <p class="value-description">
                  We approach every case with empathy and understanding, 
                  recognizing the human dignity in every individual.
                </p>
              </div>
            </div>
            
            <div class="col-md-4 mb-4">
              <div class="value-card">
                <div class="value-icon">
                  <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="value-title">Integrity</h3>
                <p class="value-description">
                  We maintain the highest ethical standards in all our 
                  actions and decisions, ensuring transparency and accountability.
                </p>
              </div>
            </div>
            
            <div class="col-md-4 mb-4">
              <div class="value-card">
                <div class="value-icon">
                  <i class="fas fa-fist-raised"></i>
                </div>
                <h3 class="value-title">Courage</h3>
                <p class="value-description">
                  We stand up against injustice and speak for those who 
                  cannot speak for themselves, regardless of the challenges.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'MissionVision',
  mounted() {
    document.title = 'Mission & Vision - BHRC'
  }
}
</script>

<style scoped>
.mission-vision-page {
  padding: 2rem 0;
  background-color: #f8f9fa;
}

.page-header {
  text-align: center;
  margin-bottom: 4rem;
}

.page-title {
  font-size: 3rem;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 1rem;
}

.page-subtitle {
  font-size: 1.2rem;
  color: #6c757d;
  max-width: 600px;
  margin: 0 auto;
}

.section {
  margin-bottom: 5rem;
  padding: 3rem 0;
}

.mission-section {
  background: white;
  border-radius: 1rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  padding: 4rem 2rem;
}

.vision-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 1rem;
  color: white;
  padding: 4rem 2rem;
}

.values-section {
  padding: 4rem 2rem;
}

.section-title {
  font-size: 2.5rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  color: inherit;
}

.section-subtitle {
  font-size: 1.1rem;
  opacity: 0.8;
  margin-bottom: 2rem;
}

.section-text {
  font-size: 1.1rem;
  line-height: 1.8;
  margin-bottom: 2rem;
}

.mission-points {
  list-style: none;
  padding: 0;
}

.mission-points li {
  padding: 0.5rem 0;
  position: relative;
  padding-left: 2rem;
}

.mission-points li::before {
  content: '✓';
  position: absolute;
  left: 0;
  color: #28a745;
  font-weight: bold;
  font-size: 1.2rem;
}

.section-image {
  text-align: center;
}

.section-image img {
  max-height: 400px;
  width: auto;
}

.vision-goals {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.goal-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 0.5rem;
  backdrop-filter: blur(10px);
}

.goal-item i {
  font-size: 1.5rem;
  color: #ffd700;
}

.value-card {
  background: white;
  padding: 2rem;
  border-radius: 1rem;
  text-align: center;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  height: 100%;
}

.value-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.value-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
}

.value-icon i {
  font-size: 2rem;
  color: white;
}

.value-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 1rem;
}

.value-description {
  color: #6c757d;
  line-height: 1.6;
}

@media (max-width: 768px) {
  .page-title {
    font-size: 2rem;
  }
  
  .section-title {
    font-size: 2rem;
  }
  
  .section {
    margin-bottom: 3rem;
    padding: 2rem 1rem;
  }
  
  .mission-section,
  .vision-section {
    padding: 2rem 1rem;
  }
  
  .vision-goals {
    margin-top: 2rem;
  }
  
  .goal-item {
    flex-direction: column;
    text-align: center;
    gap: 0.5rem;
  }
}
</style>