<template>
  <div class="activities-page">
    <div class="container">
      <!-- Page Header -->
      <div class="page-header">
        <h1 class="page-title">Our Activities</h1>
        <p class="page-subtitle">
          Discover the various initiatives and programs we undertake to protect and promote human rights
        </p>
      </div>

      <!-- Filter Tabs -->
      <div class="filter-tabs">
        <ul class="nav nav-pills justify-content-center">
          <li class="nav-item">
            <button 
              class="nav-link" 
              :class="{ active: activeFilter === 'all' }"
              @click="setFilter('all')"
            >
              All Activities
            </button>
          </li>
          <li class="nav-item">
            <button 
              class="nav-link" 
              :class="{ active: activeFilter === 'legal' }"
              @click="setFilter('legal')"
            >
              Legal Aid
            </button>
          </li>
          <li class="nav-item">
            <button 
              class="nav-link" 
              :class="{ active: activeFilter === 'awareness' }"
              @click="setFilter('awareness')"
            >
              Awareness Programs
            </button>
          </li>
          <li class="nav-item">
            <button 
              class="nav-link" 
              :class="{ active: activeFilter === 'community' }"
              @click="setFilter('community')"
            >
              Community Outreach
            </button>
          </li>
        </ul>
      </div>

      <!-- Activities Grid -->
      <div class="activities-grid">
        <div class="row">
          <div 
            v-for="activity in filteredActivities" 
            :key="activity.id"
            class="col-lg-4 col-md-6 mb-4"
          >
            <div class="activity-card">
              <div class="activity-image">
                <img :src="activity.image" :alt="activity.title" class="img-fluid">
                <div class="activity-category">
                  <span class="category-badge" :class="activity.category">
                    {{ getCategoryName(activity.category) }}
                  </span>
                </div>
              </div>
              
              <div class="activity-content">
                <h3 class="activity-title">{{ activity.title }}</h3>
                <p class="activity-description">{{ activity.description }}</p>
                
                <div class="activity-meta">
                  <div class="meta-item">
                    <i class="fas fa-calendar"></i>
                    <span>{{ formatDate(activity.date) }}</span>
                  </div>
                  <div class="meta-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>{{ activity.location }}</span>
                  </div>
                  <div class="meta-item">
                    <i class="fas fa-users"></i>
                    <span>{{ activity.participants }} participants</span>
                  </div>
                </div>
                
                <div class="activity-actions">
                  <button class="btn btn-primary" @click="viewDetails(activity)">
                    View Details
                  </button>
                  <button class="btn btn-outline-secondary" @click="shareActivity(activity)">
                    <i class="fas fa-share"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Load More Button -->
      <div class="text-center mt-5" v-if="hasMoreActivities">
        <button class="btn btn-outline-primary btn-lg" @click="loadMoreActivities">
          Load More Activities
        </button>
      </div>

      <!-- Activity Details Modal -->
      <div class="modal fade" id="activityModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">{{ selectedActivity?.title }}</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" v-if="selectedActivity">
              <img :src="selectedActivity.image" :alt="selectedActivity.title" class="img-fluid mb-3">
              <p class="lead">{{ selectedActivity.description }}</p>
              <div class="activity-details">
                <h6>Activity Details:</h6>
                <ul>
                  <li><strong>Date:</strong> {{ formatDate(selectedActivity.date) }}</li>
                  <li><strong>Location:</strong> {{ selectedActivity.location }}</li>
                  <li><strong>Participants:</strong> {{ selectedActivity.participants }}</li>
                  <li><strong>Category:</strong> {{ getCategoryName(selectedActivity.category) }}</li>
                </ul>
              </div>
              <div class="activity-impact" v-if="selectedActivity.impact">
                <h6>Impact:</h6>
                <p>{{ selectedActivity.impact }}</p>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="button" class="btn btn-primary" @click="shareActivity(selectedActivity)">
                Share Activity
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Activities',
  data() {
    return {
      activeFilter: 'all',
      selectedActivity: null,
      hasMoreActivities: true,
      activities: [
        {
          id: 1,
          title: 'Free Legal Aid Camp',
          description: 'Providing free legal consultation and assistance to underprivileged communities in rural areas.',
          category: 'legal',
          date: '2024-01-15',
          location: 'Patna, Bihar',
          participants: 150,
          image: '/assets/images/activities/legal-aid.jpg',
          impact: 'Successfully provided legal assistance to 150 families, helping them understand their rights and legal procedures.'
        },
        {
          id: 2,
          title: 'Human Rights Awareness Workshop',
          description: 'Educational workshop on fundamental human rights and constitutional provisions for students and teachers.',
          category: 'awareness',
          date: '2024-01-20',
          location: 'Gaya, Bihar',
          participants: 200,
          image: '/assets/images/activities/awareness.jpg',
          impact: 'Educated 200 participants about their fundamental rights and how to seek help in case of violations.'
        },
        {
          id: 3,
          title: 'Community Health Initiative',
          description: 'Healthcare awareness program focusing on preventive care and health rights in marginalized communities.',
          category: 'community',
          date: '2024-01-25',
          location: 'Muzaffarpur, Bihar',
          participants: 300,
          image: '/assets/images/activities/health.jpg',
          impact: 'Reached 300 community members with essential healthcare information and connected them with local health services.'
        },
        {
          id: 4,
          title: 'Women Rights Seminar',
          description: 'Empowerment seminar focusing on women\'s rights, safety, and legal protections available.',
          category: 'awareness',
          date: '2024-02-01',
          location: 'Bhagalpur, Bihar',
          participants: 120,
          image: '/assets/images/activities/women-rights.jpg',
          impact: 'Empowered 120 women with knowledge about their rights and available support systems.'
        },
        {
          id: 5,
          title: 'Child Protection Drive',
          description: 'Community outreach program to raise awareness about child rights and protection mechanisms.',
          category: 'community',
          date: '2024-02-05',
          location: 'Darbhanga, Bihar',
          participants: 180,
          image: '/assets/images/activities/child-protection.jpg',
          impact: 'Created awareness among 180 community members about child protection laws and reporting mechanisms.'
        },
        {
          id: 6,
          title: 'Legal Literacy Program',
          description: 'Educational program to improve legal literacy among rural populations and marginalized communities.',
          category: 'legal',
          date: '2024-02-10',
          location: 'Purnia, Bihar',
          participants: 250,
          image: '/assets/images/activities/legal-literacy.jpg',
          impact: 'Enhanced legal awareness of 250 participants, enabling them to better navigate legal processes.'
        }
      ]
    }
  },
  computed: {
    filteredActivities() {
      if (this.activeFilter === 'all') {
        return this.activities
      }
      return this.activities.filter(activity => activity.category === this.activeFilter)
    }
  },
  methods: {
    setFilter(filter) {
      this.activeFilter = filter
    },
    getCategoryName(category) {
      const categories = {
        legal: 'Legal Aid',
        awareness: 'Awareness',
        community: 'Community Outreach'
      }
      return categories[category] || category
    },
    formatDate(dateString) {
      const options = { year: 'numeric', month: 'long', day: 'numeric' }
      return new Date(dateString).toLocaleDateString('en-US', options)
    },
    viewDetails(activity) {
      this.selectedActivity = activity
      // Show modal (assuming Bootstrap is available)
      const modal = new bootstrap.Modal(document.getElementById('activityModal'))
      modal.show()
    },
    shareActivity(activity) {
      if (navigator.share) {
        navigator.share({
          title: activity.title,
          text: activity.description,
          url: window.location.href
        })
      } else {
        // Fallback for browsers that don't support Web Share API
        const text = `Check out this activity: ${activity.title} - ${activity.description}`
        navigator.clipboard.writeText(text).then(() => {
          alert('Activity details copied to clipboard!')
        })
      }
    },
    loadMoreActivities() {
      // Simulate loading more activities
      this.hasMoreActivities = false
    }
  },
  mounted() {
    document.title = 'Our Activities - BHRC'
  }
}
</script>

