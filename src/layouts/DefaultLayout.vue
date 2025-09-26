<template>
  <div class="default-layout">
    <!-- Header -->
    <header class="site-header">
      <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
          <!-- Logo -->
          <router-link to="/" class="navbar-brand">
            <img src="@/assets/images/logo.png" alt="BHRC Logo" class="logo">
            <span class="brand-text">BHRC</span>
          </router-link>

          <!-- Mobile Toggle -->
          <button 
            class="navbar-toggler" 
            type="button" 
            data-bs-toggle="collapse" 
            data-bs-target="#navbarNav"
          >
            <span class="navbar-toggler-icon"></span>
          </button>

          <!-- Navigation Menu -->
          <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
              <li class="nav-item">
                <router-link to="/" class="nav-link">Home</router-link>
              </li>
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                  About Us
                </a>
                <ul class="dropdown-menu">
                  <li><router-link to="/about" class="dropdown-item">About BHRC</router-link></li>
                  <li><router-link to="/mission-vision" class="dropdown-item">Mission & Vision</router-link></li>
                  <li><router-link to="/founder" class="dropdown-item">Our Founder</router-link></li>
                </ul>
              </li>
              <li class="nav-item">
                <router-link to="/activities" class="nav-link">Activities</router-link>
              </li>
              <li class="nav-item">
                <router-link to="/events" class="nav-link">Events</router-link>
              </li>
              <li class="nav-item">
                <router-link to="/gallery" class="nav-link">Gallery</router-link>
              </li>
              <li class="nav-item">
                <router-link to="/members" class="nav-link">Members</router-link>
              </li>
              <li class="nav-item">
                <router-link to="/services" class="nav-link">Services</router-link>
              </li>
              <li class="nav-item">
                <router-link to="/contact" class="nav-link">Contact</router-link>
              </li>
              <li class="nav-item">
                <router-link to="/login" class="btn btn-primary nav-btn">Login</router-link>
              </li>
            </ul>
          </div>
        </div>
      </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">
      <router-view />
    </main>

    <!-- Footer -->
    <footer class="site-footer">
      <div class="container">
        <div class="row">
          <!-- About Section -->
          <div class="col-lg-4 col-md-6 mb-4">
            <div class="footer-section">
              <h5 class="footer-title">Bihar Human Rights Commission</h5>
              <p class="footer-text">
                Dedicated to protecting and promoting human rights for all individuals in Bihar. 
                We work tirelessly to ensure justice, equality, and dignity for everyone.
              </p>
              <div class="social-links">
                <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
              </div>
            </div>
          </div>

          <!-- Quick Links -->
          <div class="col-lg-2 col-md-6 mb-4">
            <div class="footer-section">
              <h6 class="footer-subtitle">Quick Links</h6>
              <ul class="footer-links">
                <li><router-link to="/about">About Us</router-link></li>
                <li><router-link to="/activities">Activities</router-link></li>
                <li><router-link to="/events">Events</router-link></li>
                <li><router-link to="/gallery">Gallery</router-link></li>
                <li><router-link to="/members">Members</router-link></li>
              </ul>
            </div>
          </div>

          <!-- Services -->
          <div class="col-lg-2 col-md-6 mb-4">
            <div class="footer-section">
              <h6 class="footer-subtitle">Services</h6>
              <ul class="footer-links">
                <li><router-link to="/complaint">File Complaint</router-link></li>
                <li><router-link to="/join-us">Join Us</router-link></li>
                <li><router-link to="/donate">Donate</router-link></li>
                <li><router-link to="/services">Legal Aid</router-link></li>
                <li><router-link to="/contact">Contact Us</router-link></li>
              </ul>
            </div>
          </div>

          <!-- Contact Info -->
          <div class="col-lg-4 col-md-6 mb-4">
            <div class="footer-section">
              <h6 class="footer-subtitle">Contact Information</h6>
              <div class="contact-info">
                <div class="contact-item">
                  <i class="fas fa-map-marker-alt"></i>
                  <span>123 Human Rights Street, Patna, Bihar 800001</span>
                </div>
                <div class="contact-item">
                  <i class="fas fa-phone"></i>
                  <span>+91 612 123 4567</span>
                </div>
                <div class="contact-item">
                  <i class="fas fa-envelope"></i>
                  <span>info@bhrc.org</span>
                </div>
                <div class="contact-item">
                  <i class="fas fa-clock"></i>
                  <span>Mon - Fri: 9:00 AM - 6:00 PM</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
          <div class="row align-items-center">
            <div class="col-md-6">
              <p class="copyright">
                &copy; {{ currentYear }} Bihar Human Rights Commission. All rights reserved.
              </p>
            </div>
            <div class="col-md-6">
              <div class="footer-bottom-links">
                <router-link to="/privacy">Privacy Policy</router-link>
                <router-link to="/terms">Terms of Service</router-link>
                <router-link to="/sitemap">Sitemap</router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script>
