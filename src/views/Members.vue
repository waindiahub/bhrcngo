<template>
  <div class="members-page">
    <!-- Hero Section -->
    <section class="hero-section">
      <div class="container">
        <div class="hero-content">
          <h1 class="hero-title">Our Members</h1>
          <p class="hero-subtitle">
            Meet the dedicated individuals who work tirelessly to protect and promote human rights across Bihar
          </p>
        </div>
      </div>
    </section>

    <!-- Filters Section -->
    <section class="filters-section">
      <div class="container">
        <div class="filters-container">
          <div class="search-box">
            <i class="fas fa-search"></i>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Search members by name, designation, or location..."
              @input="handleSearch"
            />
            <button v-if="searchQuery" @click="clearSearch" class="clear-search">
              <i class="fas fa-times"></i>
            </button>
          </div>
          
          <div class="filter-tabs">
            <button 
              v-for="category in memberCategories" 
              :key="category.value"
              :class="['filter-tab', { active: activeCategory === category.value }]"
              @click="setActiveCategory(category.value)"
            >
              {{ category.label }}
              <span class="count" v-if="category.count">({{ category.count }})</span>
            </button>
          </div>
          
          <div class="filter-controls">
            <select v-model="sortBy" @change="handleSort" class="sort-select">
              <option value="name">Sort by Name</option>
              <option value="designation">Sort by Designation</option>
              <option value="location">Sort by Location</option>
              <option value="joined_date">Sort by Join Date</option>
            </select>
            
            <select v-model="locationFilter" @change="handleLocationFilter" class="location-select">
              <option value="">All Locations</option>
              <option v-for="location in locations" :key="location" :value="location">
                {{ location }}
              </option>
            </select>
          </div>
        </div>
      </div>
    </section>

    <!-- Loading State -->
    <div v-if="loading" class="loading-container">
      <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Loading members...</p>
      </div>
    </div>

    <!-- Members Grid -->
    <section v-else class="members-section">
      <div class="container">
        <!-- Results Info -->
        <div class="results-info">
          <p>
            Showing {{ filteredMembers.length }} of {{ totalMembers }} members
            <span v-if="searchQuery || activeCategory !== 'all' || locationFilter">
              (filtered)
            </span>
          </p>
        </div>

        <!-- No Results -->
        <div v-if="filteredMembers.length === 0" class="no-results">
          <div class="no-results-content">
            <i class="fas fa-users"></i>
            <h3>No Members Found</h3>
            <p v-if="searchQuery">
              No members match your search for "{{ searchQuery }}"
            </p>
            <p v-else-if="activeCategory !== 'all'">
              No members found in the {{ getCategoryLabel(activeCategory) }} category
            </p>
            <p v-else>
              No members found with the current filters
            </p>
            <button @click="clearAllFilters" class="btn btn-primary">
              Clear All Filters
            </button>
          </div>
        </div>

        <!-- Members Grid -->
        <div v-else class="members-grid">
          <div 
            v-for="member in paginatedMembers" 
            :key="member.id"
            class="member-card"
            @click="viewMemberDetails(member)"
          >
            <div class="member-image">
              <img 
                :src="member.photo || '/assets/images/placeholder-avatar.svg'" 
                :alt="member.name"
                @error="handleImageError"
              />
              <div class="member-status" :class="member.status">
                <i :class="getStatusIcon(member.status)"></i>
              </div>
            </div>
            
            <div class="member-info">
              <h3 class="member-name">{{ member.name }}</h3>
              <p class="member-designation">{{ member.designation }}</p>
              <p class="member-location">
                <i class="fas fa-map-marker-alt"></i>
                {{ member.location }}
              </p>
              
              <div class="member-details">
                <div class="detail-item" v-if="member.experience_years">
                  <i class="fas fa-calendar"></i>
                  <span>{{ member.experience_years }} years experience</span>
                </div>
                <div class="detail-item" v-if="member.specialization">
                  <i class="fas fa-star"></i>
                  <span>{{ member.specialization }}</span>
                </div>
                <div class="detail-item" v-if="member.email">
                  <i class="fas fa-envelope"></i>
                  <span>{{ member.email }}</span>
                </div>
                <div class="detail-item" v-if="member.phone">
                  <i class="fas fa-phone"></i>
                  <span>{{ member.phone }}</span>
                </div>
              </div>
              
              <div class="member-actions">
                <button class="btn btn-outline btn-sm" @click.stop="contactMember(member)">
                  <i class="fas fa-envelope"></i>
                  Contact
                </button>
                <button class="btn btn-primary btn-sm" @click.stop="viewProfile(member)">
                  <i class="fas fa-user"></i>
                  View Profile
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="pagination-container">
          <div class="pagination">
            <button 
              :disabled="currentPage === 1" 
              @click="goToPage(currentPage - 1)"
              class="pagination-btn"
            >
              <i class="fas fa-chevron-left"></i>
              Previous
            </button>
            
            <div class="pagination-numbers">
              <button 
                v-for="page in visiblePages" 
                :key="page"
                :class="['pagination-number', { active: page === currentPage }]"
                @click="goToPage(page)"
              >
                {{ page }}
              </button>
            </div>
            
            <button 
              :disabled="currentPage === totalPages" 
              @click="goToPage(currentPage + 1)"
              class="pagination-btn"
            >
              Next
              <i class="fas fa-chevron-right"></i>
            </button>
          </div>
          
          <div class="pagination-info">
            Page {{ currentPage }} of {{ totalPages }}
          </div>
        </div>
      </div>
    </section>

    <!-- Member Detail Modal -->
    <div v-if="selectedMember" class="member-modal" @click="closeMemberModal">
      <div class="member-modal-content" @click.stop>
        <button class="modal-close" @click="closeMemberModal">
          <i class="fas fa-times"></i>
        </button>
        
        <div class="modal-header">
          <div class="modal-image">
            <img 
              :src="selectedMember.photo || '/assets/images/placeholder-avatar.svg'" 
              :alt="selectedMember.name"
            />
          </div>
          <div class="modal-info">
            <h2>{{ selectedMember.name }}</h2>
            <p class="modal-designation">{{ selectedMember.designation }}</p>
            <p class="modal-location">
              <i class="fas fa-map-marker-alt"></i>
              {{ selectedMember.location }}
            </p>
          </div>
        </div>
        
        <div class="modal-body">
          <div class="modal-section" v-if="selectedMember.bio">
            <h3>Biography</h3>
            <p>{{ selectedMember.bio }}</p>
          </div>
          
          <div class="modal-section" v-if="selectedMember.education">
            <h3>Education</h3>
            <p>{{ selectedMember.education }}</p>
          </div>
          
          <div class="modal-section" v-if="selectedMember.achievements">
            <h3>Key Achievements</h3>
            <ul>
              <li v-for="achievement in selectedMember.achievements" :key="achievement">
                {{ achievement }}
              </li>
            </ul>
          </div>
          
          <div class="modal-section">
            <h3>Contact Information</h3>
            <div class="contact-info">
              <div v-if="selectedMember.email" class="contact-item">
                <i class="fas fa-envelope"></i>
                <a :href="`mailto:${selectedMember.email}`">{{ selectedMember.email }}</a>
              </div>
              <div v-if="selectedMember.phone" class="contact-item">
                <i class="fas fa-phone"></i>
                <a :href="`tel:${selectedMember.phone}`">{{ selectedMember.phone }}</a>
              </div>
              <div v-if="selectedMember.office_address" class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <span>{{ selectedMember.office_address }}</span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button class="btn btn-outline" @click="contactMember(selectedMember)">
            <i class="fas fa-envelope"></i>
            Send Message
          </button>
          <button class="btn btn-primary" @click="closeMemberModal">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '@/utils/api'

