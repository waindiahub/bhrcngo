<template>
  <DefaultLayout>
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="hero-content">
          <h1 class="hero-title">State Members</h1>
          <p class="hero-subtitle">
            Meet our dedicated state-level members working tirelessly for human rights across different states
          </p>
          <div class="hero-stats">
            <div class="stat-item">
              <span class="stat-number">{{ totalMembers }}</span>
              <span class="stat-label">State Members</span>
            </div>
            <div class="stat-item">
              <span class="stat-number">{{ totalStates }}</span>
              <span class="stat-label">States Covered</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Filters Section -->
    <section class="filters-section">
      <div class="container">
        <div class="filters-wrapper">
          <!-- Search -->
          <div class="search-box">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search members by name, state, or specialization..."
              class="search-input"
            >
            <i class="fas fa-search search-icon"></i>
          </div>

          <!-- State Filter -->
          <div class="filter-group">
            <select v-model="selectedState" class="filter-select">
              <option value="">All States</option>
              <option v-for="state in states" :key="state" :value="state">
                {{ state }}
              </option>
            </select>
          </div>

          <!-- Specialization Filter -->
          <div class="filter-group">
            <select v-model="selectedSpecialization" class="filter-select">
              <option value="">All Specializations</option>
              <option v-for="spec in specializations" :key="spec" :value="spec">
                {{ spec }}
              </option>
            </select>
          </div>

          <!-- Sort -->
          <div class="filter-group">
            <select v-model="sortBy" class="filter-select">
              <option value="name">Sort by Name</option>
              <option value="state">Sort by State</option>
              <option value="experience">Sort by Experience</option>
              <option value="joined_date">Sort by Join Date</option>
            </select>
          </div>
        </div>
      </div>
    </section>

    <!-- Loading State -->
    <div v-if="loading" class="loading-section">
      <div class="container">
        <div class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i>
          <p>Loading state members...</p>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-section">
      <div class="container">
        <div class="error-message">
          <i class="fas fa-exclamation-triangle"></i>
          <h3>Error Loading Members</h3>
          <p>{{ error }}</p>
          <button @click="fetchMembers" class="retry-btn">
            <i class="fas fa-redo"></i>
            Try Again
          </button>
        </div>
      </div>
    </div>

    <!-- Members Grid -->
    <section v-else class="members-section">
      <div class="container">
        <!-- Results Info -->
        <div class="results-info">
          <p>
            Showing {{ filteredMembers.length }} of {{ totalMembers }} state members
            <span v-if="searchQuery || selectedState || selectedSpecialization">
              (filtered)
            </span>
          </p>
        </div>

        <!-- Members Grid -->
        <div v-if="filteredMembers.length > 0" class="members-grid">
          <div
            v-for="member in paginatedMembers"
            :key="member.id"
            class="member-card"
            @click="openMemberModal(member)"
          >
            <div class="member-photo">
              <img
                :src="member.photo || '/images/default-avatar.jpg'"
                :alt="member.name"
                @error="handleImageError"
              >
              <div class="member-badge">State</div>
            </div>
            <div class="member-info">
              <h3 class="member-name">{{ member.name }}</h3>
              <p class="member-designation">{{ member.designation }}</p>
              <div class="member-details">
                <div class="detail-item">
                  <i class="fas fa-map-marker-alt"></i>
                  <span>{{ member.state }}, {{ member.city }}</span>
                </div>
                <div class="detail-item">
                  <i class="fas fa-briefcase"></i>
                  <span>{{ member.experience }} years experience</span>
                </div>
                <div class="detail-item">
                  <i class="fas fa-star"></i>
                  <span>{{ member.specialization }}</span>
                </div>
              </div>
              <div class="member-contact">
                <a :href="`mailto:${member.email}`" class="contact-btn">
                  <i class="fas fa-envelope"></i>
                </a>
                <a :href="`tel:${member.phone}`" class="contact-btn">
                  <i class="fas fa-phone"></i>
                </a>
                <button class="view-profile-btn">
                  <i class="fas fa-user"></i>
                  View Profile
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- No Results -->
        <div v-else class="no-results">
          <i class="fas fa-users"></i>
          <h3>No Members Found</h3>
          <p>
            <span v-if="searchQuery || selectedState || selectedSpecialization">
              No state members match your current filters. Try adjusting your search criteria.
            </span>
            <span v-else>
              No state members are currently available.
            </span>
          </p>
          <button
            v-if="searchQuery || selectedState || selectedSpecialization"
            @click="clearFilters"
            class="clear-filters-btn"
          >
            Clear Filters
          </button>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="pagination">
          <button
            @click="currentPage = 1"
            :disabled="currentPage === 1"
            class="page-btn"
          >
            <i class="fas fa-angle-double-left"></i>
          </button>
          <button
            @click="currentPage--"
            :disabled="currentPage === 1"
            class="page-btn"
          >
            <i class="fas fa-angle-left"></i>
          </button>
          
          <span class="page-info">
            Page {{ currentPage }} of {{ totalPages }}
          </span>
          
          <button
            @click="currentPage++"
            :disabled="currentPage === totalPages"
            class="page-btn"
          >
            <i class="fas fa-angle-right"></i>
          </button>
          <button
            @click="currentPage = totalPages"
            :disabled="currentPage === totalPages"
            class="page-btn"
          >
            <i class="fas fa-angle-double-right"></i>
          </button>
        </div>
      </div>
    </section>

    <!-- Member Detail Modal -->
    <div v-if="selectedMember" class="modal-overlay" @click="closeMemberModal">
      <div class="modal-content" @click.stop>
        <button @click="closeMemberModal" class="modal-close">
          <i class="fas fa-times"></i>
        </button>
        
        <div class="modal-header">
          <div class="member-photo-large">
            <img
              :src="selectedMember.photo || '/images/default-avatar.jpg'"
              :alt="selectedMember.name"
              @error="handleImageError"
            >
          </div>
          <div class="member-info-large">
            <h2>{{ selectedMember.name }}</h2>
            <p class="designation">{{ selectedMember.designation }}</p>
            <div class="member-badges">
              <span class="badge state-badge">State Member</span>
              <span class="badge experience-badge">{{ selectedMember.experience }}+ Years</span>
            </div>
          </div>
        </div>

        <div class="modal-body">
          <div class="info-section">
            <h3>Contact Information</h3>
            <div class="info-grid">
              <div class="info-item">
                <i class="fas fa-envelope"></i>
                <span>{{ selectedMember.email }}</span>
              </div>
              <div class="info-item">
                <i class="fas fa-phone"></i>
                <span>{{ selectedMember.phone }}</span>
              </div>
              <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <span>{{ selectedMember.state }}, {{ selectedMember.city }}</span>
              </div>
              <div class="info-item">
                <i class="fas fa-calendar"></i>
                <span>Joined: {{ formatDate(selectedMember.joined_date) }}</span>
              </div>
            </div>
          </div>

          <div class="info-section">
            <h3>Professional Details</h3>
            <div class="info-grid">
              <div class="info-item">
                <i class="fas fa-star"></i>
                <span>{{ selectedMember.specialization }}</span>
              </div>
              <div class="info-item">
                <i class="fas fa-briefcase"></i>
                <span>{{ selectedMember.experience }} years experience</span>
              </div>
              <div class="info-item">
                <i class="fas fa-graduation-cap"></i>
                <span>{{ selectedMember.education || 'Not specified' }}</span>
              </div>
            </div>
          </div>

          <div v-if="selectedMember.bio" class="info-section">
            <h3>Biography</h3>
            <p class="bio-text">{{ selectedMember.bio }}</p>
          </div>

          <div v-if="selectedMember.achievements" class="info-section">
            <h3>Key Achievements</h3>
            <ul class="achievements-list">
              <li v-for="achievement in selectedMember.achievements.split(';')" :key="achievement">
                {{ achievement.trim() }}
              </li>
            </ul>
          </div>
        </div>

        <div class="modal-footer">
          <a :href="`mailto:${selectedMember.email}`" class="contact-btn primary">
            <i class="fas fa-envelope"></i>
            Send Email
          </a>
          <a :href="`tel:${selectedMember.phone}`" class="contact-btn secondary">
            <i class="fas fa-phone"></i>
            Call Now
          </a>
        </div>
      </div>
    </div>
  </DefaultLayout>
