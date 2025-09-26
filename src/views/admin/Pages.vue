<template>
  <div class="admin-pages">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-content">
        <h1><i class="fas fa-file-alt"></i> Pages Management</h1>
        <p class="text-muted">Manage website content pages and static content</p>
      </div>
      <div class="header-actions">
        <button @click="showAddModal = true" class="btn btn-primary">
          <i class="fas fa-plus"></i>
          Add New Page
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-section">
      <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="stats-card total">
            <div class="stats-icon">
              <i class="fas fa-file-alt"></i>
            </div>
            <div class="stats-content">
              <h3>{{ stats.totalPages }}</h3>
              <p>Total Pages</p>
              <small class="text-success">
                {{ stats.publishedPages }} published
              </small>
            </div>
          </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="stats-card drafts">
            <div class="stats-icon">
              <i class="fas fa-edit"></i>
            </div>
            <div class="stats-content">
              <h3>{{ stats.draftPages }}</h3>
              <p>Draft Pages</p>
              <small class="text-warning">
                Pending review
              </small>
            </div>
          </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="stats-card views">
            <div class="stats-icon">
              <i class="fas fa-eye"></i>
            </div>
            <div class="stats-content">
              <h3>{{ formatNumber(stats.totalViews) }}</h3>
              <p>Total Views</p>
              <small class="text-info">
                This month: {{ formatNumber(stats.monthlyViews) }}
              </small>
            </div>
          </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
          <div class="stats-card updated">
            <div class="stats-icon">
              <i class="fas fa-clock"></i>
            </div>
            <div class="stats-content">
              <h3>{{ stats.recentUpdates }}</h3>
              <p>Recent Updates</p>
              <small class="text-primary">
                Last 7 days
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-section">
      <div class="row align-items-center">
        <div class="col-md-4">
          <div class="search-box">
            <i class="fas fa-search"></i>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Search pages..." 
              class="form-control"
            >
          </div>
        </div>
        <div class="col-md-8">
          <div class="filters">
            <select v-model="filterStatus" class="form-control me-2">
              <option value="">All Status</option>
              <option value="published">Published</option>
              <option value="draft">Draft</option>
              <option value="archived">Archived</option>
            </select>
            
            <select v-model="filterType" class="form-control me-2">
              <option value="">All Types</option>
              <option value="static">Static Page</option>
              <option value="dynamic">Dynamic Page</option>
              <option value="landing">Landing Page</option>
              <option value="blog">Blog Post</option>
            </select>
            
            <button @click="clearFilters" class="btn btn-outline-secondary">
              <i class="fas fa-times"></i>
              Clear
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pages Table -->
    <div class="table-section">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>
                <input type="checkbox" v-model="selectAll" @change="toggleSelectAll">
              </th>
              <th @click="sortBy('title')" class="sortable">
                Title
                <i class="fas fa-sort" :class="getSortIcon('title')"></i>
              </th>
              <th @click="sortBy('slug')" class="sortable">
                Slug/URL
                <i class="fas fa-sort" :class="getSortIcon('slug')"></i>
              </th>
              <th @click="sortBy('type')" class="sortable">
                Type
                <i class="fas fa-sort" :class="getSortIcon('type')"></i>
              </th>
              <th @click="sortBy('status')" class="sortable">
                Status
                <i class="fas fa-sort" :class="getSortIcon('status')"></i>
              </th>
              <th @click="sortBy('views')" class="sortable">
                Views
                <i class="fas fa-sort" :class="getSortIcon('views')"></i>
              </th>
              <th @click="sortBy('updated_at')" class="sortable">
                Last Updated
                <i class="fas fa-sort" :class="getSortIcon('updated_at')"></i>
              </th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="text-center py-4">
                <div class="loading-spinner"></div>
                Loading pages...
              </td>
            </tr>
            <tr v-else-if="paginatedPages.length === 0">
              <td colspan="8" class="text-center py-4">
                <div class="empty-state">
                  <i class="fas fa-file-alt text-muted"></i>
                  <p class="text-muted mt-2">No pages found</p>
                </div>
              </td>
            </tr>
            <tr v-else v-for="page in paginatedPages" :key="page.id">
              <td>
                <input type="checkbox" v-model="selectedPages" :value="page.id">
              </td>
              <td>
                <div class="page-info">
                  <strong>{{ page.title }}</strong>
                  <br>
                  <small class="text-muted">{{ truncateText(page.description, 50) }}</small>
                </div>
              </td>
              <td>
                <code class="page-slug">{{ page.slug }}</code>
              </td>
              <td>
                <span class="badge" :class="getTypeClass(page.type)">
                  {{ getTypeLabel(page.type) }}
                </span>
              </td>
              <td>
                <span class="badge" :class="getStatusClass(page.status)">
                  {{ getStatusLabel(page.status) }}
                </span>
              </td>
              <td>
                <div class="views-info">
                  <strong>{{ formatNumber(page.views) }}</strong>
                  <br>
                  <small class="text-muted">
                    <i class="fas fa-chart-line"></i>
                    +{{ page.monthly_views }} this month
                  </small>
                </div>
              </td>
              <td>
                <div class="date-info">
                  {{ formatDate(page.updated_at) }}
                  <br>
                  <small class="text-muted">by {{ page.updated_by }}</small>
                </div>
              </td>
              <td>
                <div class="action-buttons">
                  <button @click="viewPage(page)" class="btn btn-sm btn-outline-primary" title="View Page">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button @click="editPage(page)" class="btn btn-sm btn-outline-warning" title="Edit">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button @click="duplicatePage(page)" class="btn btn-sm btn-outline-info" title="Duplicate">
                    <i class="fas fa-copy"></i>
                  </button>
                  <button @click="deletePage(page)" class="btn btn-sm btn-outline-danger" title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div class="pagination-section" v-if="totalPages > 1">
      <nav>
        <ul class="pagination justify-content-center">
          <li class="page-item" :class="{ disabled: currentPage === 1 }">
            <button class="page-link" @click="changePage(currentPage - 1)" :disabled="currentPage === 1">
              <i class="fas fa-chevron-left"></i>
            </button>
          </li>
          
          <li v-for="page in visiblePages" :key="page" class="page-item" :class="{ active: page === currentPage }">
            <button class="page-link" @click="changePage(page)">{{ page }}</button>
          </li>
          
          <li class="page-item" :class="{ disabled: currentPage === totalPages }">
            <button class="page-link" @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages">
              <i class="fas fa-chevron-right"></i>
            </button>
          </li>
        </ul>
      </nav>
    </div>

    <!-- Add/Edit Page Modal -->
    <div class="modal fade" :class="{ show: showAddModal }" :style="{ display: showAddModal ? 'block' : 'none' }" v-if="showAddModal">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-plus"></i>
              {{ editingPage ? 'Edit Page' : 'Add New Page' }}
            </h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="savePage">
              <div class="row">
                <div class="col-md-8">
                  <div class="mb-3">
                    <label class="form-label">Page Title *</label>
                    <input 
                      type="text" 
                      v-model="pageForm.title" 
                      class="form-control" 
                      :class="{ 'is-invalid': errors.title }"
                      @input="generateSlug"
                      required
                    >
                    <div v-if="errors.title" class="invalid-feedback">{{ errors.title }}</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="mb-3">
                    <label class="form-label">Status *</label>
                    <select 
                      v-model="pageForm.status" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.status }"
                      required
                    >
                      <option value="">Select Status</option>
                      <option value="published">Published</option>
                      <option value="draft">Draft</option>
                      <option value="archived">Archived</option>
                    </select>
                    <div v-if="errors.status" class="invalid-feedback">{{ errors.status }}</div>
                  </div>
                </div>
              </div>
              
              <div class="row">
                <div class="col-md-8">
                  <div class="mb-3">
                    <label class="form-label">URL Slug *</label>
                    <input 
                      type="text" 
                      v-model="pageForm.slug" 
                      class="form-control" 
                      :class="{ 'is-invalid': errors.slug }"
                      required
                    >
                    <small class="form-text text-muted">URL: /{{ pageForm.slug }}</small>
                    <div v-if="errors.slug" class="invalid-feedback">{{ errors.slug }}</div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="mb-3">
                    <label class="form-label">Page Type *</label>
                    <select 
                      v-model="pageForm.type" 
                      class="form-control"
                      :class="{ 'is-invalid': errors.type }"
                      required
                    >
                      <option value="">Select Type</option>
                      <option value="static">Static Page</option>
                      <option value="dynamic">Dynamic Page</option>
                      <option value="landing">Landing Page</option>
                      <option value="blog">Blog Post</option>
                    </select>
                    <div v-if="errors.type" class="invalid-feedback">{{ errors.type }}</div>
                  </div>
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Meta Description</label>
                <textarea 
                  v-model="pageForm.description" 
                  class="form-control" 
                  rows="2"
                  placeholder="Brief description for SEO..."
                ></textarea>
                <small class="form-text text-muted">{{ pageForm.description.length }}/160 characters</small>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Page Content *</label>
                <div class="editor-toolbar">
                  <button type="button" class="btn btn-sm btn-outline-secondary" @click="insertText('**', '**')" title="Bold">
                    <i class="fas fa-bold"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" @click="insertText('*', '*')" title="Italic">
                    <i class="fas fa-italic"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" @click="insertText('# ', '')" title="Heading">
                    <i class="fas fa-heading"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" @click="insertText('[', '](url)')" title="Link">
                    <i class="fas fa-link"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" @click="insertText('![alt](', ')')" title="Image">
                    <i class="fas fa-image"></i>
                  </button>
                </div>
                <textarea 
                  ref="contentEditor"
                  v-model="pageForm.content" 
                  class="form-control content-editor" 
                  rows="15"
                  :class="{ 'is-invalid': errors.content }"
                  placeholder="Enter page content (Markdown supported)..."
                  required
                ></textarea>
                <div v-if="errors.content" class="invalid-feedback">{{ errors.content }}</div>
              </div>
              
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Meta Keywords</label>
                    <input 
                      type="text" 
                      v-model="pageForm.keywords" 
                      class="form-control"
                      placeholder="keyword1, keyword2, keyword3..."
                    >
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Featured Image URL</label>
                    <input 
                      type="url" 
                      v-model="pageForm.featured_image" 
                      class="form-control"
                      placeholder="https://example.com/image.jpg"
                    >
                  </div>
                </div>
              </div>
              
              <div class="row">
                <div class="col-md-4">
                  <div class="form-check">
                    <input 
                      type="checkbox" 
                      v-model="pageForm.show_in_menu" 
                      class="form-check-input"
                      id="showInMenu"
                    >
                    <label class="form-check-label" for="showInMenu">
                      Show in Navigation Menu
                    </label>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-check">
                    <input 
                      type="checkbox" 
                      v-model="pageForm.allow_comments" 
                      class="form-check-input"
                      id="allowComments"
                    >
                    <label class="form-check-label" for="allowComments">
                      Allow Comments
                    </label>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-check">
                    <input 
                      type="checkbox" 
                      v-model="pageForm.is_featured" 
                      class="form-check-input"
                      id="isFeatured"
                    >
                    <label class="form-check-label" for="isFeatured">
                      Featured Page
                    </label>
                  </div>
                </div>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeModal">Cancel</button>
            <button type="button" class="btn btn-outline-primary" @click="previewPage" v-if="pageForm.content">
              <i class="fas fa-eye"></i>
              Preview
            </button>
            <button type="button" class="btn btn-primary" @click="savePage" :disabled="saving">
              <span v-if="saving">
                <i class="fas fa-spinner fa-spin"></i>
                Saving...
              </span>
              <span v-else>
                <i class="fas fa-save"></i>
                {{ editingPage ? 'Update' : 'Save' }} Page
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AdminPages',
  data() {
    return {
      loading: false,
      saving: false,
      showAddModal: false,
      editingPage: null,
      searchQuery: '',
      filterStatus: '',
      filterType: '',
      sortField: 'updated_at',
      sortDirection: 'desc',
      currentPage: 1,
      itemsPerPage: 10,
      selectAll: false,
      selectedPages: [],
      
      stats: {
        totalPages: 24,
        publishedPages: 18,
        draftPages: 6,
        totalViews: 45230,
        monthlyViews: 8940,
        recentUpdates: 5
      },
      
      pages: [
        {
          id: 1,
          title: 'About Us',
          slug: 'about-us',
          description: 'Learn about our organization, mission, and values',
          type: 'static',
          status: 'published',
          content: '# About Us\n\nWe are dedicated to serving the community...',
          views: 5420,
          monthly_views: 890,
          keywords: 'about, organization, mission',
          featured_image: '/assets/images/about-banner.jpg',
          show_in_menu: true,
          allow_comments: false,
          is_featured: true,
          updated_at: '2024-01-15 10:30:00',
          updated_by: 'Admin'
        },
        {
          id: 2,
          title: 'Our Services',
          slug: 'services',
          description: 'Comprehensive list of services we provide',
          type: 'static',
          status: 'published',
          content: '# Our Services\n\nWe offer various services...',
          views: 3240,
          monthly_views: 520,
          keywords: 'services, help, support',
          featured_image: '/assets/images/services-banner.jpg',
          show_in_menu: true,
          allow_comments: true,
          is_featured: false,
          updated_at: '2024-01-14 14:20:00',
          updated_by: 'Editor'
        },
        {
          id: 3,
          title: 'Contact Information',
          slug: 'contact',
          description: 'Get in touch with us through various channels',
          type: 'static',
          status: 'published',
          content: '# Contact Us\n\nReach out to us...',
          views: 2180,
          monthly_views: 340,
          keywords: 'contact, phone, email, address',
          featured_image: '/assets/images/contact-banner.jpg',
          show_in_menu: true,
          allow_comments: false,
          is_featured: false,
          updated_at: '2024-01-13 09:15:00',
          updated_by: 'Admin'
        }
      ],
      
      pageForm: {
        title: '',
        slug: '',
        description: '',
        type: '',
        status: '',
        content: '',
        keywords: '',
        featured_image: '',
        show_in_menu: false,
        allow_comments: false,
        is_featured: false
      },
      
      errors: {}
    }
  },
  
  computed: {
    filteredPages() {
      let filtered = [...this.pages]
      
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(page => 
          page.title.toLowerCase().includes(query) ||
          page.description.toLowerCase().includes(query) ||
          page.slug.toLowerCase().includes(query) ||
          page.content.toLowerCase().includes(query)
        )
      }
      
      if (this.filterStatus) {
        filtered = filtered.filter(page => page.status === this.filterStatus)
      }
      
      if (this.filterType) {
        filtered = filtered.filter(page => page.type === this.filterType)
      }
      
      // Sort
      filtered.sort((a, b) => {
        let aVal = a[this.sortField]
        let bVal = b[this.sortField]
        
        if (this.sortField === 'views') {
          aVal = parseInt(aVal)
          bVal = parseInt(bVal)
        }
        
        if (this.sortDirection === 'asc') {
          return aVal > bVal ? 1 : -1
        } else {
          return aVal < bVal ? 1 : -1
        }
      })
      
      return filtered
    },
    
    paginatedPages() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredPages.slice(start, end)
    },
    
    totalPages() {
      return Math.ceil(this.filteredPages.length / this.itemsPerPage)
    },
    
    visiblePages() {
      const pages = []
      const total = this.totalPages
      const current = this.currentPage
      
      if (total <= 7) {
        for (let i = 1; i <= total; i++) {
          pages.push(i)
        }
      } else {
        if (current <= 4) {
          for (let i = 1; i <= 5; i++) {
            pages.push(i)
          }
          pages.push('...')
          pages.push(total)
        } else if (current >= total - 3) {
          pages.push(1)
          pages.push('...')
          for (let i = total - 4; i <= total; i++) {
            pages.push(i)
          }
        } else {
          pages.push(1)
          pages.push('...')
          for (let i = current - 1; i <= current + 1; i++) {
            pages.push(i)
          }
          pages.push('...')
          pages.push(total)
        }
      }
      
      return pages
    }
  },
  
  methods: {
    // Utility methods
    formatNumber(num) {
      return new Intl.NumberFormat('en-IN').format(num)
    },
    
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('en-IN')
    },
    
    truncateText(text, length) {
      return text.length > length ? text.substring(0, length) + '...' : text
    },
    
    getStatusClass(status) {
      const classes = {
        published: 'badge-success',
        draft: 'badge-warning',
        archived: 'badge-secondary'
      }
      return classes[status] || 'badge-secondary'
    },
    
    getStatusLabel(status) {
      const labels = {
        published: 'Published',
        draft: 'Draft',
        archived: 'Archived'
      }
      return labels[status] || status
    },
    
    getTypeClass(type) {
      const classes = {
        static: 'badge-primary',
        dynamic: 'badge-info',
        landing: 'badge-success',
        blog: 'badge-warning'
      }
      return classes[type] || 'badge-secondary'
    },
    
    getTypeLabel(type) {
      const labels = {
        static: 'Static',
        dynamic: 'Dynamic',
        landing: 'Landing',
        blog: 'Blog'
      }
      return labels[type] || type
    },
    
    getSortIcon(field) {
      if (this.sortField !== field) return ''
      return this.sortDirection === 'asc' ? 'fa-sort-up' : 'fa-sort-down'
    },
    
    // Actions
    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc'
      } else {
        this.sortField = field
        this.sortDirection = 'asc'
      }
    },
    
    changePage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page
      }
    },
    
    clearFilters() {
      this.searchQuery = ''
      this.filterStatus = ''
      this.filterType = ''
      this.currentPage = 1
    },
    
    toggleSelectAll() {
      if (this.selectAll) {
        this.selectedPages = this.paginatedPages.map(p => p.id)
      } else {
        this.selectedPages = []
      }
    },
    
    generateSlug() {
      if (this.pageForm.title && !this.editingPage) {
        this.pageForm.slug = this.pageForm.title
          .toLowerCase()
          .replace(/[^a-z0-9\s-]/g, '')
          .replace(/\s+/g, '-')
          .replace(/-+/g, '-')
          .trim('-')
      }
    },
    
    insertText(before, after) {
      const textarea = this.$refs.contentEditor
      const start = textarea.selectionStart
      const end = textarea.selectionEnd
      const selectedText = textarea.value.substring(start, end)
      const replacement = before + selectedText + after
      
      textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end)
      this.pageForm.content = textarea.value
      
      // Set cursor position
      const newPos = start + before.length + selectedText.length
      textarea.setSelectionRange(newPos, newPos)
      textarea.focus()
    },
    
    viewPage(page) {
      // Open page in new tab
      window.open(`/${page.slug}`, '_blank')
    },
    
    editPage(page) {
      this.editingPage = page
      this.pageForm = { ...page }
      this.showAddModal = true
    },
    
    duplicatePage(page) {
      const duplicated = {
        ...page,
        id: Date.now(),
        title: page.title + ' (Copy)',
        slug: page.slug + '-copy',
        status: 'draft',
        updated_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
        updated_by: 'Admin'
      }
      this.pages.unshift(duplicated)
    },
    
    deletePage(page) {
      if (confirm(`Are you sure you want to delete "${page.title}"?`)) {
        const index = this.pages.findIndex(p => p.id === page.id)
        if (index > -1) {
          this.pages.splice(index, 1)
        }
      }
    },
    
    previewPage() {
      // Implement preview functionality
      console.log('Preview page:', this.pageForm)
    },
    
    closeModal() {
      this.showAddModal = false
      this.editingPage = null
      this.resetForm()
    },
    
    resetForm() {
      this.pageForm = {
        title: '',
        slug: '',
        description: '',
        type: '',
        status: '',
        content: '',
        keywords: '',
        featured_image: '',
        show_in_menu: false,
        allow_comments: false,
        is_featured: false
      }
      this.errors = {}
    },
    
    validateForm() {
      this.errors = {}
      
      if (!this.pageForm.title.trim()) {
        this.errors.title = 'Page title is required'
      }
      
      if (!this.pageForm.slug.trim()) {
        this.errors.slug = 'URL slug is required'
      } else if (!/^[a-z0-9-]+$/.test(this.pageForm.slug)) {
        this.errors.slug = 'Slug can only contain lowercase letters, numbers, and hyphens'
      }
      
      if (!this.pageForm.type) {
        this.errors.type = 'Page type is required'
      }
      
      if (!this.pageForm.status) {
        this.errors.status = 'Status is required'
      }
      
      if (!this.pageForm.content.trim()) {
        this.errors.content = 'Page content is required'
      }
      
      // Check for duplicate slug
      const existingPage = this.pages.find(p => 
        p.slug === this.pageForm.slug && 
        (!this.editingPage || p.id !== this.editingPage.id)
      )
      if (existingPage) {
        this.errors.slug = 'This slug is already in use'
      }
      
      return Object.keys(this.errors).length === 0
    },
    
    savePage() {
      if (!this.validateForm()) return
      
      this.saving = true
      
      // Simulate API call
      setTimeout(() => {
        if (this.editingPage) {
          const index = this.pages.findIndex(p => p.id === this.editingPage.id)
          if (index > -1) {
            this.pages[index] = {
              ...this.pageForm,
              id: this.editingPage.id,
              updated_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
              updated_by: 'Admin'
            }
          }
        } else {
          const newPage = {
            ...this.pageForm,
            id: Date.now(),
            views: 0,
            monthly_views: 0,
            updated_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
            updated_by: 'Admin'
          }
          this.pages.unshift(newPage)
        }
        
        this.saving = false
        this.closeModal()
      }, 1000)
    }
  },
  
  mounted() {
    // Load pages data
    this.loadPages()
  }
}
</script>