const router = useRouter()

// Reactive data
const loading = ref(true)
const members = ref([])
const searchQuery = ref('')
const activeCategory = ref('all')
const sortBy = ref('name')
const locationFilter = ref('')
const currentPage = ref(1)
const itemsPerPage = ref(12)
const selectedMember = ref(null)

// Member categories
const memberCategories = ref([
  { label: 'All Members', value: 'all', count: 0 },
  { label: 'Chairperson', value: 'chairperson', count: 0 },
  { label: 'Members', value: 'member', count: 0 },
  { label: 'Secretary', value: 'secretary', count: 0 },
  { label: 'Officers', value: 'officer', count: 0 },
  { label: 'Staff', value: 'staff', count: 0 }
])

// Computed properties
const totalMembers = computed(() => members.value.length)

const locations = computed(() => {
  const uniqueLocations = [...new Set(members.value.map(member => member.location))]
  return uniqueLocations.sort()
})

const filteredMembers = computed(() => {
  let filtered = members.value

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(member => 
      member.name.toLowerCase().includes(query) ||
      member.designation.toLowerCase().includes(query) ||
      member.location.toLowerCase().includes(query) ||
      (member.specialization && member.specialization.toLowerCase().includes(query))
    )
  }

  // Filter by category
  if (activeCategory.value !== 'all') {
    filtered = filtered.filter(member => member.category === activeCategory.value)
  }

  // Filter by location
  if (locationFilter.value) {
    filtered = filtered.filter(member => member.location === locationFilter.value)
  }

  // Sort
  filtered.sort((a, b) => {
    switch (sortBy.value) {
      case 'name':
        return a.name.localeCompare(b.name)
      case 'designation':
        return a.designation.localeCompare(b.designation)
      case 'location':
        return a.location.localeCompare(b.location)
      case 'joined_date':
        return new Date(b.joined_date) - new Date(a.joined_date)
      default:
        return 0
    }
  })

  return filtered
})