</template>

<script>
import DefaultLayout from '@/layouts/DefaultLayout.vue'

export default {
  name: 'StateMembers',
  components: {
    DefaultLayout
  },
  data() {
    return {
      members: [],
      loading: true,
      error: null,
      searchQuery: '',
      selectedState: '',
      selectedSpecialization: '',
      sortBy: 'name',
      currentPage: 1,
      itemsPerPage: 12,
      selectedMember: null,
      states: [],
      specializations: []
    }
  },
  computed: {
    totalMembers() {
      return this.members.length
    },
    totalStates() {
      return new Set(this.members.map(member => member.state)).size
    },
    filteredMembers() {
      let filtered = [...this.members]

      // Search filter
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(member =>
          member.name.toLowerCase().includes(query) ||
          member.state.toLowerCase().includes(query) ||
          member.specialization.toLowerCase().includes(query) ||
          member.designation.toLowerCase().includes(query)
        )
      }

      // State filter
      if (this.selectedState) {
        filtered = filtered.filter(member => member.state === this.selectedState)
      }

      // Specialization filter
      if (this.selectedSpecialization) {
        filtered = filtered.filter(member => member.specialization === this.selectedSpecialization)
      }

      // Sort
      filtered.sort((a, b) => {
        switch (this.sortBy) {
          case 'name':
            return a.name.localeCompare(b.name)
          case 'state':
            return a.state.localeCompare(b.state)
          case 'experience':
            return b.experience - a.experience
          case 'joined_date':
            return new Date(b.joined_date) - new Date(a.joined_date)
          default:
            return 0
        }
      })

      return filtered
    },
    totalPages() {
      return Math.ceil(this.filteredMembers.length / this.itemsPerPage)
    },
    paginatedMembers() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredMembers.slice(start, end)
    }
  },
  watch: {
    searchQuery() {
      this.currentPage = 1
    },
    selectedState() {
      this.currentPage = 1
    },
    selectedSpecialization() {
      this.currentPage = 1
    },
    sortBy() {
      this.currentPage = 1
    }
  },
  async mounted() {
    await this.fetchMembers()
  },
  methods: {
    async fetchMembers() {
      try {
        this.loading = true
        this.error = null
        
        const response = await fetch('https://bhrcdata.online/backend/api.php/members?type=state')
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        
        const data = await response.json()
        
        if (data.success) {
          this.members = data.data || []
          this.extractFilterOptions()
        } else {
          throw new Error(data.message || 'Failed to fetch state members')
        }
      } catch (error) {
        console.error('Error fetching state members:', error)
        this.error = error.message
      } finally {
        this.loading = false
      }
    },
    extractFilterOptions() {
      // Extract unique states
      this.states = [...new Set(this.members.map(member => member.state))].sort()
      
      // Extract unique specializations
      this.specializations = [...new Set(this.members.map(member => member.specialization))].sort()
    },
    openMemberModal(member) {
      this.selectedMember = member
      document.body.style.overflow = 'hidden'
    },
    closeMemberModal() {
      this.selectedMember = null
      document.body.style.overflow = 'auto'
    },
    clearFilters() {
      this.searchQuery = ''
      this.selectedState = ''
      this.selectedSpecialization = ''
      this.currentPage = 1
    },
    handleImageError(event) {
      event.target.src = '/images/default-avatar.jpg'
    },
    formatDate(dateString) {
      if (!dateString) return 'Not specified'
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }
  }
}
</script>