<style scoped>
.activities-page {
  padding: 2rem 0;
  background-color: #f8f9fa;
}

.page-header {
  text-align: center;
  margin-bottom: 3rem;
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
  max-width: 800px;
  margin: 0 auto;
}

.filter-tabs {
  margin-bottom: 3rem;
}

.nav-pills .nav-link {
  background: white;
  color: #6c757d;
  border: 2px solid #e9ecef;
  margin: 0 0.5rem;
  border-radius: 2rem;
  padding: 0.75rem 1.5rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

.nav-pills .nav-link:hover {
  background: #667eea;
  color: white;
  border-color: #667eea;
}

.nav-pills .nav-link.active {
  background: #667eea;
  color: white;
  border-color: #667eea;
}

.activities-grid {
  margin-bottom: 3rem;
}

.activity-card {
  background: white;
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  height: 100%;
}

.activity-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.activity-image {
  position: relative;
  height: 200px;
  overflow: hidden;
}

.activity-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.activity-card:hover .activity-image img {
  transform: scale(1.05);
}

.activity-category {
  position: absolute;
  top: 1rem;
  right: 1rem;
}

.category-badge {
  padding: 0.5rem 1rem;
  border-radius: 2rem;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.category-badge.legal {
  background: #28a745;
  color: white;
}

.category-badge.awareness {
  background: #17a2b8;
  color: white;
}

.category-badge.community {
  background: #ffc107;
  color: #212529;
}

.activity-content {
  padding: 1.5rem;
}

.activity-title {
  font-size: 1.3rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 1rem;
  line-height: 1.3;
}

.activity-description {
  color: #6c757d;
  line-height: 1.6;
  margin-bottom: 1.5rem;
}

.activity-meta {
  margin-bottom: 1.5rem;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  color: #6c757d;
}

.meta-item i {
  color: #667eea;
  width: 16px;
}

.activity-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.btn {
  border-radius: 0.5rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-primary {
  background: #667eea;
  border-color: #667eea;
  flex: 1;
}

.btn-primary:hover {
  background: #5a6fd8;
  border-color: #5a6fd8;
}

.btn-outline-secondary {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
}

.modal-content {
  border-radius: 1rem;
  border: none;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.modal-header {
  border-bottom: 1px solid #e9ecef;
  padding: 1.5rem;
}

.modal-title {
  font-weight: 600;
  color: #2c3e50;
}

.modal-body {
  padding: 1.5rem;
}

.activity-details ul {
  list-style: none;
  padding: 0;
}

.activity-details li {
  padding: 0.5rem 0;
  border-bottom: 1px solid #f8f9fa;
}

.activity-details li:last-child {
  border-bottom: none;
}

.activity-impact {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 0.5rem;
  margin-top: 1rem;
}

@media (max-width: 768px) {
  .page-title {
    font-size: 2rem;
  }
  
  .nav-pills .nav-link {
    margin: 0.25rem;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
  }
  
  .activity-content {
    padding: 1rem;
  }
  
  .activity-title {
    font-size: 1.1rem;
  }
  
  .activity-actions {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .btn-outline-secondary {
    width: 100%;
    height: auto;
    padding: 0.5rem;
  }
}
</style>