<style scoped>
.admin-pages {
  padding: 20px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  padding-bottom: 20px;
  border-bottom: 1px solid #e9ecef;
}

.header-content h1 {
  margin: 0;
  color: #2c3e50;
  font-size: 28px;
  font-weight: 600;
}

.header-content p {
  margin: 5px 0 0 0;
  font-size: 14px;
}

.header-actions {
  display: flex;
  gap: 10px;
}

.stats-section {
  margin-bottom: 30px;
}

.stats-card {
  background: white;
  border-radius: 10px;
  padding: 25px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  display: flex;
  align-items: center;
  transition: transform 0.2s;
}

.stats-card:hover {
  transform: translateY(-2px);
}

.stats-card.total {
  border-left: 4px solid #007bff;
}

.stats-card.drafts {
  border-left: 4px solid #ffc107;
}

.stats-card.views {
  border-left: 4px solid #17a2b8;
}

.stats-card.updated {
  border-left: 4px solid #6f42c1;
}

.stats-icon {
  font-size: 40px;
  margin-right: 20px;
  opacity: 0.8;
}

.stats-card.total .stats-icon { color: #007bff; }
.stats-card.drafts .stats-icon { color: #ffc107; }
.stats-card.views .stats-icon { color: #17a2b8; }
.stats-card.updated .stats-icon { color: #6f42c1; }

.stats-content h3 {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: #2c3e50;
}

.stats-content p {
  margin: 5px 0;
  color: #6c757d;
  font-size: 14px;
}

.filters-section {
  background: white;
  padding: 20px;
  border-radius: 10px;
  margin-bottom: 20px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.search-box {
  position: relative;
}

.search-box i {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #6c757d;
}

.search-box input {
  padding-left: 45px;
}

.filters {
  display: flex;
  gap: 10px;
  align-items: center;
}

.table-section {
  background: white;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table {
  margin: 0;
}

.table th {
  background: #f8f9fa;
  border: none;
  font-weight: 600;
  color: #2c3e50;
  padding: 15px;
}

.table td {
  padding: 15px;
  vertical-align: middle;
  border-color: #e9ecef;
}

.sortable {
  cursor: pointer;
  user-select: none;
}

.sortable:hover {
  background: #e9ecef !important;
}

.page-info strong {
  color: #2c3e50;
}

.page-slug {
  background: #f8f9fa;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
}

.views-info strong {
  color: #17a2b8;
}

.date-info {
  font-size: 14px;
}

.action-buttons {
  display: flex;
  gap: 5px;
}

.action-buttons .btn {
  padding: 5px 8px;
}

.badge {
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 500;
  border-radius: 20px;
}

.badge-success { background: #d4edda; color: #155724; }
.badge-warning { background: #fff3cd; color: #856404; }
.badge-danger { background: #f8d7da; color: #721c24; }
.badge-info { background: #d1ecf1; color: #0c5460; }
.badge-primary { background: #cce7ff; color: #004085; }
.badge-secondary { background: #e2e3e5; color: #383d41; }

.pagination-section {
  margin-top: 20px;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #6c757d;
}

.empty-state i {
  font-size: 48px;
  margin-bottom: 15px;
}

.loading-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #f3f3f3;
  border-top: 2px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  display: inline-block;
  margin-right: 10px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.modal.show {
  background: rgba(0,0,0,0.5);
}

.modal-dialog {
  margin-top: 30px;
}

.modal-content {
  border: none;
  border-radius: 10px;
}

.modal-header {
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
  border-radius: 10px 10px 0 0;
}

.modal-title {
  color: #2c3e50;
  font-weight: 600;
}

.form-label {
  font-weight: 500;
  color: #2c3e50;
  margin-bottom: 5px;
}

.form-control:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.is-invalid {
  border-color: #dc3545;
}

.invalid-feedback {
  display: block;
}

.editor-toolbar {
  margin-bottom: 10px;
  padding: 10px;
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  border-bottom: none;
  border-radius: 5px 5px 0 0;
}

.editor-toolbar .btn {
  margin-right: 5px;
}

.content-editor {
  border-radius: 0 0 5px 5px;
  font-family: 'Courier New', monospace;
  font-size: 14px;
}

.form-check {
  margin-bottom: 10px;
}

.form-check-label {
  font-weight: 500;
  color: #2c3e50;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 15px;
  }
  
  .filters {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filters .form-control {
    margin-bottom: 10px;
  }
  
  .action-buttons {
    flex-direction: column;
  }
  
  .stats-card {
    margin-bottom: 15px;
  }
  
  .modal-dialog {
    margin: 10px;
  }
}
</style>