<style scoped>
/* Hero Section */
.hero-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 80px 0;
  text-align: center;
}

.hero-title {
  font-size: 3rem;
  font-weight: 700;
  margin-bottom: 1rem;
}

.hero-subtitle {
  font-size: 1.2rem;
  margin-bottom: 2rem;
  opacity: 0.9;
}

.hero-stats {
  display: flex;
  justify-content: center;
  gap: 3rem;
  margin-top: 2rem;
}

.stat-item {
  text-align: center;
}

.stat-number {
  display: block;
  font-size: 2.5rem;
  font-weight: 700;
  color: #ffd700;
}

.stat-label {
  font-size: 0.9rem;
  opacity: 0.8;
}

/* Filters Section */
.filters-section {
  background: #f8f9fa;
  padding: 2rem 0;
  border-bottom: 1px solid #e9ecef;
}

.filters-wrapper {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 300px;
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 1rem;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #666;
}

.filter-group {
  min-width: 150px;
}

.filter-select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 1rem;
  background: white;
}

/* Loading and Error States */
.loading-section,
.error-section {
  padding: 4rem 0;
  text-align: center;
}

.loading-spinner i {
  font-size: 2rem;
  color: #667eea;
  margin-bottom: 1rem;
}

.error-message {
  color: #dc3545;
}