const totalPages = computed(() => Math.ceil(filteredMembers.value.length / itemsPerPage.value))

const paginatedMembers = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredMembers.value.slice(start, end)
})

const visiblePages = computed(() => {
  const pages = []
  const total = totalPages.value
  const current = currentPage.value
  
  if (total <= 7) {
    for (let i = 1; i <= total; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) pages.push(i)
      pages.push('...')
      pages.push(total)
    } else if (current >= total - 3) {
      pages.push(1)
      pages.push('...')
      for (let i = total - 4; i <= total; i++) pages.push(i)
    } else {
      pages.push(1)
      pages.push('...')
      for (let i = current - 1; i <= current + 1; i++) pages.push(i)
      pages.push('...')
      pages.push(total)
    }
  }
  
  return pages
})

// Methods
const fetchMembers = async () => {
  try {
    loading.value = true
    const response = await api.get('members')
    
    if (response.data.success) {
      members.value = response.data.data || []
    } else {
      console.error('Failed to fetch members:', response.data.message)
      members.value = []
    }
    
    // Update category counts
    updateCategoryCounts()
    
  } catch (error) {
    console.error('Error fetching members:', error)
    members.value = []
  } finally {
    loading.value = false
  }
}

const updateCategoryCounts = () => {
  memberCategories.value.forEach(category => {
    if (category.value === 'all') {
      category.count = members.value.length
    } else {
      category.count = members.value.filter(member => member.category === category.value).length
    }
  })
}

const handleSearch = () => {
  currentPage.value = 1
}

const clearSearch = () => {
  searchQuery.value = ''
  currentPage.value = 1
}

const setActiveCategory = (category) => {
  activeCategory.value = category
  currentPage.value = 1
}

const handleSort = () => {
  currentPage.value = 1
}

const handleLocationFilter = () => {
  currentPage.value = 1
}

const clearAllFilters = () => {
  searchQuery.value = ''
  activeCategory.value = 'all'
  locationFilter.value = ''
  sortBy.value = 'name'
  currentPage.value = 1
}

