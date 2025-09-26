import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Layouts
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import AdminLayout from '@/layouts/AdminLayout.vue'
import MemberLayout from '@/layouts/MemberLayout.vue'

// Public Pages
import Home from '@/views/Home.vue'
import About from '@/views/About.vue'
import MissionVision from '@/views/MissionVision.vue'
import Founder from '@/views/Founder.vue'
import Activities from '@/views/Activities.vue'
import Events from '@/views/Events.vue'
import EventDetail from '@/views/EventDetail.vue'
import Gallery from '@/views/Gallery.vue'
import Members from '@/views/Members.vue'
// import NationalMembers from '@/views/members/NationalMembers.vue'
// import StateMembers from '@/views/members/StateMembers.vue'
import JoinUs from '@/views/JoinUs.vue'
import Complaint from '@/views/Complaint.vue'
import Donate from '@/views/Donate.vue'
import Login from '@/views/Login.vue'

// Member Area
import MemberDashboard from '@/views/member/Dashboard.vue'
import MemberProfile from '@/views/member/Profile.vue'
import MemberEvents from '@/views/member/Events.vue'
import MemberComplaints from '@/views/member/Complaints.vue'
import MemberDonations from '@/views/member/Donations.vue'
import MemberCertificates from '@/views/member/Certificates.vue'
import MemberSettings from '@/views/member/Settings.vue'

// Admin Area
import AdminDashboard from '@/views/admin/Dashboard.vue'
import AdminMembers from '@/views/admin/Members.vue'
import AdminAddMember from '@/views/admin/AddMember.vue'
import AdminComplaints from '@/views/admin/Complaints.vue'
import AdminEvents from '@/views/admin/Events.vue'
import AdminActivities from '@/views/admin/Activities.vue'
import AdminAddActivity from '@/views/admin/AddActivity.vue'
import AdminGallery from '@/views/admin/Gallery.vue'
import AdminPhotos from '@/views/admin/Photos.vue'
import AdminVideos from '@/views/admin/Videos.vue'
import AdminAddPhoto from '@/views/admin/AddPhoto.vue'
import AdminAddVideo from '@/views/admin/AddVideo.vue'
import AdminAlbums from '@/views/admin/Albums.vue'
import AdminDonations from '@/views/admin/Donations.vue'
import AdminApplications from '@/views/admin/Applications.vue'
import AdminAssociates from '@/views/admin/Associates.vue'
import AdminEnquiries from '@/views/admin/Enquiries.vue'
import AdminNewsletter from '@/views/admin/Newsletter.vue'
import AdminTestimonials from '@/views/admin/Testimonials.vue'
import AdminAchievements from '@/views/admin/Achievements.vue'
import AdminPages from '@/views/admin/Pages.vue'
import AdminReports from '@/views/admin/Reports.vue'
import AdminAnalytics from '@/views/admin/Analytics.vue'