.error-message i {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.retry-btn {
  background: #dc3545;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  margin-top: 1rem;
}

/* Members Section */
.members-section {
  padding: 3rem 0;
}

.results-info {
  margin-bottom: 2rem;
  color: #666;
}

.members-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 2rem;
  margin-bottom: 3rem;
}

.member-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: all 0.3s ease;
  cursor: pointer;
}

.member-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.member-photo {
  position: relative;
  height: 200px;
  overflow: hidden;
}

.member-photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.member-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: #28a745;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
}

.member-info {
  padding: 1.5rem;
}

.member-name {
  font-size: 1.3rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #333;
}

.member-designation {
  color: #667eea;
  font-weight: 500;
  margin-bottom: 1rem;
}

.member-details {
  margin-bottom: 1.5rem;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
  color: #666;
}

.detail-item i {
  width: 16px;
  color: #667eea;
}

.member-contact {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.contact-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #f8f9fa;
  border: 1px solid #ddd;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #667eea;
  text-decoration: none;
  transition: all 0.3s ease;
}

.contact-btn:hover {
  background: #667eea;
  color: white;
}

.view-profile-btn {
  flex: 1;
  background: #667eea;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.9rem;
  margin-left: 0.5rem;
}

/* No Results */
.no-results {
  text-align: center;
  padding: 4rem 0;
  color: #666;
}

.no-results i {
  font-size: 4rem;
  margin-bottom: 1rem;
  color: #ddd;
}

.clear-filters-btn {
  background: #667eea;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  margin-top: 1rem;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 3rem;
}

.page-btn {
  background: white;
  border: 1px solid #ddd;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.page-btn:hover:not(:disabled) {
  background: #667eea;
  color: white;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  font-weight: 500;
  color: #333;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 2rem;
}

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
}

.modal-close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #666;
  z-index: 1001;
}

.modal-header {
  display: flex;
  gap: 2rem;
  padding: 2rem;
  border-bottom: 1px solid #eee;
}

.member-photo-large {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
}

.member-photo-large img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.member-info-large h2 {
  margin-bottom: 0.5rem;
  color: #333;
}

.designation {
  color: #667eea;
  font-weight: 500;
  margin-bottom: 1rem;
}

.member-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
}

.state-badge {
  background: #28a745;
  color: white;
}

.experience-badge {
  background: #ffc107;
  color: #333;
}

.modal-body {
  padding: 2rem;
}

.info-section {
  margin-bottom: 2rem;
}

.info-section h3 {
  margin-bottom: 1rem;
  color: #333;
  border-bottom: 2px solid #667eea;
  padding-bottom: 0.5rem;
}

.info-grid {
  display: grid;
  gap: 1rem;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.info-item i {
  width: 20px;
  color: #667eea;
}

.bio-text {
  line-height: 1.6;
  color: #555;
}

.achievements-list {
  list-style: none;
  padding: 0;
}

.achievements-list li {
  padding: 0.5rem 0;
  border-bottom: 1px solid #eee;
  position: relative;
  padding-left: 1.5rem;
}

.achievements-list li:before {
  content: '✓';
  position: absolute;
  left: 0;
  color: #28a745;
  font-weight: bold;
}

.modal-footer {
  padding: 2rem;
  border-top: 1px solid #eee;
  display: flex;
  gap: 1rem;
}

.contact-btn.primary {
  background: #667eea;
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
  justify-content: center;
}

.contact-btn.secondary {
  background: #28a745;
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
  justify-content: center;
}

/* Responsive Design */
@media (max-width: 768px) {
  .hero-title {
    font-size: 2rem;
  }
  
  .hero-stats {
    gap: 2rem;
  }
  
  .filters-wrapper {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-box {
    min-width: auto;
  }
  
  .members-grid {
    grid-template-columns: 1fr;
  }
  
  .modal-header {
    flex-direction: column;
    text-align: center;
  }
  
  .member-photo-large {
    align-self: center;
  }
  
  .modal-footer {
    flex-direction: column;
  }
}
</style>