const getCategoryLabel = (value) => {
  const category = memberCategories.value.find(cat => cat.value === value)
  return category ? category.label : value
}

const getStatusIcon = (status) => {
  switch (status) {
    case 'active': return 'fas fa-check-circle'
    case 'inactive': return 'fas fa-pause-circle'
    case 'retired': return 'fas fa-clock'
    default: return 'fas fa-user'
  }
}

const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const viewMemberDetails = (member) => {
  selectedMember.value = member
}

const closeMemberModal = () => {
  selectedMember.value = null
}

const viewProfile = (member) => {
  router.push(`/members/${member.id}`)
}

const contactMember = (member) => {
  if (member.email) {
    window.location.href = `mailto:${member.email}?subject=Inquiry from BHRC Website`
  } else {
    alert('Contact information not available for this member.')
  }
}

const handleImageError = (event) => {
  event.target.src = '/assets/images/placeholder-avatar.svg'
}

// Watch for filter changes
watch([searchQuery, activeCategory, locationFilter], () => {
  currentPage.value = 1
})

onMounted(() => {
  fetchMembers()
  document.title = 'Our Members - Bihar Human Rights Commission'
})
</script>

<style scoped>
.members-page {
  min-height: 100vh;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

/* Hero Section */
.hero-section {
  background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
  color: white;
  padding: 4rem 0;
  text-align: center;
}

.hero-title {
  font-size: 3rem;
  font-weight: bold;
  margin-bottom: 1rem;
}

.hero-subtitle {
  font-size: 1.2rem;
  opacity: 0.9;
  max-width: 600px;
  margin: 0 auto;
  line-height: 1.6;
}

/* Filters Section */
.filters-section {
  background: #f8f9fa;
  padding: 2rem 0;
  border-bottom: 1px solid #e9ecef;
}

.filters-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.search-box {
  position: relative;
  max-width: 500px;
  margin: 0 auto;
}

.search-box i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #666;
}

.search-box input {
  width: 100%;
  padding: 1rem 1rem 1rem 3rem;
  border: 2px solid #e9ecef;
  border-radius: 25px;
  font-size: 1rem;
  transition: border-color 0.3s ease;
}

.search-box input:focus {
  outline: none;
  border-color: #2a5298;
}

.clear-search {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #666;
  cursor: pointer;
  padding: 0.5rem;
}