export default {
  name: 'DefaultLayout',
  computed: {
    currentYear() {
      return new Date().getFullYear()
    }
  },
  mounted() {
    // Initialize Bootstrap dropdowns
    if (typeof bootstrap !== 'undefined') {
      const dropdownElementList = document.querySelectorAll('.dropdown-toggle')
      dropdownElementList.forEach(dropdownToggleEl => {
        new bootstrap.Dropdown(dropdownToggleEl)
      })
    }
  }
}
</script>

<style scoped>
.default-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* Header Styles */
.site-header {
  background: white;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 1000;
}

.navbar {
  padding: 1rem 0;
}

.navbar-brand {
  display: flex;
  align-items: center;
  gap: 1rem;
  text-decoration: none;
  font-weight: 700;
  font-size: 1.5rem;
  color: #2c3e50;
}

.logo {
  width: 50px;
  height: 50px;
  object-fit: contain;
}

.brand-text {
  color: #667eea;
}

.navbar-nav {
  align-items: center;
  gap: 1rem;
}

.nav-link {
  color: #2c3e50;
  font-weight: 500;
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  transition: all 0.3s ease;
  text-decoration: none;
}

.nav-link:hover {
  color: #667eea;
  background-color: rgba(102, 126, 234, 0.1);
}

.nav-link.router-link-active {
  color: #667eea;
  background-color: rgba(102, 126, 234, 0.1);
}

.dropdown-menu {
  border: none;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  border-radius: 0.5rem;
  padding: 0.5rem 0;
}

.dropdown-item {
  padding: 0.5rem 1rem;
  color: #2c3e50;
  text-decoration: none;
  transition: all 0.3s ease;
}

.dropdown-item:hover {
  background-color: rgba(102, 126, 234, 0.1);
  color: #667eea;
}

.nav-btn {
  background: #667eea;
  border-color: #667eea;
  color: white;
  padding: 0.5rem 1.5rem;
  border-radius: 2rem;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.3s ease;
}

.nav-btn:hover {
  background: #5a6fd8;
  border-color: #5a6fd8;
  color: white;
  transform: translateY(-1px);
}

/* Main Content */
.main-content {
  flex: 1;
}

/* Footer Styles */
.site-footer {
  background: #2c3e50;
  color: white;
  padding: 3rem 0 1rem;
  margin-top: auto;
}

.footer-section {
  margin-bottom: 2rem;
}

.footer-title {
  font-size: 1.3rem;
  font-weight: 600;
  margin-bottom: 1rem;
  color: #667eea;
}

.footer-subtitle {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 1rem;
  color: white;
}

.footer-text {
  color: #bdc3c7;
  line-height: 1.6;
  margin-bottom: 1.5rem;
}

.social-links {
  display: flex;
  gap: 1rem;
}

.social-link {
  width: 40px;
  height: 40px;
  background: rgba(102, 126, 234, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #667eea;
  text-decoration: none;
  transition: all 0.3s ease;
}

.social-link:hover {
  background: #667eea;
  color: white;
  transform: translateY(-2px);
}

.footer-links {
  list-style: none;
  padding: 0;
}

.footer-links li {
  margin-bottom: 0.5rem;
}

.footer-links a {
  color: #bdc3c7;
  text-decoration: none;
  transition: color 0.3s ease;
}

.footer-links a:hover {
  color: #667eea;
}

.contact-info {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.contact-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  color: #bdc3c7;
}

.contact-item i {
  color: #667eea;
  width: 20px;
  margin-top: 0.2rem;
}

.footer-bottom {
  border-top: 1px solid #34495e;
  padding-top: 2rem;
  margin-top: 2rem;
}

.copyright {
  color: #bdc3c7;
  margin: 0;
}

.footer-bottom-links {
  display: flex;
  justify-content: flex-end;
  gap: 2rem;
}

.footer-bottom-links a {
  color: #bdc3c7;
  text-decoration: none;
  font-size: 0.9rem;
  transition: color 0.3s ease;
}

.footer-bottom-links a:hover {
  color: #667eea;
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .navbar-brand {
    font-size: 1.2rem;
  }
  
  .logo {
    width: 40px;
    height: 40px;
  }
  
  .navbar-nav {
    gap: 0;
    margin-top: 1rem;
  }
  
  .nav-link {
    padding: 0.75rem 1rem;
  }
  
  .nav-btn {
    margin-top: 1rem;
    text-align: center;
  }
  
  .footer-bottom-links {
    justify-content: center;
    margin-top: 1rem;
    flex-wrap: wrap;
    gap: 1rem;
  }
  
  .contact-item {
    align-items: center;
  }
}
</style>