const routes = [
  // Public Routes
  {
    path: '/',
    component: DefaultLayout,
    children: [
      { path: '', name: 'Home', component: Home },
      { path: 'about', name: 'About', component: About },
      { path: 'mission-vision', name: 'MissionVision', component: MissionVision },
      { path: 'founder', name: 'Founder', component: Founder },
      { path: 'activities', name: 'Activities', component: Activities },
      { path: 'events', name: 'Events', component: Events },
      { path: 'events/:id', name: 'EventDetail', component: EventDetail },
      { path: 'gallery', name: 'Gallery', component: Gallery },
      { path: 'members', name: 'Members', component: Members },
      // { path: 'members/national', name: 'NationalMembers', component: NationalMembers },
      // { path: 'members/state', name: 'StateMembers', component: StateMembers },
      { path: 'join-us', name: 'JoinUs', component: JoinUs },
      { path: 'complaint', name: 'Complaint', component: Complaint },
      { path: 'donate', name: 'Donate', component: Donate },
      { path: 'login', name: 'Login', component: Login }
    ]
  },

  // Member Routes
  {
    path: '/member',
    component: MemberLayout,
    meta: { requiresAuth: true, role: 'member' },
    children: [
      { path: '', redirect: '/member/dashboard' },
      { path: 'dashboard', name: 'MemberDashboard', component: MemberDashboard },
      { path: 'profile', name: 'MemberProfile', component: MemberProfile },
      { path: 'events', name: 'MemberEvents', component: MemberEvents },
      { path: 'complaints', name: 'MemberComplaints', component: MemberComplaints },
      { path: 'donations', name: 'MemberDonations', component: MemberDonations },
      { path: 'certificates', name: 'MemberCertificates', component: MemberCertificates },
      { path: 'settings', name: 'MemberSettings', component: MemberSettings }
    ]
  },

  // Admin Routes
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true, role: 'admin' },
    children: [
      { path: '', redirect: '/admin/dashboard' },
      { path: 'dashboard', name: 'AdminDashboard', component: AdminDashboard },
      { path: 'members', name: 'AdminMembers', component: AdminMembers },
      { path: 'members/add', name: 'AdminAddMember', component: AdminAddMember },
      { path: 'complaints', name: 'AdminComplaints', component: AdminComplaints },
      { path: 'events', name: 'AdminEvents', component: AdminEvents },
      { path: 'activities', name: 'AdminActivities', component: AdminActivities },
      { path: 'activities/add', name: 'AdminAddActivity', component: AdminAddActivity },
      { path: 'gallery', name: 'AdminGallery', component: AdminGallery },
      { path: 'gallery/photos', name: 'AdminPhotos', component: AdminPhotos },
      { path: 'gallery/videos', name: 'AdminVideos', component: AdminVideos },
      { path: 'gallery/photos/add', name: 'AdminAddPhoto', component: AdminAddPhoto },
      { path: 'gallery/videos/add', name: 'AdminAddVideo', component: AdminAddVideo },
      { path: 'gallery/albums', name: 'AdminAlbums', component: AdminAlbums },
      { path: 'donations', name: 'AdminDonations', component: AdminDonations },
      { path: 'applications', name: 'AdminApplications', component: AdminApplications },
      { path: 'associates', name: 'AdminAssociates', component: AdminAssociates },
      { path: 'enquiries', name: 'AdminEnquiries', component: AdminEnquiries },
      { path: 'newsletter', name: 'AdminNewsletter', component: AdminNewsletter },
      { path: 'testimonials', name: 'AdminTestimonials', component: AdminTestimonials },
      { path: 'achievements', name: 'AdminAchievements', component: AdminAchievements },
      { path: 'pages', name: 'AdminPages', component: AdminPages },
      { path: 'reports', name: 'AdminReports', component: AdminReports },
      { path: 'analytics', name: 'AdminAnalytics', component: AdminAnalytics }
    ]
  },

  // Catch all route - 404
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/NotFound.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else {
      return { top: 0 }
    }
  }
})

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  // Initialize authentication if not already done
  if (!authStore.isInitialized) {
    try {
      await authStore.initializeAuth()
    } catch (error) {
      console.error('Authentication initialization failed:', error)
      // Clear any invalid tokens and continue
      authStore.clearAuth()
    }
  }
  
  // Check if route requires authentication
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!authStore.isAuthenticated) {
      // Redirect to login page
      next({
        name: 'Login',
        query: { redirect: to.fullPath }
      })
      return
    }

    // Check role-based access
    if (to.meta.role && !authStore.hasRole(to.meta.role)) {
      // Redirect to appropriate dashboard based on user role
      if (authStore.user?.role === 'admin' || authStore.user?.role === 'moderator') {
        next('/admin/dashboard')
      } else if (authStore.user?.role === 'member' || authStore.user?.role === 'volunteer') {
        next('/member/dashboard')
      } else {
        next('/')
      }
      return
    }
  }

  // If user is authenticated and trying to access login page, redirect to dashboard
  if (to.name === 'Login' && authStore.isAuthenticated) {
    if (authStore.user?.role === 'admin' || authStore.user?.role === 'moderator') {
      next('/admin/dashboard')
    } else if (authStore.user?.role === 'member' || authStore.user?.role === 'volunteer') {
      next('/member/dashboard')
    } else {
      next('/')
    }
    return
  }

  next()
})

export default router