.filter-tabs {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.filter-tab {
  padding: 0.75rem 1.5rem;
  border: 2px solid #e9ecef;
  background: white;
  border-radius: 25px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-tab:hover {
  border-color: #2a5298;
  color: #2a5298;
}

.filter-tab.active {
  background: #2a5298;
  color: white;
  border-color: #2a5298;
}

.count {
  font-size: 0.9rem;
  opacity: 0.8;
}

.filter-controls {
  display: flex;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.sort-select,
.location-select {
  padding: 0.75rem 1rem;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  min-width: 150px;
}

/* Loading */
.loading-container {
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

/* Members Section */
.members-section {
  padding: 3rem 0;
}

.results-info {
  margin-bottom: 2rem;
  text-align: center;
  color: #666;
}

.no-results {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 40vh;
}

.no-results-content {
  text-align: center;
  max-width: 400px;
}

.no-results-content i {
  font-size: 4rem;
  color: #ccc;
  margin-bottom: 1rem;
}

.no-results-content h3 {
  color: #2c3e50;
  margin-bottom: 1rem;
}

.no-results-content p {
  color: #666;
  margin-bottom: 2rem;
}

/* Members Grid */
.members-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 2rem;
}

.member-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
  transition: all 0.3s ease;
  cursor: pointer;
  position: relative;
}

.member-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.member-image {
  position: relative;
  width: 120px;
  height: 120px;
  margin: 0 auto 1.5rem;
}

.member-image img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid #f8f9fa;
}

.member-status {
  position: absolute;
  bottom: 5px;
  right: 5px;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.8rem;
}

.member-status.active {
  background: #28a745;
}

.member-status.inactive {
  background: #ffc107;
}

.member-status.retired {
  background: #6c757d;
}

.member-info {
  text-align: center;
}

.member-name {
  font-size: 1.3rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.member-designation {
  color: #2a5298;
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.member-location {
  color: #666;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.member-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  text-align: left;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: #666;
}

.detail-item i {
  color: #2a5298;
  width: 16px;
}

.member-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

/* Pagination */
.pagination-container {
  margin-top: 3rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.pagination {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.pagination-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border: 2px solid #e9ecef;
  background: white;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.pagination-btn:hover:not(:disabled) {
  border-color: #2a5298;
  color: #2a5298;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-numbers {
  display: flex;
  gap: 0.25rem;
}

.pagination-number {
  width: 40px;
  height: 40px;
  border: 2px solid #e9ecef;
  background: white;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pagination-number:hover {
  border-color: #2a5298;
  color: #2a5298;
}

.pagination-number.active {
  background: #2a5298;
  color: white;
  border-color: #2a5298;
}

.pagination-info {
  color: #666;
  font-size: 0.9rem;
}

/* Member Modal */
.member-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.member-modal-content {
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
  z-index: 1;
}

.modal-header {
  display: flex;
  gap: 2rem;
  padding: 2rem;
  border-bottom: 1px solid #f0f0f0;
}

.modal-image {
  width: 120px;
  height: 120px;
  flex-shrink: 0;
}

.modal-image img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

.modal-info h2 {
  font-size: 1.8rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.modal-designation {
  color: #2a5298;
  font-weight: 500;
  font-size: 1.1rem;
  margin-bottom: 0.5rem;
}

.modal-location {
  color: #666;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.modal-body {
  padding: 2rem;
}

.modal-section {
  margin-bottom: 2rem;
}

.modal-section:last-child {
  margin-bottom: 0;
}

.modal-section h3 {
  font-size: 1.2rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 1rem;
}

.modal-section p {
  color: #666;
  line-height: 1.6;
}

.modal-section ul {
  list-style: none;
  padding: 0;
}

.modal-section li {
  color: #666;
  margin-bottom: 0.5rem;
  padding-left: 1rem;
  position: relative;
}

.modal-section li::before {
  content: '•';
  color: #2a5298;
  position: absolute;
  left: 0;
}

.contact-info {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.contact-item i {
  color: #2a5298;
  width: 20px;
}

.contact-item a {
  color: #2a5298;
  text-decoration: none;
}

.contact-item a:hover {
  text-decoration: underline;
}

.modal-footer {
  padding: 2rem;
  border-top: 1px solid #f0f0f0;
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
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

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.9rem;
}

.btn-primary {
  background: #2a5298;
  color: white;
}

.btn-primary:hover {
  background: #1e3c72;
  transform: translateY(-2px);
}

.btn-outline {
  background: transparent;
  color: #2a5298;
  border: 2px solid #2a5298;
}

.btn-outline:hover {
  background: #2a5298;
  color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
  .hero-title {
    font-size: 2rem;
  }
  
  .filters-container {
    gap: 1rem;
  }
  
  .filter-tabs {
    justify-content: flex-start;
    overflow-x: auto;
    padding-bottom: 0.5rem;
  }
  
  .filter-controls {
    flex-direction: column;
    align-items: stretch;
  }
  
  .sort-select,
  .location-select {
    min-width: auto;
  }
  
  .members-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .member-card {
    padding: 1.5rem;
  }
  
  .member-actions {
    flex-direction: column;
  }
  
  .pagination {
    flex-wrap: wrap;
  }
  
  .pagination-numbers {
    order: -1;
    width: 100%;
    justify-content: center;
  }
  
  .modal-header {
    flex-direction: column;
    text-align: center;
  }
  
  .modal-footer {
    flex-direction: column;
  }
}
</style>